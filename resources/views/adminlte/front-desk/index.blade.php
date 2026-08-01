@extends('adminlte::page')

@section('title', 'Registro de entradas y salidas')

@php
    $operationalStatusesJson = collect($operationalRoomStatuses)->map(fn ($label, $value) => [
        'value' => $value,
        'label' => $label,
    ])->values();
@endphp

@section('content_header')
    <div class="frontdesk-heading">
        <div class="frontdesk-heading__eyebrow">
            <i class="bi bi-reception-4" aria-hidden="true"></i>
            Operacion diaria
        </div>

        <div class="d-flex flex-wrap align-items-end justify-content-between gap-3">
            <div>
                <h1 class="frontdesk-heading__title">Panel de recepcion</h1>
                <p class="frontdesk-heading__subtitle">
                    Controla llegadas, salidas, habitaciones ocupadas y estados operativos desde una sola pantalla.
                </p>
            </div>

            <div class="frontdesk-heading__actions">
                <button type="button" class="btn btn-light frontdesk-refresh-btn" id="frontdesk-refresh-button">
                    <i class="bi bi-arrow-clockwise me-1" aria-hidden="true"></i>
                    Actualizar
                </button>

                @can('reservas.ver')
                    <a href="{{ route('adminlte.reservations.index') }}" class="btn btn-primary">
                        <i class="bi bi-calendar2-check me-1" aria-hidden="true"></i>
                        Reservas
                    </a>
                @endcan
            </div>
        </div>
    </div>
@stop

