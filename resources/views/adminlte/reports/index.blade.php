@extends('adminlte::page')

@section('title', 'Reportes')

@section('content_header')
    <div class="reports-hero">
        <div class="reports-hero-copy">
            <span class="reports-eyebrow">Inteligencia hotelera</span>
            <h1 class="m-0">Reportes</h1>
            <p class="mb-0">Analiza reservas, ingresos, pagos, caja, ocupacion y clientes con una lectura ejecutiva para tomar mejores decisiones.</p>
        </div>
        <div class="reports-period-card">
            <span>Periodo actual</span>
            <strong>{{ \Illuminate\Support\Carbon::parse($defaultFilters['date_from'])->format('d/m/Y') }}</strong>
            <small>hasta {{ \Illuminate\Support\Carbon::parse($defaultFilters['date_to'])->format('d/m/Y') }}</small>
        </div>
    </div>
@stop

@section('content')
    <div class="reports-shell">
        <div class="row g-3 mb-4 reports-kpi-row">
            <div class="col-md-6 col-xl">
                <div class="reports-kpi-card">
                    <i class="bi bi-calendar-check"></i>
                    <span>Reservas del periodo</span>
                    <strong>{{ $reservationReport['summary']['total_reservations'] }}</strong>
                    <small>Solicitudes y reservas registradas</small>
                </div>
            </div>
            <div class="col-md-6 col-xl">
                <div class="reports-kpi-card is-income">
                    <i class="bi bi-cash-coin"></i>
                    <span>Ingresos confirmados</span>
                    <strong>Bs. {{ number_format($incomeReport['summary']['total_confirmed_income'], 2, '.', '') }}</strong>
                    <small>Pagos confirmados en el periodo</small>
                </div>
            </div>
            <div class="col-md-6 col-xl">
                <div class="reports-kpi-card is-warning">
                    <i class="bi bi-hourglass-split"></i>
                    <span>Pagos pendientes</span>
                    <strong>Bs. {{ number_format($paymentReport['summary']['pending_amount'], 2, '.', '') }}</strong>
                    <small>Comprobantes o saldos por revisar</small>
                </div>
            </div>
            <div class="col-md-6 col-xl">
                <div class="reports-kpi-card is-danger">
                    <i class="bi bi-door-open"></i>
                    <span>Ocupacion actual</span>
                    <strong>{{ $occupancyReport['summary']['occupancy_rate'] }}%</strong>
                    <small>Uso de habitaciones segun filtros</small>
                </div>
            </div>
            <div class="col-md-6 col-xl">
                <div class="reports-kpi-card is-muted">
                    <i class="bi bi-receipt"></i>
                    <span>Saldo pendiente</span>
                    <strong>Bs. {{ number_format($reservationReport['summary']['balance_amount'], 2, '.', '') }}</strong>
                    <small>Monto restante por cobrar</small>
                </div>
            </div>
        </div>

        <div class="reports-workspace">
            <div class="reports-tabs-wrap">
                <ul class="nav reports-tabs" id="reports-tabs" role="tablist">
                    <li class="nav-item"><button class="nav-link active" data-bs-toggle="tab" data-bs-target="#report-tab-reservations" type="button"><i class="bi bi-calendar2-check"></i>Reservas</button></li>
                    <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#report-tab-income" type="button"><i class="bi bi-graph-up-arrow"></i>Ingresos</button></li>
                    <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#report-tab-payments" type="button"><i class="bi bi-credit-card"></i>Pagos</button></li>
                    <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#report-tab-cash-registers" type="button"><i class="bi bi-safe2"></i>Caja / Turnos</button></li>
                    <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#report-tab-occupancy" type="button"><i class="bi bi-building-check"></i>Ocupacion</button></li>
                    <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#report-tab-customers" type="button"><i class="bi bi-people"></i>Clientes</button></li>
                    <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#report-tab-hotel-chamber" type="button"><i class="bi bi-file-earmark-spreadsheet"></i>Camara hotelera</button></li>
                </ul>
            </div>
            <div class="reports-content-card">
                <div class="tab-content">
                    <div class="tab-pane fade show active" id="report-tab-reservations">@include('adminlte.reports.partials.reservations', ['report' => $reservationReport, 'filterOptions' => $filterOptions, 'canExport' => auth()->user()->can('reportes.exportar')])</div>
                    <div class="tab-pane fade" id="report-tab-income">@include('adminlte.reports.partials.income', ['report' => $incomeReport, 'filterOptions' => $filterOptions, 'canExport' => auth()->user()->can('reportes.exportar')])</div>
                    <div class="tab-pane fade" id="report-tab-payments">@include('adminlte.reports.partials.payments', ['report' => $paymentReport, 'filterOptions' => $filterOptions, 'canExport' => auth()->user()->can('reportes.exportar')])</div>
                    <div class="tab-pane fade" id="report-tab-cash-registers">@include('adminlte.reports.partials.cash-registers', ['report' => $cashRegisterReport, 'filterOptions' => $filterOptions, 'canExport' => auth()->user()->can('reportes.exportar')])</div>
                    <div class="tab-pane fade" id="report-tab-occupancy">@include('adminlte.reports.partials.occupancy', ['report' => $occupancyReport, 'filterOptions' => $filterOptions, 'canExport' => false])</div>
                    <div class="tab-pane fade" id="report-tab-customers">@include('adminlte.reports.partials.customers', ['report' => $customerReport, 'filterOptions' => $filterOptions, 'canExport' => false])</div>
                    <div class="tab-pane fade" id="report-tab-hotel-chamber">@include('adminlte.reports.partials.hotel-chamber', ['report' => $hotelChamberReport, 'filterOptions' => $filterOptions, 'canExport' => auth()->user()->can('reportes.exportar')])</div>
                </div>
            </div>
        </div>
    </div>
