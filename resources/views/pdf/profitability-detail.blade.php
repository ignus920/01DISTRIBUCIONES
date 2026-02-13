<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detalle de Rentabilidad - {{ $vendorName }}</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: Arial, sans-serif;
            font-size: 10px;
            color: #333;
            padding: 20px;
        }
        
        .header {
            text-align: center;
            margin-bottom: 20px;
            border-bottom: 2px solid #4F46E5;
            padding-bottom: 15px;
        }
        
        .header h1 {
            color: #4F46E5;
            font-size: 20px;
            margin-bottom: 5px;
        }
        
        .header h2 {
            color: #666;
            font-size: 14px;
            font-weight: normal;
            margin-bottom: 5px;
        }
        
        .header p {
            color: #888;
            font-size: 10px;
        }
        
        .info-section {
            margin-bottom: 15px;
            background-color: #f8f9fa;
            padding: 8px;
            border-radius: 5px;
            font-size: 10px;
        }
        
        .info-section p {
            margin: 3px 0;
        }
        
        .info-section strong {
            color: #4F46E5;
        }
        
        .cliente-section {
            margin-bottom: 25px;
            page-break-inside: avoid;
        }
        
        .cliente-header {
            background-color: #e0e7ff;
            padding: 8px 10px;
            margin-bottom: 10px;
            border-left: 4px solid #4F46E5;
            font-weight: bold;
            font-size: 11px;
        }
        
        .pedido-section {
            margin-bottom: 15px;
            border: 1px solid #e5e7eb;
            border-radius: 5px;
            overflow: hidden;
        }
        
        .pedido-header {
            background-color: #f3f4f6;
            padding: 6px 10px;
            font-size: 10px;
            display: flex;
            justify-content: space-between;
            border-bottom: 1px solid #e5e7eb;
        }
        
        .pedido-header strong {
            color: #4F46E5;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
        }
        
        thead {
            background-color: #6366f1;
            color: white;
        }
        
        th {
            padding: 6px 5px;
            text-align: left;
            font-size: 8px;
            font-weight: bold;
            text-transform: uppercase;
        }
        
        th.text-right {
            text-align: right;
        }
        
        th.text-center {
            text-align: center;
        }
        
        tbody tr {
            border-bottom: 1px solid #f3f4f6;
        }
        
        tbody tr:nth-child(even) {
            background-color: #fafafa;
        }
        
        td {
            padding: 5px;
            font-size: 9px;
        }
        
        td.text-right {
            text-align: right;
        }
        
        td.text-center {
            text-align: center;
        }
        
        .text-green {
            color: #059669;
            font-weight: bold;
        }
        
        .text-red {
            color: #dc2626;
            font-weight: bold;
        }
        
        .text-gray {
            color: #6b7280;
        }
        
        .pedido-subtotal {
            background-color: #f9fafb;
            padding: 6px 10px;
            font-size: 9px;
            font-weight: bold;
            text-align: right;
            border-top: 2px solid #e5e7eb;
        }
        
        .cliente-subtotal {
            background-color: #e0e7ff;
            padding: 8px 10px;
            font-size: 10px;
            font-weight: bold;
            text-align: right;
            margin-top: 5px;
            border-radius: 3px;
        }
        
        .footer {
            margin-top: 20px;
            padding-top: 10px;
            border-top: 1px solid #e5e7eb;
            text-align: center;
            font-size: 9px;
            color: #888;
        }
        
        .summary {
            margin-top: 20px;
            background-color: #f8f9fa;
            padding: 12px;
            border-radius: 5px;
            border: 2px solid #4F46E5;
        }
        
        .summary-row {
            display: flex;
            justify-content: space-between;
            margin: 4px 0;
            font-size: 11px;
        }
        
        .summary-row.total {
            font-size: 13px;
            padding-top: 8px;
            border-top: 2px solid #4F46E5;
            margin-top: 8px;
        }
        
        .summary-row strong {
            color: #4F46E5;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>Rentabilidad por Pedidos</h1>
        <h2>Vendedor: {{ $vendorName }}</h2>
        <p>Generado: {{ \Carbon\Carbon::now()->format('d/m/Y H:i:s') }}</p>
        <p>Desde: {{ $dateRange }}</p>
    </div>

    @php
        // Agrupar por cliente y luego por pedido
        $groupedByCliente = $detailData->groupBy('cliente');
        $totalGeneral = 0;
        $totalCostoGeneral = 0;
        $totalPrecioGeneral = 0;
    @endphp

    @foreach($groupedByCliente as $cliente => $pedidosCliente)
        @php
            $totalCliente = 0;
            $totalCostoCliente = 0;
            $totalPrecioCliente = 0;
        @endphp
        
        <div class="cliente-section">
            <div class="cliente-header">
                Cliente: {{ $cliente }}
            </div>
            
            @php
                $groupedByPedido = $pedidosCliente->groupBy('pedido');
            @endphp
            
            @foreach($groupedByPedido as $pedidoId => $items)
                @php
                    $fechaPedido = $items->first()->fecha;
                    $subtotalPedido = $items->sum('Rentabilidad');
                    $subtotalCosto = $items->sum('CostoPromedio');
                    $subtotalPrecio = $items->sum('PrecioUltimo');
                    
                    $totalCliente += $subtotalPedido;
                    $totalCostoCliente += $subtotalCosto;
                    $totalPrecioCliente += $subtotalPrecio;
                @endphp
                
                <div class="pedido-section">
                    <div class="pedido-header">
                        <span><strong>Pedido #{{ $pedidoId }}</strong></span>
                        <span>Fecha: {{ \Carbon\Carbon::parse($fechaPedido)->format('d/m/Y') }}</span>
                    </div>
                    
                    <table>
                        <thead>
                            <tr>
                                <th>Código</th>
                                <th>Producto</th>
                                <th class="text-center">Pedido</th>
                                <th class="text-center">Devolución</th>
                                <th class="text-center">Entregado</th>
                                <th class="text-right">Costo</th>
                                <th class="text-right">Precio</th>
                                <th class="text-right">Rentab</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($items as $item)
                            <tr>
                                <td>{{ $item->Codigo }}</td>
                                <td>{{ $item->Producto }}</td>
                                <td class="text-center">{{ number_format($item->Pedido, 0) }}</td>
                                <td class="text-center">{{ number_format($item->Devolucion, 0) }}</td>
                                <td class="text-center">{{ number_format($item->Entrega, 0) }}</td>
                                <td class="text-right">{{ number_format($item->CostoPromedio, 3) }}</td>
                                <td class="text-right">{{ number_format($item->PrecioUltimo, 3) }}</td>
                                <td class="text-right {{ $item->Rentabilidad > 0 ? 'text-green' : ($item->Rentabilidad < 0 ? 'text-red' : 'text-gray') }}">
                                    {{ number_format($item->Rentabilidad, 3) }}
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                    
                    <div class="pedido-subtotal">
                        <span style="margin-right: 20px;">Costo: {{ number_format($subtotalCosto, 3) }}</span>
                        <span style="margin-right: 20px;">Precio: {{ number_format($subtotalPrecio, 3) }}</span>
                        <span class="{{ $subtotalPedido > 0 ? 'text-green' : ($subtotalPedido < 0 ? 'text-red' : 'text-gray') }}">
                            Subtotal: {{ number_format($subtotalPedido, 3) }}
                        </span>
                    </div>
                </div>
            @endforeach
            
        </div>
        
        @php
            $totalGeneral += $totalCliente;
            $totalCostoGeneral += $totalCostoCliente;
            $totalPrecioGeneral += $totalPrecioCliente;
        @endphp
    @endforeach

    <div class="summary">
        <div class="summary-row">
            <span>Total Costo Promedio:</span>
            <strong>{{ number_format($totalCostoGeneral, 3) }}</strong>
        </div>
        <div class="summary-row">
            <span>Total Precio Último:</span>
            <strong>{{ number_format($totalPrecioGeneral, 3) }}</strong>
        </div>
        <div class="summary-row total">
            <span>TOTAL RENTABILIDAD:</span>
            <strong class="{{ $totalGeneral > 0 ? 'text-green' : ($totalGeneral < 0 ? 'text-red' : 'text-gray') }}">
                {{ number_format($totalGeneral, 3) }}
            </strong>
        </div>
    </div>

    <div class="footer">
        <p>Este documento fue generado automáticamente por el sistema de reportes.</p>
        <p>© {{ date('Y') }} - Todos los derechos reservados</p>
    </div>
</body>
</html>
