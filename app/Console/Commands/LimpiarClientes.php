<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class LimpiarClientes extends Command
{
    protected $signature = 'clientes:limpiar
                            {--force : Ejecuta sin pedir confirmación}';

    protected $description = 'Elimina clientes de las 4 tablas. Conserva la empresa dueña y sus usuarios/contactos internos.';

    public function handle(): int
    {
        // ── 1. Obtener IDs de empresas protegidas (dueñas del sistema) ───
        // Los usuarios están vinculados a contactos de las bodegas del dueño
        // tenants.company_id → vnt_companies.id (empresa dueña)
        $ownerIds = DB::table('tenants')
            ->whereNotNull('company_id')
            ->where('company_id', '>', 0)
            ->pluck('company_id')
            ->unique()
            ->toArray();

        if (empty($ownerIds)) {
            $this->error("No se encontró company_id en la tabla tenants. Abortando por seguridad.");
            return 1;
        }

        // ── 2. Mostrar empresas protegidas ───────────────────────────────
        $owners = DB::table('vnt_companies')
            ->whereIn('id', $ownerIds)
            ->select('id', 'businessName', 'identification')
            ->get();

        $this->info("========================================");
        $this->info("  LIMPIEZA DE CLIENTES");
        $this->info("========================================");
        $this->newLine();
        $this->info("Empresas PROTEGIDAS (no se eliminarán):");
        foreach ($owners as $o) {
            $this->line("  • [ID {$o->id}] {$o->businessName} — {$o->identification}");
        }

        // Bodegas del dueño también se protegen (tienen los usuarios internos)
        $ownerWarehouseIds = DB::table('vnt_warehouses')
            ->whereIn('companyId', $ownerIds)
            ->pluck('id')
            ->toArray();

        $this->line("  → " . count($ownerWarehouseIds) . " sucursal(es) internas protegidas");

        // Usuarios vinculados a esas bodegas (via vnt_contacts)
        $linkedUsers = DB::table('users')
            ->join('vnt_contacts', 'users.contact_id', '=', 'vnt_contacts.id')
            ->whereIn('vnt_contacts.warehouseId', $ownerWarehouseIds)
            ->count();

        $this->line("  → {$linkedUsers} usuario(s) vinculado(s) que serán conservados");
        $this->newLine();

        // ── 3. Calcular lo que se eliminará ─────────────────────────────
        $clientIds = DB::table('vnt_companies')
            ->whereNotIn('id', $ownerIds)
            ->pluck('id')
            ->toArray();

        if (empty($clientIds)) {
            $this->info("No hay clientes para eliminar.");
            return 0;
        }

        $clientWarehouseIds = DB::table('vnt_warehouses')
            ->whereIn('companyId', $clientIds)
            ->pluck('id')
            ->toArray();

        // Todos los usuarios tienda (profile_id=17 son siempre clientes externos)
        $storeUserIds = DB::table('users')
            ->where('profile_id', 17)
            ->pluck('id')
            ->toArray();

        $counts = [
            'tat_companies_routes'      => DB::table('tat_companies_routes')
                ->whereIn('company_id', $clientIds)->count(),
            'user_tenants (tienda)'     => DB::table('user_tenants')
                ->whereIn('user_id', $storeUserIds)->count(),
            'users (tienda)'            => count($storeUserIds),
            'vnt_contacts (clientes)'   => DB::table('vnt_contacts')
                ->whereIn('warehouseId', $clientWarehouseIds)->count(),
            'vnt_warehouses (clientes)' => count($clientWarehouseIds),
            'vnt_customers'             => DB::table('vnt_customers')->count(),
            'vnt_companies (clientes)'  => count($clientIds),
        ];

        $this->warn("Registros que serán ELIMINADOS:");
        $this->table(
            ['Tabla', 'Registros'],
            array_map(fn($t, $c) => [$t, $c], array_keys($counts), $counts)
        );
        $this->newLine();

        // ── 4. Confirmación ──────────────────────────────────────────────
        if (!$this->option('force')) {
            if (!$this->confirm('¿Confirmas la eliminación? Esta acción no se puede deshacer.')) {
                $this->info("Operación cancelada.");
                return 0;
            }
        }

        // ── 5. Eliminación en orden correcto ─────────────────────────────
        DB::beginTransaction();
        try {

            // 5a. Rutas asignadas a empresas cliente
            $d1 = DB::table('tat_companies_routes')
                ->whereIn('company_id', $clientIds)
                ->delete();
            $this->line("  ✓ tat_companies_routes: {$d1} eliminados");

            // 5b. user_tenants de usuarios tienda
            $d2 = 0;
            if (!empty($storeUserIds)) {
                $d2 = DB::table('user_tenants')
                    ->whereIn('user_id', $storeUserIds)
                    ->delete();
            }
            $this->line("  ✓ user_tenants (tienda): {$d2} eliminados");

            // 5c. Usuarios tienda (profile_id = 17)
            $d3 = DB::table('users')
                ->where('profile_id', 17)
                ->delete();
            $this->line("  ✓ users (tienda): {$d3} eliminados");

            // 5d. Contactos de bodegas cliente (NO los internos del dueño)
            $d4 = 0;
            if (!empty($clientWarehouseIds)) {
                $d4 = DB::table('vnt_contacts')
                    ->whereIn('warehouseId', $clientWarehouseIds)
                    ->delete();
            }
            $this->line("  ✓ vnt_contacts (clientes): {$d4} eliminados");

            // 5e. Bodegas cliente
            $d5 = DB::table('vnt_warehouses')
                ->whereIn('companyId', $clientIds)
                ->delete();
            $this->line("  ✓ vnt_warehouses (clientes): {$d5} eliminados");

            // 5f. vnt_customers (todos son clientes, el dueño no tiene registro aquí)
            $d6 = DB::table('vnt_customers')->delete();
            $this->line("  ✓ vnt_customers: {$d6} eliminados");

            // 5g. Empresas cliente (conservando al dueño)
            $d7 = DB::table('vnt_companies')
                ->whereNotIn('id', $ownerIds)
                ->delete();
            $this->line("  ✓ vnt_companies (clientes): {$d7} eliminados");

            DB::commit();

            $this->newLine();
            $this->info("Limpieza completada. Total: " . ($d1 + $d2 + $d3 + $d4 + $d5 + $d6 + $d7) . " registros eliminados.");
            $this->info("Usuarios internos y empresa dueña: intactos.");

        } catch (\Exception $e) {
            DB::rollBack();
            $this->error("Error: " . $e->getMessage());
            $this->error("Todos los cambios fueron revertidos.");
            return 1;
        }

        return 0;
    }
}
