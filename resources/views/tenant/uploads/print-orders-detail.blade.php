<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pedidos Cargue #{{ $deliveryId }}</title>
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
            width: 40%; /* Tamaño carta landscape */
            margin: 0 auto;
            padding: 0.3cm;
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 0.5cm;
        }
        
        /* Tarjeta de pedido individual */
        .order-card {
            border: 1px solid #000;
            border-radius: 3px;
            padding: 0.3cm;
            background-color: #ffffff;
            break-inside: avoid;
            page-break-inside: avoid;
            min-height: 12.5cm;
            position: relative;
        }
        
        /* Encabezado MAS 10 */
        .mas-header {
            text-align: center;
            font-weight: bold;
            font-size: 12px;
            margin-bottom: 5px;
        }
        
        /* Información de la empresa */
        .company-info {
            text-align: center;
            font-size: 9px;
            margin-bottom: 8px;
            line-height: 1.1;
        }
        
        /* Número de página */
        .page-info {
            text-align: center;
            font-size: 9px;
            margin-bottom: 8px;
        }
        
        /* Encabezado del pedido */
        .order-header {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 5px;
            margin-bottom: 8px;
            font-size: 9px;
        }
        
        .order-number {
            font-weight: bold;
            font-size: 10px;
        }
        
        /* Información del cliente */
        .customer-info {
            margin-bottom: 8px;
            font-size: 9px;
            line-height: 1.1;
        }
        
        .customer-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 2px;
        }
        
        .customer-label {
            font-weight: bold;
            min-width: 70px;
        }
        
        /* Tabla de items */
        .items-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 8px;
            font-size: 9px;
        }
        
        .items-table th {
            border-bottom: 1px solid #000;
            padding: 3px;
            text-align: left;
            font-weight: bold;
            background-color: #f0f0f0;
        }
        
        .items-table td {
            padding: 2px 3px;
            border-bottom: 1px solid #ddd;
            vertical-align: top;
        }
        
        .col-ref {
            width: 40px;
            text-align: left;
        }
        
        .col-cant {
            width: 30px;
            text-align: center;
        }
        
        .col-desc {
            width: auto;
            text-align: left;
        }
        
        .col-price {
            width: 40px;
            text-align: right;
        }
        
        .col-subtotal {
            width: 50px;
            text-align: right;
        }
        
        /* Observaciones */
        .observations {
            margin-bottom: 8px;
            font-size: 9px;
            min-height: 20px;
        }
        
        .obs-label {
            font-weight: bold;
        }
        
        /* Descripción legal */
        .legal-description {
            font-size: 8px;
            text-align: justify;
            margin-bottom: 8px;
            font-style: italic;
        }
        
        /* Valor en letras */
        .amount-words {
            font-size: 9px;
            font-weight: bold;
            text-align: center;
            margin-bottom: 8px;
            text-transform: uppercase;
            min-height: 30px;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        /* Totales */
        .totals {
            font-size: 9px;
            margin-bottom: 8px;
        }
        
        .total-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 2px;
        }
        
        .total-label {
            font-weight: bold;
        }
        
        .total-value {
            font-weight: bold;
            min-width: 60px;
            text-align: right;
        }
        
        .total-pagar {
            border-top: 1px solid #000;
            padding-top: 2px;
            font-weight: bold;
        }
        
        /* Pie de página */
        .footer {
            position: absolute;
            bottom: 0.3cm;
            left: 0.3cm;
            right: 0.3cm;
            text-align: center;
            font-size: 8px;
            color: #000;
        }
        
        /* Contacto del vendedor */
        .seller-contact {
            font-size: 8px;
            margin-top: 5px;
        }
        
        .seller-row {
            display: flex;
            justify-content: space-between;
        }
        
        /* Control de paginación - VERSIÓN CORREGIDA */
        @media print {
            body {
                font-size: 9px;
            }
            
            @page {
                margin: 0.3cm;
                size: letter landscape;
            }
            
            .container {
                padding: 0;
                display: grid;
                grid-template-columns: 1fr 1fr;
                gap: 0.5cm;
                width: 100%;
                max-width: 100%;
                /* page-break-inside: avoid; */
            }
            
            .order-card {
                /* min-height: 12.5cm;
                max-height: 12.5cm; Añadido para uniformidad */
                page-break-inside: avoid;
                break-inside: avoid;
            }
            
            /* SOLUCIÓN: Remover o corregir los saltos de página automáticos */
            /* Esto estaba causando el problema */
            /* .order-card:nth-child(4n+1) {
                page-break-before: auto;
            } */
            
            /* En lugar de eso, usar una clase específica para controlar páginas */
            .page-break {
                page-break-after: always;
            }
            
            /* Asegurar que el contenedor se comporte bien en impresión */
            /* .container {
                page-break-inside: avoid;
            } */
        }
        
        /* Estilos específicos para la vista previa */
        @media screen {
            body {
                background-color: #f0f0f0;
            }
            
            .container {
                background-color: #fff;
                box-shadow: 0 0 10px rgba(0,0,0,0.1);
            }
        }
    </style>
</head>
<body>
    @if(count($customerOrders) > 0)

        <div class="container">
            @foreach($customerOrders as $index => $order)
            <div class="order-card {{ $index < count($customerOrders) - 1 ? 'page-break' : '' }}">
                <!-- Contenido de la tarjeta de pedido -->
                <div class="mas-header">MAS 10</div>
                    
                    <!-- Información de la empresa -->
                    <div class="company-info">
                        Mas distribuciones JM<br>
                        Nit: 1017134785-1<br>
                        <h2 class="text-sm font-bold mb-1">PEDIDO #{{ $loop->iteration }}</h2>
                    </div>
                    <!-- Customer Info -->
                     <!-- Contacto (opcional, aparece en algunos pedidos del PDF) -->
                    @if(isset($order['customer']['contact_name']))
                    <div class="customer-info">
                        <div class="customer-row">
                            <span class="customer-label">Contacto:</span>
                            <span>{{ $order['customer']['contact_name'] }}</span>
                        </div>
                    </div>
                    @endif
            </div>
            @if($loop->iteration % 2 == 0 && !$loop->last)
            <div class="page-break col-span-2"></div>
            @endif
            @endforeach
        </div>
    @else
        <div style="text-align: center; padding: 2cm; font-size: 12px;">
            No se encontraron pedidos para esta entrega.
        </div>
    @endif
</body>
</html>