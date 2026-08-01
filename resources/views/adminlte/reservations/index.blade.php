@extends('adminlte::page')

@section('title', 'Reservas')

@php
    $roomsCatalog = $rooms->map(fn ($room) => [
        'id' => $room->id,
        'number' => $room->number,
        'status' => $room->status,
        'room_type_id' => $room->room_type_id,
        'room_type_name' => $room->roomType?->name ?? '-',
        'base_price' => (float) ($room->roomType?->base_price ?? 0),
        'price_bob' => (float) ($room->roomType?->price_bob ?? $room->roomType?->base_price ?? 0),
        'price_usd' => (float) ($room->roomType?->price_usd ?? 0),
        'reservation_deposit_percentage' => (int) ($room->roomType?->reservation_deposit_percentage ?? 20),
        'max_guests' => (int) ($room->roomType?->max_guests ?? 0),
        'label' => sprintf(
            'Hab. %s - %s - Bs. %s / $us %s - Max. %d huespedes',
            $room->number,
            $room->roomType?->name ?? 'Sin tipo',
            number_format((float) ($room->roomType?->price_bob ?? $room->roomType?->base_price ?? 0), 2, '.', ''),
            number_format((float) ($room->roomType?->price_usd ?? 0), 2, '.', ''),
            (int) ($room->roomType?->max_guests ?? 0)
        ),
    ])->values();

    $promotionsCatalog = $promotions->map(fn ($promotion) => [
        'id' => $promotion->id,
        'name' => $promotion->name,
        'discount_type' => $promotion->discount_type,
        'discount_value' => (float) $promotion->discount_value,
        'minimum_nights' => $promotion->minimum_nights,
        'room_type_ids' => $promotion->roomTypes->pluck('id')->values(),
    ])->values();
@endphp

@section('content_header')
    <div class="reservations-hero">
        <div class="reservations-hero__content">
            <span class="reservations-kicker">Operacion de reservas</span>
            <h1>Reservas</h1>
            <p>Gestiona solicitudes, confirmaciones, anticipos, check-in y check-out con una lectura rapida del estado operativo y financiero.</p>
        </div>

        <div class="reservations-hero__actions">
            @can('reservas.ver')
                <button type="button" class="btn btn-reservations-secondary" id="open-reservation-agenda-modal">
                    <i class="bi bi-calendar4-week me-2" aria-hidden="true"></i> Agenda de reservas
                </button>
            @endcan

            @can('reservas.crear')
                <button type="button" class="btn btn-reservations-primary" id="open-create-reservation-modal">
                    <i class="bi bi-calendar-plus me-2" aria-hidden="true"></i> Nueva reserva
                </button>
            @endcan
        </div>
    </div>
@stop

