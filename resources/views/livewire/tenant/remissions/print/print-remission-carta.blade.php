<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Remisión {{ $quote->consecutive }}</title>
    <style>
        @page {
            size: letter;
            margin: 0.5in;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: Arial, sans-serif;
            font-size: 10pt;
            line-height: 1.2;
            color: #000;
            background: white;
        }

        .header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 15px;
            padding-bottom: 10px;
            border-bottom: 2px solid #000;
        }

        .company-info {
            flex: 1;
        }

        .company-name {
            font-size: 18pt;
            font-weight: bold;
            margin-bottom: 5px;
            color: #000;
        }

        .company-details {
            font-size: 9pt;
            line-height: 1.3;
        }

        .quote-info {
            text-align: right;
            font-size: 11pt;
        }

        .quote-title {
            font-size: 16pt;
            font-weight: bold;
            margin-bottom: 8px;
            text-transform: uppercase;
        }

        .quote-details {
            margin-bottom: 20px;
            display: flex;
            justify-content: space-between;
        }

        .customer-info, .quote-meta {
            flex: 1;
            margin-right: 20px;
        }

        .quote-meta {
            margin-right: 0;
        }

        .section-title {
            font-weight: bold;
            margin-bottom: 8px;
            color: #333;
            border-bottom: 1px solid #ccc;
            padding-bottom: 2px;
        }

        .info-line {
            margin-bottom: 3px;
        }

        .products-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 15px;
            font-size: 9pt;
        }

        .products-table th {
            background-color: #f0f0f0;
            border: 1px solid #000;
            padding: 6px 4px;
            text-align: center;
            font-weight: bold;
            font-size: 9pt;
        }

        .products-table td {
            border: 1px solid #000;
            padding: 4px;
            text-align: center;
        }

        .products-table td.description {
            text-align: left;
            max-width: 200px;
            word-wrap: break-word;
        }

        .products-table td.code {
            text-align: center;
            width: 80px;
        }

        .products-table td.unit {
            width: 60px;
        }

        .products-table td.quantity {
            width: 50px;
        }

        .products-table td.price {
            text-align: right;
            width: 70px;
        }

        .products-table td.iva {
            width: 40px;
        }

        .products-table td.subtotal {
            text-align: right;
            width: 80px;
            font-weight: bold;
        }

        .totals-section {
            margin-top: 15px;
            display: flex;
            justify-content: space-between;
        }

        .observations {
            flex: 1;
            margin-right: 20px;
        }

        .observations-content {
            border: 1px solid #ccc;
            padding: 8px;
            min-height: 80px;
            font-size: 9pt;
            background-color: #fafafa;
        }

        .totals {
            width: 250px;
        }

        .total-line {
            display: flex;
            justify-content: space-between;
            padding: 4px 0;
            border-bottom: 1px solid #eee;
        }

        .total-line.final {
            border-top: 2px solid #000;
            border-bottom: 2px solid #000;
            font-weight: bold;
            font-size: 12pt;
            margin-top: 5px;
            padding: 6px 0;
        }

        .footer {
            margin-top: 20px;
            text-align: center;
            font-size: 8pt;
            color: #666;
            border-top: 1px solid #ccc;
            padding-top: 10px;
        }

        .footer-contact {
            margin-bottom: 5px;
        }

        .qr-section {
            text-align: center;
            margin: 15px 0;
        }

        .qr-code {
            margin: 10px 0;
        }

        .page-break {
            page-break-after: always;
        }

        @media print {
            body {
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }

            .no-print {
                display: none;
            }
        }

        .amount {
            font-weight: bold;
        }
    </style>
