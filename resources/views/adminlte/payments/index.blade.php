@extends('adminlte::page')

@section('title', 'Pagos / Comprobantes')

@php
    $reservationsCatalog = $reservations->map(fn ($reservation) => [
        'id' => $reservation->id,
        'code' => $reservation->code,
        'customer_name' => $reservation->customer?->full_name ?? '-',
        'customer_document_type' => match ($reservation->customer?->document_type) {
            'ci' => 'CI',
            'passport' => 'Pasaporte',
            'nit' => 'NIT',
            'other' => 'Otro',
            default => 'Documento',
        },
        'customer_document' => $reservation->customer?->document_number,
        'total_amount' => (float) $reservation->total_amount,
        'paid_amount' => (float) $reservation->paid_amount,
        'balance_amount' => (float) $reservation->balance_amount,
        'status' => $reservation->status,
        'label' => sprintf(
            '%s - %s - Total %s %s - Saldo %s %s',
            $reservation->code,
            $reservation->customer?->full_name ?? 'Sin cliente',
            $baseCurrency,
            number_format((float) $reservation->total_amount, 2, '.', ''),
            $baseCurrency,
            number_format((float) $reservation->balance_amount, 2, '.', '')
        ),
    ])->values();
@endphp

@section('content_header')
    <div class="payments-hero">
        <div class="payments-hero__content">
            <span class="payments-kicker">Tesoreria operativa</span>
            <h1>Pagos / Comprobantes</h1>
            <p>Registra pagos manuales ya verificados y revisa comprobantes enviados por clientes antes de confirmarlos.</p>
        </div>

        @canany(['pagos.crear', 'pagos.realizar'])
            <button type="button"
                    class="btn btn-payments-primary"
                    id="open-create-payment-modal"
                    @if ($requiresOpenCashRegister && ! $hasOpenCashRegister) data-requires-open-cash="1" @endif>
                <i class="bi bi-cash-coin me-2" aria-hidden="true"></i> Nuevo pago
            </button>
        @endcanany
    </div>
@stop

