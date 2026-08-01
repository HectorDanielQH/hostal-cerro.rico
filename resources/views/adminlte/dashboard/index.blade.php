@extends('adminlte::page')

@section('title', 'Dashboard hotelero')

@php
    $formatMoney = fn ($amount) => 'Bs. '.number_format((float) $amount, 2, '.', '');
    $activeRooms = max((int) ($stats['active_rooms'] ?? 0), 0);
    $occupiedRooms = max((int) ($stats['occupied_rooms'] ?? 0), 0);
    $occupancyRate = $activeRooms > 0 ? min(round(($occupiedRooms / $activeRooms) * 100), 100) : 0;

    $reservationLabels = [
        'pending' => 'Pendiente',
        'confirmed' => 'Confirmada',
        'checked_in' => 'Alojado',
        'checked_out' => 'Finalizada',
        'cancelled' => 'Cancelada',
        'expired' => 'Expirada',
        'no_show' => 'No se presento',
    ];

    $reservationBadges = [
        'pending' => 'text-bg-warning',
        'confirmed' => 'text-bg-primary',
        'checked_in' => 'text-bg-success',
        'checked_out' => 'text-bg-secondary',
        'cancelled' => 'text-bg-danger',
        'expired' => 'text-bg-secondary',
        'no_show' => 'text-bg-dark',
    ];

    $roomLabels = [
        'available' => 'Disponible',
        'occupied' => 'Ocupada',
        'reserved' => 'Reservada',
    ];

    $roomColors = [
        'available' => '#16a34a',
        'occupied' => '#dc2626',
        'reserved' => '#f59e0b',
    ];

    $assignedShiftLabel = $assignedWorkShift
        ? trim(($assignedWorkShift->name ?? 'Turno asignado').' · '.substr((string) $assignedWorkShift->starts_at, 0, 5).' - '.substr((string) $assignedWorkShift->ends_at, 0, 5))
        : null;
@endphp

@section('content_header')
    <section class="hotel-dashboard-hero">
        <div>
            <span class="hotel-dashboard-hero__eyebrow">
                <i class="bi bi-speedometer2" aria-hidden="true"></i>
                Centro de control
            </span>
            <h1 class="hotel-dashboard-hero__title">Dashboard hotelero</h1>
            <p class="hotel-dashboard-hero__text">
                Vista ejecutiva de ocupacion, reservas, pagos y caja para tomar decisiones rapidas durante el turno.
            </p>
        </div>

        <div class="hotel-dashboard-hero__actions">
            @can('reservas.ver')
                <a href="{{ route('adminlte.reservations.index') }}" class="btn btn-primary">
                    <i class="bi bi-calendar2-check me-1" aria-hidden="true"></i>
                    Gestionar reservas
                </a>
            @endcan

            @can('reservas.ver')
                <a href="{{ route('adminlte.front-desk.index') }}" class="btn btn-light">
                    <i class="bi bi-reception-4 me-1" aria-hidden="true"></i>
                    Recepcion
                </a>
            @endcan
        </div>
    </section>
@stop

