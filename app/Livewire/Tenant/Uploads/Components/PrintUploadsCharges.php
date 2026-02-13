<?php

namespace App\Livewire\Tenant\Uploads\Components;

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\Tenant\DeliveriesList\DisDeliveries;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Barryvdh\DomPDF\Facade\Pdf;
use App\Helpers\NumberToWordsHelper;

class PrintUploadsCharges extends Component
{
    use WithPagination;

    public $perPage = 10;

    /**
     * Resetea la paginación cuando cambia el número de items por página
     */
    public function updatedPerPage()
    {
        $this->resetPage();
    }

    public function printDetail($deliveryId)
    {
        // Debug inicial
        Log::info("=== INICIO printDetail ===", ['delivery_id' => $deliveryId]);

        try {
            // Primero verificar cuántas remisiones hay
            $remisiones = DB::table('inv_remissions')
                ->where('delivery_id', $deliveryId)
                ->select('id', 'userId')
                ->get();

            Log::info("Remisiones encontradas:", ['remisiones' => $remisiones->toArray()]);

            // Obtener items sin agrupar para mostrar todos los pedidos
            $items = DB::table('inv_remissions as r')
                ->join('inv_detail_remissions as dt', 'dt.remissionId', '=', 'r.id')
                ->join('inv_items as i', 'i.id', '=', 'dt.itemId')
                ->join('inv_items_store as its', 'i.id', '=', 'its.itemId')
                ->join('inv_categories as c', 'i.categoryId', '=', 'c.id')
                ->where('r.delivery_id', $deliveryId)
                ->select(
                    'r.id as remision_id',
                    'i.id as code',
                    'c.name as category',
                    'i.name as name_item',
                    'dt.quantity as quantity',
                    DB::raw('dt.quantity * dt.value as subtotal')
                )
                ->orderBy('r.id')
                ->orderBy('c.name')
                ->orderBy('i.name')
                ->get();

            Log::info("SQL generada:", [
                'sql' => DB::table('inv_remissions as r')
                    ->join('inv_detail_remissions as dt', 'dt.remissionId', '=', 'r.id')
                    ->join('inv_items as i', 'i.id', '=', 'dt.itemId')
                    ->join('inv_items_store as its', 'i.id', '=', 'its.itemId')
                    ->join('inv_categories as c', 'i.categoryId', '=', 'c.id')
                    ->where('r.delivery_id', $deliveryId)
                    ->toSql(),
                'bindings' => [$deliveryId]
            ]);

            // Calcular el total
            $total = collect($items)->sum('subtotal');

            //Calcular cantidad de pedidos - método simplificado
            $pedidosCount = DB::table('inv_remissions')
                ->where('delivery_id', $deliveryId)
                ->count();

            // Debug: verificar los datos
            Log::info('Items para PDF:', [
                'delivery_id' => $deliveryId,
                'items_count' => $items->count(),
                'pedidos_count' => $pedidosCount,
                'items' => $items->toArray()
            ]);

            $cleanedItems = $this->cleanUtf8Data($items);
            $cleanedTotal = $this->cleanString((string)$total);
            $cleanedPedidosCount = $this->cleanString((string)$pedidosCount);

            $data = [
                'items' => $cleanedItems,
                'total' => $cleanedTotal,
                'deliveryId' => $deliveryId,
                'pedidosCount' => $cleanedPedidosCount,
            ];

            $pdf = PDF::loadView('tenant.uploads.print-detail', $data);
            return response()->streamDownload(function () use ($pdf) {
                echo $pdf->stream();
            }, "Cargue-de-ventas-#{$deliveryId}.pdf");
        } catch (\Exception $e) {
            Log::error($e);
            session()->flash('error', "Error al generar la impresión: " . $e->getMessage());
        }
    }

