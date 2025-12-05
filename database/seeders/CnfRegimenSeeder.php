<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class CnfRegimenSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $now = Carbon::now();

        $Regimen = [
            [
                'id' => 1,
                'name' => 'Responsable de IVA',
                'status' => 1,
                'description' => 'COMMON_REGIME',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'id' => 2,
                'name' => 'No responsable de IVA',
                'status' => 1,
                'description' => 'SIMPLIFIED_REGIME',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'id' => 3,
                'name' => 'Impuesto Nacional al Consumo - INC',
                'status' => 1,
                'description' => 'NATIONAL_CONSUMPTION_TAX',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'id' => 4,
                'name' => 'No responsable de INC',
                'status' => 1,
                'description' => 'NOT_REPONSIBLE_FOR_CONSUMPTION',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'id' => 5,
                'name' => 'Responsable de IVA e INC',
                'status' => 1,
                'description' => 'INC_IVA_RESPONSIBLE',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'id' => 6,
                'name' => 'Régimen especial',
                'status' => 1,
                'description' => 'SPECIAL_REGIME',
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ];

        // Insertar en la base de datos central (rap)
        DB::connection('central')->table('cnf_regime')->upsert(
            $Regimen,
            ['id']
            
        );

        $this->command->info('✅ Responsabilidades fiscales insertadas en base de datos central');
    }
}
