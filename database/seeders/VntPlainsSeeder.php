<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class VntPlainsSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $now = Carbon::now();

        $plans = [
            [
                'id' => 1,
                'name' => 'Plan Básico',
                'description' => 'Plan básico para pequeños emprendedores con funcionalidades esenciales',
                'status' => 1,
                'type' => 'Saas',
                'merchantTypeId' => 1,
                'warehoseQty' => 1,
                'usersQty' => 2,
                'storesQty' => 1,
                'create_at' => $now,
                'update_at' => $now,
            ],
            [
                'id' => 2,
                'name' => 'Plan Profesional',
                'description' => 'Plan profesional para pequeñas y medianas empresas con módulos avanzados',
                'status' => 1,
                'type' => 'Saas',
                'merchantTypeId' => 2,
                'warehoseQty' => 3,
                'usersQty' => 10,
                'storesQty' => 3,
                'create_at' => $now,
                'update_at' => $now,
            ],
            [
                'id' => 3,
                'name' => 'Plan Empresarial',
                'description' => 'Plan empresarial para grandes empresas con funcionalidades completas',
                'status' => 1,
                'type' => 'Saas',
                'merchantTypeId' => 3,
                'warehoseQty' => 10,
                'usersQty' => 50,
                'storesQty' => 10,
                'create_at' => $now,
                'update_at' => $now,
            ],
            [
                'id' => 4,
                'name' => 'Plan Personalizado',
                'description' => 'Solución personalizada vendida con implementación a medida',
                'status' => 1,
                'type' => 'Vendido',
                'merchantTypeId' => 4,
                'warehoseQty' => 99,
                'usersQty' => 999,
                'storesQty' => 99,
                'create_at' => $now,
                'update_at' => $now,
            ],
            [
                'id' => 5,
                'name' => 'Plan Tienda Online',
                'description' => 'Plan especializado para e-commerce con módulos de venta online',
                'status' => 1,
                'type' => 'Saas',
                'merchantTypeId' => 2,
                'warehoseQty' => 2,
                'usersQty' => 5,
                'storesQty' => 5,
                'create_at' => $now,
                'update_at' => $now,
            ],
        ];

        // Insertar en la base de datos central (rap)
        DB::connection('central')->table('vnt_plains')->upsert(
            $plans,
            ['id'],
            ['name', 'description', 'status', 'type', 'merchantTypeId', 'warehoseQty', 'usersQty', 'storesQty', 'update_at']
        );

        $this->command->info('✅ Planes de suscripción insertados en base de datos central');
    }
}
