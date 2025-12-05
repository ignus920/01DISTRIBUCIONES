<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class CnfTypeIdentificationSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $now = Carbon::now();

        $typeIdentifications = [
            [
                'id' => 1,
                'name' => 'Cédula de Ciudadanía',
                'acronym' => 'CC',
                'status' => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'id' => 2,
                'name' => 'Número de Identificación Tributaria',
                'acronym' => 'NIT',
                'status' => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'id' => 3,
                'name' => 'Cédula de Extranjería',
                'acronym' => 'CE',
                'status' => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'id' => 4,
                'name' => 'Pasaporte',
                'acronym' => 'PA',
                'status' => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'id' => 5,
                'name' => 'Tarjeta de Identidad',
                'acronym' => 'TI',
                'status' => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'id' => 6,
                'name' => 'Registro Civil',
                'acronym' => 'RC',
                'status' => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ];

        // Insertar en la base de datos central (rap)
        DB::connection('central')->table('cnf_type_identifications')->upsert(
            $typeIdentifications,
            ['id'],
            ['name', 'acronym', 'status', 'updated_at']
        );

        $this->command->info('✅ Tipos de identificación insertados en base de datos central');
    }
}
