<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pre-Cargue</title>
    <style>
        body { 
            font-family: 'Arial', sans-serif; 
            font-size: 10px; 
            line-height: 1.2;
            color: #000;
            background-color: #fff;
            margin: 0;
            padding: 0;
        }
        .container {
            max-width: 800px;
            margin: 0 auto;
            padding: 15px 20px;
        }
        .header {
            text-align: center;
            margin-bottom: 15px;
            border-bottom: 1px solid #ccc;
            padding-bottom: 10px;
        }
        .header h1 {
            font-size: 16px;
            font-weight: bold;
            margin: 0 0 8px 0;
            color: #000;
        }
        .info-section {
            display: flex;
            justify-content: space-between;
            margin-bottom: 15px;
            font-size: 10px;
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
            margin-bottom: 10px;
            table-layout: fixed;
        }
        th {
            background-color: #f0f0f0;
            border: 1px solid #ccc;
            padding: 5px 4px;
            font-weight: bold;
            text-align: left;
            font-size: 10px;
            color: #000;
        }
        td {
            border: 1px solid #ccc;
            padding: 5px 4px;
            font-size: 10px;
            color: #000;
            vertical-align: top;
        }
        .category-row {
            background-color: #e8e8e8;
            font-weight: bold;
            border-left: none;
            border-right: none;
            border-top: 2px solid #ccc;
            border-bottom: 2px solid #ccc;
            padding: 5px 4px;
            font-size: 10px;
        }
        .category-row td {
            border: none;
            font-weight: bold;
            color: #000;
            padding: 4px;
        }
        .item-row td {
            border: 1px solid #ccc;
            padding: 4px 3px;
            height: 20px;
        }
        .total-section {
            margin-top: 15px;
            text-align: right;
            border-top: 2px solid #ccc;
            padding-top: 10px;
        }
        .total-label {
            font-weight: bold;
            font-size: 12px;
            color: #000;
            margin-right: 8px;
        }
        .total-amount {
            font-weight: bold;
            font-size: 12px;
            color: #000;
        }
        .footer {
            margin-top: 20px;
            text-align: center;
            font-size: 9px;
            color: #666;
            border-top: 1px solid #ccc;
            padding-top: 8px;
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
            width: 60px;
        }
        .stock-quantity {
            text-align: center;
            width: 60px;
        }
        .code {
            width: 80px; /* Ancho fijo para código */
        }
        .category {
            width: 120px; /* Ancho fijo para categoría */
        }
        .item {
            width: auto; /* Toma el espacio restante */
        }
        .subtotal {
            width: 100px; /* Ancho fijo para subtotal */
        }
        @media print {
            .no-print {
                display: none !important;
            }
            @page {
                margin: 0.5cm 1cm;
                size: A4 portrait;
            }
            body {
                print-color-adjust: exact;
                -webkit-print-color-adjust: exact;
                font-size: 9px;
            }
            .container {
                padding: 10px;
            }
            table {
                margin-bottom: 5px;
                font-size: 9px;
            }
            th, td {
                padding: 3px 2px; /* Mínimo padding para impresión */
                font-size: 9px;
            }
            .item-row td {
                padding: 2px 1px;
                height: 18px; /* Altura mínima para items */
            }
            tr {
                page-break-inside: avoid;
                page-break-after: auto;
            }
            .allow-break {
                page-break-inside: auto;
            }
            tbody {
                display: block;
            }
            .item-row:nth-child(45n) {
                page-break-after: always;
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
        <table>
            <thead>
                <tr>
                    <th class="text-left code">CÓDIGO</th>
                    <th class="text-left category">CATEGORÍA</th>
                    <th class="text-left item">ITEM</th>
                    <th class="text-center quantity">CANTIDAD PEDIDA</th>
                    <th class="text-center stock-quantity">STOCK DISPONIBLE</th>
                    <th class="text-right subtotal">SUBTOTAL</th>
                </tr>
            </thead>
            <tbody>
                @php
                    // Agrupar los items por categoría
                    $groupedItems = collect($items)->groupBy('category');
                    $rowCount = 0;
                @endphp

                @foreach ($groupedItems as $category => $itemsInCategory)
                    <!-- Fila de categoría -->
                    <tr class="category-row no-border">
                        <td colspan="6"><strong>{{ strtoupper($category) }}</strong></td>
                    </tr>
                    @php $rowCount++; @endphp
                    
                    <!-- Filas de items de esta categoría -->
                    @foreach ($itemsInCategory as $item)
                    @php 
                        $rowCount++;
                        // Añadir clase allow-break si hay muchos items seguidos
                        $breakClass = $loop->iteration % 40 == 0 ? 'allow-break' : '';
                    @endphp
                    <tr class="item-row {{ $breakClass }}" @if($item['quantity'] > $item['stockActual']) style="background-color: #FBCFD0" @endif>
                        <td class="text-left code">{{ $item['code'] }}</td>
                        <td class="text-left category">{{ $item['category'] }}</td>
                        <td class="text-left item">{{ $item['name_item'] }}</td>
                        <td class="text-center quantity">{{ $item['quantity'] }}</td>
                        <td class="text-center stock-quantity">{{ $item['stockActual'] }}</td>
                        <td class="text-right subtotal currency">$ {{ number_format($item['subtotal'], 2, ',', '.') }}</td>
                    </tr>
                    @endforeach
                @endforeach
            </tbody>
        </table>
    
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
