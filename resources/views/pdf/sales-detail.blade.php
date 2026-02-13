<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detalle de Ventas - {{ $vendorName }}</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: Arial, sans-serif;
            font-size: 12px;
            color: #333;
            padding: 20px;
        }
        
        .header {
            text-align: center;
            margin-bottom: 30px;
            border-bottom: 2px solid #4F46E5;
            padding-bottom: 15px;
        }
        
        .header h1 {
            color: #4F46E5;
            font-size: 24px;
            margin-bottom: 5px;
        }
        
        .header h2 {
            color: #666;
            font-size: 16px;
            font-weight: normal;
            margin-bottom: 5px;
        }
        
        .header p {
            color: #888;
            font-size: 11px;
        }
        
        .info-section {
            margin-bottom: 20px;
            background-color: #f8f9fa;
            padding: 10px;
            border-radius: 5px;
        }
        
        .info-section p {
            margin: 5px 0;
        }
        
        .info-section strong {
            color: #4F46E5;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        
        thead {
            background-color: #4F46E5;
            color: white;
        }
        
        th {
            padding: 10px 8px;
            text-align: left;
            font-size: 11px;
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
            border-bottom: 1px solid #e5e7eb;
        }
        
        tbody tr:nth-child(even) {
            background-color: #f9fafb;
        }
        
        tbody tr:hover {
            background-color: #f3f4f6;
        }
        
        td {
            padding: 8px;
            font-size: 11px;
        }
        
        td.text-right {
            text-align: right;
        }
        
        td.text-center {
            text-align: center;
        }
        
        .badge {
            display: inline-block;
            padding: 3px 8px;
            border-radius: 12px;
            font-size: 10px;
            font-weight: bold;
        }
        
        .badge-success {
            background-color: #d1fae5;
            color: #065f46;
        }
        
        .badge-danger {
            background-color: #fee2e2;
            color: #991b1b;
        }
        
        .badge-devolucion {
            background-color: #fee2e2;
            color: #991b1b;
        }
        
        .badge-normal {
            background-color: #e5e7eb;
            color: #374151;
        }
        
        .footer {
            margin-top: 30px;
            padding-top: 15px;
            border-top: 1px solid #e5e7eb;
            text-align: center;
            font-size: 10px;
            color: #888;
        }
        
        .summary {
            margin-top: 20px;
            text-align: right;
            font-size: 14px;
        }
        
        .summary strong {
            color: #4F46E5;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>Detalle de Ventas</h1>
        <h2>Vendedor: {{ $vendorName }}</h2>
        <p>Período: {{ $dateRange }}</p>
        <p>Generado el: {{ \Carbon\Carbon::now()->format('d/m/Y H:i:s') }}</p>
    </div>

    <div class="info-section">
        <p><strong>Total de registros:</strong> {{ $salesDetail->count() }}</p>
        <p><strong>Total ventas:</strong> ${{ number_format($salesDetail->sum('subtotal'), 2) }}</p>
        <p><strong>Devoluciones:</strong> {{ $salesDetail->where('devolucion', 1)->count() }}</p>
    </div>

    <table>
        <thead>
            <tr>
                <th>ID Pedido</th>
                <th>ID Remisión</th>
                <th>Estado</th>
                <th>Cliente</th>
                <th>Fecha</th>
                <th class="text-right">Subtotal</th>
                <th class="text-center">Devolución</th>
                <th class="text-center">Pago</th>
            </tr>
        </thead>
        <tbody>
            @foreach($salesDetail as $detail)
            <tr>
                <td>{{ $detail->pedido }}</td>
                <td>{{ $detail->remission_id }}</td>
                <td>
                    <span class="badge {{ $detail->estado === 'DEVOLUCION' ? 'badge-danger' : 'badge-success' }}">
                        {{ $detail->estado }}
                    </span>
                </td>
                <td>{{ $detail->cliente }}</td>
                <td>{{ \Carbon\Carbon::parse($detail->fecha)->format('d/m/Y') }}</td>
                <td class="text-right">${{ number_format($detail->subtotal, 2) }}</td>
                <td class="text-center">
                    @if($detail->devolucion)
                        <span class="badge badge-devolucion">Sí</span>
                    @else
                        <span class="badge badge-normal">No</span>
                    @endif
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <div class="summary">
        <p><strong>Total General: ${{ number_format($salesDetail->sum('subtotal'), 2) }}</strong></p>
    </div>

    <div class="footer">
        <p>Este documento fue generado automáticamente por el sistema de reportes.</p>
        <p>© {{ date('Y') }} - Todos los derechos reservados</p>
    </div>
</body>
</html>
