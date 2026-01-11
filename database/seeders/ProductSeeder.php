<?php

namespace Database\Seeders;

use App\Models\Product;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class ProductSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Productos típicos de la Casa Hogar
        $products = [
            [
                'name' => 'Keke de Piña',
                'description' => 'Delicioso keke de piña casero, esponjoso y húmedo',
                'stock' => 20,
                'base_price' => 15.00,
            ],
            [
                'name' => 'Chifles',
                'description' => 'Plátano verde frito en rodajas crujientes',
                'stock' => 50,
                'base_price' => 5.00,
            ],
            [
                'name' => 'Pan con Pollo',
                'description' => 'Pan artesanal relleno con pollo deshilachado y ensalada',
                'stock' => 15,
                'base_price' => 12.00,
            ],
            [
                'name' => 'Alfajores',
                'description' => 'Galletas rellenas con manjar blanco',
                'stock' => 30,
                'base_price' => 8.00,
            ],
            [
                'name' => 'Empanadas de Carne',
                'description' => 'Empanadas crujientes rellenas de carne molida',
                'stock' => 25,
                'base_price' => 6.00,
            ],
            [
                'name' => 'Mazamorra Morada',
                'description' => 'Postre tradicional peruano de maíz morado',
                'stock' => 10,
                'base_price' => 7.00,
            ],
            [
                'name' => 'Suspiro a la Limeña',
                'description' => 'Postre cremoso con merengue de vino oporto',
                'stock' => 12,
                'base_price' => 10.00,
            ],
        ];

        foreach ($products as $product) {
            Product::create($product);
        }
    }
}
