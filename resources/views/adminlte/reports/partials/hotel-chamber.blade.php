@php
    $filters = $report['filters'];
    $summary = $report['summary'];
    $rows = $report['rows'];
    $officialHeadings = $report['official_headings'];
    $officialRows = $report['official_rows'];
    $generalHeadings = $report['general_headings'];
    $generalRows = $report['general_rows'];
    $catalogRows = $report['catalog_rows'];
@endphp

<div class="report-panel hotel-chamber-report" data-report-panel="hotel-chamber">
    <div class="hotel-chamber-hero">
        <div>
            <span class="hotel-chamber-kicker">Formato Camara Hotelera</span>
            <h3>Informe de servicios de hospedaje</h3>
            <p>
                Esta pantalla toma los datos reales del sistema y los acomoda con las columnas del archivo oficial.
                Cada titular y acompanante registrado sale como una fila independiente.
            </p>
        </div>
        @if($canExport)
            <a href="{{ route('adminlte.reports.hotel-chamber.export', $filters) }}" class="btn btn-success">
                <i class="bi bi-file-earmark-spreadsheet me-1"></i>
                Descargar Excel oficial
            </a>
        @endif
    </div>

    <form class="row g-3 report-filter-form hotel-chamber-filter" data-report-form="hotel-chamber" action="{{ route('adminlte.reports.hotel-chamber') }}" method="GET">
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
            <input type="text" name="nationality" class="form-control" value="{{ $filters['nationality'] ?? '' }}" placeholder="Ej. Boliviano">
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
            <button type="submit" class="btn btn-primary">
                <i class="bi bi-search me-1"></i>
                Buscar
            </button>
            <button type="button" class="btn btn-outline-secondary" data-report-reset="hotel-chamber">Limpiar</button>
        </div>
    </form>

    <div class="row g-3 my-4">
        <div class="col-md-2"><div class="hotel-chamber-stat"><span>Huespedes</span><strong>{{ $summary['total_guests'] }}</strong></div></div>
        <div class="col-md-2"><div class="hotel-chamber-stat"><span>Reservas</span><strong>{{ $summary['total_reservations'] }}</strong></div></div>
        <div class="col-md-2"><div class="hotel-chamber-stat is-green"><span>Hospedados</span><strong>{{ $summary['currently_hosted'] }}</strong></div></div>
        <div class="col-md-2"><div class="hotel-chamber-stat is-gold"><span>Extranjeros</span><strong>{{ $summary['foreign_guests'] }}</strong></div></div>
        <div class="col-md-2"><div class="hotel-chamber-stat is-red"><span>Se pasaron</span><strong>{{ $summary['overstayed'] }}</strong></div></div>
        <div class="col-md-2"><div class="hotel-chamber-stat is-muted"><span>Extendidos</span><strong>{{ $summary['extended'] }}</strong></div></div>
    </div>

    <div class="hotel-chamber-note">
        <i class="bi bi-info-circle"></i>
        <div>
            <strong>Importante para administracion:</strong>
            factura, CUF y numero de autorizacion quedan vacios porque este sistema aun no registra facturacion tributaria.
            Si esos datos se agregan al sistema, este reporte ya tiene la columna lista para llenarlos.
        </div>
    </div>

    <div class="hotel-chamber-section">
        <div class="hotel-chamber-section-head">
            <div>
                <span>Hoja 1</span>
                <h4>Informe de servicios</h4>
            </div>
            <small>{{ $officialRows->count() }} fila(s) generadas</small>
        </div>
        <div class="table-responsive hotel-chamber-sheet">
            <table class="table table-bordered table-sm align-middle mb-0">
                <thead>
                    <tr>
                        @foreach($officialHeadings as $heading)
                            <th>{{ $heading }}</th>
                        @endforeach
                    </tr>
                </thead>
                <tbody>
                    @forelse($officialRows as $officialRow)
                        <tr>
                            @foreach($officialRow as $cell)
                                <td>{{ $cell }}</td>
                            @endforeach
                        </tr>
                    @empty
                        <tr>
                            <td colspan="{{ count($officialHeadings) }}" class="text-center text-muted py-4">No hay huespedes para los filtros seleccionados.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="hotel-chamber-section">
        <div class="hotel-chamber-section-head">
            <div>
                <span>Hoja 2</span>
                <h4>Datos generales del establecimiento</h4>
            </div>
            <small>Tomado de Configuracion del hotel y habitaciones registradas</small>
        </div>
        <div class="table-responsive hotel-chamber-sheet is-general">
            <table class="table table-bordered table-sm align-middle mb-0">
                <thead>
                    <tr>
                        @foreach($generalHeadings as $heading)
                            <th>{{ $heading }}</th>
                        @endforeach
                    </tr>
                </thead>
                <tbody>
                    @foreach($generalRows as $generalRow)
                        <tr>
                            @foreach($generalRow as $cell)
                                <td>{{ $cell }}</td>
                            @endforeach
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    <div class="hotel-chamber-section">
        <div class="hotel-chamber-section-head">
            <div>
                <span>Hoja 3</span>
                <h4>Catalogos de referencia</h4>
            </div>
            <small>Equivalente a la hoja datos del archivo modelo</small>
        </div>
        <div class="table-responsive hotel-chamber-sheet is-catalog">
            <table class="table table-bordered table-sm align-middle mb-0">
                <tbody>
                    @foreach($catalogRows as $catalogRow)
                        <tr>
                            @foreach($catalogRow as $cell)
                                <td>{{ $cell }}</td>
                            @endforeach
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    <div class="hotel-chamber-section">
        <div class="hotel-chamber-section-head">
            <div>
                <span>Revision interna</span>
                <h4>Detalle entendible para el hotel</h4>
            </div>
            <small>Sirve para verificar antes de enviar</small>
        </div>
        <div class="table-responsive">
            <table class="table table-hover align-middle">
                <thead>
                    <tr>
                        <th>Huesped</th>
                        <th>Documento</th>
                        <th>Nacionalidad</th>
                        <th>Habitacion</th>
                        <th>Entrada / salida</th>
                        <th>Estado</th>
                        <th>Observacion</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($rows as $row)
                        <tr>
                            <td>
                                <strong>{{ $row['guest_name'] }}</strong>
                                <div class="small text-muted">{{ $row['guest_kind'] }} de {{ $row['reservation_code'] }}</div>
                            </td>
                            <td>
                                {{ $row['document_type'] ?: 'Documento' }}
                                <div class="small text-muted">{{ $row['document_number'] ?: 'Sin numero registrado' }}</div>
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
                                <div class="small text-muted">{{ $row['actual_nights'] }} noche(s) reales</div>
                            </td>
                            <td>
                                <span class="badge text-bg-{{ $row['is_overstayed'] ? 'danger' : ($row['is_currently_hosted'] ? 'success' : 'secondary') }}">
                                    {{ $row['status_label'] }}
                                </span>
                            </td>
                            <td>{{ $row['official_observation'] }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center text-muted py-4">No hay huespedes para los filtros seleccionados.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

<style>
    .hotel-chamber-report {
        color: #172033;
    }

    .hotel-chamber-hero,
    .hotel-chamber-filter,
    .hotel-chamber-section,
    .hotel-chamber-note,
    .hotel-chamber-stat {
        border: 1px solid rgba(15, 23, 42, .08);
        background: #fff;
        box-shadow: 0 16px 42px rgba(15, 23, 42, .08);
    }

    .hotel-chamber-hero {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 1rem;
        margin-bottom: 1rem;
        padding: 1.35rem;
        border-radius: 1.5rem;
        background:
            radial-gradient(circle at top right, rgba(37, 99, 235, .14), transparent 34%),
            linear-gradient(135deg, #ffffff, #f8fafc);
    }

    .hotel-chamber-hero h3 {
        margin: .15rem 0 .35rem;
        font-size: clamp(1.35rem, 2vw, 2rem);
        font-weight: 900;
    }

    .hotel-chamber-hero p {
        max-width: 760px;
        margin: 0;
        color: #667085;
    }

    .hotel-chamber-kicker,
    .hotel-chamber-section-head span {
        color: #2563eb;
        font-size: .76rem;
        font-weight: 900;
        letter-spacing: .12em;
        text-transform: uppercase;
    }

    .hotel-chamber-filter {
        padding: 1rem;
        border-radius: 1.25rem;
    }

    .hotel-chamber-stat {
        min-height: 112px;
        padding: 1rem;
        border-radius: 1.25rem;
        border-left: 5px solid #2563eb;
    }

    .hotel-chamber-stat span {
        display: block;
        color: #667085;
        font-size: .75rem;
        font-weight: 800;
        letter-spacing: .08em;
        text-transform: uppercase;
    }

    .hotel-chamber-stat strong {
        display: block;
        margin-top: .35rem;
        font-size: 2rem;
        font-weight: 950;
    }

    .hotel-chamber-stat.is-green {
        border-left-color: #16a34a;
    }

    .hotel-chamber-stat.is-gold {
        border-left-color: #d6a23d;
    }

    .hotel-chamber-stat.is-red {
        border-left-color: #dc2626;
    }

    .hotel-chamber-stat.is-muted {
        border-left-color: #64748b;
    }

    .hotel-chamber-note {
        display: flex;
        gap: .8rem;
        margin-bottom: 1rem;
        padding: 1rem 1.1rem;
        border-radius: 1.25rem;
        background: #fff8eb;
        color: #5f4214;
    }

    .hotel-chamber-note i {
        color: #d97706;
        font-size: 1.35rem;
    }

    .hotel-chamber-section {
        overflow: hidden;
        margin-top: 1rem;
        border-radius: 1.35rem;
    }

    .hotel-chamber-section-head {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 1rem;
        padding: 1rem 1.1rem;
        border-bottom: 1px solid rgba(15, 23, 42, .08);
        background: #f8fafc;
    }

    .hotel-chamber-section-head h4 {
        margin: .15rem 0 0;
        font-weight: 900;
    }

    .hotel-chamber-section-head small {
        color: #667085;
        font-weight: 700;
    }

    .hotel-chamber-sheet {
        max-height: 520px;
        overflow: auto;
    }

    .hotel-chamber-sheet thead th {
        position: sticky;
        top: 0;
        z-index: 1;
        min-width: 150px;
        background: #dbeafe;
        color: #0f172a;
        font-size: .72rem;
        text-transform: uppercase;
        vertical-align: middle;
    }

    .hotel-chamber-sheet tbody td {
        font-size: .84rem;
        white-space: nowrap;
    }

    .hotel-chamber-sheet.is-general thead th {
        background: #e0f2fe;
    }

    .hotel-chamber-sheet.is-catalog td:first-child {
        min-width: 120px;
        font-weight: 900;
    }

    .hotel-chamber-sheet.is-catalog tr:first-child td,
    .hotel-chamber-sheet.is-catalog tr:nth-child(13) td {
        background: #ecfdf5;
        color: #065f46;
        font-weight: 900;
        text-transform: uppercase;
    }

    @media (max-width: 768px) {
        .hotel-chamber-hero,
        .hotel-chamber-section-head {
            align-items: stretch;
            flex-direction: column;
        }

        .hotel-chamber-hero .btn {
            width: 100%;
        }
    }
</style>
