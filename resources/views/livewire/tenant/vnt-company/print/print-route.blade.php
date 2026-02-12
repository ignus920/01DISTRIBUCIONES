<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hoja de Ruta - {{ $routeName }}</title>
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
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 2px solid #000;
        }

        .company-info {
            flex: 1;
        }

        .company-name {
            font-size: 16pt;
            font-weight: bold;
            margin-bottom: 5px;
        }

        .report-title {
            text-align: right;
            font-size: 14pt;
            font-weight: bold;
        }

        .route-info {
            margin-bottom: 20px;
            background-color: #f9f9f9;
            padding: 10px;
            border: 1px solid #ddd;
        }

        .info-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 10px;
        }

        .info-item {
            font-size: 10pt;
        }

        .info-label {
            font-weight: bold;
            color: #555;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }

        th {
            background-color: #f0f0f0;
            border: 1px solid #000;
            padding: 8px;
            text-align: center;
            font-weight: bold;
            font-size: 9pt;
        }

        td {
            border: 1px solid #000;
            padding: 6px;
            font-size: 9pt;
        }

        .text-center { text-align: center; }
        .text-right { text-align: right; }

        .w-10 { width: 40px; }
        .w-20 { width: 100px; }

        .signature-section {
            margin-top: 50px;
            display: flex;
            justify-content: space-between;
        }

        .signature-box {
            width: 250px;
            border-top: 1px solid #000;
            text-align: center;
            padding-top: 5px;
            font-size: 9pt;
        }

        @media print {
            .no-print { display: none; }
            body { -webkit-print-color-adjust: exact; }
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="company-info">
            <div class="company-name">{{ $companyName }}</div>
            <div>NIT: {{ $companyNit }}</div>
        </div>
        <div class="report-title">
            ORDEN DE CLIENTES EN RUTA
            <div style="font-size: 10pt; font-weight: normal; margin-top: 5px;">
                Fecha: {{ date('Y-m-d H:i') }}
            </div>
        </div>
    </div>

    <div class="route-info">
        <div class="info-grid">
            <div class="info-item">
                <span class="info-label">Ruta:</span> {{ $routeName }}
            </div>
            <div class="info-item">
                <span class="info-label">Vendedor:</span> {{ $salesmanName }}
            </div>
            <div class="info-item">
                <span class="info-label">Día de Venta:</span> {{ $saleDay }}
            </div>
            <div class="info-item">
                <span class="info-label">Día de Entrega:</span> {{ $deliveryDay }}
            </div>
        </div>
    </div>

    <table>
        <thead>
            <tr>
                <th class="w-10">ORD</th>
                <th>CLIENTE / NOMBRE COMERCIAL</th>
                <th class="w-20">CONTACTO / TELÉFONOS</th>
                <th>ZONA / DIRECCIÓN</th>
                <th>OBSERVACIONES / PEDIDO</th>
            </tr>
        </thead>
        <tbody>
            @foreach($items as $item)
            @php
                $company = $item->company;
                $contact = $company ? $company->activeContacts->first() : null;
                $phone = $contact ? $contact->primaryPhone : null;
            @endphp
            <tr>
                <td class="text-center">{{ $item->sales_order }}</td>
                <td>
                    @if($company)
                        <strong>{{ $company->businessName ?: ($company->firstName . ' ' . $company->lastName) }}</strong>
                        <br><small>{{ $company->billingEmail ?: 'Sin email' }}</small>
                    @else
                        <span class="text-red-600">Cliente no encontrado</span>
                    @endif
                </td>
                <td class="text-center">
                    {{ $phone ?: 'S.T.' }}
                </td>
                <td>
                    {{ $item->route->zones->name ?? 'N/A' }}
                    @if($company && $company->mainWarehouse)
                        <br><small>{{ $company->mainWarehouse->address }}</small>
                    @endif
                </td>
                <td style="height: 40px;"></td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <div class="signature-section">
        <div class="signature-box">Firma del Vendedor</div>
        <div class="signature-box">Recibido Almacén</div>
    </div>

    <script>
        window.onload = function() {
            window.print();
        }
    </script>
</body>
</html>