</head>
<body>
    <!-- Header -->
    <div class="header">
        <div class="company-info">
            <div class="company-name">{{ $company->businessName ?? $company->firstName . ' ' . $company->lastName }}</div>
            <div class="company-details">
                @if($company->businessName)
                    <div>NIT: {{ $company->identification }}</div>
                @else
                    <div>Cédula: {{ $company->identification }}</div>
                @endif
                @if($company->billingAddress)
                    <div>Dirección: {{ $company->billingAddress }}</div>
                @endif
                @if($company->phone)
                    <div>Teléfono: {{ $company->phone }}</div>
                @endif
                @if($company->billingEmail)
                    <div>Email: {{ $company->billingEmail }}</div>
                @endif
            </div>
        </div>
        <div class="quote-info">
            <div class="quote-title">{{ $documentTitle ?? 'REMISIÓN' }}</div>
            <div><strong>No. {{ $quote->consecutive }}</strong></div>
            <div>Página 1 de 1</div>
        </div>
    </div>

    <!-- Quote Details -->
    <div class="quote-details">
        <div class="customer-info">
            <div class="section-title">Señores:</div>
            <div class="info-line"><strong>{{ $customer->businessName ?: $customer->firstName . ' ' . $customer->lastName }}</strong></div>
            <div class="info-line">Atención: {{ $customer->firstName }} {{ $customer->lastName }}</div>
            <div class="info-line">NIT: {{ $customer->identification }}</div>
            @if($customer->billingAddress)
                <div class="info-line">Dirección: {{ $customer->billingAddress }}</div>
            @endif
            @if($customer->phone)
                <div class="info-line">Teléfono: {{ $customer->phone }}</div>
            @endif
        </div>

        <div class="quote-meta">
            <div class="info-line"><strong>Fecha:</strong> {{ $quote->created_at->format('Y-m-d') }}</div>
            <div class="info-line"><strong>Entrega:</strong> {{ $quote->created_at->addDays(3)->format('Y-m-d') }}</div>
            <div class="info-line"><strong>Vendedor:</strong> {{ $quote->user->name ?? 'Sistema' }}</div>
            <div class="info-line"><strong>Forma de Pago:</strong> Contado</div>
        </div>
    </div>

    <!-- Products Table -->
    <table class="products-table">
        <thead>
            <tr>
                <th>#</th>
                <th>Código</th>
                <th>Unidad</th>
                <th>Descripción</th>
                <th>Imágenes</th>
                <th>Cantidad</th>
                <th>Valor Unitario</th>
                <th>IVA %</th>
                <th>Subtotal</th>
            </tr>
        </thead>
        <tbody>
            @foreach($quote->details as $index => $detalle)
                <tr>
                    <td>{{ $index + 1 }}</td>
                    <td class="code">{{ $detalle->item ? ($detalle->item->sku ?? 'N/A') : 'N/A' }}</td>
                    <td class="unit">Unidad</td>
                    <td class="description">{{ $detalle->item ? ($detalle->item->name ?? $detalle->item->display_name) : 'Producto no encontrado' }}</td>
                    <td>
                        @if($detalle->item && $detalle->item->principalImage)
                            Sin imagen
                        @else
                            Sin imagen
                        @endif
                    </td>
                    <td class="quantity">{{ $detalle->quantity }}</td>
                    <td class="price">${{ number_format($detalle->value, 0) }}</td>
                    <td class="iva">0</td>
                    <td class="subtotal">${{ number_format($detalle->value * $detalle->quantity, 0) }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <!-- Totals Section -->
    <div class="totals-section">
        <div class="observations">
            <div class="section-title">Observaciones:</div>
            <div class="observations-content">
                @if(isset($quote->observations))
                    {{ $quote->observations }}
                @else
                    <p>{{ $defaultObservations ?? 'Sin observaciones especiales.' }}</p>
                @endif
            </div>
        </div>

        <div class="totals">
            @php
                $subtotal = $quote->details->sum(function($detalle) {
                    return $detalle->value * $detalle->quantity;
                });
                $iva = 0;
                $total = $subtotal + $iva;
            @endphp

            <div class="total-line">
                <span>Vr. Bruto:</span>
                <span class="amount">${{ number_format($subtotal, 0) }}</span>
            </div>
            <div class="total-line">
                <span>Subtotal:</span>
                <span class="amount">${{ number_format($subtotal, 0) }}</span>
            </div>
            <div class="total-line">
                <span>IVA $:</span>
                <span class="amount">${{ number_format($iva, 0) }}</span>
            </div>
            <div class="total-line final">
                <span>Total:</span>
                <span class="amount">${{ number_format($total, 0) }}</span>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <div class="footer">
        <div class="footer-contact">
            <strong>PARA PEDIDOS:</strong>
            @if(isset($company->billingEmail))
                {{ $company->billingEmail }}
            @endif
            @if(isset($company->phone))
                - {{ $company->phone }}
            @endif
        </div>
        <div>
            <strong>Apreciado cliente, favor confirmar la recepción total de los productos despachados.</strong>
        </div>
    </div>

    @if($showQR ?? false)
    <!-- QR Section -->
    <div class="qr-section">
        <div>Escanea el QR para ver el catálogo</div>
        <div class="qr-code">
            <!-- Aquí iría el QR code si está disponible -->
            <div style="width: 100px; height: 100px; border: 1px solid #000; margin: 0 auto; display: flex; align-items: center; justify-content: center;">
                QR CODE
            </div>
        </div>
    </div>
    @endif

    <script>
        window.onload = function() {
            window.print();
        }
    </script>
</body>
</html>