    public function printOrders($deliveryId)
    {
        // Debug inicial
        Log::info("=== INICIO printOrders ===", ['delivery_id' => $deliveryId]);

        try {
            // Consulta simplificada para obtener todas las remisiones del delivery
            $orders = DB::table('inv_remissions as r')
                ->join('inv_detail_remissions as dt', 'dt.remissionId', '=', 'r.id')
                ->join('inv_items as i', 'i.id', '=', 'dt.itemId')
                ->join('inv_items_store as its', 'i.id', '=', 'its.itemId')
                ->join('inv_categories as c', 'i.categoryId', '=', 'c.id')
                ->join('vnt_quotes as vq', 'vq.id', '=', 'r.quoteId')
                ->join('vnt_warehouses as vw', 'vq.customerId', '=', 'vw.id')
                ->join('vnt_companies as vc', 'vw.companyId', '=', 'vc.id')
                ->join('vnt_contacts as v_c', 'v_c.warehouseId', '=', 'vw.id')
                ->join('tat_companies_routes as t_c_r', 't_c_r.company_id', '=', 'vc.id')
                ->join('tat_routes as t_r', 't_r.id', '=', 't_c_r.route_id')
                ->where('r.delivery_id', $deliveryId)
                ->whereNotNull('vc.identification')
                ->whereNotNull('vw.district')
                ->whereNotNull('vw.address')
                ->whereNotNull('v_c.business_phone')
                ->whereNotNull('t_r.sale_day')
                ->select(
                    // Cabecera de la factura
                    DB::raw("IF(vc.typePerson = 'PERSON_ENTITY', CONCAT(vc.firstName, ' ', vc.lastName), vc.businessName) AS customerName"),
                    'vc.identification',
                    'vw.district',
                    'vw.address',
                    'v_c.business_phone',
                    'r.userId as salesPerson',
                    't_r.sale_day',
                    'r.id as remission_id',
                    // Observaciones (items)
                    'i.id as code',
                    'c.name as category',
                    'i.name as name_item',
                    'dt.quantity as quantity',
                    DB::raw('dt.quantity * dt.value as subtotal')
                )
                ->orderBy('r.id')
                ->orderBy('customerName')
                ->orderBy('c.name')
                ->orderBy('i.name')
                ->get();

            Log::info("Orders encontradas:", [
                'total_orders' => $orders->count(),
                'remisiones_unicas' => $orders->pluck('remission_id')->unique()->count(),
                'orders' => $orders->toArray()
            ]);

            // Agrupar por remisión (pedido) en lugar de por cliente
            $customerOrders = [];
            foreach ($orders as $order) {
                $key = $order->remission_id; // Usar remission_id como clave única

                if (!isset($customerOrders[$key])) {
                    $customerOrders[$key] = [
                        'customer' => [
                            'name' => $order->customerName,
                            'identification' => $order->identification,
                            'district' => $order->district,
                            'address' => $order->address,
                            'phone' => $order->business_phone,
                            'salesPerson' => $order->salesPerson,
                            'saleDay' => $order->sale_day,
                            'remission_id' => $order->remission_id,
                        ],
                        'items' => [],
                        'subtotal' => 0,
                        'iva' => 0,
                        'total' => 0
                    ];
                }

                $customerOrders[$key]['items'][] = [
                    'code' => $order->code,
                    'category' => $order->category,
                    'name' => $order->name_item,
                    'quantity' => $order->quantity,
                    'subtotal' => $order->subtotal
                ];

                $customerOrders[$key]['subtotal'] += $order->subtotal;
            }

            Log::info('CustomerOrders agrupados por remisión:', [
                'total_remisiones' => count($customerOrders),
                'remisiones_ids' => array_keys($customerOrders)
            ]);

            // Calcular totales y convertir a letras
            foreach ($customerOrders as &$customerOrder) {
                $customerOrder['total'] = $customerOrder['subtotal'] + $customerOrder['iva'];
                $customerOrder['totalInWords'] = NumberToWordsHelper::convert($customerOrder['total']);
            }

            // Limpiar datos UTF-8
            $cleanedCustomerOrders = $this->cleanUtf8Data($customerOrders);
            $cleanedDeliveryId = $this->cleanString((string)$deliveryId);

            $data = [
                'customerOrders' => $cleanedCustomerOrders,
                'deliveryId' => $cleanedDeliveryId,
            ];

            $pdf = PDF::loadView('tenant.uploads.print-orders-detail', $data);
            return response()->streamDownload(function () use ($pdf) {
                echo $pdf->stream();
            }, "Ordenes-de-venta-#{$deliveryId}.pdf");
        } catch (\Exception $e) {
            Log::error($e);
            session()->flash('error', "Error al generar la impresión: " . $e->getMessage());
        }
    }

    public function render()
    {
        $deliveries = DisDeliveries::query()
            ->leftJoin('users as transportador', 'dis_deliveries.deliveryman_id', '=', 'transportador.id')
            ->select('dis_deliveries.id', 'dis_deliveries.sale_date', 'dis_deliveries.user_id', 'dis_deliveries.salesman_id', 'dis_deliveries.deliveryman_id', 'transportador.name as transportador_name')
            ->orderBy('dis_deliveries.sale_date', 'desc')
            ->orderBy('dis_deliveries.id', 'desc')
            ->paginate($this->perPage);

        return view('livewire.tenant.uploads.components.print-uploads-charges', [
            'deliveries' => $deliveries
        ]);
    }

    private function cleanUtf8Data($data)
    {
        if (is_array($data)) {
            foreach ($data as $key => $value) {
                $data[$key] = $this->cleanUtf8Data($value);
            }
            return $data;
        } elseif (is_object($data)) {
            // Si es un objeto, convertirlo a array, verificando si tiene el método toArray
            $dataArray = method_exists($data, 'toArray') ? $data->toArray() : (array) $data;
            return $this->cleanUtf8Data($dataArray);
        } elseif (is_string($data)) {
            // Limpiar la cadena UTF-8
            $cleaned = mb_convert_encoding($data, 'UTF-8', 'UTF-8');
            // Remover caracteres inválidos
            $cleaned = preg_replace('/[^\x{0000}-\x{007F}]/u', '', $cleaned);
            // Otra alternativa más agresiva
            $cleaned = iconv('UTF-8', 'UTF-8//IGNORE//TRANSLIT', $data);
            return $cleaned;
        }
        return $data;
    }

    private function cleanString($string)
    {
        // Primero intentar con iconv
        $string = iconv('UTF-8', 'UTF-8//IGNORE', $string);

        // Si aún hay problemas, usar regex para eliminar caracteres no UTF-8 válidos
        $string = preg_replace('/[^\x{0000}-\x{007F}\x{00A0}-\x{00FF}]/u', '', $string);

        // Convertir entidades HTML si es necesario
        $string = html_entity_decode($string, ENT_QUOTES | ENT_HTML5, 'UTF-8');

        return $string;
    }
}
