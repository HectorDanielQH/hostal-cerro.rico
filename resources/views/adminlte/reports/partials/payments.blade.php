@php
    $filters = $report['filters'];
    $summary = $report['summary'];
@endphp
<div class="report-panel" data-report-panel="payments">
    <form class="row g-3 report-filter-form" data-report-form="payments" action="{{ route('adminlte.reports.payments') }}" method="GET">
        <div class="col-md-2"><label class="form-label">Desde</label><input type="date" name="date_from" value="{{ $filters['date_from'] }}" class="form-control"></div>
        <div class="col-md-2"><label class="form-label">Hasta</label><input type="date" name="date_to" value="{{ $filters['date_to'] }}" class="form-control"></div>
        <div class="col-md-2"><label class="form-label">Estado</label><select name="status" class="form-select"><option value="">Todos</option>@foreach ($filterOptions['paymentStatuses'] as $value => $label)<option value="{{ $value }}" @selected(($filters['status'] ?? null) === $value)>{{ $label }}</option>@endforeach</select></div>
        <div class="col-md-3"><label class="form-label">Metodo</label><select name="payment_method" class="form-select"><option value="">Todos</option>@foreach ($filterOptions['paymentMethods'] as $value => $label)<option value="{{ $value }}" @selected(($filters['payment_method'] ?? null) === $value)>{{ $label }}</option>@endforeach</select></div>
        <div class="col-md-3"><label class="form-label">Cliente</label><select name="customer_id" class="form-select"><option value="">Todos</option>@foreach ($filterOptions['customers'] as $customer)<option value="{{ $customer->id }}" @selected((string) ($filters['customer_id'] ?? '') === (string) $customer->id)>{{ $customer->full_name }}</option>@endforeach</select></div>
        <div class="col-12 d-flex gap-2 flex-wrap"><button type="submit" class="btn btn-primary">Buscar</button><button type="button" class="btn btn-outline-secondary" data-report-reset="payments">Limpiar</button>@if($canExport)<a href="{{ route('adminlte.reports.payments.export', $filters) }}" class="btn btn-success">Exportar CSV</a>@endif</div>
    </form>
    <div class="row g-3 mt-1">
        <div class="col-md-4"><div class="small-box text-bg-warning"><div class="inner"><h3>Bs. {{ number_format($summary['pending_amount'], 2, '.', '') }}</h3><p>Pendientes aplicables automaticamente</p></div></div></div>
        <div class="col-md-4"><div class="small-box text-bg-success"><div class="inner"><h3>Bs. {{ number_format($summary['confirmed_amount'], 2, '.', '') }}</h3><p>Confirmados aplicados automaticamente</p></div></div></div>
        <div class="col-md-4"><div class="small-box text-bg-danger"><div class="inner"><h3>Bs. {{ number_format($summary['rejected_amount'], 2, '.', '') }}</h3><p>Rechazados aplicables automaticamente</p></div></div></div>
    </div>
    <div class="table-responsive mt-3">
        <table class="table table-striped table-bordered align-middle">
            <thead><tr><th>Codigo pago</th><th>Reserva</th><th>Cliente</th><th>Monto</th><th>Metodo</th><th>Estado</th><th>Fecha pago</th><th>Fecha confirmacion</th><th>Confirmado por</th><th>Referencia</th></tr></thead>
            <tbody>
            @forelse ($report['rows'] as $payment)
                <tr>
                    <td>{{ $payment->code }}</td>
                    <td>{{ $payment->reservation?->code }}</td>
                    <td>{{ $payment->customer?->full_name }}</td>
                    <td>
                        <div>{{ $payment->currency ?? 'BOB' }} {{ number_format((float) $payment->amount, 2, '.', '') }}</div>
                        @if ((float) ($payment->amount_base ?? 0) > 0)
                            <div class="small text-muted">Aplicacion automatica Bs. {{ number_format((float) $payment->amount_base, 2, '.', '') }}</div>
                        @else
                            <div class="small text-muted">Revision manual del hotel</div>
                        @endif
                    </td>
                    <td>{{ $filterOptions['paymentMethods'][$payment->payment_method] ?? $payment->payment_method }}</td>
                    <td>{{ $filterOptions['paymentStatuses'][$payment->status] ?? $payment->status }}</td>
                    <td>{{ optional($payment->payment_date)->format('d/m/Y') }}</td>
                    <td>{{ optional($payment->confirmed_at)->format('d/m/Y H:i') ?: '-' }}</td>
                    <td>{{ $payment->confirmedBy?->name ?: '-' }}</td>
                    <td>{{ $payment->reference_number ?: '-' }}</td>
                </tr>
            @empty
                <tr><td colspan="10" class="text-center text-muted">No hay pagos para los filtros seleccionados.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>
