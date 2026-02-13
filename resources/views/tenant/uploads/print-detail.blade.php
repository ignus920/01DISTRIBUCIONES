<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cargue de ventas #{{ $loadId ?? 'PENDIENTE' }}</title>
    <style>
        /* Estilos generales para coincidir con el PDF */
        body {
            font-family: 'Arial', sans-serif;
            font-size: 10px;
            line-height: 1.2;
            color: #000000;
            background-color: #ffffff;
            margin: 0;
            padding: 0;
        }
        
        .container {
            max-width: 21cm; /* Tamaño A4 */
            margin: 0 auto;
            padding: 0.5cm;
        }
        
        /* Encabezado principal */
        .header-main {
            text-align: center;
            margin-bottom: 15px;
            border-bottom: 1px solid #000;
            padding-bottom: 8px;
        }
        
        .company-name {
            font-size: 14px;
            font-weight: bold;
            margin: 0 0 5px 0;
            text-transform: uppercase;
        }
        
        .document-title {
            font-size: 12px;
            font-weight: bold;
            margin: 5px 0;
        }
        
        /* Información del documento */
        .doc-info {
            margin-bottom: 15px;
            font-size: 9px;
        }
        
        .info-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 10px;
            margin-bottom: 8px;
        }
        
        .info-item {
            margin-bottom: 4px;
        }
        
        .info-label {
            font-weight: bold;
        }
        
        /* Tabla principal */
        .items-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 15px;
            font-size: 9px;
        }
        
        .category-header {
            background-color: #f0f0f0;
            font-weight: bold;
            border-top: 1px solid #000;
            border-bottom: 1px solid #000;
            padding: 5px 0;
            text-transform: uppercase;
            text-align: left;
            font-size: 10px;
            margin-top: 10px;
        }
        
        .category-row {
            background-color: #f8f8f8;
            font-weight: bold;
            border-top: 1px solid #000;
            border-bottom: 1px solid #000;
            padding: 4px 0;
        }
        
        .category-row td {
            padding: 4px 3px;
            border: none;
            font-weight: bold;
        }
        
        .items-table th {
            border-bottom: 1px solid #000;
            padding: 5px 3px;
            text-align: left;
            font-weight: bold;
            background-color: #f0f0f0;
        }
        
        .items-table td {
            padding: 3px;
            border-bottom: 1px solid #ddd;
            vertical-align: top;
        }
        
        .col-code {
            width: 60px;
            text-align: left;
        }
        
        .col-product {
            width: auto;
            text-align: left;
        }
        
        .col-quantity {
            width: 50px;
            text-align: center;
        }
        
        .col-subtotal {
            width: 70px;
            text-align: right;
        }
        
        /* Total */
        .total-section {
            text-align: right;
            margin-top: 10px;
            padding-top: 8px;
            border-top: 2px solid #000;
            font-size: 11px;
            font-weight: bold;
        }
        
        .total-label {
            display: inline-block;
            margin-right: 20px;
        }
        
        .total-amount {
            display: inline-block;
            min-width: 100px;
        }
        
        /* Pie de página */
        .footer {
            text-align: center;
            margin-top: 15px;
            font-size: 10px;
            color: #666;
            border-top: 1px solid #ccc;
            padding-top: 5px;
        }
        
        .page-break {
            page-break-before: always;
        }
        
        /* Control de saltos de página */
        @media print {
            body {
                font-size: 9px;
            }
            
            @page {
                margin: 0.5cm;
                size: A4 portrait;
            }
            
            .container {
                padding: 0;
            }
            
            .category-header {
                page-break-inside: avoid;
                page-break-after: avoid;
            }
            
            /* Evitar que las filas se dividan entre páginas */
            tr {
                page-break-inside: avoid;
            }
            
            /* Permitir que la tabla se divida solo entre categorías */
            .allow-break {
                page-break-inside: auto;
            }
            
            /* Ajustar el número de filas por página */
            .items-table {
                page-break-inside: auto;
            }
        }
        
        /* Estilos específicos para la vista previa */
        @media screen {
            .container {
                background-color: #fff;
                box-shadow: 0 0 10px rgba(0,0,0,0.1);
                padding: 1cm;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Encabezado principal -->
        <div class="header-main">
            <div class="company-name">Mas distribuciones</div>
            <div class="document-title">Cargue de ventas #{{ $deliveryId ?? 'PENDIENTE' }}</div>
        </div>
        
        <!-- Información del documento -->
        <div class="doc-info">
            <div class="info-grid">
                <div class="info-item">
                    <span class="info-label">Generado:</span> {{ \Carbon\Carbon::now()->format('D M j H:i:s Y') }}
                </div>
                <div class="info-item">
                    <span class="info-label">Recibe:</span> 
                </div>
                <div class="info-item">
                    <span class="info-label"># Pedidos:</span> {{ $pedidosCount ?? '74' }}
                </div>
            </div>
            <div class="info-grid">
                <div class="info-item">
                    <span class="info-label">Entrega:</span> {{ $deliveryLocation ?? 'BODEGA' }}
                </div>
                <div class="info-item">
                    <span class="info-label">Fecha:</span> {{ $deliveryDate ?? \Carbon\Carbon::parse('2025-12-27')->format('Y-m-d') }}
                </div>
                <div class="info-item">
                    <!-- Espacio para información adicional si es necesaria -->
                </div>
            </div>
        </div>
        
        <!-- Tabla de productos -->
        @php
            // Agrupar los items por remisión (pedido) primero, luego por categoría
            $groupedByRemission = collect($items)->groupBy('remision_id');
            $currentPageItems = 0;
            $maxItemsPerPage = 35;
        @endphp

        @foreach ($groupedByRemission as $remissionId => $remissionItems)
            <!-- Encabezado del pedido/remisión -->
            <div class="category-header">PEDIDO # {{ $remissionId }}</div>

            @php
                // Agrupar items de esta remisión por categoría
                $groupedByCategory = $remissionItems->groupBy('category');
            @endphp

            <table class="items-table">
                <thead>
                    <tr>
                        <th class="col-code">Codigo</th>
                        <th class="col-product">Producto</th>
                        <th class="col-quantity">Cantidad</th>
                        <th class="col-subtotal">Subtotal</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($groupedByCategory as $category => $itemsInCategory)
                        <!-- Separador de categoría dentro del pedido -->
                        <tr class="category-row">
                            <td colspan="4">{{ strtoupper($category) }}</td>
                        </tr>

                        @foreach ($itemsInCategory as $item)
                            @php $currentPageItems++; @endphp
                            <tr>
                                <td class="col-code">{{ $item['code'] }}</td>
                                <td class="col-product">{{ $item['name_item'] }}</td>
                                <td class="col-quantity">{{ $item['quantity'] }}</td>
                                <td class="col-subtotal">{{ number_format($item['subtotal'], 0) }}</td>
                            </tr>
                        @endforeach
                    @endforeach
                </tbody>
            </table>

            <!-- Subtotal del pedido -->
            @php
                $pedidoSubtotal = $remissionItems->sum('subtotal');
            @endphp
            <div style="text-align: right; margin-bottom: 15px; font-weight: bold; border-bottom: 1px solid #ccc; padding-bottom: 5px;">
                Subtotal Pedido #{{ $remissionId }}: $ {{ number_format($pedidoSubtotal, 0) }}
            </div>
        @endforeach
        
        <!-- Total -->
        <div class="total-section">
            <span class="total-label">Valor Total:</span>
            <span class="total-amount">$ {{ number_format($total, 0) }}</span>
        </div>
        
        <!-- Pie de página -->
        <div class="footer">
            <div>Documento generado automáticamente - {{ config('app.name') ?? 'Sistema' }}</div>
        </div>
    </div>
</body>
</html>