@section('content')
    <div class="row g-3 mb-4">
        <div class="col-6 col-xl-3">
            <div class="reservation-stat">
                <span class="reservation-stat__icon bg-gradient-indigo"><i class="bi bi-calendar2-week"></i></span>
                <div><small>Total</small><strong>{{ number_format($stats['total'] ?? 0) }}</strong></div>
            </div>
        </div>
        <div class="col-6 col-xl-3">
            <div class="reservation-stat">
                <span class="reservation-stat__icon bg-gradient-gold"><i class="bi bi-hourglass-split"></i></span>
                <div><small>Pendientes</small><strong>{{ number_format($stats['pending'] ?? 0) }}</strong></div>
            </div>
        </div>
        <div class="col-6 col-xl-3">
            <div class="reservation-stat">
                <span class="reservation-stat__icon bg-gradient-green"><i class="bi bi-shield-check"></i></span>
                <div><small>Confirmadas</small><strong>{{ number_format($stats['confirmed'] ?? 0) }}</strong></div>
            </div>
        </div>
        <div class="col-6 col-xl-3">
            <div class="reservation-stat">
                <span class="reservation-stat__icon bg-gradient-copper"><i class="bi bi-door-open"></i></span>
                <div><small>Llegadas hoy</small><strong>{{ number_format($stats['today_arrivals'] ?? 0) }}</strong></div>
            </div>
        </div>
    </div>

    <div class="reservations-panel">
        <div class="reservations-panel__header">
            <div>
                <span class="reservations-kicker text-primary">Calendario comercial</span>
                <h2>Reservas y seguimiento de anticipos</h2>
                <p>Visualiza habitacion, huesped, fechas, saldo y acciones inmediatas del flujo operativo.</p>
            </div>
            <div class="reservations-panel__balances">
                <div class="reservations-panel__summary reservations-panel__summary--bob">
                    <span>Saldo pendiente BOB</span>
                    <strong>Bs. {{ number_format((float) ($stats['balance_bob'] ?? 0), 2, '.', '') }}</strong>
                    <small>Reservas en bolivianos</small>
                </div>
                <div class="reservations-panel__summary reservations-panel__summary--usd">
                    <span>Saldo pendiente USD</span>
                    <strong>$us {{ number_format((float) ($stats['balance_usd'] ?? 0), 2, '.', '') }}</strong>
                    <small>Reservas en dolares</small>
                </div>
            </div>
        </div>

        <div class="table-responsive">
            <table class="table table-hover align-middle w-100" id="reservations-table">
                <thead>
                    <tr>
                        <th>Codigo</th>
                        <th>Cliente</th>
                        <th>Habitacion</th>
                        <th>Fechas</th>
                        <th>Huespedes</th>
                        <th>Total</th>
                        <th>Saldo</th>
                        <th>Estado</th>
                        <th>Registro</th>
                        <th class="text-end">Acciones</th>
                    </tr>
                </thead>
            </table>
        </div>
    </div>

    <div class="modal fade" id="reservation-agenda-modal" tabindex="-1" aria-labelledby="reservation-agenda-modal-label" aria-hidden="true">
        <div class="modal-dialog modal-fullscreen-xl-down modal-xl modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header reservation-agenda-header">
                    <div>
                        <span class="reservations-kicker text-primary">Agenda operativa</span>
                        <h5 class="modal-title" id="reservation-agenda-modal-label">Reservas por fecha y habitacion</h5>
                        <small class="text-muted">Consulta rapidamente quien llega, que habitacion ocupa, fechas, contacto, saldo y estado.</small>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                </div>
                <div class="modal-body">
                    <div class="reservation-agenda-toolbar">
                        <div>
                            <label class="form-label" for="agenda-date-from">Desde</label>
                            <input type="date" class="form-control" id="agenda-date-from" value="{{ now()->startOfMonth()->toDateString() }}">
                        </div>
                        <div>
                            <label class="form-label" for="agenda-date-to">Hasta</label>
                            <input type="date" class="form-control" id="agenda-date-to" value="{{ now()->endOfMonth()->toDateString() }}">
                        </div>
                        <div class="reservation-agenda-toolbar__actions">
                            <button type="button" class="btn btn-outline-secondary" data-agenda-range="today">Hoy</button>
                            <button type="button" class="btn btn-outline-secondary" data-agenda-range="week">Semana</button>
                            <button type="button" class="btn btn-outline-secondary" data-agenda-range="month">Mes</button>
                            <button type="button" class="btn btn-reservations-primary" id="refresh-reservation-agenda">
                                <i class="bi bi-search me-1" aria-hidden="true"></i> Ver agenda
                            </button>
                        </div>
                    </div>

                    <div class="reservation-agenda-summary" id="reservation-agenda-summary">
                        <div><span>Total</span><strong>0</strong><small>reservas activas</small></div>
                        <div><span>Pendientes</span><strong>0</strong><small>por confirmar</small></div>
                        <div><span>Confirmadas</span><strong>0</strong><small>con habitacion</small></div>
                        <div><span>Ocupadas</span><strong>0</strong><small>check-in realizado</small></div>
                    </div>

                    <div class="reservation-agenda-grid" id="reservation-agenda-grid">
                        <div class="reservation-agenda-empty">Abre la agenda para cargar las reservas del rango seleccionado.</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="create-reservation-modal" tabindex="-1" aria-labelledby="create-reservation-modal-label" aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-scrollable">
            <div class="modal-content">
                <form id="create-reservation-form" action="{{ route('adminlte.reservations.store') }}">
                    @csrf
                    <div class="modal-header">
                        <div>
                            <span class="reservations-kicker text-primary">Nueva solicitud</span>
                            <h5 class="modal-title" id="create-reservation-modal-label">Nueva reserva</h5>
                            <small class="text-muted">Flujo guiado para recepcion: fechas, huesped, habitacion, cobro y notas.</small>
                        </div>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                    </div>
                    <div class="modal-body">
                        <div class="reservation-stepper" data-create-stepper>
                            <button type="button" class="reservation-stepper__item is-active" data-step-target="1">
                                <span>1</span>
                                <strong>Fechas</strong>
                                <small>Entrada, salida y huespedes</small>
                            </button>
                            <button type="button" class="reservation-stepper__item" data-step-target="2">
                                <span>2</span>
                                <strong>Cliente</strong>
                                <small>Busca al huesped</small>
                            </button>
                            <button type="button" class="reservation-stepper__item" data-step-target="3">
                                <span>3</span>
                                <strong>Habitacion</strong>
                                <small>Disponibilidad real</small>
                            </button>
                            <button type="button" class="reservation-stepper__item" data-step-target="4">
                                <span>4</span>
                                <strong>Pago</strong>
                                <small>Origen, precio y anticipo</small>
                            </button>
                            <button type="button" class="reservation-stepper__item" data-step-target="5">
                                <span>5</span>
                                <strong>Notas</strong>
                                <small>Revisar y guardar</small>
                            </button>
                        </div>

                        <div class="reservation-step-panel is-active" data-step-panel="1">
                            <div class="reservation-step-intro">
                                <span class="reservations-kicker text-primary">Paso 1</span>
                                <h3>Primero define la estadia</h3>
                                <p>Con fechas y cantidad de personas, el sistema podra validar habitaciones disponibles sin confundir al recepcionista.</p>
                            </div>
                            <div class="row g-3">
                                <div class="col-md-3">
                                    <label class="form-label" for="create-check_in">Entrada</label>
                                    <input type="date" class="form-control reservation-trigger" id="create-check_in" name="check_in" min="{{ now()->toDateString() }}" required>
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label" for="create-check_out">Salida</label>
                                    <input type="date" class="form-control reservation-trigger" id="create-check_out" name="check_out" min="{{ now()->addDay()->toDateString() }}" required>
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label" for="create-adults">Adultos</label>
                                    <input type="number" class="form-control reservation-trigger" id="create-adults" name="adults" min="1" max="20" value="1" required>
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label" for="create-children">Ninos</label>
                                    <input type="number" class="form-control reservation-trigger" id="create-children" name="children" min="0" max="20" value="0">
                                </div>
                            </div>
                        </div>

                        <div class="reservation-step-panel" data-step-panel="2">
                            <div class="reservation-step-intro">
                                <span class="reservations-kicker text-primary">Paso 2</span>
                                <h3>Busca o selecciona al huesped</h3>
                                <p>Usa nombre, documento, telefono, WhatsApp o correo. Esto evita duplicar clientes y ayuda al seguimiento.</p>
                            </div>
                            <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label" for="create-customer_id">Cliente / huesped</label>
                                <select class="form-select reservation-customer-select" id="create-customer_id" name="customer_id" required>
                                    <option value="">Busca por nombre, documento, telefono o correo</option>
                                </select>
                                <div class="form-text">Escribe al menos 2 letras o numeros para buscar al huesped.</div>
                            </div>
                            </div>
                        </div>

                        <div class="reservation-step-panel" data-step-panel="3">
                            <div class="reservation-step-intro">
                                <span class="reservations-kicker text-primary">Paso 3</span>
                                <h3>Elige una habitacion disponible</h3>
                                <p>Abre el tablero visual para ver habitaciones libres segun fechas y capacidad. Luego selecciona una habitacion.</p>
                            </div>
                            <div class="row g-3">
                                <div class="col-12">
                                    <label class="form-label" for="create-room_id">Habitacion</label>
                                    <div class="input-group">
                                        <select class="form-select reservation-room-select" id="create-room_id" name="room_id" required>
                                            <option value="">Selecciona una habitacion</option>
                                            @foreach ($roomsCatalog as $room)
                                                <option value="{{ $room['id'] }}">{{ $room['label'] }}</option>
                                            @endforeach
                                        </select>
                                        <button type="button" class="btn btn-outline-primary reservation-availability-board-btn" data-open-availability-board data-target-form="create">
                                            <i class="bi bi-grid-3x3-gap me-1" aria-hidden="true"></i> Ver disponibles
                                        </button>
                                    </div>
                                    <div class="form-text reservation-availability-message" data-form="create"></div>
                                </div>
                                <div class="col-12">
                                    <div class="reservation-info-card rounded-3 p-3" data-room-summary>
                                        <div class="fw-semibold mb-2">Resumen de habitacion</div>
                                        <div class="text-muted mb-0">Selecciona una habitacion para ver su tipo, precio base y capacidad maxima.</div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="reservation-step-panel" data-step-panel="4">
                            <div class="reservation-step-intro">
                                <span class="reservations-kicker text-primary">Paso 4</span>
                                <h3>Define origen, pago y precio</h3>
                                <p>La reserva se guardara pendiente. Se confirma cuando se registre o apruebe el anticipo correspondiente.</p>
                            </div>
                            <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label" for="create-promotion_id">Promocion</label>
                                <select class="form-select reservation-trigger" id="create-promotion_id" name="promotion_id">
                                    <option value="">Sin promocion</option>
                                    @foreach ($promotions as $promotion)
                                        <option value="{{ $promotion->id }}">{{ $promotion->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <input type="hidden" id="create-status" name="status" value="pending">
                            <div class="col-md-6">
                                <label class="form-label" for="create-source">Origen</label>
                                <select class="form-select" id="create-source" name="source">
                                    @foreach ($sources as $sourceKey => $sourceLabel)
                                        <option value="{{ $sourceKey }}" @selected($sourceKey === 'reception')>{{ $sourceLabel }}</option>
                                    @endforeach
                                </select>
                                <div class="form-text">La reserva se guarda como pendiente. Se confirma cuando se registre o apruebe el anticipo.</div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label" for="create-preferred_payment_method">Preferencia de pago</label>
                                <select class="form-select" id="create-preferred_payment_method" name="preferred_payment_method">
                                    <option value="">Sin definir</option>
                                    @foreach ($paymentPreferences as $paymentKey => $paymentLabel)
                                        <option value="{{ $paymentKey }}">{{ $paymentLabel }}</option>
                                    @endforeach
                                </select>
                                <div class="form-text">Para reservas web, esta opcion indica como quiere pagar el cliente.</div>
                            </div>

                            @if ($canApplyDiscount)
                                <div class="col-md-3">
                                    <label class="form-label" for="create-discount_type">Descuento manual</label>
                                    <select class="form-select reservation-trigger reservation-discount-type" id="create-discount_type" name="discount_type">
                                        <option value="">Sin descuento manual</option>
                                        <option value="percentage">Porcentaje</option>
                                        <option value="fixed">Monto fijo</option>
                                    </select>
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label" for="create-discount_value">Valor descuento</label>
                                    <input type="number" step="0.01" min="0" class="form-control reservation-trigger reservation-discount-value" id="create-discount_value" name="discount_value" value="">
                                </div>
                            @endif

                            @if ($canChangePrice)
                                <div class="col-md-4">
                                    <label class="form-label" for="create-base_price">Precio base manual</label>
                                    <div class="input-group reservation-money-input">
                                        <input type="number" step="0.01" min="0" class="form-control reservation-trigger" id="create-base_price" name="base_price" value="" placeholder="Opcional">
                                        <select class="form-select reservation-trigger reservation-currency-select" name="base_price_currency" aria-label="Moneda del precio base manual">
                                            <option value="BOB">Bs.</option>
                                            <option value="USD">$us</option>
                                        </select>
                                    </div>
                                    <div class="form-text">Si eliges $us, el sistema lo convierte a bolivianos para la reserva.</div>
                                </div>
                            @endif

                            <div class="col-12">
                                <div class="reservation-preview-card rounded-3 p-3" data-quote-preview>
                                    <div class="fw-semibold mb-2">Vista previa del costo</div>
                                    <div class="text-muted mb-0">Completa habitacion y fechas para calcular noches, descuento y total final.</div>
                                </div>
                            </div>
                            <div class="col-12">
                                <div class="reservation-initial-payment-card">
                                    <div class="reservation-payment-section-title">
                                        <span>Pago inicial del cliente</span>
                                        <small>Si el cliente ya esta pagando al reservar, registra aqui la moneda y el monto exacto recibido.</small>
                                    </div>
                                    <div class="row g-3">
                                        <div class="col-md-4">
                                            <label class="form-label" for="create-initial_payment_currency">Moneda en la que paga</label>
                                            <select class="form-select" id="create-initial_payment_currency" name="initial_payment_currency">
                                                @foreach ($supportedCurrencies as $code => $label)
                                                    <option value="{{ $code }}">{{ $code }} - {{ $label }}</option>
                                                @endforeach
                                            </select>
                                            <div class="form-text">Debe coincidir con el dinero recibido: bolivianos, dolares u otra moneda configurada.</div>
                                        </div>
                                        <div class="col-md-4">
                                            <label class="form-label" for="create-initial_payment_amount">Cuanto pagara ahora</label>
                                            <input type="number" class="form-control" id="create-initial_payment_amount" name="initial_payment_amount" min="0" max="999999" step="0.01" value="0" placeholder="0.00">
                                            <div class="form-text" data-initial-payment-helper>Completa la cotizacion para ver el anticipo minimo sugerido.</div>
                                        </div>
                                        <div class="col-md-4">
                                            <label class="form-label" for="create-initial_payment_method">Metodo</label>
                                            <select class="form-select" id="create-initial_payment_method" name="initial_payment_method">
                                                <option value="">Sin pago inicial</option>
                                                @foreach ($paymentMethods as $methodKey => $methodLabel)
                                                    <option value="{{ $methodKey }}">{{ $methodLabel }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label" for="create-initial_payment_reference">Referencia</label>
                                            <input type="text" class="form-control" id="create-initial_payment_reference" name="initial_payment_reference" placeholder="Nro. transaccion, voucher o recibo">
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label" for="create-initial_payment_notes">Nota del pago</label>
                                            <input type="text" class="form-control" id="create-initial_payment_notes" name="initial_payment_notes" placeholder="Ejemplo: anticipo recibido en recepcion">
                                        </div>
                                    </div>
                                </div>
                            </div>
                            </div>
                        </div>

                        <div class="reservation-step-panel" data-step-panel="5">
                            <div class="reservation-step-intro">
                                <span class="reservations-kicker text-primary">Paso 5</span>
                                <h3>Notas y confirmacion</h3>
                                <p>Agrega solicitudes o notas internas. Antes de guardar, revisa que huesped, fechas, habitacion y total esten correctos.</p>
                            </div>
                            <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label" for="create-special_requests">Solicitudes especiales</label>
                                <textarea class="form-control" id="create-special_requests" name="special_requests" rows="3"></textarea>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label" for="create-internal_notes">Notas internas</label>
                                <textarea class="form-control" id="create-internal_notes" name="internal_notes" rows="3"></textarea>
                            </div>
                            <div class="col-12">
                                <div class="reservation-step-final-note">
                                    <i class="bi bi-info-circle"></i>
                                    <div>
                                        <strong>La reserva se guardara como pendiente.</strong>
                                        <span>Despues registra el anticipo desde pagos o desde la misma reserva para confirmarla.</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="button" class="btn btn-outline-primary" data-create-step-prev disabled>
                            <i class="bi bi-arrow-left me-1"></i> Anterior
                        </button>
                        <button type="button" class="btn btn-reservations-primary" data-create-step-next>
                            Siguiente <i class="bi bi-arrow-right ms-1"></i>
                        </button>
                        <button type="submit" class="btn btn-reservations-primary d-none" data-create-step-submit>
                            <i class="bi bi-check2-circle me-1"></i> Guardar reserva
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="modal fade" id="edit-reservation-modal" tabindex="-1" aria-labelledby="edit-reservation-modal-label" aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-scrollable">
            <div class="modal-content">
                <form id="edit-reservation-form" method="POST">
                    @csrf
                    <input type="hidden" name="_method" value="PUT">
                    <div class="modal-header">
                        <div>
                            <span class="reservations-kicker text-primary">Edicion operativa</span>
                            <h5 class="modal-title" id="edit-reservation-modal-label">Editar reserva</h5>
                            <small class="text-muted">Actualiza datos operativos, fechas, habitacion y condiciones comerciales de la reserva.</small>
                        </div>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                    </div>
                    <div class="modal-body">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label" for="edit-customer_id">Cliente / huesped</label>
                                <select class="form-select reservation-customer-select" id="edit-customer_id" name="customer_id" required>
                                    <option value="">Busca por nombre, documento, telefono o correo</option>
                                </select>
                                <div class="form-text">Puedes buscar por nombre, CI/Pasaporte, telefono, WhatsApp o email.</div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label" for="edit-room_id">Habitacion</label>
                                <div class="input-group">
                                    <select class="form-select reservation-room-select" id="edit-room_id" name="room_id" required>
                                        <option value="">Selecciona una habitacion</option>
                                        @foreach ($roomsCatalog as $room)
                                            <option value="{{ $room['id'] }}">{{ $room['label'] }}</option>
                                        @endforeach
                                    </select>
                                    <button type="button" class="btn btn-outline-primary reservation-availability-board-btn" data-open-availability-board data-target-form="edit">
                                        <i class="bi bi-grid-3x3-gap me-1" aria-hidden="true"></i> Ver disponibles
                                    </button>
                                </div>
                                <div class="form-text reservation-availability-message" data-form="edit"></div>
                            </div>

                            <div class="col-md-3">
                                <label class="form-label" for="edit-check_in">Entrada</label>
                                <input type="date" class="form-control reservation-trigger" id="edit-check_in" name="check_in" required>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label" for="edit-check_out">Salida</label>
                                <input type="date" class="form-control reservation-trigger" id="edit-check_out" name="check_out" required>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label" for="edit-adults">Adultos</label>
                                <input type="number" class="form-control reservation-trigger" id="edit-adults" name="adults" min="1" max="20" value="1" required>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label" for="edit-children">Ninos</label>
                                <input type="number" class="form-control reservation-trigger" id="edit-children" name="children" min="0" max="20" value="0">
                            </div>

                            <div class="col-md-6">
                                <label class="form-label" for="edit-promotion_id">Promocion</label>
                                <select class="form-select reservation-trigger" id="edit-promotion_id" name="promotion_id">
                                    <option value="">Sin promocion</option>
                                    @foreach ($promotions as $promotion)
                                        <option value="{{ $promotion->id }}">{{ $promotion->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label" for="edit-status">Estado</label>
                                <select class="form-select" id="edit-status" name="status">
                                    @foreach ($statuses as $statusKey => $statusLabel)
                                        <option value="{{ $statusKey }}">{{ $statusLabel }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label" for="edit-source">Origen</label>
                                <select class="form-select" id="edit-source" name="source">
                                    @foreach ($sources as $sourceKey => $sourceLabel)
                                        <option value="{{ $sourceKey }}">{{ $sourceLabel }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label" for="edit-preferred_payment_method">Preferencia de pago</label>
                                <select class="form-select" id="edit-preferred_payment_method" name="preferred_payment_method">
                                    <option value="">Sin definir</option>
                                    @foreach ($paymentPreferences as $paymentKey => $paymentLabel)
                                        <option value="{{ $paymentKey }}">{{ $paymentLabel }}</option>
                                    @endforeach
                                </select>
                                <div class="form-text">Sirve para ver como desea pagar el cliente antes de registrar el cobro.</div>
                            </div>

                            @if ($canApplyDiscount)
                                <div class="col-md-3">
                                    <label class="form-label" for="edit-discount_type">Descuento manual</label>
                                    <select class="form-select reservation-trigger reservation-discount-type" id="edit-discount_type" name="discount_type">
                                        <option value="">Sin descuento manual</option>
                                        <option value="percentage">Porcentaje</option>
                                        <option value="fixed">Monto fijo</option>
                                    </select>
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label" for="edit-discount_value">Valor descuento</label>
                                    <input type="number" step="0.01" min="0" class="form-control reservation-trigger reservation-discount-value" id="edit-discount_value" name="discount_value" value="">
                                </div>
                            @endif

                            @if ($canChangePrice)
                                <div class="col-md-4">
                                    <label class="form-label" for="edit-base_price">Precio base manual</label>
                                    <div class="input-group reservation-money-input">
                                        <input type="number" step="0.01" min="0" class="form-control reservation-trigger" id="edit-base_price" name="base_price" value="" placeholder="Opcional">
                                        <select class="form-select reservation-trigger reservation-currency-select" name="base_price_currency" aria-label="Moneda del precio base manual">
                                            <option value="BOB">Bs.</option>
                                            <option value="USD">$us</option>
                                        </select>
                                    </div>
                                    <div class="form-text">Las reservas guardan importes internos en bolivianos.</div>
                                </div>
                            @endif

                            <div class="col-12">
                                <div class="reservation-info-card rounded-3 p-3" data-room-summary>
                                    <div class="fw-semibold mb-2">Resumen de habitacion</div>
                                    <div class="text-muted mb-0">Selecciona una habitacion para ver su tipo, precio base y capacidad maxima.</div>
                                </div>
                            </div>

                            <div class="col-12">
                                <div class="reservation-preview-card rounded-3 p-3" data-quote-preview>
                                    <div class="fw-semibold mb-2">Vista previa del costo</div>
                                    <div class="text-muted mb-0">Completa habitacion y fechas para calcular noches, descuento y total final.</div>
                                </div>
                            </div>

                            <div class="col-12">
                                <div class="reservation-initial-payment-card">
                                    <div class="reservation-payment-section-title">
                                        <span>Pago recibido durante la edicion</span>
                                        <small>Si el cliente paga ahora, registra moneda, monto y metodo. Si no paga, deja el monto en 0.</small>
                                    </div>
                                    <div class="row g-3 align-items-end">
                                        <div class="col-md-4">
                                            <label class="form-label" for="edit-initial_payment_currency">Moneda en la que paga</label>
                                            <select class="form-select" id="edit-initial_payment_currency" name="initial_payment_currency">
                                                @foreach ($supportedCurrencies as $code => $label)
                                                    <option value="{{ $code }}">{{ $code }} - {{ $label }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                        <div class="col-md-4">
                                            <label class="form-label" for="edit-initial_payment_amount">Cuanto pagara ahora</label>
                                            <input type="number" class="form-control" id="edit-initial_payment_amount" name="initial_payment_amount" min="0" max="999999" step="0.01" value="0" placeholder="0.00">
                                            <div class="form-text" data-initial-payment-helper>Completa la cotizacion para ver el anticipo minimo sugerido.</div>
                                        </div>
                                        <div class="col-md-4">
                                            <label class="form-label" for="edit-initial_payment_method">Metodo</label>
                                            <select class="form-select" id="edit-initial_payment_method" name="initial_payment_method">
                                                <option value="">Sin pago nuevo</option>
                                                @foreach ($paymentMethods as $code => $label)
                                                    <option value="{{ $code }}">{{ $label }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label" for="edit-initial_payment_reference">Referencia</label>
                                            <input type="text" class="form-control" id="edit-initial_payment_reference" name="initial_payment_reference" placeholder="Nro. transaccion, voucher o recibo">
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label" for="edit-initial_payment_notes">Nota del pago</label>
                                            <input type="text" class="form-control" id="edit-initial_payment_notes" name="initial_payment_notes" placeholder="Ejemplo: anticipo recibido al actualizar la reserva">
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label" for="edit-special_requests">Solicitudes especiales</label>
                                <textarea class="form-control" id="edit-special_requests" name="special_requests" rows="3"></textarea>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label" for="edit-internal_notes">Notas internas</label>
                                <textarea class="form-control" id="edit-internal_notes" name="internal_notes" rows="3"></textarea>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-reservations-primary">Actualizar reserva</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="modal fade" id="availability-board-modal" tabindex="-1" aria-labelledby="availability-board-modal-label" aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header availability-board-header">
                    <div>
                        <span class="reservations-kicker text-primary">Mapa visual</span>
                        <h5 class="modal-title" id="availability-board-modal-label">Habitaciones disponibles</h5>
                        <small class="text-muted" id="availability-board-subtitle">Completa fechas y huespedes para ver opciones reales.</small>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                </div>
                <div class="modal-body">
                    <div class="availability-board-summary" id="availability-board-summary">
                        <div>
                            <span>Disponibles</span>
                            <strong>0</strong>
                            <small>segun fechas y huespedes</small>
                        </div>
                        <div>
                            <span>Rango</span>
                            <strong>-</strong>
                            <small>entrada y salida</small>
                        </div>
                        <div>
                            <span>Huespedes</span>
                            <strong>-</strong>
                            <small>adultos + ninos</small>
                        </div>
                    </div>
                    <div class="availability-board-grid mt-3" id="availability-board-grid">
                        <div class="availability-board-empty">Completa fechas, adultos y ninos para buscar habitaciones disponibles.</div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cerrar</button>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="reservation-payments-modal" tabindex="-1" aria-labelledby="reservation-payments-modal-label" aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header reservation-payments-header">
                    <div>
                        <span class="reservations-kicker text-primary">Pagos y comprobantes</span>
                        <h5 class="modal-title" id="reservation-payments-modal-label">Gestionar pagos de la reserva</h5>
                        <small class="text-muted">Registra pagos, revisa comprobantes y aprueba anticipos desde el flujo de reservas.</small>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                </div>
                <div class="modal-body">
                    <div class="reservation-payment-modal-summary mb-3" id="reservation-payment-modal-summary"></div>

                    <div class="reservation-payment-split">
                        <div>
                            <div class="reservation-payment-section-title">
                                <span>Comprobantes registrados</span>
                                <small>Historial asociado a esta reserva</small>
                            </div>
                            <div class="reservation-payment-list" id="reservation-payment-list"></div>
                        </div>

                        @canany(['pagos.crear', 'pagos.realizar'])
                            <form id="reservation-payment-form" class="reservation-payment-form" action="{{ route('adminlte.payments.store') }}" enctype="multipart/form-data">
                                @csrf
                                <input type="hidden" name="_method" value="POST" id="reservation-payment-form-method">
                                <input type="hidden" id="reservation-payment-editing-id">
                                <input type="hidden" name="reservation_id" id="reservation-payment-reservation-id">

                                <div class="reservation-payment-section-title">
                                    <span id="reservation-payment-form-title">Registrar nuevo pago</span>
                                    <small id="reservation-payment-form-help">Usa esto para pagos manuales o comprobantes recibidos por administracion</small>
                                </div>

                                <div class="row g-3">
                                    <div class="col-md-5">
                                        <label class="form-label" for="reservation-payment-currency">Moneda</label>
                                        <select class="form-select" name="currency" id="reservation-payment-currency" required>
                                            @foreach ($supportedCurrencies as $code => $label)
                                                <option value="{{ $code }}">{{ $code }} - {{ $label }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="col-md-7">
                                        <label class="form-label" for="reservation-payment-amount">Monto que entrega</label>
                                        <input type="number" class="form-control" name="amount" id="reservation-payment-amount" step="0.01" min="0.01" placeholder="0.00" required>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label" for="reservation-payment-method">Metodo</label>
                                        <select class="form-select" name="payment_method" id="reservation-payment-method" required>
                                            @foreach ($paymentMethods as $code => $label)
                                                <option value="{{ $code }}">{{ $label }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label" for="reservation-payment-date">Fecha de pago</label>
                                        <input type="date" class="form-control" name="payment_date" id="reservation-payment-date" value="{{ now()->toDateString() }}" required>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label" for="reservation-payment-receipt">Comprobante</label>
                                        <input type="file" class="form-control" name="receipt_image" id="reservation-payment-receipt" accept=".jpg,.jpeg,.png,.webp,.pdf">
                                        <div class="form-text">JPG, PNG, WEBP o PDF. Maximo 10 MB.</div>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label" for="reservation-payment-reference">Referencia</label>
                                        <input type="text" class="form-control" name="reference_number" id="reservation-payment-reference" placeholder="Codigo de transaccion, voucher o deposito">
                                    </div>
                                    <div class="col-12">
                                        <label class="form-label" for="reservation-payment-notes">Notas internas</label>
                                        <textarea class="form-control" name="notes" id="reservation-payment-notes" rows="3" placeholder="Ejemplo: pago recibido por recepcion, validado con banco, etc."></textarea>
                                    </div>
                                </div>

                                <div class="reservation-payment-form__footer">
                                    <span class="text-muted small">Si el pago es manual, se registra directamente como confirmado segun las reglas actuales del sistema.</span>
                                    <div class="d-flex flex-wrap gap-2 justify-content-end">
                                        <button type="button" class="btn btn-outline-secondary d-none" id="reservation-payment-cancel-edit">
                                            Cancelar edicion
                                        </button>
                                        <button type="submit" class="btn btn-reservations-primary" id="reservation-payment-submit">
                                            <i class="bi bi-cash-coin me-1" aria-hidden="true"></i> Guardar pago
                                        </button>
                                    </div>
                                </div>
                            </form>
                        @else
                            <div class="reservation-payment-form reservation-payment-empty">
                                No tienes permiso para registrar pagos, pero puedes revisar el historial asociado a la reserva.
                            </div>
                        @endcanany
                    </div>
                </div>
            </div>
        </div>
    </div>
@stop

@push('css')
    <link rel="stylesheet" href="{{ asset('vendor/datatables/dataTables.bootstrap5.min.css') }}">
    <link rel="stylesheet" href="{{ asset('vendor/select2/select2.min.css') }}">
    <style>
        .reservations-hero {
            position: relative;
            overflow: hidden;
            border-radius: 1.75rem;
            padding: 1.5rem;
            color: #fff;
            background:
                radial-gradient(circle at top right, rgba(20, 184, 166, 0.46), transparent 34%),
                linear-gradient(135deg, #24104d 0%, #173d67 48%, #0f5132 100%);
            box-shadow: 0 1.5rem 4rem rgba(23, 61, 103, 0.2);
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 1rem;
        }

        .reservations-hero::after {
            content: "";
            position: absolute;
            right: -6rem;
            bottom: -7rem;
            width: 18rem;
            height: 18rem;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.08);
            pointer-events: none;
        }

        .reservations-hero__content {
            position: relative;
            z-index: 1;
            max-width: 810px;
        }

        .reservations-hero h1 {
            margin: 0;
            font-size: clamp(1.8rem, 3vw, 2.75rem);
            font-weight: 850;
            letter-spacing: -0.04em;
        }

        .reservations-hero p {
            max-width: 740px;
            margin: 0.55rem 0 0;
            color: rgba(255, 255, 255, 0.78);
        }

        .reservations-kicker {
            display: inline-flex;
            align-items: center;
            gap: 0.45rem;
            margin-bottom: 0.4rem;
            font-size: 0.72rem;
            font-weight: 850;
            letter-spacing: 0.14em;
            text-transform: uppercase;
            color: rgba(255, 255, 255, 0.72);
        }

        .reservations-kicker::before {
            content: "";
            width: 1.85rem;
            height: 1px;
            background: currentColor;
            opacity: 0.75;
        }

        .btn-reservations-primary {
            position: relative;
            z-index: 1;
            border: 0;
            border-radius: 999px;
            padding: 0.78rem 1.15rem;
            color: #fff;
            font-weight: 850;
            letter-spacing: 0.02em;
            background: linear-gradient(135deg, #14b8a6, #245f9d);
            box-shadow: 0 1rem 2.3rem rgba(36, 95, 157, 0.28);
        }

        .btn-reservations-primary:hover,
        .btn-reservations-primary:focus {
            color: #fff;
            transform: translateY(-1px);
            box-shadow: 0 1.2rem 2.8rem rgba(36, 95, 157, 0.36);
        }

        .reservations-hero__actions {
            position: relative;
            z-index: 1;
            display: flex;
            flex-wrap: wrap;
            justify-content: flex-end;
            gap: 0.7rem;
        }

        .btn-reservations-secondary {
            border: 1px solid rgba(255, 255, 255, 0.28);
            border-radius: 999px;
            padding: 0.78rem 1.15rem;
            color: #fff;
            font-weight: 850;
            letter-spacing: 0.02em;
            background: rgba(255, 255, 255, 0.12);
            backdrop-filter: blur(14px);
            box-shadow: inset 0 1px rgba(255, 255, 255, 0.2), 0 1rem 2.2rem rgba(17, 24, 39, 0.16);
        }

        .btn-reservations-secondary:hover,
        .btn-reservations-secondary:focus {
            color: #fff;
            border-color: rgba(255, 255, 255, 0.44);
            background: rgba(255, 255, 255, 0.2);
            transform: translateY(-1px);
        }

        .reservation-stat {
            min-height: 104px;
            padding: 1.05rem;
            border: 1px solid rgba(17, 24, 39, 0.08);
            border-radius: 1.35rem;
            background: rgba(255, 255, 255, 0.94);
            box-shadow: 0 1rem 2.4rem rgba(17, 24, 39, 0.07);
            display: flex;
            align-items: center;
            gap: 0.95rem;
        }

        .reservation-stat__icon {
            width: 3.05rem;
            height: 3.05rem;
            border-radius: 1rem;
            color: #fff;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 1.35rem;
            box-shadow: inset 0 1px rgba(255, 255, 255, 0.2), 0 0.8rem 1.5rem rgba(17, 24, 39, 0.13);
        }

        .reservation-stat small {
            display: block;
            color: #6b7280;
            font-weight: 800;
            letter-spacing: 0.03em;
            text-transform: uppercase;
        }

        .reservation-stat strong {
            display: block;
            color: #161827;
            font-size: 1.45rem;
            line-height: 1.1;
        }

        .bg-gradient-indigo { background: linear-gradient(135deg, #245f9d, #173d67); }
        .bg-gradient-green { background: linear-gradient(135deg, #14b8a6, #0f766e); }
        .bg-gradient-gold { background: linear-gradient(135deg, #df941b, #8a5610); }
        .bg-gradient-copper { background: linear-gradient(135deg, #7c3aed, #4c1d95); }

        .reservations-panel {
            overflow: hidden;
            border: 1px solid rgba(17, 24, 39, 0.08);
            border-radius: 1.5rem;
            background: #fff;
            box-shadow: 0 1.5rem 4rem rgba(17, 24, 39, 0.08);
        }

        .reservations-panel__header {
            padding: 1.35rem 1.5rem;
            border-bottom: 1px solid rgba(17, 24, 39, 0.08);
            background:
                linear-gradient(135deg, rgba(36, 95, 157, 0.08), rgba(20, 184, 166, 0.08)),
                #fff;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 1rem;
        }

        .reservations-panel__header h2 {
            margin: 0;
            color: #161827;
            font-size: 1.25rem;
            font-weight: 850;
        }

        .reservations-panel__header p {
            margin: 0.25rem 0 0;
            color: #6b7280;
        }

        .reservations-panel__balances {
            display: grid;
            grid-template-columns: repeat(2, minmax(180px, 1fr));
            gap: 0.75rem;
            min-width: min(440px, 100%);
        }

        .reservations-panel__summary {
            padding: 0.85rem 1rem;
            border: 1px solid rgba(36, 95, 157, 0.12);
            border-radius: 1rem;
            background: rgba(255, 255, 255, 0.72);
            text-align: right;
        }

        .reservations-panel__summary--usd {
            border-color: rgba(20, 184, 166, 0.18);
            background: linear-gradient(135deg, rgba(20, 184, 166, 0.08), rgba(255, 255, 255, 0.82));
        }

        .reservations-panel__summary span,
        .reservations-panel__summary small {
            display: block;
            color: #6b7280;
            font-size: 0.75rem;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.08em;
        }

        .reservations-panel__summary strong {
            color: #173d67;
            font-size: 1.25rem;
        }

        #reservations-table {
            margin: 0 !important;
        }

        #reservations-table thead th {
            padding-top: 1rem;
            padding-bottom: 1rem;
            border-bottom: 1px solid rgba(17, 24, 39, 0.08);
            color: #6b7280;
            font-size: 0.74rem;
            font-weight: 850;
            letter-spacing: 0.08em;
            text-transform: uppercase;
            background: #fbfaf8;
        }

        #reservations-table tbody td {
            padding-top: 1rem;
            padding-bottom: 1rem;
            border-color: rgba(17, 24, 39, 0.06);
        }

        #reservations-table tbody tr:hover {
            background: rgba(36, 95, 157, 0.035);
        }

        .reservation-code {
            color: #173d67;
            font-weight: 900;
            letter-spacing: 0.02em;
        }

        .reservation-muted {
            color: #6b7280;
            font-size: 0.82rem;
        }

        .reservation-lockup {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            min-width: 190px;
        }

        .reservation-avatar {
            width: 2.75rem;
            height: 2.75rem;
            border-radius: 0.95rem;
            color: #fff;
            background: linear-gradient(135deg, #173d67, #14b8a6);
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-weight: 900;
            box-shadow: 0 0.8rem 1.5rem rgba(23, 61, 103, 0.18);
        }

        .reservation-pill {
            display: inline-flex;
            align-items: center;
            gap: 0.35rem;
            padding: 0.38rem 0.7rem;
            border-radius: 999px;
            font-size: 0.78rem;
            font-weight: 850;
        }

        .reservation-pill--room {
            color: #173d67;
            background: rgba(36, 95, 157, 0.1);
        }

        .reservation-pill--paid {
            color: #0f766e;
            background: rgba(20, 184, 166, 0.12);
        }

        .reservation-pill--pending {
            color: #7c2d12;
            background: rgba(245, 158, 11, 0.15);
        }

        .reservation-availability-board-btn {
            min-width: 150px;
            font-weight: 800;
            white-space: nowrap;
        }

        .availability-board-header {
            background:
                radial-gradient(circle at top right, rgba(20, 184, 166, 0.16), transparent 34%),
                linear-gradient(135deg, rgba(36, 95, 157, 0.08), rgba(255, 255, 255, 0.96));
        }

        .availability-board-summary {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 1rem;
        }

        .availability-board-summary > div {
            padding: 1rem;
            border: 1px solid rgba(17, 24, 39, 0.08);
            border-radius: 1.15rem;
            background: #fff;
            box-shadow: 0 0.9rem 2rem rgba(17, 24, 39, 0.06);
        }

        .availability-board-summary span,
        .availability-board-summary small {
            display: block;
            color: #6b7280;
            font-size: 0.72rem;
            font-weight: 850;
            letter-spacing: 0.08em;
            text-transform: uppercase;
        }

        .availability-board-summary strong {
            display: block;
            color: #173d67;
            font-size: 1.35rem;
            line-height: 1.2;
        }

        .availability-board-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(230px, 1fr));
            gap: 1rem;
        }

        .availability-room-card {
            position: relative;
            overflow: hidden;
            min-height: 245px;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            gap: 1rem;
            padding: 1rem;
            border: 1px solid rgba(17, 24, 39, 0.08);
            border-radius: 1.35rem;
            background:
                radial-gradient(circle at top right, rgba(20, 184, 166, 0.16), transparent 40%),
                linear-gradient(145deg, #ffffff, #f8fbfc);
            box-shadow: 0 1.2rem 2.8rem rgba(17, 24, 39, 0.08);
            transition: transform 0.18s ease, border-color 0.18s ease, box-shadow 0.18s ease;
        }

        .availability-room-card:hover {
            border-color: rgba(36, 95, 157, 0.28);
            box-shadow: 0 1.4rem 3rem rgba(36, 95, 157, 0.14);
            transform: translateY(-2px);
        }

        .availability-room-number {
            width: 4.4rem;
            height: 4.4rem;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border-radius: 1.3rem;
            color: #fff;
            background: linear-gradient(135deg, #173d67, #14b8a6);
            font-size: 1.45rem;
            font-weight: 950;
            box-shadow: 0 1rem 2rem rgba(23, 61, 103, 0.22);
        }

        .availability-room-card h4 {
            margin: 0;
            color: #161827;
            font-size: 1.1rem;
            font-weight: 900;
        }

        .availability-room-meta {
            display: flex;
            flex-wrap: wrap;
            gap: 0.45rem;
        }

        .availability-room-meta span {
            display: inline-flex;
            align-items: center;
            gap: 0.35rem;
            padding: 0.38rem 0.62rem;
            border-radius: 999px;
            color: #173d67;
            background: rgba(36, 95, 157, 0.09);
            font-size: 0.78rem;
            font-weight: 800;
        }

        .availability-room-price {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 0.5rem;
        }

        .availability-room-price div {
            padding: 0.68rem;
            border-radius: 0.9rem;
            background: rgba(255, 255, 255, 0.8);
            border: 1px solid rgba(17, 24, 39, 0.06);
        }

        .availability-room-price small {
            display: block;
            color: #6b7280;
            font-size: 0.68rem;
            font-weight: 850;
            text-transform: uppercase;
        }

        .availability-room-price strong {
            color: #161827;
            font-size: 0.98rem;
        }

        .availability-room-visual {
            display: grid;
            gap: 0.65rem;
            margin: 1rem 0;
            padding: 0.85rem;
            border: 1px solid rgba(17, 24, 39, 0.07);
            border-radius: 1rem;
            background: linear-gradient(135deg, rgba(15, 23, 42, 0.04), rgba(20, 184, 166, 0.08));
        }

        .availability-room-bar-label {
            display: flex;
            justify-content: space-between;
            gap: 1rem;
            color: #536171;
            font-size: 0.68rem;
            font-weight: 900;
            letter-spacing: 0.06em;
            text-transform: uppercase;
        }

        .availability-room-bar {
            height: 0.52rem;
            overflow: hidden;
            border-radius: 999px;
            background: rgba(148, 163, 184, 0.25);
        }

        .availability-room-bar span {
            display: block;
            height: 100%;
            border-radius: inherit;
            background: linear-gradient(90deg, #245f9d, #14b8a6);
        }

        .availability-room-bar--deposit span {
            background: linear-gradient(90deg, #c78211, #ef5a3c);
        }

        .availability-board-empty {
            grid-column: 1 / -1;
            padding: 1.2rem;
            border: 1px dashed rgba(36, 95, 157, 0.28);
            border-radius: 1.15rem;
            color: #6b7280;
            background: rgba(36, 95, 157, 0.04);
            text-align: center;
        }

        .reservation-agenda-header {
            background:
                radial-gradient(circle at top right, rgba(20, 184, 166, 0.16), transparent 36%),
                linear-gradient(135deg, #ffffff, #f8fbfc);
        }

        .reservation-agenda-toolbar {
            display: grid;
            grid-template-columns: minmax(150px, 190px) minmax(150px, 190px) 1fr;
            gap: 0.9rem;
            align-items: end;
            padding: 1rem;
            border: 1px solid rgba(17, 24, 39, 0.08);
            border-radius: 1.25rem;
            background: rgba(248, 250, 252, 0.92);
            box-shadow: 0 1rem 2.2rem rgba(17, 24, 39, 0.06);
        }

        .reservation-agenda-toolbar__actions {
            display: flex;
            flex-wrap: wrap;
            justify-content: flex-end;
            gap: 0.55rem;
        }

        .reservation-agenda-summary {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: 0.75rem;
            margin: 1rem 0;
        }

        .reservation-agenda-summary > div {
            padding: 0.9rem;
            border: 1px solid rgba(17, 24, 39, 0.08);
            border-radius: 1.05rem;
            background: #fff;
            box-shadow: 0 0.8rem 1.8rem rgba(17, 24, 39, 0.06);
        }

        .reservation-agenda-summary span,
        .reservation-agenda-summary small {
            display: block;
            color: #64748b;
            font-size: 0.72rem;
            font-weight: 850;
            letter-spacing: 0.07em;
            text-transform: uppercase;
        }

        .reservation-agenda-summary strong {
            display: block;
            color: #173d67;
            font-size: 1.35rem;
            line-height: 1.2;
        }

        .reservation-agenda-grid {
            display: grid;
            gap: 0.9rem;
        }

        .reservation-agenda-card {
            position: relative;
            overflow: hidden;
            display: grid;
            grid-template-columns: minmax(150px, 190px) 1fr minmax(170px, 230px);
            gap: 1rem;
            padding: 1rem;
            border: 1px solid rgba(17, 24, 39, 0.08);
            border-radius: 1.25rem;
            background:
                radial-gradient(circle at top left, rgba(36, 95, 157, 0.11), transparent 34%),
                #fff;
            box-shadow: 0 1rem 2.5rem rgba(17, 24, 39, 0.07);
        }

        .reservation-agenda-card::before {
            content: "";
            position: absolute;
            inset-block: 0;
            left: 0;
            width: 0.35rem;
            background: linear-gradient(180deg, #245f9d, #14b8a6);
        }

        .reservation-agenda-date {
            padding-left: 0.35rem;
        }

        .reservation-agenda-date strong {
            display: block;
            color: #161827;
            font-size: 1rem;
        }

        .reservation-agenda-date span,
        .reservation-agenda-card small {
            display: block;
            color: #64748b;
            font-size: 0.78rem;
            font-weight: 750;
        }

        .reservation-agenda-room {
            display: inline-flex;
            align-items: center;
            gap: 0.45rem;
            width: fit-content;
            margin-bottom: 0.4rem;
            padding: 0.42rem 0.7rem;
            border-radius: 999px;
            color: #173d67;
            background: rgba(36, 95, 157, 0.09);
            font-weight: 850;
        }

        .reservation-agenda-customer {
            margin: 0;
            color: #161827;
            font-size: 1.08rem;
            font-weight: 900;
        }

        .reservation-agenda-meta {
            display: flex;
            flex-wrap: wrap;
            gap: 0.45rem;
            margin-top: 0.65rem;
        }

        .reservation-agenda-meta span {
            display: inline-flex;
            align-items: center;
            gap: 0.35rem;
            padding: 0.35rem 0.55rem;
            border-radius: 999px;
            color: #334155;
            background: #f1f5f9;
            font-size: 0.75rem;
            font-weight: 800;
        }

        .reservation-agenda-money {
            display: grid;
            gap: 0.5rem;
        }

        .reservation-agenda-money div {
            padding: 0.65rem;
            border-radius: 0.9rem;
            background: rgba(248, 250, 252, 0.96);
            border: 1px solid rgba(17, 24, 39, 0.06);
        }

        .reservation-agenda-money small,
        .reservation-agenda-money strong {
            display: block;
        }

        .reservation-agenda-money strong {
            color: #161827;
        }

        .reservation-agenda-empty {
            padding: 2rem;
            border: 1px dashed rgba(36, 95, 157, 0.28);
            border-radius: 1.25rem;
            color: #64748b;
            background: rgba(36, 95, 157, 0.04);
            text-align: center;
        }

        .reservation-payment-bar {
            height: 0.45rem;
            overflow: hidden;
            border-radius: 999px;
            background: #eef2f7;
        }

        .reservation-payment-bar span {
            display: block;
            height: 100%;
            border-radius: inherit;
            background: linear-gradient(135deg, #14b8a6, #245f9d);
        }

        .reservation-actions {
            display: inline-flex;
            flex-wrap: wrap;
            justify-content: end;
            gap: 0.45rem;
        }

        .reservation-action-btn {
            width: 2.25rem;
            height: 2.25rem;
            border-radius: 0.8rem;
            display: inline-flex;
            align-items: center;
            justify-content: center;
        }

        #create-reservation-modal .modal-dialog,
        #edit-reservation-modal .modal-dialog,
        #availability-board-modal .modal-dialog,
        #reservation-payments-modal .modal-dialog {
            height: calc(100vh - 3.5rem);
        }

        #create-reservation-modal .modal-content,
        #edit-reservation-modal .modal-content,
        #availability-board-modal .modal-content,
        #reservation-payments-modal .modal-content {
            max-height: 100%;
            overflow: hidden;
            border: 0;
            border-radius: 1.5rem;
            box-shadow: 0 2rem 5rem rgba(17, 24, 39, 0.22);
        }

        #create-reservation-modal form,
        #edit-reservation-modal form {
            display: flex;
            flex-direction: column;
            min-height: 0;
            height: 100%;
        }

        #create-reservation-modal .modal-body,
        #edit-reservation-modal .modal-body,
        #availability-board-modal .modal-body,
        #reservation-payments-modal .modal-body {
            min-height: 0;
            overflow-y: auto;
            overscroll-behavior: contain;
            background: #fbfaf8;
        }

        #create-reservation-modal .modal-header,
        #edit-reservation-modal .modal-header,
        #create-reservation-modal .modal-footer,
        #edit-reservation-modal .modal-footer {
            background: #fff;
            border-color: rgba(17, 24, 39, 0.08);
        }

        .reservation-stepper {
            display: grid;
            grid-template-columns: repeat(5, minmax(0, 1fr));
            gap: 0.75rem;
            margin-bottom: 1rem;
            padding: 0.75rem;
            border: 1px solid rgba(36, 95, 157, 0.1);
            border-radius: 1.25rem;
            background: linear-gradient(135deg, rgba(36, 95, 157, 0.06), rgba(20, 184, 166, 0.06));
        }

        .reservation-stepper__item {
            border: 1px solid rgba(17, 24, 39, 0.08);
            border-radius: 1rem;
            padding: 0.8rem;
            background: rgba(255, 255, 255, 0.86);
            text-align: left;
            transition: transform 0.2s ease, border-color 0.2s ease, box-shadow 0.2s ease;
        }

        .reservation-stepper__item span {
            width: 2rem;
            height: 2rem;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 0.55rem;
            border-radius: 999px;
            color: #245f9d;
            background: rgba(36, 95, 157, 0.1);
            font-weight: 900;
        }

        .reservation-stepper__item strong,
        .reservation-stepper__item small {
            display: block;
        }

        .reservation-stepper__item strong {
            color: #161827;
            font-size: 0.92rem;
        }

        .reservation-stepper__item small {
            color: #64748b;
            font-size: 0.72rem;
        }

        .reservation-stepper__item.is-active {
            border-color: rgba(36, 95, 157, 0.42);
            box-shadow: 0 0.8rem 1.7rem rgba(36, 95, 157, 0.12);
            transform: translateY(-1px);
        }

        .reservation-stepper__item.is-active span {
            color: #fff;
            background: linear-gradient(135deg, #245f9d, #14b8a6);
        }

        .reservation-stepper__item.is-complete span {
            color: #fff;
            background: #16a34a;
        }

        .reservation-step-panel {
            display: none;
            padding: 1.1rem;
            border: 1px solid rgba(17, 24, 39, 0.08);
            border-radius: 1.25rem;
            background: rgba(255, 255, 255, 0.9);
            box-shadow: 0 1rem 2.5rem rgba(17, 24, 39, 0.05);
        }

        .reservation-step-panel.is-active {
            display: block;
            animation: reservationStepIn 0.24s ease both;
        }

        .reservation-step-intro {
            margin-bottom: 1rem;
            padding-bottom: 0.9rem;
            border-bottom: 1px solid rgba(17, 24, 39, 0.08);
        }

        .reservation-step-intro h3 {
            margin: 0.1rem 0 0.35rem;
            color: #161827;
            font-weight: 900;
        }

        .reservation-step-intro p {
            max-width: 760px;
            margin: 0;
            color: #64748b;
        }

        .reservation-step-final-note {
            display: flex;
            gap: 0.8rem;
            align-items: flex-start;
            padding: 1rem;
            border-radius: 1rem;
            color: #173d67;
            background: rgba(36, 95, 157, 0.08);
        }

        .reservation-step-final-note i {
            font-size: 1.25rem;
        }

        .reservation-step-final-note strong,
        .reservation-step-final-note span {
            display: block;
        }

        .reservation-initial-payment-card {
            padding: 1rem;
            border: 1px solid rgba(22, 163, 74, 0.16);
            border-radius: 1.15rem;
            background:
                radial-gradient(circle at top right, rgba(22, 163, 74, 0.08), transparent 34%),
                linear-gradient(135deg, rgba(240, 253, 244, 0.78), rgba(255, 255, 255, 0.96));
            box-shadow: 0 1rem 2.2rem rgba(22, 163, 74, 0.06);
        }

        .reservation-initial-payment-card .reservation-payment-section-title {
            margin-bottom: 0.9rem;
        }

        @keyframes reservationStepIn {
            from {
                opacity: 0;
                transform: translateY(8px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .reservation-preview-card,
        .reservation-info-card {
            border: 1px dashed rgba(36, 95, 157, 0.18);
            background:
                linear-gradient(135deg, rgba(36, 95, 157, 0.06), rgba(20, 184, 166, 0.08)),
                #fff;
            box-shadow: 0 0.75rem 1.8rem rgba(17, 24, 39, 0.04);
        }

        #create-reservation-modal .form-label,
        #edit-reservation-modal .form-label {
            color: #374151;
            font-size: 0.82rem;
            font-weight: 850;
        }

        #create-reservation-modal .form-control,
        #create-reservation-modal .form-select,
        #edit-reservation-modal .form-control,
        #edit-reservation-modal .form-select {
            border-radius: 0.85rem;
        }

        .reservation-money-input .form-control {
            min-width: 0;
        }

        .reservation-money-input .reservation-currency-select {
            max-width: 6.5rem;
            flex: 0 0 6.5rem;
            font-weight: 850;
        }

        .select2-container--default .select2-selection--single,
        .select2-container--bootstrap-5 .select2-selection {
            min-height: 38px;
            border-radius: 0.85rem;
            border-color: #dee2e6;
        }

        .select2-container--default .select2-selection--single .select2-selection__rendered,
        .select2-container--bootstrap-5 .select2-selection--single .select2-selection__rendered {
            padding-top: 0.22rem;
            color: #374151;
        }

        .select2-container--default .select2-selection--single .select2-selection__arrow {
            min-height: 38px;
        }

        .select2-dropdown {
            z-index: 2000;
            border-color: rgba(75, 85, 99, 0.55);
            border-radius: 0.95rem;
            overflow: hidden;
            background: #2f3542;
            box-shadow: 0 1rem 2.5rem rgba(17, 24, 39, 0.28);
        }

        .select2-container--default .select2-search--dropdown .select2-search__field {
            border-color: rgba(255, 255, 255, 0.18);
            border-radius: 0.7rem;
            color: #f8fafc;
            background: #1f2937;
            outline: none;
        }

        .select2-container--default .select2-results__option {
            color: #e5e7eb;
            padding: 0.65rem 0.85rem;
        }

        .select2-container--default .select2-results__option--highlighted.select2-results__option--selectable {
            color: #fff;
            background: #4b5563;
        }

        .select2-container--default .select2-results__option--selected {
            color: #fff;
            background: #374151;
        }

        .reservation-customer-option {
            display: grid;
            gap: 0.2rem;
            padding: 0.15rem 0;
        }

        .reservation-customer-option strong {
            color: #f8fafc;
            font-weight: 850;
        }

        .reservation-customer-option small {
            display: flex;
            flex-wrap: wrap;
            gap: 0.7rem;
            color: #cbd5e1;
            font-size: 0.78rem;
        }

        .reservation-payments-header {
            background: linear-gradient(135deg, #ffffff, #f8fafc);
            border-color: rgba(17, 24, 39, 0.08);
        }

        .reservation-payment-modal-summary {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: 0.85rem;
        }

        .reservation-payment-modal-summary > div,
        .reservation-payment-form,
        .reservation-payment-card {
            border: 1px solid rgba(17, 24, 39, 0.08);
            border-radius: 1.2rem;
            background: rgba(255, 255, 255, 0.94);
            box-shadow: 0 1rem 2.5rem rgba(17, 24, 39, 0.06);
        }

        .reservation-payment-modal-summary > div {
            padding: 1rem;
        }

        .reservation-payment-modal-summary span,
        .reservation-payment-section-title small,
        .reservation-payment-card small {
            display: block;
            color: #64748b;
            font-size: 0.78rem;
        }

        .reservation-payment-modal-summary strong {
            display: block;
            margin-top: 0.25rem;
            color: #111827;
            font-size: 1.05rem;
        }

        .reservation-payment-split {
            display: grid;
            grid-template-columns: minmax(0, 1.1fr) minmax(340px, 0.9fr);
            gap: 1rem;
            align-items: start;
        }

        .reservation-payment-section-title {
            display: flex;
            align-items: end;
            justify-content: space-between;
            gap: 1rem;
            margin-bottom: 0.75rem;
        }

        .reservation-payment-section-title span {
            color: #111827;
            font-weight: 850;
        }

        .reservation-payment-list {
            display: grid;
            gap: 0.75rem;
        }

        .reservation-payment-card {
            padding: 1rem;
        }

        .reservation-payment-card__top,
        .reservation-payment-card__actions {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 0.75rem;
            flex-wrap: wrap;
        }

        .reservation-payment-card__amount {
            color: #111827;
            font-size: 1.35rem;
            font-weight: 900;
            letter-spacing: -0.03em;
        }

        .reservation-payment-card__meta {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 0.55rem;
            margin: 0.85rem 0;
        }

        .reservation-payment-card__meta div {
            border-radius: 0.85rem;
            padding: 0.65rem;
            background: #f8fafc;
            color: #334155;
            font-size: 0.82rem;
        }

        .reservation-payment-form {
            padding: 1rem;
        }

        .reservation-payment-form .form-label {
            color: #374151;
            font-size: 0.8rem;
            font-weight: 850;
        }

        .reservation-payment-form .form-control,
        .reservation-payment-form .form-select {
            border-radius: 0.85rem;
        }

        .reservation-payment-form__footer {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 1rem;
            margin-top: 1rem;
        }

        .reservation-payment-empty {
            padding: 1.25rem;
            color: #64748b;
            text-align: center;
        }

        @media (max-width: 767.98px) {
            .reservations-hero,
            .reservations-panel__header {
                align-items: stretch;
                flex-direction: column;
            }

            .btn-reservations-primary {
                width: 100%;
            }

            .reservations-panel__balances,
            .reservations-panel__summary {
                min-width: 0;
                text-align: left;
            }

            .reservations-panel__balances {
                grid-template-columns: 1fr;
            }

            .reservations-hero__actions,
            .reservations-hero__actions .btn {
                width: 100%;
            }

            .reservation-agenda-toolbar,
            .reservation-agenda-summary,
            .reservation-agenda-card,
            .reservation-payment-modal-summary,
            .reservation-payment-split,
            .reservation-payment-card__meta {
                grid-template-columns: 1fr;
            }

            .reservation-payment-form__footer {
                align-items: stretch;
                flex-direction: column;
            }

            .reservation-agenda-toolbar__actions {
                justify-content: stretch;
            }

            .reservation-agenda-toolbar__actions .btn {
                flex: 1 1 140px;
            }

            .reservation-stepper {
                grid-template-columns: 1fr;
            }

            .reservation-stepper__item {
                display: grid;
                grid-template-columns: auto 1fr;
                column-gap: 0.75rem;
                align-items: center;
            }

            .reservation-stepper__item span {
                grid-row: span 2;
                margin-bottom: 0;
            }

            .availability-board-summary,
            .availability-room-price {
                grid-template-columns: 1fr;
            }

            .reservation-availability-board-btn {
                width: 100%;
            }
        }
    </style>
@endpush

@push('js')
    <script src="{{ asset('vendor/jquery/jquery.min.js') }}"></script>
    <script src="{{ asset('vendor/datatables/dataTables.min.js') }}"></script>
    <script src="{{ asset('vendor/datatables/dataTables.bootstrap5.min.js') }}"></script>
    <script src="{{ asset('vendor/select2/select2.full.min.js') }}"></script>
    <script>
        window.addEventListener('load', async () => {
            let $ = window.jQuery || window.$;
            const swal = window.Swal;
            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content ?? '';
            const quoteUrl = '{{ route('adminlte.reservations.quote') }}';
            const availableRoomsUrl = '{{ route('adminlte.reservations.available-rooms') }}';
            const customerSearchUrl = '{{ route('adminlte.reservations.customer-search') }}';
            const reservationAgendaUrl = '{{ route('adminlte.reservations.agenda') }}';
            const paymentStoreUrl = '{{ route('adminlte.payments.store') }}';
            const requiresOpenCashRegister = @json($requiresOpenCashRegister);
            const hasOpenCashRegister = @json($hasOpenCashRegister);
            const openCashRegisterUrl = @json(route('adminlte.cash-registers.index'));
            const roomsCatalog = @json($roomsCatalog);
            const promotionsCatalog = @json($promotionsCatalog);
            const currencySymbols = @json($currencySymbols ?? []);
            const createForm = document.getElementById('create-reservation-form');
            const editForm = document.getElementById('edit-reservation-form');
            const createModal = window.bootstrap ? new window.bootstrap.Modal(document.getElementById('create-reservation-modal')) : null;
            const editModal = window.bootstrap ? new window.bootstrap.Modal(document.getElementById('edit-reservation-modal')) : null;
            const reservationPaymentsModalElement = document.getElementById('reservation-payments-modal');
            const reservationPaymentsModal = window.bootstrap && reservationPaymentsModalElement ? new window.bootstrap.Modal(reservationPaymentsModalElement) : null;
            const reservationPaymentForm = document.getElementById('reservation-payment-form');
            const reservationPaymentSummary = document.getElementById('reservation-payment-modal-summary');
            const reservationPaymentList = document.getElementById('reservation-payment-list');
            const reservationPaymentReservationId = document.getElementById('reservation-payment-reservation-id');
            const reservationPaymentFormMethod = document.getElementById('reservation-payment-form-method');
            const reservationPaymentEditingId = document.getElementById('reservation-payment-editing-id');
            const reservationPaymentFormTitle = document.getElementById('reservation-payment-form-title');
            const reservationPaymentFormHelp = document.getElementById('reservation-payment-form-help');
            const reservationPaymentSubmit = document.getElementById('reservation-payment-submit');
            const reservationPaymentCancelEdit = document.getElementById('reservation-payment-cancel-edit');
            const reservationAgendaModalElement = document.getElementById('reservation-agenda-modal');
            const reservationAgendaModal = window.bootstrap && reservationAgendaModalElement ? new window.bootstrap.Modal(reservationAgendaModalElement) : null;
            const agendaDateFrom = document.getElementById('agenda-date-from');
            const agendaDateTo = document.getElementById('agenda-date-to');
            const reservationAgendaGrid = document.getElementById('reservation-agenda-grid');
            const reservationAgendaSummary = document.getElementById('reservation-agenda-summary');
            const availabilityBoardModalElement = document.getElementById('availability-board-modal');
            const availabilityBoardModal = window.bootstrap && availabilityBoardModalElement ? new window.bootstrap.Modal(availabilityBoardModalElement) : null;
            const availabilityBoardGrid = document.getElementById('availability-board-grid');
            const availabilityBoardSummary = document.getElementById('availability-board-summary');
            const availabilityBoardSubtitle = document.getElementById('availability-board-subtitle');
            const roomsMap = new Map(roomsCatalog.map((room) => [String(room.id), room]));
            const promotionsMap = new Map(promotionsCatalog.map((promotion) => [String(promotion.id), promotion]));
            const quoteTimers = new WeakMap();
            const quotePayloads = new WeakMap();
            let availabilityBoardForm = null;
            let activePaymentReservation = null;
            let createReservationStep = 1;

            if (typeof $ !== 'function') {
                await loadScript('{{ asset('vendor/jquery/jquery.min.js') }}');
                $ = window.jQuery || window.$;
            }

            if (typeof $ !== 'function') {
                console.error('jQuery no esta disponible para DataTables.');
                return;
            }

            if (typeof $.fn.DataTable !== 'function') {
                await loadScript('{{ asset('vendor/datatables/dataTables.min.js') }}', true);
                await loadScript('{{ asset('vendor/datatables/dataTables.bootstrap5.min.js') }}', true);
            }

            if (typeof $.fn.select2 !== 'function') {
                await loadScript('{{ asset('vendor/select2/select2.full.min.js') }}', true);
            }

            initializeCustomerSelects();

            window.reservationsTable = $('#reservations-table').DataTable({
                processing: true,
                serverSide: true,
                ajax: '{{ route('adminlte.reservations.data') }}',
                language: {
                    url: 'https://cdn.datatables.net/plug-ins/1.13.8/i18n/es-ES.json',
                },
                order: [[8, 'desc']],
                columns: [
                    {
                        data: 'code',
                        name: 'code',
                        render: (data, type, row) => {
                            if (type !== 'display') {
                                return data;
                            }

                            const paymentPreference = row.preferred_payment_method_label
                                ? `<div class="small text-muted">Pago: ${row.preferred_payment_method_label}</div>`
                                : '';

                            return `<div class="reservation-code">${row.code}</div><div class="reservation-muted"><i class="bi bi-signpost me-1"></i>${row.source_label}</div>${paymentPreference}`;
                        }
                    },
                    {
                        data: 'customer_name',
                        name: 'customer.full_name',
                        render: (data, type, row) => {
                            if (type !== 'display') {
                                return data;
                            }

                            const initial = (row.customer_name || '?').trim().charAt(0).toUpperCase();
                            const document = row.customer_document ? `<div class="reservation-muted">${row.customer_document}</div>` : '';
                            return `<div class="reservation-lockup"><span class="reservation-avatar">${initial}</span><div><div class="fw-semibold">${row.customer_name}</div>${document}</div></div>`;
                        }
                    },
                    {
                        data: 'room_number',
                        name: 'room.number',
                        render: (data, type, row) => {
                            if (type !== 'display') {
                                return data;
                            }

                            return `<span class="reservation-pill reservation-pill--room"><i class="bi bi-door-open"></i>Hab. ${row.room_number}</span><div class="reservation-muted mt-1">${row.room_type_name}</div>`;
                        }
                    },
                    {
                        data: 'check_in',
                        name: 'check_in',
                        render: (data, type, row) => {
                            if (type !== 'display') {
                                return `${row.check_in} ${row.check_out}`;
                            }

                            return `<div class="fw-semibold">${row.check_in_formatted} - ${row.check_out_formatted}</div><div class="reservation-muted"><i class="bi bi-moon-stars me-1"></i>${row.nights} noche(s)</div>`;
                        }
                    },
                    { data: 'guests_summary', name: 'adults', className: 'text-nowrap' },
                    {
                        data: 'total_amount',
                        name: 'total_amount',
                        render: (data, type, row) => {
                            if (type !== 'display') {
                                return data;
                            }

                            const discount = row.discount_label ? `<div class="small text-muted">${row.discount_label}</div>` : '';
                            return `<div class="fw-semibold">${row.display_total_amount_formatted || row.total_amount_formatted}</div>
                                <div class="reservation-muted">${row.display_price_per_night_formatted || row.price_per_night_formatted} / noche</div>
                                <div class="reservation-muted">Moneda: ${row.display_currency || 'BOB'}</div>
                                ${discount}`;
                        }
                    },
                    {
                        data: 'balance_amount',
                        name: 'balance_amount',
                        render: (data, type, row) => {
                            if (type !== 'display') {
                                return data;
                            }

                            const badge = Number(row.balance_amount) > 0
                                ? '<span class="reservation-pill reservation-pill--pending"><i class="bi bi-exclamation-circle"></i>Pendiente</span>'
                                : '<span class="reservation-pill reservation-pill--paid"><i class="bi bi-check2-circle"></i>Pagado</span>';
                            const width = Number(row.payment_progress_percentage || 0);
                            const paymentInfo = Number(row.pending_payments_count || 0) > 0
                                ? `<div class="small text-warning fw-semibold mt-1">${row.pending_payments_count} comprobante(s) por revisar</div>`
                                : `<div class="small text-muted mt-1">${row.payments_count || 0} pago(s) registrado(s)</div>`;

                            return `<div class="fw-semibold">${row.display_balance_amount_formatted || row.balance_amount_formatted}</div>
                                <div class="reservation-payment-bar mt-2"><span style="width: ${width}%"></span></div>
                                <div class="reservation-muted mt-1">Pagado: ${row.display_paid_amount_formatted || row.paid_amount_formatted}</div>
                                <div class="reservation-muted">Anticipo: ${row.display_deposit_summary_label || row.deposit_summary_label}</div>
                                <div class="mt-1">${badge}</div>${paymentInfo}`;
                        }
                    },
                    {
                        data: 'status',
                        name: 'status',
                        className: 'text-center',
                        render: (data, type, row) => type === 'display'
                            ? `<span class="${row.status_badge_class}"><i class="bi ${row.status_icon} me-1"></i>${row.status_label}</span>`
                            : data
                    },
                    { data: 'created_at_formatted', name: 'created_at' },
                    {
                        data: null,
                        orderable: false,
                        searchable: false,
                        className: 'text-end',
                        render: (data, type, row) => {
                            if (type !== 'display') {
                                return '';
                            }

                            let actions = '<div class="reservation-actions" role="group">';

                            actions += `<button type="button" class="btn btn-outline-dark reservation-action-btn reservation-payments-btn" data-reservation="${encodeURIComponent(JSON.stringify(row))}" title="Pagos y comprobantes">
                                <i class="bi bi-receipt-cutoff"></i>
                            </button>`;

                            if (row.can_update) {
                                actions += `<button type="button" class="btn btn-outline-primary reservation-action-btn reservation-edit-btn" data-reservation="${encodeURIComponent(JSON.stringify(row))}" title="Editar">
                                    <i class="bi bi-pencil-square"></i>
                                </button>`;
                            }

                            if (row.can_confirm) {
                                actions += `<button type="button" class="btn btn-outline-success reservation-action-btn reservation-confirm-btn" data-url="${row.confirm_url}" data-code="${row.code}" title="Confirmar">
                                    <i class="bi bi-check2-circle"></i>
                                </button>`;
                            }

                            if (row.can_confirm_blocked) {
                                actions += `<button type="button" class="btn btn-outline-warning reservation-action-btn reservation-confirm-blocked-btn" data-message="${escapeHtml(row.confirm_blocked_message)}" title="Falta anticipo">
                                    <i class="bi bi-exclamation-triangle"></i>
                                </button>`;
                            }

                            if (row.can_cancel) {
                                actions += `<button type="button" class="btn btn-outline-danger reservation-action-btn reservation-cancel-btn" data-url="${row.cancel_url}" data-code="${row.code}" title="Cancelar">
                                    <i class="bi bi-x-circle"></i>
                                </button>`;
                            }

                            if (row.can_checkin) {
                                actions += `<button type="button" class="btn btn-outline-info reservation-action-btn reservation-checkin-btn" data-url="${row.checkin_url}" data-code="${row.code}" title="Entrada">
                                    <i class="bi bi-box-arrow-in-right"></i>
                                </button>`;
                            }

                            if (row.can_checkout) {
                                actions += `<button type="button" class="btn btn-outline-secondary reservation-action-btn reservation-checkout-btn" data-url="${row.checkout_url}" data-code="${row.code}" title="Salida">
                                    <i class="bi bi-box-arrow-right"></i>
                                </button>`;
                            }

                            actions += '</div>';
                            return actions;
                        }
                    }
                ],
            });

            document.getElementById('open-create-reservation-modal')?.addEventListener('click', async () => {
                resetReservationForm(createForm, true);
                setCreateReservationStep(1);
                createModal?.show();
                await refreshReservationContext(createForm);
            });

            document.getElementById('open-reservation-agenda-modal')?.addEventListener('click', async () => {
                reservationAgendaModal?.show();
                await loadReservationAgenda();
            });

            document.getElementById('refresh-reservation-agenda')?.addEventListener('click', loadReservationAgenda);

            createForm.querySelector('[data-create-step-prev]')?.addEventListener('click', () => {
                setCreateReservationStep(createReservationStep - 1);
            });

            createForm.querySelector('[data-create-step-next]')?.addEventListener('click', async () => {
                if (!validateCreateReservationStep(createReservationStep)) {
                    return;
                }

                if (createReservationStep === 3) {
                    await refreshReservationContext(createForm);
                }

                setCreateReservationStep(createReservationStep + 1);
            });

            createForm.querySelectorAll('[data-step-target]').forEach((button) => {
                button.addEventListener('click', () => {
                    const targetStep = Number(button.dataset.stepTarget || 1);

                    if (targetStep <= createReservationStep || validateCreateReservationStep(createReservationStep)) {
                        setCreateReservationStep(targetStep);
                    }
                });
            });

            createForm.addEventListener('submit', async (event) => {
                event.preventDefault();

                for (let step = 1; step <= 5; step++) {
                    setCreateReservationStep(step);

                    if (!validateCreateReservationStep(step)) {
                        return;
                    }
                }

                setCreateReservationStep(5);
                await submitReservationForm(createForm, createForm.action, createModal, false);
            });

            editForm.addEventListener('submit', async (event) => {
                event.preventDefault();

                if (!validateReservationPaymentFields(editForm)) {
                    return;
                }

                await submitReservationForm(editForm, editForm.action, editModal, true);
            });

            reservationPaymentForm?.addEventListener('submit', async (event) => {
                event.preventDefault();
                await submitReservationPayment();
            });

            reservationPaymentCancelEdit?.addEventListener('click', () => {
                resetReservationPaymentForm();
            });

            document.addEventListener('click', async (event) => {
                const agendaRangeButton = event.target.closest('[data-agenda-range]');
                if (agendaRangeButton) {
                    applyAgendaRange(agendaRangeButton.dataset.agendaRange);
                    await loadReservationAgenda();
                    return;
                }

                const editButton = event.target.closest('.reservation-edit-btn');
                if (editButton) {
                    const reservation = JSON.parse(decodeURIComponent(editButton.dataset.reservation));
                    fillEditForm(reservation);
                    editModal?.show();
                    await refreshReservationContext(editForm);
                    return;
                }

                const paymentsButton = event.target.closest('.reservation-payments-btn');
                if (paymentsButton) {
                    const reservation = JSON.parse(decodeURIComponent(paymentsButton.dataset.reservation));
                    openReservationPaymentsModal(reservation);
                    return;
                }

                const paymentConfirmButton = event.target.closest('.reservation-payment-confirm-btn');
                if (paymentConfirmButton) {
                    if (paymentConfirmButton.dataset.requiresOpenCashRegister === '1' && mustOpenCashRegisterFirst()) {
                        await showOpenCashRegisterWarning();
                        return;
                    }

                    await processReservationPaymentAction(paymentConfirmButton.dataset.url, paymentConfirmButton.dataset.message || 'Confirmar este pago?');
                    return;
                }

                const paymentEditButton = event.target.closest('.reservation-payment-edit-btn');
                if (paymentEditButton) {
                    const payment = activePaymentReservation?.payments?.find((item) => String(item.id) === String(paymentEditButton.dataset.paymentId));

                    if (payment) {
                        fillReservationPaymentFormForEdit(payment);
                    }

                    return;
                }

                const paymentRejectButton = event.target.closest('.reservation-payment-reject-btn');
                if (paymentRejectButton) {
                    await processReservationPaymentAction(paymentRejectButton.dataset.url, 'Rechazar este comprobante?', {
                        input: 'textarea',
                        inputLabel: 'Motivo de rechazo',
                        inputPlaceholder: 'Explica brevemente por que se rechaza...',
                        confirmButtonColor: '#dc3545',
                        reasonField: 'rejection_reason',
                    });
                    return;
                }

                const paymentCancelButton = event.target.closest('.reservation-payment-cancel-btn');
                if (paymentCancelButton) {
                    await processReservationPaymentAction(paymentCancelButton.dataset.url, 'Anular este pago?', {
                        input: 'textarea',
                        inputLabel: 'Motivo de anulacion',
                        inputPlaceholder: 'Motivo interno opcional...',
                        confirmButtonColor: '#dc3545',
                        reasonField: 'cancellation_reason',
                    });
                    return;
                }

                const paymentRefundButton = event.target.closest('.reservation-payment-refund-btn');
                if (paymentRefundButton) {
                    await processReservationPaymentAction(paymentRefundButton.dataset.url, 'Registrar devolucion de este pago?', {
                        input: 'textarea',
                        inputLabel: 'Motivo de devolucion',
                        inputPlaceholder: 'Motivo interno opcional...',
                        confirmButtonColor: '#0dcaf0',
                        reasonField: 'refund_reason',
                    });
                    return;
                }

                const confirmButton = event.target.closest('.reservation-confirm-btn');
                if (confirmButton) {
                    await processReservationAction(confirmButton.dataset.url, `Confirmar la reserva ${confirmButton.dataset.code}?`);
                    return;
                }

                const confirmBlockedButton = event.target.closest('.reservation-confirm-blocked-btn');
                if (confirmBlockedButton) {
                    await fireAlert({
                        icon: 'warning',
                        title: 'No se puede aprobar todavia',
                        text: confirmBlockedButton.dataset.message || 'Primero debe aprobarse el comprobante o registrarse el anticipo requerido.',
                    });
                    return;
                }

                const cancelButton = event.target.closest('.reservation-cancel-btn');
                if (cancelButton) {
                    await cancelReservation(cancelButton.dataset.url, cancelButton.dataset.code);
                    return;
                }

                const checkinButton = event.target.closest('.reservation-checkin-btn');
                if (checkinButton) {
                    await processReservationAction(checkinButton.dataset.url, `Registrar entrada de la reserva ${checkinButton.dataset.code}?`);
                    return;
                }

                const checkoutButton = event.target.closest('.reservation-checkout-btn');
                if (checkoutButton) {
                    await processReservationAction(checkoutButton.dataset.url, `Registrar salida de la reserva ${checkoutButton.dataset.code}?`, {
                        allowForceCheckout: true,
                    });
                    return;
                }

                const availabilityButton = event.target.closest('[data-open-availability-board]');
                if (availabilityButton) {
                    const targetForm = availabilityButton.dataset.targetForm === 'edit' ? editForm : createForm;
                    await openAvailabilityBoard(targetForm);
                    return;
                }

                const selectAvailableRoomButton = event.target.closest('[data-select-available-room]');
                if (selectAvailableRoomButton && availabilityBoardForm) {
                    const roomId = selectAvailableRoomButton.dataset.selectAvailableRoom;
                    const roomField = availabilityBoardForm.querySelector('[name="room_id"]');

                    roomField.value = roomId;
                    roomField.dispatchEvent(new Event('change', { bubbles: true }));
                    availabilityBoardModal?.hide();
                    await refreshReservationContext(availabilityBoardForm);
                }
            });

            [createForm, editForm].forEach((form) => {
                form.querySelectorAll('.reservation-trigger').forEach((field) => {
                    field.addEventListener('change', () => scheduleContextRefresh(form));
                    field.addEventListener('input', () => scheduleContextRefresh(form));
                });
            });

            createForm.querySelector('[name="initial_payment_currency"]')?.addEventListener('change', () => updateInitialPaymentHelper(createForm));
            editForm.querySelector('[name="initial_payment_currency"]')?.addEventListener('change', () => updateInitialPaymentHelper(editForm));
            createForm.querySelector('[name="initial_payment_amount"]')?.addEventListener('input', (event) => {
                event.currentTarget.dataset.autofilledInitialPayment = '0';
            });
            editForm.querySelector('[name="initial_payment_amount"]')?.addEventListener('input', (event) => {
                event.currentTarget.dataset.autofilledInitialPayment = '0';
            });

            function openReservationPaymentsModal(reservation) {
                activePaymentReservation = reservation;
                resetReservationPaymentForm();

                renderReservationPaymentSummary(reservation);
                renderReservationPaymentList(reservation);
                reservationPaymentsModal?.show();
            }

            function resetReservationPaymentForm() {
                if (!reservationPaymentForm) {
                    return;
                }

                reservationPaymentForm.reset();
                reservationPaymentForm.action = paymentStoreUrl;
                reservationPaymentFormMethod.value = 'POST';
                reservationPaymentEditingId.value = '';
                reservationPaymentReservationId.value = activePaymentReservation?.id || '';
                reservationPaymentForm.querySelector('[name="payment_date"]').value = new Date().toISOString().slice(0, 10);
                reservationPaymentForm.querySelector('[name="currency"]').value = activePaymentReservation?.display_currency || 'BOB';
                reservationPaymentForm.querySelector('[name="reservation_id"]').disabled = false;
                reservationPaymentFormTitle.textContent = 'Registrar nuevo pago';
                reservationPaymentFormHelp.textContent = 'Usa esto para pagos manuales o comprobantes recibidos por administracion';
                reservationPaymentSubmit.innerHTML = '<i class="bi bi-cash-coin me-1" aria-hidden="true"></i> Guardar pago';
                reservationPaymentCancelEdit.classList.add('d-none');
            }

            function fillReservationPaymentFormForEdit(payment) {
                if (!reservationPaymentForm) {
                    return;
                }

                reservationPaymentForm.action = payment.update_url;
                reservationPaymentFormMethod.value = 'PUT';
                reservationPaymentEditingId.value = payment.id;
                reservationPaymentReservationId.value = activePaymentReservation?.id || '';
                reservationPaymentForm.querySelector('[name="reservation_id"]').disabled = true;
                reservationPaymentForm.querySelector('[name="currency"]').value = payment.currency || activePaymentReservation?.display_currency || 'BOB';
                reservationPaymentForm.querySelector('[name="amount"]').value = Number(payment.amount || 0).toFixed(2);
                reservationPaymentForm.querySelector('[name="payment_method"]').value = payment.payment_method || 'cash';
                reservationPaymentForm.querySelector('[name="payment_date"]').value = payment.payment_date_raw || new Date().toISOString().slice(0, 10);
                reservationPaymentForm.querySelector('[name="reference_number"]').value = payment.reference_number || '';
                reservationPaymentForm.querySelector('[name="notes"]').value = payment.notes_raw || '';
                reservationPaymentForm.querySelector('[name="receipt_image"]').value = '';
                reservationPaymentFormTitle.textContent = `Editar pago ${payment.code || ''}`;
                reservationPaymentFormHelp.textContent = 'Corrige monto, moneda, metodo o referencia. El sistema no permitira dejar la reserva por debajo del anticipo minimo.';
                reservationPaymentSubmit.innerHTML = '<i class="bi bi-pencil-square me-1" aria-hidden="true"></i> Actualizar pago';
                reservationPaymentCancelEdit.classList.remove('d-none');
                reservationPaymentForm.scrollIntoView({ behavior: 'smooth', block: 'start' });
            }

            function renderReservationPaymentSummary(reservation) {
                if (!reservationPaymentSummary) {
                    return;
                }

                reservationPaymentSummary.innerHTML = `
                    <div>
                        <span>Reserva</span>
                        <strong>${escapeHtml(reservation.code)}</strong>
                        <small>${escapeHtml(reservation.customer_name || 'Cliente sin nombre')}</small>
                    </div>
                    <div>
                        <span>Total</span>
                        <strong>${escapeHtml(reservation.display_total_amount_formatted || reservation.total_amount_formatted || '-')}</strong>
                        <small>${escapeHtml(reservation.room_type_name || 'Habitacion')}</small>
                    </div>
                    <div>
                        <span>Pagado</span>
                        <strong>${escapeHtml(reservation.display_paid_amount_formatted || reservation.paid_amount_formatted || '-')}</strong>
                        <small>${reservation.payments_count || 0} pago(s) registrados</small>
                    </div>
                    <div>
                        <span>Saldo / anticipo</span>
                        <strong>${escapeHtml(reservation.display_balance_amount_formatted || reservation.balance_amount_formatted || '-')}</strong>
                        <small>${escapeHtml(reservation.display_deposit_summary_label || reservation.deposit_summary_label || '')}</small>
                    </div>
                `;
            }

            function renderReservationPaymentList(reservation) {
                if (!reservationPaymentList) {
                    return;
                }

                const payments = reservation.payments || [];

                if (!payments.length) {
                    reservationPaymentList.innerHTML = '<div class="reservation-payment-empty">Todavia no hay pagos ni comprobantes para esta reserva.</div>';
                    return;
                }

                reservationPaymentList.innerHTML = payments.map(renderReservationPaymentCard).join('');
            }

            function renderReservationPaymentCard(payment) {
                const reference = payment.reference_number
                    ? `<div><small>Referencia</small>${escapeHtml(payment.reference_number)}</div>`
                    : '<div><small>Referencia</small>Sin referencia</div>';
                const notes = payment.notes_raw
                    ? `<div class="small text-muted mt-2">${escapeHtml(payment.notes_raw)}</div>`
                    : '';
                const rejection = payment.rejection_reason
                    ? `<div class="alert alert-danger py-2 px-3 mt-2 mb-0 small">${escapeHtml(payment.rejection_reason)}</div>`
                    : '';
                const receipt = payment.receipt_url
                    ? `<a class="btn btn-sm btn-outline-dark" href="${payment.receipt_url}" target="_blank" rel="noopener">
                        <i class="bi bi-paperclip me-1" aria-hidden="true"></i> Ver comprobante
                    </a>`
                    : '<span class="text-muted small">Sin archivo adjunto</span>';
                let actions = '';

                if (payment.can_update) {
                    actions += `<button type="button" class="btn btn-sm btn-outline-primary reservation-payment-edit-btn" data-payment-id="${payment.id}">
                        <i class="bi bi-pencil-square me-1" aria-hidden="true"></i> Editar
                    </button>`;
                }

                if (payment.can_confirm) {
                    const confirmText = payment.will_confirm_reservation
                        ? 'Confirmar pago y aprobar la reserva porque cubre el anticipo requerido?'
                        : 'Confirmar este pago?';
                    actions += `<button type="button" class="btn btn-sm btn-success reservation-payment-confirm-btn" data-url="${payment.confirm_url}" data-message="${escapeHtml(confirmText)}" data-requires-open-cash-register="${payment.requires_open_cash_register_to_confirm ? '1' : '0'}">
                        <i class="bi bi-check2-circle me-1" aria-hidden="true"></i> Aprobar
                    </button>`;
                }

                if (payment.can_reject) {
                    actions += `<button type="button" class="btn btn-sm btn-outline-danger reservation-payment-reject-btn" data-url="${payment.reject_url}">
                        <i class="bi bi-x-circle me-1" aria-hidden="true"></i> Rechazar
                    </button>`;
                }

                if (payment.can_cancel) {
                    actions += `<button type="button" class="btn btn-sm btn-outline-secondary reservation-payment-cancel-btn" data-url="${payment.cancel_url}">
                        <i class="bi bi-slash-circle me-1" aria-hidden="true"></i> Anular
                    </button>`;
                }

                if (payment.can_refund) {
                    actions += `<button type="button" class="btn btn-sm btn-outline-info reservation-payment-refund-btn" data-url="${payment.refund_url}">
                        <i class="bi bi-arrow-counterclockwise me-1" aria-hidden="true"></i> Devolver
                    </button>`;
                }

                return `
                    <article class="reservation-payment-card">
                        <div class="reservation-payment-card__top">
                            <div>
                                <div class="reservation-payment-card__amount">${escapeHtml(payment.amount_formatted || '-')}</div>
                                <small>${escapeHtml(payment.code || 'Pago')} - ${escapeHtml(payment.payment_method_label || 'Metodo no definido')}</small>
                            </div>
                            <span class="${escapeHtml(payment.status_badge_class || 'badge text-bg-secondary')}">${escapeHtml(payment.status_label || 'Estado')}</span>
                        </div>
                        <div class="reservation-payment-card__meta">
                            <div><small>Fecha de pago</small>${escapeHtml(payment.payment_date_formatted || '-')}</div>
                            <div><small>Registrado</small>${escapeHtml(payment.created_at_formatted || '-')}</div>
                            ${reference}
                            <div><small>Aplicado al saldo</small>${escapeHtml(payment.amount_base_formatted || 'No aplica')}</div>
                        </div>
                        ${notes}
                        ${rejection}
                        <div class="reservation-payment-card__actions mt-3">
                            ${receipt}
                            <div class="d-flex flex-wrap gap-2 justify-content-end">${actions || '<span class="text-muted small">Sin acciones disponibles</span>'}</div>
                        </div>
                    </article>
                `;
            }

            async function submitReservationPayment() {
                if (!reservationPaymentForm) {
                    return;
                }

                if (!reservationPaymentEditingId?.value && mustOpenCashRegisterFirst()) {
                    await showOpenCashRegisterWarning();
                    return;
                }

                const submitButton = reservationPaymentForm.querySelector('button[type="submit"]');
                submitButton?.setAttribute('disabled', 'disabled');

                try {
                    const response = await fetch(reservationPaymentForm.action, {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': csrfToken,
                            'Accept': 'application/json',
                        },
                        body: new FormData(reservationPaymentForm),
                    });

                    if (!response.ok) {
                        await handleRequestError(response);
                        return;
                    }

                    const payload = await response.json();
                    reservationPaymentsModal?.hide();
                    window.reservationsTable.ajax.reload(null, false);

                    await fireAlert({
                        icon: 'success',
                        title: payload.message || 'Pago registrado correctamente.',
                        timer: 2200,
                        showConfirmButton: false,
                    });
                } finally {
                    submitButton?.removeAttribute('disabled');
                }
            }

            async function processReservationPaymentAction(url, question, options = {}) {
                let extraValue = '';

                if (swal) {
                    const result = await swal.fire({
                        icon: options.confirmButtonColor === '#dc3545' ? 'warning' : 'question',
                        title: question,
                        input: options.input,
                        inputLabel: options.inputLabel,
                        inputPlaceholder: options.inputPlaceholder,
                        showCancelButton: true,
                        confirmButtonText: 'Si, continuar',
                        cancelButtonText: 'Volver',
                        confirmButtonColor: options.confirmButtonColor || '#198754',
                    });

                    if (!result.isConfirmed) {
                        return;
                    }

                    extraValue = result.value || '';
                } else if (!window.confirm(question)) {
                    return;
                }

                const formData = new FormData();
                if (options.reasonField) {
                    formData.append(options.reasonField, extraValue);
                    formData.append('reason', extraValue);
                }

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

                const payload = await response.json();
                reservationPaymentsModal?.hide();
                window.reservationsTable.ajax.reload(null, false);

                await fireAlert({
                    icon: 'success',
                    title: payload.message || 'Pago actualizado correctamente.',
                    timer: 2200,
                    showConfirmButton: false,
                });
            }

            function mustOpenCashRegisterFirst() {
                return requiresOpenCashRegister && !hasOpenCashRegister;
            }

            async function showOpenCashRegisterWarning() {
                const result = await fireAlert({
                    icon: 'warning',
                    title: 'Primero abre tu caja',
                    text: 'Para registrar o aprobar ingresos necesitas tener una caja abierta en tu turno.',
                    showCancelButton: true,
                    confirmButtonText: 'Ir a caja',
                    cancelButtonText: 'Volver',
                }, true);

                if (result.isConfirmed) {
                    window.location.href = openCashRegisterUrl;
                }
            }

            function setCreateReservationStep(step) {
                createReservationStep = Math.max(1, Math.min(5, Number(step || 1)));

                createForm.querySelectorAll('[data-step-panel]').forEach((panel) => {
                    panel.classList.toggle('is-active', Number(panel.dataset.stepPanel) === createReservationStep);
                });

                createForm.querySelectorAll('[data-step-target]').forEach((button) => {
                    const buttonStep = Number(button.dataset.stepTarget || 1);
                    button.classList.toggle('is-active', buttonStep === createReservationStep);
                    button.classList.toggle('is-complete', buttonStep < createReservationStep);
                });

                const previousButton = createForm.querySelector('[data-create-step-prev]');
                const nextButton = createForm.querySelector('[data-create-step-next]');
                const submitButton = createForm.querySelector('[data-create-step-submit]');

                previousButton?.toggleAttribute('disabled', createReservationStep === 1);
                nextButton?.classList.toggle('d-none', createReservationStep === 5);
                submitButton?.classList.toggle('d-none', createReservationStep !== 5);

                createForm.querySelector('.modal-body')?.scrollTo({ top: 0, behavior: 'smooth' });
            }

            function validateCreateReservationStep(step) {
                const panel = createForm.querySelector(`[data-step-panel="${step}"]`);

                if (!panel) {
                    return true;
                }

                const requiredFields = [...panel.querySelectorAll('[required]')];
                const invalidField = requiredFields.find((field) => {
                    if (field.disabled) {
                        return false;
                    }

                    return !String(field.value || '').trim();
                });

                if (invalidField) {
                    invalidField.focus();
                    fireAlert({
                        icon: 'warning',
                        title: 'Completa este paso',
                        text: 'Falta un dato obligatorio antes de continuar.',
                        timer: 1800,
                        showConfirmButton: false,
                    });

                    return false;
                }

                if (step === 1) {
                    const checkIn = createForm.querySelector('[name="check_in"]').value;
                    const checkOut = createForm.querySelector('[name="check_out"]').value;

                    if (checkIn && checkOut && checkOut <= checkIn) {
                        createForm.querySelector('[name="check_out"]').focus();
                        fireAlert({
                            icon: 'warning',
                            title: 'Revisa las fechas',
                            text: 'La salida debe ser posterior a la entrada.',
                            timer: 2200,
                            showConfirmButton: false,
                        });

                        return false;
                    }
                }

                if (step === 4) {
                    if (!validateReservationPaymentFields(createForm)) {
                        return false;
                    }
                }

                return true;
            }

            function validateReservationPaymentFields(form) {
                const initialAmount = Number(form.querySelector('[name="initial_payment_amount"]')?.value || 0);
                const initialMethod = form.querySelector('[name="initial_payment_method"]')?.value || '';
                const initialCurrency = form.querySelector('[name="initial_payment_currency"]')?.value || '';

                if (initialAmount > 0 && (!initialCurrency || !initialMethod)) {
                    form.querySelector(!initialCurrency ? '[name="initial_payment_currency"]' : '[name="initial_payment_method"]')?.focus();
                    fireAlert({
                        icon: 'warning',
                        title: 'Completa el pago recibido',
                        text: 'Si registras un monto, tambien debes indicar moneda y metodo de pago.',
                        timer: 2300,
                        showConfirmButton: false,
                    });

                    return false;
                }

                return true;
            }

            function resetReservationForm(form, defaults = false) {
                form.reset();

                if (defaults) {
                    form.querySelector('[name="adults"]').value = 1;
                    form.querySelector('[name="children"]').value = 0;
                    form.querySelector('[name="status"]').value = 'pending';
                    form.querySelector('[name="source"]').value = 'reception';
                }

                setEnhancedSelectValue(form.querySelector('[name="customer_id"]'), '', '');

                const currencyField = form.querySelector('[name="base_price_currency"]');
                if (currencyField) {
                    currencyField.value = 'BOB';
                }

                const initialPaymentAmountField = form.querySelector('[name="initial_payment_amount"]');
                if (initialPaymentAmountField) {
                    initialPaymentAmountField.dataset.autofilledInitialPayment = '1';
                }

                form.querySelector('[data-room-summary]').innerHTML = `
                    <div class="fw-semibold mb-2">Resumen de habitacion</div>
                    <div class="text-muted mb-0">Selecciona una habitacion para ver su tipo, precio base y capacidad maxima.</div>
                `;
                form.querySelector('[data-quote-preview]').innerHTML = `
                    <div class="fw-semibold mb-2">Vista previa del costo</div>
                    <div class="text-muted mb-0">Completa habitacion y fechas para calcular noches, descuento y total final.</div>
                `;
                updateInitialPaymentHelper(form);
                form.querySelector('.reservation-availability-message').textContent = '';
                toggleManualDiscountFields(form);
            }

            function fillEditForm(reservation) {
                resetReservationForm(editForm);
                editForm.action = reservation.update_url;
                const customerLabel = [
                    reservation.customer_name,
                    reservation.customer_document ? `${reservation.customer_document_type_label || 'Documento'} ${reservation.customer_document}` : '',
                ].filter(Boolean).join(' - ');
                setEnhancedSelectValue(editForm.querySelector('[name="customer_id"]'), reservation.customer_id ?? '', customerLabel);
                editForm.querySelector('[name="room_id"]').value = reservation.room_id ?? '';
                editForm.querySelector('[name="check_in"]').value = reservation.check_in ?? '';
                editForm.querySelector('[name="check_out"]').value = reservation.check_out ?? '';
                editForm.querySelector('[name="adults"]').value = reservation.adults ?? 1;
                editForm.querySelector('[name="children"]').value = reservation.children ?? 0;
                editForm.querySelector('[name="promotion_id"]').value = reservation.promotion_id ?? '';
                editForm.querySelector('[name="status"]').value = reservation.status ?? 'pending';
                editForm.querySelector('[name="source"]').value = reservation.source ?? 'reception';
                editForm.querySelector('[name="preferred_payment_method"]').value = reservation.preferred_payment_method ?? '';
                editForm.querySelector('[name="special_requests"]').value = reservation.special_requests ?? '';
                editForm.querySelector('[name="internal_notes"]').value = reservation.internal_notes ?? '';

                const basePriceField = editForm.querySelector('[name="base_price"]');
                if (basePriceField) {
                    basePriceField.value = reservation.base_price ?? '';
                }

                const basePriceCurrencyField = editForm.querySelector('[name="base_price_currency"]');
                if (basePriceCurrencyField) {
                    basePriceCurrencyField.value = 'BOB';
                }

                const discountTypeField = editForm.querySelector('[name="discount_type"]');
                const discountValueField = editForm.querySelector('[name="discount_value"]');
                if (discountTypeField) {
                    discountTypeField.value = reservation.promotion_id ? '' : (reservation.discount_type ?? '');
                }
                if (discountValueField) {
                    discountValueField.value = reservation.promotion_id ? '' : (reservation.discount_value ?? '');
                }

                toggleManualDiscountFields(editForm);
            }

            function initializeCustomerSelects() {
                if (typeof $.fn.select2 !== 'function') {
                    console.warn('Select2 no esta disponible para el buscador de clientes.');
                    return;
                }

                $('.reservation-customer-select').each(function () {
                    const $field = $(this);
                    const $modal = $field.closest('.modal');

                    $field.select2({
                        width: '100%',
                        dropdownParent: $modal.length ? $modal : $(document.body),
                        placeholder: 'Buscar cliente o huesped',
                        allowClear: false,
                        minimumInputLength: 2,
                        ajax: {
                            url: customerSearchUrl,
                            dataType: 'json',
                            delay: 280,
                            cache: true,
                            data: (params) => ({
                                term: params.term || '',
                                page: params.page || 1,
                            }),
                            processResults: (payload) => ({
                                results: payload.results || [],
                                pagination: payload.pagination || { more: false },
                            }),
                        },
                        templateResult: renderCustomerOption,
                        templateSelection: (customer) => escapeHtml(customerSelectionLabel(customer)),
                        escapeMarkup: (markup) => markup,
                        language: {
                            inputTooShort: () => 'Escribe al menos 2 letras o numeros',
                            noResults: () => 'Sin resultados',
                            searching: () => 'Buscando...',
                            loadingMore: () => 'Cargando mas clientes...',
                            errorLoading: () => 'No se pudieron cargar los clientes',
                        },
                    });
                });
            }

            function renderCustomerOption(customer) {
                if (customer.loading) {
                    return customer.text;
                }

                const customerName = escapeHtml(customer.name || customer.text || 'Cliente');
                const documentLabel = customerDocumentLabel(customer);
                const title = escapeHtml([customer.name || customer.text || 'Cliente', documentLabel].filter(Boolean).join(' - '));
                const document = documentLabel ? `<span><i class="bi bi-card-text me-1"></i>${escapeHtml(documentLabel)}</span>` : '';
                const phone = customer.phone ? `<span><i class="bi bi-telephone me-1"></i>${escapeHtml(customer.phone)}</span>` : '';
                const email = customer.email ? `<span><i class="bi bi-envelope me-1"></i>${escapeHtml(customer.email)}</span>` : '';
                const details = [phone, email].filter(Boolean).join('');

                return `
                    <div class="reservation-customer-option">
                        <strong>${title}</strong>
                        ${details ? `<small>${details}</small>` : `<small>${document || 'Sin datos adicionales registrados'}</small>`}
                    </div>
                `;
            }

            function customerSelectionLabel(customer) {
                return [customer.name || customer.text || 'Cliente seleccionado', customerDocumentLabel(customer)]
                    .filter(Boolean)
                    .join(' - ');
            }

            function customerDocumentLabel(customer) {
                if (!customer.document) {
                    return '';
                }

                return `${customer.document_type || 'Documento'} ${customer.document}`;
            }

            function escapeHtml(value) {
                return String(value ?? '').replace(/[&<>"']/g, (character) => ({
                    '&': '&amp;',
                    '<': '&lt;',
                    '>': '&gt;',
                    '"': '&quot;',
                    "'": '&#039;',
                }[character]));
            }

            function setEnhancedSelectValue(field, value, text = '') {
                if (!field) {
                    return;
                }

                if (!value) {
                    field.value = '';
                    if (typeof $.fn.select2 === 'function' && $(field).data('select2')) {
                        $(field).val(null).trigger('change');
                    }

                    return;
                }

                if (text && !field.querySelector(`option[value="${value}"]`)) {
                    field.append(new Option(text, value, true, true));
                }

                field.value = value;

                if (typeof $.fn.select2 === 'function' && $(field).data('select2')) {
                    $(field).val(value).trigger('change');
                }
            }

            function applyAgendaRange(range) {
                const today = new Date();
                const from = new Date(today);
                const to = new Date(today);

                if (range === 'week') {
                    const day = today.getDay() || 7;
                    from.setDate(today.getDate() - day + 1);
                    to.setDate(from.getDate() + 6);
                } else if (range === 'month') {
                    from.setDate(1);
                    to.setMonth(from.getMonth() + 1, 0);
                }

                agendaDateFrom.value = formatDateInput(from);
                agendaDateTo.value = formatDateInput(to);
            }

            function formatDateInput(date) {
                return [
                    date.getFullYear(),
                    String(date.getMonth() + 1).padStart(2, '0'),
                    String(date.getDate()).padStart(2, '0'),
                ].join('-');
            }

            async function loadReservationAgenda() {
                if (!reservationAgendaGrid || !reservationAgendaSummary) {
                    return;
                }

                reservationAgendaGrid.innerHTML = '<div class="reservation-agenda-empty">Cargando agenda de reservas...</div>';

                const params = new URLSearchParams({
                    date_from: agendaDateFrom.value,
                    date_to: agendaDateTo.value,
                });

                try {
                    const response = await fetch(`${reservationAgendaUrl}?${params.toString()}`, {
                        headers: { 'Accept': 'application/json' },
                    });

                    if (!response.ok) {
                        throw new Error('No se pudo cargar la agenda en este momento.');
                    }

                    const payload = await response.json();
                    renderReservationAgenda(payload);
                } catch (error) {
                    reservationAgendaSummary.innerHTML = renderAgendaSummary({});
                    reservationAgendaGrid.innerHTML = `<div class="reservation-agenda-empty">${escapeHtml(error.message || 'No se pudo cargar la agenda.')}</div>`;
                }
            }

            function renderReservationAgenda(payload) {
                reservationAgendaSummary.innerHTML = renderAgendaSummary(payload.summary || {});
                const reservations = payload.reservations || [];

                if (!reservations.length) {
                    reservationAgendaGrid.innerHTML = '<div class="reservation-agenda-empty">No hay reservas activas en el rango seleccionado.</div>';
                    return;
                }

                reservationAgendaGrid.innerHTML = reservations.map(renderReservationAgendaCard).join('');
            }

            function renderAgendaSummary(summary) {
                return `
                    <div><span>Total</span><strong>${summary.total || 0}</strong><small>reservas activas</small></div>
                    <div><span>Pendientes</span><strong>${summary.pending || 0}</strong><small>por confirmar</small></div>
                    <div><span>Confirmadas</span><strong>${summary.confirmed || 0}</strong><small>con habitacion</small></div>
                    <div><span>Ocupadas</span><strong>${summary.checked_in || 0}</strong><small>check-in realizado</small></div>
                `;
            }

            function renderReservationAgendaCard(reservation) {
                const document = reservation.customer_document
                    ? `${reservation.customer_document_type_label || 'Documento'} ${reservation.customer_document}`
                    : 'Sin documento';
                const contacts = [
                    reservation.customer_phone ? `<span><i class="bi bi-telephone"></i>${escapeHtml(reservation.customer_phone)}</span>` : '',
                    reservation.customer_whatsapp ? `<span><i class="bi bi-whatsapp"></i>${escapeHtml(reservation.customer_whatsapp)}</span>` : '',
                    reservation.customer_email ? `<span><i class="bi bi-envelope"></i>${escapeHtml(reservation.customer_email)}</span>` : '',
                ].filter(Boolean).join('');
                const promotion = reservation.promotion_name ? `<span><i class="bi bi-tag"></i>${escapeHtml(reservation.promotion_name)}</span>` : '';
                const request = reservation.special_requests
                    ? `<div class="small text-muted mt-2"><i class="bi bi-chat-left-text me-1"></i>${escapeHtml(reservation.special_requests)}</div>`
                    : '';

                return `
                    <article class="reservation-agenda-card">
                        <div class="reservation-agenda-date">
                            <span>Reserva</span>
                            <strong>${escapeHtml(reservation.code)}</strong>
                            <small>${escapeHtml(reservation.check_in_formatted)} al ${escapeHtml(reservation.check_out_formatted)}</small>
                            <small>${Number(reservation.nights || 0)} noche(s)</small>
                            <span class="${reservation.status_badge_class} mt-2">${escapeHtml(reservation.status_label)}</span>
                        </div>
                        <div>
                            <div class="reservation-agenda-room">
                                <i class="bi bi-door-open"></i>
                                Hab. ${escapeHtml(reservation.room_number)} · ${escapeHtml(reservation.room_type_name)}
                            </div>
                            <h4 class="reservation-agenda-customer">${escapeHtml(reservation.customer_name)}</h4>
                            <small>${escapeHtml(document)}</small>
                            <div class="reservation-agenda-meta">
                                <span><i class="bi bi-people"></i>${escapeHtml(reservation.guests_summary)}</span>
                                <span><i class="bi bi-credit-card"></i>${escapeHtml(reservation.payment_method_label)}</span>
                                <span><i class="bi bi-signpost"></i>${escapeHtml(reservation.source_label)}</span>
                                ${promotion}
                                ${contacts}
                            </div>
                            ${request}
                        </div>
                        <div class="reservation-agenda-money">
                            <div><small>Total</small><strong>${escapeHtml(reservation.total_amount_formatted)}</strong></div>
                            <div><small>Pagado</small><strong>${escapeHtml(reservation.paid_amount_formatted)}</strong></div>
                            <div><small>Saldo</small><strong>${escapeHtml(reservation.balance_amount_formatted)}</strong></div>
                            <div><small>Anticipo pendiente</small><strong>${escapeHtml(reservation.deposit_pending_formatted)}</strong></div>
                        </div>
                    </article>
                `;
            }

            function scheduleContextRefresh(form) {
                clearTimeout(quoteTimers.get(form));
                const timer = setTimeout(() => {
                    refreshReservationContext(form);
                }, 350);
                quoteTimers.set(form, timer);
            }

            async function refreshReservationContext(form) {
                toggleManualDiscountFields(form);
                renderRoomSummary(form);
                await Promise.all([
                    updateAvailabilityMessage(form),
                    refreshQuote(form),
                ]);
            }

            function renderRoomSummary(form) {
                const room = roomsMap.get(String(form.querySelector('[name="room_id"]').value || ''));
                const summary = form.querySelector('[data-room-summary]');

                if (!room) {
                    summary.innerHTML = `
                        <div class="fw-semibold mb-2">Resumen de habitacion</div>
                        <div class="text-muted mb-0">Selecciona una habitacion para ver su tipo, precio base y capacidad maxima.</div>
                    `;
                    return;
                }

                summary.innerHTML = `
                    <div class="fw-semibold mb-2">Resumen de habitacion</div>
                    <div class="row g-2">
                        <div class="col-md-4"><span class="text-muted d-block small">Habitacion</span><span class="fw-semibold">Hab. ${room.number}</span></div>
                        <div class="col-md-4"><span class="text-muted d-block small">Tipo</span><span class="fw-semibold">${room.room_type_name}</span></div>
                        <div class="col-md-4"><span class="text-muted d-block small">Precio BOB</span><span class="fw-semibold">Bs. ${Number(room.price_bob ?? room.base_price).toFixed(2)}</span></div>
                        <div class="col-md-4"><span class="text-muted d-block small">Precio USD</span><span class="fw-semibold">$us ${Number(room.price_usd ?? 0).toFixed(2)}</span></div>
                        <div class="col-md-4"><span class="text-muted d-block small">Capacidad maxima</span><span class="fw-semibold">${room.max_guests} huespedes</span></div>
                        <div class="col-md-4"><span class="text-muted d-block small">Anticipo para confirmar</span><span class="fw-semibold">${room.reservation_deposit_percentage}%</span></div>
                        <div class="col-md-4"><span class="text-muted d-block small">Estado actual</span><span class="fw-semibold">${room.status}</span></div>
                    </div>
                `;
            }

            async function updateAvailabilityMessage(form) {
                const roomId = form.querySelector('[name="room_id"]').value;
                const target = form.querySelector('.reservation-availability-message');
                const params = availabilityParamsForForm(form);

                if (!params) {
                    target.textContent = '';
                    return;
                }

                const response = await fetch(`${availableRoomsUrl}?${params.toString()}`, {
                    headers: { 'Accept': 'application/json' },
                });

                if (!response.ok) {
                    target.innerHTML = '<span class="text-danger">No se pudo validar disponibilidad en este momento.</span>';
                    return;
                }

                const payload = await response.json();
                const availableIds = new Set((payload.rooms || []).map((room) => String(room.id)));

                if (!roomId) {
                    target.innerHTML = `<span class="text-success">Disponibles ${payload.rooms.length} habitacion(es) para el rango seleccionado.</span>`;
                    return;
                }

                if (availableIds.has(String(roomId))) {
                    target.innerHTML = '<span class="text-success">La habitacion seleccionada esta disponible para esas fechas.</span>';
                    return;
                }

                target.innerHTML = '<span class="text-danger">La habitacion seleccionada no esta disponible en esas fechas o supera la capacidad permitida.</span>';
            }

            function availabilityParamsForForm(form) {
                const checkIn = form.querySelector('[name="check_in"]').value;
                const checkOut = form.querySelector('[name="check_out"]').value;
                const adults = form.querySelector('[name="adults"]').value || 1;
                const children = form.querySelector('[name="children"]').value || 0;

                if (!checkIn || !checkOut || !adults) {
                    return null;
                }

                return new URLSearchParams({
                    check_in: checkIn,
                    check_out: checkOut,
                    adults,
                    children,
                });
            }

            async function openAvailabilityBoard(form) {
                availabilityBoardForm = form;
                const params = availabilityParamsForForm(form);

                availabilityBoardModal?.show();

                if (!params) {
                    renderAvailabilityBoard([], form, 'Completa entrada, salida y huespedes antes de buscar habitaciones disponibles.');
                    return;
                }

                availabilityBoardGrid.innerHTML = '<div class="availability-board-empty">Buscando habitaciones disponibles...</div>';

                try {
                    const response = await fetch(`${availableRoomsUrl}?${params.toString()}`, {
                        headers: { 'Accept': 'application/json' },
                    });

                    if (!response.ok) {
                        throw new Error('No se pudo validar la disponibilidad en este momento.');
                    }

                    const payload = await response.json();
                    renderAvailabilityBoard(payload.rooms || [], form);
                } catch (error) {
                    renderAvailabilityBoard([], form, error.message || 'No se pudo validar la disponibilidad en este momento.');
                }
            }

            function renderAvailabilityBoard(rooms, form, emptyMessage = null) {
                const checkIn = form.querySelector('[name="check_in"]').value || '-';
                const checkOut = form.querySelector('[name="check_out"]').value || '-';
                const adults = Number(form.querySelector('[name="adults"]').value || 0);
                const children = Number(form.querySelector('[name="children"]').value || 0);
                const guests = adults + children;

                availabilityBoardSubtitle.textContent = `${checkIn} al ${checkOut} | ${guests || '-'} huesped(es)`;
                availabilityBoardSummary.innerHTML = `
                    <div>
                        <span>Disponibles</span>
                        <strong>${rooms.length}</strong>
                        <small>habitaciones libres</small>
                    </div>
                    <div>
                        <span>Rango</span>
                        <strong>${escapeHtml(checkIn)} - ${escapeHtml(checkOut)}</strong>
                        <small>entrada y salida</small>
                    </div>
                    <div>
                        <span>Huespedes</span>
                        <strong>${guests || '-'}</strong>
                        <small>${adults} adulto(s) / ${children} nino(s)</small>
                    </div>
                `;

                if (!rooms.length) {
                    availabilityBoardGrid.innerHTML = `<div class="availability-board-empty">${escapeHtml(emptyMessage || 'No hay habitaciones disponibles para esos datos. Cambia fechas o cantidad de huespedes.')}</div>`;
                    return;
                }

                availabilityBoardGrid.innerHTML = rooms.map((room) => renderAvailabilityRoomCard(room, form)).join('');
            }

            function renderAvailabilityRoomCard(room, form) {
                const currentRoomId = String(form.querySelector('[name="room_id"]').value || '');
                const isSelected = currentRoomId === String(room.id);
                const catalogRoom = roomsMap.get(String(room.id)) || room;
                const guests = Number(form.querySelector('[name="adults"]').value || 0) + Number(form.querySelector('[name="children"]').value || 0);
                const maxGuests = Number(room.max_guests || catalogRoom.max_guests || 1);
                const priceBob = Number(catalogRoom.price_bob ?? room.base_price ?? 0).toFixed(2);
                const priceUsd = Number(catalogRoom.price_usd ?? 0).toFixed(2);
                const depositPercentage = Number(catalogRoom.reservation_deposit_percentage ?? 20);
                const capacityPercent = Math.max(8, Math.min(100, Math.round((guests / Math.max(maxGuests, 1)) * 100)));
                const depositPercent = Math.max(8, Math.min(100, depositPercentage));

                return `
                    <article class="availability-room-card ${isSelected ? 'border-primary' : ''}">
                        <div>
                            <div class="d-flex justify-content-between gap-3 align-items-start mb-3">
                                <div class="availability-room-number">${escapeHtml(room.number)}</div>
                                <span class="badge text-bg-success">Disponible</span>
                            </div>
                            <h4>${escapeHtml(room.room_type_name)}</h4>
                            <div class="availability-room-meta mt-2">
                                <span><i class="bi bi-people"></i> Max. ${room.max_guests}</span>
                                <span><i class="bi bi-shield-check"></i> Anticipo ${depositPercentage}%</span>
                                <span><i class="bi bi-door-open"></i> Hab. ${escapeHtml(room.number)}</span>
                            </div>
                            <div class="availability-room-visual">
                                <div>
                                    <div class="availability-room-bar-label">
                                        <span>Capacidad usada</span>
                                        <span>${guests}/${maxGuests}</span>
                                    </div>
                                    <div class="availability-room-bar"><span style="width: ${capacityPercent}%"></span></div>
                                </div>
                                <div>
                                    <div class="availability-room-bar-label">
                                        <span>Anticipo requerido</span>
                                        <span>${depositPercentage}%</span>
                                    </div>
                                    <div class="availability-room-bar availability-room-bar--deposit"><span style="width: ${depositPercent}%"></span></div>
                                </div>
                            </div>
                        </div>
                        <div>
                            <div class="availability-room-price mb-3">
                                <div><small>Precio BOB</small><strong>Bs. ${priceBob}</strong></div>
                                <div><small>Precio USD</small><strong>$us ${priceUsd}</strong></div>
                            </div>
                            <button type="button" class="btn ${isSelected ? 'btn-success' : 'btn-reservations-primary'} w-100" data-select-available-room="${room.id}">
                                <i class="bi ${isSelected ? 'bi-check2-circle' : 'bi-cursor'} me-1"></i>
                                ${isSelected ? 'Habitacion seleccionada' : 'Elegir esta habitacion'}
                            </button>
                        </div>
                    </article>
                `;
            }

            async function refreshQuote(form) {
                const roomId = form.querySelector('[name="room_id"]').value;
                const checkIn = form.querySelector('[name="check_in"]').value;
                const checkOut = form.querySelector('[name="check_out"]').value;
                const preview = form.querySelector('[data-quote-preview]');

                if (!roomId || !checkIn || !checkOut) {
                    quotePayloads.delete(form);
                    preview.innerHTML = `
                        <div class="fw-semibold mb-2">Vista previa del costo</div>
                        <div class="text-muted mb-0">Completa habitacion y fechas para calcular noches, descuento y total final.</div>
                    `;
                    updateInitialPaymentHelper(form);
                    return;
                }

                const formData = new FormData();
                formData.append('room_id', roomId);
                formData.append('check_in', checkIn);
                formData.append('check_out', checkOut);

                const promotionId = form.querySelector('[name="promotion_id"]').value;
                if (promotionId) {
                    formData.append('promotion_id', promotionId);
                }

                const discountTypeField = form.querySelector('[name="discount_type"]');
                const discountValueField = form.querySelector('[name="discount_value"]');
                const basePriceField = form.querySelector('[name="base_price"]');
                const basePriceCurrencyField = form.querySelector('[name="base_price_currency"]');

                if (discountTypeField?.value) {
                    formData.append('discount_type', discountTypeField.value);
                }

                if (discountValueField?.value) {
                    formData.append('discount_value', discountValueField.value);
                }

                if (basePriceField?.value) {
                    formData.append('base_price', basePriceField.value);
                }

                if (basePriceCurrencyField?.value) {
                    formData.append('base_price_currency', basePriceCurrencyField.value);
                }

                const response = await fetch(quoteUrl, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': csrfToken,
                        'Accept': 'application/json',
                    },
                    body: formData,
                });

                if (!response.ok) {
                    quotePayloads.delete(form);
                    preview.innerHTML = `
                        <div class="fw-semibold mb-2">Vista previa del costo</div>
                        <div class="text-danger mb-0">No fue posible calcular la cotizacion con los datos actuales.</div>
                    `;
                    updateInitialPaymentHelper(form);
                    return;
                }

                const payload = await response.json();
                quotePayloads.set(form, payload);
                const manualCurrency = basePriceCurrencyField?.value ?? 'BOB';
                const manualPrice = basePriceField?.value ? Number(basePriceField.value).toFixed(2) : '';
                const conversionNote = manualCurrency === 'USD' && manualPrice
                    ? `<div class="small text-muted mt-1">Precio manual ingresado: $us ${manualPrice}. Se cotiza y guarda convertido a bolivianos.</div>`
                    : '';

                preview.innerHTML = `
                    <div class="fw-semibold mb-2">Vista previa del costo</div>
                    <div class="row g-2">
                        <div class="col-md-2"><span class="text-muted d-block small">Noches</span><span class="fw-semibold">${payload.nights}</span></div>
                        <div class="col-md-2"><span class="text-muted d-block small">Base / noche</span><span class="fw-semibold">Bs. ${Number(payload.base_price).toFixed(2)}</span></div>
                        <div class="col-md-2"><span class="text-muted d-block small">Descuento</span><span class="fw-semibold">Bs. ${Number(payload.discount_amount).toFixed(2)}</span></div>
                        <div class="col-md-2"><span class="text-muted d-block small">Final / noche</span><span class="fw-semibold">Bs. ${Number(payload.price_per_night).toFixed(2)}</span></div>
                        <div class="col-md-3"><span class="text-muted d-block small">Total</span><span class="fw-semibold">Bs. ${Number(payload.total_amount).toFixed(2)}</span></div>
                        <div class="col-md-3"><span class="text-muted d-block small">Anticipo minimo</span><span class="fw-semibold">${payload.deposit_percentage}% - Bs. ${Number(payload.deposit_amount_required).toFixed(2)}</span></div>
                    </div>
                    <div class="small text-muted mt-2">${payload.label}</div>
                    ${conversionNote}
                `;
                updateInitialPaymentHelper(form, payload);
            }

            function updateInitialPaymentHelper(form, payload = null) {
                payload = payload || quotePayloads.get(form) || null;
                const helper = form.querySelector('[data-initial-payment-helper]');
                const amountField = form.querySelector('[name="initial_payment_amount"]');
                const currencyField = form.querySelector('[name="initial_payment_currency"]');
                const selectedCurrency = currencyField?.value || 'BOB';
                const selectedSymbol = currencySymbols[selectedCurrency] || selectedCurrency;

                if (!helper) {
                    return;
                }

                if (!payload) {
                    helper.innerHTML = 'Completa habitacion y fechas para ver el total y el anticipo minimo sugerido.';
                    return;
                }

                const selectedQuote = payload.quote_by_currency?.[selectedCurrency] || payload.quote_by_currency?.BOB || null;
                const minimumDeposit = Number(selectedQuote?.deposit_amount_required ?? payload.deposit_amount_required ?? 0);
                const reservationTotal = Number(selectedQuote?.total_amount ?? payload.total_amount ?? 0);
                const pricePerNight = Number(selectedQuote?.price_per_night ?? payload.price_per_night ?? 0);
                const canSuggestSelectedCurrency = Boolean(selectedQuote);
                const wasAutofilled = amountField?.dataset.autofilledInitialPayment !== '0';

                if (form === createForm && amountField && canSuggestSelectedCurrency && wasAutofilled) {
                    amountField.value = minimumDeposit.toFixed(2);
                    amountField.dataset.autofilledInitialPayment = '1';
                }

                helper.innerHTML = `
                    <span class="d-block fw-semibold text-dark">Total en ${selectedCurrency}: ${selectedSymbol} ${reservationTotal.toFixed(2)}.</span>
                    <span class="d-block">Anticipo minimo: ${selectedSymbol} ${minimumDeposit.toFixed(2)} (${payload.deposit_percentage}%). Precio por noche: ${selectedSymbol} ${pricePerNight.toFixed(2)}.</span>
                    <span class="d-block">${canSuggestSelectedCurrency ? 'Registra el monto real recibido y el metodo de pago.' : 'Esta moneda esta habilitada para registro, pero no tiene precio automatico en este tipo de habitacion.'}</span>
                `;
            }

            function toggleManualDiscountFields(form) {
                const promotionField = form.querySelector('[name="promotion_id"]');
                const hasPromotion = Boolean(promotionField?.value);
                const discountTypeField = form.querySelector('.reservation-discount-type');
                const discountValueField = form.querySelector('.reservation-discount-value');

                if (!discountTypeField || !discountValueField) {
                    return;
                }

                discountTypeField.disabled = hasPromotion;
                discountValueField.disabled = hasPromotion;

                if (hasPromotion) {
                    discountTypeField.value = '';
                    discountValueField.value = '';
                }
            }

            async function submitReservationForm(form, url, modalInstance, useMethodOverride) {
                const formData = new FormData(form);

                if (useMethodOverride) {
                    formData.set('_method', 'PUT');
                }

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

                const payload = await response.json();
                modalInstance?.hide();
                resetReservationForm(form, form === createForm);
                window.reservationsTable.ajax.reload(null, false);

                await fireAlert({
                    icon: 'success',
                    title: payload.message || 'Operacion completada correctamente.',
                    timer: 1800,
                    showConfirmButton: false,
                });
            }

            async function processReservationAction(url, confirmationText, options = {}) {
                const confirmation = await fireAlert({
                    icon: 'question',
                    title: 'Confirmar accion',
                    text: confirmationText,
                    showCancelButton: true,
                    confirmButtonText: 'Si, continuar',
                    cancelButtonText: 'Cancelar',
                }, true);

                if (!confirmation.isConfirmed) {
                    return;
                }

                const formData = new FormData();

                const response = await fetch(url, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': csrfToken,
                        'Accept': 'application/json',
                    },
                    body: formData,
                });

                if (!response.ok) {
                    const payload = await safeJson(response);

                    if (response.status === 422 && options.allowForceCheckout && payload.requires_force_checkout) {
                        const forceConfirmation = await fireAlert({
                            icon: 'warning',
                            title: 'Saldo pendiente',
                            text: payload.message || 'La reserva tiene saldo pendiente. Deseas registrar la salida de todas formas?',
                            showCancelButton: true,
                            confirmButtonText: 'Si, registrar salida',
                            cancelButtonText: 'Volver',
                        }, true);

                        if (!forceConfirmation.isConfirmed) {
                            return;
                        }

                        formData.set('force_checkout', '1');

                        const forcedResponse = await fetch(url, {
                            method: 'POST',
                            headers: {
                                'X-CSRF-TOKEN': csrfToken,
                                'Accept': 'application/json',
                            },
                            body: formData,
                        });

                        if (!forcedResponse.ok) {
                            await handleRequestError(forcedResponse);
                            return;
                        }

                        const forcedPayload = await forcedResponse.json();
                        window.reservationsTable.ajax.reload(null, false);

                        await fireAlert({
                            icon: 'success',
                            title: forcedPayload.message || 'Operacion completada correctamente.',
                            timer: 1800,
                            showConfirmButton: false,
                        });

                        return;
                    }

                    await handleRequestError(response);
                    return;
                }

                const payload = await response.json();
                window.reservationsTable.ajax.reload(null, false);

                await fireAlert({
                    icon: 'success',
                    title: payload.message || 'Operacion completada correctamente.',
                    timer: 1800,
                    showConfirmButton: false,
                });
            }

            async function cancelReservation(url, code) {
                let cancellationReason = '';

                if (swal) {
                    const result = await swal.fire({
                        icon: 'warning',
                        title: `Cancelar reserva ${code}`,
                        input: 'textarea',
                        inputLabel: 'Motivo de cancelacion (opcional)',
                        inputPlaceholder: 'Escribe un motivo si deseas registrarlo...',
                        showCancelButton: true,
                        confirmButtonText: 'Cancelar reserva',
                        cancelButtonText: 'Volver',
                        confirmButtonColor: '#dc3545',
                    });

                    if (!result.isConfirmed) {
                        return;
                    }

                    cancellationReason = result.value || '';
                } else if (!window.confirm(`Cancelar la reserva ${code}?`)) {
                    return;
                }

                const formData = new FormData();
                formData.append('cancellation_reason', cancellationReason);

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

                const payload = await response.json();
                window.reservationsTable.ajax.reload(null, false);

                await fireAlert({
                    icon: 'success',
                    title: payload.message || 'Reserva cancelada correctamente.',
                    timer: 1800,
                    showConfirmButton: false,
                });
            }

            async function safeJson(response) {
                try {
                    return await response.clone().json();
                } catch (error) {
                    return {};
                }
            }

            async function handleRequestError(response) {
                let html = 'Ocurrio un error inesperado.';

                try {
                    const payload = await safeJson(response);

                    if (response.status === 422 && payload.errors) {
                        const errors = Object.values(payload.errors).flat();
                        html = `<ul class="text-start mb-0">${errors.map((error) => `<li>${error}</li>`).join('')}</ul>`;
                    } else if (payload.message) {
                        html = payload.message;
                    }
                } catch (error) {
                    html = 'No fue posible procesar la respuesta del servidor.';
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

                const fallbackText = options.text || String(options.html || options.title || '').replace(/<[^>]*>/g, ' ').replace(/\s+/g, ' ').trim();

                if (confirmFallback) {
                    return { isConfirmed: window.confirm(fallbackText) };
                }

                window.alert(fallbackText);
                return { isConfirmed: true };
            }
        });

        function loadScript(src, forceReload = false) {
            return new Promise((resolve, reject) => {
                const existing = document.querySelector(`script[src="${src}"]`);
                if (existing && !forceReload) {
                    resolve();
                    return;
                }

                existing?.remove();

                const script = document.createElement('script');
                script.src = src;
                script.onload = () => {
                    script.dataset.loaded = 'true';
                    resolve();
                };
                script.onerror = reject;
                document.head.appendChild(script);
            });
        }
    </script>
@endpush
