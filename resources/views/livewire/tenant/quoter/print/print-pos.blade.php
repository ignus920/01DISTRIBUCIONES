<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cotización {{ $quote->consecutive }}</title>
    <style>
        @page {
            size: 80mm 100%; /* Ancho fijo 80mm, alto automático */
            margin: 2mm; /* Márgenes mínimos */
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Courier New', monospace;
            font-size: 8pt;
            line-height: 1.1;
            color: #000;
            background: white;
            width: 76mm; /* 80mm - 4mm de margen */
            margin: 0 auto;
        }

        .center {
            text-align: center;
        }

        .left {
            text-align: left;
        }

        .right {
            text-align: right;
        }

        .bold {
            font-weight: bold;
        }

        .large {
            font-size: 10pt;
        }

        .medium {
            font-size: 9pt;
        }

        .small {
            font-size: 7pt;
        }

        .separator {
            border-bottom: 1px dashed #000;
            margin: 3mm 0;
        }

        .double-separator {
            border-bottom: 2px solid #000;
            margin: 2mm 0;
        }

        .header {
            text-align: center;
            margin-bottom: 3mm;
        }

        .company-name {
            font-size: 11pt;
            font-weight: bold;
            margin-bottom: 1mm;
        }

        .company-info {
            font-size: 7pt;
            line-height: 1.2;
            margin-bottom: 2mm;
        }

        .quote-header {
            text-align: center;
            margin: 2mm 0;
        }

        .quote-number {
            font-size: 10pt;
            font-weight: bold;
        }

        .customer-section {
            margin: 2mm 0;
            font-size: 7pt;
        }

        .customer-line {
            margin-bottom: 1mm;
            word-wrap: break-word;
        }

        .products-section {
            margin: 2mm 0;
        }

        .product-item {
            margin-bottom: 2mm;
            border-bottom: 1px dotted #ccc;
            padding-bottom: 1mm;
        }

        .product-code {
            font-size: 7pt;
            color: #666;
        }

        .product-name {
            font-size: 8pt;
            font-weight: bold;
            margin: 1mm 0;
            word-wrap: break-word;
        }

        .product-details {
            display: flex;
            justify-content: space-between;
            font-size: 7pt;
        }

        .quantity-price {
            display: flex;
            justify-content: space-between;
            font-size: 8pt;
            margin-top: 1mm;
        }

        .totals-section {
            margin-top: 3mm;
            font-size: 8pt;
        }

        .total-line {
            display: flex;
            justify-content: space-between;
            margin: 1mm 0;
            padding: 0.5mm 0;
        }

        .final-total {
            border-top: 2px solid #000;
            border-bottom: 2px solid #000;
            font-weight: bold;
            font-size: 9pt;
            margin-top: 2mm;
            padding: 1mm 0;
        }

        .observations-section {
            margin: 3mm 0;
            font-size: 7pt;
        }

        .observations-title {
            font-weight: bold;
            margin-bottom: 1mm;
        }

        .observations-text {
            word-wrap: break-word;
            line-height: 1.3;
        }

        .footer {
            margin-top: 5mm;
            text-align: center;
            font-size: 6pt;
            line-height: 1.2;
        }

        .contact-info {
            margin: 2mm 0;
        }

        .thank-you {
            margin-top: 3mm;
            font-weight: bold;
        }

        .qr-section {
            text-align: center;
            margin: 3mm 0;
        }

        .qr-placeholder {
            width: 30mm;
            height: 30mm;
            border: 1px solid #000;
            margin: 2mm auto;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 6pt;
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

        /* Utility classes para espaciado */
        .mb-1 { margin-bottom: 1mm; }
        .mb-2 { margin-bottom: 2mm; }
        .mt-1 { margin-top: 1mm; }
        .mt-2 { margin-top: 2mm; }
    </style>
</head>
<body>
    <!-- Header -->
    <div class="header">
        <div class="company-name">{{ Str::limit($company->businessName ?? $company->firstName . ' ' . $company->lastName, 30) }}</div>
        <div class="company-info">
            @if($company->businessName)
                NIT: {{ $company->identification }}<br>
            @else
                CC: {{ $company->identification }}<br>
            @endif
            @if($company->phone)
                Tel: {{ $company->phone }}<br>
            @endif
            @if($company->billingEmail)
                {{ Str::limit($company->billingEmail, 25) }}
            @endif
        </div>
    </div>

    <div class="double-separator"></div>

    <!-- Quote Info -->
    <div class="quote-header">
        <div class="bold large">COTIZACIÓN</div>
        <div class="quote-number">No. {{ $quote->consecutive }}</div>
        <div class="small">FECHA: {{ $quote->created_at->format('Y-m-d H:i') }}</div>
    </div>

    <div class="separator"></div>

    <!-- Customer Info -->
    <div class="customer-section">
        <div class="customer-line bold">Cliente: {{ Str::limit($customer->businessName ?: $customer->firstName . ' ' . $customer->lastName, 35) }}</div>
        <div class="customer-line">{{ $customer->identification }}</div>
        @if($customer->phone)
            <div class="customer-line">Tel: {{ $customer->phone }}</div>
        @endif
        @if($customer->billingEmail)
            <div class="customer-line">{{ Str::limit($customer->billingEmail, 30) }}</div>
        @endif
    </div>

    <div class="separator"></div>

    <!-- Products -->
    <div class="products-section">
        @php
            $totalGeneral = 0;
        @endphp

        @foreach($quote->detalles as $index => $detalle)
            @php
                $subtotalItem = $detalle->value * $detalle->quantity;
                $totalGeneral += $subtotalItem;
            @endphp

            <div class="product-item">
                @if($detalle->item->sku)
                    <div class="product-code">{{ $detalle->item->sku }}</div>
                @endif

                <div class="product-name">
                    {{ Str::limit($detalle->item->name ?? $detalle->item->display_name, 35) }}
                </div>

                <div class="quantity-price">
                    <span>{{ $detalle->quantity }} x ${{ number_format($detalle->value, 0) }}</span>
                    <span class="bold">${{ number_format($subtotalItem, 0) }}</span>
                </div>
            </div>
        @endforeach
    </div>

    <div class="separator"></div>

    <!-- Totals -->
    <div class="totals-section">
        <div class="total-line">
            <span>Subtotal:</span>
            <span>${{ number_format($totalGeneral, 0) }}</span>
        </div>
        <div class="total-line">
            <span>IVA (0%):</span>
            <span>$0</span>
        </div>
        <div class="total-line final-total">
            <span>TOTAL:</span>
            <span>${{ number_format($totalGeneral, 0) }}</span>
        </div>
    </div>

    <!-- Observations -->
    @if($quote->observations)
        <div class="separator"></div>
        <div class="observations-section">
            <div class="observations-title">Observaciones:</div>
            <div class="observations-text">{{ $quote->observations }}</div>
        </div>
    @endif

    <!-- QR Code Section (optional) -->
    @if($showQR ?? false)
        <div class="separator"></div>
        <div class="qr-section">
            <div class="small">Escanea para catálogo:</div>
            <div class="qr-placeholder">QR</div>
        </div>
    @endif

    <div class="separator"></div>

    <!-- Footer -->
    <div class="footer">
        @if($company->billingEmail || $company->phone)
            <div class="contact-info">
                <div class="bold small">CONTACTO:</div>
                @if($company->billingEmail)
                    <div>{{ $company->billingEmail }}</div>
                @endif
                @if($company->phone)
                    <div>{{ $company->phone }}</div>
                @endif
            </div>
        @endif

        <div class="thank-you">
            ¡Gracias por su preferencia!
        </div>

        <div class="mt-2 small">
            Cotización válida por 15 días
        </div>
    </div>

    <div style="margin-top: 10mm;"></div> <!-- Espacio final para corte -->
</body>
</html>