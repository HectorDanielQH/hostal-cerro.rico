@php
    $filters = $report['filters'];
    $summary = $report['summary'];
@endphp
<div class="report-panel" data-report-panel="reservations">
    <form class="row g-3 report-filter-form" data-report-form="reservations" action="{{ route('adminlte.reports.reservations') }}" method="GET">
        <div class="col-md-2">
            <label class="form-label">Desde</label>
            <input type="date" name="date_from" value="{{ $filters['date_from'] }}" class="form-control">
        </div>
        <div class="col-md-2">
            <label class="form-label">Hasta</label>
            <input type="date" name="date_to" value="{{ $filters['date_to'] }}" class="form-control">
        </div>
        <div class="col-md-2">
            <label class="form-label">Estado</label>
            <select name="status" class="form-select">
                <option value="">Todos</option>
                @foreach ($filterOptions['reservationStatuses'] as $value => $label)
                    <option value="{{ $value }}" @selected(($filters['status'] ?? null) === $value)>{{ $label }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-2">
            <label class="form-label">Origen</label>
            <select name="source" class="form-select">
                <option value="">Todos</option>
                @foreach ($filterOptions['reservationSources'] as $value => $label)
                    <option value="{{ $value }}" @selected(($filters['source'] ?? null) === $value)>{{ $label }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-2">
            <label class="form-label">Tipo habitacion</label>
            <select name="room_type_id" class="form-select">
                <option value="">Todos</option>
                @foreach ($filterOptions['roomTypes'] as $roomType)
                    <option value="{{ $roomType->id }}" @selected((string) ($filters['room_type_id'] ?? '') === (string) $roomType->id)>{{ $roomType->name }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-2">
            <label class="form-label">Cliente</label>
            <select name="customer_id" class="form-select">
                <option value="">Todos</option>
                @foreach ($filterOptions['customers'] as $customer)
                    <option value="{{ $customer->id }}" @selected((string) ($filters['customer_id'] ?? '') === (string) $customer->id)>{{ $customer->full_name }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-12 d-flex gap-2 flex-wrap">
            <button type="submit" class="btn btn-primary">Buscar</button>
            <button type="button" class="btn btn-outline-secondary" data-report-reset="reservations">Limpiar</button>
            @if ($canExport)
                <a href="{{ route('adminlte.reports.reservations.export', $filters) }}" class="btn btn-success">Exportar CSV</a>
            @endif
        </div>
    </form>

    <div class="row g-3 mt-1">
        <div class="col-md-3"><div class="small-box text-bg-primary"><div class="inner"><h3>{{ $summary['total_reservations'] }}</h3><p>Reservas</p></div></div></div>
        <div class="col-md-3"><div class="small-box text-bg-warning"><div class="inner"><h3>{{ $summary['pending'] }}</h3><p>Pendientes</p></div></div></div>
        <div class="col-md-3"><div class="small-box text-bg-success"><div class="inner"><h3>Bs. {{ number_format($summary['paid_amount'], 2, '.', '') }}</h3><p>Pagado</p></div></div></div>
        <div class="col-md-3"><div class="small-box text-bg-secondary"><div class="inner"><h3>Bs. {{ number_format($summary['balance_amount'], 2, '.', '') }}</h3><p>Saldo</p></div></div></div>
        <div class="col-md-3"><div class="small-box text-bg-dark"><div class="inner"><h3>{{ $summary['expired'] ?? 0 }}</h3><p>Expiradas</p></div></div></div>
    </div>

    <div class="table-responsive mt-3">
        <table class="table table-striped table-bordered align-middle">
            <thead>
                <tr>
                    <th>Codigo</th><th>Cliente</th><th>Habitacion</th><th>Tipo</th><th>Entrada</th><th>Salida</th><th>Noches</th><th>Adultos</th><th>Ninos</th><th>Estado</th><th>Origen</th><th>Total</th><th>Pagado</th><th>Saldo</th><th>Creada</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($report['rows'] as $reservation)
                    <tr>
                        <td>{{ $reservation->code }}</td>
                        <td>{{ $reservation->customer?->full_name }}</td>
                        <td>{{ $reservation->room?->number ?: '-' }}</td>
                        <td>{{ $reservation->roomType?->name ?? $reservation->room?->roomType?->name ?? '-' }}</td>
                        <td>{{ optional($reservation->check_in)->format('d/m/Y') }}</td>
                        <td>{{ optional($reservation->check_out)->format('d/m/Y') }}</td>
                        <td>{{ $reservation->nights }}</td>
                        <td>{{ $reservation->adults }}</td>
                        <td>{{ $reservation->children }}</td>
                        <td>{{ $filterOptions['reservationStatuses'][$reservation->status] ?? $reservation->status }}</td>
                        <td>{{ $filterOptions['reservationSources'][$reservation->source] ?? $reservation->source }}</td>
                        <td>Bs. {{ number_format((float) $reservation->total_amount, 2, '.', '') }}</td>
                        <td>Bs. {{ number_format((float) $reservation->paid_amount, 2, '.', '') }}</td>
                        <td>Bs. {{ number_format((float) $reservation->balance_amount, 2, '.', '') }}</td>
                        <td>{{ optional($reservation->created_at)->format('d/m/Y H:i') }}</td>
                    </tr>
                @empty
                    <tr><td colspan="15" class="text-center text-muted">No hay registros para los filtros seleccionados.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
