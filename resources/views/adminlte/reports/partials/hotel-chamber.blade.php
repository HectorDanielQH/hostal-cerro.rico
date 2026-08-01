@php
    $filters = $report['filters'];
    $summary = $report['summary'];
    $rows = $report['rows'];
@endphp

<div class="report-panel" data-report-panel="hotel-chamber">
    <form class="row g-3 report-filter-form" data-report-form="hotel-chamber" action="{{ route('adminlte.reports.hotel-chamber') }}" method="GET">
        <div class="col-md-2">
            <label class="form-label">Desde</label>
            <input type="date" name="date_from" class="form-control" value="{{ $filters['date_from'] }}">
        </div>
        <div class="col-md-2">
            <label class="form-label">Hasta</label>
            <input type="date" name="date_to" class="form-control" value="{{ $filters['date_to'] }}">
        </div>
        <div class="col-md-3">
            <label class="form-label">Tipo de habitacion</label>
            <select name="room_type_id" class="form-select">
                <option value="">Todos</option>
                @foreach ($filterOptions['roomTypes'] as $roomType)
                    <option value="{{ $roomType->id }}" @selected((string) ($filters['room_type_id'] ?? '') === (string) $roomType->id)>{{ $roomType->name }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-2">
            <label class="form-label">Nacionalidad</label>
            <input type="text" name="nationality" class="form-control" value="{{ $filters['nationality'] ?? '' }}" placeholder="Ej. Bolivia, Argentina">
        </div>
        <div class="col-md-3">
            <label class="form-label">Situacion</label>
            <select name="lodging_status" class="form-select">
                <option value="all" @selected(($filters['lodging_status'] ?? 'all') === 'all')>Todos los hospedajes del periodo</option>
                <option value="currently_hosted" @selected(($filters['lodging_status'] ?? '') === 'currently_hosted')>Hospedados actualmente</option>
                <option value="overstayed" @selected(($filters['lodging_status'] ?? '') === 'overstayed')>Se pasaron de salida</option>
                <option value="extended" @selected(($filters['lodging_status'] ?? '') === 'extended')>Salida extendida</option>
                <option value="checked_out" @selected(($filters['lodging_status'] ?? '') === 'checked_out')>Con salida registrada</option>
            </select>
        </div>
        <div class="col-12 d-flex gap-2 flex-wrap">
            <button type="submit" class="btn btn-primary">Buscar</button>
            <button type="button" class="btn btn-outline-secondary" data-report-reset="hotel-chamber">Limpiar</button>
            @if($canExport)
                <a href="{{ route('adminlte.reports.hotel-chamber.export', $filters) }}" class="btn btn-success">Descargar CSV Camara Hotelera</a>
            @endif
        </div>
    </form>

    <div class="row g-3 my-3">
        <div class="col-md-2"><div class="small-box text-bg-primary"><div class="inner"><h3>{{ $summary['total_guests'] }}</h3><p>Huespedes</p></div></div></div>
        <div class="col-md-2"><div class="small-box text-bg-info"><div class="inner"><h3>{{ $summary['total_reservations'] }}</h3><p>Reservas</p></div></div></div>
        <div class="col-md-2"><div class="small-box text-bg-success"><div class="inner"><h3>{{ $summary['currently_hosted'] }}</h3><p>Hospedados</p></div></div></div>
        <div class="col-md-2"><div class="small-box text-bg-warning"><div class="inner"><h3>{{ $summary['foreign_guests'] }}</h3><p>Extranjeros</p></div></div></div>
        <div class="col-md-2"><div class="small-box text-bg-danger"><div class="inner"><h3>{{ $summary['overstayed'] }}</h3><p>Se pasaron</p></div></div></div>
        <div class="col-md-2"><div class="small-box text-bg-secondary"><div class="inner"><h3>{{ $summary['extended'] }}</h3><p>Extendidos</p></div></div></div>
    </div>

    <div class="alert alert-info">
        Este reporte esta pensado para envio diario a la camara hotelera: muestra quienes se hospedan en el periodo, nacionalidad, fechas desde/hasta, habitacion, estado y observaciones de sobreestadia o extension.
    </div>

    <div class="table-responsive">
        <table class="table table-hover align-middle">
            <thead>
                <tr>
                    <th>Reserva</th>
                    <th>Huesped</th>
                    <th>Nacionalidad</th>
                    <th>Habitacion</th>
                    <th>Desde / Hasta</th>
                    <th>Check-in/out real</th>
                    <th>Huespedes</th>
                    <th>Estado</th>
                    <th>Observacion</th>
                </tr>
            </thead>
            <tbody>
                @forelse($rows as $row)
                    <tr>
                        <td>
                            <strong>{{ $row['reservation_code'] }}</strong>
                            <div class="small text-muted">{{ $row['source'] }}</div>
                        </td>
                        <td>
                            <strong>{{ $row['guest_name'] }}</strong>
                            <div class="small text-muted">{{ $row['document_type'] }} {{ $row['document_number'] ?: '-' }}</div>
                            <div class="small text-muted">{{ $row['phone'] ?: $row['email'] }}</div>
                        </td>
                        <td>
                            {{ $row['nationality'] }}
                            <div class="small text-muted">{{ $row['country'] }} / {{ $row['city'] }}</div>
                        </td>
                        <td>
                            Hab. {{ $row['room_number'] }}
                            <div class="small text-muted">{{ $row['room_type'] }}</div>
                        </td>
                        <td>
                            {{ $row['check_in'] }} - {{ $row['check_out'] }}
                            <div class="small text-muted">{{ $row['reserved_nights'] }} noche(s) reservadas</div>
                        </td>
                        <td>
                            <div>{{ $row['checked_in_at'] ?: 'Sin check-in real' }}</div>
                            <div class="small text-muted">{{ $row['checked_out_at'] ?: 'Sin check-out real' }}</div>
                        </td>
                        <td>{{ $row['adults'] }} adulto(s) / {{ $row['children'] }} nino(s)</td>
                        <td><span class="badge text-bg-{{ $row['is_overstayed'] ? 'danger' : ($row['is_currently_hosted'] ? 'success' : 'secondary') }}">{{ $row['status_label'] }}</span></td>
                        <td>{{ $row['operational_observation'] }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="9" class="text-center text-muted py-4">No hay huespedes para los filtros seleccionados.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
