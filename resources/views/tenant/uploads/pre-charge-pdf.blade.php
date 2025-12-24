<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pre-Cargue</title>
    <style>
        body { 
            font-family: 'Arial', sans-serif; 
            font-size: 11px; 
            line-height: 1.4;
            color: #000;
            background-color: #fff;
        }
        .container {
            max-width: 800px;
            margin: 0 auto;
            padding: 20px;
        }
        .header {
            text-align: center;
            margin-bottom: 25px;
            border-bottom: 1px solid #ccc;
            padding-bottom: 15px;
        }
        .header h1 {
            font-size: 18px;
            font-weight: bold;
            margin: 0 0 10px 0;
            color: #000;
        }
        .info-section {
            display: flex;
            justify-content: space-between;
            margin-bottom: 25px;
            font-size: 11px;
        }
        .info-item {
            flex: 1;
        }
        .info-item strong {
            font-weight: bold;
            color: #333;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 15px;
        }
        th {
            background-color: #f0f0f0;
            border: 1px solid #ccc;
            padding: 8px 6px;
            font-weight: bold;
            text-align: left;
            font-size: 11px;
            color: #000;
        }
        td {
            border: 1px solid #ccc;
            padding: 8px 6px;
            font-size: 11px;
            color: #000;
        }
        .category-row {
            background-color: #e8e8e8;
            font-weight: bold;
            border-left: none;
            border-right: none;
            border-top: 2px solid #ccc;
            border-bottom: 2px solid #ccc;
            padding: 8px 6px;
            font-size: 11px;
        }
        .category-row td {
            border: none;
            font-weight: bold;
            color: #000;
            padding: 6px;
        }
        .item-row td {
            border: 1px solid #ccc;
        }
        .total-section {
            margin-top: 25px;
            text-align: right;
            border-top: 2px solid #ccc;
            padding-top: 15px;
        }
        .total-label {
            font-weight: bold;
            font-size: 14px;
            color: #000;
            margin-right: 10px;
        }
        .total-amount {
            font-weight: bold;
            font-size: 14px;
            color: #000;
        }
        .footer {
            margin-top: 30px;
            text-align: center;
            font-size: 10px;
            color: #666;
            border-top: 1px solid #ccc;
            padding-top: 10px;
        }
        .document-info {
            font-style: italic;
            color: #555;
        }
        .no-border td {
            border: none !important;
        }
        .text-right {
            text-align: right;
        }
        .text-left {
            text-align: left;
        }
        .text-center {
            text-align: center;
        }
        .currency {
            text-align: right;
            white-space: nowrap;
        }
        .quantity {
            text-align: center;
        }
        @media print {
            .no-print {
                display: none !important;
            }
            @page {
                margin: 1cm;
            }
            body {
                print-color-adjust: exact;
                -webkit-print-color-adjust: exact;
            }
            .container {
                padding: 0;
            }
            table {
                page-break-inside: auto;
            }
            tr {
                page-break-inside: avoid;
                page-break-after: auto;
            }
        }
    </style>
</head>
<body class="bg-white">
    <div class="container">
        <div class="header">
            <h1>Reporte de Pre-Cargue</h1>
        </div>

        <div class="info-section">
            <div class="info-item text-right">
                <strong>Fecha de Impresión:</strong> {{ \Carbon\Carbon::now()->format('d/m/Y H:i') }}
            </div>
        </div>
        @php
            // Agrupar los items por categoría, ya que la consulta los ordena así.
            $groupedItems = collect($items)->groupBy('category');
        @endphp
    
        @foreach ($groupedItems as $category => $itemsInCategory)
            <h2>{{ $category }}</h2>
            <table>
                <thead>
                    <tr>
                        <th class="text-left">Código</th>
                        <th class="text-left">Nombre</th>
                        <th class="text-center">Cantidad Pedida</th>
                        <th class="text-center">Stock Disponible</th>
                        <th class="text-right">Subtotal</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($itemsInCategory as $item)
                        <tr class="item-row" @if($item['quantity'] > $item['stockActual']) style="background-color: #FFC2AD" @endif>
                            <td class="text-left">{{ $item['code'] }}</td>
                            <td class="text-left">{{ $item['name_item'] }}</td>
                            <td class="quantity">{{ $item['quantity'] }}</td>
                            <td class="quantity">{{ $item['stockActual'] }}</td>
                            <td class="currency">{{ number_format($item['subtotal'], 2, ',', '.') }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endforeach
    
        <hr style="margin-top: 30px;">
    
         <div class="total-section">
            <span class="total-label">TOTAL:</span>
            <span class="total-amount">$ {{ number_format($total, 2, ',', '.') }}</span>
        </div>

        <div class="footer">
            <div class="document-info">
                Documento generado automáticamente - Multitenancy
            </div>
        </div>
    </div>

</body>
</html>