@stop

@push('css')
    <style>
        :root {
            --reports-ink: #172033;
            --reports-muted: #667085;
            --reports-line: rgba(15, 23, 42, .08);
            --reports-gold: #d6a23d;
            --reports-blue: #2563eb;
            --reports-green: #16a34a;
            --reports-red: #dc2626;
            --reports-shadow: 0 24px 60px rgba(15, 23, 42, .12);
        }

        .reports-hero {
            position: relative;
            overflow: hidden;
            display: flex;
            align-items: stretch;
            justify-content: space-between;
            gap: 1.5rem;
            min-height: 170px;
            padding: 1.8rem;
            border-radius: 30px;
            color: #fff;
            background:
                radial-gradient(circle at 12% 12%, rgba(214, 162, 61, .35), transparent 30%),
                radial-gradient(circle at 80% 20%, rgba(37, 99, 235, .22), transparent 34%),
                linear-gradient(135deg, #111827 0%, #22324b 52%, #0f172a 100%);
            box-shadow: var(--reports-shadow);
        }

        .reports-hero::after {
            content: "";
            position: absolute;
            right: -80px;
            bottom: -110px;
            width: 360px;
            height: 240px;
            border-radius: 999px;
            background: rgba(255, 255, 255, .1);
            transform: rotate(-12deg);
        }

        .reports-hero-copy,
        .reports-period-card {
            position: relative;
            z-index: 1;
        }

        .reports-hero h1 {
            font-size: clamp(2.3rem, 5vw, 4rem);
            font-weight: 850;
            letter-spacing: -.05em;
        }

        .reports-hero p {
            max-width: 780px;
            color: rgba(255, 255, 255, .74);
        }

        .reports-eyebrow {
            display: inline-flex;
            margin-bottom: .5rem;
            color: #f5d38b;
            font-size: .72rem;
            font-weight: 850;
            letter-spacing: .16em;
            text-transform: uppercase;
        }

        .reports-period-card {
            display: flex;
            flex-direction: column;
            justify-content: center;
            min-width: 260px;
            padding: 1.2rem;
            border: 1px solid rgba(255, 255, 255, .18);
            border-radius: 24px;
            background: rgba(255, 255, 255, .1);
            backdrop-filter: blur(16px);
        }

        .reports-period-card span,
        .reports-period-card small {
            color: rgba(255, 255, 255, .66);
            font-size: .78rem;
            font-weight: 800;
            letter-spacing: .08em;
            text-transform: uppercase;
        }

        .reports-period-card strong {
            color: #fff;
            font-size: 1.6rem;
            font-weight: 850;
            letter-spacing: -.04em;
        }

        .reports-shell {
            margin-top: 1.5rem;
        }

        .reports-kpi-card {
            position: relative;
            overflow: hidden;
            min-height: 170px;
            padding: 1.25rem;
            border: 1px solid var(--reports-line);
            border-radius: 26px;
            background: #fff;
            box-shadow: 0 18px 45px rgba(15, 23, 42, .08);
        }

        .reports-kpi-card::before {
            content: "";
            position: absolute;
            inset: 0;
            background: linear-gradient(135deg, rgba(37, 99, 235, .09), transparent 58%);
            pointer-events: none;
        }

        .reports-kpi-card i {
            position: absolute;
            right: 1rem;
            bottom: .7rem;
            color: rgba(37, 99, 235, .14);
            font-size: 3.4rem;
        }

        .reports-kpi-card span,
        .report-panel .form-label {
            display: block;
            color: var(--reports-muted);
            font-size: .76rem;
            font-weight: 850;
            letter-spacing: .08em;
            text-transform: uppercase;
        }

        .reports-kpi-card strong {
            position: relative;
            display: block;
            margin-top: .75rem;
            color: var(--reports-ink);
            font-size: clamp(1.55rem, 2.6vw, 2.2rem);
            font-weight: 850;
            letter-spacing: -.05em;
            line-height: 1.05;
        }

        .reports-kpi-card small {
            position: relative;
            display: block;
            margin-top: .5rem;
            color: var(--reports-muted);
        }

        .reports-kpi-card.is-income::before {
            background: linear-gradient(135deg, rgba(22, 163, 74, .11), transparent 58%);
        }

        .reports-kpi-card.is-income i {
            color: rgba(22, 163, 74, .16);
        }

        .reports-kpi-card.is-warning::before {
            background: linear-gradient(135deg, rgba(214, 162, 61, .14), transparent 58%);
        }

        .reports-kpi-card.is-warning i {
            color: rgba(214, 162, 61, .2);
        }

        .reports-kpi-card.is-danger::before {
            background: linear-gradient(135deg, rgba(220, 38, 38, .1), transparent 58%);
        }

        .reports-kpi-card.is-danger i {
            color: rgba(220, 38, 38, .15);
        }

        .reports-kpi-card.is-muted::before {
            background: linear-gradient(135deg, rgba(100, 116, 139, .12), transparent 58%);
        }

        .reports-workspace {
            overflow: hidden;
            border: 1px solid var(--reports-line);
            border-radius: 30px;
            background: #fff;
            box-shadow: 0 18px 45px rgba(15, 23, 42, .08);
        }

        .reports-tabs-wrap {
            padding: 1rem;
            border-bottom: 1px solid var(--reports-line);
            background: linear-gradient(135deg, #f8fafc, #ffffff);
        }

        .reports-tabs {
            display: flex;
            gap: .65rem;
            overflow-x: auto;
            padding-bottom: .1rem;
            flex-wrap: nowrap;
        }

        .reports-tabs .nav-link {
            display: inline-flex;
            align-items: center;
            gap: .5rem;
            white-space: nowrap;
            border: 1px solid rgba(15, 23, 42, .08);
            border-radius: 999px;
            color: #475569;
            background: #fff;
            font-weight: 800;
            padding: .7rem 1rem;
            transition: all .18s ease;
        }

        .reports-tabs .nav-link:hover {
            color: var(--reports-blue);
            transform: translateY(-1px);
            box-shadow: 0 10px 24px rgba(15, 23, 42, .08);
        }

        .reports-tabs .nav-link.active {
            color: #fff;
            border-color: transparent;
            background: linear-gradient(135deg, #172033, #2563eb);
            box-shadow: 0 14px 28px rgba(37, 99, 235, .24);
        }

        .reports-content-card {
            padding: 1.25rem;
        }

        .report-panel {
            animation: reportFadeIn .22s ease both;
        }

        .report-filter-form {
            margin-bottom: 1rem;
            padding: 1rem;
            border: 1px solid var(--reports-line);
            border-radius: 24px;
            background: linear-gradient(135deg, #f8fafc, #fff);
        }

        .report-filter-form .form-control,
        .report-filter-form .form-select {
            border-radius: 14px;
            border-color: rgba(15, 23, 42, .12);
            min-height: 42px;
        }

        .report-filter-form .btn {
            border-radius: 999px;
            padding: .6rem 1rem;
            font-weight: 800;
        }

        .report-panel .small-box {
            overflow: hidden;
            border: 0;
            border-radius: 22px;
            box-shadow: 0 14px 30px rgba(15, 23, 42, .1);
        }

        .report-panel .small-box .inner {
            padding: 1rem;
        }

        .report-panel .small-box h3 {
            font-weight: 850;
            letter-spacing: -.05em;
        }

        .report-panel .card {
            border: 1px solid var(--reports-line);
            border-radius: 22px;
            box-shadow: 0 14px 30px rgba(15, 23, 42, .06);
        }

        .report-panel .card-header {
            border-bottom: 1px solid var(--reports-line);
            background: #f8fafc;
            border-top-left-radius: 22px;
            border-top-right-radius: 22px;
        }

        .report-panel .table {
            margin-bottom: 0;
        }

        .report-panel .table thead th {
            border-bottom: 0;
            color: #64748b;
            font-size: .72rem;
            font-weight: 850;
            letter-spacing: .08em;
            text-transform: uppercase;
            white-space: nowrap;
            background: #f8fafc;
        }

        .report-panel .table tbody td {
            border-color: rgba(15, 23, 42, .06);
            vertical-align: middle;
        }

        .reports-loading {
            display: grid;
            place-items: center;
            min-height: 260px;
            border: 1px dashed rgba(15, 23, 42, .14);
            border-radius: 24px;
            color: var(--reports-muted);
            background: #f8fafc;
        }

        @keyframes reportFadeIn {
            from {
                opacity: 0;
                transform: translateY(8px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        @media (max-width: 991.98px) {
            .reports-hero {
                flex-direction: column;
            }

            .reports-period-card {
                min-width: 0;
            }
        }

        @media (max-width: 575.98px) {
            .reports-hero,
            .reports-workspace,
            .reports-kpi-card {
                border-radius: 22px;
            }

            .reports-content-card {
                padding: .85rem;
            }
        }
    </style>
@endpush

@push('js')
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const tabMap = {
                reservations: document.querySelector('#report-tab-reservations'),
                income: document.querySelector('#report-tab-income'),
                payments: document.querySelector('#report-tab-payments'),
                'cash-registers': document.querySelector('#report-tab-cash-registers'),
                occupancy: document.querySelector('#report-tab-occupancy'),
                customers: document.querySelector('#report-tab-customers'),
                'hotel-chamber': document.querySelector('#report-tab-hotel-chamber'),
            };

            const reportActions = async (form, reset = false) => {
                const key = form.dataset.reportForm;
                const container = tabMap[key];

                if (!container) {
                    return;
                }

                if (reset) {
                    form.reset();
                    const isDailyReport = key === 'hotel-chamber';
                    const today = isDailyReport ? @json($defaultDailyFilters['date_to']) : @json($defaultFilters['date_to']);
                    const firstDay = isDailyReport ? @json($defaultDailyFilters['date_from']) : @json($defaultFilters['date_from']);
                    const dateFrom = form.querySelector('[name="date_from"]');
                    const dateTo = form.querySelector('[name="date_to"]');
                    if (dateFrom) dateFrom.value = firstDay;
                    if (dateTo) dateTo.value = today;
                    const lodgingStatus = form.querySelector('[name="lodging_status"]');
                    if (lodgingStatus) lodgingStatus.value = 'all';
                }

                const params = new URLSearchParams(new FormData(form));
                container.innerHTML = '<div class="reports-loading"><div><div class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></div>Cargando reporte...</div></div>';

                try {
                    const response = await fetch(`${form.action}?${params.toString()}`, {
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest',
                            'Accept': 'text/html',
                        },
                    });

                    if (!response.ok) {
                        throw new Error('No fue posible cargar el reporte.');
                    }

                    container.innerHTML = await response.text();
                } catch (error) {
                    if (window.Swal) {
                        window.Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: error.message,
                        });
                    }
                    container.innerHTML = '<div class="alert alert-danger">No fue posible cargar el reporte.</div>';
                }
            };

            document.addEventListener('submit', (event) => {
                const form = event.target.closest('[data-report-form]');
                if (!form) return;
                event.preventDefault();
                reportActions(form);
            });

            document.addEventListener('click', (event) => {
                const button = event.target.closest('[data-report-reset]');
                if (!button) return;
                const form = document.querySelector(`[data-report-form="${button.dataset.reportReset}"]`);
                if (!form) return;
                reportActions(form, true);
            });
        });
    </script>
@endpush
