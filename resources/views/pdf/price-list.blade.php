<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lista de Precios</title>
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
        
        .category-section {
            margin-bottom: 25px;
            page-break-inside: avoid;
        }
        
        .category-header {
            background-color: #4F46E5;
            color: white;
            padding: 8px 10px;
            font-size: 14px;
            font-weight: bold;
            margin-bottom: 10px;
            border-radius: 3px;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 15px;
        }
        
        thead {
            background-color: #e5e7eb;
        }
        
        th {
            padding: 8px;
            text-align: left;
            font-size: 11px;
            font-weight: bold;
            text-transform: uppercase;
            color: #374151;
        }
        
        th.text-right {
            text-align: right;
        }
        
        tbody tr {
            border-bottom: 1px solid #e5e7eb;
        }
        
        tbody tr:nth-child(even) {
            background-color: #f9fafb;
        }
        
        td {
            padding: 8px;
            font-size: 11px;
        }
        
        td.text-right {
            text-align: right;
            font-weight: bold;
            color: #059669;
        }
        
        .footer {
            margin-top: 30px;
            padding-top: 15px;
            border-top: 1px solid #e5e7eb;
            text-align: center;
            font-size: 10px;
            color: #888;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>Lista de Precios</h1>
        <p>Generado el: {{ \Carbon\Carbon::now()->format('d/m/Y H:i:s') }}</p>
    </div>

    <div class="info-section">
        <p><strong>Total de productos:</strong> {{ $totalItems }}</p>
        <p><strong>Categorías:</strong> {{ $groupedData->count() }}</p>
    </div>

    @foreach($groupedData as $categoria => $items)
    <div class="category-section">
        <div class="category-header">
            {{ $categoria }}
        </div>
        
        <table>
            <thead>
                <tr>
                    <th style="width: 70%;">Producto</th>
                    <th class="text-right" style="width: 30%;">Precio</th>
                </tr>
            </thead>
            <tbody>
                @foreach($items as $item)
                <tr>
                    <td>{{ $item->item }}</td>
                    <td class="text-right">${{ number_format($item->precio, 2) }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @endforeach

    <div class="footer">
        <p>Este documento fue generado automáticamente por el sistema de reportes.</p>
        <p>© {{ date('Y') }} - Todos los derechos reservados</p>
    </div>
</body>
</html>
