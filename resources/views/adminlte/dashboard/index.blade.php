@extends('adminlte::page')

@section('title', 'Inicio del turno')

@php
    $formatMoney = fn ($amount) => 'Bs. '.number_format((float) $amount, 2, '.', '');
    $ledgerRows = $dailyLedger['rows'];
    $ledgerSummary = $dailyLedger['summary'];
    $ledgerDate = $dailyLedger['date'];
    $assignedShiftLabel = $assignedWorkShift
        ? trim(($assignedWorkShift->name ?? 'Turno asignado').' · '.substr((string) $assignedWorkShift->starts_at, 0, 5).' - '.substr((string) $assignedWorkShift->ends_at, 0, 5))
        : null;
@endphp

@section('content_header')
    <section class="simple-dashboard-hero">
        <div>
            <span class="simple-dashboard-hero__eyebrow">
                <i class="bi bi-house-check" aria-hidden="true"></i>
                Inicio del turno
            </span>
            <h1>Que debo atender hoy</h1>
            <p>
                Panel sencillo para recepcion y administracion: habitaciones, reservas, pagos y caja sin complicaciones.
            </p>
        </div>
        <div class="simple-dashboard-actions">
            @can('reservas.crear')
                <a href="{{ route('adminlte.reservations.index') }}" class="btn btn-warning">
                    <i class="bi bi-plus-circle me-1"></i>
                    Registrar reserva
                </a>
            @endcan
            @can('reservas.ver')
                <a href="{{ route('adminlte.front-desk.index') }}" class="btn btn-light">
                    <i class="bi bi-journal-text me-1"></i>
                    Ver libro de recepcion
                </a>
            @endcan
            @can('caja.abrir')
                <a href="{{ route('adminlte.cash-registers.index') }}" class="btn btn-outline-light">
                    <i class="bi bi-cash-stack me-1"></i>
                    Caja
                </a>
            @endcan
        </div>
    </section>
@stop

