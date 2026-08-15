@extends('adminlte::page')

@section('title', 'Libro de recepcion')

@php
    $ledgerRows = $dailyLedger['rows'];
    $summary = $dailyLedger['summary'];
    $ledgerDate = $dailyLedger['date'];
    $stayAlerts = $dailyLedger['stay_alerts'] ?? [];
    $cancellationRequests = collect($stayAlerts)->where('type', 'cancellation')->values();
    $supportedCurrencies = $supportedCurrencies ?? ['BOB' => 'Bolivianos', 'USD' => 'Dolares'];
    $currencySymbols = $currencySymbols ?? ['BOB' => 'Bs.', 'USD' => '$us'];
    $formatMoney = fn ($amount, $currency = 'BOB') => ($currency === 'USD' ? '$us ' : 'Bs. ').number_format((float) $amount, 2, '.', '');
@endphp

@section('content_header')
    <section class="ledger-hero">
        <div>
            <span class="ledger-hero__eyebrow"><i class="bi bi-journal-text" aria-hidden="true"></i> Libro diario</span>
            <h1>Recepcion de hoy</h1>
            <p>Una sola pantalla para ver habitaciones, reservas, estados, pagos y comprobantes sin salir del cuaderno.</p>
        </div>
        <div class="ledger-hero__actions">
            @can('caja.ver')
                <a href="{{ route('adminlte.cash-registers.index') }}" class="btn btn-outline-light">
                    <i class="bi bi-cash-stack me-1" aria-hidden="true"></i> Caja del turno
                </a>
            @endcan
        </div>
    </section>
@stop

