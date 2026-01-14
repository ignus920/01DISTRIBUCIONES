<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pedidos Cargue #{{ $deliveryId }}</title>
    <style>
        @page {
            size: letter landscape;
            margin: 0.5cm;
        }
        body {
            font-family: 'Arial', sans-serif;
            font-size: 7.5pt;
            margin: 0;
            padding: 0;
            color: #000;
            line-height: 1.1;
        }
        .page-wrapper {
            width: 100%;
            page-break-after: always;
        }
        .page-wrapper:last-child {
            page-break-after: avoid;
        }
        
        /* Matriz 2x2 */
        .outer-table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 5px;
            table-layout: fixed;
        }
        .order-cell {
            width: 50%;
            height: 10.7cm; /* Aumentado para llenar más la hoja verticalmente */
            vertical-align: top;
            border: 1px solid #000;
            border-radius: 4px;
            padding: 8px; /* Un poco más de aire interno */
            overflow: hidden;
        }

        /* Estilos Internos del Pedido */
        table {
            width: 100%;
            border-collapse: collapse;
        }
        
        /* Logo y Header */
        .header-center {
            text-align: center;
            font-size: 8.5pt;
        }
        .header-right {
            text-align: right;
            font-size: 8.5pt;
        }
        .order-title {
            font-weight: bold;
            font-size: 9.5pt;
        }

        /* Info Cliente 3 Columnas */
        .info-table {
            margin-top: 5px;
            margin-bottom: 5px;
        }
        .info-table td {
            width: 33.33%;
            vertical-align: top;
            padding-right: 5px;
        }
        .label {
            font-weight: bold;
        }

        /* Tabla de Items */
        .items-header th {
            border-top: 1.5pt solid #000;
            border-bottom: 1.5pt solid #000;
            text-align: left;
            padding: 4px 2px;
            font-size: 8pt;
        }
        .items-table td {
            padding: 3px 2px;
            font-size: 8pt;
        }
        .row-even {
            background-color: #f2f2f2;
        }
        
        /* Alineación especial para precios solicitada */
        .col-right {
            text-align: right;
            padding-right: 20px !important; /* Espacio extra a la derecha para empujar los números hacia el centro */
        }
        .col-center {
            text-align: center;
        }
        .th-align {
            text-align: right;
            padding-right: 25px !important; /* Alinear cabecera con el bloque de números */
        }

        /* Footer y Totales */
        .footer-area {
            margin-top: 5px;
        }
        .legal-note {
            font-size: 6.5pt;
            font-style: italic;
            width: 60%;
            vertical-align: top;
        }
        .totals-area {
            width: 40%;
            vertical-align: top;
        }
        .totals-table td {
            padding: 1px 2px;
        }
        .total-pay {
            font-weight: bold;
            border-top: 1pt solid #000;
        }
        .bottom-contact {
            text-align: center;
            font-weight: bold;
            font-size: 8.5pt;
            margin-top: 8px;
        }
    </style>
