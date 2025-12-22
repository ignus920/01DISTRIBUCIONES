<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <title>Reporte de Caja</title>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.3.1/css/bootstrap.min.css">
    <style>
        .zona_impresion {

            width: 380px;
            padding: 10px 5px 10px 5px;

            float: left;
            margin-left: 00px;
            border-style: solid;
            border: 1px solid #999;
            box-shadow: 0 1px 4px rgba(0, 0, 0, 0.4);

        }

        /* Estilos Bootstrap básicos para tablas */
        .table {
            width: 100%;
            max-width: 100%;
            margin-bottom: 1rem;
            background-color: transparent;
        }

        .table th,
        .table td {
            padding: 0.75rem;
            vertical-align: top;
            border-top: 1px solid #dee2e6;
        }

        .table thead th {
            vertical-align: bottom;
            border-bottom: 2px solid #dee2e6;
        }

        .table tbody+tbody {
            border-top: 2px solid #dee2e6;
        }

        .table-bordered {
            border: 1px solid #dee2e6;
        }

        .table-bordered th,
        .table-bordered td {
            border: 1px solid #dee2e6;
        }

        .table-bordered thead th,
        .table-bordered thead td {
            border-bottom-width: 2px;
        }

        /* TUS ESTILOS PERSONALIZADOS */
        body {
            font-family: sans-serif;
            font-size: 12px;
            padding: 20px;
        }

        .header {
            text-align: center;
            margin-bottom: 20px;
        }

        .title {
            font-size: 16px;
            font-weight: bold;
            margin-bottom: 5px;
        }

        .subtitle {
            font-size: 14px;
            margin-bottom: 15px;
        }

        .report-info {
            margin-bottom: 20px;
            text-align: center;
        }

        .table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }

        .table th {
            background-color: #f8f9fa;
            border: 1px solid #dee2e6;
            padding: 8px;
            text-align: left;
            font-weight: bold;
        }

        .table td {
            border: 1px solid #dee2e6;
            padding: 8px;
        }

        .totals {
            margin-top: 20px;
            padding: 10px;
            background-color: #f8f9fa;
            border: 1px solid #dee2e6;
        }

        .total-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 5px;
            font-weight: bold;
        }

        .total-grand {
            font-size: 14px;
            font-weight: bold;
            margin-top: 10px;
            padding-top: 10px;
            border-top: 2px solid #000;
        }

        .observations {
            margin-top: 30px;
            margin-bottom: 30px;
        }

        .obs-title {
            font-weight: bold;
            margin-bottom: 10px;
        }

        .obs-content {
            border: 1px solid #dee2e6;
            min-height: 50px;
            padding: 10px;
        }

        .footer {
            text-align: center;
            margin-top: 40px;
            font-size: 11px;
            color: #666;
        }

        .separator {
            border-top: 1px dashed #ccc;
            margin: 20px 0;
        }

        .text-right {
            text-align: right;
        }

        .text-center {
            text-align: center;
        }
    </style>
</head>

<body>
    <div class="zona_impresion">
        <div class="header">
            <div class="title">Reporte de cierre de caja</div>
        </div>

        <div class="report-info">
            <div><strong>Sucursal: {{$infoCashier['warehouse_name']}}</strong></div><br>
            <div><strong>Cajero: {{$infoCashier['user_name']}}</strong></div><br>
            <div><strong>Cierre:</strong> {{ $date ?? 'N/A' }} {{ $time ?? '' }}</div><br>
            <div><strong>CAJA # {{ $pettyCash[0]['consecutive'] ?? 'N/A' }}</strong></div>
        </div>

        <table class="table">
            <thead>
                <tr>
                    <th>Forma pago</th>
                    <th class="text-right">Arqueo</th>
                    <th class="text-right">Sistema</th>
                    <th class="text-right">Diferencia</th>
                </tr>
            </thead>
            <tbody>
                @php
                $totalArqueo = 0;
                $totalSistema = 0;
                $totalDiferencia = 0;
                @endphp

                @if(isset($details) && count($details) > 0)
                @foreach($details as $detail)
                @php
                $methodName = $detail['method_payments']['name'] ??
                ($detail['methodPayments']['name'] ?? 'DESCONOCIDO');
                $arqueo = $detail['value'] ?? 0;
                $sistema = $detail['valueSystem'] ?? 0;
                $diferencia = $arqueo - $sistema;
                $totalArqueo += $arqueo;
                $totalSistema += $sistema;
                $totalDiferencia += $diferencia;
                @endphp
                <tr>
                    <td>{{ $methodName }}</td>
                    <td class="text-right">{{ number_format($arqueo, 0) }}</td>
                    <td class="text-right">{{ number_format($sistema, 0) }}</td>
                    <td class="text-right">{{ number_format($diferencia, 0) }}</td>
                </tr>
                @endforeach
                @else
                <tr>
                    <td colspan="4" class="text-center">No hay detalles para mostrar.</td>
                </tr>
                @endif
            </tbody>
        </table>

        <div class="observations">
            <div class="obs-title">Observaciones:</div>
            <div class="obs-content">
                @if(isset($details) && isset($details[0]['reconciliation']['observations']))
                {{ $details[0]['reconciliation']['observations'] }}
                @else
                No hay observaciones de reconciliación.
                @endif
            </div>
        </div>

        <div class="separator"></div>

        <div class="footer">
            <strong>Impresión:</strong> {{ date('Y-m-d H:i:s') }}
        </div>
    </div>

</body>

</html>