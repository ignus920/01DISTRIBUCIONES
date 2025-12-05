<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class CnfFiscalResponsabilitySeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $now = Carbon::now();

        $fiscalResponsabilities = [
            [
                'id' => 1,
                'description' => 'Ninguna',
                'integrationDataId' => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'id' => 2,
                'description' => 'Gran contribuyente',
                'integrationDataId' => 2,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'id' => 3,
                'description' => 'Autorretenedor',
                'integrationDataId' => 3,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'id' => 4,
                'description' => 'Agente de retención en el impuesto sobre las ventas',
                'integrationDataId' => 4,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'id' => 5,
                'description' => 'Régimen simple de tributación',
                'integrationDataId' => 5,
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ];

        // Insertar en la base de datos central (rap)
        DB::connection('central')->table('cnf_fiscal_responsabilities')->upsert(
            $fiscalResponsabilities,
            ['id'],
            ['description', 'integrationDataId', 'updated_at']
        );

        $this->command->info('✅ Responsabilidades fiscales insertadas en base de datos central');
    }
}
