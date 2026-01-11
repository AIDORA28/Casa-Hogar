# Guía de Deployment - Casa Hogar System

## Preparación para Producción

### 1. Configuración de Entorno

#### `.env.production` (Ejemplo)
```env
APP_NAME="Casa Hogar System"
APP_ENV=production
APP_DEBUG=false
APP_URL=https://casahogar.tudominio.com

# PostgreSQL Producción
DB_CONNECTION=pgsql
DB_HOST=tu_servidor_postgresql.com
DB_PORT=5432
DB_DATABASE=casa_hogar_prod
DB_USERNAME=usuario_prod
DB_PASSWORD=contraseña_super_segura_aquí

# Sanctum
SANCTUM_STATEFUL_DOMAINS=casahogar.tudominio.com
SESSION_DOMAIN=.tudominio.com
```

### 2. Optimización de Laravel

```bash
# Cachear configuración
php artisan config:cache

# Cachear rutas
php artisan route:cache

# Cachear vistas
php artisan view:cache

# Optimizar autoload de Composer
composer install --optimize-autoloader --no-dev

# Compilar assets para producción
npm run build
```

### 3. Deployment en VPS (DigitalOcean/Linode/Vultr)

#### Requisitos del Servidor
- Ubuntu 22.04 LTS
- PHP 8.2+  
- PostgreSQL 15
- Nginx
- Node.js 18+
- Composer

#### Instalación de Dependencias

```bash
# Actualizar sistema
sudo apt update && sudo apt upgrade -y

# Instalar PHP 8.2
sudo add-apt-repository ppa:ondrej/php
sudo apt install php8.2-fpm php8.2-pgsql php8.2-mbstring php8.2-xml php8.2-curl php8.2-zip

# Instalar PostgreSQL
sudo apt install postgresql postgresql-contrib

# Instalar Nginx
sudo apt install nginx

# Instalar Composer
curl -sS https://getcomposer.org/installer | php
sudo mv composer.phar /usr/local/bin/composer

# Instalar Node.js
curl -fsSL https://deb.nodesource.com/setup_18.x | sudo -E bash -
sudo apt install -y nodejs
```

#### Configurar PostgreSQL

```bash
# Entrar a PostgreSQL
sudo -u postgres psql

# Crear base de datos y usuario
CREATE DATABASE casa_hogar_prod;
CREATE USER usuario_prod WITH ENCRYPTED PASSWORD 'contraseña_segura';
GRANT ALL PRIVILEGES ON DATABASE casa_hogar_prod TO usuario_prod;
\q
```

#### Clonar Proyecto

```bash
cd /var/www
git clone https://github.com/tu-usuario/casa-hogar.git
cd casa-hogar

# Instalar dependencias
composer install --optimize-autoloader --no-dev
npm install
npm run build

# Configurar permisos
sudo chown -R www-data:www-data storage bootstrap/cache
sudo chmod -R 775 storage bootstrap/cache

# Copiar .env
cp .env.example .env
nano .env  # Editar con datos de producción

# Generar key
php artisan key:generate

# Migrar base de datos
php artisan migrate --force

# Seedear (solo primera vez)
php artisan db:seed --force
```

#### Configuración de Nginx

```bash
sudo nano /etc/nginx/sites-available/casahogar
```

```nginx
server {
    listen 80;
    server_name casahogar.tudominio.com;
    root /var/www/casa-hogar/public;

    add_header X-Frame-Options "SAMEORIGIN";
    add_header X-Content-Type-Options "nosniff";

    index index.php;

    charset utf-8;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location = /favicon.ico { access_log off; log_not_found off; }
    location = /robots.txt  { access_log off; log_not_found off; }

    error_page 404 /index.php;

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.2-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }
}
```

```bash
# Activar sitio
sudo ln -s /etc/nginx/sites-available/casahogar /etc/nginx/sites-enabled/
sudo nginx -t
sudo systemctl restart nginx
```

#### SSL con Let's Encrypt

```bash
# Instalar Certbot
sudo apt install certbot python3-certbot-nginx

# Obtener certificado
sudo certbot --nginx -d casahogar.tudominio.com

# Renovación automática (ya viene configurado)
sudo certbot renew --dry-run
```

### 4. Deployment en Railway.app (Alternativa Simple)

Railway es más simple y maneja PostgreSQL automáticamente.

#### Pasos:

1. Crear cuenta en [railway.app](https://railway.app)
2. Crear nuevo proyecto
3. Agregar PostgreSQL desde marketplace
4. Agregar servicio desde GitHub
5. Configurar variables de entorno:
   ```
   APP_ENV=production
   APP_DEBUG=false
   DB_CONNECTION=pgsql
   DB_HOST=${{Postgres.PGHOST}}
   DB_PORT=${{Postgres.PGPORT}}
   DB_DATABASE=${{Postgres.PGDATABASE}}
   DB_USERNAME=${{Postgres.PGUSER}}
   DB_PASSWORD=${{Postgres.PGPASSWORD}}
   ```
6. Deploy automático en cada push

### 5. Mantenimiento

#### Logs
```bash
# Ver logs de Laravel
tail -f storage/logs/laravel.log

# Ver logs de Nginx
sudo tail -f /var/log/nginx/error.log
```

#### Backup de Base de Datos

```bash
# Crear backup
pg_dump -U usuario_prod casa_hogar_prod > backup_$(date +%Y%m%d).sql

# Restaurar backup
psql -U usuario_prod casa_hogar_prod < backup_20260109.sql
```

#### Actualizar Código

```bash
cd /var/www/casa-hogar
git pull origin main
composer install --optimize-autoloader --no-dev
npm install && npm run build
php artisan migrate --force
php artisan config:cache
php artisan route:cache
php artisan view:cache
sudo systemctl restart php8.2-fpm
```

### 6. Seguridad

```bash
# Firewall
sudo ufw allow 22/tcp
sudo ufw allow 80/tcp
sudo ufw allow 443/tcp
sudo ufw enable

# Fail2ban
sudo apt install fail2ban
sudo systemctl enable fail2ban
```

### 7. Monitoreo

```bash
# Instalar supervisor para queue workers (si usas colas)
sudo apt install supervisor

sudo nano /etc/supervisor/conf.d/casahogar.conf
```

```ini
[program:casahogar-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /var/www/casa-hogar/artisan queue:work --sleep=3 --tries=3
autostart=true
autorestart=true
user=www-data
numprocs=1
redirect_stderr=true
stdout_logfile=/var/www/casa-hogar/storage/logs/worker.log
```

---

## Checklist de Deployment

- [ ] Variables de entorno configuradas
- [ ] Migraciones ejecutadas
- [ ] Assets compilados (`npm run build`)
- [ ] Permisos correctos (storage, cache)
- [ ] Cache de Laravel activado
- [ ] SSL configurado
- [ ] Firewall activado
- [ ] Backup configurado
- [ ] Logs monitoreados
- [ ] Probar funcionalidad completa

---

## URLs Importantes

- **Sitio Web**: https://casahogar.tudominio.com
- **Panel Admin**: https://casahogar.tudominio.com/dashboard
- **API**: https://casahogar.tudominio.com/api/*

---

## Soporte

Para reportar problemas o solicitar ayuda, revisar:
- `storage/logs/laravel.log`
- `/var/log/nginx/error.log`
- Activity Logs en el panel admin