@section('content')
    <section class="ledger-summary" aria-label="Resumen del dia">
        <article><i class="bi bi-calendar3" aria-hidden="true"></i><span>Fecha</span><strong>{{ $ledgerDate->format('d/m/Y') }}</strong><small>{{ ucfirst($ledgerDate->translatedFormat('l')) }}</small></article>
        <article class="ledger-summary--green"><i class="bi bi-check2-circle" aria-hidden="true"></i><span>Disponibles</span><strong>{{ $summary['available'] }}</strong><small>listas para vender</small></article>
        <article class="ledger-summary--amber"><i class="bi bi-calendar-check" aria-hidden="true"></i><span>Reservadas</span><strong>{{ $summary['reserved'] }}</strong><small>con solicitud o reserva</small></article>
        <article class="ledger-summary--red"><i class="bi bi-person-fill-lock" aria-hidden="true"></i><span>Ocupadas</span><strong>{{ $summary['occupied'] }}</strong><small>con huesped alojado</small></article>
        <article class="ledger-summary--cyan"><i class="bi bi-stars" aria-hidden="true"></i><span>Limpieza</span><strong>{{ $summary['cleaning'] ?? 0 }}</strong><small>no vender todavia</small></article>
        <article class="ledger-summary--dark"><i class="bi bi-tools" aria-hidden="true"></i><span>Reparacion</span><strong>{{ $summary['maintenance'] ?? 0 }}</strong><small>fuera de venta</small></article>
        <article class="ledger-summary--blue"><i class="bi bi-bell" aria-hidden="true"></i><span>Para atender</span><strong>{{ $summary['arrivals'] + $summary['departures'] + $summary['pending'] + $cancellationRequests->count() }}</strong><small>entradas, salidas, pendientes o anulaciones</small></article>
    </section>

    @if ($cancellationRequests->isNotEmpty())
        <section class="ledger-cancellation-requests" aria-label="Solicitudes de anulacion de reservas">
            <div class="ledger-cancellation-requests__head">
                <div>
                    <span><i class="bi bi-calendar-x-fill" aria-hidden="true"></i> Solicitudes de anulacion</span>
                    <h2>Clientes que pidieron anular su reserva</h2>
                    <p>Revisa politica de 5 dias habiles, pagos realizados y si corresponde devolucion o retencion.</p>
                </div>
                <strong>{{ $cancellationRequests->count() }} pendiente(s) de revisar</strong>
            </div>
            <div class="ledger-cancellation-requests__list">
                @foreach ($cancellationRequests as $request)
                    @php
                        $customerPhone = preg_replace('/\D+/', '', (string) ($request['customer_phone'] ?? ''));
                        if ($customerPhone !== '' && strlen($customerPhone) === 8) {
                            $customerPhone = '591'.$customerPhone;
                        }
                        $whatsappUrl = $customerPhone !== ''
                            ? 'https://wa.me/'.$customerPhone.'?text='.rawurlencode('Hola '.$request['customer_name'].', le escribimos de recepcion sobre la anulacion de su reserva '.$request['reservation_code'].'.')
                            : null;
                    @endphp
                    <article class="ledger-cancellation-card">
                        <div>
                            <span>Hab. {{ $request['room_number'] }} - {{ $request['room_type'] }}</span>
                            <strong>{{ $request['customer_name'] }}</strong>
                            <small>Reserva {{ $request['reservation_code'] }} | Entrada {{ $request['check_in'] }} | Salida {{ $request['check_out'] }}</small>
                        </div>
                        <p>{{ $request['message'] }}</p>
                        <div class="ledger-cancellation-card__meta">
                            <span><i class="bi bi-clock-history" aria-hidden="true"></i> Anulada {{ $request['cancelled_at'] ?? '-' }}</span>
                            @if ($request['customer_phone'])
                                <span><i class="bi bi-telephone" aria-hidden="true"></i> {{ $request['customer_phone'] }}</span>
                            @endif
                            @if (($request['balance'] ?? 0) > 0)
                                <span><i class="bi bi-cash-coin" aria-hidden="true"></i> Saldo {{ $formatMoney($request['balance']) }}</span>
                            @endif
                        </div>
                        <div class="ledger-cancellation-card__actions">
                            @if ($whatsappUrl)
                                <a href="{{ $whatsappUrl }}" target="_blank" rel="noopener" class="btn btn-success btn-sm">
                                    <i class="bi bi-whatsapp me-1" aria-hidden="true"></i> WhatsApp
                                </a>
                            @endif
                            <button type="button"
                                class="btn btn-outline-dark btn-sm ledger-cancellation-review-btn"
                                data-room-id="{{ $request['room_id'] ?? '' }}"
                                data-reservation-id="{{ $request['reservation_id'] ?? '' }}">
                                <i class="bi bi-eye me-1" aria-hidden="true"></i> Revisar detalle
                            </button>
                            <button type="button"
                                class="btn btn-danger btn-sm ledger-cancellation-done-btn"
                                data-review-url="{{ $request['cancellation_review_url'] ?? '' }}"
                                data-room-id="{{ $request['room_id'] ?? '' }}">
                                <i class="bi bi-check2-circle me-1" aria-hidden="true"></i> Marcar como revisada
                            </button>
                        </div>
                    </article>
                @endforeach
            </div>
        </section>
    @endif

    @if (count($stayAlerts))
        <section class="ledger-stay-alerts" aria-label="Alertas de hospedaje">
            <div class="ledger-stay-alerts__head">
                <div>
                    <span><i class="bi bi-exclamation-triangle" aria-hidden="true"></i> Cuidado recepcion</span>
                    <h2>Entradas proximas, salidas, anulaciones y hospedajes excedidos</h2>
                    <p>Revisa habitaciones que llegan en los proximos 7 dias, anulaciones de clientes, salidas cercanas y estadias vencidas.</p>
                </div>
                <strong>{{ count($stayAlerts) }} alerta(s)</strong>
            </div>
            <div class="ledger-stay-alerts__list">
                @foreach ($stayAlerts as $alert)
                    <article class="ledger-stay-alert ledger-stay-alert--{{ $alert['severity'] }}">
                        <div>
                            <span>{{ $alert['title'] }}</span>
                            <strong>Hab. {{ $alert['room_number'] }} - {{ $alert['customer_name'] }}</strong>
                            <small>
                                {{ $alert['room_type'] }} | Reserva {{ $alert['reservation_code'] }}
                                @if (($alert['type'] ?? '') === 'arrival')
                                    | Entrada {{ $alert['check_in'] }} | Salida {{ $alert['check_out'] }}
                                @elseif (($alert['type'] ?? '') === 'cancellation')
                                    | Entrada {{ $alert['check_in'] }} | Anulada {{ $alert['cancelled_at'] ?? '-' }}
                                @else
                                    | Salida planificada {{ $alert['check_out'] }}
                                @endif
                            </small>
                        </div>
                        <p>{{ $alert['message'] }}</p>
                        <div class="ledger-stay-alert__meta">
                            @if ($alert['customer_phone'])
                                <span><i class="bi bi-telephone" aria-hidden="true"></i> {{ $alert['customer_phone'] }}</span>
                            @endif
                            @if ($alert['balance'] > 0)
                                <span><i class="bi bi-cash-coin" aria-hidden="true"></i> Saldo {{ $formatMoney($alert['balance']) }}</span>
                            @else
                                <span><i class="bi bi-check-circle" aria-hidden="true"></i> Sin saldo pendiente</span>
                            @endif
                            @if (($alert['type'] ?? '') === 'arrival')
                                <span><i class="bi bi-calendar-event" aria-hidden="true"></i> {{ ($alert['days_until'] ?? 0) === 0 ? 'Llega hoy' : 'Llega en '.($alert['days_until'] ?? 0).' dia(s)' }}</span>
                            @elseif (($alert['type'] ?? '') === 'cancellation')
                                <span><i class="bi bi-calendar-x" aria-hidden="true"></i> Cliente anulo desde su panel</span>
                            @endif
                        </div>
                    </article>
                @endforeach
            </div>
        </section>
    @endif

    <section class="ledger-search-panel">
        <div>
            <label for="ledger-search-input">Buscar en recepcion</label>
            <div class="ledger-search-box">
                <i class="bi bi-search" aria-hidden="true"></i>
                <input type="search" id="ledger-search-input" placeholder="Busca por habitacion, cliente, codigo, telefono, estado o fecha...">
            </div>
            <small>Ejemplo: 12D, Rosario, RES-2026, ocupado, limpieza, pendiente.</small>
        </div>
        <button type="button" class="btn btn-outline-secondary" id="ledger-clear-search">Limpiar busqueda</button>
    </section>

    <section class="ledger-help">
        <div>
            <strong>Como usar esta pantalla</strong>
            <span>Lee por habitacion. Primero mira el color y el estado; despues usa los botones de la misma fila para registrar, ampliar o revisar reservas.</span>
        </div>
        <div>
            <strong>Colores</strong>
            <span><b class="dot dot--green"></b> Disponible <b class="dot dot--amber"></b> Reservada <b class="dot dot--red"></b> Ocupada <b class="dot dot--cyan"></b> Limpieza <b class="dot dot--dark"></b> Reparacion</span>
        </div>
    </section>

    <section class="ledger-paper">
        <div class="ledger-paper__top">
            <div>
                <span>Control de habitaciones</span>
                <strong>{{ ucfirst($ledgerDate->translatedFormat('l d/m/Y')) }}</strong>
            </div>
            <p>Usa esta tabla como el libro fisico: habitacion, huesped, estadia, estado y acciones principales.</p>
        </div>

        <div class="table-responsive">
            <table class="table ledger-table align-middle">
                <thead>
                    <tr>
                        <th>Habitacion</th>
                        <th>Huesped / solicitante</th>
                        <th>Personas</th>
                        <th>Estadia</th>
                        <th>Confirmacion</th>
                        <th>Estado y observaciones</th>
                        <th class="text-end">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($ledgerRows as $row)
                        @php
                            $roomReservationsPreview = collect($row['room_reservations'])
                                ->filter(fn ($reservation) => in_array($reservation['status'] ?? '', ['pending', 'confirmed', 'checked_in'], true))
                                ->sortBy('check_in_iso')
                                ->take(3)
                                ->values();
                            $onlineRequestsCount = collect($row['room_reservations'])
                                ->filter(fn ($reservation) => ($reservation['is_online_request'] ?? false) === true)
                                ->count();
                        @endphp
                        @php($roomData = [
                            'room_id' => $row['room']->id,
                            'room_number' => $row['room_number'],
                            'room_type' => $row['room_type'],
                            'room_status' => $row['room_status'],
                            'room_status_label' => $row['room_status_label'],
                            'ledger_status_label' => $row['ledger_status_label'],
                            'status_update_url' => route('adminlte.front-desk.rooms.status', $row['room']),
                            'can_change_room_status' => $row['can_change_room_status'],
                            'status_options' => $row['status_options'],
                            'reservations' => $row['room_reservations'],
                            'payments' => $row['room_payments'],
                        ])
                        <tr class="{{ $row['ledger_status_class'] }}"
                            data-ledger-row
                            data-room-id="{{ $row['room']->id }}"
                            data-search="{{ \Illuminate\Support\Str::lower(collect([
                                $row['room_number'],
                                $row['room_type'],
                                $row['customer_name'],
                                $row['customer_phone'],
                                $row['customer_city'],
                                $row['customer_country'],
                                $row['reservation']?->code,
                                $row['ledger_status_label'],
                                $row['room_status_label'],
                                $row['reservation_status_label'],
                                $row['date_range'],
                                $row['notes'],
                            ])->filter()->implode(' ')) }}">
                            <td data-label="Habitacion">
                                <div class="ledger-room-card">
                                    <strong class="ledger-room">{{ $row['room_number'] }}</strong>
                                    <small>{{ $row['room_type'] }}</small>
                                    <span class="ledger-room-state">{{ $row['room_status_label'] }}</span>
                                </div>
                            </td>
                            <td data-label="Huesped">
                                @if ($row['customer_name'])
                                    <div class="ledger-guest-cell">
                                        <strong>{{ $row['customer_name'] }}</strong>
                                        <small>{{ collect([$row['customer_phone'], $row['customer_city'], $row['customer_country']])->filter()->implode(' - ') }}</small>
                                        <span class="ledger-code">{{ $row['reservation']?->code }}</span>
                                    </div>
                                @else
                                    <div class="ledger-empty-guest">
                                        <i class="bi bi-door-open" aria-hidden="true"></i>
                                        <span>Sin huesped asignado</span>
                                    </div>
                                @endif
                            </td>
                            <td class="text-center" data-label="Personas"><strong class="ledger-number-pill">{{ $row['people'] ?? '-' }}</strong></td>
                            <td class="text-center" data-label="Estadia">
                                <strong class="ledger-number-pill">{{ $row['nights'] ?? '-' }}</strong>
                                @if ($row['date_range'])
                                    <small>{{ $row['date_range'] }}</small>
                                @endif
                            </td>
                            <td class="text-center" data-label="Confirmacion">
                                <span class="ledger-confirm {{ $row['confirmed_label'] === 'SI' ? 'is-confirmed' : 'is-pending' }}">{{ $row['confirmed_label'] ?: '-' }}</span>
                                <small>{{ $row['reservation_status_label'] }}</small>
                            </td>
                            <td data-label="Estado">
                                <div class="ledger-observation-cell">
                                    <span class="ledger-status-pill">{{ $row['ledger_status_label'] }}</span>
                                    <div class="ledger-mini-list">
                                @if ($row['is_arrival_today'])
                                    <span class="ledger-mini ledger-mini--in">Llega hoy</span>
                                @endif
                                @if ($row['is_departure_today'])
                                    <span class="ledger-mini ledger-mini--out">Sale hoy</span>
                                @endif
                                @if ($row['balance'] !== null && $row['balance'] > 0)
                                    <span class="ledger-mini ledger-mini--money">Saldo {{ $formatMoney($row['balance']) }}</span>
                                @endif
                                    </div>
                                    <p>{{ $row['notes'] ?: 'Sin observaciones.' }}</p>
                                </div>
                            </td>
                            <td class="text-end" data-label="Acciones">
                                <script type="application/json" id="ledger-room-data-{{ $row['room']->id }}">@json($roomData)</script>
                                <div class="ledger-action-panel">
                                    @if ($roomReservationsPreview->isNotEmpty())
                                        <div class="ledger-reservation-preview">
                                            <span>Reservados</span>
                                            @foreach ($roomReservationsPreview as $reservationPreview)
                                                <button type="button"
                                                    class="ledger-reservation-preview__item ledger-info-btn"
                                                    data-modal-type="reservations"
                                                    data-room-id="{{ $row['room']->id }}">
                                                    <strong>{{ $reservationPreview['customer'] ?? 'Cliente' }}</strong>
                                                    <small>{{ $reservationPreview['check_in'] ?? '-' }} al {{ $reservationPreview['check_out'] ?? '-' }}</small>
                                                </button>
                                            @endforeach
                                        </div>
                                    @endif

                                    <div class="ledger-actions">
                                        @if ($onlineRequestsCount > 0)
                                            <button type="button" class="btn btn-danger btn-sm ledger-info-btn ledger-online-request-btn" data-modal-type="reservations" data-room-id="{{ $row['room']->id }}">
                                                🙋 Quieren reservar
                                            </button>
                                        @endif
                                        <button type="button" class="btn btn-outline-dark btn-sm ledger-info-btn" data-modal-type="status" data-room-id="{{ $row['room']->id }}">
                                            ⚙️ Estado
                                        </button>
                                        <button type="button" class="btn btn-outline-primary btn-sm ledger-info-btn" data-modal-type="reservations" data-room-id="{{ $row['room']->id }}">
                                            👥 Ver reservados
                                        </button>
                                        <button type="button" class="btn btn-outline-info btn-sm ledger-info-btn" data-modal-type="reservations" data-room-id="{{ $row['room']->id }}">
                                            📅 Calendario
                                        </button>
                                        @can('reservas.crear')
                                            <button type="button" class="btn btn-warning btn-sm ledger-new-reservation-btn"
                                                data-room-id="{{ $row['room']->id }}"
                                                data-room-number="{{ $row['room_number'] }}">
                                                ➕ Nueva
                                            </button>
                                        @endcan

                                        @if ($row['can_checkin'])
                                            <button type="button" class="btn btn-success btn-sm ledger-action"
                                                data-action-url="{{ route('adminlte.front-desk.check-in', $row['reservation']) }}"
                                                data-action-title="Registrar entrada de {{ $row['customer_name'] }}">
                                                ✅ Entrada
                                            </button>
                                        @endif
                                        @if ($row['can_checkout'])
                                            <button type="button" class="btn btn-primary btn-sm ledger-action"
                                                data-action-url="{{ route('adminlte.front-desk.check-out', $row['reservation']) }}"
                                                data-action-title="Registrar salida de {{ $row['customer_name'] }}"
                                                data-force-checkout="{{ $row['balance'] > 0 ? '1' : '0' }}">
                                                🚪 Salida
                                            </button>
                                        @endif
                                    </div>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="text-center text-secondary py-5">No hay habitaciones registradas.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>

    <div class="modal fade" id="ledgerStatusModal" tabindex="-1" aria-labelledby="ledgerStatusModalTitle" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <form class="modal-content ledger-modal" id="ledger-status-form">
                <div class="modal-header">
                    <div>
                        <span class="ledger-modal-kicker">Estado de habitacion</span>
                        <h5 class="modal-title" id="ledgerStatusModalTitle">Habitacion</h5>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" id="ledger-status-url">
                    <div class="ledger-current-status" id="ledger-current-status"></div>
                    <label class="form-label fw-bold mt-3" for="ledger-room-status-select">Cambiar a</label>
                    <select class="form-select" id="ledger-room-status-select"></select>
                    <label class="form-label fw-bold mt-3" for="ledger-room-status-notes">Observacion</label>
                    <textarea class="form-control" id="ledger-room-status-notes" rows="3" placeholder="Ejemplo: limpieza terminada, chapa en reparacion, habitacion lista..."></textarea>
                    <small class="text-secondary d-block mt-2">Si hay un huesped alojado, el sistema protegera la habitacion para evitar cambios incorrectos.</small>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary">Guardar estado</button>
                </div>
            </form>
        </div>
    </div>

    <div class="modal fade" id="ledgerReservationsModal" tabindex="-1" aria-labelledby="ledgerReservationsModalTitle" aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-scrollable">
            <div class="modal-content ledger-modal">
                <div class="modal-header">
                    <div>
                        <span class="ledger-modal-kicker">Reservas de la habitacion</span>
                        <h5 class="modal-title" id="ledgerReservationsModalTitle">Habitacion</h5>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                </div>
                <div class="modal-body" id="ledger-reservations-body"></div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="ledgerPaymentsModal" tabindex="-1" aria-labelledby="ledgerPaymentsModalTitle" aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-scrollable">
            <div class="modal-content ledger-modal">
                <div class="modal-header">
                    <div>
                        <span class="ledger-modal-kicker">Pagos y comprobantes</span>
                        <h5 class="modal-title" id="ledgerPaymentsModalTitle">Habitacion</h5>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                </div>
                <div class="modal-body" id="ledger-payments-body"></div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="ledgerNewReservationModal" tabindex="-1" aria-labelledby="ledgerNewReservationModalTitle" aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-scrollable">
            <form class="modal-content ledger-modal" id="ledger-new-reservation-form">
                <div class="modal-header">
                    <div>
                        <span class="ledger-modal-kicker">Nueva reserva desde recepcion</span>
                        <h5 class="modal-title" id="ledgerNewReservationModalTitle">Habitacion</h5>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="room_id" id="ledger-reservation-room-id">
                    <input type="hidden" name="source" value="reception">
                    <input type="hidden" name="status" value="pending">
                    <input type="hidden" name="check_in" id="ledger-reservation-check-in">
                    <input type="hidden" name="check_out" id="ledger-reservation-check-out">

                    <div class="ledger-booking-guide">
                        <div>
                            <span class="ledger-modal-kicker">Reserva guiada</span>
                            <h6>Completa de izquierda a derecha</h6>
                            <p>Primero fechas libres, luego cliente y finalmente pago. El boton de guardar se activa cuando lo necesario esta listo.</p>
                        </div>
                        <div class="ledger-booking-guide__steps" aria-label="Pasos para crear reserva">
                            <span><b>1</b> Fechas</span>
                            <span><b>2</b> Cliente</span>
                            <span><b>3</b> Pago</span>
                            <span><b>4</b> Guardar</span>
                        </div>
                    </div>

                    <div class="ledger-booking-steps">
                        <article class="ledger-booking-panel">
                            <div class="ledger-booking-panel__title">
                                <span>1</span>
                                <div>
                                    <h6>Fechas de hospedaje</h6>
                                    <p>Click en entrada y luego click en salida. Los dias rojos estan ocupados.</p>
                                </div>
                            </div>
                            <div class="ledger-booking-toolbar">
                                <button type="button" class="btn btn-outline-secondary btn-sm" data-booking-calendar-nav="-1"><i class="bi bi-chevron-left"></i> Mes anterior</button>
                                <div class="ledger-booking-jump">
                                    <select class="form-select form-select-sm" id="ledger-booking-month" aria-label="Seleccionar mes"></select>
                                    <input class="form-control form-control-sm" type="number" id="ledger-booking-year" min="1900" max="2200" step="1" aria-label="Seleccionar gestion">
                                    <button type="button" class="btn btn-outline-success btn-sm" id="ledger-booking-today"><i class="bi bi-calendar-event"></i> Hoy</button>
                                </div>
                                <strong id="ledger-booking-calendar-title">Calendario</strong>
                                <button type="button" class="btn btn-outline-primary btn-sm" data-booking-calendar-nav="1">Mes siguiente <i class="bi bi-chevron-right"></i></button>
                            </div>
                            <div class="ledger-booking-calendar" id="ledger-booking-calendar"></div>
                            <div class="ledger-booking-date-summary" id="ledger-booking-date-summary">Primero selecciona entrada y salida.</div>
                        </article>

                        <article class="ledger-booking-panel">
                            <div class="ledger-booking-panel__title">
                                <span>2</span>
                                <div>
                                    <h6>Cliente y personas</h6>
                                    <p>Busca al cliente. Si no existe, registralo rapido aqui mismo.</p>
                                </div>
                            </div>
                            <div class="ledger-booking-block">
                                <strong>Buscar cliente</strong>
                                <small>Busca por nombre, documento, telefono o correo.</small>
                                <label class="form-label fw-bold mt-2" for="ledger-reservation-customer-id">Cliente / huesped</label>
                                <select class="form-select" id="ledger-reservation-customer-id" name="customer_id" required></select>
                                <div class="form-text fw-bold text-secondary">Escribe nombre, CI, telefono, WhatsApp o correo para buscar al cliente.</div>
                                <button type="button" class="btn btn-outline-primary btn-sm rounded-pill mt-2" id="ledger-show-new-customer">
                                    <i class="bi bi-person-plus me-1"></i> Agregar cliente y acompanantes
                                </button>
                            </div>
                            <div class="ledger-booking-block mt-3">
                                <strong>Personas que se hospedaran</strong>
                                <small>El sistema validara que no supere la capacidad de la habitacion.</small>
                                <div class="row g-3 mt-1">
                                    <div class="col-md-6">
                                        <label class="form-label fw-bold" for="ledger-reservation-adults">Adultos</label>
                                        <input type="number" class="form-control" id="ledger-reservation-adults" name="adults" min="1" max="20" value="1" required>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label fw-bold" for="ledger-reservation-children">Ninos</label>
                                        <input type="number" class="form-control" id="ledger-reservation-children" name="children" min="0" max="20" value="0">
                                    </div>
                                </div>
                            </div>
                            <div class="ledger-booking-block mt-3">
                                <strong>Observaciones</strong>
                                <small>Opcional. Escribe lo que recepcion deba recordar.</small>
                                <label class="form-label fw-bold mt-2" for="ledger-reservation-special-requests">Detalle</label>
                                <textarea class="form-control" id="ledger-reservation-special-requests" name="special_requests" rows="2" placeholder="Ejemplo: llegada tarde, cama adicional, datos importantes..."></textarea>
                            </div>
                        </article>

                        <article class="ledger-booking-panel">
                            <div class="ledger-booking-panel__title">
                                <span>3</span>
                                <div>
                                    <h6>Pago inicial</h6>
                                    <p>Elige la moneda real que entrega el cliente. Esa moneda se mantiene para este pago.</p>
                                </div>
                            </div>
                            <div class="ledger-booking-block">
                                <strong>Como esta pagando</strong>
                                <small>Selecciona la moneda real y el metodo usado por el cliente.</small>
                                <div class="row g-3 mt-1">
                                    <div class="col-md-4">
                                        <label class="form-label fw-bold" for="ledger-reservation-payment-currency">Moneda</label>
                                        <select class="form-select" id="ledger-reservation-payment-currency" name="initial_payment_currency">
                                            @foreach ($supportedCurrencies as $currencyCode => $currencyName)
                                                <option value="{{ $currencyCode }}">{{ $currencyCode }} - {{ $currencyName }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label fw-bold" for="ledger-reservation-payment-method">Metodo</label>
                                        <select class="form-select" id="ledger-reservation-payment-method" name="initial_payment_method">
                                            <option value="cash">Efectivo</option>
                                            <option value="qr">QR</option>
                                            <option value="bank">Deposito / Transferencia</option>
                                            <option value="card">Tarjeta</option>
                                            <option value="other">Otro</option>
                                        </select>
                                        <input type="hidden" name="preferred_payment_method" id="ledger-reservation-preferred-payment" value="cash">
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label fw-bold" for="ledger-reservation-payment-amount">Monto ahora</label>
                                        <input type="number" class="form-control" id="ledger-reservation-payment-amount" name="initial_payment_amount" min="0" step="0.01" value="0">
                                    </div>
                                </div>
                            </div>
                            <div class="ledger-booking-block mt-3">
                                <strong>Comprobante o referencia</strong>
                                <small>Opcional, pero recomendado si ya entrego dinero.</small>
                                <div class="row g-3 mt-1">
                                    <div class="col-md-6">
                                        <label class="form-label fw-bold" for="ledger-reservation-payment-reference">Referencia</label>
                                        <input type="text" class="form-control" id="ledger-reservation-payment-reference" name="initial_payment_reference" placeholder="Nro. recibo, transaccion o voucher">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label fw-bold" for="ledger-reservation-payment-notes">Nota del pago</label>
                                        <input type="text" class="form-control" id="ledger-reservation-payment-notes" name="initial_payment_notes" placeholder="Ejemplo: anticipo recibido en recepcion">
                                    </div>
                                </div>
                            </div>
                        </article>

                        <article class="ledger-booking-panel ledger-booking-summary" id="ledger-booking-summary">
                            <div class="ledger-booking-ready">
                                <span>4</span>
                                <div>
                                    <h6>Resumen de la reserva</h6>
                                    <p>Selecciona fechas para ver total, anticipo y saldo.</p>
                                </div>
                            </div>
                        </article>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-warning fw-bold" id="ledger-save-reservation-button" disabled>Guardar reserva</button>
                </div>
            </form>
        </div>
    </div>

    <div class="modal fade" id="ledgerActionModal" tabindex="-1" aria-labelledby="ledgerActionModalTitle" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <form class="modal-content ledger-modal" id="ledger-action-form">
                <div class="modal-header">
                    <div>
                        <span class="ledger-modal-kicker" id="ledgerActionModalKicker">Confirmar accion</span>
                        <h5 class="modal-title" id="ledgerActionModalTitle">Confirmar accion</h5>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" id="ledger-action-url">
                    <input type="hidden" id="ledger-action-field" value="notes">
                    <input type="hidden" id="ledger-action-force-checkout" value="0">
                    <p class="ledger-action-message" id="ledger-action-message">Revisa la accion antes de continuar.</p>
                    <div id="ledger-action-notes-wrap">
                        <label class="form-label fw-bold" for="ledger-action-notes">Observacion</label>
                        <textarea class="form-control" id="ledger-action-notes" rows="3" placeholder="Ejemplo: ingreso confirmado, pago verificado, motivo de rechazo..."></textarea>
                        <small class="text-secondary d-block mt-2" id="ledger-action-help">Puedes dejar este campo vacio si no corresponde.</small>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary" id="ledger-action-submit">Confirmar</button>
                </div>
            </form>
        </div>
    </div>

    <div class="modal fade" id="ledgerQuickCustomerModal" tabindex="-1" aria-labelledby="ledgerQuickCustomerModalTitle" aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-scrollable">
            <form class="modal-content ledger-modal" id="ledger-quick-customer-form">
                <div class="modal-header">
                    <div>
                        <span class="ledger-modal-kicker">Cliente nuevo</span>
                        <h5 class="modal-title" id="ledgerQuickCustomerModalTitle">Registrar cliente y acompanantes</h5>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                </div>
                <div class="modal-body ledger-customer-modal-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Nombre completo</label>
                            <input type="text" class="form-control" id="ledger-new-customer-name" required>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-bold">Documento</label>
                            <select class="form-select" id="ledger-new-customer-document-type">
                                <option value="">Opcional</option>
                                <option value="ci">CI</option>
                                <option value="passport">Pasaporte</option>
                                <option value="nit">NIT</option>
                                <option value="other">Otro</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-bold">Numero documento</label>
                            <input type="text" class="form-control" id="ledger-new-customer-document-number">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-bold">Telefono</label>
                            <input type="text" class="form-control" id="ledger-new-customer-phone">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-bold">WhatsApp</label>
                            <input type="text" class="form-control" id="ledger-new-customer-whatsapp">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Correo</label>
                            <input type="email" class="form-control" id="ledger-new-customer-email">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-bold">Nacionalidad</label>
                            <select class="form-select ledger-country-select" id="ledger-new-customer-nationality" data-country-select></select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-bold">Pais</label>
                            <select class="form-select ledger-country-select" id="ledger-new-customer-country" data-country-select></select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-bold">Ciudad</label>
                            <input type="text" class="form-control" id="ledger-new-customer-city">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-bold">Fecha nacimiento</label>
                            <input type="date" class="form-control" id="ledger-new-customer-birth-date">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Direccion</label>
                            <input type="text" class="form-control" id="ledger-new-customer-address">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-bold">Empresa</label>
                            <input type="text" class="form-control" id="ledger-new-customer-company-name">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-bold">NIT empresa</label>
                            <input type="text" class="form-control" id="ledger-new-customer-tax-number">
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-bold">Notas</label>
                            <textarea class="form-control" id="ledger-new-customer-notes" rows="3"></textarea>
                        </div>
                        <div class="col-12 d-flex flex-wrap gap-3">
                            <label class="form-check"><input class="form-check-input" type="checkbox" id="ledger-new-customer-is-foreign" value="1"> Extranjero</label>
                            <label class="form-check"><input class="form-check-input" type="checkbox" id="ledger-new-customer-is-company" value="1"> Empresa</label>
                        </div>
                    </div>

                    <div class="ledger-guests-panel mt-3">
                        <div class="ledger-guests-panel__header">
                            <div>
                                <span class="ledger-modal-kicker">Acompanantes de esta reserva</span>
                                <strong>Personas adicionales de esta reserva</strong>
                                <small>Se guardaran cuando se cree la reserva. Es opcional, pero ayuda para reportes y control.</small>
                            </div>
                            <button type="button" class="btn btn-outline-primary rounded-pill fw-bold" id="ledger-add-quick-guest-button">
                                <i class="bi bi-person-plus me-1" aria-hidden="true"></i> Anadir acompanante
                            </button>
                        </div>
                        <div class="ledger-guests-list" id="ledger-quick-guests-list"></div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary fw-bold">Guardar cliente y seleccionar</button>
                </div>
            </form>
        </div>
    </div>

    <div class="modal fade" id="ledgerCustomerOptionsModal" tabindex="-1" aria-labelledby="ledgerCustomerOptionsModalTitle" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content ledger-modal">
                <div class="modal-header">
                    <div>
                        <span class="ledger-modal-kicker">Cliente / huesped</span>
                        <h5 class="modal-title" id="ledgerCustomerOptionsModalTitle">Opciones del cliente</h5>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                </div>
                <div class="modal-body">
                    <p class="ledger-action-message" id="ledger-customer-options-help">Selecciona que deseas revisar o modificar.</p>
                    <div class="ledger-customer-actions">
                        <button type="button" class="btn btn-success" id="ledger-checkin-reservation-button">
                            <i class="bi bi-box-arrow-in-right me-1" aria-hidden="true"></i> Registrar entrada
                        </button>
                        <button type="button" class="btn btn-outline-primary" id="ledger-checkout-reservation-button">
                            <i class="bi bi-box-arrow-right me-1" aria-hidden="true"></i> Registrar salida
                        </button>
                        <button type="button" class="btn btn-warning" id="ledger-extend-reservation-button">
                            <i class="bi bi-calendar-plus me-1" aria-hidden="true"></i> Ampliar hospedaje
                        </button>
                        <button type="button" class="btn btn-primary" id="ledger-edit-customer-button">
                            <i class="bi bi-pencil-square me-1" aria-hidden="true"></i> Editar datos del cliente
                        </button>
                        <button type="button" class="btn btn-outline-success" id="ledger-view-customer-payments-button">
                            <i class="bi bi-cash-coin me-1" aria-hidden="true"></i> Ver pagos de esta reserva
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="ledgerExtendStayModal" tabindex="-1" aria-labelledby="ledgerExtendStayModalTitle" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <form class="modal-content ledger-modal" id="ledger-extend-stay-form">
                <div class="modal-header">
                    <div>
                        <span class="ledger-modal-kicker">Ampliacion de hospedaje</span>
                        <h5 class="modal-title" id="ledgerExtendStayModalTitle">Nueva salida</h5>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" id="ledger-extend-stay-url">
                    <div class="ledger-current-status" id="ledger-extend-stay-summary"></div>
                    <label class="form-label fw-bold mt-3" for="ledger-extend-new-check-out">Nueva fecha de salida</label>
                    <input type="date" class="form-control" id="ledger-extend-new-check-out" name="new_check_out" required>
                    <small class="text-secondary d-block mt-2" id="ledger-extend-stay-help">El sistema validara que la habitacion no este reservada en los dias adicionales.</small>
                    <label class="form-label fw-bold mt-3" for="ledger-extend-stay-notes">Observacion</label>
                    <textarea class="form-control" id="ledger-extend-stay-notes" name="notes" rows="3" placeholder="Ejemplo: huesped solicita una noche adicional, se informo saldo pendiente, etc."></textarea>
                    <div class="ledger-booking-ok mt-3" id="ledger-extend-stay-preview">Selecciona una nueva salida para ver las noches adicionales.</div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-warning fw-bold">Guardar ampliacion</button>
                </div>
            </form>
        </div>
    </div>

    <div class="modal fade" id="ledgerEditReservationDatesModal" tabindex="-1" aria-labelledby="ledgerEditReservationDatesModalTitle" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <form class="modal-content ledger-modal" id="ledger-edit-reservation-dates-form">
                <div class="modal-header">
                    <div>
                        <span class="ledger-modal-kicker">Editar hospedaje solicitado</span>
                        <h5 class="modal-title" id="ledgerEditReservationDatesModalTitle">Cambiar fechas</h5>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" id="ledger-edit-dates-url">
                    <div class="ledger-current-status" id="ledger-edit-dates-summary"></div>
                    <div class="row g-3 mt-1">
                        <div class="col-md-6">
                            <label class="form-label fw-bold" for="ledger-edit-check-in">Entrada</label>
                            <input type="date" class="form-control" id="ledger-edit-check-in" name="check_in" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold" for="ledger-edit-check-out">Salida</label>
                            <input type="date" class="form-control" id="ledger-edit-check-out" name="check_out" required>
                        </div>
                    </div>
                    <div class="ledger-booking-ok mt-3" id="ledger-edit-dates-preview">Selecciona fechas para validar choques.</div>
                    <label class="form-label fw-bold mt-3" for="ledger-edit-dates-notes">Observacion</label>
                    <textarea class="form-control" id="ledger-edit-dates-notes" name="notes" rows="3" placeholder="Ejemplo: cliente pidio cambiar llegada o salida antes de aprobar."></textarea>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-warning fw-bold">Guardar fechas</button>
                </div>
            </form>
        </div>
    </div>

    <div class="modal fade" id="ledgerCustomerEditModal" tabindex="-1" aria-labelledby="ledgerCustomerEditModalTitle" aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-scrollable">
            <form class="modal-content ledger-modal" id="ledger-customer-edit-form">
                <div class="modal-header">
                    <div>
                        <span class="ledger-modal-kicker">Editar cliente</span>
                        <h5 class="modal-title" id="ledgerCustomerEditModalTitle">Datos del cliente</h5>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" id="ledger-customer-update-url">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label fw-bold" for="ledger-customer-full-name">Nombre completo</label>
                            <input type="text" class="form-control" id="ledger-customer-full-name" name="full_name" required>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-bold" for="ledger-customer-document-type">Documento</label>
                            <select class="form-select" id="ledger-customer-document-type" name="document_type">
                                <option value="">Sin documento</option>
                                <option value="ci">CI</option>
                                <option value="passport">Pasaporte</option>
                                <option value="nit">NIT</option>
                                <option value="other">Otro</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-bold" for="ledger-customer-document-number">Numero documento</label>
                            <input type="text" class="form-control" id="ledger-customer-document-number" name="document_number">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-bold" for="ledger-customer-phone">Telefono</label>
                            <input type="text" class="form-control" id="ledger-customer-phone" name="phone">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-bold" for="ledger-customer-whatsapp">WhatsApp</label>
                            <input type="text" class="form-control" id="ledger-customer-whatsapp" name="whatsapp">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold" for="ledger-customer-email">Correo</label>
                            <input type="email" class="form-control" id="ledger-customer-email" name="email">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-bold" for="ledger-customer-nationality">Nacionalidad</label>
                            <select class="form-select ledger-country-select" id="ledger-customer-nationality" name="nationality" data-country-select></select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-bold" for="ledger-customer-country">Pais</label>
                            <select class="form-select ledger-country-select" id="ledger-customer-country" name="country" data-country-select></select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-bold" for="ledger-customer-city">Ciudad</label>
                            <input type="text" class="form-control" id="ledger-customer-city" name="city">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-bold" for="ledger-customer-birth-date">Fecha nacimiento</label>
                            <input type="date" class="form-control" id="ledger-customer-birth-date" name="birth_date">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold" for="ledger-customer-address">Direccion</label>
                            <input type="text" class="form-control" id="ledger-customer-address" name="address">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-bold" for="ledger-customer-company-name">Empresa</label>
                            <input type="text" class="form-control" id="ledger-customer-company-name" name="company_name">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-bold" for="ledger-customer-tax-number">NIT empresa</label>
                            <input type="text" class="form-control" id="ledger-customer-tax-number" name="tax_number">
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-bold" for="ledger-customer-notes">Notas</label>
                            <textarea class="form-control" id="ledger-customer-notes" name="notes" rows="3"></textarea>
                        </div>
                        <div class="col-12 d-flex flex-wrap gap-3">
                            <label class="form-check"><input class="form-check-input" type="checkbox" id="ledger-customer-is-foreign" name="is_foreign" value="1"> Extranjero</label>
                            <label class="form-check"><input class="form-check-input" type="checkbox" id="ledger-customer-is-company" name="is_company" value="1"> Empresa</label>
                            <label class="form-check"><input class="form-check-input" type="checkbox" id="ledger-customer-is-active" name="is_active" value="1"> Activo</label>
                        </div>
                        <div class="col-12">
                            <div class="ledger-guests-panel">
                                <div class="ledger-guests-panel__header">
                                    <div>
                                        <span class="ledger-modal-kicker">Acompanantes de esta reserva</span>
                                        <strong>Personas adicionales que se hospedaran</strong>
                                        <small>Opcional, pero recomendado para reportes y control de hospedaje.</small>
                                    </div>
                                    <button type="button" class="btn btn-outline-primary rounded-pill fw-bold" id="ledger-add-guest-button">
                                        <i class="bi bi-person-plus me-1" aria-hidden="true"></i> Anadir acompanante
                                    </button>
                                </div>
                                <div class="ledger-guests-list" id="ledger-guests-list"></div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary">Guardar cliente</button>
                </div>
            </form>
        </div>
    </div>

    <div class="modal fade" id="ledgerCustomerPaymentsModal" tabindex="-1" aria-labelledby="ledgerCustomerPaymentsModalTitle" aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-scrollable">
            <div class="modal-content ledger-modal">
                <div class="modal-header">
                    <div>
                        <span class="ledger-modal-kicker">Pagos del cliente</span>
                        <h5 class="modal-title" id="ledgerCustomerPaymentsModalTitle">Historial de pagos</h5>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                </div>
                <div class="modal-body" id="ledger-customer-payments-body"></div>
            </div>
        </div>
    </div>
@stop

@push('css')
    <link rel="stylesheet" href="{{ asset('vendor/select2/select2.min.css') }}">
    <style>
        .ledger-hero{display:flex;flex-wrap:wrap;gap:1rem;align-items:end;justify-content:space-between;padding:1.2rem;border-radius:1.2rem;background:linear-gradient(135deg,#172033 0%,#26364f 62%,#4b3724 100%);color:#fff;box-shadow:0 16px 38px rgba(15,23,42,.18)}
        .ledger-hero__eyebrow{display:inline-flex;gap:.45rem;align-items:center;padding:.35rem .7rem;margin-bottom:.7rem;border:1px solid rgba(255,255,255,.22);border-radius:999px;background:rgba(255,255,255,.1);font-size:.72rem;font-weight:800;letter-spacing:.08em;text-transform:uppercase}
        .ledger-hero h1{margin:0;font-size:clamp(1.8rem,3vw,2.5rem);font-weight:900}
        .ledger-hero p{max-width:58rem;margin:.35rem 0 0;color:rgba(255,255,255,.78)}
        .ledger-hero__actions,.ledger-actions{display:flex;flex-wrap:wrap;gap:.45rem}
        .ledger-hero__actions .btn,.ledger-actions .btn{border-radius:999px;font-weight:800}
        .ledger-summary{display:grid;grid-template-columns:repeat(7,minmax(0,1fr));gap:.8rem;margin-bottom:.9rem}
        .ledger-summary article,.ledger-help,.ledger-paper,.ledger-search-panel,.ledger-stay-alerts{border:1px solid rgba(15,23,42,.09);background:#fff;box-shadow:0 12px 30px rgba(15,23,42,.07)}
        .ledger-summary article{position:relative;overflow:hidden;padding:.9rem;border-radius:1rem;border-left:.35rem solid #475569}
        .ledger-summary article>i{position:absolute;right:.8rem;top:.75rem;color:#94a3b8;font-size:1.35rem;opacity:.72}
        .ledger-summary span,.ledger-summary small,.ledger-table small{display:block;color:#64748b;font-size:.78rem;font-weight:700}
        .ledger-summary strong{display:block;color:#111827;font-size:1.85rem;line-height:1}
        .ledger-summary--green{border-left-color:#16a34a!important}.ledger-summary--amber{border-left-color:#f59e0b!important}.ledger-summary--red{border-left-color:#dc2626!important}.ledger-summary--blue{border-left-color:#2563eb!important}.ledger-summary--cyan{border-left-color:#0891b2!important}.ledger-summary--dark{border-left-color:#334155!important}
        .ledger-stay-alerts{margin-bottom:.9rem;padding:1rem;border-radius:1.15rem;background:linear-gradient(135deg,#fff7ed,#ffffff 52%,#fef2f2)}
        .ledger-stay-alerts__head{display:flex;flex-wrap:wrap;gap:.8rem;align-items:flex-start;justify-content:space-between;margin-bottom:.85rem}
        .ledger-stay-alerts__head span{display:inline-flex;gap:.45rem;align-items:center;color:#b45309;font-size:.72rem;font-weight:950;letter-spacing:.08em;text-transform:uppercase}
        .ledger-stay-alerts__head h2{margin:.2rem 0;color:#111827;font-size:1.35rem;font-weight:950}
        .ledger-stay-alerts__head p{margin:0;color:#64748b;font-weight:750}
        .ledger-stay-alerts__head>strong{padding:.45rem .75rem;border-radius:999px;background:#111827;color:#fff;font-size:.82rem}
        .ledger-cancellation-requests{margin-bottom:.9rem;padding:1rem;border-radius:1.15rem;border:1px solid rgba(185,28,28,.16);background:linear-gradient(135deg,#fff1f2,#fff 58%,#fff7ed);box-shadow:0 12px 30px rgba(15,23,42,.07)}
        .ledger-cancellation-requests__head{display:flex;flex-wrap:wrap;gap:.8rem;align-items:flex-start;justify-content:space-between;margin-bottom:.85rem}
        .ledger-cancellation-requests__head span{display:inline-flex;gap:.45rem;align-items:center;color:#b91c1c;font-size:.72rem;font-weight:950;letter-spacing:.08em;text-transform:uppercase}
        .ledger-cancellation-requests__head h2{margin:.2rem 0;color:#111827;font-size:1.35rem;font-weight:950}
        .ledger-cancellation-requests__head p{margin:0;color:#64748b;font-weight:750}
        .ledger-cancellation-requests__head>strong{padding:.45rem .75rem;border-radius:999px;background:#b91c1c;color:#fff;font-size:.82rem}
        .ledger-cancellation-requests__list{display:grid;grid-template-columns:repeat(auto-fit,minmax(18rem,1fr));gap:.75rem}
        .ledger-cancellation-card{display:grid;gap:.65rem;padding:.95rem;border-radius:1rem;border:1px solid rgba(185,28,28,.12);border-left:.45rem solid #b91c1c;background:#fff;box-shadow:0 .9rem 1.8rem rgba(15,23,42,.08)}
        .ledger-cancellation-card span,.ledger-cancellation-card strong,.ledger-cancellation-card small{display:block}.ledger-cancellation-card>div:first-child span{color:#b91c1c;font-size:.72rem;font-weight:950;text-transform:uppercase}.ledger-cancellation-card strong{color:#111827;font-size:1.05rem}.ledger-cancellation-card small{color:#64748b;font-weight:750}.ledger-cancellation-card p{margin:0;color:#334155;font-weight:850}.ledger-cancellation-card__meta,.ledger-cancellation-card__actions{display:flex;flex-wrap:wrap;gap:.45rem}.ledger-cancellation-card__meta span{display:inline-flex;gap:.35rem;align-items:center;padding:.32rem .55rem;border-radius:999px;background:#f8fafc;color:#334155;font-size:.74rem;font-weight:850}.ledger-cancellation-card__actions .btn{border-radius:999px;font-weight:850}
        .ledger-stay-alerts__list{display:grid;grid-template-columns:repeat(auto-fit,minmax(17rem,1fr));gap:.75rem}
        .ledger-stay-alert{padding:.9rem;border-radius:1rem;border:1px solid rgba(15,23,42,.08);border-left:.45rem solid #f59e0b;background:#fff;box-shadow:0 .9rem 1.8rem rgba(15,23,42,.08)}
        .ledger-stay-alert>div:first-child span{display:inline-block;margin-bottom:.25rem;padding:.18rem .5rem;border-radius:999px;background:#fef3c7;color:#92400e;font-size:.68rem;font-weight:950;text-transform:uppercase}
        .ledger-stay-alert strong,.ledger-stay-alert small{display:block}.ledger-stay-alert strong{color:#111827;font-size:1rem}.ledger-stay-alert small{color:#64748b;font-weight:750}
        .ledger-stay-alert p{margin:.65rem 0;color:#334155;font-weight:850}
        .ledger-stay-alert__meta{display:flex;flex-wrap:wrap;gap:.45rem}.ledger-stay-alert__meta span{display:inline-flex;gap:.35rem;align-items:center;padding:.32rem .55rem;border-radius:999px;background:#f8fafc;color:#334155;font-size:.74rem;font-weight:850}
        .ledger-stay-alert--urgent{border-left-color:#f97316;background:linear-gradient(135deg,#fff7ed,#fff)}
        .ledger-stay-alert--danger{border-left-color:#dc2626;background:linear-gradient(135deg,#fef2f2,#fff)}
        .ledger-stay-alert--danger>div:first-child span{background:#fee2e2;color:#991b1b}
        .ledger-stay-alert--arrival{border-left-color:#2563eb;background:linear-gradient(135deg,#eff6ff,#fff)}
        .ledger-stay-alert--arrival>div:first-child span{background:#dbeafe;color:#1d4ed8}
        .ledger-stay-alert--arrival-pending{border-left-color:#d97706;background:linear-gradient(135deg,#fffbeb,#fff)}
        .ledger-stay-alert--arrival-pending>div:first-child span{background:#fef3c7;color:#92400e}
        .ledger-stay-alert--cancellation{border-left-color:#b91c1c;background:linear-gradient(135deg,#fff1f2,#fff)}
        .ledger-stay-alert--cancellation>div:first-child span{background:#fee2e2;color:#991b1b}
        .ledger-search-panel{display:flex;gap:1rem;align-items:end;justify-content:space-between;padding:1rem;margin-bottom:.9rem;border-radius:1rem}
        .ledger-search-panel>div{flex:1 1 auto}.ledger-search-panel label{display:block;margin-bottom:.4rem;color:#0f172a;font-size:1rem;font-weight:900}.ledger-search-panel small{display:block;margin-top:.4rem;color:#64748b;font-weight:700}
        .ledger-search-box{display:flex;align-items:center;gap:.65rem;padding:.7rem .85rem;border:1px solid rgba(15,23,42,.14);border-radius:999px;background:#f8fafc}.ledger-search-box i{color:#2563eb;font-size:1.1rem}.ledger-search-box input{width:100%;border:0;outline:0;background:transparent;color:#0f172a;font-weight:700}
        .ledger-help{display:grid;grid-template-columns:1.4fr 1fr;gap:1rem;padding:.9rem 1rem;margin-bottom:.9rem;border-radius:1rem;background:#fffdf6}.ledger-help strong,.ledger-help span{display:block}.ledger-help span{color:#64748b}
        .dot{display:inline-block;width:.72rem;height:.72rem;margin:0 .18rem 0 .5rem;border-radius:999px}.dot--green{background:#16a34a}.dot--amber{background:#f59e0b}.dot--red{background:#dc2626}.dot--cyan{background:#0891b2}.dot--dark{background:#334155}
        .ledger-paper{overflow:hidden;border-radius:1rem;background:linear-gradient(90deg,rgba(37,99,235,.08) 1px,transparent 1px) 0 0/5rem 5rem,linear-gradient(0deg,rgba(37,99,235,.08) 1px,transparent 1px) 0 0/5rem 2.6rem,#fffef9}
        .ledger-paper{overflow:hidden;border-radius:1.2rem}
        .ledger-paper__top{display:flex;flex-wrap:wrap;gap:1rem;align-items:center;justify-content:space-between;padding:1rem 1.15rem;color:#475569;border-bottom:1px solid rgba(15,23,42,.08);background:linear-gradient(135deg,#fff,#f8fafc)}
        .ledger-paper__top span,.ledger-paper__top strong{display:block}.ledger-paper__top span{font-size:.72rem;font-weight:950;letter-spacing:.08em;text-transform:uppercase;color:#2563eb}.ledger-paper__top strong{color:#0f172a;font-size:1.25rem}.ledger-paper__top p{max-width:42rem;margin:0;color:#64748b;font-weight:750}
        .ledger-table{margin:0;min-width:1180px;border-collapse:separate;border-spacing:0 .55rem;--bs-table-bg:transparent}
        .ledger-table thead th{border:0;color:#334155;font-size:.72rem;font-weight:950;letter-spacing:.06em;text-transform:uppercase;background:#eef2f7;padding:.8rem .9rem}
        .ledger-table thead th:first-child{border-radius:.9rem 0 0 .9rem}.ledger-table thead th:last-child{border-radius:0 .9rem .9rem 0}
        .ledger-table tbody tr{filter:drop-shadow(0 .65rem 1.2rem rgba(15,23,42,.06))}
        .ledger-table tbody td{border-top:1px solid rgba(15,23,42,.07);border-bottom:1px solid rgba(15,23,42,.07);background:#fff;padding:.85rem .9rem;vertical-align:middle}
        .ledger-table tbody td:first-child{border-left:1px solid rgba(15,23,42,.07);border-radius:1rem 0 0 1rem}.ledger-table tbody td:last-child{border-right:1px solid rgba(15,23,42,.07);border-radius:0 1rem 1rem 0}
        .ledger-room-card{display:grid;gap:.25rem;min-width:7rem}.ledger-room{display:block;color:#0f172a;font-size:1.45rem;line-height:1;font-weight:950}.ledger-room-card small{font-size:.76rem}.ledger-room-state{display:inline-flex;width:fit-content;padding:.2rem .55rem;border-radius:999px;background:#f1f5f9;color:#334155;font-size:.68rem;font-weight:950}
        .ledger-guest-cell strong{display:block;color:#0f172a;font-size:.96rem}.ledger-guest-cell small{margin-top:.15rem}.ledger-empty-guest{display:flex;gap:.5rem;align-items:center;color:#64748b;font-weight:850}.ledger-empty-guest i{display:grid;place-items:center;width:2rem;height:2rem;border-radius:999px;background:#f1f5f9;color:#475569}
        .ledger-number-pill{display:inline-grid;min-width:2.55rem;height:2.55rem;place-items:center;border-radius:.9rem;background:#f8fafc;color:#0f172a;font-size:1.05rem;font-weight:950;border:1px solid rgba(15,23,42,.08)}
        .ledger-code,.ledger-status-pill,.ledger-mini{display:inline-flex;width:fit-content;margin-top:.25rem;padding:.24rem .58rem;border-radius:999px;font-size:.72rem;font-weight:900}.ledger-code{background:#eef2ff;color:#3730a3}.ledger-status-pill{background:#f1f5f9;color:#334155}.ledger-mini-list{display:flex;flex-wrap:wrap;gap:.28rem}.ledger-mini--in{background:#dcfce7;color:#166534}.ledger-mini--out{background:#dbeafe;color:#1d4ed8}.ledger-mini--money{background:#fef3c7;color:#92400e}
        .ledger-confirm{display:inline-grid;min-width:2.65rem;min-height:2.65rem;place-items:center;border-radius:999px;background:#f1f5f9;color:#0f172a;font-weight:950;border:1px solid rgba(15,23,42,.08)}.ledger-confirm.is-confirmed{background:#dcfce7;color:#166534}.ledger-confirm.is-pending{background:#fef3c7;color:#92400e}
        .ledger-observation-cell{display:grid;gap:.35rem;min-width:15rem}.ledger-observation-cell p{margin:.2rem 0 0;color:#475569;font-size:.82rem;font-weight:700;line-height:1.35}
        .ledger-initials{display:inline-grid;min-width:2.4rem;height:2.4rem;place-items:center;border-radius:999px;background:#e0f2fe;color:#075985;font-weight:950}
        .ledger-action-panel{display:grid;gap:.55rem;justify-items:end}
        .ledger-reservation-preview{display:grid;gap:.35rem;min-width:16rem;text-align:left}
        .ledger-reservation-preview>span{color:#64748b;font-size:.68rem;font-weight:950;letter-spacing:.08em;text-transform:uppercase}
        .ledger-reservation-preview__item{display:block;width:100%;padding:.48rem .62rem;border:1px solid rgba(37,99,235,.14);border-radius:.85rem;background:#f8fbff;text-align:left;transition:.18s ease}
        .ledger-reservation-preview__item:hover{background:#eff6ff;border-color:rgba(37,99,235,.35);transform:translateY(-1px)}
        .ledger-reservation-preview__item strong,.ledger-reservation-preview__item small{display:block}.ledger-reservation-preview__item strong{color:#0f172a;font-size:.82rem}.ledger-reservation-preview__item small{color:#64748b;font-size:.7rem;font-weight:800}
        .ledger-actions{justify-content:flex-end;min-width:18rem}.ledger-actions .btn{display:inline-flex;gap:.35rem;align-items:center;justify-content:center;padding:.38rem .68rem;box-shadow:0 .45rem .9rem rgba(15,23,42,.06)}
        .ledger-online-request-btn{animation:ledgerPulse 1.8s ease-in-out infinite}
        .ledger-modal-card--online-request{border-color:rgba(220,38,38,.25)!important;background:linear-gradient(135deg,#fff5f5,#fff)!important}
        @keyframes ledgerPulse{0%,100%{box-shadow:0 .45rem .9rem rgba(220,38,38,.08)}50%{box-shadow:0 .55rem 1.3rem rgba(220,38,38,.24)}}
        .ledger-status--available td:first-child{box-shadow:inset .42rem 0 #16a34a}.ledger-status--reserved td:first-child{box-shadow:inset .42rem 0 #f59e0b}.ledger-status--occupied td:first-child{box-shadow:inset .42rem 0 #dc2626}.ledger-status--cleaning td:first-child{box-shadow:inset .42rem 0 #0891b2}.ledger-status--maintenance td:first-child{box-shadow:inset .42rem 0 #334155}
        .ledger-status--available .ledger-room-state{background:#dcfce7;color:#166534}.ledger-status--reserved .ledger-room-state{background:#fef3c7;color:#92400e}.ledger-status--occupied .ledger-room-state{background:#fee2e2;color:#991b1b}.ledger-status--cleaning .ledger-room-state{background:#cffafe;color:#155e75}.ledger-status--maintenance .ledger-room-state{background:#e2e8f0;color:#334155}
        .ledger-modal{border:0;border-radius:1.2rem;overflow:hidden}.ledger-modal-kicker{display:block;color:#2563eb;font-size:.72rem;font-weight:900;letter-spacing:.08em;text-transform:uppercase}.ledger-current-status,.ledger-action-message{padding:1rem;border-radius:1rem;background:#f8fafc;color:#0f172a;font-weight:800}.ledger-modal-list{display:grid;gap:.75rem}.ledger-modal-card{padding:1rem;border:1px solid rgba(15,23,42,.08);border-radius:1rem;background:#fff}.ledger-modal-card__top{display:flex;flex-wrap:wrap;gap:.75rem;align-items:start;justify-content:space-between}.ledger-modal-card small{display:block;color:#64748b;font-weight:700}.ledger-modal-empty{display:grid;min-height:10rem;place-items:center;border-radius:1rem;background:#f8fafc;color:#64748b;text-align:center;font-weight:800}.ledger-reservation-frame{display:block;width:100%;height:calc(100vh - 4.3rem);border:0;background:#f8fafc}
        .ledger-calendar{display:grid;gap:1rem;margin-bottom:1rem}.ledger-calendar-toolbar{display:flex;flex-wrap:wrap;gap:.75rem;align-items:center;justify-content:space-between;padding:1rem;border-radius:1rem;background:linear-gradient(135deg,#eff6ff,#f8fafc);border:1px solid rgba(37,99,235,.12)}.ledger-calendar-toolbar strong{display:block;color:#0f172a;font-size:1.05rem}.ledger-calendar-toolbar span{color:#64748b;font-weight:700}.ledger-calendar-legend{display:flex;flex-wrap:wrap;gap:.45rem}.ledger-calendar-legend span{display:inline-flex;align-items:center;gap:.35rem;padding:.35rem .6rem;border-radius:999px;background:#fff;color:#475569;font-size:.75rem;font-weight:800;border:1px solid rgba(15,23,42,.08)}.ledger-calendar-month{overflow:hidden;border:1px solid rgba(15,23,42,.08);border-radius:1.1rem;background:#fff;box-shadow:0 .8rem 2rem rgba(15,23,42,.06)}.ledger-calendar-month h6{margin:0;padding:.85rem 1rem;color:#0f172a;font-weight:900;background:#fbfaf8;border-bottom:1px solid rgba(15,23,42,.07)}.ledger-calendar-grid{display:grid;grid-template-columns:repeat(7,minmax(5.6rem,1fr));min-width:760px}.ledger-calendar-day-name,.ledger-calendar-day{min-height:4.7rem;padding:.55rem;border-right:1px solid rgba(15,23,42,.06);border-bottom:1px solid rgba(15,23,42,.06)}.ledger-calendar-day-name{min-height:auto;background:#f8fafc;color:#64748b;font-size:.72rem;font-weight:900;text-align:center;text-transform:uppercase}.ledger-calendar-day{position:relative;background:#fff}.ledger-calendar-day.is-muted{background:#f8fafc;color:#94a3b8}.ledger-calendar-day.is-today{box-shadow:inset 0 0 0 2px rgba(37,99,235,.45)}.ledger-calendar-number{display:inline-flex;width:1.65rem;height:1.65rem;align-items:center;justify-content:center;border-radius:999px;font-weight:900}.ledger-calendar-day.is-booked{background:linear-gradient(180deg,rgba(37,99,235,.08),rgba(20,184,166,.07))}.ledger-calendar-booking{display:block;max-width:100%;margin-top:.35rem;padding:.22rem .42rem;border-radius:.55rem;background:#2563eb;color:#fff;font-size:.68rem;font-weight:850;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}.ledger-calendar-booking--pending{background:#f59e0b;color:#111827}.ledger-calendar-booking--checked_in{background:#dc2626}.ledger-calendar-booking--confirmed{background:#16a34a}.ledger-calendar-booking--checked_out{background:#64748b}.ledger-calendar-booking--cancelled,.ledger-calendar-booking--expired{background:#94a3b8;color:#0f172a}
        .ledger-calendar-nav{display:flex;flex-wrap:wrap;gap:.45rem;align-items:center}.ledger-calendar-nav .btn{border-radius:999px;font-weight:850}.ledger-calendar-jump{display:flex;flex-wrap:wrap;gap:.45rem;align-items:center;padding:.45rem;border-radius:999px;background:#fff;border:1px solid rgba(15,23,42,.08)}.ledger-calendar-jump select,.ledger-calendar-jump input{width:auto;min-width:8rem;border:0;background:#f8fafc;border-radius:999px;font-weight:850}.ledger-calendar-jump input{max-width:8rem;text-align:center}.ledger-calendar-stats{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:.65rem}.ledger-calendar-stats div{padding:.75rem;border:1px solid rgba(15,23,42,.08);border-radius:.9rem;background:#fff}.ledger-calendar-stats span{display:block;color:#64748b;font-size:.7rem;font-weight:900;text-transform:uppercase;letter-spacing:.06em}.ledger-calendar-stats strong{display:block;color:#0f172a;font-size:1.15rem}.ledger-calendar-booking{border:0;text-align:left;cursor:pointer}.ledger-calendar-booking:hover,.ledger-calendar-booking:focus{filter:brightness(.96);outline:2px solid rgba(37,99,235,.35);outline-offset:1px}.ledger-reservation-detail{padding:1rem;border:1px solid rgba(37,99,235,.13);border-radius:1.15rem;background:linear-gradient(135deg,#fff,#f8fafc);box-shadow:0 .8rem 2rem rgba(15,23,42,.06);transition:.2s ease}.ledger-reservation-detail.is-focused{border-color:#2563eb;box-shadow:0 0 0 .25rem rgba(37,99,235,.18),0 .9rem 2rem rgba(37,99,235,.14)}.ledger-reservation-detail h5{margin:0;color:#0f172a;font-weight:950}.ledger-detail-grid{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:.65rem;margin-top:.8rem}.ledger-detail-grid div{padding:.7rem;border-radius:.85rem;background:#fff;border:1px solid rgba(15,23,42,.07)}.ledger-detail-grid span{display:block;color:#64748b;font-size:.7rem;font-weight:900;letter-spacing:.06em;text-transform:uppercase}.ledger-detail-grid strong{display:block;color:#0f172a}.ledger-detail-payments{display:grid;gap:.5rem;margin-top:.8rem}.ledger-detail-payment{display:flex;flex-wrap:wrap;gap:.5rem;align-items:center;justify-content:space-between;padding:.65rem .75rem;border-radius:.85rem;background:#fff;border:1px solid rgba(15,23,42,.07)}
        .ledger-request-panel{margin-bottom:1rem;padding:1rem;border:1px solid rgba(220,38,38,.16);border-radius:1.15rem;background:linear-gradient(135deg,#fff7ed,#fff 55%,#fef2f2);box-shadow:0 .9rem 2rem rgba(15,23,42,.07)}.ledger-request-panel--empty{border-color:rgba(15,23,42,.08);background:#f8fafc}.ledger-request-panel__head{display:flex;flex-wrap:wrap;gap:.75rem;align-items:flex-start;justify-content:space-between;margin-bottom:.75rem}.ledger-request-panel__head h6,.ledger-request-panel--empty h6{margin:.15rem 0;color:#0f172a;font-size:1.15rem;font-weight:950}.ledger-request-panel__head p,.ledger-request-panel--empty p{margin:0;color:#64748b;font-weight:750}.ledger-request-panel__head>strong{padding:.45rem .7rem;border-radius:999px;background:#dc2626;color:#fff;font-size:.78rem}.ledger-request-list{display:grid;grid-template-columns:repeat(auto-fit,minmax(15rem,1fr));gap:.7rem}.ledger-request-item{display:grid;gap:.45rem}.ledger-request-card{display:grid;gap:.18rem;padding:.85rem;border:1px solid rgba(220,38,38,.16);border-radius:1rem;background:#fff;text-align:left;box-shadow:0 .65rem 1.4rem rgba(15,23,42,.07);transition:.18s ease}.ledger-request-card:hover,.ledger-request-card:focus{transform:translateY(-1px);border-color:rgba(220,38,38,.38);outline:2px solid rgba(220,38,38,.18)}.ledger-request-card span{color:#dc2626;font-size:.68rem;font-weight:950;letter-spacing:.07em;text-transform:uppercase}.ledger-request-card strong{color:#0f172a;font-size:1rem}.ledger-request-card small{color:#475569;font-weight:800}.ledger-request-card em{justify-self:start;margin-top:.25rem;padding:.18rem .48rem;border-radius:999px;background:#fef3c7;color:#92400e;font-size:.7rem;font-style:normal;font-weight:950}.ledger-decision-stack{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:.7rem;margin:.85rem 0}.ledger-conflict-alert,.ledger-warning-alert,.ledger-ok-alert{padding:.8rem;border-radius:1rem;border:1px solid rgba(15,23,42,.08);font-weight:850}.ledger-conflict-alert{background:#fef2f2;color:#991b1b;border-color:rgba(220,38,38,.18)}.ledger-warning-alert{background:#fffbeb;color:#92400e;border-color:rgba(245,158,11,.2)}.ledger-ok-alert{background:#ecfdf5;color:#166534;border-color:rgba(22,163,74,.18)}.ledger-conflict-alert strong,.ledger-conflict-alert small,.ledger-warning-alert strong,.ledger-warning-alert small{display:block}.ledger-conflict-alert small,.ledger-warning-alert small{margin-top:.2rem}.ledger-decision-actions{display:flex;flex-wrap:wrap;gap:.55rem;margin-top:1rem;padding-top:.85rem;border-top:1px solid rgba(15,23,42,.08)}
        .ledger-request-card.is-active{border-color:#2563eb;outline:3px solid rgba(37,99,235,.22);background:linear-gradient(135deg,#eff6ff,#fff);box-shadow:0 1rem 2.1rem rgba(37,99,235,.16)}.ledger-calendar-day.is-selected-request{background:linear-gradient(180deg,rgba(37,99,235,.22),rgba(37,99,235,.1));box-shadow:inset 0 0 0 2px rgba(37,99,235,.55)}.ledger-calendar-day.is-selected-request .ledger-calendar-number{background:#2563eb;color:#fff}.ledger-calendar-day.is-selected-request .ledger-calendar-booking{outline:2px solid rgba(255,255,255,.85);box-shadow:0 .45rem 1rem rgba(37,99,235,.2)}
        .ledger-whatsapp-link{display:inline-flex;align-items:center;justify-content:center;gap:.25rem;padding:.55rem .75rem;border-radius:999px;background:#16a34a;color:#fff!important;font-size:.78rem;font-weight:950;text-decoration:none;box-shadow:0 .55rem 1.1rem rgba(22,163,74,.22)}.ledger-whatsapp-link:hover,.ledger-whatsapp-link:focus{background:#15803d;color:#fff!important;transform:translateY(-1px)}
        .ledger-customer-actions{display:grid;grid-template-columns:1fr 1fr;gap:.75rem}.ledger-customer-actions .btn{min-height:3rem;border-radius:1rem;font-weight:900}.ledger-customer-name-btn{padding:0;border:0;background:transparent;color:#0f172a;font-weight:950;text-align:left}.ledger-customer-name-btn:hover,.ledger-customer-name-btn:focus{color:#2563eb;text-decoration:underline}.ledger-customer-payment-list{display:grid;gap:.75rem}.ledger-customer-payment-card{padding:1rem;border:1px solid rgba(15,23,42,.08);border-radius:1rem;background:#fff;box-shadow:0 .8rem 1.8rem rgba(15,23,42,.06)}.ledger-customer-payment-card__top{display:flex;flex-wrap:wrap;gap:.75rem;align-items:start;justify-content:space-between}.ledger-customer-payment-card small{display:block;color:#64748b;font-weight:750}.ledger-payment-shell{display:grid;gap:1rem}.ledger-payment-overview{display:grid;grid-template-columns:1.15fr repeat(3,minmax(0,.7fr));gap:.75rem}.ledger-payment-overview>div{padding:1rem;border:1px solid rgba(15,23,42,.08);border-radius:1.1rem;background:#fff;box-shadow:0 .7rem 1.7rem rgba(15,23,42,.05)}.ledger-payment-overview span,.ledger-payment-form .form-label{display:block;color:#64748b;font-size:.72rem;font-weight:950;letter-spacing:.06em;text-transform:uppercase}.ledger-payment-overview strong{display:block;color:#0f172a;font-size:1.35rem;line-height:1.1}.ledger-payment-overview small{display:block;margin-top:.25rem;color:#64748b;font-weight:750}.ledger-payment-progress{height:.65rem;margin-top:.8rem;border-radius:999px;background:#e2e8f0;overflow:hidden}.ledger-payment-progress b{display:block;height:100%;border-radius:999px;background:linear-gradient(90deg,#16a34a,#2563eb)}.ledger-payment-form{padding:1rem;border:1px solid rgba(22,163,74,.18);border-radius:1.2rem;background:linear-gradient(135deg,#f0fdf4,#fff);box-shadow:0 1rem 2rem rgba(15,23,42,.06)}.ledger-payment-form__head{display:flex;flex-wrap:wrap;gap:.75rem;align-items:start;justify-content:space-between;margin-bottom:1rem}.ledger-payment-form__head strong{display:block;color:#0f172a;font-size:1.1rem}.ledger-payment-form__head small{display:block;color:#64748b;font-weight:750}.ledger-payment-methods{display:grid;grid-template-columns:repeat(5,minmax(0,1fr));gap:.5rem}.ledger-payment-methods button{min-height:3rem;border:1px solid rgba(15,23,42,.1);border-radius:.9rem;background:#fff;color:#334155;font-weight:900}.ledger-payment-methods button.is-active{border-color:#16a34a;background:#dcfce7;color:#166534}.ledger-payment-preview{padding:.8rem;border-radius:1rem;background:#0f172a;color:#fff;font-weight:900}.ledger-payment-preview span{display:block;color:#cbd5e1;font-size:.72rem;text-transform:uppercase;letter-spacing:.06em}.ledger-payment-history-title{display:flex;flex-wrap:wrap;gap:.75rem;align-items:center;justify-content:space-between}.ledger-payment-history-title h6{margin:0;color:#0f172a;font-weight:950}.ledger-payment-badge{display:inline-flex;align-items:center;gap:.35rem;padding:.35rem .65rem;border-radius:999px;background:#eef2ff;color:#3730a3;font-size:.75rem;font-weight:950}
        .ledger-booking-guide{display:grid;grid-template-columns:1fr auto;gap:1rem;align-items:center;margin-bottom:1rem;padding:1rem;border:1px solid rgba(37,99,235,.16);border-radius:1.25rem;background:linear-gradient(135deg,#eef6ff,#fffaf2);box-shadow:0 1rem 2.2rem rgba(15,23,42,.06)}.ledger-booking-guide h6{margin:0;color:#0f172a;font-size:1.2rem;font-weight:950}.ledger-booking-guide p{margin:.25rem 0 0;color:#475569;font-weight:750}.ledger-booking-guide__steps{display:flex;flex-wrap:wrap;gap:.5rem}.ledger-booking-guide__steps span{display:inline-flex;gap:.45rem;align-items:center;padding:.5rem .75rem;border-radius:999px;background:#fff;color:#334155;font-size:.78rem;font-weight:950;border:1px solid rgba(15,23,42,.08);box-shadow:0 .4rem 1rem rgba(15,23,42,.07)}.ledger-booking-guide__steps b,.ledger-booking-panel__title>span,.ledger-booking-ready>span{display:inline-grid;place-items:center;width:2.15rem;height:2.15rem;border-radius:999px;background:#2563eb;color:#fff;font-weight:950;box-shadow:0 .55rem 1.1rem rgba(37,99,235,.22)}.ledger-booking-steps{display:grid;grid-template-columns:1fr;gap:1rem}.ledger-booking-panel{position:relative;padding:1.1rem;border:1px solid rgba(15,23,42,.1);border-left:.45rem solid #2563eb;border-radius:1.2rem;background:#fff;box-shadow:0 1.1rem 2.5rem rgba(15,23,42,.08)}.ledger-booking-panel:nth-child(2){border-left-color:#0891b2}.ledger-booking-panel:nth-child(3){border-left-color:#d97706}.ledger-booking-panel:nth-child(4){border-left-color:#16a34a}.ledger-booking-panel__title,.ledger-booking-ready{display:flex;gap:.85rem;align-items:flex-start;margin-bottom:1rem;padding-bottom:.85rem;border-bottom:1px solid rgba(15,23,42,.08)}.ledger-booking-panel h6{margin:0;color:#0f172a;font-size:1.12rem;font-weight:950}.ledger-booking-panel p{margin:.2rem 0 0;color:#64748b;font-weight:750}.ledger-booking-block{padding:1rem;border:1px solid rgba(15,23,42,.09);border-radius:1rem;background:#f8fafc;box-shadow:inset 0 0 0 1px rgba(255,255,255,.7)}.ledger-booking-block strong,.ledger-booking-block small{display:block}.ledger-booking-block strong{color:#0f172a;font-size:.98rem;font-weight:950}.ledger-booking-block small{margin-top:.1rem;color:#64748b;font-weight:750}.ledger-booking-block .form-label{font-size:.76rem;color:#475569;text-transform:uppercase;letter-spacing:.05em}.ledger-booking-toolbar{display:flex;flex-wrap:wrap;gap:.5rem;align-items:center;justify-content:space-between;margin-bottom:.75rem;padding:.65rem;border-radius:1rem;background:#f8fafc;border:1px solid rgba(15,23,42,.08)}.ledger-booking-toolbar strong{font-size:1rem;color:#0f172a}.ledger-booking-jump{display:flex;flex-wrap:wrap;gap:.45rem;align-items:center;padding:.35rem;border-radius:.9rem;background:#fff;border:1px solid rgba(15,23,42,.08)}.ledger-booking-jump select{min-width:9rem}.ledger-booking-jump input{max-width:7rem;text-align:center}.ledger-booking-jump .btn{white-space:nowrap}.ledger-booking-calendar{overflow:auto;border:1px solid rgba(15,23,42,.1);border-radius:1rem;background:#fff}.ledger-booking-calendar-grid{display:grid;grid-template-columns:repeat(7,minmax(4.6rem,1fr));min-width:650px}.ledger-booking-weekday,.ledger-booking-day{padding:.55rem;border-right:1px solid rgba(15,23,42,.06);border-bottom:1px solid rgba(15,23,42,.06);text-align:left}.ledger-booking-weekday{background:#f1f5f9;color:#475569;font-size:.72rem;font-weight:950;text-align:center;text-transform:uppercase}.ledger-booking-day{min-height:4rem;border:0;background:#fff;color:#0f172a;font-weight:900}.ledger-booking-day small{display:block;margin-top:.25rem;font-size:.65rem}.ledger-booking-day strong{display:block;margin-top:.1rem;font-size:.68rem;line-height:1.1}.ledger-booking-day em{display:inline-flex;margin-top:.28rem;padding:.12rem .38rem;border-radius:999px;background:#fff;color:#991b1b;font-size:.62rem;font-style:normal;font-weight:950;box-shadow:0 .25rem .7rem rgba(153,27,27,.12)}.ledger-booking-reserved-by{color:#7f1d1d}.ledger-booking-day:hover:not(:disabled){background:#eff6ff}.ledger-booking-day.is-muted{color:#94a3b8;background:#f8fafc}.ledger-booking-day.is-blocked{color:#991b1b;background:#fee2e2}.ledger-booking-day.is-blocked:not(:disabled){cursor:pointer}.ledger-booking-day.is-blocked:not(:disabled):hover{background:#fecaca}.ledger-booking-day.is-selected{color:#fff;background:#2563eb}.ledger-booking-day.is-range{background:#dbeafe}.ledger-booking-date-summary{margin-top:.85rem;padding:.9rem;border-radius:1rem;background:#fff7ed;color:#9a3412;font-weight:950;border:1px solid rgba(251,146,60,.2)}.ledger-new-customer-box{margin-top:1rem;padding:1rem;border:1px solid rgba(37,99,235,.18);border-radius:1rem;background:#eef6ff}.ledger-new-customer-box__head{padding:.8rem;margin:-.2rem -.2rem .8rem;border-radius:.9rem;background:#fff;border:1px solid rgba(15,23,42,.07);display:flex;flex-wrap:wrap;gap:.5rem;justify-content:space-between}.ledger-new-customer-box__head strong,.ledger-new-customer-box__head small{display:block}.ledger-new-customer-box__head small{color:#64748b}.ledger-booking-summary{background:#fff;color:#0f172a}.ledger-booking-summary h6{color:#0f172a}.ledger-booking-summary p{color:#64748b}.ledger-booking-summary-grid{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:.75rem}.ledger-booking-summary-grid div{padding:.9rem;border-radius:1rem;background:#f8fafc;border:1px solid rgba(15,23,42,.09)}.ledger-booking-summary-grid span{display:block;color:#64748b;font-size:.72rem;font-weight:950;text-transform:uppercase}.ledger-booking-summary-grid strong{display:block;color:#0f172a;font-size:1.08rem}.ledger-booking-warning{padding:.9rem;border-radius:1rem;background:#fef2f2;color:#991b1b;font-weight:900;border:1px solid rgba(239,68,68,.18)}.ledger-booking-ok{padding:.9rem;margin-top:.75rem;border-radius:1rem;background:#ecfdf5;border:1px solid rgba(34,197,94,.25);color:#166534;font-weight:900}
        .ledger-country-select option{font-weight:700}.ledger-country-option{display:inline-flex;gap:.5rem;align-items:center;font-weight:800}.ledger-country-flag{display:inline-flex;width:1.7rem;min-width:1.7rem;height:1.15rem;align-items:center;justify-content:center;overflow:hidden;border-radius:.22rem;background:#f1f5f9;box-shadow:0 0 0 1px rgba(15,23,42,.08)}.ledger-country-flag img{display:block;width:100%;height:100%;object-fit:cover}.ledger-modal .select2-container{width:100%!important}.ledger-modal .select2-container--default .select2-selection--single{display:flex;align-items:center;min-height:calc(2.5rem + 2px);border:1px solid #dee2e6;border-radius:.375rem;background:#fff}.ledger-modal .select2-container--default .select2-selection--single .select2-selection__rendered{width:100%;padding-left:.75rem;color:#212529;line-height:normal}.ledger-modal .select2-container--default .select2-selection--single .select2-selection__arrow{height:100%;right:.45rem}.ledger-modal .select2-container--default.select2-container--focus .select2-selection--single{border-color:#86b7fe;box-shadow:0 0 0 .25rem rgba(13,110,253,.25)}.select2-dropdown.ledger-country-dropdown{border-color:#dee2e6;border-radius:.8rem;overflow:hidden;box-shadow:0 1rem 2.5rem rgba(15,23,42,.18)}.select2-dropdown.ledger-country-dropdown .select2-search__field{border-radius:.6rem!important;padding:.55rem .7rem!important}.ledger-guests-panel{padding:1rem;border:1px solid rgba(37,99,235,.14);border-radius:1.2rem;background:linear-gradient(135deg,#fff,#f8fafc)}.ledger-guests-panel__header{display:flex;flex-wrap:wrap;gap:1rem;align-items:center;justify-content:space-between;margin-bottom:.85rem}.ledger-guests-panel__header strong,.ledger-guests-panel__header small{display:block}.ledger-guests-panel__header small{color:#64748b}.ledger-guests-list{display:grid;gap:.75rem}.ledger-guest-card{padding:.9rem;border:1px solid rgba(15,23,42,.08);border-radius:1rem;background:#fff}.ledger-guest-card__top{display:flex;gap:.75rem;align-items:center;justify-content:space-between;margin-bottom:.75rem}.ledger-guest-card__top strong{color:#0f172a}.ledger-guest-card .form-label{font-size:.78rem;font-weight:900;color:#475569}.ledger-guest-empty{padding:1rem;border:1px dashed rgba(37,99,235,.25);border-radius:1rem;color:#64748b;background:#fff;text-align:center;font-weight:800}
        .ledger-customer-modal-body{background:#fff}.ledger-customer-modal-body .form-label{margin-bottom:.35rem;color:#212529;font-size:.86rem;font-weight:950}.ledger-customer-modal-body .form-control,.ledger-customer-modal-body .form-select{min-height:2.35rem;border-color:#d9e1ec;border-radius:.45rem;box-shadow:inset 0 1px 2px rgba(15,23,42,.04)}.ledger-customer-modal-body textarea.form-control{min-height:4.8rem}.ledger-customer-modal-body .ledger-guests-panel{border-color:rgba(13,110,253,.25);background:#f8fbff}.ledger-customer-modal-body .ledger-guest-empty{border-color:rgba(13,110,253,.25);color:#64748b}
        .ledger-calendar-booking--no_show{background:#94a3b8;color:#0f172a}
        @media(max-width:1399.98px){.ledger-summary{grid-template-columns:repeat(3,minmax(0,1fr))}}@media(max-width:1199.98px){.ledger-help,.ledger-booking-steps,.ledger-booking-guide{grid-template-columns:1fr}}@media(max-width:767.98px){.ledger-hero__actions,.ledger-hero__actions .btn,.ledger-search-panel .btn{width:100%}.ledger-hero__actions .btn{justify-content:center}.ledger-summary,.ledger-calendar-stats,.ledger-detail-grid,.ledger-customer-actions,.ledger-booking-summary-grid{grid-template-columns:1fr}.ledger-search-panel{display:block}.ledger-calendar-nav,.ledger-calendar-nav .btn,.ledger-calendar-jump,.ledger-calendar-jump select,.ledger-calendar-jump input,.ledger-booking-toolbar .btn,.ledger-booking-jump,.ledger-booking-jump select,.ledger-booking-jump input{width:100%;max-width:none}.ledger-calendar-nav .btn,.ledger-booking-toolbar .btn{justify-content:center}.ledger-calendar-jump,.ledger-booking-jump{border-radius:1rem}.ledger-guests-panel__header .btn{width:100%}.ledger-table{min-width:0;border-spacing:0 .8rem}.ledger-table thead{display:none}.ledger-table tbody,.ledger-table tr,.ledger-table td{display:block;width:100%}.ledger-table tbody tr{padding:.85rem;border-radius:1rem;background:#fff;box-shadow:0 .9rem 1.8rem rgba(15,23,42,.08);filter:none}.ledger-table tbody td{display:grid;grid-template-columns:7rem 1fr;gap:.75rem;align-items:center;border:0!important;border-radius:0!important;padding:.5rem .35rem;background:transparent}.ledger-table tbody td::before{content:attr(data-label);color:#64748b;font-size:.72rem;font-weight:950;text-transform:uppercase}.ledger-table tbody td:first-child{box-shadow:none!important}.ledger-action-panel{justify-items:stretch}.ledger-reservation-preview{min-width:0}.ledger-actions{justify-content:flex-start;min-width:0}.ledger-actions .btn{width:100%}.ledger-observation-cell{min-width:0}}
        @media print{.main-header,.main-sidebar,.content-header,.ledger-hero__actions,.ledger-help,.ledger-actions,.ledger-search-panel{display:none!important}.content-wrapper{margin-left:0!important}.ledger-paper{box-shadow:none}}
    </style>
@endpush

@push('js')
    <script>
        function initializeLedgerPage() {
        const bootstrap = window.bootstrap?.Modal ? window.bootstrap : createLedgerBootstrapFallback();

        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content ?? '';
        const jqueryAssetUrl = @json(asset('vendor/jquery/jquery.min.js'));
        const select2AssetUrl = @json(asset('vendor/select2/select2.full.min.js'));
        const reservationStoreUrl = @json(route('adminlte.reservations.store'));
        const reservationQuoteUrl = @json(route('adminlte.reservations.quote'));
        const paymentStoreUrl = @json(route('adminlte.payments.store'));
        const customerSearchUrl = @json(route('adminlte.reservations.customer-search'));
        const customerStoreUrl = @json(route('adminlte.customers.store'));
        const supportedCurrencies = @json($supportedCurrencies);
        const currencySymbols = @json($currencySymbols);
        const canCreatePayment = @json(auth()->user()?->can('create', \App\Models\Payment::class) ?? false);
        const paymentMethods = {
            cash: 'Efectivo',
            qr: 'QR',
            bank: 'Deposito / Transferencia',
            card: 'Tarjeta',
            other: 'Otro',
        };
        let ledgerSearchInput = document.getElementById('ledger-search-input');
        let ledgerClearSearch = document.getElementById('ledger-clear-search');
        let ledgerRows = Array.from(document.querySelectorAll('[data-ledger-row]'));
        const statusModal = new bootstrap.Modal(document.getElementById('ledgerStatusModal'));
        const reservationsModal = new bootstrap.Modal(document.getElementById('ledgerReservationsModal'));
        const paymentsModal = new bootstrap.Modal(document.getElementById('ledgerPaymentsModal'));
        const newReservationModal = new bootstrap.Modal(document.getElementById('ledgerNewReservationModal'));
        const actionModal = new bootstrap.Modal(document.getElementById('ledgerActionModal'));
        const customerOptionsModal = new bootstrap.Modal(document.getElementById('ledgerCustomerOptionsModal'));
        const customerEditModal = new bootstrap.Modal(document.getElementById('ledgerCustomerEditModal'));
        const customerPaymentsModal = new bootstrap.Modal(document.getElementById('ledgerCustomerPaymentsModal'));
        const quickCustomerModal = new bootstrap.Modal(document.getElementById('ledgerQuickCustomerModal'));
        const extendStayModal = new bootstrap.Modal(document.getElementById('ledgerExtendStayModal'));
        const editReservationDatesModal = new bootstrap.Modal(document.getElementById('ledgerEditReservationDatesModal'));
        let activeReservationsRoom = null;
        let activeCalendarMonth = null;
        let activeBookingRoom = null;
        let activeBookingMonth = new Date(new Date().getFullYear(), new Date().getMonth(), 1);
        let bookingSelection = { start: null, end: null };
        let bookingQuote = null;
        let pendingReservationGuests = [];
        let quickGuestRowCounter = 0;
        let activeCustomerReservation = null;
        let activeCustomerSummary = null;
        let countriesCatalog = [];
        let countriesLoadPromise = null;
        let guestRowCounter = 0;

        bindLedgerSearch();

        document.addEventListener('click', async (event) => {
            const infoButton = event.target.closest('.ledger-info-btn');
            const actionButton = event.target.closest('.ledger-action');
            const paymentAction = event.target.closest('[data-payment-action]');
            const reservationAction = event.target.closest('[data-reservation-action]');
            const newReservationButton = event.target.closest('.ledger-new-reservation-btn');
            const calendarNavButton = event.target.closest('[data-calendar-nav]');
            const reservationDetailButton = event.target.closest('[data-reservation-detail-id]');
            const calendarBookingButton = event.target.closest('[data-calendar-reservation-id]');
            const calendarTodayButton = event.target.closest('[data-calendar-today]');
            const customerActionButton = event.target.closest('[data-customer-action-reservation-id]');
            const bookingCalendarNav = event.target.closest('[data-booking-calendar-nav]');
            const bookingReservationButton = event.target.closest('[data-booking-reservation-id]');
            const bookingDayButton = event.target.closest('[data-booking-day]');
            const cancellationReviewButton = event.target.closest('.ledger-cancellation-review-btn');
            const cancellationDoneButton = event.target.closest('.ledger-cancellation-done-btn');

            if (cancellationReviewButton) {
                openCancellationReview(cancellationReviewButton.dataset.roomId, cancellationReviewButton.dataset.reservationId);
                return;
            }

            if (cancellationDoneButton) {
                await markCancellationReviewed(cancellationDoneButton.dataset.reviewUrl, cancellationDoneButton.dataset.roomId);
                return;
            }

            if (bookingReservationButton) {
                showBookingReservationInfo(Number(bookingReservationButton.dataset.bookingReservationId));
                return;
            }

            if (bookingCalendarNav) {
                activeBookingMonth.setMonth(activeBookingMonth.getMonth() + Number(bookingCalendarNav.dataset.bookingCalendarNav));
                renderBookingCalendar();
                return;
            }

            if (bookingDayButton) {
                selectBookingDate(bookingDayButton.dataset.bookingDay);
                return;
            }

            if (calendarNavButton) {
                activeCalendarMonth.setMonth(activeCalendarMonth.getMonth() + Number(calendarNavButton.dataset.calendarNav));
                refreshReservationCalendar();
                return;
            }

            if (calendarTodayButton) {
                const today = new Date();
                activeCalendarMonth = new Date(today.getFullYear(), today.getMonth(), 1);
                refreshReservationCalendar();
                return;
            }

            if (reservationDetailButton) {
                showReservationDetail(Number(reservationDetailButton.dataset.reservationDetailId), true);
                return;
            }

            if (calendarBookingButton) {
                showReservationDetail(Number(calendarBookingButton.dataset.calendarReservationId));
                return;
            }

            if (customerActionButton) {
                showReservationDetail(Number(customerActionButton.dataset.customerActionReservationId));
                return;
            }

            if (reservationAction) {
                handleReservationAction(reservationAction);
                return;
            }

            if (newReservationButton) {
                openNewReservationModal(readRoomData(newReservationButton.dataset.roomId));
                return;
            }

            if (infoButton) {
                const room = readRoomData(infoButton.dataset.roomId);
                if (infoButton.dataset.modalType === 'status') openStatusModal(room);
                if (infoButton.dataset.modalType === 'reservations') openReservationsModal(room);
                if (infoButton.dataset.modalType === 'payments') openPaymentsModal(room);
                return;
            }

            if (actionButton) {
                openActionModal({
                    url: actionButton.dataset.actionUrl,
                    kicker: 'Movimiento de habitacion',
                    title: actionButton.dataset.actionTitle,
                    message: actionButton.dataset.forceCheckout === '1'
                        ? 'Esta reserva tiene saldo pendiente. Si continuas, el sistema registrara la salida de todas formas.'
                        : 'Confirma esta accion para actualizar el libro de recepcion.',
                    field: 'notes',
                    help: 'Puedes anotar cualquier detalle que recepcion deba recordar.',
                    forceCheckout: actionButton.dataset.forceCheckout === '1',
                    submitText: 'Confirmar',
                    submitClass: 'btn btn-primary',
                });
                return;
            }

            if (paymentAction) {
                const isReject = paymentAction.dataset.paymentAction === 'reject';
                openActionModal({
                    url: paymentAction.dataset.url,
                    kicker: isReject ? 'Rechazar comprobante' : 'Aprobar comprobante',
                    title: isReject ? 'Rechazar pago recibido' : 'Aprobar pago recibido',
                    message: isReject
                        ? 'Indica el motivo si el comprobante no corresponde. El cliente podra revisar el estado de su reserva.'
                        : 'El pago se aplicara al saldo de la reserva y actualizara el estado financiero.',
                    field: isReject ? 'reason' : 'notes',
                    help: isReject ? 'Ejemplo: monto incorrecto, comprobante ilegible, cuenta no corresponde.' : 'Puedes anotar el banco, cajero o verificacion realizada.',
                    forceCheckout: false,
                    submitText: isReject ? 'Rechazar pago' : 'Aprobar pago',
                    submitClass: isReject ? 'btn btn-danger' : 'btn btn-success',
                });
            }
        });

        document.addEventListener('contextmenu', (event) => {
            const requestButton = event.target.closest('.ledger-request-card');
            if (requestButton) {
                event.preventDefault();
                showReservationDetail(Number(requestButton.dataset.calendarReservationId));
                openRequestDecisionMenu(Number(requestButton.dataset.calendarReservationId));
                return;
            }

            const calendarBookingButton = event.target.closest('[data-calendar-reservation-id]');
            const customerActionButton = event.target.closest('[data-customer-action-reservation-id]');
            const reservationId = calendarBookingButton?.dataset.calendarReservationId
                || customerActionButton?.dataset.customerActionReservationId;

            if (!reservationId) {
                return;
            }

            event.preventDefault();
            openCustomerOptions(Number(reservationId));
        });

        document.getElementById('ledger-status-form')?.addEventListener('submit', async (event) => {
            event.preventDefault();
            const formData = new FormData();
            formData.append('_method', 'PATCH');
            formData.append('status', document.getElementById('ledger-room-status-select').value);
            const notes = document.getElementById('ledger-room-status-notes').value.trim();
            if (notes) formData.append('notes', notes);
            await submitLedgerAction(document.getElementById('ledger-status-url').value, formData);
        });

        document.getElementById('ledger-action-form')?.addEventListener('submit', async (event) => {
            event.preventDefault();
            const formData = new FormData();
            const field = document.getElementById('ledger-action-field').value || 'notes';
            const notes = document.getElementById('ledger-action-notes').value.trim();
            if (notes) formData.append(field, notes);
            if (document.getElementById('ledger-action-force-checkout').value === '1') {
                formData.append('force_checkout', '1');
            }
            await submitLedgerAction(document.getElementById('ledger-action-url').value, formData);
        });

        document.addEventListener('change', (event) => {
            if (!event.target.matches('[data-calendar-month], [data-calendar-year]') || !activeCalendarMonth) {
                return;
            }

            applyCalendarJump();
        });

        document.addEventListener('keydown', (event) => {
            if (!event.target.matches('[data-calendar-year]') || event.key !== 'Enter') {
                return;
            }

            event.preventDefault();
            applyCalendarJump();
        });

        document.getElementById('ledger-edit-customer-button')?.addEventListener('click', async () => {
            if (!activeCustomerReservation) return;
            await openCustomerEditModal(activeCustomerReservation);
        });

        document.getElementById('ledger-view-customer-payments-button')?.addEventListener('click', async () => {
            if (!activeCustomerReservation) return;
            await openCustomerPaymentsModal(activeCustomerReservation);
        });

        document.getElementById('ledger-checkin-reservation-button')?.addEventListener('click', () => {
            if (!activeCustomerReservation) return;
            openReservationMovementAction(activeCustomerReservation, 'checkin');
        });

        document.getElementById('ledger-checkout-reservation-button')?.addEventListener('click', () => {
            if (!activeCustomerReservation) return;
            openReservationMovementAction(activeCustomerReservation, 'checkout');
        });

        document.getElementById('ledger-extend-reservation-button')?.addEventListener('click', () => {
            if (!activeCustomerReservation) return;
            openExtendStayModal(activeCustomerReservation);
        });

        document.getElementById('ledger-extend-new-check-out')?.addEventListener('input', updateExtendStayPreview);
        document.getElementById('ledger-edit-check-in')?.addEventListener('input', updateEditDatesPreview);
        document.getElementById('ledger-edit-check-out')?.addEventListener('input', updateEditDatesPreview);

        document.getElementById('ledger-extend-stay-form')?.addEventListener('submit', async (event) => {
            event.preventDefault();
            await submitExtendStay(event.currentTarget);
        });

        document.getElementById('ledger-edit-reservation-dates-form')?.addEventListener('submit', async (event) => {
            event.preventDefault();
            await submitReservationDateEdit(event.currentTarget);
        });

        document.getElementById('ledger-customer-edit-form')?.addEventListener('submit', async (event) => {
            event.preventDefault();
            await submitCustomerEdit(event.currentTarget);
        });

        document.addEventListener('submit', async (event) => {
            if (!event.target.matches('#ledger-customer-payment-form')) {
                return;
            }

            event.preventDefault();
            await submitCustomerPayment(event.target);
        });

        document.addEventListener('click', (event) => {
            const methodButton = event.target.closest('[data-payment-method-choice]');
            const fillBalanceButton = event.target.closest('[data-payment-fill-balance]');
            const clearPaymentButton = event.target.closest('[data-payment-clear]');

            if (methodButton) {
                selectCustomerPaymentMethod(methodButton.dataset.paymentMethodChoice);
                return;
            }

            if (fillBalanceButton) {
                fillCustomerPaymentBalance();
                return;
            }

            if (clearPaymentButton) {
                clearCustomerPaymentForm();
            }
        });

        document.addEventListener('input', (event) => {
            if (!event.target.matches('#ledger-customer-payment-amount, #ledger-customer-payment-currency')) {
                return;
            }

            updateCustomerPaymentPreview();
        });

        document.addEventListener('change', (event) => {
            if (!event.target.matches('#ledger-customer-payment-currency, #ledger-customer-payment-method')) {
                return;
            }

            if (event.target.matches('#ledger-customer-payment-method')) {
                markActiveCustomerPaymentMethod(event.target.value);
            }

            updateCustomerPaymentPreview();
        });

        document.getElementById('ledger-add-guest-button')?.addEventListener('click', () => {
            addGuestRow();
        });

        document.getElementById('ledger-show-new-customer')?.addEventListener('click', () => {
            openQuickCustomerModal();
        });

        document.getElementById('ledger-quick-customer-form')?.addEventListener('submit', async (event) => {
            event.preventDefault();
            await storeQuickCustomer();
        });

        document.getElementById('ledger-add-quick-guest-button')?.addEventListener('click', () => {
            addQuickGuestRow();
        });

        document.getElementById('ledger-new-reservation-form')?.addEventListener('submit', async (event) => {
            event.preventDefault();
            await storeLedgerReservation(event.currentTarget);
        });

        ['ledger-reservation-adults', 'ledger-reservation-children', 'ledger-reservation-payment-currency', 'ledger-reservation-payment-method', 'ledger-reservation-payment-amount'].forEach((id) => {
            document.getElementById(id)?.addEventListener('change', () => {
                if (id === 'ledger-reservation-payment-method') {
                    document.getElementById('ledger-reservation-preferred-payment').value = document.getElementById(id).value;
                }
                refreshBookingQuote();
            });
        });

        document.getElementById('ledger-reservation-customer-id')?.addEventListener('change', () => {
            updateBookingSummary();
        });

        document.getElementById('ledger-booking-month')?.addEventListener('change', applyBookingCalendarJump);
        document.getElementById('ledger-booking-year')?.addEventListener('change', applyBookingCalendarJump);
        document.getElementById('ledger-booking-year')?.addEventListener('keydown', (event) => {
            if (event.key !== 'Enter') return;
            event.preventDefault();
            applyBookingCalendarJump();
        });
        document.getElementById('ledger-booking-today')?.addEventListener('click', () => {
            const today = new Date();
            activeBookingMonth = new Date(today.getFullYear(), today.getMonth(), 1);
            renderBookingCalendar();
        });

        document.addEventListener('click', (event) => {
            const removeGuestButton = event.target.closest('[data-remove-guest-row]');
            const removeQuickGuestButton = event.target.closest('[data-remove-quick-guest-row]');

            if (removeQuickGuestButton) {
                removeQuickGuestButton.closest('[data-quick-guest-row]')?.remove();
                refreshQuickGuestEmptyState();
                notifyToast('Acompanante quitado de la reserva nueva.', 'info');
                return;
            }

            if (removeGuestButton) {
                removeGuestButton.closest('[data-guest-row]')?.remove();
                refreshGuestEmptyState();
                notifyToast('Acompanante quitado. Guarda el cliente para confirmar el cambio.', 'info');
            }
        });

        document.getElementById('ledgerNewReservationModal')?.addEventListener('hidden.bs.modal', () => {
            resetNewReservationForm();
        });

        window.addEventListener('message', async (event) => {
            if (event.origin !== window.location.origin || event.data?.type !== 'front-desk-reservation-created') {
                return;
            }

            showLoadingAlert('Registrando reserva', 'La reserva fue enviada. Estamos actualizando el libro de recepcion.');
            newReservationModal.hide();
            await refreshLedgerFromServer('Reserva registrada correctamente.', false);
        });

        function bindLedgerSearch() {
            ledgerSearchInput = document.getElementById('ledger-search-input');
            ledgerClearSearch = document.getElementById('ledger-clear-search');
            ledgerRows = Array.from(document.querySelectorAll('[data-ledger-row]'));

            ledgerSearchInput?.addEventListener('input', filterLedgerRows);
            ledgerClearSearch?.addEventListener('click', () => {
                ledgerSearchInput.value = '';
                filterLedgerRows();
                ledgerSearchInput.focus();
            });
        }

        function filterLedgerRows() {
            const term = ledgerSearchInput?.value.trim().toLowerCase() || '';
            ledgerRows.forEach((row) => row.classList.toggle('d-none', term !== '' && !row.dataset.search.includes(term)));
        }

        async function openNewReservationModal(room) {
            activeBookingRoom = room;
            activeBookingMonth = new Date(new Date().getFullYear(), new Date().getMonth(), 1);
            bookingSelection = { start: null, end: null };
            bookingQuote = null;

            document.getElementById('ledgerNewReservationModalTitle').textContent = `Hab. ${room.room_number} - ${room.room_type}`;
            resetNewReservationForm(false);
            document.getElementById('ledger-reservation-room-id').value = room.room_id;
            await initializeReservationCustomerSelect();
            renderBookingCalendar();
            updateBookingSummary();
            newReservationModal.show();
        }

        function resetNewReservationForm(clearRoom = true) {
            const form = document.getElementById('ledger-new-reservation-form');
            form?.reset();
            bookingSelection = { start: null, end: null };
            bookingQuote = null;
            document.getElementById('ledger-reservation-check-in').value = '';
            document.getElementById('ledger-reservation-check-out').value = '';
            document.getElementById('ledger-booking-date-summary').textContent = 'Primero selecciona entrada y salida.';
            document.getElementById('ledger-booking-summary').innerHTML = defaultBookingSummaryHtml();
            document.getElementById('ledger-save-reservation-button').disabled = true;
            pendingReservationGuests = [];
            renderQuickGuestRows([]);

            if (clearRoom) {
                activeBookingRoom = null;
                document.getElementById('ledger-reservation-room-id').value = '';
            }

            const customerSelect = document.getElementById('ledger-reservation-customer-id');
            if (customerSelect && window.jQuery?.fn?.select2) {
                window.jQuery(customerSelect).val(null).trigger('change');
            }
        }

        async function initializeReservationCustomerSelect() {
            await ensureSelect2Loaded();
            const select = document.getElementById('ledger-reservation-customer-id');
            if (!select || typeof window.jQuery?.fn?.select2 !== 'function') return;

            const $select = window.jQuery(select);
            if ($select.hasClass('select2-hidden-accessible')) {
                $select.select2('destroy');
            }

            $select.select2({
                width: '100%',
                placeholder: 'Escribe al menos 3 letras o numeros',
                allowClear: true,
                minimumInputLength: 3,
                dropdownParent: window.jQuery('#ledgerNewReservationModal'),
                ajax: {
                    url: customerSearchUrl,
                    dataType: 'json',
                    delay: 250,
                    data: (params) => ({
                        term: params.term || '',
                        page: params.page || 1,
                    }),
                    processResults: (data) => data,
                    cache: true,
                },
                language: {
                    inputTooShort: () => 'Escribe al menos 3 letras o numeros para buscar',
                    noResults: () => 'No se encontraron clientes',
                    searching: () => 'Buscando cliente...',
                    loadingMore: () => 'Cargando mas resultados...',
                    errorLoading: () => 'No se pudo buscar clientes',
                },
            });
        }

        async function openQuickCustomerModal() {
            document.getElementById('ledger-quick-customer-form')?.reset();
            renderQuickGuestRows(pendingReservationGuests);
            await ensureCountriesLoaded();
            quickCustomerModal.show();
        }

        function renderBookingCalendar() {
            if (!activeBookingRoom) return;
            const title = document.getElementById('ledger-booking-calendar-title');
            const calendar = document.getElementById('ledger-booking-calendar');
            const monthSelect = document.getElementById('ledger-booking-month');
            const yearInput = document.getElementById('ledger-booking-year');
            title.textContent = activeBookingMonth.toLocaleDateString('es-BO', { month: 'long', year: 'numeric' });
            monthSelect.innerHTML = renderMonthOptions(activeBookingMonth);
            monthSelect.value = String(activeBookingMonth.getMonth());
            yearInput.value = String(activeBookingMonth.getFullYear());

            const monthStart = new Date(activeBookingMonth.getFullYear(), activeBookingMonth.getMonth(), 1);
            const monthEnd = new Date(activeBookingMonth.getFullYear(), activeBookingMonth.getMonth() + 1, 0);
            const gridStart = new Date(monthStart);
            gridStart.setDate(monthStart.getDate() - ((monthStart.getDay() + 6) % 7));
            const gridEnd = new Date(monthEnd);
            gridEnd.setDate(monthEnd.getDate() + (6 - ((monthEnd.getDay() + 6) % 7)));
            const today = stripTime(new Date());
            const days = [];
            const cursor = new Date(gridStart);

            while (cursor <= gridEnd) {
                const dateValue = isoDate(cursor);
                const isMuted = cursor.getMonth() !== monthStart.getMonth();
                const isPast = stripTime(cursor) < today;
                const dayReservation = bookingReservationForDate(cursor);
                const isBlocked = isPast || Boolean(dayReservation);
                const isSelected = [bookingSelection.start, bookingSelection.end].includes(dateValue);
                const isRange = bookingSelection.start && bookingSelection.end
                    && stripTime(cursor) > parseIsoDate(bookingSelection.start)
                    && stripTime(cursor) < parseIsoDate(bookingSelection.end);

                days.push(`<button type="button" class="ledger-booking-day ${isMuted ? 'is-muted' : ''} ${isBlocked ? 'is-blocked' : ''} ${isSelected ? 'is-selected' : ''} ${isRange ? 'is-range' : ''}" data-booking-day="${dateValue}" ${isPast ? 'disabled' : ''}>
                    <span>${cursor.getDate()}</span>
                    ${dayReservation ? `<small class="ledger-booking-reserved-by">Reservado por</small><strong>${escapeHtml(dayReservation.customer || 'Cliente')}</strong><em data-booking-reservation-id="${dayReservation.id}">Ver detalle</em>` : ''}
                </button>`);
                cursor.setDate(cursor.getDate() + 1);
            }

            calendar.innerHTML = `<div class="ledger-booking-calendar-grid">
                ${['Lun', 'Mar', 'Mie', 'Jue', 'Vie', 'Sab', 'Dom'].map((day) => `<div class="ledger-booking-weekday">${day}</div>`).join('')}
                ${days.join('')}
            </div>`;
        }

        function applyBookingCalendarJump() {
            const month = Number(document.getElementById('ledger-booking-month')?.value ?? activeBookingMonth.getMonth());
            const rawYear = Number(document.getElementById('ledger-booking-year')?.value ?? activeBookingMonth.getFullYear());
            const year = Number.isInteger(rawYear) && rawYear >= 1900 && rawYear <= 2200 ? rawYear : activeBookingMonth.getFullYear();
            activeBookingMonth = new Date(year, month, 1);
            renderBookingCalendar();
        }

        function selectBookingDate(dateValue) {
            const selectedDate = parseIsoDate(dateValue);
            const reservation = bookingReservationForDate(selectedDate);
            if (reservation) {
                showBookingReservationInfo(Number(reservation.id));
                return;
            }

            if (!bookingSelection.start || bookingSelection.end || dateValue <= bookingSelection.start) {
                bookingSelection = { start: dateValue, end: null };
            } else {
                bookingSelection.end = dateValue;
            }

            if (bookingSelection.start && bookingSelection.end && bookingRangeHasConflict(bookingSelection.start, bookingSelection.end)) {
                notifyError('Ese rango se cruza con una reserva existente. Elige fechas libres.');
                bookingSelection.end = null;
            }

            document.getElementById('ledger-reservation-check-in').value = bookingSelection.start || '';
            document.getElementById('ledger-reservation-check-out').value = bookingSelection.end || '';
            updateBookingDateSummary();
            renderBookingCalendar();
            refreshBookingQuote();
        }

        function updateBookingDateSummary() {
            const summary = document.getElementById('ledger-booking-date-summary');
            if (!bookingSelection.start) {
                summary.textContent = 'Primero selecciona entrada y salida.';
                return;
            }

            if (!bookingSelection.end) {
                summary.textContent = `Entrada seleccionada: ${formatCalendarDate(parseIsoDate(bookingSelection.start))}. Ahora elige la salida.`;
                return;
            }

            const nights = Math.max((parseIsoDate(bookingSelection.end) - parseIsoDate(bookingSelection.start)) / 86400000, 1);
            summary.textContent = `Entrada ${formatCalendarDate(parseIsoDate(bookingSelection.start))} - Salida ${formatCalendarDate(parseIsoDate(bookingSelection.end))} - ${nights} noche(s).`;
        }

        function isBookingDayBlocked(date) {
            return Boolean(bookingReservationForDate(date));
        }

        function bookingReservationForDate(date) {
            return activeBookingRoom.reservations.find((reservation) => {
                if (!['pending', 'confirmed', 'checked_in'].includes(reservation.status)) return false;
                const checkIn = parseIsoDate(reservation.check_in_iso);
                const checkOut = parseIsoDate(reservation.check_out_iso);
                return checkIn && checkOut && stripTime(date) >= stripTime(checkIn) && stripTime(date) < stripTime(checkOut);
            });
        }

        async function showBookingReservationInfo(reservationId) {
            if (!activeBookingRoom) return;
            const reservation = activeBookingRoom.reservations.find((item) => Number(item.id) === Number(reservationId));
            if (!reservation) return;

            const html = `
                <div class="text-start">
                    <div class="mb-2"><strong>Cliente:</strong> ${escapeHtml(reservation.customer || 'Sin cliente')}</div>
                    <div class="mb-2"><strong>Reserva:</strong> ${escapeHtml(reservation.code || '-')}</div>
                    <div class="mb-2"><strong>Estado:</strong> ${escapeHtml(reservation.status_label || '-')}</div>
                    <div class="mb-2"><strong>Fechas reservadas:</strong> ${escapeHtml(reservation.check_in || reservation.check_in_iso || '-')} al ${escapeHtml(reservation.check_out || reservation.check_out_iso || '-')}</div>
                    ${reservation.checked_out_at ? `<div class="mb-2"><strong>Salida real:</strong> ${escapeHtml(reservation.checked_out_at)}${reservation.is_early_checkout ? ' <span class="badge text-bg-warning">salio antes</span>' : ''}</div>` : ''}
                    <div class="mb-2"><strong>Personas:</strong> ${escapeHtml(String(reservation.people || '-'))}</div>
                    ${reservation.phone ? `<div class="mb-2"><strong>Telefono:</strong> ${escapeHtml(reservation.phone)}</div>` : ''}
                    ${reservation.notes ? `<div><strong>Notas:</strong> ${escapeHtml(reservation.notes)}</div>` : ''}
                </div>`;

            if (window.Swal) {
                await window.Swal.fire({
                    icon: 'info',
                    title: `Habitacion ${escapeHtml(activeBookingRoom.room_number)} reservada`,
                    html,
                    confirmButtonText: 'Entendido',
                });
                return;
            }

            console.info(`Habitacion reservada por ${reservation.customer || 'cliente'}`);
        }

        function bookingRangeHasConflict(startValue, endValue) {
            const start = parseIsoDate(startValue);
            const end = parseIsoDate(endValue);
            return activeBookingRoom.reservations.some((reservation) => {
                if (!['pending', 'confirmed', 'checked_in'].includes(reservation.status)) return false;
                const checkIn = parseIsoDate(reservation.check_in_iso);
                const checkOut = parseIsoDate(reservation.check_out_iso);
                return checkIn && checkOut && start < checkOut && end > checkIn;
            });
        }

        async function refreshBookingQuote() {
            if (!activeBookingRoom || !bookingSelection.start || !bookingSelection.end) {
                updateBookingSummary();
                return;
            }

            const formData = new FormData();
            formData.append('room_id', activeBookingRoom.room_id);
            formData.append('check_in', bookingSelection.start);
            formData.append('check_out', bookingSelection.end);

            try {
                const response = await fetch(reservationQuoteUrl, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': csrfToken,
                        'Accept': 'application/json',
                    },
                    body: formData,
                });

                if (!response.ok) {
                    const payload = await safeJsonResponse(response);
                    bookingQuote = null;
                    await notifyError(payload.message || firstValidationMessage(payload) || 'No se pudo calcular la reserva.');
                    updateBookingSummary();
                    return;
                }

                bookingQuote = await response.json();
                updateBookingSummary();
            } catch (error) {
                bookingQuote = null;
                updateBookingSummary(error.message || 'No se pudo calcular la reserva.');
            }
        }

        function updateBookingSummary(errorMessage = null) {
            const summary = document.getElementById('ledger-booking-summary');
            const saveButton = document.getElementById('ledger-save-reservation-button');
            const currency = document.getElementById('ledger-reservation-payment-currency')?.value || 'BOB';
            const quoteCurrency = bookingQuote?.quote_by_currency?.[currency] || bookingQuote?.quote_by_currency?.BOB || null;
            const amount = Number(document.getElementById('ledger-reservation-payment-amount')?.value || 0);
            const symbol = currencySymbols[currency] || `${currency} `;

            if (errorMessage) {
                summary.innerHTML = `<div class="ledger-booking-ready"><span>!</span><div><h6>No se pudo calcular</h6><p>Revisa los datos marcados antes de guardar.</p></div></div><div class="ledger-booking-warning">${escapeHtml(errorMessage)}</div>`;
                saveButton.disabled = true;
                saveButton.textContent = 'Revisa los datos';
                return;
            }

            if (!bookingQuote || !quoteCurrency) {
                summary.innerHTML = defaultBookingSummaryHtml();
                saveButton.disabled = true;
                saveButton.textContent = 'Primero completa fechas';
                return;
            }

            const customerSelected = Boolean(document.getElementById('ledger-reservation-customer-id')?.value);
            const deposit = Number(quoteCurrency.deposit_amount_required || 0);
            const total = Number(quoteCurrency.total_amount || 0);
            const balance = Math.max(total - amount, 0);
            const paymentWarning = amount > 0 && amount < deposit
                ? `<div class="ledger-booking-warning mt-3">El pago inicial debe cubrir al menos ${formatCurrency(deposit, currency)} para confirmar la reserva. Puedes dejarlo en 0 para crearla pendiente.</div>`
                : '';
            const currencyNote = !bookingQuote?.quote_by_currency?.[currency]
                ? `<div class="small text-muted fw-bold mt-2">Esta moneda esta habilitada en configuracion, pero la habitacion no tiene precio propio para ${escapeHtml(currency)}. El sistema registrara la moneda elegida y usara la equivalencia configurada para caja/saldo.</div>`
                : '';
            const readyMessage = customerSelected
                ? '<div class="ledger-booking-ok">Todo lo basico esta listo. Revisa el resumen y presiona Guardar reserva.</div>'
                : '<div class="ledger-booking-warning mt-3">Falta seleccionar o registrar el cliente.</div>';

            summary.innerHTML = `<div class="ledger-booking-ready"><span>4</span><div><h6>Resumen calculado en ${escapeHtml(currency)}</h6><p>Verifica total, anticipo y saldo antes de guardar.</p></div></div>
                <div class="ledger-booking-summary-grid">
                    <div><span>Noches</span><strong>${bookingQuote.nights}</strong></div>
                    <div><span>Precio por noche</span><strong>${formatCurrency(quoteCurrency.price_per_night, currency)}</strong></div>
                    <div><span>Total</span><strong>${formatCurrency(total, currency)}</strong></div>
                    <div><span>Anticipo minimo</span><strong>${formatCurrency(deposit, currency)}</strong></div>
                    <div><span>Paga ahora</span><strong>${formatCurrency(amount, currency)}</strong></div>
                    <div><span>Saldo estimado</span><strong>${formatCurrency(balance, currency)}</strong></div>
                </div>
                ${currencyNote}
                ${paymentWarning || readyMessage}`;

            saveButton.disabled = Boolean(paymentWarning) || !customerSelected;
            saveButton.textContent = customerSelected && !paymentWarning ? 'Guardar reserva' : 'Falta completar';
        }

        function defaultBookingSummaryHtml() {
            return `<div class="ledger-booking-ready">
                <span>4</span>
                <div>
                    <h6>Resumen de la reserva</h6>
                    <p>Selecciona fechas libres para calcular total, anticipo y saldo.</p>
                </div>
            </div>
            <div class="ledger-booking-summary-grid">
                <div><span>Fechas</span><strong>Pendiente</strong></div>
                <div><span>Cliente</span><strong>Pendiente</strong></div>
                <div><span>Total</span><strong>-</strong></div>
                <div><span>Guardar</span><strong>No disponible</strong></div>
            </div>`;
        }

        async function storeQuickCustomer() {
            const fullName = document.getElementById('ledger-new-customer-name').value.trim();
            if (!fullName) {
                await notifyError('Escribe el nombre completo del cliente.');
                return;
            }

            showLoadingAlert('Guardando cliente', 'Estamos registrando al cliente nuevo.');
            const formData = new FormData();
            formData.append('full_name', fullName);
            formData.append('document_type', document.getElementById('ledger-new-customer-document-type').value);
            formData.append('document_number', document.getElementById('ledger-new-customer-document-number').value);
            formData.append('phone', document.getElementById('ledger-new-customer-phone').value);
            formData.append('whatsapp', document.getElementById('ledger-new-customer-whatsapp').value);
            formData.append('email', document.getElementById('ledger-new-customer-email').value);
            formData.append('nationality', document.getElementById('ledger-new-customer-nationality').value);
            formData.append('country', document.getElementById('ledger-new-customer-country').value);
            formData.append('city', document.getElementById('ledger-new-customer-city').value);
            formData.append('birth_date', document.getElementById('ledger-new-customer-birth-date').value);
            formData.append('address', document.getElementById('ledger-new-customer-address').value);
            formData.append('company_name', document.getElementById('ledger-new-customer-company-name').value);
            formData.append('tax_number', document.getElementById('ledger-new-customer-tax-number').value);
            formData.append('notes', document.getElementById('ledger-new-customer-notes').value);
            formData.append('is_foreign', document.getElementById('ledger-new-customer-is-foreign').checked ? '1' : '0');
            formData.append('is_company', document.getElementById('ledger-new-customer-is-company').checked ? '1' : '0');
            formData.append('is_active', '1');

            try {
                const response = await fetch(customerStoreUrl, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': csrfToken,
                        'Accept': 'application/json',
                    },
                    body: formData,
                });
                const payload = await safeJsonResponse(response);
                closeLoadingAlert();

                if (!response.ok) {
                    await notifyError(payload.message || firstValidationMessage(payload) || 'No se pudo registrar el cliente.');
                    return;
                }

                const customer = payload.customer;
                const select = document.getElementById('ledger-reservation-customer-id');
                const option = new Option(customer.text, customer.id, true, true);
                select.appendChild(option);
                window.jQuery(select).trigger('change');
                pendingReservationGuests = quickGuestFormRows();
                quickCustomerModal.hide();
                notifyToast('Cliente registrado, seleccionado y acompanantes preparados.');
                updateBookingSummary();
            } catch (error) {
                closeLoadingAlert();
                await notifyError(error.message || 'No se pudo registrar el cliente.');
            }
        }

        function renderQuickGuestRows(guests) {
            const list = document.getElementById('ledger-quick-guests-list');
            if (!list) return;
            list.innerHTML = '';
            quickGuestRowCounter = 0;
            guests.forEach((guest) => addQuickGuestRow(guest));
            refreshQuickGuestEmptyState();
        }

        function addQuickGuestRow(guest = {}) {
            const list = document.getElementById('ledger-quick-guests-list');
            const index = quickGuestRowCounter++;
            const wrapper = document.createElement('article');
            wrapper.className = 'ledger-guest-card';
            wrapper.dataset.quickGuestRow = String(index);
            wrapper.innerHTML = `
                <div class="ledger-guest-card__top">
                    <strong>Acompanante ${index + 1}</strong>
                    <button type="button" class="btn btn-outline-danger btn-sm rounded-pill" data-remove-quick-guest-row>Quitar</button>
                </div>
                <div class="row g-2">
                    <div class="col-md-4">
                        <label class="form-label">Nombre completo</label>
                        <input type="text" class="form-control" name="quick_guests[${index}][full_name]" value="${escapeHtml(guest.full_name || '')}">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Documento</label>
                        <select class="form-select" name="quick_guests[${index}][document_type]">
                            <option value="">Opcional</option>
                            <option value="ci" ${guest.document_type === 'ci' ? 'selected' : ''}>CI</option>
                            <option value="passport" ${guest.document_type === 'passport' ? 'selected' : ''}>Pasaporte</option>
                            <option value="nit" ${guest.document_type === 'nit' ? 'selected' : ''}>NIT</option>
                            <option value="other" ${guest.document_type === 'other' ? 'selected' : ''}>Otro</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Numero</label>
                        <input type="text" class="form-control" name="quick_guests[${index}][document_number]" value="${escapeHtml(guest.document_number || '')}">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Fecha nacimiento</label>
                        <input type="date" class="form-control" name="quick_guests[${index}][birth_date]" value="${escapeHtml(guest.birth_date || '')}">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Nacionalidad</label>
                        <select class="form-select ledger-country-select" name="quick_guests[${index}][nationality]" data-country-select data-pending-value="${escapeHtml(guest.nationality || '')}"></select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Pais</label>
                        <select class="form-select ledger-country-select" name="quick_guests[${index}][country]" data-country-select data-pending-value="${escapeHtml(guest.country || '')}"></select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Relacion</label>
                        <input type="text" class="form-control" name="quick_guests[${index}][relationship]" placeholder="Ej. esposa, hijo, colega" value="${escapeHtml(guest.relationship || '')}">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Nota</label>
                        <input type="text" class="form-control" name="quick_guests[${index}][notes]" value="${escapeHtml(guest.notes || '')}">
                    </div>
                </div>`;
            list.appendChild(wrapper);
            populateCountrySelects();
            refreshQuickGuestEmptyState();
        }

        function refreshQuickGuestEmptyState() {
            const list = document.getElementById('ledger-quick-guests-list');
            const hasRows = Boolean(list?.querySelector('[data-quick-guest-row]'));
            let empty = list?.querySelector('[data-quick-guest-empty]');
            if (!hasRows && !empty && list) {
                empty = document.createElement('div');
                empty.className = 'ledger-guest-empty';
                empty.dataset.quickGuestEmpty = '1';
                empty.textContent = 'No hay acompanantes agregados para esta nueva reserva.';
                list.appendChild(empty);
            }
            if (hasRows) {
                empty?.remove();
            }
        }

        function quickGuestFormRows() {
            return Array.from(document.querySelectorAll('#ledger-quick-guests-list [data-quick-guest-row]'))
                .map((row) => {
                    const item = {};
                    row.querySelectorAll('[name]').forEach((input) => {
                        const key = input.name.match(/\[([^\]]+)\]$/)?.[1];
                        if (key) item[key] = input.value;
                    });
                    return item;
                })
                .filter((guest) => String(guest.full_name || '').trim() !== '');
        }

        function quickGuestsFormData() {
            const formData = new FormData();
            pendingReservationGuests.forEach((guest, index) => {
                Object.entries(guest).forEach(([key, value]) => {
                    formData.append(`guests[${index}][${key}]`, value ?? '');
                });
            });
            formData.append('_method', 'PUT');
            return formData;
        }

        async function storeLedgerReservation(form) {
            if (!bookingSelection.start || !bookingSelection.end) {
                await notifyError('Selecciona fecha de entrada y salida en el calendario.');
                return;
            }

            if (bookingRangeHasConflict(bookingSelection.start, bookingSelection.end)) {
                await notifyError('La habitacion no esta libre en ese rango. Elige otras fechas.');
                return;
            }

            showLoadingAlert('Guardando reserva', 'Estamos registrando la reserva y el pago inicial si corresponde.');
            const formData = new FormData(form);
            formData.set('initial_payment_method', document.getElementById('ledger-reservation-payment-method').value);
            formData.set('preferred_payment_method', document.getElementById('ledger-reservation-payment-method').value);

            try {
                const response = await fetch(reservationStoreUrl, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': csrfToken,
                        'Accept': 'application/json',
                    },
                    body: formData,
                });
                const payload = await safeJsonResponse(response);

                if (!response.ok) {
                    closeLoadingAlert();
                    await notifyError(payload.message || firstValidationMessage(payload) || 'No se pudo guardar la reserva.');
                    return;
                }

                if (payload.guest_update_url && pendingReservationGuests.length) {
                    const guestsResponse = await fetch(payload.guest_update_url, {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': csrfToken,
                            'Accept': 'application/json',
                        },
                        body: quickGuestsFormData(),
                    });

                    if (!guestsResponse.ok) {
                        const guestsPayload = await safeJsonResponse(guestsResponse);
                        closeLoadingAlert();
                        await notifyError(guestsPayload.message || firstValidationMessage(guestsPayload) || 'La reserva se creo, pero no se pudieron guardar los acompanantes.');
                        return;
                    }
                }

                const createdRoomId = activeBookingRoom?.room_id || document.getElementById('ledger-reservation-room-id')?.value || null;
                newReservationModal.hide();
                await refreshLedgerFromServer(payload.message || 'Reserva creada correctamente.', false, {
                    focusRoomId: createdRoomId,
                    reopenReservations: true,
                });
            } catch (error) {
                closeLoadingAlert();
                await notifyError(error.message || 'No se pudo guardar la reserva.');
            }
        }

        function formatCurrency(amount, currency) {
            const symbol = currencySymbols[currency] || `${currency} `;
            return `${symbol}${Number(amount || 0).toFixed(2)}`;
        }

        function isoDate(date) {
            return `${date.getFullYear()}-${String(date.getMonth() + 1).padStart(2, '0')}-${String(date.getDate()).padStart(2, '0')}`;
        }

        function addDays(date, days) {
            const copy = new Date(date);
            copy.setDate(copy.getDate() + days);
            return copy;
        }

        function readRoomData(roomId) {
            return JSON.parse(document.getElementById(`ledger-room-data-${roomId}`).textContent);
        }

        function openStatusModal(room) {
            document.getElementById('ledgerStatusModalTitle').textContent = `Hab. ${room.room_number} - ${room.room_type}`;
            document.getElementById('ledger-status-url').value = room.status_update_url;
            document.getElementById('ledger-current-status').innerHTML = `<div>Estado actual: <strong>${escapeHtml(room.room_status_label)}</strong></div><small>Estado del cuaderno: ${escapeHtml(room.ledger_status_label)}</small>`;
            document.getElementById('ledger-room-status-notes').value = '';
            const select = document.getElementById('ledger-room-status-select');
            select.innerHTML = room.status_options.map((option) => `<option value="${option.value}" ${option.value === room.room_status ? 'selected' : ''}>${escapeHtml(option.label)}</option>`).join('');
            select.disabled = !room.can_change_room_status;
            statusModal.show();
        }

        function openReservationsModal(room) {
            const onlineRequests = room.reservations.filter((reservation) => reservation.is_online_request === true);
            document.getElementById('ledgerReservationsModalTitle').textContent = onlineRequests.length
                ? `Hab. ${room.room_number} - ${onlineRequests.length} solicitud(es) por revisar`
                : `Hab. ${room.room_number} - reservas`;
            const body = document.getElementById('ledger-reservations-body');
            activeReservationsRoom = room;
            activeCalendarMonth = initialCalendarMonth(room);

            if (!room.reservations.length) {
                body.innerHTML = `<div class="ledger-modal-empty">Esta habitacion no tiene reservas activas o futuras.</div>`;
            } else {
                refreshReservationCalendar();
            }
            reservationsModal.show();
            notifyToast('Reservas listadas para esta habitacion.', 'info');
        }

        function openCancellationReview(roomId, reservationId) {
            if (!roomId || !reservationId) {
                notifyError('No se pudo ubicar la solicitud de anulacion.');
                return;
            }

            const room = readRoomData(roomId);
            const reservation = (room.reservations || []).find((item) => Number(item.id) === Number(reservationId));

            if (!reservation) {
                notifyError('La reserva anulada ya no esta disponible en el libro de esta habitacion.');
                return;
            }

            document.getElementById('ledgerReservationsModalTitle').textContent = `Hab. ${room.room_number} - revisar anulacion`;
            activeReservationsRoom = room;
            const date = parseIsoDate(reservation.check_in_iso || reservation.calendar_check_in_iso);
            activeCalendarMonth = date ? new Date(date.getFullYear(), date.getMonth(), 1) : initialCalendarMonth(room);
            refreshReservationCalendar();
            reservationsModal.show();
            showReservationDetail(Number(reservation.id));
            notifyToast('Solicitud de anulacion abierta para revision.', 'info');
        }

        async function markCancellationReviewed(url, roomId) {
            if (!url) {
                await notifyError('No se encontro la ruta para marcar esta anulacion como revisada.');
                return;
            }

            let confirmed = true;
            if (window.Swal) {
                const result = await window.Swal.fire({
                    icon: 'question',
                    title: 'Marcar anulacion como revisada?',
                    text: 'Usa esto despues de comunicarte con el cliente por WhatsApp o telefono y revisar pagos/devolucion si corresponde.',
                    showCancelButton: true,
                    confirmButtonText: 'Si, ya fue revisada',
                    cancelButtonText: 'Volver',
                    confirmButtonColor: '#dc3545',
                });
                confirmed = result.isConfirmed;
            } else {
                confirmed = window.confirm('Marcar esta anulacion como revisada?');
            }

            if (!confirmed) {
                return;
            }

            showLoadingAlert('Guardando revision', 'Estamos cerrando la alerta y conservando el historial.');

            try {
                const response = await fetch(url, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': csrfToken,
                        'Accept': 'application/json',
                    },
                });
                const payload = await safeJsonResponse(response);

                if (!response.ok || payload.ok === false) {
                    closeLoadingAlert();
                    await notifyError(payload.message || firstValidationMessage(payload) || 'No se pudo marcar la anulacion como revisada.');
                    return;
                }

                await refreshLedgerFromServer(payload.message || 'Anulacion marcada como revisada.', false, {
                    focusRoomId: roomId,
                    reopenReservations: document.getElementById('ledgerReservationsModal')?.classList.contains('show'),
                });
            } catch (error) {
                closeLoadingAlert();
                await notifyError(error.message || 'No se pudo marcar la anulacion como revisada.');
            }
        }

        function openPaymentsModal(room) {
            document.getElementById('ledgerPaymentsModalTitle').textContent = `Hab. ${room.room_number} - pagos y comprobantes`;
            const body = document.getElementById('ledger-payments-body');
            if (!room.payments.length) {
                body.innerHTML = `<div class="ledger-modal-empty">Esta habitacion no tiene pagos o comprobantes registrados en sus reservas.</div>`;
            } else {
                body.innerHTML = `<div class="ledger-modal-list">${room.payments.map(renderPayment).join('')}</div>`;
            }
            paymentsModal.show();
            notifyToast('Pagos y comprobantes listados.', 'info');
        }

        function openActionModal(options) {
            document.getElementById('ledgerActionModalKicker').textContent = options.kicker;
            document.getElementById('ledgerActionModalTitle').textContent = options.title;
            document.getElementById('ledger-action-message').textContent = options.message;
            document.getElementById('ledger-action-help').textContent = options.help;
            document.getElementById('ledger-action-url').value = options.url;
            document.getElementById('ledger-action-field').value = options.field || 'notes';
            document.getElementById('ledger-action-force-checkout').value = options.forceCheckout ? '1' : '0';
            document.getElementById('ledger-action-notes').value = '';
            const submit = document.getElementById('ledger-action-submit');
            submit.textContent = options.submitText || 'Confirmar';
            submit.className = options.submitClass || 'btn btn-primary';
            actionModal.show();
        }

        function refreshReservationCalendar() {
            const body = document.getElementById('ledger-reservations-body');
            if (!activeReservationsRoom) return;
            body.innerHTML = renderReservationCalendar(activeReservationsRoom, activeCalendarMonth);
            const onlineRequests = activeReservationsRoom.reservations.filter((reservation) => reservation.is_online_request === true);
            const monthReservations = reservationsForMonth(activeReservationsRoom.reservations, activeCalendarMonth);
            const selectedReservation = onlineRequests[0] || monthReservations[0] || activeReservationsRoom.reservations[0] || null;
            if (selectedReservation) {
                showReservationDetail(selectedReservation.id);
            }
        }

        function renderReservationCalendar(room, visibleMonth) {
            const reservations = room.reservations
                .map((reservation) => ({
                    ...reservation,
                    startDate: parseIsoDate(reservation.calendar_check_in_iso || reservation.check_in_iso),
                    endDate: parseIsoDate(reservation.calendar_check_out_iso || reservation.check_out_iso),
                }))
                .filter((reservation) => reservation.startDate && reservation.endDate);

            if (!reservations.length) {
                return '';
            }

            const firstDate = new Date(Math.min(...reservations.map((reservation) => reservation.startDate.getTime())));
            const lastDate = new Date(Math.max(...reservations.map((reservation) => reservation.endDate.getTime())));
            const rangeLabel = `${formatCalendarDate(firstDate)} al ${formatCalendarDate(lastDate)}`;
            const monthReservations = reservationsForMonth(reservations, visibleMonth);
            const completedCount = monthReservations.filter((reservation) => reservation.status === 'checked_out').length;
            const inactiveCount = monthReservations.filter((reservation) => ['cancelled', 'expired', 'no_show'].includes(reservation.status)).length;
            const pendingCount = monthReservations.filter((reservation) => reservation.status === 'pending').length;
            const onlineRequests = room.reservations.filter((reservation) => reservation.is_online_request === true);

            return `<section class="ledger-calendar" aria-label="Calendario de reservas">
                ${renderOnlineRequestsPanel(onlineRequests)}
                <div class="ledger-calendar-toolbar">
                    <div>
                        <strong>Agenda de la habitacion ${escapeHtml(room.room_number)}</strong>
                        <span>Historial completo cargado: ${rangeLabel}</span>
                    </div>
                    <div class="ledger-calendar-nav">
                        <button type="button" class="btn btn-outline-secondary btn-sm" data-calendar-nav="-1"><i class="bi bi-chevron-left"></i> Mes anterior</button>
                        <div class="ledger-calendar-jump">
                            <select class="form-select form-select-sm" data-calendar-month aria-label="Seleccionar mes">${renderMonthOptions(visibleMonth)}</select>
                            <input class="form-control form-control-sm" type="number" inputmode="numeric" min="1900" max="2200" step="1" value="${visibleMonth.getFullYear()}" data-calendar-year aria-label="Escribir gestion">
                        </div>
                        <button type="button" class="btn btn-outline-primary btn-sm" data-calendar-today><i class="bi bi-calendar-event"></i> Hoy</button>
                        <button type="button" class="btn btn-primary btn-sm" data-calendar-nav="1">Mes siguiente <i class="bi bi-chevron-right"></i></button>
                    </div>
                    <div class="ledger-calendar-legend">
                        <span><b class="dot dot--amber"></b>Pendiente</span>
                        <span><b class="dot dot--green"></b>Confirmada</span>
                        <span><b class="dot dot--red"></b>Ocupada</span>
                        <span><b class="dot dot--dark"></b>Finalizada / anulada</span>
                    </div>
                </div>
                <div class="ledger-calendar-stats">
                    <div><span>Reservas del mes</span><strong>${monthReservations.length}</strong></div>
                    <div><span>Pendientes</span><strong>${pendingCount}</strong></div>
                    <div><span>Finalizadas</span><strong>${completedCount}</strong></div>
                    <div><span>Canceladas / vencidas</span><strong>${inactiveCount}</strong></div>
                </div>
                <div class="table-responsive">${renderCalendarMonth(visibleMonth, reservations)}</div>
                <div class="small text-muted fw-bold">Consejo: clic izquierdo sobre el solicitante para ver y resaltar sus fechas. Clic derecho sobre el solicitante para aprobar o rechazar la solicitud.</div>
                <div id="ledger-reservation-detail" class="ledger-reservation-detail"></div>
                <div class="ledger-modal-list">${monthReservations.length ? monthReservations.map(renderReservation).join('') : '<div class="ledger-modal-empty">No hay reservas para este mes. Usa los botones para revisar otros meses.</div>'}</div>
            </section>`;
        }

        function renderCalendarMonth(monthDate, reservations) {
            const monthStart = new Date(monthDate.getFullYear(), monthDate.getMonth(), 1);
            const monthEnd = new Date(monthDate.getFullYear(), monthDate.getMonth() + 1, 0);
            const gridStart = new Date(monthStart);
            gridStart.setDate(monthStart.getDate() - ((monthStart.getDay() + 6) % 7));
            const gridEnd = new Date(monthEnd);
            gridEnd.setDate(monthEnd.getDate() + (6 - ((monthEnd.getDay() + 6) % 7)));
            const days = [];
            const cursor = new Date(gridStart);

            while (cursor <= gridEnd) {
                const dateValue = isoDate(cursor);
                const dayReservations = reservations.filter((reservation) => isDateInReservation(cursor, reservation));
                const isCurrentMonth = cursor.getMonth() === monthStart.getMonth();
                const isToday = isSameDate(cursor, new Date());
                const isSelectedRequestDay = activeCustomerReservation
                    && dayReservations.some((reservation) => Number(reservation.id) === Number(activeCustomerReservation.id));
                const classes = [
                    'ledger-calendar-day',
                    isCurrentMonth ? '' : 'is-muted',
                    isToday ? 'is-today' : '',
                    dayReservations.length ? 'is-booked' : '',
                    isSelectedRequestDay ? 'is-selected-request' : '',
                ].filter(Boolean).join(' ');

                days.push(`<div class="${classes}" data-calendar-day="${dateValue}">
                    <span class="ledger-calendar-number">${cursor.getDate()}</span>
                    ${dayReservations.slice(0, 2).map((reservation) => renderCalendarBooking(reservation)).join('')}
                    ${dayReservations.length > 2 ? `<span class="ledger-calendar-booking">+${dayReservations.length - 2} mas</span>` : ''}
                </div>`);
                cursor.setDate(cursor.getDate() + 1);
            }

            return `<article class="ledger-calendar-month">
                <h6>${monthDate.toLocaleDateString('es-BO', { month: 'long', year: 'numeric' })}</h6>
                <div class="ledger-calendar-grid">
                    ${['Lun', 'Mar', 'Mie', 'Jue', 'Vie', 'Sab', 'Dom'].map((day) => `<div class="ledger-calendar-day-name">${day}</div>`).join('')}
                    ${days.join('')}
                </div>
            </article>`;
        }

        function applyCalendarJump() {
            if (!activeCalendarMonth) return;

            const month = Number(document.querySelector('[data-calendar-month]')?.value ?? activeCalendarMonth.getMonth());
            const rawYear = Number(document.querySelector('[data-calendar-year]')?.value ?? activeCalendarMonth.getFullYear());
            const year = Number.isInteger(rawYear) && rawYear >= 1900 && rawYear <= 2200 ? rawYear : activeCalendarMonth.getFullYear();
            activeCalendarMonth = new Date(year, month, 1);
            refreshReservationCalendar();
        }

        function renderCalendarBooking(reservation) {
            return `<button type="button" class="ledger-calendar-booking ledger-calendar-booking--${escapeHtml(reservation.status)}" data-calendar-reservation-id="${reservation.id}" title="${escapeHtml(reservation.code)} - ${escapeHtml(reservation.customer)}">
                ${escapeHtml(reservation.customer)} · ${escapeHtml(reservation.status_label)}
            </button>`;
        }

        function renderOnlineRequestsPanel(requests) {
            if (!requests.length) {
                return `<section class="ledger-request-panel ledger-request-panel--empty">
                    <div>
                        <span class="ledger-modal-kicker">Solicitudes por internet</span>
                        <h6>No hay clientes esperando aprobacion para esta habitacion.</h6>
                        <p>Abajo puedes revisar el calendario completo de la habitacion.</p>
                    </div>
                </section>`;
            }

            return `<section class="ledger-request-panel">
                <div class="ledger-request-panel__head">
                    <div>
                        <span class="ledger-modal-kicker">Primero atiende estas solicitudes</span>
                        <h6>Personas que quieren reservar esta habitacion</h6>
                        <p>Haz clic en un cliente para ver fechas, comprobante, choques y aprobar o rechazar.</p>
                    </div>
                    <strong>${requests.length} pendiente(s)</strong>
                </div>
                <div class="ledger-request-list">
                    ${requests.map((reservation) => `<div class="ledger-request-item">
                        <button type="button" class="ledger-request-card" data-calendar-reservation-id="${reservation.id}">
                            <span>Solicitud web</span>
                            <strong>${escapeHtml(reservation.customer)}</strong>
                            <small>${escapeHtml(reservation.check_in || '-')} al ${escapeHtml(reservation.check_out || '-')} - ${reservation.people || 0} persona(s)</small>
                            <em>${reservation.pending_payments_count || 0} comprobante(s) pendiente(s)</em>
                        </button>
                        ${whatsappLink(reservation) ? `<a class="ledger-whatsapp-link" href="${whatsappLink(reservation)}" target="_blank" rel="noopener" title="Escribir por WhatsApp a ${escapeHtml(reservation.customer)}"><i class="bi bi-whatsapp me-1"></i> WhatsApp</a>` : ''}
                    </div>`).join('')}
                </div>
            </section>`;
        }

        function whatsappLink(reservation) {
            const rawNumber = reservation?.whatsapp || reservation?.phone || '';
            const cleanNumber = String(rawNumber).replace(/[^\d+]/g, '');
            let normalized = cleanNumber.startsWith('+') ? cleanNumber.slice(1) : cleanNumber;

            if (!cleanNumber.startsWith('+') && /^\d{8}$/.test(normalized)) {
                normalized = `591${normalized}`;
            }

            if (!normalized || normalized.length < 7) {
                return '';
            }

            const message = encodeURIComponent(`Hola ${reservation.customer || ''}, le escribimos de recepcion sobre su solicitud de reserva ${reservation.code || ''}.`);
            return `https://wa.me/${normalized}?text=${message}`;
        }

        function highlightSelectedReservationInCalendar(reservation) {
            document.querySelectorAll('.ledger-request-card').forEach((card) => {
                card.classList.toggle('is-active', Number(card.dataset.calendarReservationId) === Number(reservation.id));
            });

            document.querySelectorAll('.ledger-calendar-day').forEach((day) => {
                day.classList.remove('is-selected-request');
            });

            const start = parseIsoDate(reservation.calendar_check_in_iso || reservation.check_in_iso);
            const end = parseIsoDate(reservation.calendar_check_out_iso || reservation.check_out_iso);

            if (!start || !end) return;

            document.querySelectorAll('[data-calendar-day]').forEach((day) => {
                const current = parseIsoDate(day.dataset.calendarDay);
                day.classList.toggle('is-selected-request', current && current >= stripTime(start) && current <= stripTime(end));
            });
        }

        async function openRequestDecisionMenu(reservationId) {
            if (!activeReservationsRoom) return;
            const reservation = activeReservationsRoom.reservations.find((item) => Number(item.id) === Number(reservationId));
            if (!reservation) return;

            const payment = pendingPaymentForReservation(reservation);
            const html = `
                <div class="text-start">
                    <p class="mb-2"><strong>${escapeHtml(reservation.customer)}</strong></p>
                    <p class="mb-2">Solicita ${escapeHtml(reservation.check_in || '-')} al ${escapeHtml(reservation.check_out || '-')} en habitacion ${escapeHtml(activeReservationsRoom.room_number || '-')}.</p>
                    <p class="mb-0 text-secondary">Elige una accion. Si apruebas, el sistema confirmara el comprobante y la reserva si cubre el anticipo.</p>
                </div>`;

            if (!window.Swal) {
                const approve = window.confirm('Aprobar comprobante y reserva? Cancelar = rechazar comprobante y cancelar solicitud.');
                if (approve) {
                    await approveRequestReservation(reservation, payment);
                } else {
                    await rejectRequestReservation(reservation, payment);
                }
                return;
            }

            const result = await window.Swal.fire({
                icon: 'question',
                title: 'Que haras con esta solicitud?',
                html,
                showDenyButton: true,
                showCancelButton: true,
                confirmButtonText: 'Aprobar comprobante y reserva',
                denyButtonText: 'Rechazar comprobante y cancelar solicitud',
                cancelButtonText: 'Cerrar',
                confirmButtonColor: '#16a34a',
                denyButtonColor: '#dc2626',
            });

            if (result.isConfirmed) {
                await approveRequestReservation(reservation, payment);
            } else if (result.isDenied) {
                await rejectRequestReservation(reservation, payment);
            }
        }

        function pendingPaymentForReservation(reservation) {
            const payments = reservation.payments?.length
                ? reservation.payments
                : activeReservationsRoom.payments.filter((payment) => payment.reservation_code === reservation.code);

            return payments.find((payment) => payment.status === 'pending') || null;
        }

        async function approveRequestReservation(reservation, payment) {
            if (!payment?.can_confirm || !payment.confirm_url) {
                await notifyError('Esta solicitud no tiene un comprobante pendiente que puedas aprobar.');
                return;
            }

            const formData = new FormData();
            await submitRequestDecision(payment.confirm_url, formData, 'Aprobando comprobante', 'Estamos confirmando el pago y actualizando la reserva.');
        }

        async function rejectRequestReservation(reservation, payment) {
            let reason = 'Solicitud rechazada desde recepcion.';

            if (window.Swal) {
                const result = await window.Swal.fire({
                    icon: 'warning',
                    title: 'Motivo del rechazo',
                    input: 'textarea',
                    inputPlaceholder: 'Ejemplo: comprobante no corresponde, fechas no disponibles, monto incorrecto...',
                    inputValue: reason,
                    showCancelButton: true,
                    confirmButtonText: 'Rechazar y cancelar',
                    cancelButtonText: 'Volver',
                    confirmButtonColor: '#dc2626',
                });

                if (!result.isConfirmed) return;
                reason = result.value || reason;
            }

            if (!reservation.can_cancel_reservation || !reservation.cancel_url) {
                await notifyError('Tu usuario no puede cancelar esta solicitud o la reserva ya no esta pendiente.');
                return;
            }

            showLoadingAlert('Rechazando solicitud', 'Estamos rechazando el comprobante y cancelando la solicitud.');

            try {
                if (payment?.can_reject && payment.reject_url) {
                    const rejectData = new FormData();
                    rejectData.append('reason', reason);
                    const rejectResponse = await fetch(payment.reject_url, {
                        method: 'POST',
                        headers: { 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' },
                        body: rejectData,
                    });
                    const rejectPayload = await safeJsonResponse(rejectResponse);

                    if (!rejectResponse.ok) {
                        closeLoadingAlert();
                        await notifyError(rejectPayload.message || firstValidationMessage(rejectPayload) || 'No se pudo rechazar el comprobante.');
                        return;
                    }
                }

                const cancelData = new FormData();
                cancelData.append('cancellation_reason', reason);
                await submitRequestDecision(reservation.cancel_url, cancelData, 'Cancelando solicitud', 'Estamos cancelando la solicitud de reserva.', false);
            } catch (error) {
                closeLoadingAlert();
                await notifyError(error.message || 'No se pudo rechazar la solicitud.');
            }
        }

        async function submitRequestDecision(url, formData, title, text, showLoader = true) {
            if (showLoader) {
                showLoadingAlert(title, text);
            }

            try {
                const response = await fetch(url, {
                    method: 'POST',
                    headers: { 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' },
                    body: formData,
                });
                const payload = await safeJsonResponse(response);

                if (!response.ok || payload.ok === false) {
                    closeLoadingAlert();
                    await notifyError(payload.message || firstValidationMessage(payload) || 'No se pudo completar la accion.');
                    return;
                }

                await refreshLedgerFromServer(payload.message || 'Solicitud actualizada correctamente.', false, {
                    focusRoomId: activeReservationsRoom?.room_id,
                    reopenReservations: true,
                });
            } catch (error) {
                closeLoadingAlert();
                await notifyError(error.message || 'No se pudo completar la accion.');
            }
        }

        function openCustomerOptions(reservationId) {
            if (!activeReservationsRoom) return;
            const reservation = activeReservationsRoom.reservations.find((item) => Number(item.id) === Number(reservationId));
            if (!reservation) return;

            activeCustomerReservation = reservation;
            activeCustomerSummary = null;
            showReservationDetail(reservationId);

            document.getElementById('ledgerCustomerOptionsModalTitle').textContent = reservation.customer || 'Cliente';
            document.getElementById('ledger-customer-options-help').textContent = `Reserva ${reservation.code} de la habitacion ${activeReservationsRoom.room_number}. Elige que deseas hacer solo con esta reserva.`;
            const editButton = document.getElementById('ledger-edit-customer-button');
            editButton.disabled = !reservation.can_update_customer;
            editButton.title = reservation.can_update_customer ? '' : 'Tu usuario no tiene permiso para editar clientes.';
            const checkinButton = document.getElementById('ledger-checkin-reservation-button');
            const checkoutButton = document.getElementById('ledger-checkout-reservation-button');
            const extendButton = document.getElementById('ledger-extend-reservation-button');
            checkinButton.disabled = !reservation.can_checkin;
            checkoutButton.disabled = !reservation.can_checkout;
            extendButton.disabled = !reservation.can_extend;
            checkinButton.title = reservation.can_checkin ? 'Registrar entrada de esta reserva.' : 'La entrada solo se habilita para reservas confirmadas.';
            checkoutButton.title = reservation.can_checkout ? 'Registrar salida de esta reserva, incluso si se retira antes.' : 'La salida solo se habilita cuando la reserva tiene entrada activa.';
            extendButton.title = reservation.can_extend ? 'Ampliar la fecha de salida de esta reserva.' : 'Solo se puede ampliar una reserva confirmada u ocupada.';
            customerOptionsModal.show();
        }

        function openExtendStayModal(reservation) {
            customerOptionsModal.hide();
            activeCustomerReservation = reservation;
            document.getElementById('ledgerExtendStayModalTitle').textContent = `Ampliar - ${reservation.customer}`;
            document.getElementById('ledger-extend-stay-url').value = reservation.extend_url || '';
            document.getElementById('ledger-extend-stay-summary').innerHTML = `
                <div>Reserva: <strong>${escapeHtml(reservation.code)}</strong></div>
                <small>Habitacion ${escapeHtml(activeReservationsRoom?.room_number || '-')} - salida actual ${escapeHtml(reservation.check_out || '-')} - ${Number(reservation.nights || 0)} noche(s)</small>`;

            const currentCheckout = parseIsoDate(reservation.check_out_iso);
            const minimumCheckout = currentCheckout ? isoDate(addDays(currentCheckout, 1)) : '';
            const input = document.getElementById('ledger-extend-new-check-out');
            input.min = minimumCheckout;
            input.value = minimumCheckout;
            document.getElementById('ledger-extend-stay-notes').value = '';
            updateExtendStayPreview();
            extendStayModal.show();
        }

        function handleReservationAction(button) {
            const action = button.dataset.reservationAction;
            const reservation = activeReservationsRoom?.reservations.find((item) => Number(item.id) === Number(button.dataset.reservationId || activeCustomerReservation?.id));

            if (action === 'edit-dates' && reservation) {
                openEditReservationDatesModal(reservation);
                return;
            }

            if (action === 'confirm') {
                openActionModal({
                    url: button.dataset.url,
                    kicker: 'Aprobar reserva',
                    title: 'Aprobar estadia solicitada',
                    message: 'El sistema aprobara la reserva si el anticipo minimo ya esta confirmado y no hay bloqueo.',
                    field: 'notes',
                    help: 'Puedes dejar una observacion interna si corresponde.',
                    submitText: 'Aprobar reserva',
                    submitClass: 'btn btn-success',
                });
                return;
            }

            if (action === 'cancel') {
                openActionModal({
                    url: button.dataset.url,
                    kicker: 'Rechazar solicitud',
                    title: 'Rechazar o cancelar reserva',
                    message: 'Esta accion cancelara la solicitud de reserva. Usa la observacion para dejar claro el motivo.',
                    field: 'cancellation_reason',
                    help: 'Ejemplo: fechas no disponibles, comprobante no corresponde, cliente pidio cancelar.',
                    submitText: 'Cancelar solicitud',
                    submitClass: 'btn btn-danger',
                });
            }
        }

        function openEditReservationDatesModal(reservation) {
            activeCustomerReservation = reservation;
            reservationsModal.hide();
            document.getElementById('ledgerEditReservationDatesModalTitle').textContent = `Editar fechas - ${reservation.customer}`;
            document.getElementById('ledger-edit-dates-url').value = reservation.dates_update_url || '';
            document.getElementById('ledger-edit-dates-summary').innerHTML = `
                <div>Reserva: <strong>${escapeHtml(reservation.code)}</strong></div>
                <small>Habitacion ${escapeHtml(activeReservationsRoom?.room_number || '-')} - actual ${escapeHtml(reservation.check_in || '-')} al ${escapeHtml(reservation.check_out || '-')}</small>`;
            document.getElementById('ledger-edit-check-in').value = reservation.check_in_iso || '';
            document.getElementById('ledger-edit-check-out').value = reservation.check_out_iso || '';
            document.getElementById('ledger-edit-dates-notes').value = '';
            updateEditDatesPreview();
            editReservationDatesModal.show();
        }

        function editDatesHasConflict(reservation, newCheckIn, newCheckOut) {
            if (!activeReservationsRoom || !reservation) return false;

            return activeReservationsRoom.reservations.some((item) => {
                if (Number(item.id) === Number(reservation.id)) return false;
                if (!['pending', 'confirmed', 'checked_in'].includes(item.status)) return false;
                const checkIn = parseIsoDate(item.check_in_iso);
                const checkOut = parseIsoDate(item.check_out_iso);
                return checkIn && checkOut && checkIn < newCheckOut && checkOut > newCheckIn;
            });
        }

        function updateEditDatesPreview() {
            if (!activeCustomerReservation) return;
            const preview = document.getElementById('ledger-edit-dates-preview');
            const checkIn = parseIsoDate(document.getElementById('ledger-edit-check-in').value);
            const checkOut = parseIsoDate(document.getElementById('ledger-edit-check-out').value);

            if (!checkIn || !checkOut || checkOut <= checkIn) {
                preview.className = 'ledger-booking-warning mt-3';
                preview.textContent = 'La salida debe ser posterior a la entrada.';
                return;
            }

            const hasConflict = editDatesHasConflict(activeCustomerReservation, checkIn, checkOut);
            const nights = Math.max((checkOut - checkIn) / 86400000, 1);
            preview.className = hasConflict ? 'ledger-booking-warning mt-3' : 'ledger-booking-ok mt-3';
            preview.textContent = hasConflict
                ? 'Estas fechas chocan con otra reserva de la misma habitacion.'
                : `Fechas disponibles para esta habitacion. Total aproximado: ${nights} noche(s).`;
        }

        async function submitReservationDateEdit(form) {
            if (!activeCustomerReservation) return;
            const checkIn = parseIsoDate(document.getElementById('ledger-edit-check-in').value);
            const checkOut = parseIsoDate(document.getElementById('ledger-edit-check-out').value);

            if (!checkIn || !checkOut || checkOut <= checkIn || editDatesHasConflict(activeCustomerReservation, checkIn, checkOut)) {
                await notifyError('No se pueden guardar esas fechas porque no son validas o chocan con otra reserva.');
                return;
            }

            const formData = new FormData(form);
            formData.append('_method', 'PUT');
            showLoadingAlert('Guardando fechas', 'Estamos actualizando el hospedaje solicitado.');

            try {
                const response = await fetch(document.getElementById('ledger-edit-dates-url').value, {
                    method: 'POST',
                    headers: { 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' },
                    body: formData,
                });
                const payload = await safeJsonResponse(response);

                if (!response.ok || payload.ok === false) {
                    closeLoadingAlert();
                    await notifyError(payload.message || firstValidationMessage(payload) || 'No se pudieron actualizar las fechas.');
                    return;
                }

                editReservationDatesModal.hide();
                await refreshLedgerFromServer(payload.message || 'Fechas actualizadas correctamente.', false, {
                    focusRoomId: activeReservationsRoom?.room_id,
                    reopenReservations: true,
                });
            } catch (error) {
                closeLoadingAlert();
                await notifyError(error.message || 'No se pudieron actualizar las fechas.');
            }
        }

        function updateExtendStayPreview() {
            if (!activeCustomerReservation) return;
            const preview = document.getElementById('ledger-extend-stay-preview');
            const currentCheckout = parseIsoDate(activeCustomerReservation.check_out_iso);
            const newCheckout = parseIsoDate(document.getElementById('ledger-extend-new-check-out').value);

            if (!currentCheckout || !newCheckout || newCheckout <= currentCheckout) {
                preview.className = 'ledger-booking-warning mt-3';
                preview.textContent = 'La nueva salida debe ser posterior a la salida actual.';
                return;
            }

            const conflict = extensionHasConflict(activeCustomerReservation, newCheckout);
            const extraNights = Math.max((newCheckout - currentCheckout) / 86400000, 1);
            const totalNights = Number(activeCustomerReservation.nights || 0) + extraNights;

            preview.className = conflict ? 'ledger-booking-warning mt-3' : 'ledger-booking-ok mt-3';
            preview.textContent = conflict
                ? 'Hay otra reserva en los dias adicionales. Elige otra fecha.'
                : `Se agregaran ${extraNights} noche(s). Total aproximado: ${totalNights} noche(s). El saldo se recalculara al guardar.`;
        }

        function extensionHasConflict(reservation, newCheckout) {
            if (!activeReservationsRoom) return false;
            const currentCheckout = parseIsoDate(reservation.check_out_iso);

            return activeReservationsRoom.reservations.some((item) => {
                if (Number(item.id) === Number(reservation.id)) return false;
                if (!['pending', 'confirmed', 'checked_in'].includes(item.status)) return false;
                const checkIn = parseIsoDate(item.check_in_iso);
                const checkOut = parseIsoDate(item.check_out_iso);
                return checkIn && checkOut && checkIn < newCheckout && checkOut > currentCheckout;
            });
        }

        async function submitExtendStay(form) {
            if (!activeCustomerReservation) return;
            const newCheckout = parseIsoDate(document.getElementById('ledger-extend-new-check-out').value);

            if (!newCheckout || extensionHasConflict(activeCustomerReservation, newCheckout)) {
                await notifyError('No se puede ampliar porque la fecha no es valida o cruza con otra reserva.');
                return;
            }

            const formData = new FormData(form);
            showLoadingAlert('Guardando ampliacion', 'Estamos actualizando fechas, noches y saldo de la reserva.');

            try {
                const response = await fetch(document.getElementById('ledger-extend-stay-url').value, {
                    method: 'POST',
                    headers: { 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' },
                    body: formData,
                });
                const payload = await safeJsonResponse(response);

                if (!response.ok || payload.ok === false) {
                    closeLoadingAlert();
                    await notifyError(payload.message || firstValidationMessage(payload) || 'No se pudo ampliar el hospedaje.');
                    return;
                }

                extendStayModal.hide();
                await refreshLedgerFromServer(payload.message || 'Hospedaje ampliado correctamente.', false, {
                    focusRoomId: activeReservationsRoom?.room_id,
                    reopenReservations: true,
                });
            } catch (error) {
                closeLoadingAlert();
                await notifyError(error.message || 'No se pudo ampliar el hospedaje.');
            }
        }

        function openReservationMovementAction(reservation, action) {
            const isCheckout = action === 'checkout';
            const today = stripTime(new Date());
            const plannedCheckout = parseIsoDate(reservation.check_out_iso);
            const isEarlyCheckout = isCheckout && plannedCheckout && today < stripTime(plannedCheckout);
            const hasBalance = Number(reservation.balance || 0) > 0;

            customerOptionsModal.hide();
            openActionModal({
                url: isCheckout ? reservation.checkout_url : reservation.checkin_url,
                kicker: isCheckout ? 'Salida de huesped' : 'Entrada de huesped',
                title: `${isCheckout ? 'Registrar salida' : 'Registrar entrada'} - ${reservation.customer}`,
                message: isCheckout
                    ? `${isEarlyCheckout ? 'El cliente se esta retirando antes de la fecha prevista. ' : ''}${hasBalance ? 'La reserva tiene saldo pendiente. ' : ''}Confirma si deseas registrar la salida ahora.`
                    : 'Confirma la entrada para marcar la habitacion como ocupada.',
                field: 'notes',
                help: isCheckout
                    ? 'Puedes anotar: salida anticipada, motivo del retiro, saldo pendiente, devolucion o cualquier observacion.'
                    : 'Puedes anotar: hora de llegada, documentos revisados, entrega de llave u observaciones.',
                forceCheckout: isCheckout && (hasBalance || isEarlyCheckout),
                submitText: isCheckout ? 'Registrar salida' : 'Registrar entrada',
                submitClass: isCheckout ? 'btn btn-primary' : 'btn btn-success',
            });
        }

        async function customerSummaryFor(reservation) {
            if (activeCustomerSummary?.customer?.id === reservation.customer_id) {
                return activeCustomerSummary;
            }

            if (!reservation.customer_summary_url) {
                throw new Error('Esta reserva no tiene cliente asociado.');
            }

            const response = await fetch(reservation.customer_summary_url, {
                headers: { 'Accept': 'application/json' },
            });

            if (!response.ok) {
                const payload = await safeJsonResponse(response);
                throw new Error(payload.message || 'No se pudo cargar la informacion del cliente.');
            }

            activeCustomerSummary = await response.json();
            return activeCustomerSummary;
        }

        async function openCustomerEditModal(reservation) {
            try {
                const payload = await customerSummaryFor(reservation);
                const customer = payload.customer;

                if (!customer.can_update) {
                    await notifyError('Tu usuario no tiene permiso para editar clientes.');
                    return;
                }

                document.getElementById('ledgerCustomerEditModalTitle').textContent = customer.full_name || 'Datos del cliente';
                document.getElementById('ledger-customer-update-url').value = customer.update_url;
                await ensureCountriesLoaded();
                setCustomerField('full-name', customer.full_name);
                setCustomerField('document-type', customer.document_type);
                setCustomerField('document-number', customer.document_number);
                setCustomerField('phone', customer.phone);
                setCustomerField('whatsapp', customer.whatsapp);
                setCustomerField('email', customer.email);
                setCustomerField('nationality', customer.nationality);
                setCustomerField('country', customer.country);
                setCustomerField('city', customer.city);
                setCustomerField('birth-date', customer.birth_date);
                setCustomerField('address', customer.address);
                setCustomerField('company-name', customer.company_name);
                setCustomerField('tax-number', customer.tax_number);
                setCustomerField('notes', customer.notes);
                setCustomerChecked('is-foreign', customer.is_foreign);
                setCustomerChecked('is-company', customer.is_company);
                setCustomerChecked('is-active', customer.is_active);
                renderGuestRows(reservation.guests || []);
                customerOptionsModal.hide();
                customerEditModal.show();
            } catch (error) {
                await notifyError(error.message || 'No se pudo abrir la edicion del cliente.');
            }
        }

        async function openCustomerPaymentsModal(reservation) {
            try {
                const payload = await customerSummaryFor(reservation);
                const customer = payload.customer;
                document.getElementById('ledgerCustomerPaymentsModalTitle').textContent = `Pagos de ${reservation.code} - Hab. ${activeReservationsRoom?.room_number || '-'}`;
                document.getElementById('ledger-customer-payments-body').innerHTML = renderReservationPaymentPanel(payload, reservation);
                updateCustomerPaymentPreview();
                customerOptionsModal.hide();
                customerPaymentsModal.show();
                notifyToast('Pagos del cliente listados.', 'info');
            } catch (error) {
                await notifyError(error.message || 'No se pudieron cargar los pagos del cliente.');
            }
        }

        async function submitCustomerEdit(form) {
            showLoadingAlert('Guardando cliente', 'Estamos actualizando los datos del cliente y sus acompanantes.');
            const formData = new FormData(form);
            formData.set('_method', 'PUT');
            ['is_foreign', 'is_company', 'is_active'].forEach((field) => {
                formData.set(field, form.querySelector(`[name="${field}"]`)?.checked ? '1' : '0');
            });

            try {
                const response = await fetch(document.getElementById('ledger-customer-update-url').value, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': csrfToken,
                        'Accept': 'application/json',
                    },
                    body: formData,
                });

                if (!response.ok) {
                    const payload = await safeJsonResponse(response);
                    closeLoadingAlert();
                    await notifyError(payload.message || firstValidationMessage(payload) || 'No se pudo actualizar el cliente.');
                    return;
                }

                if (activeCustomerReservation?.guest_update_url) {
                    const guestsResponse = await fetch(activeCustomerReservation.guest_update_url, {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': csrfToken,
                            'Accept': 'application/json',
                        },
                        body: guestFormData(),
                    });

                    if (!guestsResponse.ok) {
                        const guestsPayload = await safeJsonResponse(guestsResponse);
                        closeLoadingAlert();
                        await notifyError(guestsPayload.message || firstValidationMessage(guestsPayload) || 'El cliente se guardo, pero no se pudieron guardar los acompanantes.');
                        return;
                    }
                }

                const payload = await response.json();
                customerEditModal.hide();
                await refreshLedgerFromServer(payload.message || 'Cliente actualizado correctamente.', false);
            } catch (error) {
                closeLoadingAlert();
                await notifyError(error.message || 'No se pudo actualizar el cliente.');
            }
        }

        function renderReservationPaymentPanel(payload, reservationContext = activeCustomerReservation) {
            const reservation = (payload.reservations || []).find((item) => Number(item.id) === Number(reservationContext?.id)) || reservationContext;
            const payments = (payload.payments || []).filter((payment) => Number(payment.reservation_id) === Number(reservation?.id) || payment.reservation_code === reservation?.code);
            const confirmedPayments = payments.filter((payment) => payment.status === 'confirmed');
            const pendingPayments = payments.filter((payment) => payment.status === 'pending');
            const displayCurrency = reservation?.display_currency || reservation?.locked_payment_currency || 'BOB';
            const total = Number(reservation?.display_total ?? reservation?.total ?? 0);
            const paid = Number(reservation?.display_paid ?? reservation?.paid ?? 0);
            const balance = Number(reservation?.display_balance ?? reservation?.balance ?? 0);
            const progress = total > 0 ? Math.min(Math.max((paid / total) * 100, 0), 100) : 0;

            return `<section class="ledger-payment-shell">
                <div class="ledger-payment-overview">
                    <div>
                        <span>Reserva y habitacion</span>
                        <strong>${escapeHtml(reservation?.code || '-')}</strong>
                        <small>Hab. ${escapeHtml(activeReservationsRoom?.room_number || reservation?.room || '-')} - ${escapeHtml(reservation?.room_type || 'Sin tipo')}</small>
                        <small>${escapeHtml(reservation?.check_in || '-')} al ${escapeHtml(reservation?.check_out || '-')} - ${escapeHtml(reservation?.status_label || 'Sin estado')}</small>
                    </div>
                    <div><span>Total</span><strong>${money(total, displayCurrency)}</strong><small>Monto de la reserva</small></div>
                    <div><span>Pagado</span><strong>${money(paid, displayCurrency)}</strong><small>${confirmedPayments.length} confirmado(s)</small></div>
                    <div><span>Saldo</span><strong>${money(balance, displayCurrency)}</strong><small>${pendingPayments.length} pendiente(s)</small><div class="ledger-payment-progress"><b style="width:${progress}%"></b></div></div>
                </div>
                ${renderReservationPaymentForm(reservation)}
                <div class="ledger-payment-history-title">
                    <h6>Historial de pagos de esta reserva</h6>
                    <span class="ledger-payment-badge">${payments.length} registrado(s)</span>
                </div>
                <div class="ledger-customer-payment-list">
                    ${payments.length ? payments.map(renderReservationPaymentCard).join('') : '<div class="ledger-modal-empty">Esta reserva todavia no tiene pagos registrados.</div>'}
                </div>
            </section>`;
        }

        function renderReservationPaymentCard(payment) {
            const badge = payment.status === 'confirmed' ? 'text-bg-success' : payment.status === 'pending' ? 'text-bg-warning' : payment.status === 'rejected' ? 'text-bg-danger' : 'text-bg-secondary';

            return `<article class="ledger-customer-payment-card">
                <div class="ledger-customer-payment-card__top">
                    <div>
                        <strong>${escapeHtml(payment.code)} - ${money(payment.amount, payment.currency)}</strong>
                        <small>${escapeHtml(payment.method || '-')} - ${escapeHtml(payment.payment_date || '-')}</small>
                    </div>
                    <span class="badge ${badge}">${escapeHtml(payment.status_label)}</span>
                </div>
                <small>Referencia: ${escapeHtml(payment.reference || 'Sin referencia')}</small>
                ${payment.notes ? `<p class="mb-0 mt-2 text-secondary">${escapeHtml(payment.notes)}</p>` : ''}
                ${payment.receipt_url ? `<a class="btn btn-outline-secondary btn-sm rounded-pill mt-2" href="${payment.receipt_url}" target="_blank" rel="noopener">Ver comprobante</a>` : ''}
            </article>`;
        }

        function renderReservationPaymentForm(reservation) {
            if (!canCreatePayment) {
                return `<div class="ledger-modal-card">
                    <span class="ledger-modal-kicker">Nuevo pago</span>
                    <strong>Tu usuario no tiene permiso para registrar pagos</strong>
                    <small>Puedes revisar el historial, pero administracion debe habilitarte el permiso de pagos.</small>
                </div>`;
            }

            const displayCurrency = reservation?.display_currency || reservation?.locked_payment_currency || 'BOB';
            const displayBalance = Number(reservation?.display_balance ?? reservation?.balance ?? 0);

            if (!reservation || displayBalance <= 0 || ['cancelled', 'expired'].includes(reservation.status)) {
                return `<div class="ledger-modal-card">
                    <span class="ledger-modal-kicker">Nuevo pago</span>
                    <strong>Esta reserva no tiene saldo pendiente</strong>
                    <small>No es necesario registrar otro pago para esta habitacion/reserva.</small>
                </div>`;
            }

            const lockedCurrency = reservation.locked_payment_currency || null;
            const currencyOptions = Object.entries(supportedCurrencies).map(([code, label]) => `<option value="${escapeHtml(code)}" ${code === displayCurrency ? 'selected' : ''}>${escapeHtml(code)} - ${escapeHtml(label)}</option>`).join('');
            const methodOptions = Object.entries(paymentMethods).map(([code, label]) => `<option value="${escapeHtml(code)}">${escapeHtml(label)}</option>`).join('');
            const methodButtons = Object.entries(paymentMethods).map(([code, label], index) => `<button type="button" class="${index === 0 ? 'is-active' : ''}" data-payment-method-choice="${escapeHtml(code)}">${escapeHtml(label)}</button>`).join('');

            return `<form class="ledger-payment-form" id="ledger-customer-payment-form" enctype="multipart/form-data" data-balance="${displayBalance}" data-display-currency="${escapeHtml(displayCurrency)}" data-reservation-code="${escapeHtml(reservation.code)}">
                <input type="hidden" name="reservation_id" value="${reservation.id}">
                <div class="ledger-payment-form__head">
                    <div>
                        <span class="ledger-modal-kicker">Anadir nuevo pago</span>
                        <strong>Registrar pago para ${escapeHtml(reservation.code)}</strong>
                        <small>Este pago pertenece solo a la habitacion ${escapeHtml(activeReservationsRoom?.room_number || reservation.room || '-')}.</small>
                    </div>
                    <button type="submit" class="btn btn-success rounded-pill fw-bold">
                        <i class="bi bi-cash-coin me-1" aria-hidden="true"></i> Guardar pago
                    </button>
                </div>
                <div class="row g-3">
                    <div class="col-12">
                        <div class="ledger-payment-preview" id="ledger-customer-payment-preview">
                            <span>Vista previa</span>
                            Pago para ${escapeHtml(reservation.code)} por ${formatCurrency(0, displayCurrency)}
                        </div>
                    </div>
                    <div class="col-12">
                        <label class="form-label fw-bold">Metodo de pago</label>
                        <div class="ledger-payment-methods">${methodButtons}</div>
                        <select class="form-select d-none" id="ledger-customer-payment-method" name="payment_method" required>${methodOptions}</select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label fw-bold" for="ledger-customer-payment-currency">Moneda</label>
                        <select class="form-select" id="ledger-customer-payment-currency" name="currency" required ${lockedCurrency ? 'disabled' : ''}>${currencyOptions}</select>
                        ${lockedCurrency ? `<input type="hidden" name="currency" value="${escapeHtml(displayCurrency)}"><div class="form-text fw-bold text-success">Moneda fijada por pagos anteriores: ${escapeHtml(displayCurrency)}.</div>` : '<div class="form-text fw-bold text-secondary">La primera moneda registrada fijara la reserva.</div>'}
                    </div>
                    <div class="col-md-3">
                        <label class="form-label fw-bold" for="ledger-customer-payment-amount">Monto que entrega</label>
                        <input type="number" class="form-control" id="ledger-customer-payment-amount" name="amount" min="0.01" step="0.01" required placeholder="0.00">
                    </div>
                    <div class="col-md-3 d-grid align-items-end">
                        <button type="button" class="btn btn-outline-success fw-bold" data-payment-fill-balance>Usar saldo pendiente</button>
                    </div>
                    <div class="col-md-3 d-grid align-items-end">
                        <button type="button" class="btn btn-outline-secondary fw-bold" data-payment-clear>Limpiar</button>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-bold" for="ledger-customer-payment-date">Fecha de pago</label>
                        <input type="date" class="form-control" id="ledger-customer-payment-date" name="payment_date" value="${isoDate(new Date())}">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-bold" for="ledger-customer-payment-reference">Referencia</label>
                        <input type="text" class="form-control" id="ledger-customer-payment-reference" name="reference_number" placeholder="Recibo, transaccion, voucher, banco...">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-bold" for="ledger-customer-payment-receipt">Comprobante</label>
                        <input type="file" class="form-control" id="ledger-customer-payment-receipt" name="receipt_image" accept=".jpg,.jpeg,.png,.webp,.pdf">
                        <div class="form-text">JPG, PNG, WEBP o PDF. Maximo 10 MB.</div>
                    </div>
                    <div class="col-12">
                        <label class="form-label fw-bold" for="ledger-customer-payment-notes">Notas internas</label>
                        <textarea class="form-control" id="ledger-customer-payment-notes" name="notes" rows="2" placeholder="Ejemplo: pago recibido en recepcion, validado con banco, etc."></textarea>
                    </div>
                </div>
            </form>`;
        }

        function selectCustomerPaymentMethod(method) {
            const select = document.getElementById('ledger-customer-payment-method');
            if (!select) return;
            select.value = method;
            markActiveCustomerPaymentMethod(method);
            updateCustomerPaymentPreview();
        }

        function markActiveCustomerPaymentMethod(method) {
            document.querySelectorAll('[data-payment-method-choice]').forEach((button) => {
                button.classList.toggle('is-active', button.dataset.paymentMethodChoice === method);
            });
        }

        function fillCustomerPaymentBalance() {
            const form = document.getElementById('ledger-customer-payment-form');
            const amount = document.getElementById('ledger-customer-payment-amount');
            const currency = document.getElementById('ledger-customer-payment-currency');
            if (!form || !amount || !currency) return;

            const displayCurrency = form.dataset.displayCurrency || 'BOB';

            if (currency.value !== displayCurrency) {
                notifyToast(`El saldo pendiente se llena automaticamente en ${displayCurrency}. Si eliges otra moneda, escribe el monto recibido.`, 'info');
                return;
            }

            amount.value = Number(form.dataset.balance || 0).toFixed(2);
            updateCustomerPaymentPreview();
        }

        function clearCustomerPaymentForm() {
            const form = document.getElementById('ledger-customer-payment-form');
            if (!form) return;
            form.querySelector('[name="amount"]').value = '';
            form.querySelector('[name="reference_number"]').value = '';
            form.querySelector('[name="notes"]').value = '';
            updateCustomerPaymentPreview();
        }

        function updateCustomerPaymentPreview() {
            const form = document.getElementById('ledger-customer-payment-form');
            const preview = document.getElementById('ledger-customer-payment-preview');
            if (!form || !preview) return;
            const amount = Number(document.getElementById('ledger-customer-payment-amount')?.value || 0);
            const currency = document.getElementById('ledger-customer-payment-currency')?.value || 'BOB';
            const method = paymentMethods[document.getElementById('ledger-customer-payment-method')?.value || 'cash'] || 'Efectivo';
            preview.innerHTML = `<span>Vista previa</span>Pago para ${escapeHtml(form.dataset.reservationCode || 'la reserva')} por ${formatCurrency(amount, currency)} via ${escapeHtml(method)}`;
        }

        function renderCustomerPayments(payload, reservationContext = activeCustomerReservation) {
            const reservations = (payload.reservations || []).filter((reservation) => Number(reservation.id) === Number(reservationContext?.id));
            const selectedReservation = reservations[0] || reservationContext;
            const payments = (payload.payments || []).filter((payment) => {
                if (payment.reservation_id) {
                    return Number(payment.reservation_id) === Number(selectedReservation?.id);
                }

                return payment.reservation_code === selectedReservation?.code;
            });
            const confirmedPayments = payments.filter((payment) => payment.status === 'confirmed');
            const pendingPayments = payments.filter((payment) => payment.status === 'pending');
            const payableReservations = selectedReservation && Number(selectedReservation.balance || 0) > 0 && !['cancelled', 'expired'].includes(selectedReservation.status)
                ? [selectedReservation]
                : [];

            return `<div class="ledger-calendar-stats mb-3">
                    <div><span>Reserva</span><strong>${escapeHtml(selectedReservation?.code || '-')}</strong></div>
                    <div><span>Habitacion</span><strong>${escapeHtml(activeReservationsRoom?.room_number || selectedReservation?.room || '-')}</strong></div>
                    <div><span>Pagos de esta reserva</span><strong>${payments.length}</strong></div>
                    <div><span>Pendientes</span><strong>${pendingPayments.length}</strong></div>
                </div>
                ${renderCustomerPaymentForm(payableReservations)}
                <div class="ledger-customer-payment-list">
                    ${payments.length ? payments.map((payment) => `<article class="ledger-customer-payment-card">
                        <div class="ledger-customer-payment-card__top">
                            <div>
                                <strong>${escapeHtml(payment.code)} · ${money(payment.amount, payment.currency)}</strong>
                                <small>Reserva ${escapeHtml(payment.reservation_code || '-')} · ${escapeHtml(payment.method || '-')} · ${escapeHtml(payment.payment_date || '-')}</small>
                            </div>
                            <span class="badge ${payment.status === 'confirmed' ? 'text-bg-success' : payment.status === 'pending' ? 'text-bg-warning' : payment.status === 'rejected' ? 'text-bg-danger' : 'text-bg-secondary'}">${escapeHtml(payment.status_label)}</span>
                        </div>
                        <small>Referencia: ${escapeHtml(payment.reference || 'Sin referencia')}</small>
                        ${payment.notes ? `<p class="mb-0 mt-2 text-secondary">${escapeHtml(payment.notes)}</p>` : ''}
                        ${payment.receipt_url ? `<a class="btn btn-outline-secondary btn-sm rounded-pill mt-2" href="${payment.receipt_url}" target="_blank" rel="noopener">Ver comprobante</a>` : ''}
                    </article>`).join('') : '<div class="ledger-modal-empty">Este cliente no tiene pagos registrados.</div>'}
                </div>
                <h6 class="mt-4 fw-bold">Reserva seleccionada</h6>
                <div class="ledger-modal-list">${reservations.length ? reservations.map((reservation) => `<article class="ledger-modal-card">
                    <div class="ledger-modal-card__top">
                        <div><strong>${escapeHtml(reservation.code)}</strong><small>Hab. ${escapeHtml(reservation.room || '-')} · ${escapeHtml(reservation.room_type || '-')}</small></div>
                        <span class="badge text-bg-primary">${escapeHtml(reservation.status_label)}</span>
                    </div>
                    <div class="row g-2 mt-2">
                        <div class="col-md-3"><small>Desde</small><strong>${escapeHtml(reservation.check_in || '-')}</strong></div>
                        <div class="col-md-3"><small>Hasta</small><strong>${escapeHtml(reservation.check_out || '-')}</strong></div>
                        <div class="col-md-2"><small>Total</small><strong>${money(reservation.total, 'BOB')}</strong></div>
                        <div class="col-md-2"><small>Pagado</small><strong>${money(reservation.paid, 'BOB')}</strong></div>
                        <div class="col-md-2"><small>Saldo</small><strong>${money(reservation.balance, 'BOB')}</strong></div>
                    </div>
                </article>`).join('') : '<div class="ledger-modal-empty">Este cliente no tiene reservas relacionadas.</div>'}</div>`;
        }

        function renderCustomerPaymentForm(reservations) {
            if (!canCreatePayment) {
                return `<div class="ledger-modal-card mb-3">
                    <div class="ledger-modal-card__top">
                        <div>
                            <span class="ledger-modal-kicker">Nuevo pago</span>
                            <strong>Tu usuario no tiene permiso para registrar pagos</strong>
                            <small>Puedes revisar el historial, pero administracion debe habilitarte el permiso de pagos.</small>
                        </div>
                    </div>
                </div>`;
            }

            if (!reservations.length) {
                return `<div class="ledger-modal-card mb-3">
                    <div class="ledger-modal-card__top">
                        <div>
                            <span class="ledger-modal-kicker">Nuevo pago</span>
                            <strong>No hay reservas con saldo pendiente</strong>
                            <small>Cuando el cliente tenga una reserva con saldo, aqui podras registrar su pago.</small>
                        </div>
                    </div>
                </div>`;
            }

            const defaultReservationId = activeCustomerReservation?.id;
            const reservationOptions = reservations.map((reservation) => `<option value="${reservation.id}" data-balance="${reservation.balance}" data-code="${escapeHtml(reservation.code)}" ${Number(reservation.id) === Number(defaultReservationId) ? 'selected' : ''}>
                ${escapeHtml(reservation.code)} - Hab. ${escapeHtml(reservation.room || '-')} - saldo ${money(reservation.balance, 'BOB')}
            </option>`).join('');
            const currencyOptions = Object.entries(supportedCurrencies).map(([code, label]) => `<option value="${escapeHtml(code)}">${escapeHtml(code)} - ${escapeHtml(label)}</option>`).join('');
            const methodOptions = Object.entries(paymentMethods).map(([code, label]) => `<option value="${escapeHtml(code)}">${escapeHtml(label)}</option>`).join('');

            return `<form class="ledger-modal-card mb-3" id="ledger-customer-payment-form" enctype="multipart/form-data">
                <div class="ledger-modal-card__top">
                    <div>
                        <span class="ledger-modal-kicker">Anadir nuevo pago</span>
                        <strong>Registra el pago desde la reserva del cliente</strong>
                        <small>Selecciona la reserva correcta, registra la moneda real y guarda. El sistema actualizara saldo y caja.</small>
                    </div>
                    <button type="submit" class="btn btn-success rounded-pill fw-bold">
                        <i class="bi bi-cash-coin me-1" aria-hidden="true"></i> Guardar pago
                    </button>
                </div>
                <div class="row g-3 mt-2">
                    <div class="col-md-12">
                        <label class="form-label fw-bold" for="ledger-customer-payment-reservation">Reserva a la que pertenece el pago</label>
                        <select class="form-select" id="ledger-customer-payment-reservation" name="reservation_id" required>
                            ${reservationOptions}
                        </select>
                        <div class="form-text fw-bold text-secondary" id="ledger-customer-payment-hint">Selecciona una reserva con saldo pendiente.</div>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label fw-bold" for="ledger-customer-payment-currency">Moneda</label>
                        <select class="form-select" id="ledger-customer-payment-currency" name="currency" required>${currencyOptions}</select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label fw-bold" for="ledger-customer-payment-amount">Monto que entrega</label>
                        <input type="number" class="form-control" id="ledger-customer-payment-amount" name="amount" min="0.01" step="0.01" required placeholder="0.00">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label fw-bold" for="ledger-customer-payment-method">Metodo</label>
                        <select class="form-select" id="ledger-customer-payment-method" name="payment_method" required>${methodOptions}</select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label fw-bold" for="ledger-customer-payment-date">Fecha de pago</label>
                        <input type="date" class="form-control" id="ledger-customer-payment-date" name="payment_date" value="${isoDate(new Date())}">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-bold" for="ledger-customer-payment-reference">Referencia</label>
                        <input type="text" class="form-control" id="ledger-customer-payment-reference" name="reference_number" placeholder="Recibo, transaccion, voucher, banco...">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-bold" for="ledger-customer-payment-receipt">Comprobante</label>
                        <input type="file" class="form-control" id="ledger-customer-payment-receipt" name="receipt_image" accept=".jpg,.jpeg,.png,.webp,.pdf">
                        <div class="form-text">JPG, PNG, WEBP o PDF. Maximo 10 MB.</div>
                    </div>
                    <div class="col-12">
                        <label class="form-label fw-bold" for="ledger-customer-payment-notes">Notas internas</label>
                        <textarea class="form-control" id="ledger-customer-payment-notes" name="notes" rows="2" placeholder="Ejemplo: pago recibido en recepcion, validado con banco, etc."></textarea>
                    </div>
                </div>
            </form>`;
        }

        function updateCustomerPaymentHint() {
            const select = document.getElementById('ledger-customer-payment-reservation');
            const hint = document.getElementById('ledger-customer-payment-hint');
            if (!select || !hint) return;
            const selected = select.selectedOptions[0];
            const balance = Number(selected?.dataset.balance || 0);
            hint.textContent = `Saldo pendiente de ${selected?.dataset.code || 'la reserva'}: ${money(balance, 'BOB')}. No registres un monto mayor al saldo.`;
        }

        async function submitCustomerPayment(form) {
            showLoadingAlert('Registrando pago', 'Estamos guardando el pago y actualizando el saldo de la reserva.');
            const formData = new FormData(form);

            try {
                const response = await fetch(paymentStoreUrl, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': csrfToken,
                        'Accept': 'application/json',
                    },
                    body: formData,
                });

                if (!response.ok) {
                    const payload = await safeJsonResponse(response);
                    closeLoadingAlert();
                    await notifyError(payload.message || firstValidationMessage(payload) || 'No se pudo registrar el pago.');
                    return;
                }

                const payload = await response.json();
                customerPaymentsModal.hide();
                await refreshLedgerFromServer(payload.message || 'Pago registrado correctamente.', false);
            } catch (error) {
                closeLoadingAlert();
                await notifyError(error.message || 'No se pudo registrar el pago.');
            }
        }

        function setCustomerField(field, value) {
            const input = document.getElementById(`ledger-customer-${field}`);
            if (!input) return;

            input.value = value ?? '';
            if (input.matches('[data-country-select]') && window.jQuery?.fn?.select2) {
                window.jQuery(input).val(value ?? '').trigger('change.select2');
            }
        }

        function setCustomerChecked(field, value) {
            const input = document.getElementById(`ledger-customer-${field}`);
            if (input) input.checked = Boolean(value);
        }

        async function ensureCountriesLoaded() {
            await ensureSelect2Loaded();

            if (countriesCatalog.length) {
                populateCountrySelects();
                return countriesCatalog;
            }

            if (!countriesLoadPromise) {
                countriesLoadPromise = fetch('https://restcountries.com/v3.1/all?fields=name,translations,flags,cca2')
                    .then((response) => {
                        if (!response.ok) throw new Error('No se pudo cargar la lista de paises.');
                        return response.json();
                    })
                    .then((countries) => countries
                        .map((country) => ({
                            name: country.translations?.spa?.common || country.name?.common || '',
                            code: country.cca2 || '',
                            flag: country.flags?.svg || country.flags?.png || countryFlagImageUrl(country.cca2 || ''),
                        }))
                        .filter((country) => country.name)
                        .sort((left, right) => left.name.localeCompare(right.name, 'es')))
                    .catch((error) => {
                        console.warn(error);
                        return fallbackCountries();
                    });
            }

            countriesCatalog = await countriesLoadPromise;
            populateCountrySelects();
            return countriesCatalog;
        }

        function populateCountrySelects() {
            document.querySelectorAll('[data-country-select]').forEach((select) => {
                const currentValue = select.value || select.dataset.pendingValue || '';
                destroyCountrySelect2(select);

                select.innerHTML = '<option value=""></option>' + countriesCatalog
                    .map((country) => `<option value="${escapeHtml(country.name)}" data-code="${escapeHtml(country.code)}" data-flag="${escapeHtml(country.flag)}">${escapeHtml(country.name)}</option>`)
                    .join('');
                select.value = currentValue;
                select.dataset.pendingValue = '';
                initializeCountrySelect2(select);
            });
        }

        async function ensureSelect2Loaded() {
            if (typeof window.jQuery !== 'function') {
                await loadLedgerScript(jqueryAssetUrl);
            }

            if (typeof window.jQuery?.fn?.select2 !== 'function') {
                await loadLedgerScript(select2AssetUrl);
            }

            return typeof window.jQuery?.fn?.select2 === 'function';
        }

        function loadLedgerScript(src) {
            return new Promise((resolve, reject) => {
                const existingScript = document.querySelector(`script[src="${src}"]`);
                if (existingScript?.dataset.loaded === 'true') {
                    resolve();
                    return;
                }

                if (existingScript) {
                    existingScript.addEventListener('load', resolve, { once: true });
                    existingScript.addEventListener('error', reject, { once: true });
                    return;
                }

                const script = document.createElement('script');
                script.src = src;
                script.defer = true;
                script.dataset.loaded = 'false';
                script.addEventListener('load', () => {
                    script.dataset.loaded = 'true';
                    resolve();
                }, { once: true });
                script.addEventListener('error', reject, { once: true });
                document.head.appendChild(script);
            });
        }

        function initializeCountrySelect2(select) {
            if (typeof window.jQuery?.fn?.select2 !== 'function') return;

            const $select = window.jQuery(select);
            const $modal = $select.closest('.modal');

            $select.select2({
                width: '100%',
                placeholder: 'Selecciona pais',
                allowClear: true,
                dropdownParent: $modal.length ? $modal : window.jQuery(document.body),
                dropdownCssClass: 'ledger-country-dropdown',
                templateResult: renderCountrySelect2Option,
                templateSelection: renderCountrySelect2Option,
                escapeMarkup: (markup) => markup,
            });
        }

        function destroyCountrySelect2(select) {
            if (typeof window.jQuery?.fn?.select2 !== 'function') return;

            const $select = window.jQuery(select);
            if ($select.hasClass('select2-hidden-accessible')) {
                $select.select2('destroy');
            }
        }

        function renderCountrySelect2Option(option) {
            if (!option.id) return escapeHtml(option.text || '');

            const code = option.element?.dataset?.code || '';
            const flagImage = option.element?.dataset?.flag || countryFlagImageUrl(code);
            const flagFallback = countryFlagEmoji(code);
            const flagMarkup = flagImage
                ? `<span class="ledger-country-flag"><img src="${escapeHtml(flagImage)}" alt="Bandera ${escapeHtml(option.text || '')}" loading="lazy" onerror="this.classList.add('d-none');this.nextElementSibling.classList.remove('d-none');"><span class="d-none">${flagFallback}</span></span>`
                : `<span class="ledger-country-flag">${flagFallback}</span>`;

            return `<span class="ledger-country-option">${flagMarkup}<span>${escapeHtml(option.text || '')}</span></span>`;
        }

        function renderGuestRows(guests) {
            const list = document.getElementById('ledger-guests-list');
            list.innerHTML = '';
            guestRowCounter = 0;
            guests.forEach((guest) => addGuestRow(guest));
            refreshGuestEmptyState();
        }

        function addGuestRow(guest = {}) {
            const list = document.getElementById('ledger-guests-list');
            const index = guestRowCounter++;
            const wrapper = document.createElement('article');
            wrapper.className = 'ledger-guest-card';
            wrapper.dataset.guestRow = String(index);
            wrapper.innerHTML = `
                <div class="ledger-guest-card__top">
                    <strong>Acompanante ${index + 1}</strong>
                    <button type="button" class="btn btn-outline-danger btn-sm rounded-pill" data-remove-guest-row>Quitar</button>
                </div>
                <div class="row g-2">
                    <div class="col-md-4">
                        <label class="form-label">Nombre completo</label>
                        <input type="text" class="form-control" name="guests[${index}][full_name]" value="${escapeHtml(guest.full_name || '')}">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Documento</label>
                        <select class="form-select" name="guests[${index}][document_type]">
                            <option value="">Opcional</option>
                            <option value="ci" ${guest.document_type === 'ci' ? 'selected' : ''}>CI</option>
                            <option value="passport" ${guest.document_type === 'passport' ? 'selected' : ''}>Pasaporte</option>
                            <option value="nit" ${guest.document_type === 'nit' ? 'selected' : ''}>NIT</option>
                            <option value="other" ${guest.document_type === 'other' ? 'selected' : ''}>Otro</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Numero</label>
                        <input type="text" class="form-control" name="guests[${index}][document_number]" value="${escapeHtml(guest.document_number || '')}">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Fecha nacimiento</label>
                        <input type="date" class="form-control" name="guests[${index}][birth_date]" value="${escapeHtml(guest.birth_date || '')}">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Nacionalidad</label>
                        <select class="form-select ledger-country-select" name="guests[${index}][nationality]" data-country-select data-pending-value="${escapeHtml(guest.nationality || '')}"></select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Pais</label>
                        <select class="form-select ledger-country-select" name="guests[${index}][country]" data-country-select data-pending-value="${escapeHtml(guest.country || '')}"></select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Relacion</label>
                        <input type="text" class="form-control" name="guests[${index}][relationship]" placeholder="Ej. esposa, hijo, colega" value="${escapeHtml(guest.relationship || '')}">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Nota</label>
                        <input type="text" class="form-control" name="guests[${index}][notes]" value="${escapeHtml(guest.notes || '')}">
                    </div>
                </div>`;
            list.appendChild(wrapper);
            populateCountrySelects();
            refreshGuestEmptyState();
        }

        function refreshGuestEmptyState() {
            const list = document.getElementById('ledger-guests-list');
            const hasRows = Boolean(list.querySelector('[data-guest-row]'));
            let empty = list.querySelector('[data-guest-empty]');
            if (!hasRows && !empty) {
                empty = document.createElement('div');
                empty.className = 'ledger-guest-empty';
                empty.dataset.guestEmpty = '1';
                empty.textContent = 'No hay acompanantes registrados para esta reserva.';
                list.appendChild(empty);
            }
            if (hasRows) {
                empty?.remove();
            }
        }

        function guestFormData() {
            const formData = new FormData();
            document.querySelectorAll('#ledger-guests-list [data-guest-row]').forEach((row, rowIndex) => {
                row.querySelectorAll('[name]').forEach((input) => {
                    const name = input.name.replace(/guests\[\d+\]/, `guests[${rowIndex}]`);
                    formData.append(name, input.value);
                });
            });
            formData.append('_method', 'PUT');
            return formData;
        }

        function countryFlagEmoji(code) {
            code = String(code || '').toUpperCase();
            if (code.length !== 2) return '';
            return code.replace(/./g, (char) => String.fromCodePoint(127397 + char.charCodeAt(0)));
        }

        function countryFlagImageUrl(code) {
            code = String(code || '').trim().toLowerCase();
            return code.length === 2 ? `https://flagcdn.com/w40/${code}.png` : '';
        }

        function fallbackCountries() {
            return [
                { name: 'Bolivia', code: 'BO' },
                { name: 'Argentina', code: 'AR' },
                { name: 'Brasil', code: 'BR' },
                { name: 'Chile', code: 'CL' },
                { name: 'Colombia', code: 'CO' },
                { name: 'Ecuador', code: 'EC' },
                { name: 'Estados Unidos', code: 'US' },
                { name: 'Paraguay', code: 'PY' },
                { name: 'Peru', code: 'PE' },
                { name: 'Uruguay', code: 'UY' },
            ].map((country) => ({ ...country, flag: countryFlagImageUrl(country.code) }));
        }

        function renderMonthOptions(visibleMonth) {
            return Array.from({ length: 12 }, (_, index) => {
                const label = new Date(visibleMonth.getFullYear(), index, 1).toLocaleDateString('es-BO', { month: 'long' });
                return `<option value="${index}" ${index === visibleMonth.getMonth() ? 'selected' : ''}>${escapeHtml(capitalize(label))}</option>`;
            }).join('');
        }

        function showReservationDetail(reservationId) {
            const detail = document.getElementById('ledger-reservation-detail');
            if (!detail || !activeReservationsRoom) return;
            const reservation = activeReservationsRoom.reservations.find((item) => Number(item.id) === Number(reservationId));
            if (!reservation) return;
            const payments = activeReservationsRoom.payments.filter((payment) => payment.reservation_code === reservation.code);
            const confirmedPayments = payments.filter((payment) => payment.status === 'confirmed');
            const totalPaidBob = confirmedPayments.filter((payment) => payment.currency !== 'USD').reduce((sum, payment) => sum + Number(payment.amount || 0), 0);
            const totalPaidUsd = confirmedPayments.filter((payment) => payment.currency === 'USD').reduce((sum, payment) => sum + Number(payment.amount || 0), 0);
            const documentLabel = [reservation.document_type, reservation.document_number].filter(Boolean).join(' - ') || 'Sin documento';
            const locationLabel = [reservation.city, reservation.country || reservation.nationality].filter(Boolean).join(' - ') || 'Sin ubicacion';
            const contactLabel = [reservation.phone, reservation.whatsapp, reservation.email].filter(Boolean).join(' / ') || 'Sin contacto';

            detail.innerHTML = `<div class="ledger-modal-card__top">
                    <div>
                        <small>Ficha rapida del huesped</small>
                        <h5><button type="button" class="ledger-customer-name-btn" data-customer-action-reservation-id="${reservation.id}">${escapeHtml(reservation.customer)}</button></h5>
                        <span class="ledger-code">${escapeHtml(reservation.code)}</span>
                    </div>
                    <span class="badge text-bg-primary">${escapeHtml(reservation.status_label)}</span>
                </div>
                <div class="ledger-detail-grid">
                    <div><span>Documento</span><strong>${escapeHtml(documentLabel)}</strong></div>
                    <div><span>Contacto</span><strong>${escapeHtml(contactLabel)}</strong></div>
                    <div><span>Ciudad / Pais</span><strong>${escapeHtml(locationLabel)}</strong></div>
                    <div><span>Fechas reservadas</span><strong>${escapeHtml(reservation.check_in || '-')} al ${escapeHtml(reservation.check_out || '-')}</strong></div>
                    <div><span>Movimiento real</span><strong>${escapeHtml(realMovementLabel(reservation))}</strong></div>
                    <div><span>Personas</span><strong>${reservation.people}</strong></div>
                    <div><span>Noches</span><strong>${reservation.nights}</strong></div>
                    <div><span>Total</span><strong>${money(reservation.total, 'BOB')}</strong></div>
                    <div><span>Saldo</span><strong>${money(reservation.balance, 'BOB')}</strong></div>
                    <div><span>Pagos registrados</span><strong>${payments.length}</strong></div>
                    <div><span>Pagado confirmado</span><strong>Bs. ${totalPaidBob.toFixed(2)}${totalPaidUsd > 0 ? ' / $us ' + totalPaidUsd.toFixed(2) : ''}</strong></div>
                    <div><span>Notas reserva</span><strong>${escapeHtml(reservation.notes || 'Sin notas')}</strong></div>
                    <div><span>Notas cliente</span><strong>${escapeHtml(reservation.customer_notes || 'Sin notas')}</strong></div>
                </div>
                <div class="ledger-detail-payments">
                    ${payments.length ? payments.map((payment) => `<div class="ledger-detail-payment">
                        <strong>${escapeHtml(payment.code)} · ${money(payment.amount, payment.currency)}</strong>
                        <span class="badge ${payment.status === 'confirmed' ? 'text-bg-success' : payment.status === 'pending' ? 'text-bg-warning' : 'text-bg-secondary'}">${escapeHtml(payment.status_label)}</span>
                        <small>${escapeHtml(payment.method || '-')} · ${escapeHtml(payment.payment_date || '-')} · ${escapeHtml(payment.reference || 'Sin referencia')}</small>
                    </div>`).join('') : '<div class="ledger-detail-payment"><strong>Sin pagos registrados</strong><small>Cuando exista comprobante o pago manual, se mostrara aqui.</small></div>'}
                </div>`;
        }

        function showReservationDetail(reservationId, focusDetail = false) {
            const detail = document.getElementById('ledger-reservation-detail');
            if (!detail || !activeReservationsRoom) return false;
            const reservation = activeReservationsRoom.reservations.find((item) => Number(item.id) === Number(reservationId));
            if (!reservation) return false;

            activeCustomerReservation = reservation;
            highlightSelectedReservationInCalendar(reservation);
            const payments = reservation.payments?.length ? reservation.payments : activeReservationsRoom.payments.filter((payment) => payment.reservation_code === reservation.code);
            const confirmedPayments = payments.filter((payment) => payment.status === 'confirmed');
            const totalPaidBob = confirmedPayments.filter((payment) => payment.currency !== 'USD').reduce((sum, payment) => sum + Number(payment.amount || 0), 0);
            const totalPaidUsd = confirmedPayments.filter((payment) => payment.currency === 'USD').reduce((sum, payment) => sum + Number(payment.amount || 0), 0);
            const documentLabel = [reservation.document_type, reservation.document_number].filter(Boolean).join(' - ') || 'Sin documento';
            const locationLabel = [reservation.city, reservation.country || reservation.nationality].filter(Boolean).join(' - ') || 'Sin ubicacion';
            const contactLabel = [reservation.phone, reservation.whatsapp, reservation.email].filter(Boolean).join(' / ') || 'Sin contacto';
            const pendingPayment = payments.find((payment) => payment.status === 'pending');
            const conflictHtml = reservation.has_conflicts
                ? `<div class="ledger-conflict-alert">
                    <strong><i class="bi bi-exclamation-triangle me-1"></i> Cuidado: hay choque de fechas en esta habitacion</strong>
                    ${(reservation.conflicts || []).map((conflict) => `<small>${escapeHtml(conflict.customer)} - ${escapeHtml(conflict.check_in)} al ${escapeHtml(conflict.check_out)} (${escapeHtml(conflict.status_label)})</small>`).join('')}
                </div>`
                : `<div class="ledger-ok-alert"><i class="bi bi-check2-circle me-1"></i> No se detectan choques de fecha para esta habitacion.</div>`;
            const depositHtml = `<div class="${reservation.has_required_deposit ? 'ledger-ok-alert' : 'ledger-warning-alert'}">
                <strong>Anticipo requerido: ${reservation.deposit_percentage || 0}%</strong>
                <small>${reservation.has_required_deposit ? 'El anticipo ya esta cubierto para aprobar.' : `Falta confirmar ${money(reservation.deposit_pending || 0, reservation.display_currency || 'BOB')} para aprobar automaticamente.`}</small>
            </div>`;
            const cancellationHtml = reservation.status === 'cancelled'
                ? `<div class="ledger-conflict-alert">
                    <strong><i class="bi bi-calendar-x me-1"></i> Reserva anulada por el cliente</strong>
                    <small>Fecha de anulacion: ${escapeHtml(reservation.cancelled_at || '-')}</small>
                    <small>Motivo: ${escapeHtml(reservation.cancellation_reason || 'El cliente no dejo motivo.')}</small>
                    ${reservation.cancellation_reviewed_at ? `<small>Revision: ${escapeHtml(reservation.cancellation_reviewed_at)} por ${escapeHtml(reservation.cancellation_reviewed_by || 'usuario interno')}</small>` : '<small>Pendiente de revision por recepcion.</small>'}
                    <small>Revisar politica de 5 dias habiles, pagos registrados y devolucion o retencion si corresponde.</small>
                </div>`
                : '';
            const actionsHelpHtml = reservation.status === 'cancelled'
                ? `<strong>Revision administrativa</strong>
                    <small>Esta anulacion ya libero la habitacion si no existian otras reservas activas. Revisa pagos, comprobantes y aplica la politica de devolucion o retencion segun corresponda.</small>`
                : `<strong>Acciones de solicitud</strong>
                    <small>Click derecho sobre el solicitante de arriba para aprobar comprobante y reserva, o rechazar comprobante y cancelar solicitud.</small>`;

            detail.innerHTML = `<div class="ledger-modal-card__top">
                    <div>
                        <small>${reservation.is_online_request ? 'Solicitud enviada desde la pagina web' : 'Ficha rapida del huesped'}</small>
                        <h5><button type="button" class="ledger-customer-name-btn" data-customer-action-reservation-id="${reservation.id}">${escapeHtml(reservation.customer)}</button></h5>
                        <span class="ledger-code">${escapeHtml(reservation.code)}</span>
                    </div>
                    <div class="d-flex flex-wrap gap-2 align-items-center justify-content-end">
                        ${whatsappLink(reservation) ? `<a class="btn btn-success btn-sm rounded-pill fw-bold" href="${whatsappLink(reservation)}" target="_blank" rel="noopener"><i class="bi bi-whatsapp me-1"></i> Escribir por WhatsApp</a>` : ''}
                        <span class="badge text-bg-primary">${escapeHtml(reservation.status_label)}</span>
                    </div>
                </div>
                ${cancellationHtml}
                <div class="ledger-decision-stack">${conflictHtml}${depositHtml}</div>
                <div class="ledger-detail-grid">
                    <div><span>Documento</span><strong>${escapeHtml(documentLabel)}</strong></div>
                    <div><span>Contacto</span><strong>${escapeHtml(contactLabel)}</strong></div>
                    <div><span>Ciudad / Pais</span><strong>${escapeHtml(locationLabel)}</strong></div>
                    <div><span>Fechas solicitadas</span><strong>${escapeHtml(reservation.check_in || '-')} al ${escapeHtml(reservation.check_out || '-')}</strong></div>
                    <div><span>Movimiento real</span><strong>${escapeHtml(realMovementLabel(reservation))}</strong></div>
                    <div><span>Personas</span><strong>${reservation.people}</strong></div>
                    <div><span>Noches</span><strong>${reservation.nights}</strong></div>
                    <div><span>Total</span><strong>${money(reservation.display_total ?? reservation.total, reservation.display_currency || 'BOB')}</strong></div>
                    <div><span>Saldo</span><strong>${money(reservation.display_balance ?? reservation.balance, reservation.display_currency || 'BOB')}</strong></div>
                    <div><span>Pagos registrados</span><strong>${payments.length}</strong></div>
                    <div><span>Pagado confirmado</span><strong>Bs. ${totalPaidBob.toFixed(2)}${totalPaidUsd > 0 ? ' / $us ' + totalPaidUsd.toFixed(2) : ''}</strong></div>
                    <div><span>Notas reserva</span><strong>${escapeHtml(reservation.notes || 'Sin notas')}</strong></div>
                    <div><span>Notas cliente</span><strong>${escapeHtml(reservation.customer_notes || 'Sin notas')}</strong></div>
                </div>
                <div class="ledger-detail-payments">
                    ${payments.length ? payments.map((payment) => `<div class="ledger-detail-payment">
                        <strong>${escapeHtml(payment.code)} - ${payment.amount_formatted || money(payment.amount, payment.currency)}</strong>
                        <span class="badge ${payment.status === 'confirmed' ? 'text-bg-success' : payment.status === 'pending' ? 'text-bg-warning' : payment.status === 'rejected' ? 'text-bg-danger' : 'text-bg-secondary'}">${escapeHtml(payment.status_label)}</span>
                        <small>${escapeHtml(payment.method || '-')} - ${escapeHtml(payment.payment_date || '-')} - ${escapeHtml(payment.reference || 'Sin referencia')}</small>
                        ${payment.receipt_url ? `<a class="btn btn-outline-secondary btn-sm rounded-pill" href="${payment.receipt_url}" target="_blank" rel="noopener"><i class="bi bi-paperclip me-1"></i> Ver comprobante</a>` : ''}
                    </div>`).join('') : '<div class="ledger-detail-payment"><strong>Sin pagos registrados</strong><small>Cuando exista comprobante o pago manual, se mostrara aqui.</small></div>'}
                </div>
                <div class="ledger-decision-actions">
                    <div class="ledger-warning-alert w-100">
                        ${actionsHelpHtml}
                    </div>
                </div>`;

            if (focusDetail) {
                detail.classList.add('is-focused');
                detail.scrollIntoView({ behavior: 'smooth', block: 'start' });
                window.setTimeout(() => detail.classList.remove('is-focused'), 1600);
            }

            return true;
        }

        function realMovementLabel(reservation) {
            const parts = [];

            if (reservation.checked_in_at) {
                parts.push(`Entrada real: ${reservation.checked_in_at}`);
            }

            if (reservation.checked_out_at) {
                parts.push(`Salida real: ${reservation.checked_out_at}${reservation.is_early_checkout ? ' (salio antes)' : ''}`);
            }

            return parts.length ? parts.join(' / ') : 'Aun no registra entrada o salida real.';
        }

        function initialCalendarMonth(room) {
            const today = stripTime(new Date());
            const reservations = room.reservations.map((reservation) => ({
                ...reservation,
                startDate: parseIsoDate(reservation.calendar_check_in_iso || reservation.check_in_iso),
                endDate: parseIsoDate(reservation.calendar_check_out_iso || reservation.check_out_iso),
            })).filter((reservation) => reservation.startDate && reservation.endDate);
            const currentReservation = reservations.find((reservation) => today >= stripTime(reservation.startDate) && today <= stripTime(reservation.endDate));
            const firstFutureReservation = reservations.find((reservation) => stripTime(reservation.endDate) >= today);
            const base = currentReservation?.startDate || firstFutureReservation?.startDate || reservations[0]?.startDate || today;

            return new Date(base.getFullYear(), base.getMonth(), 1);
        }

        function reservationsForMonth(reservations, monthDate) {
            const monthStart = new Date(monthDate.getFullYear(), monthDate.getMonth(), 1);
            const monthEnd = new Date(monthDate.getFullYear(), monthDate.getMonth() + 1, 0);

            return reservations
                .map((reservation) => ({
                    ...reservation,
                    startDate: reservation.startDate || parseIsoDate(reservation.calendar_check_in_iso || reservation.check_in_iso),
                    endDate: reservation.endDate || parseIsoDate(reservation.calendar_check_out_iso || reservation.check_out_iso),
                }))
                .filter((reservation) => reservation.startDate && reservation.endDate)
                .filter((reservation) => stripTime(reservation.startDate) <= monthEnd && stripTime(reservation.endDate) >= monthStart)
                .sort((left, right) => stripTime(left.startDate) - stripTime(right.startDate));
        }

        function parseIsoDate(value) {
            if (!value) return null;
            const [year, month, day] = String(value).split('-').map(Number);
            if (!year || !month || !day) return null;
            return new Date(year, month - 1, day);
        }

        function isDateInReservation(date, reservation) {
            return stripTime(date) >= stripTime(reservation.startDate) && stripTime(date) <= stripTime(reservation.endDate);
        }

        function isSameDate(left, right) {
            return stripTime(left).getTime() === stripTime(right).getTime();
        }

        function stripTime(date) {
            return new Date(date.getFullYear(), date.getMonth(), date.getDate());
        }

        function formatCalendarDate(date) {
            return date.toLocaleDateString('es-BO', { day: '2-digit', month: 'short', year: 'numeric' });
        }

        function capitalize(value) {
            value = String(value || '');
            return value.charAt(0).toUpperCase() + value.slice(1);
        }

        function renderReservation(reservation) {
            const isOnlineRequest = reservation.is_online_request === true;

            return `<article class="ledger-modal-card ${isOnlineRequest ? 'ledger-modal-card--online-request' : ''}">
                <div class="ledger-modal-card__top">
                    <div><strong>${isOnlineRequest ? '🙋 Quiere reservar desde web' : escapeHtml(reservation.code)}</strong><small><button type="button" class="ledger-customer-name-btn" data-customer-action-reservation-id="${reservation.id}">${escapeHtml(reservation.customer)}</button>${reservation.phone ? ' - ' + escapeHtml(reservation.phone) : ''}</small>${isOnlineRequest ? `<span class="ledger-code">${escapeHtml(reservation.code)}</span>` : ''}</div>
                    <span class="badge ${isOnlineRequest ? 'text-bg-danger' : 'text-bg-primary'}">${escapeHtml(reservation.status_label)}</span>
                </div>
                <div class="row g-2 mt-2">
                    <div class="col-md-3"><small>Desde</small><strong>${reservation.check_in || '-'}</strong></div>
                    <div class="col-md-3"><small>Reservado hasta</small><strong>${reservation.check_out || '-'}</strong></div>
                    ${reservation.checked_out_at ? `<div class="col-md-4"><small>Salida real</small><strong>${escapeHtml(reservation.checked_out_at)}${reservation.is_early_checkout ? ' - salio antes' : ''}</strong></div>` : ''}
                    <div class="col-md-2"><small>Personas</small><strong>${reservation.people}</strong></div>
                    <div class="col-md-2"><small>Noches</small><strong>${reservation.nights}</strong></div>
                    <div class="col-md-2"><small>Saldo</small><strong>${money(reservation.balance, 'BOB')}</strong></div>
                </div>
                ${reservation.notes ? `<p class="mb-0 mt-2 text-secondary">${escapeHtml(reservation.notes)}</p>` : ''}
            </article>`;
        }

        function renderReservation(reservation) {
            const isOnlineRequest = reservation.is_online_request === true;

            return `<article class="ledger-modal-card ${isOnlineRequest ? 'ledger-modal-card--online-request' : ''}">
                <div class="ledger-modal-card__top">
                    <div>
                        <strong>${isOnlineRequest ? 'Quiere reservar desde web' : escapeHtml(reservation.code)}</strong>
                        <small><button type="button" class="ledger-customer-name-btn" data-customer-action-reservation-id="${reservation.id}">${escapeHtml(reservation.customer)}</button>${reservation.phone ? ' - ' + escapeHtml(reservation.phone) : ''}</small>
                        ${isOnlineRequest ? `<span class="ledger-code">${escapeHtml(reservation.code)}</span>` : ''}
                    </div>
                    <span class="badge ${isOnlineRequest ? 'text-bg-danger' : 'text-bg-primary'}">${escapeHtml(reservation.status_label)}</span>
                </div>
                <div class="row g-2 mt-2">
                    <div class="col-md-3"><small>Entrada</small><strong>${reservation.check_in || '-'}</strong></div>
                    <div class="col-md-3"><small>Salida</small><strong>${reservation.check_out || '-'}</strong></div>
                    <div class="col-md-2"><small>Personas</small><strong>${reservation.people}</strong></div>
                    <div class="col-md-2"><small>Noches</small><strong>${reservation.nights}</strong></div>
                    <div class="col-md-2"><small>Saldo</small><strong>${money(reservation.display_balance ?? reservation.balance, reservation.display_currency || 'BOB')}</strong></div>
                </div>
                ${reservation.notes ? `<p class="mb-0 mt-2 text-secondary">${escapeHtml(reservation.notes)}</p>` : ''}
                <div class="d-flex flex-wrap gap-2 mt-3">
                    <button type="button" class="btn btn-outline-primary btn-sm rounded-pill" data-reservation-detail-id="${reservation.id}">Ver detalle</button>
                    ${reservation.pending_payments_count > 0 ? `<span class="badge text-bg-warning align-self-center">${reservation.pending_payments_count} comprobante(s) por revisar</span>` : ''}
                    ${reservation.has_conflicts ? '<span class="badge text-bg-danger align-self-center">Choque de fechas</span>' : ''}
                </div>
            </article>`;
        }

        function renderPayment(payment) {
            const actions = [
                payment.receipt_url ? `<a class="btn btn-outline-secondary btn-sm rounded-pill" href="${payment.receipt_url}" target="_blank" rel="noopener">Ver comprobante</a>` : '',
                payment.can_confirm ? `<button type="button" class="btn btn-success btn-sm rounded-pill" data-payment-action="confirm" data-url="${payment.confirm_url}">Aprobar</button>` : '',
                payment.can_reject ? `<button type="button" class="btn btn-outline-danger btn-sm rounded-pill" data-payment-action="reject" data-url="${payment.reject_url}">Rechazar</button>` : '',
            ].filter(Boolean).join('');

            return `<article class="ledger-modal-card">
                <div class="ledger-modal-card__top">
                    <div><strong>${escapeHtml(payment.code)}</strong><small>${escapeHtml(payment.customer)} - ${escapeHtml(payment.reservation_code || '')}</small></div>
                    <span class="badge ${payment.status === 'confirmed' ? 'text-bg-success' : payment.status === 'pending' ? 'text-bg-warning' : 'text-bg-secondary'}">${escapeHtml(payment.status_label)}</span>
                </div>
                <div class="row g-2 mt-2">
                    <div class="col-md-3"><small>Monto</small><strong>${money(payment.amount, payment.currency)}</strong></div>
                    <div class="col-md-3"><small>Metodo</small><strong>${escapeHtml(payment.method || '-')}</strong></div>
                    <div class="col-md-3"><small>Fecha</small><strong>${payment.payment_date || '-'}</strong></div>
                    <div class="col-md-3"><small>Referencia</small><strong>${escapeHtml(payment.reference || '-')}</strong></div>
                </div>
                ${payment.notes ? `<p class="mb-0 mt-2 text-secondary">${escapeHtml(payment.notes)}</p>` : ''}
                ${actions ? `<div class="d-flex flex-wrap gap-2 mt-3">${actions}</div>` : ''}
            </article>`;
        }

        async function submitLedgerAction(url, formData) {
            showLoadingAlert('Guardando accion', 'Estamos actualizando la informacion en la base de datos.');
            try {
                const response = await fetch(url, { method: 'POST', headers: { 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' }, body: formData });
                let payload = {};
                try { payload = await response.json(); } catch (error) { payload = {}; }
                if (!response.ok || payload.ok === false) {
                    closeLoadingAlert();
                    await notifyError(payload.message || firstValidationMessage(payload) || 'No se pudo completar la accion.');
                    return;
                }
                statusModal.hide();
                actionModal.hide();
                paymentsModal.hide();
                await refreshLedgerFromServer(payload.message || 'Accion realizada correctamente.', false);
            } catch (error) {
                closeLoadingAlert();
                await notifyError(error.message || 'No se pudo completar la accion.');
            }
        }

        async function refreshLedgerFromServer(successMessage = null, showOwnLoader = true, options = {}) {
            try {
                if (showOwnLoader) {
                    showLoadingAlert('Actualizando pantalla', 'Estamos trayendo la informacion mas reciente.');
                }

                const refreshUrl = new URL(window.location.href);
                refreshUrl.searchParams.set('_ledger_refresh', Date.now().toString());

                const response = await fetch(refreshUrl.toString(), {
                    cache: 'no-store',
                    headers: {
                        'Accept': 'text/html',
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                });

                if (!response.ok) {
                    throw new Error('No se pudo actualizar el libro de recepcion.');
                }

                const html = await response.text();
                const documentFragment = new DOMParser().parseFromString(html, 'text/html');
                replaceLedgerSection(documentFragment, '.ledger-summary');
                replaceLedgerSection(documentFragment, '.ledger-cancellation-requests', true);
                replaceLedgerSection(documentFragment, '.ledger-stay-alerts', true);
                replaceLedgerSection(documentFragment, '.ledger-search-panel');
                replaceLedgerSection(documentFragment, '.ledger-help');
                replaceLedgerSection(documentFragment, '.ledger-paper');
                bindLedgerSearch();
                filterLedgerRows();
                syncActiveLedgerState(options);

                if (successMessage) {
                    closeLoadingAlert();
                    await notifySuccess(successMessage);
                } else {
                    closeLoadingAlert();
                }
            } catch (error) {
                closeLoadingAlert();
                await notifyError(`${error.message || 'No se pudo refrescar la informacion.'} Actualiza manualmente si ves datos antiguos.`);
            }
        }

        function replaceLedgerSection(sourceDocument, selector, optional = false) {
            const current = document.querySelector(selector);
            const fresh = sourceDocument.querySelector(selector);
            if (current && fresh) {
                current.replaceWith(fresh);
            } else if (current && optional && !fresh) {
                current.remove();
            } else if (!current && optional && fresh) {
                const summary = document.querySelector('.ledger-summary');
                if (summary) {
                    summary.insertAdjacentElement('afterend', fresh);
                }
            }
        }

        function syncActiveLedgerState(options = {}) {
            const focusedRoomId = options.focusRoomId || activeReservationsRoom?.room_id || activeBookingRoom?.room_id || null;

            if (activeReservationsRoom?.room_id) {
                activeReservationsRoom = readRoomData(activeReservationsRoom.room_id);
            }

            if (activeBookingRoom?.room_id) {
                activeBookingRoom = readRoomData(activeBookingRoom.room_id);
            }

            if (activeCustomerReservation?.id) {
                const refreshedReservation = findReservationInLedger(activeCustomerReservation.id);
                if (refreshedReservation) {
                    activeCustomerReservation = refreshedReservation;
                }
            }

            if (focusedRoomId && options.reopenReservations) {
                const room = readRoomData(focusedRoomId);
                activeReservationsRoom = room;
                openReservationsModal(room);
            } else if (document.getElementById('ledgerReservationsModal')?.classList.contains('show') && activeReservationsRoom) {
                refreshReservationCalendar();
            }
        }

        function findReservationInLedger(reservationId) {
            for (const row of Array.from(document.querySelectorAll('[id^="ledger-room-data-"]'))) {
                const room = JSON.parse(row.textContent);
                const reservation = (room.reservations || []).find((item) => Number(item.id) === Number(reservationId));

                if (reservation) {
                    return reservation;
                }
            }

            return null;
        }

        async function notifySuccess(message, title = 'Listo') {
            if (window.Swal) {
                await window.Swal.fire({
                    icon: 'success',
                    title,
                    text: message,
                    timer: 1900,
                    showConfirmButton: false,
                });
                return;
            }

            console.info(message);
        }

        function showLoadingAlert(title = 'Procesando', text = 'Por favor espera un momento.') {
            if (!window.Swal) {
                console.info(`${title}: ${text}`);
                return;
            }

            window.Swal.fire({
                title,
                text,
                allowOutsideClick: false,
                allowEscapeKey: false,
                showConfirmButton: false,
                didOpen: () => {
                    window.Swal.showLoading();
                },
            });
        }

        function closeLoadingAlert() {
            if (window.Swal?.isVisible() && typeof window.Swal.close === 'function') {
                window.Swal.close();
            }
        }

        async function notifyError(message, title = 'Revisa la informacion') {
            if (window.Swal) {
                await window.Swal.fire({
                    icon: 'error',
                    title,
                    text: message,
                });
                return;
            }

            console.error(message);
        }

        function notifyToast(message, icon = 'success') {
            if (window.Swal) {
                window.Swal.fire({
                    toast: true,
                    position: 'top-end',
                    icon,
                    title: message,
                    timer: 1800,
                    timerProgressBar: true,
                    showConfirmButton: false,
                });
                return;
            }

            console.info(message);
        }

        function money(amount, currency) {
            return `${currency === 'USD' ? '$us ' : 'Bs. '}${Number(amount || 0).toFixed(2)}`;
        }

        function escapeHtml(value) {
            return String(value ?? '').replace(/[&<>"']/g, (char) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;' }[char]));
        }

        async function safeJsonResponse(response) {
            try {
                return await response.json();
            } catch (error) {
                return {};
            }
        }

        function firstValidationMessage(payload) {
            const errors = payload?.errors || {};
            const firstKey = Object.keys(errors)[0];
            return firstKey && Array.isArray(errors[firstKey]) ? errors[firstKey][0] : null;
        }

        function createLedgerBootstrapFallback() {
            class FallbackModal {
                constructor(element) {
                    this.element = element;
                    this.backdrop = null;
                }

                show() {
                    if (!this.element) return;
                    this.element.style.display = 'block';
                    this.element.removeAttribute('aria-hidden');
                    this.element.setAttribute('aria-modal', 'true');
                    this.element.classList.add('show');
                    document.body.classList.add('modal-open');
                    this.backdrop = document.createElement('div');
                    this.backdrop.className = 'modal-backdrop fade show';
                    document.body.appendChild(this.backdrop);
                }

                hide() {
                    if (!this.element) return;
                    this.element.classList.remove('show');
                    this.element.style.display = 'none';
                    this.element.setAttribute('aria-hidden', 'true');
                    this.element.removeAttribute('aria-modal');
                    document.body.classList.remove('modal-open');
                    this.backdrop?.remove();
                    this.backdrop = null;
                    this.element.dispatchEvent(new Event('hidden.bs.modal'));
                }
            }

            document.addEventListener('click', (event) => {
                const closeButton = event.target.closest('[data-bs-dismiss="modal"]');
                if (!closeButton) return;
                const modal = closeButton.closest('.modal');
                if (!modal) return;
                modal.classList.remove('show');
                modal.style.display = 'none';
                modal.setAttribute('aria-hidden', 'true');
                modal.removeAttribute('aria-modal');
                document.querySelector('.modal-backdrop')?.remove();
                document.body.classList.remove('modal-open');
                modal.dispatchEvent(new Event('hidden.bs.modal'));
            });

            console.warn('Bootstrap no estaba disponible; se activo un fallback basico para modales de recepcion.');

            return { Modal: FallbackModal };
        }
        }

        if (document.readyState === 'complete') {
            initializeLedgerPage();
        } else {
            window.addEventListener('load', initializeLedgerPage, { once: true });
        }
    </script>
@endpush