@section('content')
    <section class="hotel-kpi-grid" aria-label="Indicadores principales">
        <article class="hotel-kpi hotel-kpi--blue">
            <span class="hotel-kpi__icon"><i class="bi bi-box-arrow-in-right" aria-hidden="true"></i></span>
            <span class="hotel-kpi__label">Llegadas hoy</span>
            <strong class="hotel-kpi__value">{{ $stats['arrivals_today'] }}</strong>
            <small class="hotel-kpi__meta">Reservas listas para check-in</small>
        </article>

        <article class="hotel-kpi hotel-kpi--cyan">
            <span class="hotel-kpi__icon"><i class="bi bi-box-arrow-right" aria-hidden="true"></i></span>
            <span class="hotel-kpi__label">Salidas hoy</span>
            <strong class="hotel-kpi__value">{{ $stats['departures_today'] }}</strong>
            <small class="hotel-kpi__meta">Habitaciones a liberar</small>
        </article>

        <article class="hotel-kpi hotel-kpi--green">
            <span class="hotel-kpi__icon"><i class="bi bi-door-open" aria-hidden="true"></i></span>
            <span class="hotel-kpi__label">Disponibles</span>
            <strong class="hotel-kpi__value">{{ $stats['available_rooms'] }}</strong>
            <small class="hotel-kpi__meta">De {{ $activeRooms }} habitaciones activas</small>
        </article>

        <article class="hotel-kpi hotel-kpi--amber">
            <span class="hotel-kpi__icon"><i class="bi bi-credit-card-2-front" aria-hidden="true"></i></span>
            <span class="hotel-kpi__label">Pagos por revisar</span>
            <strong class="hotel-kpi__value">{{ $stats['pending_payments'] }}</strong>
            <small class="hotel-kpi__meta">Comprobantes pendientes</small>
        </article>
    </section>

    <div class="row g-3">
        <div class="col-xl-5">
            <section class="hotel-panel hotel-occupancy-panel">
                <div class="hotel-panel__header">
                    <div>
                        <span class="hotel-panel__kicker">Ocupacion</span>
                        <h2 class="hotel-panel__title">Estado actual del hotel</h2>
                    </div>
                    <span class="hotel-panel__badge">{{ $occupancyRate }}%</span>
                </div>

                <div class="hotel-occupancy-ring" style="--occupancy: {{ $occupancyRate }};">
                    <div class="hotel-occupancy-ring__center">
                        <strong>{{ $occupiedRooms }}</strong>
                        <span>ocupadas</span>
                    </div>
                </div>

                <div class="hotel-room-status-list">
                    @forelse ($roomStatuses as $status => $total)
                        <div class="hotel-room-status">
                            <span class="hotel-room-status__dot" style="--dot-color: {{ $roomColors[$status] ?? '#64748b' }}"></span>
                            <span>{{ $roomLabels[$status] ?? ucfirst((string) $status) }}</span>
                            <strong>{{ $total }}</strong>
                        </div>
                    @empty
                        <p class="text-secondary mb-0">Aun no existen habitaciones registradas.</p>
                    @endforelse
                </div>
            </section>
        </div>

        <div class="col-xl-7">
            <section class="hotel-panel h-100">
                <div class="hotel-panel__header">
                    <div>
                        <span class="hotel-panel__kicker">Resumen financiero</span>
                        <h2 class="hotel-panel__title">Ingresos y saldos</h2>
                    </div>
                    @can('pagos.ver')
                        <a href="{{ route('adminlte.payments.index') }}" class="btn btn-sm btn-outline-primary rounded-pill">
                            Ver pagos
                        </a>
                    @endcan
                </div>

                <div class="hotel-money-grid">
                    <article>
                        <span>Ingresos hoy</span>
                        <strong>{{ $formatMoney($stats['today_revenue']) }}</strong>
                    </article>
                    <article>
                        <span>Ingresos del mes</span>
                        <strong>{{ $formatMoney($stats['month_revenue']) }}</strong>
                    </article>
                    <article>
                        <span>Saldo por cobrar</span>
                        <strong>{{ $formatMoney($stats['pending_balance']) }}</strong>
                    </article>
                    <article>
                        <span>Cajas abiertas</span>
                        <strong>{{ $stats['open_cash_registers'] }}</strong>
                    </article>
                </div>

                <div class="hotel-cash-strip">
                    <span class="hotel-cash-strip__icon">
                        <i class="bi bi-cash-stack" aria-hidden="true"></i>
                    </span>
                    <div>
                        <strong>{{ $cashRegister?->code ?? 'Sin caja abierta' }}</strong>
                        <span>
                            @if ($cashRegister)
                                {{ $cashRegister->opened_by_name ?? 'Usuario' }} · Apertura {{ optional(\Illuminate\Support\Carbon::parse($cashRegister->opened_at))->format('d/m/Y H:i') }}
                            @else
                                Abre una caja para controlar ingresos y movimientos del turno.
                            @endif
                        </span>
                        @if ($cashRegister && $cashRegister->shift_name)
                            <span class="hotel-cash-strip__shift">Turno activo: {{ $cashRegister->shift_name }}</span>
                        @elseif ($assignedShiftLabel)
                            <span class="hotel-cash-strip__shift">Tu turno asignado: {{ $assignedShiftLabel }}</span>
                        @else
                            <span class="hotel-cash-strip__shift hotel-cash-strip__shift--warning">Aun no tienes turno asignado en Usuarios.</span>
                        @endif
                    </div>
                    @can('caja.abrir')
                        @unless ($cashRegister)
                            <a href="{{ route('adminlte.cash-registers.index') }}" class="btn btn-sm btn-primary rounded-pill ms-auto">
                                Abrir caja
                            </a>
                        @endunless
                    @endcan
                </div>
            </section>
        </div>
    </div>

    <div class="row g-3 mt-1">
        <div class="col-xl-7">
            <section class="hotel-panel">
                <div class="hotel-panel__header">
                    <div>
                        <span class="hotel-panel__kicker">Reservas</span>
                        <h2 class="hotel-panel__title">Ultimas solicitudes</h2>
                    </div>
                    <span class="hotel-panel__badge hotel-panel__badge--soft">{{ $stats['pending_reservations'] }} pendientes</span>
                </div>

                <div class="table-responsive">
                    <table class="table align-middle hotel-table">
                        <thead>
                            <tr>
                                <th>Codigo</th>
                                <th>Cliente</th>
                                <th>Habitacion</th>
                                <th>Fechas</th>
                                <th>Saldo</th>
                                <th>Estado</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($recentReservations as $reservation)
                                <tr>
                                    <td class="fw-bold">{{ $reservation->code }}</td>
                                    <td>{{ $reservation->customer_name ?? '-' }}</td>
                                    <td>Hab. {{ $reservation->room_number ?? '-' }}</td>
                                    <td class="text-nowrap">
                                        {{ optional(\Illuminate\Support\Carbon::parse($reservation->check_in))->format('d/m/Y') }}
                                        -
                                        {{ optional(\Illuminate\Support\Carbon::parse($reservation->check_out))->format('d/m/Y') }}
                                    </td>
                                    <td class="fw-semibold">{{ $formatMoney($reservation->balance_amount ?? 0) }}</td>
                                    <td>
                                        <span class="badge {{ $reservationBadges[$reservation->status] ?? 'text-bg-secondary' }}">
                                            {{ $reservationLabels[$reservation->status] ?? ucfirst((string) $reservation->status) }}
                                        </span>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="text-center text-secondary py-4">No hay reservas registradas.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </section>
        </div>

        <div class="col-xl-5">
            <section class="hotel-panel h-100">
                <div class="hotel-panel__header">
                    <div>
                        <span class="hotel-panel__kicker">Pagos</span>
                        <h2 class="hotel-panel__title">Comprobantes pendientes</h2>
                    </div>
                </div>

                <div class="hotel-payment-list">
                    @forelse ($pendingPayments as $payment)
                        <article class="hotel-payment-item">
                            <span class="hotel-payment-item__icon">
                                <i class="bi bi-receipt" aria-hidden="true"></i>
                            </span>
                            <div>
                                <strong>{{ $payment->code }}</strong>
                                <span>{{ $payment->customer_name ?? 'Cliente' }} · {{ $payment->reservation_code ?? 'Sin reserva' }}</span>
                            </div>
                            <strong>{{ $formatMoney($payment->amount_base ?? 0) }}</strong>
                        </article>
                    @empty
                        <div class="hotel-empty-state">
                            <i class="bi bi-check2-circle" aria-hidden="true"></i>
                            <strong>Todo revisado</strong>
                            <span>No hay comprobantes pendientes.</span>
                        </div>
                    @endforelse
                </div>
            </section>
        </div>
    </div>