</head>
<body>
    @if(count($customerOrders) > 0)
        @php
            $allCards = [];
            foreach($customerOrders as $orderIndex => $order) {
                // Forzamos el índice a entero para evitar TypeError: string + int
                $numO = (int)$orderIndex;
                
                // Obtenemos los ítems reales del pedido
                $items = collect($order['items'] ?? []);
                
                // MODO PRUEBA: Comenta estas líneas para usar datos reales sin simulación
                /*
                if($items->count() > 0) {
                    $originalItems = $items;
                    while($items->count() < 45) {
                        $items = $items->concat($originalItems);
                    }
                }
                */
                
                // OPTIMIZACIÓN: Aumentamos a 26 productos por tarjeta para usar todo el espacio vertical
                $chunkedItems = $items->chunk(26); 
                $countPages = (int)$chunkedItems->count();
                
                if ($countPages == 0) {
                    $allCards[] = [
                        'order' => $order,
                        'orderNumber' => $numO + 1,
                        'items' => collect([]),
                        'currentPage' => 1,
                        'totalPages' => 1,
                        'isLast' => true,
                        'pageSubtotal' => 0
                    ];
                } else {
                    foreach($chunkedItems as $pageIndex => $chunk) {
                        $pIdx = (int)$pageIndex;
                        $allCards[] = [
                            'order' => $order,
                            'orderNumber' => $numO + 1,
                            'items' => $chunk,
                            'currentPage' => $pIdx + 1,
                            'totalPages' => $countPages,
                            'isLast' => ($pIdx + 1) == $countPages,
                            'pageSubtotal' => $chunk->sum('subtotal')
                        ];
                    }
                }
            }
            
            // Agrupamos en bloques de 4 para la rejilla 2x2
            $cardChunks = collect($allCards)->chunk(4);
            $logoPath = public_path('logo.png');
            $hasLogo = file_exists($logoPath);
        @endphp

        @foreach($cardChunks as $sheetIndex => $sheet)
            <div class="page-wrapper">
                <table class="outer-table">
                    @foreach($sheet->chunk(2) as $row)
                        <tr>
                            @foreach($row as $card)
                                @php
                                    $order = $card['order'];
                                    // La ID real es remission_id dentro del sub-array customer
                                    $idPed = $order['customer']['remission_id'] ?? $order['id'] ?? 'S/N';
                                @endphp
                                <td class="order-cell">
                                    <!-- Cabecera -->
                                    <table style="margin-bottom: 5px;">
                                        <tr>
                                            <td width="25%" style="vertical-align: middle;">
                                                @if($hasLogo)
                                                    <img src="{{ $logoPath }}" alt="Logo" style="width: 130px; height: auto;">
                                                @else
                                                    <div style="font-size: 18pt; font-weight: bold; color: #003366;">MAS JM</div>
                                                @endif
                                            </td>
                                            <td width="45%" class="header-center">
                                                <strong>Mas distribuciones JM</strong><br>
                                                Nit: 1017134785-1<br>
                                                PÁGINA: {{ ($card['orderNumber']) }}-{{ ($card['currentPage']) }}
                                            </td>
                                            <td width="30%" class="header-right">
                                                <span class="order-title">PEDIDO # {{ $idPed }}</span><br>
                                                <strong>FECHA:</strong> {{ \Carbon\Carbon::parse($order['order_date'] ?? now())->format('Y-m-d') }}<br>
                                                <strong>FECHA ENTRE:</strong> {{ \Carbon\Carbon::parse($order['delivery_date'] ?? now())->format('Y-m-d') }}
                                            </td>
                                        </tr>
                                    </table>

                                    <!-- Cliente -->
                                    <table class="info-table">
                                        <tr>
                                            <td>
                                                <span class="label">Cliente:</span> {{ substr((string)($order['customer']['name'] ?? ''), 0, 25) }}<br>
                                                <span class="label">Identificación:</span> {{ (string)($order['customer']['identification'] ?? '') }}<br>
                                                <span class="label">Barrio:</span> {{ (string)($order['customer']['district'] ?? '') }}
                                            </td>
                                            <td>
                                                <span class="label">Contacto:</span> {{ substr((string)($order['customer']['contact_name'] ?? 'N/A'), 0, 25) }}<br>
                                                <span class="label">Dirección:</span> {{ substr((string)($order['customer']['address'] ?? ''), 0, 30) }}<br>
                                                <span class="label">Teléfono:</span> {{ (string)($order['customer']['phone'] ?? '') }}
                                            </td>
                                            <td>
                                                <span class="label">Vendedor:</span> {{ (string)($order['customer']['salesPerson'] ?? '') }}<br>
                                                <span class="label">Día visita:</span> {{ (string)($order['customer']['saleDay'] ?? '') }}<br>
                                                <span class="label">Tel vendedor:</span> 304 6800740
                                            </td>
                                        </tr>
                                    </table>

                                    <div style="font-size: 7.5pt; margin-bottom: 3px;">
                                        <span class="label">Observaciones:</span> {{ (string)($order['observations'] ?? '') }}
                                    </div>

                                    <!-- Productos -->
                                    <table class="items-table">
                                        <thead class="items-header">
                                            <tr>
                                                <th width="12%">Ref</th>
                                                <th width="8%" class="col-center">Cant</th>
                                                <th width="50%">Descripcion</th>
                                                <th width="15%" class="th-align">Precio</th>
                                                <th width="15%" class="th-align">Subtotal</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach($card['items'] as $item)
                                            <tr class="{{ $loop->even ? 'row-even' : '' }}">
                                                <td>{{ (string)($item['code'] ?? '') }}</td>
                                                <td class="col-center">{{ number_format((float)($item['quantity'] ?? 0), 0) }}</td>
                                                <td>{{ substr((string)($item['name'] ?? ''), 0, 42) }}</td>
                                                <td class="col-right">{{ number_format((float)($item['unit_price'] ?? ($item['subtotal'] / (($item['quantity'] ?? 1) ?: 1))), 0, '.', '.') }}</td>
                                                <td class="col-right">{{ number_format((float)($item['subtotal'] ?? 0), 0, '.', '.') }}</td>
                                            </tr>
                                            @endforeach
                                        </tbody>
                                    </table>

                                    <!-- Footer -->
                                    <table class="footer-area">
                                        <tr>
                                            <td class="legal-note">
                                                <div style="margin-bottom: 5px;">
                                                    <strong>Descripcion:</strong><br>
                                                    Documento sustitutivo de la factura, Decreto 1514 del 98 artículo 1, IVA Regimen simplificado
                                                </div>
                                                @if($card['isLast'])
                                                    @if(isset($order['totalInWords']))
                                                    <div style="font-weight: bold; text-transform: uppercase;">
                                                        VALOR EN LETRAS: {{ (string)$order['totalInWords'] }}
                                                    </div>
                                                    @endif
                                                @else
                                                <div style="font-weight: bold; color: #777; font-style: italic;">
                                                    @php $nextP = (int)$card['currentPage'] + 1; @endphp
                                                    (SIGUE EN PAG. {{ (int)$card['orderNumber'] }}-{{ $nextP }})
                                                </div>
                                                @endif
                                            </td>
                                            <td class="totals-area">
                                                <table class="totals-table">
                                                    @if($card['isLast'])
                                                        <tr>
                                                            <td class="label">TOTAL PÁGINA</td>
                                                            <td class="col-right">$ {{ number_format((float)$card['pageSubtotal'], 0, '.', '.') }}</td>
                                                        </tr>
                                                        <tr>
                                                            <td class="label">Subtotal Pedido</td>
                                                            <td class="col-right">$ {{ number_format((float)($order['subtotal'] ?? 0), 0, '.', '.') }}</td>
                                                        </tr>
                                                        <tr>
                                                            <td class="label">Iva</td>
                                                            <td class="col-right">$ {{ number_format((float)($order['iva'] ?? 0), 0, '.', '.') }}</td>
                                                        </tr>
                                                        <tr class="total-pay">
                                                            <td class="label">TOTAL A PAGAR</td>
                                                            <td class="col-right"><strong>$ {{ number_format((float)($order['total'] ?? 0), 0, '.', '.') }}</strong></td>
                                                        </tr>
                                                    @else
                                                        <tr>
                                                            <td class="label">TOTAL PÁGINA</td>
                                                            <td class="col-right">$ {{ number_format((float)$card['pageSubtotal'], 0, '.', '.') }}</td>
                                                        </tr>
                                                        <tr class="total-pay">
                                                            <td class="label" colspan="2" style="text-align: center; color: #777; font-size: 7pt; border-top: 1pt solid #ccc;">
                                                                (TOTAL FINAL EN ÚLTIMA PÁG.)
                                                            </td>
                                                        </tr>
                                                    @endif
                                                </table>
                                            </td>
                                        </tr>
                                    </table>

                                    <div class="bottom-contact">
                                        Teléfono: 6014774491 - Bogota - Colombia
                                    </div>
                                </td>
                            @endforeach
                            @if($row->count() < 2)
                                <td class="order-cell" style="border: none;"></td>
                            @endif
                        </tr>
                    @endforeach
                </table>
            </div>
        @endforeach
    @else
        <div style="text-align: center; padding: 2cm; font-family: sans-serif;">
            No se encontraron pedidos.
        </div>
    @endif
</body>
</html>
