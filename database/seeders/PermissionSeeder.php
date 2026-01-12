<?php

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\User;
use Illuminate\Database\Seeder;

class PermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Crear permisos
        $manageInventory = Permission::create([
            'name' => 'manage_inventory',
            'description' => 'Gestionar inventario (crear, editar y eliminar productos)'
        ]);

        $downloadReports = Permission::create([
            'name' => 'download_reports',
            'description' => 'Generar y descargar reportes en PDF'
        ]);

        $injectCapital = Permission::create([
            'name' => 'inject_capital',
            'description' => 'Inyectar capital extraordinario en el registro diario'
        ]);

        // Asignar todos los permisos al administrador
        $admin = User::where('email', 'admin@casahogar.com')->first();
        if ($admin) {
            $admin->permissions()->attach([
                $manageInventory->id,
                $downloadReports->id,
                $injectCapital->id
            ]);
        }

        $this->command->info('✓ Permisos creados y asignados al administrador');
    }
}
