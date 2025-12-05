<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class VntMerchantTypesSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $now = Carbon::now();

        $merchantTypes = [
            [
                'id' => 1,
                'name' => 'Emprendedor Individual',
                'description' => 'Persona natural que desarrolla actividad comercial básica',
                'version' => '1.0.0',
                'status' => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'id' => 2,
                'name' => 'Pequeña y Mediana Empresa',
                'description' => 'PYME con hasta 200 empleados y actividad comercial media',
                'version' => '1.0.0',
                'status' => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'id' => 3,
                'name' => 'Gran Empresa',
                'description' => 'Empresa de gran tamaño con más de 200 empleados',
                'version' => '1.0.0',
                'status' => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'id' => 4,
                'name' => 'Corporación',
                'description' => 'Corporación multinacional con requerimientos personalizados',
                'version' => '1.0.0',
                'status' => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ];

        // Insertar en la base de datos central (rap)
        DB::connection('central')->table('vnt_merchant_types')->upsert(
            $merchantTypes,
            ['id'],
            ['name', 'description', 'version', 'status', 'updated_at']
        );

        $this->command->info('✅ Tipos de comerciante insertados en base de datos central');
    }
}