@section('content')
    <div class="payments-panel">
        <div class="payments-panel__header">
            <div>
                <span class="payments-kicker text-primary">Control de caja</span>
                <h2>Listado de pagos y comprobantes</h2>
                <p>Los pagos creados por el personal se aplican al guardar. Los comprobantes enviados desde la web quedan pendientes hasta su revision.</p>
            </div>
        </div>

        <div class="payments-kpi-grid">
            <div class="payment-kpi payment-kpi--success">
                <span><i class="bi bi-check2-circle me-1"></i>Confirmado y aplicado</span>
                <strong>{{ $paymentStats['confirmed_applied'] }}</strong>
                <small>Este total ya suma como pagado en las reservas.</small>
            </div>
            <div class="payment-kpi payment-kpi--warning">
                <span><i class="bi bi-hourglass-split me-1"></i>Pendiente de revision</span>
                <strong>{{ $paymentStats['pending_count'] }} pago(s)</strong>
                <small>Solo comprobantes enviados por clientes requieren aprobacion.</small>
            </div>
            <div class="payment-kpi payment-kpi--usd">
                <span><i class="bi bi-currency-dollar me-1"></i>Dolares registrados</span>
                <strong>{{ $paymentStats['usd_registered'] }}</strong>
                <small>Se aplica al saldo con el precio USD registrado para la habitacion.</small>
            </div>
            <div class="payment-kpi payment-kpi--danger">
                <span><i class="bi bi-x-circle me-1"></i>Rechazados</span>
                <strong>{{ $paymentStats['rejected_count'] }}</strong>
                <small>No suman hasta ser corregidos y aprobados.</small>
            </div>
        </div>

        <div class="table-responsive">
            <table class="table table-hover align-middle w-100" id="payments-table">
                <thead>
                    <tr>
                        <th>Codigo</th>
                        <th>Reserva</th>
                        <th>Cliente</th>
                        <th>Monto</th>
                        <th>Metodo</th>
                        <th>Comprobante</th>
                        <th>Estado</th>
                        <th>Registro</th>
                        <th class="text-end">Acciones</th>
                    </tr>
                </thead>
            </table>
        </div>
    </div>

    <div class="modal fade" id="create-payment-modal" tabindex="-1" aria-labelledby="create-payment-modal-label" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-scrollable">
            <div class="modal-content">
                <form id="create-payment-form" action="{{ route('adminlte.payments.store') }}" enctype="multipart/form-data">
                    @csrf
                    <input type="hidden" name="_method" id="payment-form-method" value="POST">
                    <input type="hidden" id="editing-payment-id" value="">
                    <div class="modal-header">
                        <div>
                            <span class="payments-kicker text-primary">Registro de pago</span>
                            <h5 class="modal-title" id="create-payment-modal-label">Nuevo pago</h5>
                            <small class="text-muted" id="create-payment-modal-help">Registra un pago para una reserva activa y adjunta el comprobante si corresponde.</small>
                        </div>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                    </div>
                    <div class="modal-body">
                        <div class="row g-3">
                            <div class="col-12">
                                <label class="form-label" for="reservation_id">Reserva</label>
                                <select class="form-select payment-reservation-select" id="reservation_id" name="reservation_id" required>
                                    <option value="">Busca por codigo, cliente, documento o telefono</option>
                                </select>
                                <div class="form-text">Escribe al menos 2 letras o numeros para buscar una reserva activa.</div>
                            </div>

                            <div class="col-12">
                                <div class="payment-reservation-card rounded-3 p-3" id="payment-reservation-summary">
                                    <div class="fw-semibold mb-2">Resumen de reserva</div>
                                    <div class="text-muted mb-0">Selecciona una reserva para ver el total, pagado y saldo pendiente.</div>
                                </div>
                            </div>

                            <div class="col-md-5">
                                <label class="form-label" for="amount">Monto recibido</label>
                                <div class="input-group payment-money-input">
                                    <input type="number" class="form-control" id="amount" name="amount" min="0.01" max="999999" step="0.01" required placeholder="0.00">
                                    <select class="form-select" id="currency" name="currency" required aria-label="Moneda del pago">
                                        @foreach ($supportedCurrencies as $currencyCode => $currencyLabel)
                                            <option value="{{ $currencyCode }}" @selected($currencyCode === $baseCurrency)>{{ $currencyCode }} - {{ $currencyLabel }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="form-text">Registra la moneda real que entrego o transfirio el cliente.</div>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label" for="payment_method">Metodo de pago</label>
                                <select class="form-select" id="payment_method" name="payment_method" required>
                                    <option value="">Selecciona un metodo</option>
                                    @foreach ($paymentMethods as $methodKey => $methodLabel)
                                        <option value="{{ $methodKey }}">{{ $methodLabel }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label" for="payment_date">Fecha de pago</label>
                                <input type="date" class="form-control" id="payment_date" name="payment_date" value="{{ now()->toDateString() }}">
                            </div>
                            <div class="col-12">
                                <div class="payment-reservation-card rounded-3 p-3 h-100" id="payment-conversion-summary">
                                    <div class="fw-semibold mb-2">Aplicacion al saldo</div>
                                    <div class="text-muted mb-0">Al guardar este pago manual, el sistema lo aplica al saldo. Si cubre el anticipo minimo, la reserva pendiente se confirma automaticamente.</div>
                                </div>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label" for="reference_number">Numero de referencia</label>
                                <input type="text" class="form-control" id="reference_number" name="reference_number" maxlength="150">
                                <div class="form-text">Recomendado para QR, banco o tarjeta.</div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label" for="receipt_image">Comprobante</label>
                                <input type="file" class="form-control" id="receipt_image" name="receipt_image" accept=".jpg,.jpeg,.png,.webp,.pdf">
                                <div class="form-text" id="receipt-file-name">Acepta JPG, PNG, WEBP o PDF hasta 10 MB.</div>
                            </div>

                            <div class="col-12">
                                <label class="form-label" for="notes">Notas</label>
                                <textarea class="form-control" id="notes" name="notes" rows="4"></textarea>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-payments-primary">Guardar pago</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@stop

@push('css')
    <link rel="stylesheet" href="{{ asset('vendor/datatables/dataTables.bootstrap5.min.css') }}">
    <link rel="stylesheet" href="{{ asset('vendor/select2/select2.min.css') }}">
    <style>
        .payments-hero {
            position: relative;
            overflow: hidden;
            border-radius: 1.75rem;
            padding: 1.5rem;
            color: #fff;
            background:
                radial-gradient(circle at top right, rgba(34, 197, 94, 0.38), transparent 34%),
                linear-gradient(135deg, #12312f 0%, #155e75 50%, #1d4ed8 100%);
            box-shadow: 0 1.5rem 4rem rgba(15, 23, 42, 0.2);
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 1rem;
        }

        .payments-hero::after {
            content: "";
            position: absolute;
            right: -5rem;
            bottom: -7rem;
            width: 18rem;
            height: 18rem;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.08);
            pointer-events: none;
        }

        .payments-hero__content {
            position: relative;
            z-index: 1;
            max-width: 790px;
        }

        .payments-hero h1 {
            margin: 0;
            font-size: clamp(1.8rem, 3vw, 2.75rem);
            font-weight: 850;
            letter-spacing: -0.04em;
        }

        .payments-hero p {
            max-width: 740px;
            margin: 0.55rem 0 0;
            color: rgba(255, 255, 255, 0.78);
        }

        .payments-kicker {
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

        .payments-kicker::before {
            content: "";
            width: 1.85rem;
            height: 1px;
            background: currentColor;
            opacity: 0.75;
        }

        .btn-payments-primary {
            position: relative;
            z-index: 1;
            border: 0;
            border-radius: 999px;
            padding: 0.78rem 1.15rem;
            color: #fff;
            font-weight: 850;
            background: linear-gradient(135deg, #16a34a, #0f766e);
            box-shadow: 0 1rem 2.3rem rgba(15, 118, 110, 0.28);
        }

        .btn-payments-primary:hover,
        .btn-payments-primary:focus {
            color: #fff;
            transform: translateY(-1px);
            box-shadow: 0 1.2rem 2.8rem rgba(15, 118, 110, 0.36);
        }

        .payments-panel {
            overflow: hidden;
            border: 1px solid rgba(17, 24, 39, 0.08);
            border-radius: 1.5rem;
            background: #fff;
            box-shadow: 0 1.5rem 4rem rgba(17, 24, 39, 0.08);
        }

        .payments-panel__header {
            padding: 1.35rem 1.5rem;
            border-bottom: 1px solid rgba(17, 24, 39, 0.08);
            background:
                linear-gradient(135deg, rgba(22, 163, 74, 0.08), rgba(14, 165, 233, 0.08)),
                #fff;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 1rem;
        }

        .payments-panel__header h2 {
            margin: 0;
            color: #161827;
            font-size: 1.25rem;
            font-weight: 850;
        }

        .payments-panel__header p {
            margin: 0.25rem 0 0;
            color: #6b7280;
        }

        .payments-kpi-grid {
            padding: 1rem 1.25rem;
            border-bottom: 1px solid rgba(17, 24, 39, 0.08);
            background: #fbfaf8;
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: 0.85rem;
        }

        .payment-kpi {
            position: relative;
            overflow: hidden;
            min-height: 116px;
            padding: 1rem;
            border: 1px solid rgba(17, 24, 39, 0.08);
            border-radius: 1.15rem;
            background: #fff;
            box-shadow: 0 0.8rem 1.8rem rgba(17, 24, 39, 0.055);
        }

        .payment-kpi::after {
            content: "";
            position: absolute;
            right: -2.5rem;
            bottom: -2.75rem;
            width: 7rem;
            height: 7rem;
            border-radius: 50%;
            background: currentColor;
            opacity: 0.08;
        }

        .payment-kpi span {
            display: block;
            color: #6b7280;
            font-size: 0.72rem;
            font-weight: 850;
            letter-spacing: 0.07em;
            text-transform: uppercase;
        }

        .payment-kpi strong {
            display: block;
            margin-top: 0.45rem;
            color: #111827;
            font-size: clamp(1.1rem, 2vw, 1.45rem);
            line-height: 1.12;
        }

        .payment-kpi small {
            display: block;
            margin-top: 0.45rem;
            color: #6b7280;
            line-height: 1.25;
        }

        .payment-kpi--success { color: #0f766e; }
        .payment-kpi--warning { color: #b45309; }
        .payment-kpi--usd { color: #1d4ed8; }
        .payment-kpi--danger { color: #b91c1c; }

        #payments-table {
            margin: 0 !important;
        }

        #payments-table thead th {
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

        #payments-table tbody td {
            padding-top: 1rem;
            padding-bottom: 1rem;
            border-color: rgba(17, 24, 39, 0.06);
        }

        #create-payment-modal .modal-dialog {
            height: calc(100vh - 3.5rem);
        }

        #create-payment-modal .modal-content {
            max-height: 100%;
            overflow: hidden;
            border: 0;
            border-radius: 1.5rem;
            box-shadow: 0 2rem 5rem rgba(17, 24, 39, 0.22);
        }

        #create-payment-modal form {
            display: flex;
            flex-direction: column;
            min-height: 0;
            height: 100%;
        }

        #create-payment-modal .modal-body {
            min-height: 0;
            overflow-y: auto;
            overscroll-behavior: contain;
            background: #fbfaf8;
        }

        #create-payment-modal .modal-header,
        #create-payment-modal .modal-footer {
            background: #fff;
            border-color: rgba(17, 24, 39, 0.08);
        }

        .payment-reservation-card,
        .payment-recommended {
            border: 1px dashed rgba(15, 118, 110, 0.18);
            background:
                linear-gradient(135deg, rgba(22, 163, 74, 0.06), rgba(14, 165, 233, 0.07)),
                #fff;
            box-shadow: 0 0.75rem 1.8rem rgba(17, 24, 39, 0.04);
        }

        .payment-money-input .form-control {
            min-width: 0;
        }

        .payment-money-input #currency {
            max-width: 6.5rem;
            flex: 0 0 6.5rem;
            font-weight: 850;
        }

        #create-payment-modal .form-label {
            color: #374151;
            font-size: 0.82rem;
            font-weight: 850;
        }

        #create-payment-modal .form-control,
        #create-payment-modal .form-select {
            border-radius: 0.85rem;
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

        .payment-reservation-option {
            display: grid;
            gap: 0.25rem;
            padding: 0.15rem 0;
        }

        .payment-reservation-option strong {
            color: #f8fafc;
            font-weight: 850;
        }

        .payment-reservation-option small {
            display: flex;
            flex-wrap: wrap;
            gap: 0.7rem;
            color: #cbd5e1;
            font-size: 0.78rem;
        }

        @media (max-width: 767.98px) {
            .payments-hero,
            .payments-panel__header {
                align-items: stretch;
                flex-direction: column;
            }

            .payments-kpi-grid {
                grid-template-columns: 1fr;
            }

            .btn-payments-primary {
                width: 100%;
            }

        }

        @media (min-width: 768px) and (max-width: 1199.98px) {
            .payments-kpi-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr));
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
            const reservationSearchUrl = '{{ route('adminlte.payments.reservation-search') }}';
            const requiresOpenCashRegister = @json($requiresOpenCashRegister);
            const hasOpenCashRegister = @json($hasOpenCashRegister);
            const openCashRegisterUrl = @json(route('adminlte.cash-registers.index'));
            const reservationsCatalog = @json($reservationsCatalog);
            const reservationsMap = new Map(reservationsCatalog.map((reservation) => [String(reservation.id), reservation]));
            const createForm = document.getElementById('create-payment-form');
            const createModal = window.bootstrap ? new window.bootstrap.Modal(document.getElementById('create-payment-modal')) : null;
            const reservationSelect = document.getElementById('reservation_id');
            const amountInput = document.getElementById('amount');
            const currencySelect = document.getElementById('currency');
            const paymentMethodSelect = document.getElementById('payment_method');
            const referenceInput = document.getElementById('reference_number');
            const receiptInput = document.getElementById('receipt_image');
            const summaryCard = document.getElementById('payment-reservation-summary');
            const conversionCard = document.getElementById('payment-conversion-summary');
            const receiptFileName = document.getElementById('receipt-file-name');
            const baseCurrency = @json($baseCurrency);
            const currencySymbols = @json($currencySymbols ?? []);

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

            initializeReservationSelect();

            window.paymentsTable = $('#payments-table').DataTable({
                processing: true,
                serverSide: true,
                ajax: '{{ route('adminlte.payments.data') }}',
                language: {
                    url: 'https://cdn.datatables.net/plug-ins/1.13.8/i18n/es-ES.json',
                },
                order: [[7, 'desc']],
                columns: [
                    {
                        data: 'code',
                        name: 'code',
                        render: (data, type, row) => type === 'display'
                            ? `<div class="fw-semibold">${row.code}</div><div class="small text-muted">${row.payment_date_formatted}</div>`
                            : data
                    },
                    {
                        data: 'reservation_code',
                        name: 'reservation.code',
                        render: (data, type, row) => {
                            if (type !== 'display') {
                                return data;
                            }

                            const source = row.reservation_source_label ? `<div class="small text-muted">Origen: ${row.reservation_source_label}</div>` : '';
                            const dates = `<div class="small text-muted">Estadia: ${row.reservation_check_in_formatted} - ${row.reservation_check_out_formatted}</div>`;
                            const requestedPayment = `<div class="small text-muted">Cliente pidio pagar por: ${row.reservation_requested_payment_label}</div>`;
                            const specialRequests = row.reservation_special_requests
                                ? `<div class="small text-muted">Pedido: ${escapeHtml(row.reservation_special_requests)}</div>`
                                : '';
                            const autoConfirm = row.will_confirm_reservation
                                ? '<div class="small text-success fw-semibold"><i class="bi bi-shield-check me-1"></i>Al aprobar, confirma la reserva</div>'
                                : '';

                            return `<div class="fw-semibold">${row.reservation_code}</div>
                                <span class="badge text-bg-light border">${row.reservation_status_label}</span>
                                ${source}
                                ${dates}
                                ${requestedPayment}
                                ${specialRequests}
                                <div class="small text-muted">Anticipo requerido ${row.reservation_deposit_required_payment_currency_formatted || row.reservation_deposit_required_formatted}</div>
                                <div class="small text-muted">Anticipo pendiente ${row.reservation_deposit_pending_payment_currency_formatted || row.reservation_deposit_pending_formatted}</div>
                                <div class="small text-muted">Total ${row.reservation_total_payment_currency_formatted || row.reservation_total_formatted}</div>
                                <div class="small text-muted">Pagado ${row.reservation_paid_payment_currency_formatted || row.reservation_paid_formatted}</div>
                                <div class="small text-muted">Saldo ${row.reservation_balance_payment_currency_formatted || row.reservation_balance_formatted}</div>
                                ${autoConfirm}`;
                        }
                    },
                    { data: 'customer_name', name: 'customer.full_name' },
                    {
                        data: 'amount_formatted',
                        name: 'amount_base',
                        className: 'text-nowrap fw-semibold',
                        render: (data, type, row) => {
                            if (type !== 'display') {
                                return row.amount_raw;
                            }

                            const baseLine = row.affects_balance
                                ? `<div class="small text-muted">Aplica al saldo de la reserva</div>`
                                : '<div class="small text-muted">No aplica saldo automatico</div>';

                            return `<div>${row.amount_formatted}</div>${baseLine}`;
                        }
                    },
                    {
                        data: 'payment_method_label',
                        name: 'payment_method',
                        render: (data, type, row) => {
                            if (type !== 'display') {
                                return data;
                            }

                            const reference = row.reference_number ? `<div class="small text-muted">${row.reference_number}</div>` : '';
                            return `<div>${row.payment_method_label}</div>${reference}`;
                        }
                    },
                    {
                        data: 'receipt_url',
                        orderable: false,
                        searchable: false,
                        className: 'text-center',
                        render: (data, type, row) => {
                            if (type !== 'display') {
                                return data || '';
                            }

                            if (!row.receipt_url) {
                                return '<span class="text-muted">Sin comprobante</span>';
                            }

                            return `<a href="${row.receipt_url}" class="btn btn-outline-secondary btn-sm" target="_blank" rel="noopener">Ver comprobante</a>`;
                        }
                    },
                    {
                        data: 'status',
                        name: 'status',
                        className: 'text-center',
                        render: (data, type, row) => type === 'display'
                            ? `<span class="${row.status_badge_class}">${row.status_label}</span>`
                            : data
                    },
                    {
                        data: 'created_at_formatted',
                        name: 'created_at',
                        render: (data, type, row) => {
                            if (type !== 'display') {
                                return data;
                            }

                            const createdBy = row.created_by_name ? `<div class="small text-muted">Registrado por ${row.created_by_name}</div>` : '';
                            const confirmedBy = row.confirmed_by_name ? `<div class="small text-muted">Confirmado por ${row.confirmed_by_name}</div>` : '';
                            return `<div>${row.created_at_formatted}</div>${createdBy}${confirmedBy}`;
                        }
                    },
                    {
                        data: null,
                        orderable: false,
                        searchable: false,
                        className: 'text-end',
                        render: (data, type, row) => {
                            if (type !== 'display') {
                                return '';
                            }

                            let actions = '<div class="btn-group btn-group-sm flex-wrap justify-content-end" role="group">';

                            if (row.can_update) {
                                actions += `<button type="button" class="btn btn-outline-primary payment-edit-btn"
                                    data-id="${row.id}"
                                    data-url="${row.update_url}"
                                    data-code="${row.code}"
                                    data-reservation-code="${row.reservation_code}"
                                    data-reservation-id="${row.reservation_id}"
                                    data-reservation-status="${row.reservation_status}"
                                    data-reservation-total="${row.reservation_total_raw}"
                                    data-reservation-paid="${row.reservation_paid_raw}"
                                    data-reservation-balance="${row.reservation_balance_raw}"
                                    data-reservation-supports-usd="${row.reservation_supports_usd ? '1' : '0'}"
                                    data-reservation-display-currency="${row.reservation_display_currency || row.currency_raw || baseCurrency}"
                                    data-reservation-locked-payment-currency="${row.reservation_locked_payment_currency || row.currency_raw || ''}"
                                    data-reservation-total-usd="${row.reservation_total_usd_raw || 0}"
                                    data-reservation-paid-usd="${row.reservation_paid_usd_raw || 0}"
                                    data-reservation-balance-usd="${row.reservation_balance_usd_raw || 0}"
                                    data-customer-name="${(row.customer_name ?? '').replace(/"/g, '&quot;')}"
                                    data-customer-document-type-label="${row.customer_document_type_label ?? 'Documento'}"
                                    data-customer-document="${row.customer_document ?? ''}"
                                    data-amount="${row.amount_raw}"
                                    data-currency="${row.currency_raw}"
                                    data-payment-method="${row.payment_method_raw}"
                                    data-payment-date="${row.payment_date_raw ?? ''}"
                                    data-reference-number="${row.reference_number ?? ''}"
                                    data-notes="${(row.notes_raw ?? '').replace(/"/g, '&quot;')}"
                                    data-rejection-reason="${(row.rejection_reason ?? '').replace(/"/g, '&quot;')}"
                                >
                                    <i class="bi bi-pencil-square"></i>
                                </button>`;
                            }

                            if (row.can_confirm) {
                                actions += `<button type="button" class="btn btn-outline-success payment-confirm-btn" data-url="${row.confirm_url}" data-code="${row.code}" data-will-confirm-reservation="${row.will_confirm_reservation ? '1' : '0'}" data-reservation-code="${row.reservation_code}" data-requires-open-cash-register="${row.requires_open_cash_register_to_confirm ? '1' : '0'}">
                                    <i class="bi bi-check2-circle"></i>
                                </button>`;
                            }

                            if (row.can_reject) {
                                actions += `<button type="button" class="btn btn-outline-danger payment-reject-btn" data-url="${row.reject_url}" data-code="${row.code}" data-action="rechazar">
                                    <i class="bi bi-x-circle"></i>
                                </button>`;
                            }

                            if (row.can_cancel) {
                                actions += `<button type="button" class="btn btn-outline-secondary payment-cancel-btn" data-url="${row.cancel_url}" data-code="${row.code}" data-action="anular">
                                    <i class="bi bi-slash-circle"></i>
                                </button>`;
                            }

                            if (row.can_refund) {
                                actions += `<button type="button" class="btn btn-outline-info payment-refund-btn" data-url="${row.refund_url}" data-code="${row.code}" data-action="devolver">
                                    <i class="bi bi-arrow-counterclockwise"></i>
                                </button>`;
                            }

                            actions += '</div>';
                            return actions;
                        }
                    }
                ],
            });

            document.getElementById('open-create-payment-modal')?.addEventListener('click', () => {
                if (mustOpenCashRegisterFirst()) {
                    showOpenCashRegisterWarning();
                    return;
                }

                resetPaymentForm();
                createModal?.show();
            });

            reservationSelect.addEventListener('change', updateReservationSummary);
            paymentMethodSelect.addEventListener('change', toggleRecommendedPaymentFields);
            currencySelect.addEventListener('change', () => {
                const reservation = reservationsMap.get(String(reservationSelect.value || ''));
                const isEditing = Boolean(document.getElementById('editing-payment-id').value);

                if (reservation && !isEditing) {
                    amountInput.value = getReservationAmount(reservation, 'balance_amount', currencySelect.value || baseCurrency).toFixed(2);
                }

                updateReservationSummary();
            });
            amountInput.addEventListener('input', updateConversionSummary);
            receiptInput.addEventListener('change', updateReceiptFileName);

            createForm.addEventListener('submit', async (event) => {
                event.preventDefault();
                await submitPaymentForm();
            });

            document.addEventListener('click', async (event) => {
                const confirmButton = event.target.closest('.payment-confirm-btn');
                if (confirmButton) {
                    if (confirmButton.dataset.requiresOpenCashRegister === '1' && mustOpenCashRegisterFirst()) {
                        showOpenCashRegisterWarning();
                        return;
                    }

                    const autoConfirmText = confirmButton.dataset.willConfirmReservation === '1'
                        ? ` Este pago cubre el anticipo: tambien se confirmara automaticamente la reserva ${confirmButton.dataset.reservationCode}.`
                        : '';
                    await processPaymentAction(confirmButton.dataset.url, `Confirmar el pago ${confirmButton.dataset.code}?${autoConfirmText}`);
                    return;
                }

                const rejectButton = event.target.closest('.payment-reject-btn');
                if (rejectButton) {
                    await processPaymentActionWithReason(rejectButton.dataset.url, rejectButton.dataset.code, 'Rechazar pago', 'Motivo de rechazo (opcional)');
                    return;
                }

                const editButton = event.target.closest('.payment-edit-btn');
                if (editButton) {
                    openEditPaymentModal(editButton.dataset);
                    return;
                }

                const cancelButton = event.target.closest('.payment-cancel-btn');
                if (cancelButton) {
                    await processPaymentActionWithReason(cancelButton.dataset.url, cancelButton.dataset.code, 'Anular pago', 'Motivo de anulacion (opcional)');
                    return;
                }

                const refundButton = event.target.closest('.payment-refund-btn');
                if (refundButton) {
                    await processPaymentActionWithReason(refundButton.dataset.url, refundButton.dataset.code, 'Devolver pago', 'Motivo de devolucion (opcional)');
                }
            });

            function mustOpenCashRegisterFirst() {
                return requiresOpenCashRegister && !hasOpenCashRegister;
            }

            async function showOpenCashRegisterWarning() {
                const result = await fireAlert({
                    icon: 'warning',
                    title: 'Primero debes abrir caja',
                    text: 'Para registrar o confirmar ingresos durante tu turno, abre tu caja desde Caja.',
                    showCancelButton: true,
                    confirmButtonText: 'Ir a abrir caja',
                    cancelButtonText: 'Cancelar',
                }, true);

                if (result.isConfirmed) {
                    window.location.href = openCashRegisterUrl;
                }
            }

            function resetPaymentForm() {
                createForm.reset();
                createForm.action = '{{ route('adminlte.payments.store') }}';
                document.getElementById('payment-form-method').value = 'POST';
                document.getElementById('editing-payment-id').value = '';
                document.getElementById('create-payment-modal-label').textContent = 'Nuevo pago';
                document.getElementById('create-payment-modal-help').textContent = 'Registra un pago para una reserva activa y adjunta el comprobante si corresponde.';
                reservationSelect.disabled = false;
                setReservationSelectValue('');
                document.getElementById('payment_date').value = '{{ now()->toDateString() }}';
                amountInput.value = '';
                currencySelect.value = baseCurrency;
                currencySelect.disabled = false;
                summaryCard.innerHTML = `
                    <div class="fw-semibold mb-2">Resumen de reserva</div>
                    <div class="text-muted mb-0">Selecciona una reserva para ver el total, pagado y saldo pendiente.</div>
                `;
                conversionCard.innerHTML = `
                    <div class="fw-semibold mb-2">Aplicacion al saldo</div>
                    <div class="text-muted mb-0">Al guardar el pago manual, el sistema lo aplica automaticamente al saldo de la reserva.</div>
                `;
                receiptFileName.textContent = 'Acepta JPG, PNG, WEBP o PDF hasta 10 MB.';
                updateConversionSummary();
                toggleRecommendedPaymentFields();
            }

            function openEditPaymentModal(dataset) {
                resetPaymentForm();
                createForm.action = dataset.url;
                document.getElementById('payment-form-method').value = 'PUT';
                document.getElementById('editing-payment-id').value = dataset.id;
                document.getElementById('create-payment-modal-label').textContent = `Editar pago ${dataset.code}`;
                document.getElementById('create-payment-modal-help').textContent = 'Puedes corregir pagos pendientes, rechazados o confirmados. El sistema no permitira dejar la reserva por debajo del anticipo minimo.';
                const documentLabel = dataset.customerDocument
                    ? `${dataset.customerDocumentTypeLabel || 'Documento'} ${dataset.customerDocument}`
                    : '';
                const customerLabel = [dataset.customerName || 'Sin cliente', documentLabel].filter(Boolean).join(' - ');
                const reservationPayload = {
                    id: dataset.reservationId || '',
                    text: `${dataset.reservationCode || 'Reserva'} - ${customerLabel}`,
                    code: dataset.reservationCode || '',
                    customer_name: dataset.customerName || 'Sin cliente',
                    customer_document_type: dataset.customerDocumentTypeLabel || 'Documento',
                    customer_document: dataset.customerDocument || '',
                    status: dataset.reservationStatus || '',
                    total_amount: Number(dataset.reservationTotal || 0),
                    paid_amount: Number(dataset.reservationPaid || 0),
                    balance_amount: Number(dataset.reservationBalance || 0),
                    supports_usd: dataset.reservationSupportsUsd === '1',
                    total_amount_usd: Number(dataset.reservationTotalUsd || 0),
                    paid_amount_usd: Number(dataset.reservationPaidUsd || 0),
                    balance_amount_usd: Number(dataset.reservationBalanceUsd || 0),
                    display_currency: dataset.reservationDisplayCurrency || dataset.currency || baseCurrency,
                    locked_payment_currency: dataset.reservationLockedPaymentCurrency || dataset.currency || null,
                };
                if (reservationPayload.id) {
                    reservationsMap.set(String(reservationPayload.id), reservationPayload);
                }
                setReservationSelectValue(reservationPayload.id, reservationPayload.text);
                reservationSelect.disabled = true;
                amountInput.value = Number(dataset.amount || 0).toFixed(2);
                currencySelect.value = dataset.currency || baseCurrency;
                currencySelect.disabled = Boolean(reservationPayload.locked_payment_currency);
                paymentMethodSelect.value = dataset.paymentMethod || '';
                document.getElementById('payment_date').value = dataset.paymentDate || '';
                referenceInput.value = dataset.referenceNumber || '';
                document.getElementById('notes').value = dataset.notes || '';
                updateReservationSummary();
                updateConversionSummary();
                toggleRecommendedPaymentFields();

                if (dataset.rejectionReason) {
                    summaryCard.insertAdjacentHTML('beforeend', `
                        <div class="alert alert-warning mt-3 mb-0">
                            <strong>Motivo de rechazo anterior:</strong> ${dataset.rejectionReason}
                        </div>
                    `);
                }

                createModal?.show();
            }

            function initializeReservationSelect() {
                if (typeof $.fn.select2 !== 'function') {
                    console.warn('Select2 no esta disponible para el buscador de reservas.');
                    return;
                }

                $('.payment-reservation-select').select2({
                    width: '100%',
                    dropdownParent: $('#create-payment-modal'),
                    placeholder: 'Buscar por codigo, cliente o saldo',
                    allowClear: false,
                    minimumInputLength: 2,
                    ajax: {
                        url: reservationSearchUrl,
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
                    templateResult: renderReservationOption,
                    templateSelection: (reservation) => escapeHtml(reservationSelectionLabel(reservation)),
                    escapeMarkup: (markup) => markup,
                    language: {
                        inputTooShort: () => 'Escribe al menos 2 letras o numeros',
                        noResults: () => 'Sin resultados',
                        searching: () => 'Buscando...',
                        loadingMore: () => 'Cargando mas reservas...',
                        errorLoading: () => 'No se pudieron cargar las reservas',
                    },
                }).on('select2:select', (event) => {
                    const reservation = event.params.data;
                    reservationsMap.set(String(reservation.id), reservation);
                    updateReservationSummary();
                });
            }

            function renderReservationOption(reservation) {
                if (reservation.loading) {
                    return reservation.text;
                }

                const title = escapeHtml(reservationSelectionLabel(reservation));
                const status = reservation.status ? `<span><i class="bi bi-info-circle me-1"></i>${escapeHtml(reservation.status)}</span>` : '';
                const phone = reservation.customer_phone ? `<span><i class="bi bi-telephone me-1"></i>${escapeHtml(reservation.customer_phone)}</span>` : '';
                const email = reservation.customer_email ? `<span><i class="bi bi-envelope me-1"></i>${escapeHtml(reservation.customer_email)}</span>` : '';
                const balance = `<span><i class="bi bi-wallet2 me-1"></i>Saldo ${formatReservationAmount(reservation, 'balance_amount', currencySelect.value || baseCurrency)}</span>`;
                const details = [status, phone, email, balance].filter(Boolean).join('');

                return `
                    <div class="payment-reservation-option">
                        <strong>${title}</strong>
                        <small>${details}</small>
                    </div>
                `;
            }

            function reservationSelectionLabel(reservation) {
                const document = reservation.customer_document
                    ? `${reservation.customer_document_type || 'Documento'} ${reservation.customer_document}`
                    : '';
                const customer = [reservation.customer_name || reservation.text || 'Sin cliente', document].filter(Boolean).join(' - ');

                return reservation.code ? `${reservation.code} - ${customer}` : customer;
            }

            function setReservationSelectValue(value, text = '') {
                if (!value) {
                    reservationSelect.value = '';
                    if (typeof $.fn.select2 === 'function' && $(reservationSelect).data('select2')) {
                        $(reservationSelect).val(null).trigger('change');
                    }

                    return;
                }

                if (text && !reservationSelect.querySelector(`option[value="${value}"]`)) {
                    reservationSelect.append(new Option(text, value, true, true));
                }

                reservationSelect.value = value;

                if (typeof $.fn.select2 === 'function' && $(reservationSelect).data('select2')) {
                    $(reservationSelect).val(value).trigger('change');
                }
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

            function updateReservationSummary() {
                const reservation = reservationsMap.get(String(reservationSelect.value || ''));

                if (!reservation) {
                    summaryCard.innerHTML = `
                        <div class="fw-semibold mb-2">Resumen de reserva</div>
                        <div class="text-muted mb-0">Selecciona una reserva para ver el total, pagado y saldo pendiente.</div>
                    `;
                    currencySelect.disabled = false;
                    return;
                }

                applyReservationCurrencyLock(reservation);
                const isEditing = Boolean(document.getElementById('editing-payment-id').value);
                if (!isEditing && !amountInput.value) {
                    amountInput.value = getReservationAmount(reservation, 'balance_amount', currencySelect.value || baseCurrency).toFixed(2);
                }

                const customerDocument = reservation.customer_document
                    ? `${reservation.customer_document_type || 'Documento'} ${reservation.customer_document}`
                    : '';
                const customerName = [reservation.customer_name, customerDocument].filter(Boolean).join(' - ');

                summaryCard.innerHTML = `
                    <div class="fw-semibold mb-2">Resumen de reserva</div>
                    <div class="row g-2">
                        <div class="col-md-4"><span class="text-muted d-block small">Reserva</span><span class="fw-semibold">${reservation.code}</span></div>
                        <div class="col-md-4"><span class="text-muted d-block small">Cliente</span><span class="fw-semibold">${customerName || 'Sin cliente'}</span></div>
                        <div class="col-md-4"><span class="text-muted d-block small">Estado</span><span class="fw-semibold">${reservation.status}</span></div>
                        <div class="col-md-4"><span class="text-muted d-block small">Total</span><span class="fw-semibold">${formatReservationAmount(reservation, 'total_amount', currencySelect.value || baseCurrency)}</span></div>
                        <div class="col-md-4"><span class="text-muted d-block small">Pagado</span><span class="fw-semibold">${formatReservationAmount(reservation, 'paid_amount', currencySelect.value || baseCurrency)}</span></div>
                        <div class="col-md-4"><span class="text-muted d-block small">Saldo</span><span class="fw-semibold">${formatReservationAmount(reservation, 'balance_amount', currencySelect.value || baseCurrency)}</span></div>
                    </div>
                `;

                updateConversionSummary();
            }

            function applyReservationCurrencyLock(reservation) {
                const lockedCurrency = reservation.locked_payment_currency || null;
                const displayCurrency = reservation.display_currency || lockedCurrency || baseCurrency;

                if (lockedCurrency) {
                    currencySelect.value = displayCurrency;
                    currencySelect.disabled = true;
                    return;
                }

                currencySelect.disabled = false;
                if (!currencySelect.value) {
                    currencySelect.value = displayCurrency;
                }
            }

            function toggleRecommendedPaymentFields() {
                const isRecommended = ['qr', 'bank', 'card'].includes(paymentMethodSelect.value);
                referenceInput.classList.toggle('payment-recommended', isRecommended);
                receiptInput.classList.toggle('payment-recommended', isRecommended);
            }

            function updateConversionSummary() {
                const reservation = reservationsMap.get(String(reservationSelect.value || ''));
                const amount = Number(amountInput.value || 0);
                const currency = currencySelect.value || baseCurrency;

                if (!reservation || !amount) {
                    conversionCard.innerHTML = `
                        <div class="fw-semibold mb-2">Aplicacion al saldo</div>
                        <div class="text-muted mb-0">Al guardar el pago manual, el sistema lo aplica automaticamente al saldo de la reserva.</div>
                    `;
                    return;
                }

                if (currency === baseCurrency) {
                    conversionCard.innerHTML = `
                        <div class="fw-semibold mb-2">Aplicacion al saldo</div>
                        <div class="row g-2">
                            <div class="col-md-4"><span class="text-muted d-block small">Pago ingresado</span><span class="fw-semibold">${currency} ${amount.toFixed(2)}</span></div>
                            <div class="col-md-4"><span class="text-muted d-block small">Saldo actual</span><span class="fw-semibold">${formatReservationAmount(reservation, 'balance_amount', currency)}</span></div>
                            <div class="col-md-4"><span class="text-muted d-block small">Aplicacion automatica</span><span class="fw-semibold">${formatMoney(amount, currency)}</span></div>
                        </div>
                    `;

                    return;
                }

                conversionCard.innerHTML = `
                    <div class="fw-semibold mb-2">Aplicacion al saldo</div>
                    <div class="row g-2">
                        <div class="col-md-4"><span class="text-muted d-block small">Pago ingresado</span><span class="fw-semibold">${currency} ${amount.toFixed(2)}</span></div>
                        <div class="col-md-4"><span class="text-muted d-block small">Saldo actual</span><span class="fw-semibold">${formatReservationAmount(reservation, 'balance_amount', currency)}</span></div>
                        <div class="col-md-4"><span class="text-muted d-block small">Aplicacion automatica</span><span class="fw-semibold">${formatMoney(amount, currency)}</span></div>
                        <div class="col-md-12"><span class="text-muted d-block small">Nota</span><span class="fw-semibold">El pago se registrara y se mostrara en ${currency}. El saldo interno se mantiene conciliado por el sistema.</span></div>
                    </div>
                `;
            }

            function formatReservationAmount(reservation, field, currency) {
                const amount = getReservationAmount(reservation, field, currency);
                const normalizedCurrency = String(currency || baseCurrency).toUpperCase();

                if (normalizedCurrency === 'USD' && !reservation.supports_usd) {
                    return 'USD no configurado';
                }

                return formatMoney(amount, normalizedCurrency === 'USD' ? 'USD' : 'BOB');
            }

            function getReservationAmount(reservation, field, currency) {
                const normalizedCurrency = String(currency || baseCurrency).toUpperCase();

                if (normalizedCurrency === 'USD' && reservation.supports_usd) {
                    return Number(reservation[`${field}_usd`] || 0);
                }

                return Number(reservation[field] || 0);
            }

            function formatMoney(amount, currency) {
                const normalizedCurrency = String(currency || baseCurrency).toUpperCase();
                const symbol = currencySymbols[normalizedCurrency] || normalizedCurrency;

                return `${symbol} ${Number(amount || 0).toFixed(2)}`;
            }

            function updateReceiptFileName() {
                const file = receiptInput.files?.[0];
                receiptFileName.textContent = file ? `Archivo seleccionado: ${file.name}` : 'Acepta JPG, PNG, WEBP o PDF hasta 10 MB.';
            }

            async function submitPaymentForm() {
                const formData = new FormData(createForm);
                formData.set('currency', currencySelect.value || baseCurrency);

                const response = await fetch(createForm.action, {
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

                const payload = await parseJsonResponse(response);
                if (await stopOnNonJsonResponse(payload)) {
                    return;
                }

                createModal?.hide();
                resetPaymentForm();
                window.paymentsTable.ajax.reload(null, false);

                await fireAlert({
                    icon: 'success',
                    title: payload.message || 'Pago registrado correctamente.',
                    timer: 1800,
                    showConfirmButton: false,
                });
            }

            async function processPaymentAction(url, text) {
                const confirmation = await fireAlert({
                    icon: 'question',
                    title: 'Confirmar accion',
                    text,
                    showCancelButton: true,
                    confirmButtonText: 'Si, continuar',
                    cancelButtonText: 'Cancelar',
                }, true);

                if (!confirmation.isConfirmed) {
                    return;
                }

                const response = await fetch(url, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': csrfToken,
                        'Accept': 'application/json',
                    },
                });

                if (!response.ok) {
                    await handleRequestError(response);
                    return;
                }

                const payload = await parseJsonResponse(response);
                if (await stopOnNonJsonResponse(payload)) {
                    return;
                }

                window.paymentsTable.ajax.reload(null, false);

                await fireAlert({
                    icon: 'success',
                    title: payload.message || 'Operacion completada correctamente.',
                    timer: 1800,
                    showConfirmButton: false,
                });
            }

            async function processPaymentActionWithReason(url, code, title, inputLabel) {
                let reason = '';

                if (swal) {
                    const result = await swal.fire({
                        icon: 'warning',
                        title: `${title} ${code}`,
                        input: 'textarea',
                        inputLabel,
                        inputPlaceholder: 'Escribe un motivo si deseas registrarlo...',
                        showCancelButton: true,
                        confirmButtonText: 'Continuar',
                        cancelButtonText: 'Volver',
                    });

                    if (!result.isConfirmed) {
                        return;
                    }

                    reason = result.value || '';
                } else if (!window.confirm(`${title} ${code}?`)) {
                    return;
                }

                const formData = new FormData();
                formData.append('reason', reason);

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

                const payload = await parseJsonResponse(response);
                if (await stopOnNonJsonResponse(payload)) {
                    return;
                }

                window.paymentsTable.ajax.reload(null, false);

                await fireAlert({
                    icon: 'success',
                    title: payload.message || 'Operacion completada correctamente.',
                    timer: 1800,
                    showConfirmButton: false,
                });
            }

            async function handleRequestError(response) {
                let html = 'Ocurrio un error inesperado.';

                try {
                    const payload = await parseJsonResponse(response);
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

            async function parseJsonResponse(response) {
                const contentType = response.headers.get('content-type') || '';

                if (contentType.includes('application/json')) {
                    return response.json();
                }

                const html = await response.text();
                const isLoginRedirect = html.includes('<form') && html.includes('login');

                return {
                    nonJson: true,
                    message: isLoginRedirect
                        ? 'Tu sesion expiro. Vuelve a iniciar sesion e intenta nuevamente.'
                        : 'El servidor devolvio una pagina HTML en vez de JSON. Revisa el log de Laravel para ver el detalle del error.',
                };
            }

            async function stopOnNonJsonResponse(payload) {
                if (!payload?.nonJson) {
                    return false;
                }

                await fireAlert({
                    icon: 'error',
                    title: 'Respuesta inesperada del servidor',
                    text: payload.message,
                });

                return true;
            }

            async function fireAlert(options, confirmFallback = false) {
                if (swal) {
                    return swal.fire(options);
                }

                if (confirmFallback) {
                    return { isConfirmed: window.confirm(options.text || options.title || '') };
                }

                window.alert(options.text || options.title || '');
                return { isConfirmed: true };
            }

            updateConversionSummary();
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
