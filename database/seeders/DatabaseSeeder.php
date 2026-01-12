<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Crear usuario administrador inicial
        User::create([
            'name' => 'Administrador',
            'email' => 'admin@casahogar.com',
            'password' => bcrypt('admin123'), // Contraseña: admin123
            'role' => 'admin',
        ]);

        // Crear un tesorero de ejemplo
        User::create([
            'name' => 'Juan Pérez',
            'email' => 'tesorero@casahogar.com',
            'password' => bcrypt('tesorero123'),
            'role' => 'tesorero',
        ]);

        // Llamar a los seeders
        $this->call([
            PermissionSeeder::class,
            ProductSeeder::class,
        ]);
    }
}
