<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Pedidos Cargue #{{ $deliveryId }}</title>
    <style>
        @page { size: letter landscape; margin: 0; padding: 0; }
        body {
            font-family: 'Helvetica', 'Arial', sans-serif;
            font-size: 8.2pt;
            margin: 0;
            padding: 0;
            color: #000;
            line-height: 1.1;
        }
        .page-wrapper {
            width: 100%;
            height: 20.5cm;
            overflow: hidden;
        }
        .outer-table { width: 100%; height: 100%; border-collapse: collapse; table-layout: fixed; }
        .order-cell {
            width: 50%;
            height: 20.5cm;
            vertical-align: top;
            padding: 0.5cm 0.7cm;
            position: relative;
            box-sizing: border-box;
        }
        table { width: 100%; border-collapse: collapse; }
        .logo-img { max-width: 100px; height: auto; }
        .company-info { text-align: center; font-size: 8.5pt; }
        .order-info { text-align: right; }
        .order-number { font-size: 12pt; font-weight: bold; }
        .order-date-row { font-size: 8.5pt; font-weight: bold; }
        .client-info-table { margin-top: 8px; }
        .client-info-table td { width: 33.33%; vertical-align: top; font-size: 8.2pt; padding: 1px 0; }
        .bold { font-weight: bold; }
        .obs-section { margin-top: 4px; font-size: 8.2pt; margin-bottom: 6px; }
        .items-table { margin-top: 2px; border-top: 1pt solid #000; }
        .items-table th { border-bottom: 1pt solid #000; text-align: left; padding: 3px 2px; font-size: 8.5pt; font-weight: bold; }
        .items-table td { padding: 3px 2px; font-size: 8.5pt; }
        .text-center { text-align: center; }
        .text-right { text-align: right; }
        .footer-wrapper { position: absolute; bottom: 1.2cm; left: 0.7cm; right: 0.7cm; }
        .totals-table td { 
            padding: 1px 2px; 
            font-size: 8.5pt; 
            white-space: nowrap; /* Evita que el $ salte de línea */
        }
        .total-pay-row { font-weight: bold; border-top: 1pt solid #000; font-size: 9.5pt; }
        .letras { font-weight: bold; text-transform: uppercase; font-size: 8pt; margin-top: 8px; display: block; }
        .contact-line { text-align: center; font-weight: bold; font-size: 8pt; margin-top: 15px; border-top: 0.5pt solid #ccc; padding-top: 6px; width: 100%; }
    </style>
</head>
<body>@if(count($customerOrders) > 0)@php
    $allCards = [];
    foreach($customerOrders as $orderIndex => $order) {
        $items = collect($order['items'] ?? []);
        $chunkedItems = $items->chunk(15); 
        $countPages = (int)$chunkedItems->count();
        foreach($chunkedItems as $pageIndex => $chunk) {
            $allCards[] = [
                'order' => $order,
                'items' => $chunk,
                'currentPage' => $pageIndex + 1,
                'totalPages' => $countPages,
                'isLast' => ($pageIndex + 1) == $countPages,
                'pageSubtotal' => $chunk->sum('subtotal')
            ];
        }
    }
    $cardChunks = collect($allCards)->chunk(2);
    $logoPath = public_path('logo.png');
    $hasLogo = file_exists($logoPath);
@endphp@foreach($cardChunks as $index => $sheet)<div class="page-wrapper" style="{{ $index > 0 ? 'page-break-before: always;' : '' }}"><table class="outer-table"><tr>@foreach($sheet as $card)@php $order = $card['order']; $idPed = $order['customer']['remission_id'] ?? 'S/N'; @endphp<td class="order-cell"><table class="header-table"><tr><td width="30%">@if($hasLogo)<img src="{{ $logoPath }}" class="logo-img" alt="Logo">@else<div style="font-size: 18pt; font-weight: bold;">MAS J.M.</div>@endif</td><td width="40%" class="company-info"><strong style="font-size: 10pt;">Mas distribuciones JM</strong><br>Nit: 1017134785-1<br>PÁGINA: {{ $card['currentPage'] }} de {{ $card['totalPages'] }}</td><td width="30%" class="order-info"><div class="order-number">PEDIDO # {{ $idPed }}</div><div class="order-date-row">FECHA: {{ \Carbon\Carbon::parse($order['order_date'] ?? now())->format('Y-m-d') }}</div><div class="order-date-row">F ENTREGA: {{ \Carbon\Carbon::parse($order['delivery_date'] ?? now())->format('Y-m-d') }}</div></td></tr></table><table class="client-info-table"><tr><td><span class="bold">Cliente:</span> {{ $order['customer']['name'] ?? '' }}<br><span class="bold">Identificación:</span> {{ $order['customer']['identification'] ?? '' }}<br><span class="bold">Barrio:</span> {{ $order['customer']['district'] ?? '' }}</td><td><span class="bold">Contacto:</span> {{ $order['customer']['contact_name'] ?? 'N/A' }}<br><span class="bold">Dirección:</span> {{ $order['customer']['address'] ?? '' }}<br><span class="bold">Teléfono:</span> {{ $order['customer']['phone'] ?? '' }}</td><td><span class="bold">Vendedor:</span> {{ $order['customer']['salesPerson'] ?? '' }}<br><span class="bold">Día visita:</span> {{ $order['customer']['saleDay'] ?? '' }}<br><span class="bold">Tel vendedor:</span> 304 6800740</td></tr></table><div class="obs-section"><span class="bold">Observaciones:</span> {{ $order['observations'] ?? '' }}</div><table class="items-table"><thead><tr><th width="12%">Ref</th><th width="8%" class="text-center">Cant</th><th width="50%">Descripcion</th><th width="15%" class="text-right">Precio</th><th width="15%" class="text-right">Subtotal</th></tr></thead><tbody>@foreach($card['items'] as $item)<tr><td>{{ $item['code'] ?? '' }}</td><td class="text-center">{{ number_format((float)($item['quantity'] ?? 0), 0) }}</td><td>{{ $item['name'] ?? '' }}</td><td class="text-right">{{ number_format((float)($item['unit_price'] ?? 0), 0, '.', '.') }}</td><td class="text-right">{{ number_format((float)($item['subtotal'] ?? 0), 0, '.', '.') }}</td></tr>@endforeach</tbody></table><div class="footer-wrapper"><table class="footer-container"><tr><td class="footer-left">@if($card['isLast'])<span class="letras">VALOR EN LETRAS: {{ $order['totalInWords'] ?? '' }}</span>@else<div style="font-weight: bold; color: #555; margin-top: 10px;">(SIGUE EN PÁGINA {{ $card['currentPage'] + 1 }})</div>@endif</td><td class="footer-right"><table class="totals-table"><tr><td class="bold">TOTAL PÁGINA</td><td class="text-right">$ {{ number_format((float)$card['pageSubtotal'], 0, '.', '.') }}</td></tr>@if($card['isLast'])<tr><td>Subtotal Pedido</td><td class="text-right">$ {{ number_format((float)$order['subtotal'], 0, '.', '.') }}</td></tr><tr class="total-pay-row"><td>TOTAL A PAGAR</td><td class="text-right">$ {{ number_format((float)$order['total'], 0, '.', '.') }}</td></tr>@endif</table></td></tr></table><div class="contact-line">Teléfono: 4774491 - Bogota - Colombia</div></div></td>@endforeach @if($sheet->count() < 2)<td class="order-cell" style="border: none;"></td>@endif</tr></table></div>@endforeach @else<div style="text-align: center; padding: 2cm;">No se encontraron pedidos.</div>@endif</body>
</html>