@section('content')
    <section class="simple-status-grid" aria-label="Resumen principal">
        <article class="simple-status-card simple-status-card--green">
            <span>Habitaciones libres</span>
            <strong>{{ $ledgerSummary['available'] }}</strong>
            <small>listas para vender</small>
        </article>
        <article class="simple-status-card simple-status-card--amber">
            <span>Reservadas</span>
            <strong>{{ $ledgerSummary['reserved'] }}</strong>
            <small>revisar fechas y pagos</small>
        </article>
        <article class="simple-status-card simple-status-card--red">
            <span>Ocupadas</span>
            <strong>{{ $ledgerSummary['occupied'] }}</strong>
            <small>huespedes alojados</small>
        </article>
        <article class="simple-status-card simple-status-card--blue">
            <span>Pagos por revisar</span>
            <strong>{{ $stats['pending_payments'] }}</strong>
            <small>comprobantes pendientes</small>
        </article>
    </section>

    <div class="row g-3">
        <div class="col-xl-8">
            <section class="simple-panel">
                <div class="simple-panel__header">
                    <div>
                        <span>Libro de hoy · {{ $ledgerDate->format('d/m/Y') }}</span>
                        <h2>Habitaciones y huespedes</h2>
                    </div>
                    <a href="{{ route('adminlte.front-desk.index') }}" class="btn btn-sm btn-primary rounded-pill">
                        Abrir completo
                    </a>
                </div>

                <div class="table-responsive">
                    <table class="table simple-ledger-table align-middle">
                        <thead>
                            <tr>
                                <th>Hab.</th>
                                <th>Solicitante</th>
                                <th>Personas</th>
                                <th>Noches</th>
                                <th>Estado</th>
                                <th>Observacion</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($ledgerRows as $row)
                                <tr>
                                    <td>
                                        <strong>Hab. {{ $row['room_number'] }}</strong>
                                        <small>{{ $row['room_type'] }}</small>
                                    </td>
                                    <td>
                                        @if ($row['customer_name'])
                                            <strong>{{ $row['customer_name'] }}</strong>
                                            <small>{{ collect([$row['customer_phone'], $row['customer_city']])->filter()->implode(' · ') }}</small>
                                        @else
                                            <span class="text-secondary">Libre</span>
                                        @endif
                                    </td>
                                    <td>{{ $row['people'] ?? '-' }}</td>
                                    <td>{{ $row['nights'] ?? '-' }}</td>
                                    <td>
                                        <span class="simple-ledger-status {{ $row['ledger_status_class'] }}">
                                            {{ $row['ledger_status_label'] }}
                                        </span>
                                    </td>
                                    <td>{{ $row['notes'] ?: '-' }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="text-center text-secondary py-4">No hay habitaciones registradas.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </section>
        </div>

        <div class="col-xl-4">
            <section class="simple-panel simple-panel--side">
                <div class="simple-panel__header">
                    <div>
                        <span>Mi turno y caja</span>
                        <h2>Antes de cobrar</h2>
                    </div>
                </div>

                <div class="simple-shift-box">
                    <i class="bi bi-clock-history"></i>
                    <div>
                        <strong>{{ $assignedShiftLabel ?? 'Sin turno asignado' }}</strong>
                        <span>
                            @if ($assignedShiftLabel)
                                Este es el horario configurado para tu usuario.
                            @else
                                Administracion debe asignar tu turno.
                            @endif
                        </span>
                    </div>
                </div>

                <div class="simple-shift-box {{ $cashRegister ? 'simple-shift-box--ok' : 'simple-shift-box--warning' }}">
                    <i class="bi bi-cash-coin"></i>
                    <div>
                        <strong>{{ $cashRegister?->code ?? 'Caja no abierta' }}</strong>
                        <span>
                            @if ($cashRegister)
                                Abierta por {{ $cashRegister->opened_by_name ?? 'usuario' }} · {{ $formatMoney($cashRegister->opening_amount ?? 0) }}
                            @else
                                Abre caja antes de registrar ingresos en efectivo.
                            @endif
                        </span>
                    </div>
                </div>

                <div class="simple-money-list">
                    <div><span>Ingresos hoy</span><strong>{{ $formatMoney($stats['today_revenue']) }}</strong></div>
                    <div><span>Saldo por cobrar</span><strong>{{ $formatMoney($stats['pending_balance']) }}</strong></div>
                    <div><span>Reservas pendientes</span><strong>{{ $stats['pending_reservations'] }}</strong></div>
                </div>
            </section>

            <section class="simple-panel mt-3">
                <div class="simple-panel__header">
                    <div>
                        <span>Comprobantes</span>
                        <h2>Revisar pagos</h2>
                    </div>
                    @can('pagos.ver')
                        <a href="{{ route('adminlte.payments.index') }}" class="btn btn-sm btn-outline-primary rounded-pill">Ver pagos</a>
                    @endcan
                </div>

                <div class="simple-payment-list">
                    @forelse ($pendingPayments as $payment)
                        <article>
                            <div>
                                <strong>{{ $payment->customer_name ?? 'Cliente' }}</strong>
                                <span>{{ $payment->reservation_code ?? $payment->code }}</span>
                            </div>
                            <b>{{ $formatMoney($payment->amount_base ?? 0) }}</b>
                        </article>
                    @empty
                        <div class="simple-empty">
                            <i class="bi bi-check2-circle"></i>
                            <strong>No hay pagos pendientes</strong>
                        </div>
                    @endforelse
                </div>
            </section>
        </div>
    </div>
@stop

@push('css')
    <style>
        .simple-dashboard-hero {
            display: flex;
            flex-wrap: wrap;
            gap: 1rem;
            align-items: end;
            justify-content: space-between;
            padding: 1.2rem;
            border-radius: 1.2rem;
            background: linear-gradient(135deg, #162033 0%, #25364f 58%, #69451f 100%);
            color: #fff;
            box-shadow: 0 16px 38px rgba(15, 23, 42, .18);
        }

        .simple-dashboard-hero__eyebrow {
            display: inline-flex;
            gap: .45rem;
            align-items: center;
            padding: .35rem .7rem;
            margin-bottom: .65rem;
            border-radius: 999px;
            background: rgba(255,255,255,.12);
            font-size: .72rem;
            font-weight: 850;
            letter-spacing: .08em;
            text-transform: uppercase;
        }

        .simple-dashboard-hero h1 {
            margin: 0;
            font-size: clamp(1.85rem, 3vw, 2.6rem);
            font-weight: 900;
        }

        .simple-dashboard-hero p {
            max-width: 46rem;
            margin: .35rem 0 0;
            color: rgba(255,255,255,.78);
        }

        .simple-dashboard-actions {
            display: flex;
            flex-wrap: wrap;
            gap: .55rem;
        }

        .simple-dashboard-actions .btn {
            border-radius: 999px;
            font-weight: 850;
        }

        .simple-status-grid {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: .85rem;
            margin-bottom: 1rem;
        }

        .simple-status-card,
        .simple-panel {
            border: 1px solid rgba(15, 23, 42, .09);
            background: #fff;
            box-shadow: 0 12px 30px rgba(15, 23, 42, .07);
        }

        .simple-status-card {
            padding: 1rem;
            border-radius: 1rem;
            border-left: .38rem solid #64748b;
        }

        .simple-status-card span,
        .simple-status-card small,
        .simple-panel__header span,
        .simple-ledger-table small,
        .simple-shift-box span,
        .simple-payment-list span,
        .simple-money-list span {
            display: block;
            color: #64748b;
            font-size: .8rem;
            font-weight: 700;
        }

        .simple-status-card strong {
            display: block;
            color: #111827;
            font-size: 2.2rem;
            line-height: 1;
        }

        .simple-status-card--green { border-left-color: #16a34a; }
        .simple-status-card--amber { border-left-color: #f59e0b; }
        .simple-status-card--red { border-left-color: #dc2626; }
        .simple-status-card--blue { border-left-color: #2563eb; }

        .simple-panel {
            padding: 1rem;
            border-radius: 1rem;
        }

        .simple-panel__header {
            display: flex;
            gap: 1rem;
            align-items: center;
            justify-content: space-between;
            margin-bottom: .9rem;
        }

        .simple-panel__header span {
            color: #2563eb;
            letter-spacing: .07em;
            text-transform: uppercase;
        }

        .simple-panel__header h2 {
            margin: .1rem 0 0;
            font-size: 1.2rem;
            font-weight: 900;
        }

        .simple-ledger-table {
            margin-bottom: 0;
            min-width: 780px;
        }

        .simple-ledger-table thead th {
            color: #1d4ed8;
            font-size: .76rem;
            text-transform: uppercase;
            background: #eff6ff;
        }

        .simple-ledger-table td {
            vertical-align: top;
        }

        .simple-ledger-status {
            display: inline-flex;
            padding: .28rem .62rem;
            border-radius: 999px;
            font-size: .76rem;
            font-weight: 850;
        }

        .ledger-status--available { background: #dcfce7; color: #166534; }
        .ledger-status--reserved { background: #fef3c7; color: #92400e; }
        .ledger-status--occupied { background: #fee2e2; color: #991b1b; }

        .simple-shift-box,
        .simple-payment-list article,
        .simple-money-list div,
        .simple-empty {
            display: flex;
            gap: .75rem;
            align-items: center;
            padding: .85rem;
            border-radius: .9rem;
            background: #f8fafc;
        }

        .simple-shift-box + .simple-shift-box,
        .simple-money-list,
        .simple-payment-list {
            margin-top: .75rem;
        }

        .simple-shift-box i {
            display: grid;
            flex: 0 0 2.5rem;
            width: 2.5rem;
            height: 2.5rem;
            place-items: center;
            border-radius: .8rem;
            background: #e0f2fe;
            color: #0369a1;
            font-size: 1.2rem;
        }

        .simple-shift-box--ok i { background: #dcfce7; color: #166534; }
        .simple-shift-box--warning i { background: #fef3c7; color: #92400e; }

        .simple-money-list {
            display: grid;
            gap: .55rem;
        }

        .simple-money-list div,
        .simple-payment-list article {
            justify-content: space-between;
        }

        .simple-money-list strong,
        .simple-payment-list b {
            color: #111827;
            font-size: 1.05rem;
        }

        .simple-payment-list {
            display: grid;
            gap: .55rem;
        }

        .simple-empty {
            justify-content: center;
            min-height: 7rem;
            color: #166534;
            text-align: center;
        }

        @media (max-width: 1199.98px) {
            .simple-status-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }
        }

        @media (max-width: 767.98px) {
            .simple-dashboard-actions,
            .simple-dashboard-actions .btn {
                width: 100%;
            }

            .simple-dashboard-actions .btn {
                justify-content: center;
            }

            .simple-status-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
@endpush
