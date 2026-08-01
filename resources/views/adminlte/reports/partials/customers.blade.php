@php
    $filters = $report['filters'];
    $summary = $report['summary'];
@endphp
<div class="report-panel" data-report-panel="customers">
    <form class="row g-3 report-filter-form" data-report-form="customers" action="{{ route('adminlte.reports.customers') }}" method="GET">
        <div class="col-md-2"><label class="form-label">Desde</label><input type="date" name="date_from" value="{{ $filters['date_from'] }}" class="form-control"></div>
        <div class="col-md-2"><label class="form-label">Hasta</label><input type="date" name="date_to" value="{{ $filters['date_to'] }}" class="form-control"></div>
        <div class="col-md-3"><label class="form-label">Nacionalidad</label><input type="text" name="nationality" value="{{ $filters['nationality'] ?? '' }}" class="form-control"></div>
        <div class="col-md-2"><label class="form-label">Tipo</label><select name="is_company" class="form-select"><option value="">Todos</option><option value="0" @selected(($filters['is_company'] ?? null) === '0')>Persona</option><option value="1" @selected(($filters['is_company'] ?? null) === '1')>Empresa</option></select></div>
        <div class="col-md-3"><label class="form-label">Estado</label><select name="is_active" class="form-select"><option value="">Todos</option><option value="1" @selected(($filters['is_active'] ?? null) === '1')>Activo</option><option value="0" @selected(($filters['is_active'] ?? null) === '0')>Inactivo</option></select></div>
        <div class="col-12 d-flex gap-2 flex-wrap"><button type="submit" class="btn btn-primary">Buscar</button><button type="button" class="btn btn-outline-secondary" data-report-reset="customers">Limpiar</button></div>
    </form>
    <div class="row g-3 mt-1">
        <div class="col-md-3"><div class="small-box text-bg-primary"><div class="inner"><h3>{{ $summary['total_customers'] }}</h3><p>Clientes</p></div></div></div>
        <div class="col-md-3"><div class="small-box text-bg-success"><div class="inner"><h3>{{ $summary['active_customers'] }}</h3><p>Activos</p></div></div></div>
        <div class="col-md-3"><div class="small-box text-bg-warning"><div class="inner"><h3>{{ $summary['foreign_customers'] }}</h3><p>Extranjeros</p></div></div></div>
        <div class="col-md-3"><div class="small-box text-bg-secondary"><div class="inner"><h3>{{ $summary['companies'] }}</h3><p>Empresas</p></div></div></div>
    </div>
    <div class="table-responsive mt-3">
        <table class="table table-striped table-bordered align-middle">
            <thead><tr><th>Cliente</th><th>Documento</th><th>Nacionalidad</th><th>Contacto</th><th>Email</th><th>Reservas</th><th>Total reservado</th><th>Total pagado</th><th>Ultima reserva</th><th>Estado</th></tr></thead>
            <tbody>
            @forelse ($report['rows'] as $customer)
                @php
                    $reservations = $customer->reservations ?? collect();
                    $payments = $customer->payments ?? collect();
                @endphp
                <tr>
                    <td>{{ $customer->full_name }}</td>
                    <td>{{ trim(collect([$customer->document_type, $customer->document_number])->filter()->implode(' - ')) ?: '-' }}</td>
                    <td>{{ $customer->nationality ?: '-' }}</td>
                    <td>{{ $customer->whatsapp ?: ($customer->phone ?: '-') }}</td>
                    <td>{{ $customer->email ?: '-' }}</td>
                    <td>{{ $reservations->count() }}</td>
                    <td>Bs. {{ number_format((float) $reservations->sum('total_amount'), 2, '.', '') }}</td>
                    <td>Bs. {{ number_format((float) $payments->sum('amount'), 2, '.', '') }}</td>
                    <td>{{ optional($reservations->sortByDesc('created_at')->first()?->created_at)->format('d/m/Y H:i') ?: '-' }}</td>
                    <td>{{ $customer->is_active ? 'Activo' : 'Inactivo' }}</td>
                </tr>
            @empty
                <tr><td colspan="10" class="text-center text-muted">No hay clientes para los filtros seleccionados.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>