@section('content')
    <section class="frontdesk-stats" aria-label="Resumen de recepcion">
        <article class="frontdesk-stat frontdesk-stat--arrivals">
            <div class="frontdesk-stat__icon">
                <i class="bi bi-box-arrow-in-right" aria-hidden="true"></i>
            </div>
            <div>
                <span class="frontdesk-stat__label">Llegadas de hoy</span>
                <strong class="frontdesk-stat__value" id="summary-arrivals">0</strong>
                <small class="frontdesk-stat__meta">Reservas confirmadas</small>
            </div>
        </article>

        <article class="frontdesk-stat frontdesk-stat--departures">
            <div class="frontdesk-stat__icon">
                <i class="bi bi-box-arrow-right" aria-hidden="true"></i>
            </div>
            <div>
                <span class="frontdesk-stat__label">Salidas de hoy</span>
                <strong class="frontdesk-stat__value" id="summary-departures">0</strong>
                <small class="frontdesk-stat__meta">Check-out pendiente</small>
            </div>
        </article>

        <article class="frontdesk-stat frontdesk-stat--occupied">
            <div class="frontdesk-stat__icon">
                <i class="bi bi-house-lock" aria-hidden="true"></i>
            </div>
            <div>
                <span class="frontdesk-stat__label">Ocupadas</span>
                <strong class="frontdesk-stat__value" id="summary-occupied">0</strong>
                <small class="frontdesk-stat__meta">Huespedes alojados</small>
            </div>
        </article>

        <article class="frontdesk-stat frontdesk-stat--available">
            <div class="frontdesk-stat__icon">
                <i class="bi bi-door-open" aria-hidden="true"></i>
            </div>
            <div>
                <span class="frontdesk-stat__label">Disponibles</span>
                <strong class="frontdesk-stat__value" id="summary-available">0</strong>
                <small class="frontdesk-stat__meta">Listas para vender</small>
            </div>
        </article>

        <article class="frontdesk-stat frontdesk-stat--reserved">
            <div class="frontdesk-stat__icon">
                <i class="bi bi-calendar2-check" aria-hidden="true"></i>
            </div>
            <div>
                <span class="frontdesk-stat__label">Reservadas</span>
                <strong class="frontdesk-stat__value" id="summary-reserved">0</strong>
                <small class="frontdesk-stat__meta">Con fechas confirmadas</small>
            </div>
        </article>
    </section>

    <x-adminlte-card icon="bi bi-clipboard2-check" title="Operacion diaria de recepcion" bodyClass="p-0" class="frontdesk-operations-card">
        <div class="frontdesk-operations-intro">
            <div>
                <span class="frontdesk-operations-intro__kicker">Recepcion en vivo</span>
                <h2 class="frontdesk-operations-intro__title">Prioridades del turno</h2>
                <p class="frontdesk-operations-intro__text">
                    Usa estas pestanas para ejecutar entradas, salidas y cambios de estado sin salir del panel.
                </p>
            </div>
            <span class="frontdesk-operations-intro__badge">
                <i class="bi bi-shield-check" aria-hidden="true"></i>
                Acciones seguras
            </span>
        </div>

        <ul class="nav nav-tabs frontdesk-tabs" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link active" data-bs-toggle="tab" data-bs-target="#arrivals-pane" type="button" role="tab">
                    <i class="bi bi-calendar-check me-1" aria-hidden="true"></i>
                    Llegadas de hoy
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" data-bs-toggle="tab" data-bs-target="#departures-pane" type="button" role="tab">
                    <i class="bi bi-calendar-x me-1" aria-hidden="true"></i>
                    Salidas de hoy
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" data-bs-toggle="tab" data-bs-target="#occupied-pane" type="button" role="tab">
                    <i class="bi bi-person-check me-1" aria-hidden="true"></i>
                    Alojados
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" data-bs-toggle="tab" data-bs-target="#rooms-pane" type="button" role="tab">
                    <i class="bi bi-grid-3x3-gap me-1" aria-hidden="true"></i>
                    Estado de habitaciones
                </button>
            </li>
        </ul>

        <div class="tab-content p-3">
            <div class="tab-pane fade show active" id="arrivals-pane" role="tabpanel">
                <div class="table-responsive">
                    <table class="table table-hover align-middle w-100" id="arrivals-table">
                        <thead>
                            <tr>
                                <th>Reserva</th>
                                <th>Cliente</th>
                                <th>Habitacion</th>
                                <th>Fechas</th>
                                <th>Huespedes</th>
                                <th>Total</th>
                                <th>Saldo</th>
                                <th>Estado</th>
                                <th class="text-end">Acciones</th>
                            </tr>
                        </thead>
                    </table>
                </div>
            </div>

            <div class="tab-pane fade" id="departures-pane" role="tabpanel">
                <div class="table-responsive">
                    <table class="table table-hover align-middle w-100" id="departures-table">
                        <thead>
                            <tr>
                                <th>Reserva</th>
                                <th>Cliente</th>
                                <th>Habitacion</th>
                                <th>Fechas</th>
                                <th>Huespedes</th>
                                <th>Total</th>
                                <th>Saldo</th>
                                <th>Estado</th>
                                <th class="text-end">Acciones</th>
                            </tr>
                        </thead>
                    </table>
                </div>
            </div>

            <div class="tab-pane fade" id="occupied-pane" role="tabpanel">
                <div class="table-responsive">
                    <table class="table table-hover align-middle w-100" id="occupied-table">
                        <thead>
                            <tr>
                                <th>Reserva</th>
                                <th>Cliente</th>
                                <th>Habitacion</th>
                                <th>Fechas</th>
                                <th>Huespedes</th>
                                <th>Saldo</th>
                                <th>Estado</th>
                                <th class="text-end">Acciones</th>
                            </tr>
                        </thead>
                    </table>
                </div>
            </div>

            <div class="tab-pane fade" id="rooms-pane" role="tabpanel">
                <div class="table-responsive">
                    <table class="table table-hover align-middle w-100" id="rooms-status-table">
                        <thead>
                            <tr>
                                <th>Habitacion</th>
                                <th>Tipo</th>
                                <th>Piso</th>
                                <th>Precio</th>
                                <th>Estado</th>
                                <th>Reserva</th>
                                <th>Activo</th>
                                <th class="text-end">Acciones</th>
                            </tr>
                        </thead>
                    </table>
                </div>
            </div>
        </div>
    </x-adminlte-card>
