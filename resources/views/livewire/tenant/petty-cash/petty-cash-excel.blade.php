<table>
    <thead>
        <tr>
            <th>#</th>
            <th>Tipo</th>
            <th>Motivo</th>
            <th>Medio de Pago</th>
            <th>Valor</th>
            <th>Observaciones</th>
            <th>Fecha</th>
        </tr>
    </thead>
    <tbody>
        @foreach($details as $detail)
        <tr>
            <td>{{ $detail->id }}</td>
            <td>{{ $detail->reasonsPettyCash->type == 'i' ? 'Ingreso' : 'Egreso' }}</td>
            <td>{{ $detail->reasonsPettyCash->name }}</td>
            <td>{{ $detail->methodPayments->name }}</td>
            <td>{{ $detail->methodPayments->value}}</td>
            <td>{{ $detail->observations}}</td>
            <td>{{ $detail->created_at->format('Y-m-d H:i:s')}}</td>
        </tr>
        @endforeach
    </tbody>
</table>