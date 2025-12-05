<?php

namespace Database\Seeders\Customers;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Customers\FiscalResponsibility;

class FiscalResponsibilitySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        //
      $data = [
            [
                'description' => 'Ninguna',
                'integrationDataId' => 0,
            ],
            [
                'description' => 'Gran Contribuyente',
                'integrationDataId' => 5,
            ],
            [
                'description' => 'Autorretenedor',
                'integrationDataId' => 7,
            ],
            [
                'description' => 'Agente de retención en el impuesto sobre las ventas',
                'integrationDataId' => 12,
            ],
            [
                'description' => 'Régimen simple de tributación',
                'integrationDataId' => 114,
            ],
        ];

        foreach ($data as $item) {
            FiscalResponsibility::firstOrCreate(
                ['description' => $item['description']], // Busca por descripción
                $item
            );
        }
    }
    
}
