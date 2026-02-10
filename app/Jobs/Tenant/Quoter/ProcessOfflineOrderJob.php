<?php

namespace App\Jobs\Tenant\Quoter;

use App\Models\Tenant\Customer\VntCompany;
use App\Models\Tenant\Quoter\VntQuote;
use App\Models\Tenant\Quoter\VntDetailQuote;
use App\Livewire\Tenant\VntCompany\Services\CompanyService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Hash;
use App\Models\Auth\User;
use App\Models\Auth\UserTenant;

class ProcessOfflineOrderJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $orderData;
    protected $userId;
    protected $warehouseId;
    protected $branchId;
    protected $tenantId;

    /**
     * Create a new job instance.
     */
    public function __construct(array $orderData, $userId, $warehouseId, $branchId, $tenantId = null)
    {
        $this->orderData = $orderData;
        $this->userId = $userId;
        $this->warehouseId = $warehouseId;
        $this->branchId = $branchId;
        $this->tenantId = $tenantId ?: tenant('id');
    }


    /**
     * Lógica principal para procesar el pedido offline una vez que llega al servidor.
     */
    public function handle(): void
    {
        $uuid = $this->orderData['uuid'] ?? 'N/A';
        Log::info("📦 [Job] Iniciando procesamiento de pedido offline: {$uuid}", ['tenant' => $this->tenantId]);

        try {
            // Inicializar el contexto del inquilino (Tenancy) para que use la base de datos correcta
            if ($this->tenantId) {
                tenancy()->initialize($this->tenantId);
            }
            
            DB::beginTransaction();

            // PREVENCIÓN DE DUPLICADOS / EDICIÓN: 
            // Buscamos si ya existe una cotización que contenga este UUID en sus observaciones.
            $existingQuote = VntQuote::where('observations', 'LIKE', "%[UUID: {$uuid}]%")->first();
            
            // Si existe, preparamos para actualizar en lugar de salir
            $quote = null;

            $isRestock = $this->orderData['is_restock'] ?? false;
            $customerId = null;

            // 1. Manejo de Cliente:
            // Dependiendo del tipo de pedido, determinamos quién es el cliente.
            if ($isRestock) {
                // Si es un pedido de reposición (TAT), el cliente es la empresa asociada al usuario que lo envía.
                $customerId = DB::table('vnt_contacts')
                    ->join('vnt_warehouses', 'vnt_contacts.warehouseId', '=', 'vnt_warehouses.id')
                    ->where('vnt_contacts.id', DB::table('users')->where('id', $this->userId)->value('contact_id'))
                    ->value('vnt_warehouses.companyId');
                
                if (!$customerId) {
                    throw new \Exception("No se pudo identificar la empresa para el pedido Restock (Tienda TAT)");
                }
            } else {
                // Caso estándar: obtenemos los datos del cliente que se seleccionó o creó offline.
                $offlineCustomer = $this->orderData['customer'] ?? $this->orderData['cliente'] ?? null;
                
                if (!$offlineCustomer) {
                    throw new \Exception("Datos de cliente no encontrados en el pedido offline.");
                }

                // Si fue un cliente "Temporal" creado offline, intentamos crearlo formalmente en la DB.
                if (isset($offlineCustomer['isTemporary']) && $offlineCustomer['isTemporary']) {
                    $existing = VntCompany::where('identification', $offlineCustomer['identification'])->first();
                    
                    if ($existing) {
                        $customerId = $existing->id;
                    } else {
                        // Mapeo de campos para el servicio de creación de empresas
                        $companyData = [
                            'typeIdentificationId' => $offlineCustomer['typeIdentificationId'] ?: 1,
                            'identification' => $offlineCustomer['identification'],
                            'checkDigit' => $offlineCustomer['verification_digit'] ?? null,
                            'businessName' => $offlineCustomer['businessName'],
                            'firstName' => $offlineCustomer['businessName'],
                            'billingEmail' => $offlineCustomer['billingEmail'] ?? null,
                            'business_phone' => $offlineCustomer['phone'] ?? $offlineCustomer['business_phone'] ?? null,
                            'typePerson' => (($offlineCustomer['typeIdentificationId'] ?? 1) == 2) ? 'Juridica' : 'Natural',
                            'status' => 1,
                            'type' => 'CLIENTE',
                            'routeId' => $offlineCustomer['routeId'] ?? null,
                            'regimeId' => 2,
                            'fiscalResponsabilityId' => 1
                        ];

                        $warehouses = [
                            [
                                'name' => 'Sucursal Principal',
                                'address' => $offlineCustomer['address'] ?? 'Sin dirección',
                                'district' => $offlineCustomer['district'] ?? 'Sin Barrio',
                                'cityId' => $offlineCustomer['cityId'] ?? 1,
                                'main' => 1,
                                'status' => 1
                            ]
                        ];

                        $companyService = app(CompanyService::class);
                        $newCompany = $companyService->create($companyData, $warehouses);
                        $customerId = $newCompany->id;

                        // NUEVO: Crear usuario si se marcó en el formulario offline
                        if (isset($offlineCustomer['createUser']) && $offlineCustomer['createUser'] && !empty($offlineCustomer['billingEmail'])) {
                            try {
                                Log::info("👤 [Job] Intentando crear usuario para cliente offline: {$offlineCustomer['billingEmail']}");
                                
                                // Verificar si el usuario ya existe
                                $existingUser = User::where('email', $offlineCustomer['billingEmail'])->first();
                                
                                if (!$existingUser) {
                                    $newUser = User::create([
                                        'name' => $offlineCustomer['businessName'],
                                        'email' => $offlineCustomer['billingEmail'],
                                        'password' => Hash::make('12345678'), // Password default
                                        'profile_id' => 17, // Perfil Tienda
                                        'contact_id' => $newCompany->mainWarehouse?->contacts->first()?->id,
                                        'phone' => $offlineCustomer['phone'] ?? null,
                                    ]);

                                    UserTenant::create([
                                        'user_id' => $newUser->id,
                                        'tenant_id' => $this->tenantId,
                                        'is_active' => 1,
                                    ]);

                                    // Copiar productos en background
                                    \App\Jobs\CopyProductsToClientJob::dispatch($newCompany->id);
                                    
                                    Log::info("✅ [Job] Usuario creado exitosamente para el cliente sincronizado.");
                                } else {
                                    Log::warning("⚠️ [Job] El email {$offlineCustomer['billingEmail']} ya está registrado como usuario.");
                                }
                            } catch (\Exception $e) {
                                Log::error("❌ [Job] Error al crear usuario offline: " . $e->getMessage());
                                // No fallar el job principal si falla la creación del usuario
                            }
                        }
                    }
                } else {
                    // Si ya existía, usamos su ID o buscamos por identificación.
                    $customerId = $offlineCustomer['id'] ?? null;
                    if (!$customerId && isset($offlineCustomer['identification'])) {
                        $existing = VntCompany::where('identification', $offlineCustomer['identification'])->first();
                        $customerId = $existing ? $existing->id : null;
                    }
                }
            }

            if (!$customerId) {
                throw new \Exception("No se pudo determinar el ID del cliente para el pedido offline.");
            }

            // 2. Crear o Actualizar la Cotización (Cabecera)
            
            // Adjuntamos el UUID en las observaciones si no está
            $observations = ($this->orderData['observaciones'] ?? 'Sincronizado Offline');
            if (!str_contains($observations, "[UUID: {$uuid}]")) {
                $observations .= " [UUID: {$uuid}]";
            }

            if ($existingQuote) {
                Log::info("🔄 [Job] Actualizando pedido existente con UUID {$uuid} (Quote ID: {$existingQuote->id})");
                $quote = $existingQuote;
                $quote->update([
                    'status' => 'REGISTRADO', // Resetear estado si es necesario
                    'customerId' => $customerId,
                    'observations' => $observations,
                    'updated_at' => now(),
                    // Mantener otros campos originales si se desea
                ]);

                // Borrar detalles anteriores para re-insertar los nuevos (manera más limpia de actualizar items)
                $quote->details()->delete();
                
            } else {
                Log::info("🆕 [Job] Creando nuevo pedido offline con UUID {$uuid}");
                $lastQuote = VntQuote::orderBy('consecutive', 'desc')->first();
                $nextConsecutive = $lastQuote ? $lastQuote->consecutive + 1 : 1;

                $quote = VntQuote::create([
                    'consecutive' => $nextConsecutive,
                    'status' => 'REGISTRADO',
                    'typeQuote' => $isRestock ? 'RESTOCK' : 'POS',
                    'customerId' => $customerId,
                    'warehouseId' => $this->warehouseId,
                    'userId' => $this->userId,
                    'observations' => $observations,
                    'branchId' => $this->branchId,
                    'created_at' => $this->orderData['fecha'] ?? now()
                ]);
            }

            // 3. Crear los Detalles del Pedido
            foreach ($this->orderData['items'] as $item) {
                VntDetailQuote::create([
                    'quantity' => $item['quantity'],
                    'tax_percentage' => 0,
                    'price' => $item['price'],
                    'quoteId' => $quote->id,
                    'itemId' => $item['id'],
                    'description' => $item['name'] ?? 'Producto Offline',
                    'priceList' => $item['price']
                ]);
            }

            // 4. Auditoría para pedidos TAT (Opcional):
            // Si el pedido fue de tipo Restock, también dejamos rastro en la tabla específica listado TAT.
            if ($isRestock) {
                foreach ($this->orderData['items'] as $item) {
                    DB::table('tat_restock_list')->insert([
                        'itemId' => $item['id'],
                        'company_id' => $customerId,
                        'quantity_request' => $item['quantity'],
                        'quantity_recive' => 0,
                        'status' => 'Confirmado',
                        'order_number' => $quote->id, 
                        'created_at' => now(),
                        'updated_at' => now()
                    ]);
                }
            }

            DB::commit();
            Log::info("✅ [Job] Pedido offline {$uuid} sincronizado con éxito. Quote ID: {$quote->id}");

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("❌ [Job] Error al procesar pedido offline {$uuid}: " . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);
            // Relanzar la excepción para que el Job falle y pueda ser reintentado por Laravel/Redis
            throw $e;
        }
    }
}
