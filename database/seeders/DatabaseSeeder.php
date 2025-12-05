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
        $this->command->info('🌱 Iniciando seeders del sistema...');

        // Ejecutar seeders para la base de datos central (rap)
        $this->command->info('📊 Ejecutando seeders para base de datos central (rap)...');
        $this->call([
            CentralDatabaseSeeder::class,
        ]);

        // Crear usuario de prueba local
        // $this->command->info('👤 Creando usuario de prueba...');
        // User::factory()->create([
        //     'name' => 'Test User',
        //     'email' => 'test@example.com',
        // ]);

        $this->command->info('✅ Todos los seeders completados exitosamente');
    }
}
