<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CentralDatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Run the database seeds para la base de datos central (rap).
     */
    public function run(): void
    {
        // Usar la conexión central (base de datos 'rap')
        $connection = DB::connection('central');

        // Verificar conexión
        if (!$connection->getPdo()) {
            $this->command->error('No se pudo conectar a la base de datos central (rap)');
            return;
        }

        $this->command->info('🚀 Iniciando seeders para base de datos central (rap)...');

        // Ejecutar seeders en orden de dependencias
        $this->call([
            CnfTypeIdentificationSeeder::class,
            CnfFiscalResponsabilitySeeder::class,
            UsrProfilesSeeder::class,
            VntModulsSeeder::class,
            VntMerchantTypesSeeder::class,
            VntPlainsSeeder::class,
        ]);

        $this->command->info('✅ Seeders completados para base de datos central (rap)');
    }
}
