<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Models\Auth\Tenant;
use App\Services\Tenant\TenantManager;

class CopyProductsToClientJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 600; // 10 minutos de timeout

    /**
     * Create a new job instance.
     */
    public function __construct(
        public $companyId,
        public $tenantId
    ) {}

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        Log::info('Iniciando CopyProductsToClientJob', [
            'company_id' => $this->companyId,
            'tenant_id' => $this->tenantId
        ]);

        try {
            // Inicializar el tenant si es necesario
            if ($this->tenantId) {
                $tenant = Tenant::find($this->tenantId);
                if ($tenant) {
                    $tenantManager = app(TenantManager::class);
                    $tenantManager->setConnection($tenant);
                    tenancy()->initialize($tenant);
                    Log::info('Tenant inicializado correctamente en el Job');
                } else {
                    Log::error('Tenant no encontrado en el Job', ['tenant_id' => $this->tenantId]);
                    // Aún así intentamos continuar por si acaso
                }
            }

            $this->copyProductsToClient($this->companyId);

        } catch (\Exception $e) {
            Log::error('Error crítico en CopyProductsToClientJob', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            // Opcional: Notificar fallo
        }
    }

    private function copyProductsToClient($companyId)
    {
        try {
            // Obtener todos los productos activos de la distribuidora
            $distributorProducts = DB::table('inv_items')
                ->leftJoin('inv_categories', 'inv_items.categoryId', '=', 'inv_categories.id')
                ->where('inv_items.status', 1) // Solo productos activos
                ->whereNull('inv_items.deleted_at') // No eliminados
                ->select(
                    'inv_items.id as item_father_id',
                    'inv_items.sku',
                    'inv_items.name',
                    'inv_items.taxId',
                    'inv_items.categoryId',
                    'inv_categories.name as category_name'
                )
                ->get();

            Log::info('Productos encontrados para copiar', ['count' => $distributorProducts->count()]);

            foreach ($distributorProducts as $product) {
                // Obtener el precio más reciente del producto
                $latestPrice = DB::table('inv_values')
                    ->where('itemId', $product->item_father_id)
                    ->where('type', 'precio')
                    ->orderBy('created_at', 'desc')
                    ->first();

                // Obtener el costo más reciente del producto
                $latestCost = DB::table('inv_values')
                    ->where('itemId', $product->item_father_id)
                    ->where('type', 'costo')
                    ->whereIn('label', ['Costo Inicial', 'Costo'])
                    ->orderBy('created_at', 'desc')
                    ->first();

                // Preparar datos para insertar en tat_items
                $tatItemData = [
                    'item_father_id' => $product->item_father_id,
                    'company_id' => $companyId,
                    'sku' => $product->sku,
                    'name' => $product->name,
                    'taxId' => $product->taxId,
                    'categoryId' => $product->categoryId,
                    'stock' => 0,
                    'cost' => $latestCost ? (int) $latestCost->values : 0,
                    'price' => $latestPrice ? (int) $latestPrice->values : 0,
                    'status' => 1,
                    'created_at' => now(),
                    'updated_at' => now(),
                    'deleted_at' => now(), // Placeholder
                ];

                // Verificar existencia
                $exists = DB::table('tat_items')
                    ->where('item_father_id', $product->item_father_id)
                    ->where('company_id', $companyId)
                    ->exists();

                if (!$exists) {
                    try {
                        DB::table('tat_items')->insert($tatItemData);
                    } catch (\Exception $e) {
                        Log::error('Error insertando producto individual en Job', [
                            'product_id' => $product->item_father_id,
                            'error' => $e->getMessage()
                        ]);
                    }
                }
            }

            Log::info('Job finalizado: Productos copiados exitosamente', [
                'company_id' => $companyId
            ]);

        } catch (\Exception $e) {
            Log::error('Error en lógica de copia de productos (Job)', [
                'company_id' => $companyId,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }
}
