@php
    $filters = $report['filters'];
    $summary = $report['summary'];
@endphp
<div class="report-panel" data-report-panel="occupancy">
    <form class="row g-3 report-filter-form" data-report-form="occupancy" action="{{ route('adminlte.reports.occupancy') }}" method="GET">
        <div class="col-md-4"><label class="form-label">Desde</label><input type="date" name="date_from" value="{{ $filters['date_from'] }}" class="form-control"></div>
        <div class="col-md-4"><label class="form-label">Hasta</label><input type="date" name="date_to" value="{{ $filters['date_to'] }}" class="form-control"></div>
        <div class="col-md-4"><label class="form-label">Tipo habitacion</label><select name="room_type_id" class="form-select"><option value="">Todos</option>@foreach ($filterOptions['roomTypes'] as $roomType)<option value="{{ $roomType->id }}" @selected((string) ($filters['room_type_id'] ?? '') === (string) $roomType->id)>{{ $roomType->name }}</option>@endforeach</select></div>
        <div class="col-12 d-flex gap-2 flex-wrap"><button type="submit" class="btn btn-primary">Buscar</button><button type="button" class="btn btn-outline-secondary" data-report-reset="occupancy">Limpiar</button></div>
    </form>
    <div class="row g-3 mt-1">
        <div class="col-md-3"><div class="small-box text-bg-primary"><div class="inner"><h3>{{ $summary['total_rooms'] }}</h3><p>Total habitaciones</p></div></div></div>
        <div class="col-md-3"><div class="small-box text-bg-success"><div class="inner"><h3>{{ $summary['available'] }}</h3><p>Disponibles</p></div></div></div>
        <div class="col-md-3"><div class="small-box text-bg-warning"><div class="inner"><h3>{{ $summary['reserved'] }}</h3><p>Reservadas</p></div></div></div>
        <div class="col-md-3"><div class="small-box text-bg-danger"><div class="inner"><h3>{{ $summary['occupancy_rate'] }}%</h3><p>Ocupacion actual</p></div></div></div>
    </div>
    <div class="row g-4 mt-2">
        <div class="col-lg-6">
            <div class="card"><div class="card-header"><strong>Ocupacion por tipo</strong></div><div class="card-body table-responsive">
                <table class="table table-sm table-striped"><thead><tr><th>Tipo</th><th>Total</th><th>Ocupadas</th><th>Reservadas</th><th>Activas</th><th>%</th></tr></thead><tbody>
                @forelse($report['by_room_type'] as $row)
                    <tr><td>{{ $row['room_type_name'] }}</td><td>{{ $row['total_rooms'] }}</td><td>{{ $row['occupied'] }}</td><td>{{ $row['reserved'] }}</td><td>{{ $row['active_reservations'] }}</td><td>{{ number_format($row['occupancy_rate'], 2, '.', '') }}%</td></tr>
                @empty
                    <tr><td colspan="6" class="text-center text-muted">Sin datos.</td></tr>
                @endforelse
                </tbody></table>
            </div></div>
        </div>
        <div class="col-lg-6">
            <div class="card"><div class="card-header"><strong>Reservas activas por rango</strong></div><div class="card-body table-responsive">
                <table class="table table-sm table-striped"><thead><tr><th>Codigo</th><th>Cliente</th><th>Tipo</th><th>Entrada</th><th>Salida</th><th>Estado</th></tr></thead><tbody>
                @forelse($report['active_reservations'] as $reservation)
                    <tr><td>{{ $reservation->code }}</td><td>{{ $reservation->customer?->full_name }}</td><td>{{ $reservation->roomType?->name ?? '-' }}</td><td>{{ optional($reservation->check_in)->format('d/m/Y') }}</td><td>{{ optional($reservation->check_out)->format('d/m/Y') }}</td><td>{{ $filterOptions['reservationStatuses'][$reservation->status] ?? $reservation->status }}</td></tr>
                @empty
                    <tr><td colspan="6" class="text-center text-muted">Sin reservas activas en el rango.</td></tr>
                @endforelse
                </tbody></table>
            </div></div>
        </div>
    </div>
</div>