@stop

@push('css')
    <style>
        .hotel-dashboard-hero {
            position: relative;
            display: flex;
            flex-wrap: wrap;
            gap: 1.25rem;
            align-items: end;
            justify-content: space-between;
            padding: 1.45rem;
            overflow: hidden;
            border-radius: 1.35rem;
            background:
                radial-gradient(circle at 88% 12%, rgba(250, 204, 21, 0.22), transparent 28%),
                radial-gradient(circle at 12% 20%, rgba(59, 130, 246, 0.24), transparent 30%),
                linear-gradient(135deg, #111827 0%, #1f2937 54%, #334155 100%);
            color: #fff;
            box-shadow: 0 18px 46px rgba(15, 23, 42, 0.18);
        }

        .hotel-dashboard-hero__eyebrow {
            display: inline-flex;
            gap: 0.45rem;
            align-items: center;
            padding: 0.36rem 0.72rem;
            margin-bottom: 0.85rem;
            border: 1px solid rgba(255, 255, 255, 0.18);
            border-radius: 999px;
            background: rgba(255, 255, 255, 0.1);
            color: rgba(255, 255, 255, 0.78);
            font-size: 0.72rem;
            font-weight: 800;
            letter-spacing: 0.08em;
            text-transform: uppercase;
        }

        .hotel-dashboard-hero__title {
            margin: 0;
            font-size: clamp(1.85rem, 3vw, 2.65rem);
            font-weight: 850;
            letter-spacing: -0.045em;
        }

        .hotel-dashboard-hero__text {
            max-width: 48rem;
            margin: 0.45rem 0 0;
            color: rgba(255, 255, 255, 0.72);
        }

        .hotel-dashboard-hero__actions {
            display: flex;
            flex-wrap: wrap;
            gap: 0.65rem;
        }

        .hotel-dashboard-hero__actions .btn {
            border-radius: 999px;
            padding: 0.65rem 1rem;
            font-weight: 800;
            box-shadow: 0 12px 24px rgba(15, 23, 42, 0.18);
        }

        .hotel-kpi-grid {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: 1rem;
            margin-bottom: 1rem;
        }

        .hotel-kpi,
        .hotel-panel {
            border: 1px solid rgba(15, 23, 42, 0.08);
            background: #fff;
            box-shadow: 0 14px 34px rgba(15, 23, 42, 0.08);
        }

        .hotel-kpi {
            position: relative;
            min-height: 9.2rem;
            padding: 1rem;
            overflow: hidden;
            border-radius: 1.15rem;
        }

        .hotel-kpi::after {
            position: absolute;
            inset: auto -2.2rem -2.6rem auto;
            width: 7rem;
            height: 7rem;
            content: "";
            border-radius: 999px;
            background: var(--kpi-soft);
        }

        .hotel-kpi__icon {
            display: grid;
            width: 2.9rem;
            height: 2.9rem;
            margin-bottom: 0.85rem;
            place-items: center;
            border-radius: 1rem;
            background: var(--kpi-soft);
            color: var(--kpi-color);
            font-size: 1.35rem;
        }

        .hotel-kpi__label,
        .hotel-kpi__meta {
            display: block;
            color: #64748b;
            font-size: 0.78rem;
            font-weight: 750;
        }

        .hotel-kpi__label {
            color: #475569;
            letter-spacing: 0.03em;
            text-transform: uppercase;
        }

        .hotel-kpi__value {
            display: block;
            margin: 0.1rem 0;
            color: #0f172a;
            font-size: 2.25rem;
            font-weight: 850;
            line-height: 1;
            letter-spacing: -0.05em;
        }

        .hotel-kpi--blue {
            --kpi-color: #2563eb;
            --kpi-soft: rgba(37, 99, 235, 0.12);
        }

        .hotel-kpi--cyan {
            --kpi-color: #0891b2;
            --kpi-soft: rgba(8, 145, 178, 0.13);
        }

        .hotel-kpi--green {
            --kpi-color: #16a34a;
            --kpi-soft: rgba(22, 163, 74, 0.13);
        }

        .hotel-kpi--amber {
            --kpi-color: #d97706;
            --kpi-soft: rgba(217, 119, 6, 0.13);
        }

        .hotel-panel {
            height: 100%;
            padding: 1rem;
            border-radius: 1.2rem;
        }

        .hotel-panel__header {
            display: flex;
            gap: 1rem;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 1rem;
        }

        .hotel-panel__kicker {
            color: #2563eb;
            font-size: 0.72rem;
            font-weight: 850;
            letter-spacing: 0.08em;
            text-transform: uppercase;
        }

        .hotel-panel__title {
            margin: 0.15rem 0 0;
            color: #0f172a;
            font-size: 1.15rem;
            font-weight: 850;
        }

        .hotel-panel__badge {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-width: 3.4rem;
            padding: 0.45rem 0.75rem;
            border-radius: 999px;
            background: #0f172a;
            color: #fff;
            font-weight: 850;
        }

        .hotel-panel__badge--soft {
            background: #fff7ed;
            color: #c2410c;
        }

        .hotel-occupancy-ring {
            display: grid;
            width: min(17rem, 78vw);
            aspect-ratio: 1;
            margin: 0.25rem auto 1.25rem;
            place-items: center;
            border-radius: 999px;
            background: conic-gradient(#2563eb calc(var(--occupancy) * 1%), #e2e8f0 0);
        }

        .hotel-occupancy-ring__center {
            display: grid;
            width: 70%;
            height: 70%;
            place-items: center;
            border-radius: 999px;
            background: #fff;
            color: #64748b;
            text-align: center;
            box-shadow: inset 0 0 0 1px rgba(15, 23, 42, 0.08);
        }

        .hotel-occupancy-ring__center strong {
            display: block;
            color: #0f172a;
            font-size: 3rem;
            font-weight: 900;
            line-height: 1;
        }

        .hotel-room-status-list,
        .hotel-payment-list {
            display: grid;
            gap: 0.65rem;
        }

        .hotel-room-status,
        .hotel-payment-item,
        .hotel-cash-strip {
            display: flex;
            gap: 0.75rem;
            align-items: center;
            padding: 0.75rem;
            border-radius: 0.95rem;
            background: #f8fafc;
        }

        .hotel-room-status strong,
        .hotel-payment-item > strong {
            margin-left: auto;
            color: #0f172a;
        }

        .hotel-room-status__dot {
            width: 0.72rem;
            height: 0.72rem;
            border-radius: 999px;
            background: var(--dot-color);
        }

        .hotel-money-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 0.8rem;
        }

        .hotel-money-grid article {
            padding: 1rem;
            border-radius: 1rem;
            background: #f8fafc;
        }

        .hotel-money-grid span,
        .hotel-cash-strip span,
        .hotel-payment-item span {
            display: block;
            color: #64748b;
            font-size: 0.82rem;
        }

        .hotel-money-grid strong {
            display: block;
            margin-top: 0.2rem;
            color: #0f172a;
            font-size: 1.35rem;
            font-weight: 850;
        }

        .hotel-cash-strip {
            margin-top: 1rem;
            background: linear-gradient(135deg, #eff6ff 0%, #f8fafc 100%);
            flex-wrap: wrap;
        }

        .hotel-cash-strip__shift {
            display: inline-flex !important;
            width: fit-content;
            margin-top: 0.35rem;
            padding: 0.25rem 0.6rem;
            border-radius: 999px;
            background: #dbeafe;
            color: #1d4ed8 !important;
            font-size: 0.76rem !important;
            font-weight: 850;
        }

        .hotel-cash-strip__shift--warning {
            background: #fef3c7;
            color: #92400e !important;
        }

        .hotel-cash-strip__icon,
        .hotel-payment-item__icon {
            display: grid;
            flex: 0 0 2.6rem;
            width: 2.6rem;
            height: 2.6rem;
            place-items: center;
            border-radius: 0.9rem;
            background: #fff;
            color: #2563eb;
            font-size: 1.2rem;
        }

        .hotel-table {
            margin-bottom: 0;
        }

        .hotel-table thead th {
            color: #64748b;
            font-size: 0.75rem;
            letter-spacing: 0.04em;
            text-transform: uppercase;
            white-space: nowrap;
        }

        .hotel-empty-state {
            display: grid;
            min-height: 12rem;
            place-items: center;
            align-content: center;
            border-radius: 1rem;
            background: #f8fafc;
            color: #64748b;
            text-align: center;
        }

        .hotel-empty-state i {
            color: #16a34a;
            font-size: 2.25rem;
        }

        .hotel-empty-state strong {
            color: #0f172a;
        }

        @media (max-width: 1199.98px) {
            .hotel-kpi-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }
        }

        @media (max-width: 767.98px) {
            .hotel-dashboard-hero {
                padding: 1rem;
            }

            .hotel-dashboard-hero__actions,
            .hotel-dashboard-hero__actions .btn {
                width: 100%;
            }

            .hotel-dashboard-hero__actions .btn {
                justify-content: center;
            }

            .hotel-kpi-grid,
            .hotel-money-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
@endpush
