<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class VntModulsSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $now = Carbon::now();

        $modules = [
            [
                'id' => 1,
                'name' => 'Ventas',
                'description' => 'Módulo para gestión de ventas, facturación y clientes',
                'version' => '1.0.0',
                'migration' => 'create_sales_tables',
                'dev_hours' => 120,
                'status' => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'id' => 2,
                'name' => 'Inventario',
                'description' => 'Módulo para gestión de inventarios, productos y almacenes',
                'version' => '1.0.0',
                'migration' => 'create_inventory_tables',
                'dev_hours' => 80,
                'status' => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'id' => 3,
                'name' => 'Contabilidad',
                'description' => 'Módulo para gestión contable, reportes financieros y libros',
                'version' => '1.0.0',
                'migration' => 'create_accounting_tables',
                'dev_hours' => 160,
                'status' => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'id' => 4,
                'name' => 'Compras',
                'description' => 'Módulo para gestión de compras, proveedores y cotizaciones',
                'version' => '1.0.0',
                'migration' => 'create_purchases_tables',
                'dev_hours' => 100,
                'status' => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'id' => 5,
                'name' => 'Recursos Humanos',
                'description' => 'Módulo para gestión de empleados, nómina y recursos humanos',
                'version' => '1.0.0',
                'migration' => 'create_hr_tables',
                'dev_hours' => 140,
                'status' => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'id' => 6,
                'name' => 'CRM',
                'description' => 'Módulo de gestión de relaciones con clientes',
                'version' => '1.0.0',
                'migration' => 'create_crm_tables',
                'dev_hours' => 90,
                'status' => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'id' => 7,
                'name' => 'Reportes',
                'description' => 'Módulo para generación de reportes y dashboards',
                'version' => '1.0.0',
                'migration' => 'create_reports_tables',
                'dev_hours' => 60,
                'status' => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'id' => 8,
                'name' => 'E-commerce',
                'description' => 'Módulo para tienda online y comercio electrónico',
                'version' => '1.0.0',
                'migration' => 'create_ecommerce_tables',
                'dev_hours' => 200,
                'status' => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ];

        // Insertar en la base de datos central (rap)
        DB::connection('central')->table('vnt_moduls')->upsert(
            $modules,
            ['id'],
            ['name', 'description', 'version', 'migration', 'dev_hours', 'status', 'updated_at']
        );

        $this->command->info('✅ Módulos del sistema insertados en base de datos central');
    }
}
