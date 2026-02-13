<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CopyProductsToClientJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * El número de veces que se puede intentar el job.
     *
     * @var int
     */
    public $tries = 3;

    /**
     * El número de segundos que el job puede ejecutarse antes de timeout.
     *
     * @var int
     */
    public $timeout = 300; // 5 minutos

    /**
     * ID de la compañía
     *
     * @var int
     */
    protected $companyId;

    /**
     * Create a new job instance.
     */
    public function __construct(int $companyId)
    {
        $this->companyId = $companyId;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        Log::info('Iniciando copia de productos para cliente', [
            'company_id' => $this->companyId
        ]);

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

            $totalProducts = $distributorProducts->count();
            $copiedCount = 0;
            $skippedCount = 0;
            $errorCount = 0;

            Log::info('Productos encontrados para copiar', [
                'company_id' => $this->companyId,
                'total_products' => $totalProducts
            ]);

            $tatCategories = [
                    'company_id' => $this->companyId,
                    'name' => 'TIENDA',
                    'status' => 1,
                    'created_at' => now(),
                    'updated_at' => now()
            ];
            $categoryId = DB::table('tat_categories')->insertGetId($tatCategories);
            

            foreach ($distributorProducts as $product) {
                try {
                    // Verificar si ya existe el producto para este cliente
                    $existingProduct = DB::table('tat_items')
                        ->where('item_father_id', $product->item_father_id)
                        ->where('company_id', $this->companyId)
                        ->first();

                    if ($existingProduct) {
                        $skippedCount++;
                        continue;
                    }

                    // Obtener la imagen principal del producto
                    $mainImage = DB::table('inv_image_gallery')
                        ->where('itemId', $product->item_father_id)
                        ->where('type', 'PRINCIPAL')
                        ->whereNull('deleted_at')
                        ->first();

                    // Obtener el precio más reciente del producto
                    $latestPrice = DB::table('inv_values')
                        ->where('itemId', $product->item_father_id)
                        ->where('type', 'precio')
                        ->whereIn('label', ['Precio Regular'])
                        ->orderBy('created_at', 'desc')
                        ->first();

                    // Preparar datos para insertar en tat_items
                    $tatItemData = [
                        'item_father_id' => $product->item_father_id,
                        'company_id' => $this->companyId,
                        'sku' => $product->sku,
                        'name' => $product->name,
                        'taxId' => $product->taxId,
                        'categoryId' => $categoryId,
                        'stock' => 0, // Stock inicial en 0 para el cliente
                        'img_path' => $mainImage ? $mainImage->img_path : null,
                        'cost' => $latestPrice ? (int) $latestPrice->values : 0,
                        'price' => $latestPrice ? (int) (round($latestPrice->values / 0.7 / 100) * 100) : 0,
                        'status' => 1, // Activo
                        'created_at' => now(),
                        'updated_at' => now(),
                        'deleted_at' => null, // Corregido: null para productos activos
                    ];

                    // Insertar el producto en tat_items
                    DB::table('tat_items')->insert($tatItemData);
                    $copiedCount++;

                } catch (\Exception $e) {
                    $errorCount++;
                    Log::error('Error copiando producto individual', [
                        'product_id' => $product->item_father_id,
                        'product_name' => $product->name,
                        'company_id' => $this->companyId,
                        'error' => $e->getMessage()
                    ]);
                    // Continuar con el siguiente producto
                    continue;
                }
            }

            // Asegurar que exista el producto genérico para esta empresa
            $this->ensureGenericProductExists();


            Log::info('Copia de productos completada', [
                'company_id' => $this->companyId,
                'total_products' => $totalProducts,
                'copied' => $copiedCount,
                'skipped' => $skippedCount,
                'errors' => $errorCount
            ]);

        } catch (\Exception $e) {
            Log::error('Error general al copiar productos', [
                'company_id' => $this->companyId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            // Re-lanzar la excepción para que el job se reintente
            throw $e;
        }
    }

    /**
     * Asegura que exista un producto genérico para la empresa.
     */
    private function ensureGenericProductExists(): void
    {
        try {
            $exists = DB::table('tat_items')
                ->where('company_id', $this->companyId)
                ->where('sku', 'GENERICO')
                ->exists();

            if (!$exists) {
                DB::table('tat_items')->insert([
                    'item_father_id' => 0,
                    'company_id' => $this->companyId,
                    'sku' => 'GENERICO',
                    'name' => 'PRODUCTO GENERICO',
                    'taxId' => 2, // IVA 19% por defecto o según corresponda
                    'categoryId' => 1, // Categoría por defecto
                    'stock' => 999999,
                    'img_path' => '',
                    'cost' => 0,
                    'price' => 0,
                    'status' => 1,
                    'created_at' => now(),
                    'updated_at' => now(),
                    'deleted_at' => null,
                ]);

                Log::info('Producto genérico creado para la empresa', ['company_id' => $this->companyId]);
            }
        } catch (\Exception $e) {
            Log::error('Error al asegurar producto genérico', [
                'company_id' => $this->companyId,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Handle a job failure.

     */
    public function failed(\Throwable $exception): void
    {
        Log::error('Job de copia de productos falló después de todos los intentos', [
            'company_id' => $this->companyId,
            'error' => $exception->getMessage()
        ]);
    }
}