@stop

@push('css')
    <link rel="stylesheet" href="https://cdn.datatables.net/2.3.8/css/dataTables.bootstrap5.css">

    <style>
        .frontdesk-heading {
            position: relative;
            overflow: hidden;
            padding: 1.35rem;
            border: 1px solid rgba(15, 23, 42, 0.08);
            border-radius: 1.35rem;
            background:
                radial-gradient(circle at top left, rgba(13, 110, 253, 0.22), transparent 34%),
                linear-gradient(135deg, #0f172a 0%, #1e293b 58%, #334155 100%);
            color: #fff;
            box-shadow: 0 18px 45px rgba(15, 23, 42, 0.18);
        }

        .frontdesk-heading::after {
            position: absolute;
            inset: auto -4rem -5rem auto;
            width: 13rem;
            height: 13rem;
            content: "";
            border-radius: 999px;
            background: rgba(255, 255, 255, 0.08);
        }

        .frontdesk-heading__eyebrow {
            display: inline-flex;
            gap: 0.45rem;
            align-items: center;
            padding: 0.35rem 0.7rem;
            margin-bottom: 0.85rem;
            border: 1px solid rgba(255, 255, 255, 0.18);
            border-radius: 999px;
            background: rgba(255, 255, 255, 0.1);
            color: rgba(255, 255, 255, 0.78);
            font-size: 0.72rem;
            font-weight: 700;
            letter-spacing: 0.08em;
            text-transform: uppercase;
        }

        .frontdesk-heading__title {
            margin: 0;
            font-size: clamp(1.7rem, 3vw, 2.45rem);
            font-weight: 800;
            letter-spacing: -0.04em;
        }

        .frontdesk-heading__subtitle {
            max-width: 46rem;
            margin: 0.45rem 0 0;
            color: rgba(255, 255, 255, 0.72);
            font-size: 0.98rem;
        }

        .frontdesk-heading__actions {
            position: relative;
            z-index: 1;
            display: flex;
            flex-wrap: wrap;
            gap: 0.65rem;
        }

        .frontdesk-heading__actions .btn {
            border-radius: 999px;
            padding: 0.65rem 1rem;
            font-weight: 700;
            box-shadow: 0 12px 24px rgba(15, 23, 42, 0.18);
        }

        .frontdesk-refresh-btn.is-loading i {
            animation: frontdeskSpin 0.8s linear infinite;
        }

        .frontdesk-stats {
            display: grid;
            grid-template-columns: repeat(6, minmax(0, 1fr));
            gap: 1rem;
            margin-bottom: 1rem;
        }

        .frontdesk-stat {
            position: relative;
            display: flex;
            gap: 0.85rem;
            min-height: 8.25rem;
            padding: 1rem;
            overflow: hidden;
            border: 1px solid rgba(15, 23, 42, 0.08);
            border-radius: 1.15rem;
            background: #fff;
            box-shadow: 0 14px 34px rgba(15, 23, 42, 0.08);
        }

        .frontdesk-stat::after {
            position: absolute;
            inset: auto -2.3rem -2.7rem auto;
            width: 7rem;
            height: 7rem;
            content: "";
            border-radius: 999px;
            background: var(--frontdesk-accent-soft);
        }

        .frontdesk-stat__icon {
            display: grid;
            flex: 0 0 2.9rem;
            width: 2.9rem;
            height: 2.9rem;
            place-items: center;
            border-radius: 1rem;
            background: var(--frontdesk-accent-soft);
            color: var(--frontdesk-accent);
            font-size: 1.35rem;
        }

        .frontdesk-stat__label,
        .frontdesk-stat__meta {
            display: block;
            color: #64748b;
            font-size: 0.78rem;
            font-weight: 700;
            line-height: 1.25;
        }

        .frontdesk-stat__label {
            color: #475569;
            letter-spacing: 0.03em;
            text-transform: uppercase;
        }

        .frontdesk-stat__value {
            display: block;
            margin: 0.15rem 0;
            color: #0f172a;
            font-size: 2.05rem;
            font-weight: 850;
            line-height: 1;
            letter-spacing: -0.04em;
        }

        .frontdesk-stat--arrivals {
            --frontdesk-accent: #2563eb;
            --frontdesk-accent-soft: rgba(37, 99, 235, 0.12);
        }

        .frontdesk-stat--departures {
            --frontdesk-accent: #0891b2;
            --frontdesk-accent-soft: rgba(8, 145, 178, 0.13);
        }

        .frontdesk-stat--occupied {
            --frontdesk-accent: #dc2626;
            --frontdesk-accent-soft: rgba(220, 38, 38, 0.12);
        }

        .frontdesk-stat--available {
            --frontdesk-accent: #16a34a;
            --frontdesk-accent-soft: rgba(22, 163, 74, 0.13);
        }

        .frontdesk-stat--reserved {
            --frontdesk-accent: #f59e0b;
            --frontdesk-accent-soft: rgba(245, 158, 11, 0.16);
        }

        .frontdesk-operations-card {
            overflow: hidden;
            border: 0;
            border-radius: 1.2rem;
            box-shadow: 0 16px 42px rgba(15, 23, 42, 0.08);
        }

        .frontdesk-operations-intro {
            display: flex;
            flex-wrap: wrap;
            gap: 1rem;
            align-items: center;
            justify-content: space-between;
            padding: 1rem 1.15rem;
            border-bottom: 1px solid rgba(15, 23, 42, 0.08);
            background: linear-gradient(135deg, #f8fafc 0%, #eef2ff 100%);
        }

        .frontdesk-operations-intro__kicker {
            color: #2563eb;
            font-size: 0.72rem;
            font-weight: 800;
            letter-spacing: 0.08em;
            text-transform: uppercase;
        }

        .frontdesk-operations-intro__title {
            margin: 0.15rem 0;
            color: #0f172a;
            font-size: 1.15rem;
            font-weight: 800;
        }

        .frontdesk-operations-intro__text {
            margin: 0;
            color: #64748b;
        }

        .frontdesk-operations-intro__badge {
            display: inline-flex;
            gap: 0.45rem;
            align-items: center;
            padding: 0.55rem 0.8rem;
            border-radius: 999px;
            background: #fff;
            color: #166534;
            font-size: 0.82rem;
            font-weight: 800;
            box-shadow: inset 0 0 0 1px rgba(22, 101, 52, 0.12);
        }

        .frontdesk-tabs {
            gap: 0.35rem;
            padding: 0.85rem 1rem 0;
            border-bottom-color: rgba(15, 23, 42, 0.08);
        }

        .frontdesk-tabs .nav-link {
            border: 0;
            border-radius: 999px;
            color: #475569;
            font-weight: 800;
        }

        .frontdesk-tabs .nav-link:hover {
            background: #f1f5f9;
            color: #0f172a;
        }

        .frontdesk-tabs .nav-link.active {
            background: #0f172a;
            color: #fff;
            box-shadow: 0 12px 24px rgba(15, 23, 42, 0.16);
        }

        .frontdesk-action-btn {
            display: inline-flex;
            gap: 0.35rem;
            align-items: center;
            border-radius: 999px;
            font-weight: 700;
        }

        .frontdesk-room-name {
            display: inline-flex;
            gap: 0.45rem;
            align-items: center;
            font-weight: 800;
        }

        @keyframes frontdeskSpin {
            to {
                transform: rotate(360deg);
            }
        }

        @media (max-width: 1399.98px) {
            .frontdesk-stats {
                grid-template-columns: repeat(3, minmax(0, 1fr));
            }
        }

        @media (max-width: 767.98px) {
            .frontdesk-heading {
                padding: 1rem;
            }

            .frontdesk-heading__actions,
            .frontdesk-heading__actions .btn {
                width: 100%;
            }

            .frontdesk-heading__actions .btn {
                justify-content: center;
            }

            .frontdesk-stats {
                grid-template-columns: 1fr;
            }

            .frontdesk-stat {
                min-height: auto;
            }

            .frontdesk-tabs {
                flex-wrap: nowrap;
                overflow-x: auto;
                padding-bottom: 0.65rem;
            }

            .frontdesk-tabs .nav-link {
                white-space: nowrap;
            }
        }
    </style>
@endpush

@push('js')
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const $ = window.jQuery;
            const swal = window.Swal;
            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content ?? '';
            const operationalStatuses = @json($operationalStatusesJson);
            const summaryUrl = '{{ route('adminlte.front-desk.summary') }}';
            const refreshButton = document.getElementById('frontdesk-refresh-button');

            if (typeof $ !== 'function') {
                console.error('jQuery no esta disponible para DataTables.');
                return;
            }

            loadSummary();

            window.arrivalsTable = $('#arrivals-table').DataTable({
                processing: true,
                serverSide: true,
                ajax: '{{ route('adminlte.front-desk.arrivals') }}',
                language: {
                    url: 'https://cdn.datatables.net/plug-ins/1.13.8/i18n/es-ES.json',
                },
                order: [[3, 'asc']],
                columns: buildReservationColumns(true, false, false),
            });

            window.departuresTable = $('#departures-table').DataTable({
                processing: true,
                serverSide: true,
                ajax: '{{ route('adminlte.front-desk.departures') }}',
                language: {
                    url: 'https://cdn.datatables.net/plug-ins/1.13.8/i18n/es-ES.json',
                },
                order: [[3, 'asc']],
                columns: buildReservationColumns(false, true, false),
            });

            window.occupiedTable = $('#occupied-table').DataTable({
                processing: true,
                serverSide: true,
                ajax: '{{ route('adminlte.front-desk.occupied') }}',
                language: {
                    url: 'https://cdn.datatables.net/plug-ins/1.13.8/i18n/es-ES.json',
                },
                order: [[3, 'asc']],
                columns: buildReservationColumns(false, true, true),
            });

            window.roomsStatusTable = $('#rooms-status-table').DataTable({
                processing: true,
                serverSide: true,
                ajax: '{{ route('adminlte.front-desk.rooms-status') }}',
                language: {
                    url: 'https://cdn.datatables.net/plug-ins/1.13.8/i18n/es-ES.json',
                },
                order: [[0, 'asc']],
                columns: [
                    {
                        data: 'number',
                        name: 'number',
                        render: (data, type, row) => type === 'display'
                            ? `<span class="frontdesk-room-name"><i class="bi bi-door-open text-primary" aria-hidden="true"></i> Hab. ${row.number}</span>`
                            : data
                    },
                    { data: 'room_type_name', name: 'roomType.name' },
                    { data: 'floor', name: 'floor', defaultContent: '-' },
                    { data: 'room_type_price_formatted', name: 'roomType.base_price', className: 'text-nowrap' },
                    {
                        data: 'status',
                        name: 'status',
                        className: 'text-center',
                        render: (data, type, row) => type === 'display'
                            ? `<span class="${row.status_badge_class}">${row.status_label}</span>`
                            : data
                    },
                    {
                        data: 'current_reservation_label',
                        name: 'reservations.code',
                        render: (data, type, row) => {
                            if (type !== 'display') {
                                return data;
                            }

                            if (!data || data === '-') {
                                return '<span class="text-muted">Sin reserva activa</span>';
                            }

                            return `<div class="fw-semibold">${data}</div><div class="small text-muted"><i class="bi bi-calendar-range me-1"></i>${row.current_reservation_dates}</div>`;
                        }
                    },
                    { data: 'active_label', name: 'is_active', className: 'text-center' },
                    {
                        data: null,
                        orderable: false,
                        searchable: false,
                        className: 'text-end',
                        render: (data, type, row) => {
                            if (type !== 'display') {
                                return '';
                            }

                            if (!row.can_change_status) {
                                return '<span class="text-muted">Sin permisos</span>';
                            }

                            return `<button type="button" class="btn btn-outline-primary btn-sm frontdesk-action-btn room-status-btn" data-room="${encodeURIComponent(JSON.stringify(row))}">
                                <i class="bi bi-sliders" aria-hidden="true"></i>
                                Cambiar
                            </button>`;
                        }
                    }
                ],
            });

            document.addEventListener('click', async (event) => {
                const manualRefreshButton = event.target.closest('#frontdesk-refresh-button');
                if (manualRefreshButton) {
                    await reloadFrontDesk();
                    return;
                }

                const checkInButton = event.target.closest('.frontdesk-checkin-btn');
                if (checkInButton) {
                    const reservation = JSON.parse(decodeURIComponent(checkInButton.dataset.reservation));
                    await performCheckIn(reservation);
                    return;
                }

                const checkOutButton = event.target.closest('.frontdesk-checkout-btn');
                if (checkOutButton) {
                    const reservation = JSON.parse(decodeURIComponent(checkOutButton.dataset.reservation));
                    await performCheckOut(reservation);
                    return;
                }

                const roomStatusButton = event.target.closest('.room-status-btn');
                if (roomStatusButton) {
                    const room = JSON.parse(decodeURIComponent(roomStatusButton.dataset.room));
                    await updateRoomStatus(room);
                }
            });

            function buildReservationColumns(includeCheckIn, includeCheckOut, compact) {
                const columns = [
                    {
                        data: 'code',
                        name: 'code',
                        render: (data, type, row) => type === 'display'
                            ? `<div class="fw-semibold">${row.code}</div><div class="small text-muted">${row.status_label}</div>`
                            : data
                    },
                    {
                        data: 'customer_name',
                        name: 'customer.full_name',
                        render: (data, type, row) => {
                            if (type !== 'display') {
                                return data;
                            }

                            const document = row.customer_document ? `<div class="small text-muted">${row.customer_document}</div>` : '';
                            return `<div>${row.customer_name}</div>${document}`;
                        }
                    },
                    {
                        data: 'room_number',
                        name: 'room.number',
                        render: (data, type, row) => type === 'display'
                            ? `<div>${row.room_number}</div><div class="small text-muted">${row.room_type_name}</div>`
                            : data
                    },
                    {
                        data: 'check_in',
                        name: 'check_in',
                        render: (data, type, row) => {
                            if (type !== 'display') {
                                return `${row.check_in} ${row.check_out}`;
                            }

                            return `<div>${row.check_in_formatted} - ${row.check_out_formatted}</div><div class="small text-muted">${row.nights} noche(s)</div>`;
                        }
                    },
                    {
                        data: 'guests_summary',
                        name: 'adults',
                        className: 'text-nowrap',
                    }
                ];

                if (!compact) {
                    columns.push({
                        data: 'total_amount',
                        name: 'total_amount',
                        render: (data, type, row) => type === 'display'
                            ? `<div class="fw-semibold">${row.total_amount_formatted}</div><div class="small text-muted">Pagado ${row.paid_amount_formatted}</div>`
                            : data
                    });
                }

                columns.push({
                    data: 'balance_amount',
                    name: 'balance_amount',
                    render: (data, type, row) => {
                        if (type !== 'display') {
                            return data;
                        }

                        const badge = Number(row.balance_amount) > 0
                            ? '<span class="badge text-bg-warning">Saldo pendiente</span>'
                            : '<span class="badge text-bg-success">Sin saldo</span>';

                        return `<div class="fw-semibold">${row.balance_amount_formatted}</div><div class="mt-1">${badge}</div>`;
                    }
                });

                columns.push({
                    data: 'status',
                    name: 'status',
                    className: 'text-center',
                    render: (data, type, row) => type === 'display'
                        ? `<span class="${row.status_badge_class}">${row.status_label}</span>`
                        : data
                });

                columns.push({
                    data: null,
                    orderable: false,
                    searchable: false,
                    className: 'text-end',
                    render: (data, type, row) => {
                        if (type !== 'display') {
                            return '';
                        }

                        let actions = '<div class="btn-group btn-group-sm flex-wrap justify-content-end" role="group">';

                        if (includeCheckIn && row.can_checkin) {
                            actions += `<button type="button" class="btn btn-outline-success frontdesk-action-btn frontdesk-checkin-btn" data-reservation="${encodeURIComponent(JSON.stringify(row))}">
                                <i class="bi bi-box-arrow-in-right" aria-hidden="true"></i>
                                Entrada
                            </button>`;
                        }

                        if (includeCheckOut && row.can_checkout) {
                            actions += `<button type="button" class="btn btn-outline-primary frontdesk-action-btn frontdesk-checkout-btn" data-reservation="${encodeURIComponent(JSON.stringify(row))}">
                                <i class="bi bi-box-arrow-right" aria-hidden="true"></i>
                                Salida
                            </button>`;
                        }

                        actions += '</div>';
                        return actions;
                    }
                });

                return columns;
            }

            async function performCheckIn(reservation) {
                const result = await fireAlert({
                    icon: Number(reservation.balance_amount) > 0 ? 'warning' : 'question',
                    title: `Entrada de ${reservation.code}`,
                    text: Number(reservation.balance_amount) > 0 ? 'La reserva tiene saldo pendiente. Desea continuar?' : undefined,
                    input: 'textarea',
                    inputLabel: 'Observacion operativa (opcional)',
                    inputPlaceholder: 'Escribe una observacion si deseas registrarla...',
                    showCancelButton: true,
                    confirmButtonText: 'Realizar check-in',
                    cancelButtonText: 'Cancelar',
                }, true);

                if (!result.isConfirmed) {
                    return;
                }

                const formData = new FormData();
                if (result.value) {
                    formData.append('notes', result.value);
                }

                await submitAction(reservation.checkin_url, formData);
            }

            async function performCheckOut(reservation) {
                const result = await fireAlert({
                    icon: Number(reservation.balance_amount) > 0 ? 'warning' : 'question',
                    title: `Salida de ${reservation.code}`,
                    input: 'textarea',
                    inputLabel: 'Observacion operativa (opcional)',
                    inputPlaceholder: 'Escribe una observacion si deseas registrarla...',
                    showCancelButton: true,
                    confirmButtonText: 'Realizar check-out',
                    cancelButtonText: 'Cancelar',
                }, true);

                if (!result.isConfirmed) {
                    return;
                }

                const firstTry = new FormData();
                if (result.value) {
                    firstTry.append('notes', result.value);
                }

                const response = await fetch(reservation.checkout_url, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': csrfToken,
                        'Accept': 'application/json',
                    },
                    body: firstTry,
                });

                if (response.ok) {
                    await handleSuccess(response);
                    return;
                }

                let payload = null;
                try {
                    payload = await response.json();
                } catch (error) {
                    payload = null;
                }

                if (response.status === 422 && payload?.message?.includes('saldo pendiente')) {
                    const forceResult = await fireAlert({
                        icon: 'warning',
                        title: 'Saldo pendiente',
                        text: 'La reserva tiene saldo pendiente. Desea forzar el check-out?',
                        showCancelButton: true,
                        confirmButtonText: 'Si, forzar',
                        cancelButtonText: 'Cancelar',
                    }, true);

                    if (!forceResult.isConfirmed) {
                        return;
                    }

                    const forceData = new FormData();
                    if (result.value) {
                        forceData.append('notes', result.value);
                    }
                    forceData.append('force_checkout', '1');

                    await submitAction(reservation.checkout_url, forceData);
                    return;
                }

                await handleRequestError(response, payload);
            }

            async function updateRoomStatus(room) {
                const inputOptions = {};
                operationalStatuses.forEach((status) => {
                    inputOptions[status.value] = status.label;
                });

                const statusResult = await fireAlert({
                    title: `Estado operativo de Hab. ${room.number}`,
                    input: 'select',
                    inputOptions,
                    inputValue: ['available', 'reserved', 'occupied'].includes(room.status) ? room.status : 'available',
                    showCancelButton: true,
                    confirmButtonText: 'Continuar',
                    cancelButtonText: 'Cancelar',
                }, true);

                if (!statusResult.isConfirmed || !statusResult.value) {
                    return;
                }

                const notesResult = await fireAlert({
                    title: 'Observacion operativa',
                    input: 'textarea',
                    inputLabel: 'Observacion opcional',
                    inputPlaceholder: 'Escribe una observacion si deseas registrarla...',
                    showCancelButton: true,
                    confirmButtonText: 'Guardar cambio',
                    cancelButtonText: 'Cancelar',
                }, true);

                if (!notesResult.isConfirmed) {
                    return;
                }

                const formData = new FormData();
                formData.append('_method', 'PATCH');
                formData.append('status', statusResult.value);
                if (notesResult.value) {
                    formData.append('notes', notesResult.value);
                }

                await submitAction(room.update_status_url, formData);
            }

            async function submitAction(url, formData) {
                const response = await fetch(url, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': csrfToken,
                        'Accept': 'application/json',
                    },
                    body: formData,
                });

                if (!response.ok) {
                    await handleRequestError(response);
                    return;
                }

                await handleSuccess(response);
            }

            async function handleSuccess(response) {
                const payload = await response.json();
                await reloadFrontDesk();

                await fireAlert({
                    icon: 'success',
                    title: payload.message || 'Operacion completada correctamente.',
                    timer: 1800,
                    showConfirmButton: false,
                });
            }

            async function reloadFrontDesk() {
                refreshButton?.classList.add('is-loading');
                refreshButton?.setAttribute('disabled', 'disabled');

                window.arrivalsTable?.ajax.reload(null, false);
                window.departuresTable?.ajax.reload(null, false);
                window.occupiedTable?.ajax.reload(null, false);
                window.roomsStatusTable?.ajax.reload(null, false);
                await loadSummary();

                refreshButton?.classList.remove('is-loading');
                refreshButton?.removeAttribute('disabled');
            }

            async function loadSummary() {
                const response = await fetch(summaryUrl, {
                    headers: {
                        'Accept': 'application/json',
                    },
                });

                if (!response.ok) {
                    return;
                }

                const payload = await response.json();
                document.getElementById('summary-arrivals').textContent = payload.arrivals_today ?? 0;
                document.getElementById('summary-departures').textContent = payload.departures_today ?? 0;
                document.getElementById('summary-occupied').textContent = payload.currently_occupied ?? 0;
                document.getElementById('summary-available').textContent = payload.rooms_available ?? 0;
                document.getElementById('summary-reserved').textContent = payload.rooms_reserved ?? 0;
            }

            async function handleRequestError(response, payload = null) {
                let html = 'Ocurrio un error inesperado.';

                if (!payload) {
                    try {
                        payload = await response.json();
                    } catch (error) {
                        payload = null;
                    }
                }

                if (response.status === 422 && payload?.errors) {
                    const errors = Object.values(payload.errors).flat();
                    html = `<ul class="mb-0 text-start">${errors.map((error) => `<li>${error}</li>`).join('')}</ul>`;
                } else if (payload?.message) {
                    html = payload.message;
                }

                await fireAlert({
                    icon: 'error',
                    title: 'No se pudo completar la accion',
                    html,
                });
            }

            async function fireAlert(options, confirmFallback = false) {
                if (swal) {
                    return swal.fire(options);
                }

                if (confirmFallback) {
                    return { isConfirmed: window.confirm(options.text || options.title || '') };
                }

                window.alert(options.text || options.title || '');
                return { isConfirmed: true, value: '' };
            }
        });
    </script>
@endpush
