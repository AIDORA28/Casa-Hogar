<?php

namespace Database\Seeders;

use App\Models\Nurse;
use Illuminate\Database\Seeder;

class NurseSeeder extends Seeder
{
    /**
     * Run the database seeder.
     */
    public function run(): void
    {
        $nurses = [
            ['name' => 'María García', 'is_active' => true],
            ['name' => 'Ana Martínez', 'is_active' => true],
            ['name' => 'Rosa López', 'is_active' => true],
            ['name' => 'Carmen Rodríguez', 'is_active' => true],
        ];

        foreach ($nurses as $nurseData) {
            Nurse::updateOrCreate(
                ['name' => $nurseData['name']], // Buscar por nombre
                $nurseData // Datos a insertar/actualizar
            );
        }
    }
}
