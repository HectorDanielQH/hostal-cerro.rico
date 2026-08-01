@extends('adminlte::page')

@section('title', 'Caja / Turnos')

@php
    $assignedShiftLabel = $assignedWorkShift
        ? $assignedWorkShift->name.' ('.$assignedWorkShift->scheduleLabel().')'
        : null;

    $currentCashRegisterData = $currentCashRegister ? [
        'id' => $currentCashRegister->id,
        'code' => $currentCashRegister->code,
        'user_name' => $currentCashRegister->user?->name ?? '-',
        'shift_name' => $currentCashRegister->shift_name,
        'opened_at_formatted' => optional($currentCashRegister->opened_at)?->format('d/m/Y H:i') ?? '-',
        'opening_amount' => (float) $currentCashRegister->opening_amount,
        'total_income' => (float) $currentCashRegister->total_income,
        'total_expense' => (float) $currentCashRegister->total_expense,
        'total_adjustment' => (float) $currentCashRegister->total_adjustment,
        'expected_amount' => (float) $currentCashRegister->expected_amount,
        'counted_amount' => $currentCashRegister->counted_amount !== null ? (float) $currentCashRegister->counted_amount : null,
        'difference_amount' => (float) $currentCashRegister->difference_amount,
        'close_url' => route('adminlte.cash-registers.close', $currentCashRegister),
        'movements_url' => route('adminlte.cash-registers.movements', $currentCashRegister),
        'arqueo_url' => route('adminlte.cash-registers.arqueo', $currentCashRegister),
    ] : null;
@endphp

@section('content_header')
    <div class="cash-page-header">
        <div class="cash-header-copy">
            <span class="cash-eyebrow">Control operativo</span>
            <h1 class="m-0">Caja / Turnos</h1>
            <p class="mb-0">Apertura, movimientos, arqueo y cierre de caja. Los pagos aprobados desde Pagos se enlazan a reservas y entran aqui como ingresos del turno cuando hay caja abierta.</p>
        </div>
        <div class="cash-header-status">
            <span class="cash-status-dot {{ $currentCashRegister ? 'is-open' : '' }}" id="cash-header-dot"></span>
            <div>
                <small id="cash-header-state">{{ $currentCashRegister ? 'Caja activa' : 'Sin caja abierta' }}</small>
                <strong id="cash-header-code">{{ $currentCashRegister?->code ?? 'Pendiente de apertura' }}</strong>
            </div>
        </div>
    </div>
@stop

@section('content')
    <div class="cash-shell">
        <div class="cash-current-panel" id="current-cash-register-card">
            @if ($currentCashRegister)
                <div class="cash-current-main">
                    <div class="cash-current-top">
                        <div>
                            <span class="cash-pill cash-pill-success">Caja abierta</span>
                            <h2>{{ $currentCashRegister->code }}</h2>
                            <p>{{ $currentCashRegister->shift_name ? 'Turno: '.$currentCashRegister->shift_name.' - ' : '' }}Apertura: {{ optional($currentCashRegister->opened_at)?->format('d/m/Y H:i') ?? '-' }}</p>
                        </div>
                        <div class="cash-user-chip">
                            <i class="bi bi-person-badge"></i>
                            <span>{{ $currentCashRegister->user?->name ?? '-' }}</span>
                        </div>
                    </div>

                    <div class="cash-metric-hero">
                        <span>Caja esperada</span>
                        <strong>Bs. {{ number_format((float) $currentCashRegister->expected_amount, 2, '.', '') }}</strong>
                        <small>Saldo calculado con movimientos en moneda base</small>
                    </div>
                </div>

                <div class="cash-actions-panel">
                    @can('caja.ajustar')
                        <button type="button" class="btn btn-light cash-action-btn" id="open-movement-modal-btn">
                            <i class="bi bi-plus-circle"></i>
                            Registrar movimiento
                        </button>
                    @endcan
                    @can('caja.cerrar')
                        <button type="button" class="btn btn-warning cash-action-btn" id="open-close-cash-modal-btn">
                            <i class="bi bi-lock"></i>
                            Cerrar caja
                        </button>
                    @endcan
                </div>
            @else
                <div class="cash-empty-state">
                    <div class="cash-empty-icon">
                        <i class="bi bi-safe2"></i>
                    </div>
                    <div>
                        <span class="cash-pill cash-pill-muted">Pendiente</span>
                        <h2>No tienes caja abierta</h2>
                        <p>Abre una caja para comenzar a registrar ingresos, egresos y ajustes del turno actual.</p>
                    </div>
                    @can('caja.abrir')
                        <button type="button" class="btn btn-warning cash-action-btn" id="open-open-cash-modal-btn">
                            <i class="bi bi-unlock"></i>
                            Abrir caja
                        </button>
                    @endcan
                </div>
            @endif
        </div>

        <div class="row g-3 mb-4">
            <div class="col-md-6 col-xl-3">
                <div class="cash-stat-card">
                    <span>Monto inicial</span>
                    <strong id="cash-stat-opening">Bs. {{ number_format((float) ($currentCashRegister?->opening_amount ?? 0), 2, '.', '') }}</strong>
                    <i class="bi bi-wallet2"></i>
                </div>
            </div>
            <div class="col-md-6 col-xl-3">
                <div class="cash-stat-card is-income">
                    <span>Ingresos</span>
                    <strong id="cash-stat-income">Bs. {{ number_format((float) ($currentCashRegister?->total_income ?? 0), 2, '.', '') }}</strong>
                    <i class="bi bi-arrow-down-left-circle"></i>
                </div>
            </div>
            <div class="col-md-6 col-xl-3">
                <div class="cash-stat-card is-expense">
                    <span>Egresos</span>
                    <strong id="cash-stat-expense">Bs. {{ number_format((float) ($currentCashRegister?->total_expense ?? 0), 2, '.', '') }}</strong>
                    <i class="bi bi-arrow-up-right-circle"></i>
                </div>
            </div>
            <div class="col-md-6 col-xl-3">
                <div class="cash-stat-card is-adjustment">
                    <span>Ajustes</span>
                    <strong id="cash-stat-adjustment">Bs. {{ number_format((float) ($currentCashRegister?->total_adjustment ?? 0), 2, '.', '') }}</strong>
                    <i class="bi bi-sliders"></i>
                </div>
            </div>
        </div>

        <div class="cash-history-card">
            <div class="cash-section-head">
                <div>
                    <span class="cash-eyebrow">Auditoria</span>
                    <h3>Historial de cajas / turnos</h3>
                        <p>Revisa aperturas, cierres, diferencias y movimientos. Los ingresos enlazados a pagos muestran codigo de pago, reserva y cliente.</p>
                </div>
            </div>
            <div class="table-responsive">
                <table class="table table-hover align-middle w-100 cash-table" id="cash-registers-table">
                    <thead>
                        <tr>
                            <th>Codigo</th>
                            <th>Usuario</th>
                            <th>Turno</th>
                            <th>Apertura</th>
                            <th>Ingresos</th>
                            <th>Egresos</th>
                            <th>Esperado</th>
                            <th>Contado</th>
                            <th>Diferencia</th>
                            <th>Estado</th>
                            <th class="text-end">Acciones</th>
                        </tr>
                    </thead>
                </table>
            </div>
        </div>
    </div>

    <div class="modal fade" id="open-cash-modal" tabindex="-1" aria-labelledby="open-cash-modal-label" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-scrollable">
            <div class="modal-content">
                <form id="open-cash-form" action="{{ route('adminlte.cash-registers.open') }}">
                    @csrf
                    <div class="modal-header">
                        <div>
                            <h5 class="modal-title" id="open-cash-modal-label">Abrir caja</h5>
                            <small class="text-muted">Inicia un nuevo turno de caja para el usuario actual.</small>
                        </div>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                    </div>
                    <div class="modal-body">
                        <div class="row g-3">
                            <div class="col-md-4">
                                <label class="form-label" for="opening_amount">Monto inicial</label>
                                <input type="number" class="form-control" id="opening_amount" name="opening_amount" min="0" max="999999" step="0.01" required>
                            </div>
                            <div class="col-md-8">
                                <label class="form-label" for="shift_name">Turno</label>
                                @if ($assignedShiftLabel)
                                    <input type="text" class="form-control" id="shift_name_display" value="{{ $assignedShiftLabel }}" readonly>
                                    <input type="hidden" id="shift_name" name="shift_name" value="{{ $assignedShiftLabel }}">
                                    <div class="form-text">
                                        Turno asignado automaticamente desde el usuario. Si necesitas cambiarlo, hazlo desde Administracion interna > Usuarios.
                                    </div>
                                @else
                                    <input type="text" class="form-control" id="shift_name" name="shift_name" maxlength="100" placeholder="Ej. Turno manana">
                                    <div class="form-text text-warning">
                                        Este usuario no tiene turno asignado. Puedes escribirlo manualmente o asignarlo desde Usuarios.
                                    </div>
                                @endif
                            </div>
                            <div class="col-12">
                                <label class="form-label" for="opening_notes">Notas de apertura</label>
                                <textarea class="form-control" id="opening_notes" name="opening_notes" rows="4"></textarea>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-primary">Abrir caja</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="modal fade" id="close-cash-modal" tabindex="-1" aria-labelledby="close-cash-modal-label" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-scrollable">
            <div class="modal-content">
                <form id="close-cash-form" method="POST">
                    @csrf
                    <div class="modal-header">
                        <div>
                            <h5 class="modal-title" id="close-cash-modal-label">Cerrar caja</h5>
                            <small class="text-muted">Registra el monto contado y calcula la diferencia al cierre.</small>
                        </div>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                    </div>
                    <div class="modal-body">
                        <div class="cash-summary-card rounded-3 p-3 mb-3" id="close-cash-summary">
                            <div class="text-muted">Selecciona una caja abierta para ver el resumen de cierre.</div>
                        </div>
                        <div class="row g-3">
                            <div class="col-md-4">
                                <label class="form-label" for="counted_amount">Caja contada</label>
                                <input type="number" class="form-control" id="counted_amount" name="counted_amount" min="0" max="999999" step="0.01" required>
                            </div>
                            <div class="col-md-8">
                                <label class="form-label" for="closing_notes">Notas de cierre</label>
                                <textarea class="form-control" id="closing_notes" name="closing_notes" rows="3"></textarea>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-primary">Cerrar caja</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="modal fade" id="movement-modal" tabindex="-1" aria-labelledby="movement-modal-label" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-scrollable">
            <div class="modal-content">
                <form id="movement-form" action="{{ route('adminlte.cash-registers.movements.store') }}">
                    @csrf
                    <input type="hidden" name="cash_register_id" id="movement_cash_register_id" value="">
                    <div class="modal-header">
                        <div>
                            <h5 class="modal-title" id="movement-modal-label">Registrar movimiento</h5>
                            <small class="text-muted">Agrega ingresos, egresos o ajustes manuales a una caja abierta.</small>
                        </div>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                    </div>
                    <div class="modal-body">
                        <div class="row g-3">
                            <div class="col-md-4">
                                <label class="form-label" for="movement_type">Tipo</label>
                                <select class="form-select" id="movement_type" name="type" required>
                                    <option value="">Selecciona un tipo</option>
                                    @foreach ($movementTypes as $typeKey => $typeLabel)
                                        <option value="{{ $typeKey }}">{{ $typeLabel }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label" for="movement_amount">Monto</label>
                                <input type="number" class="form-control" id="movement_amount" name="amount" min="0.01" max="999999" step="0.01" required>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label" for="movement_currency">Moneda</label>
                                <select class="form-select" id="movement_currency" name="currency" required>
                                    @foreach ($supportedCurrencies as $currencyCode => $currencyLabel)
                                        <option value="{{ $currencyCode }}" @selected($currencyCode === $baseCurrency)>{{ $currencyCode }} - {{ $currencyLabel }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label" for="movement_payment_method">Metodo</label>
                                <select class="form-select" id="movement_payment_method" name="payment_method">
                                    <option value="">Sin metodo</option>
                                    @foreach ($paymentMethods as $methodKey => $methodLabel)
                                        <option value="{{ $methodKey }}">{{ $methodLabel }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-8">
                                <div class="cash-summary-card rounded-3 p-3 h-100" id="movement-conversion-summary">
                                    <div class="text-muted">Los movimientos en {{ $baseCurrency }} afectan los totales automaticos. Otras monedas se registran sin convertir.</div>
                                </div>
                            </div>
                            <div class="col-12">
                                <label class="form-label" for="movement_concept">Concepto</label>
                                <input type="text" class="form-control" id="movement_concept" name="concept" maxlength="255" required>
                            </div>
                            <div class="col-12">
                                <label class="form-label" for="movement_notes">Notas</label>
                                <textarea class="form-control" id="movement_notes" name="notes" rows="4"></textarea>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-primary">Guardar movimiento</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="modal fade" id="movements-view-modal" tabindex="-1" aria-labelledby="movements-view-modal-label" aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header">
                    <div>
                        <h5 class="modal-title" id="movements-view-modal-label">Movimientos de caja</h5>
                        <small class="text-muted" id="movements-modal-subtitle">Consulta el detalle cronologico de movimientos registrados.</small>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                </div>
                <div class="modal-body">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle w-100" id="cash-movements-table">
                            <thead>
                                <tr>
                                    <th>Tipo</th>
                                    <th>Concepto</th>
                                    <th>Monto</th>
                                    <th>Metodo</th>
                                    <th>Fecha</th>
                                    <th>Usuario</th>
                                    <th>Notas</th>
                                </tr>
                            </thead>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="arqueo-modal" tabindex="-1" aria-labelledby="arqueo-modal-label" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header">
                    <div>
                        <h5 class="modal-title" id="arqueo-modal-label">Arqueo de caja</h5>
                        <small class="text-muted" id="arqueo-modal-subtitle">Resumen consolidado de caja y movimientos agrupados.</small>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                </div>
                <div class="modal-body" id="arqueo-modal-body">
                    <div class="text-muted">Cargando resumen de arqueo...</div>
                </div>
            </div>
        </div>
    </div>
@stop

@push('css')
    <link rel="stylesheet" href="https://cdn.datatables.net/2.3.8/css/dataTables.bootstrap5.css">
    <style>
        :root {
            --cash-ink: #152238;
            --cash-muted: #6b7280;
            --cash-panel: #ffffff;
            --cash-line: rgba(15, 23, 42, .08);
            --cash-gold: #f4b740;
            --cash-green: #16a34a;
            --cash-red: #dc2626;
            --cash-blue: #2563eb;
            --cash-shadow: 0 24px 60px rgba(15, 23, 42, .12);
        }

        .cash-page-header {
            position: relative;
            overflow: hidden;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 1.5rem;
            min-height: 150px;
            padding: 1.75rem;
            border-radius: 28px;
            color: #fff;
            background:
                radial-gradient(circle at 10% 10%, rgba(244, 183, 64, .28), transparent 32%),
                linear-gradient(135deg, #151a2d 0%, #263650 48%, #101827 100%);
            box-shadow: var(--cash-shadow);
        }

        .cash-page-header::after {
            content: "";
            position: absolute;
            inset: auto -8% -52% 45%;
            height: 180px;
            border-radius: 999px;
            background: rgba(255, 255, 255, .11);
            filter: blur(4px);
            transform: rotate(-8deg);
        }

        .cash-header-copy,
        .cash-header-status {
            position: relative;
            z-index: 1;
        }

        .cash-header-copy h1 {
            font-size: clamp(2rem, 4vw, 3.4rem);
            font-weight: 800;
            letter-spacing: -.04em;
        }

        .cash-header-copy p {
            max-width: 720px;
            color: rgba(255, 255, 255, .76);
        }

        .cash-eyebrow {
            display: inline-flex;
            margin-bottom: .45rem;
            color: #f8d48b;
            font-size: .72rem;
            font-weight: 800;
            letter-spacing: .16em;
            text-transform: uppercase;
        }

        .cash-header-status {
            display: inline-flex;
            align-items: center;
            gap: .75rem;
            min-width: 250px;
            padding: .9rem 1rem;
            border: 1px solid rgba(255, 255, 255, .2);
            border-radius: 20px;
            background: rgba(255, 255, 255, .1);
            backdrop-filter: blur(14px);
        }

        .cash-header-status small,
        .cash-header-status strong {
            display: block;
        }

        .cash-header-status small {
            color: rgba(255, 255, 255, .62);
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: .08em;
        }

        .cash-status-dot {
            width: 14px;
            height: 14px;
            border-radius: 999px;
            background: #94a3b8;
            box-shadow: 0 0 0 6px rgba(148, 163, 184, .18);
        }

        .cash-status-dot.is-open {
            background: #22c55e;
            box-shadow: 0 0 0 6px rgba(34, 197, 94, .2);
        }

        .cash-shell {
            margin-top: 1.5rem;
        }

        .cash-current-panel {
            display: grid;
            grid-template-columns: minmax(0, 1fr) 280px;
            gap: 1rem;
            margin-bottom: 1rem;
            padding: 1.25rem;
            border: 1px solid var(--cash-line);
            border-radius: 28px;
            background: var(--cash-panel);
            box-shadow: 0 18px 45px rgba(15, 23, 42, .08);
        }

        .cash-current-main {
            padding: 1.25rem;
            border-radius: 24px;
            background:
                linear-gradient(135deg, rgba(37, 99, 235, .08), rgba(244, 183, 64, .12)),
                #f8fafc;
        }

        .cash-current-top {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 1rem;
        }

        .cash-current-top h2,
        .cash-empty-state h2,
        .cash-section-head h3 {
            margin: .55rem 0 .25rem;
            color: var(--cash-ink);
            font-weight: 800;
            letter-spacing: -.03em;
        }

        .cash-current-top p,
        .cash-empty-state p,
        .cash-section-head p {
            margin: 0;
            color: var(--cash-muted);
        }

        .cash-pill {
            display: inline-flex;
            align-items: center;
            border-radius: 999px;
            padding: .35rem .7rem;
            font-size: .75rem;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: .08em;
        }

        .cash-pill-success {
            color: #166534;
            background: rgba(34, 197, 94, .14);
        }

        .cash-pill-muted {
            color: #475569;
            background: rgba(100, 116, 139, .14);
        }

        .cash-user-chip {
            display: inline-flex;
            align-items: center;
            gap: .55rem;
            padding: .65rem .8rem;
            border: 1px solid rgba(15, 23, 42, .08);
            border-radius: 999px;
            color: var(--cash-ink);
            background: rgba(255, 255, 255, .72);
            font-weight: 700;
        }

        .cash-metric-hero {
            margin-top: 1.5rem;
        }

        .cash-metric-hero span,
        .cash-metric-hero small,
        .cash-stat-card span {
            display: block;
            color: var(--cash-muted);
            font-size: .8rem;
            font-weight: 800;
            letter-spacing: .08em;
            text-transform: uppercase;
        }

        .cash-metric-hero strong {
            display: block;
            color: var(--cash-ink);
            font-size: clamp(2.3rem, 5vw, 4.25rem);
            line-height: 1;
            letter-spacing: -.06em;
        }

        .cash-actions-panel {
            display: flex;
            flex-direction: column;
            justify-content: center;
            gap: .85rem;
            padding: 1rem;
            border-radius: 24px;
            background: linear-gradient(180deg, #172033, #0f172a);
        }

        .cash-action-btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: .55rem;
            min-height: 48px;
            border: 0;
            border-radius: 16px;
            font-weight: 800;
            box-shadow: 0 12px 30px rgba(15, 23, 42, .16);
        }

        .cash-empty-state {
            grid-column: 1 / -1;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 1.25rem;
            padding: 1.25rem;
            border-radius: 24px;
            background: linear-gradient(135deg, #f8fafc, #eef2ff);
        }

        .cash-empty-icon {
            display: grid;
            place-items: center;
            flex: 0 0 72px;
            width: 72px;
            height: 72px;
            border-radius: 24px;
            color: #92400e;
            background: rgba(244, 183, 64, .22);
            font-size: 2rem;
        }

        .cash-stat-card {
            position: relative;
            overflow: hidden;
            min-height: 130px;
            padding: 1.2rem;
            border: 1px solid var(--cash-line);
            border-radius: 24px;
            background: #fff;
            box-shadow: 0 16px 34px rgba(15, 23, 42, .07);
        }

        .cash-stat-card strong {
            display: block;
            margin-top: .65rem;
            color: var(--cash-ink);
            font-size: 1.55rem;
            font-weight: 850;
            letter-spacing: -.04em;
        }

        .cash-stat-card i {
            position: absolute;
            right: 1rem;
            bottom: .8rem;
            color: rgba(37, 99, 235, .15);
            font-size: 3rem;
        }

        .cash-stat-card.is-income i {
            color: rgba(22, 163, 74, .18);
        }

        .cash-stat-card.is-expense i {
            color: rgba(220, 38, 38, .18);
        }

        .cash-stat-card.is-adjustment i {
            color: rgba(244, 183, 64, .25);
        }

        .cash-history-card {
            padding: 1.15rem;
            border: 1px solid var(--cash-line);
            border-radius: 28px;
            background: #fff;
            box-shadow: 0 18px 45px rgba(15, 23, 42, .08);
        }

        .cash-section-head {
            display: flex;
            align-items: flex-end;
            justify-content: space-between;
            gap: 1rem;
            margin-bottom: 1rem;
            padding: .3rem .25rem 0;
        }

        .cash-section-head .cash-eyebrow {
            color: #8b5e13;
        }

        .cash-table thead th {
            border-bottom: 0;
            color: #64748b;
            font-size: .72rem;
            font-weight: 800;
            letter-spacing: .08em;
            text-transform: uppercase;
            background: #f8fafc;
        }

        .cash-table tbody td {
            border-color: rgba(15, 23, 42, .06);
            padding-top: 1rem;
            padding-bottom: 1rem;
        }

        .cash-summary-card {
            border: 1px solid rgba(15, 23, 42, .08);
            background: linear-gradient(135deg, #f8fafc, #fff);
            box-shadow: inset 0 1px 0 rgba(255, 255, 255, .8);
        }

        .cash-money-positive {
            color: var(--cash-green);
            font-weight: 800;
        }

        .cash-money-negative {
            color: var(--cash-red);
            font-weight: 800;
        }

        .cash-action-group {
            display: inline-flex;
            justify-content: flex-end;
            gap: .35rem;
        }

        .cash-action-group .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 34px;
            height: 34px;
            border-radius: 12px;
        }

        .modal-content {
            border: 0;
            border-radius: 24px;
            box-shadow: 0 28px 70px rgba(15, 23, 42, .22);
        }

        .modal-header {
            border-bottom: 1px solid rgba(15, 23, 42, .08);
            background: linear-gradient(135deg, #f8fafc, #fff);
        }

        #open-cash-modal .modal-dialog,
        #close-cash-modal .modal-dialog,
        #movement-modal .modal-dialog,
        #movements-view-modal .modal-dialog,
        #arqueo-modal .modal-dialog {
            height: calc(100vh - 3.5rem);
        }

        #open-cash-modal .modal-content,
        #close-cash-modal .modal-content,
        #movement-modal .modal-content,
        #movements-view-modal .modal-content,
        #arqueo-modal .modal-content {
            max-height: 100%;
        }

        #open-cash-modal form,
        #close-cash-modal form,
        #movement-modal form {
            display: flex;
            flex-direction: column;
            min-height: 0;
            height: 100%;
        }

        #open-cash-modal .modal-body,
        #close-cash-modal .modal-body,
        #movement-modal .modal-body,
        #movements-view-modal .modal-body,
        #arqueo-modal .modal-body {
            min-height: 0;
            overflow-y: auto;
            overscroll-behavior: contain;
        }

        @media (max-width: 991.98px) {
            .cash-page-header,
            .cash-current-top,
            .cash-empty-state {
                align-items: stretch;
                flex-direction: column;
            }

            .cash-current-panel {
                grid-template-columns: 1fr;
            }

            .cash-header-status {
                width: 100%;
            }
        }

        @media (max-width: 575.98px) {
            .cash-page-header,
            .cash-current-panel,
            .cash-history-card {
                border-radius: 20px;
                padding: 1rem;
            }

            .cash-metric-hero strong {
                font-size: 2.35rem;
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
            const baseCurrency = @json($baseCurrency);
            const currencySymbols = @json($currencySymbols ?? []);
            const canOpenCash = @json(auth()->user()?->can('caja.abrir') ?? false);
            const canAdjustCash = @json(auth()->user()?->can('caja.ajustar') ?? false);
            const canCloseCash = @json(auth()->user()?->can('caja.cerrar') ?? false);
            const currentUrl = '{{ route('adminlte.cash-registers.current') }}';
            const currentCashRegisterSeed = @json($currentCashRegisterData);
            let currentCashRegister = currentCashRegisterSeed;
            let cashMovementsTable = null;

            const openCashForm = document.getElementById('open-cash-form');
            const closeCashForm = document.getElementById('close-cash-form');
            const movementForm = document.getElementById('movement-form');
            const movementAmountInput = document.getElementById('movement_amount');
            const movementCurrencySelect = document.getElementById('movement_currency');
            const movementConversionSummary = document.getElementById('movement-conversion-summary');
            const countedAmountInput = document.getElementById('counted_amount');
            const currentCashCard = document.getElementById('current-cash-register-card');
            const headerDot = document.getElementById('cash-header-dot');
            const headerState = document.getElementById('cash-header-state');
            const headerCode = document.getElementById('cash-header-code');
            const statOpening = document.getElementById('cash-stat-opening');
            const statIncome = document.getElementById('cash-stat-income');
            const statExpense = document.getElementById('cash-stat-expense');
            const statAdjustment = document.getElementById('cash-stat-adjustment');
            const closeCashSummary = document.getElementById('close-cash-summary');
            const movementCashRegisterId = document.getElementById('movement_cash_register_id');
            const openCashModal = window.bootstrap ? new window.bootstrap.Modal(document.getElementById('open-cash-modal')) : null;
            const closeCashModal = window.bootstrap ? new window.bootstrap.Modal(document.getElementById('close-cash-modal')) : null;
            const movementModal = window.bootstrap ? new window.bootstrap.Modal(document.getElementById('movement-modal')) : null;
            const movementsViewModal = window.bootstrap ? new window.bootstrap.Modal(document.getElementById('movements-view-modal')) : null;
            const arqueoModal = window.bootstrap ? new window.bootstrap.Modal(document.getElementById('arqueo-modal')) : null;

            if (typeof $ !== 'function') {
                console.error('jQuery no esta disponible para DataTables.');
                return;
            }

            window.cashRegistersTable = $('#cash-registers-table').DataTable({
                processing: true,
                serverSide: true,
                ajax: '{{ route('adminlte.cash-registers.data') }}',
                language: {
                    url: 'https://cdn.datatables.net/plug-ins/1.13.8/i18n/es-ES.json',
                },
                order: [[3, 'desc']],
                columns: [
                    {
                        data: 'code',
                        name: 'code',
                        render: (data, type, row) => type === 'display'
                            ? `<div class="fw-semibold text-dark">${row.code}</div><div class="small text-muted">${row.closed_at_formatted !== '-' ? `Cierre: ${row.closed_at_formatted}` : 'Caja en seguimiento'}</div>`
                            : data
                    },
                    {
                        data: 'user_name',
                        name: 'user.name',
                        render: (data, type) => type === 'display'
                            ? `<div class="d-flex align-items-center gap-2"><span class="rounded-circle bg-primary-subtle text-primary-emphasis d-inline-flex align-items-center justify-content-center" style="width:34px;height:34px;"><i class="bi bi-person"></i></span><span class="fw-semibold">${data || '-'}</span></div>`
                            : data
                    },
                    {
                        data: 'shift_name',
                        name: 'shift_name',
                        render: (data, type) => type === 'display'
                            ? `<span class="badge rounded-pill text-bg-light border">${data || 'Sin turno'}</span>`
                            : data
                    },
                    { data: 'opened_at_formatted', name: 'opened_at' },
                    { data: 'total_income_formatted', name: 'total_income', className: 'text-nowrap cash-money-positive' },
                    { data: 'total_expense_formatted', name: 'total_expense', className: 'text-nowrap cash-money-negative' },
                    { data: 'expected_amount_formatted', name: 'expected_amount', className: 'text-nowrap fw-semibold' },
                    { data: 'counted_amount_formatted', name: 'counted_amount', className: 'text-nowrap' },
                    {
                        data: 'difference_amount',
                        name: 'difference_amount',
                        className: 'text-center',
                        render: (data, type, row) => {
                            if (type !== 'display') {
                                return data;
                            }

                            let badgeClass = 'badge text-bg-success';
                            if (Number(row.difference_amount) < 0) {
                                badgeClass = 'badge text-bg-danger';
                            } else if (Number(row.difference_amount) > 0) {
                                badgeClass = 'badge text-bg-warning';
                            }

                            return `<span class="${badgeClass}">${row.difference_amount_formatted}</span>`;
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
                        data: null,
                        orderable: false,
                        searchable: false,
                        className: 'text-end',
                        render: (data, type, row) => {
                            if (type !== 'display') {
                                return '';
                            }

                            let actions = '<div class="cash-action-group" role="group">';
                            actions += `<button type="button" class="btn btn-outline-primary cash-movements-btn" title="Ver movimientos" data-url="${row.movements_url}" data-code="${row.code}">
                                <i class="bi bi-list-ul"></i>
                            </button>`;

                            if (row.can_arqueo) {
                                actions += `<button type="button" class="btn btn-outline-info cash-arqueo-btn" title="Ver arqueo" data-url="${row.arqueo_url}" data-code="${row.code}">
                                    <i class="bi bi-calculator"></i>
                                </button>`;
                            }

                            if (row.can_close && row.status === 'open') {
                                actions += `<button type="button" class="btn btn-outline-success cash-close-btn" title="Cerrar caja" data-url="${row.close_url}" data-code="${row.code}" data-cash='${encodeURIComponent(JSON.stringify(row))}'>
                                    <i class="bi bi-lock"></i>
                                </button>`;
                            }

                            actions += '</div>';
                            return actions;
                        }
                    }
                ],
            });

            document.getElementById('open-open-cash-modal-btn')?.addEventListener('click', () => {
                openCashForm.reset();
                openCashModal?.show();
            });

            document.getElementById('open-close-cash-modal-btn')?.addEventListener('click', () => {
                if (!currentCashRegister) {
                    return;
                }
                prepareCloseCashModal(currentCashRegister);
                closeCashModal?.show();
            });

            document.getElementById('open-movement-modal-btn')?.addEventListener('click', () => {
                if (!currentCashRegister) {
                    return;
                }
                movementForm.reset();
                movementCashRegisterId.value = currentCashRegister.id;
                movementCurrencySelect.value = baseCurrency;
                updateMovementConversionSummary();
                movementModal?.show();
            });

            countedAmountInput.addEventListener('input', () => {
                if (!currentCashRegister) {
                    return;
                }
                renderCloseCashSummary(currentCashRegister, Number(countedAmountInput.value || 0));
            });

            movementCurrencySelect.addEventListener('change', updateMovementConversionSummary);
            movementAmountInput.addEventListener('input', updateMovementConversionSummary);

            openCashForm.addEventListener('submit', async (event) => {
                event.preventDefault();
                await submitForm(openCashForm.action, new FormData(openCashForm));
                openCashModal?.hide();
                openCashForm.reset();
            });

            closeCashForm.addEventListener('submit', async (event) => {
                event.preventDefault();
                await submitCloseCashForm(closeCashForm.action, new FormData(closeCashForm));
                closeCashModal?.hide();
                closeCashForm.reset();
            });

            movementForm.addEventListener('submit', async (event) => {
                event.preventDefault();
                await submitForm(movementForm.action, new FormData(movementForm));
                movementModal?.hide();
                movementForm.reset();
            });

            document.addEventListener('click', async (event) => {
                const closeButton = event.target.closest('.cash-close-btn');
                if (closeButton) {
                    const row = JSON.parse(decodeURIComponent(closeButton.dataset.cash));
                    prepareCloseCashModal(row);
                    closeCashModal?.show();
                    return;
                }

                const movementsButton = event.target.closest('.cash-movements-btn');
                if (movementsButton) {
                    openMovementsModal(movementsButton.dataset.url, movementsButton.dataset.code);
                    return;
                }

                const arqueoButton = event.target.closest('.cash-arqueo-btn');
                if (arqueoButton) {
                    await openArqueoModal(arqueoButton.dataset.url, arqueoButton.dataset.code);
                }
            });

            async function submitForm(url, formData) {
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
                    throw new Error('request_failed');
                }

                const payload = await response.json();
                window.cashRegistersTable.ajax.reload(null, false);
                await refreshCurrentCashRegister();

                await fireAlert({
                    icon: 'success',
                    title: payload.message || 'Operacion completada correctamente.',
                    timer: 1800,
                    showConfirmButton: false,
                });
            }

            async function submitCloseCashForm(url, formData) {
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
                    throw new Error('request_failed');
                }

                const payload = await response.json();

                await fireAlert({
                    icon: 'success',
                    title: payload.message || 'Caja cerrada correctamente.',
                    text: 'Actualizando los valores de caja...',
                    timer: 1200,
                    showConfirmButton: false,
                });

                window.location.reload();
            }

            function updateMovementConversionSummary() {
                const amount = Number(movementAmountInput.value || 0);
                const currency = movementCurrencySelect.value || baseCurrency;

                if (!amount) {
                    movementConversionSummary.innerHTML = `<div class="text-muted">Los movimientos en ${baseCurrency} afectan los totales automaticos. Otras monedas se registran sin convertir.</div>`;
                    return;
                }

                if (currency === baseCurrency) {
                    movementConversionSummary.innerHTML = `
                        <div class="fw-semibold mb-2">Aplicacion en caja</div>
                        <div class="row g-2">
                            <div class="col-md-4"><span class="text-muted d-block small">Movimiento</span><span class="fw-semibold">${currency} ${amount.toFixed(2)}</span></div>
                            <div class="col-md-4"><span class="text-muted d-block small">Impacto automatico</span><span class="fw-semibold">${baseCurrency} ${amount.toFixed(2)}</span></div>
                            <div class="col-md-4"><span class="text-muted d-block small">Moneda base</span><span class="fw-semibold">${baseCurrency}</span></div>
                        </div>
                    `;

                    return;
                }

                movementConversionSummary.innerHTML = `
                    <div class="fw-semibold mb-2">Aplicacion en caja</div>
                    <div class="row g-2">
                        <div class="col-md-4"><span class="text-muted d-block small">Movimiento</span><span class="fw-semibold">${currency} ${amount.toFixed(2)}</span></div>
                        <div class="col-md-4"><span class="text-muted d-block small">Impacto automatico</span><span class="fw-semibold">${baseCurrency} 0.00</span></div>
                        <div class="col-md-4"><span class="text-muted d-block small">Nota</span><span class="fw-semibold">Se registrara en ${currency} y quedara fuera de los totales automaticos de la moneda base.</span></div>
                    </div>
                `;
            }

            function prepareCloseCashModal(cashRegister) {
                closeCashForm.action = cashRegister.close_url;
                countedAmountInput.value = Number(cashRegister.expected_amount || 0).toFixed(2);
                renderCloseCashSummary(cashRegister, Number(cashRegister.expected_amount || 0));
            }

            function renderCloseCashSummary(cashRegister, countedAmount) {
                const difference = countedAmount - Number(cashRegister.expected_amount || 0);
                closeCashSummary.innerHTML = `
                    <div class="row g-3">
                        <div class="col-md-4"><span class="text-muted d-block small">Monto inicial</span><span class="fw-semibold">Bs. ${Number(cashRegister.opening_amount || 0).toFixed(2)}</span></div>
                        <div class="col-md-4"><span class="text-muted d-block small">Ingresos</span><span class="fw-semibold">Bs. ${Number(cashRegister.total_income || 0).toFixed(2)}</span></div>
                        <div class="col-md-4"><span class="text-muted d-block small">Egresos</span><span class="fw-semibold">Bs. ${Number(cashRegister.total_expense || 0).toFixed(2)}</span></div>
                        <div class="col-md-4"><span class="text-muted d-block small">Ajustes</span><span class="fw-semibold">Bs. ${Number(cashRegister.total_adjustment || 0).toFixed(2)}</span></div>
                        <div class="col-md-4"><span class="text-muted d-block small">Caja esperada</span><span class="fw-semibold">Bs. ${Number(cashRegister.expected_amount || 0).toFixed(2)}</span></div>
                        <div class="col-md-4"><span class="text-muted d-block small">Diferencia calculada</span><span class="fw-semibold">Bs. ${difference.toFixed(2)}</span></div>
                    </div>
                `;
            }

            async function refreshCurrentCashRegister() {
                const response = await fetch(currentUrl, {
                    headers: {
                        'Accept': 'application/json',
                    },
                });

                if (!response.ok) {
                    return;
                }

                currentCashRegister = await response.json();
                renderCashHeader(currentCashRegister);
                renderCurrentCashCard(currentCashRegister);
                renderCurrentCashStats(currentCashRegister);
            }

            function renderCashHeader(cashRegister) {
                headerDot.classList.toggle('is-open', !!cashRegister);
                headerState.textContent = cashRegister ? 'Caja activa' : 'Sin caja abierta';
                headerCode.textContent = cashRegister?.code ?? 'Pendiente de apertura';
            }

            function renderCurrentCashCard(cashRegister) {
                if (!cashRegister) {
                    currentCashCard.innerHTML = `
                        <div class="cash-empty-state">
                            <div class="cash-empty-icon">
                                <i class="bi bi-safe2"></i>
                            </div>
                            <div>
                                <span class="cash-pill cash-pill-muted">Pendiente</span>
                                <h2>No tienes caja abierta</h2>
                                <p>Abre una caja para comenzar a registrar ingresos, egresos y ajustes del turno actual.</p>
                            </div>
                            ${canOpenCash ? `<button type="button" class="btn btn-warning cash-action-btn" id="open-open-cash-modal-btn-dynamic">
                                <i class="bi bi-unlock"></i>
                                Abrir caja
                            </button>` : ''}
                        </div>
                    `;

                    document.getElementById('open-open-cash-modal-btn-dynamic')?.addEventListener('click', () => {
                        openCashForm.reset();
                        openCashModal?.show();
                    });
                    return;
                }

                currentCashCard.innerHTML = `
                    <div class="cash-current-main">
                        <div class="cash-current-top">
                            <div>
                                <span class="cash-pill cash-pill-success">Caja abierta</span>
                                <h2>${cashRegister.code}</h2>
                                <p>${cashRegister.shift_name ? `Turno: ${cashRegister.shift_name} - ` : ''}Apertura: ${cashRegister.opened_at_formatted}</p>
                            </div>
                            <div class="cash-user-chip">
                                <i class="bi bi-person-badge"></i>
                                <span>${cashRegister.user_name}</span>
                            </div>
                        </div>
                        <div class="cash-metric-hero">
                            <span>Caja esperada</span>
                            <strong>${cashRegister.expected_amount_formatted}</strong>
                            <small>Saldo calculado con movimientos en moneda base</small>
                        </div>
                    </div>
                    <div class="cash-actions-panel">
                        ${canAdjustCash ? `<button type="button" class="btn btn-light cash-action-btn" id="open-movement-modal-btn-dynamic">
                            <i class="bi bi-plus-circle"></i>
                            Registrar movimiento
                        </button>` : ''}
                        ${canCloseCash ? `<button type="button" class="btn btn-warning cash-action-btn" id="open-close-cash-modal-btn-dynamic">
                            <i class="bi bi-lock"></i>
                            Cerrar caja
                        </button>` : ''}
                    </div>
                `;

                document.getElementById('open-movement-modal-btn-dynamic')?.addEventListener('click', () => {
                    movementForm.reset();
                    movementCashRegisterId.value = cashRegister.id;
                    movementCurrencySelect.value = baseCurrency;
                    updateMovementConversionSummary();
                    movementModal?.show();
                });

                document.getElementById('open-close-cash-modal-btn-dynamic')?.addEventListener('click', () => {
                    prepareCloseCashModal(cashRegister);
                    closeCashModal?.show();
                });
            }

            function renderCurrentCashStats(cashRegister) {
                statOpening.textContent = cashRegister?.opening_amount_formatted ?? 'Bs. 0.00';
                statIncome.textContent = cashRegister?.total_income_formatted ?? 'Bs. 0.00';
                statExpense.textContent = cashRegister?.total_expense_formatted ?? 'Bs. 0.00';
                statAdjustment.textContent = cashRegister?.total_adjustment_formatted ?? 'Bs. 0.00';
            }

            function openMovementsModal(url, code) {
                document.getElementById('movements-modal-subtitle').textContent = `Caja ${code}`;

                if (cashMovementsTable) {
                    cashMovementsTable.ajax.url(url).load();
                } else {
                    cashMovementsTable = $('#cash-movements-table').DataTable({
                        processing: true,
                        serverSide: true,
                        ajax: url,
                        language: {
                            url: 'https://cdn.datatables.net/plug-ins/1.13.8/i18n/es-ES.json',
                        },
                        order: [[4, 'desc']],
                        columns: [
                            { data: 'type_label', name: 'type' },
                            {
                                data: 'concept',
                                name: 'concept',
                                render: (data, type, row) => {
                                    if (type !== 'display') {
                                        return data;
                                    }

                                    const linkedPayment = row.payment_code
                                        ? `<div class="small text-success fw-semibold"><i class="bi bi-link-45deg me-1"></i>Pago ${row.payment_code}</div>`
                                        : '<div class="small text-muted">Movimiento manual</div>';
                                    const reservation = row.reservation_code
                                        ? `<div class="small text-muted">Reserva ${row.reservation_code}${row.customer_name ? ` - ${row.customer_name}` : ''}</div>`
                                        : '';

                                    return `<div class="fw-semibold">${data || '-'}</div>${linkedPayment}${reservation}`;
                                }
                            },
                            {
                                data: 'amount_formatted',
                                name: 'amount_base',
                                className: 'text-nowrap fw-semibold',
                                render: (data, type, row) => {
                                    if (type !== 'display') {
                                        return data;
                                    }

                                    const baseLine = row.affects_base
                                        ? `<div class="small text-muted">Impacto automatico: ${row.amount_base_formatted}</div>`
                                        : '<div class="small text-muted">Sin impacto automatico</div>';

                                    return `<div>${row.amount_formatted}</div>${baseLine}`;
                                }
                            },
                            { data: 'payment_method_label', name: 'payment_method' },
                            { data: 'movement_date_formatted', name: 'movement_date' },
                            { data: 'created_by_name', name: 'createdBy.name', defaultContent: '-' },
                            { data: 'notes', name: 'notes', defaultContent: '-' },
                        ],
                    });
                }

                movementsViewModal?.show();
            }

            async function openArqueoModal(url, code) {
                document.getElementById('arqueo-modal-subtitle').textContent = `Resumen de ${code}`;
                document.getElementById('arqueo-modal-body').innerHTML = '<div class="text-muted">Cargando resumen de arqueo...</div>';
                arqueoModal?.show();

                const response = await fetch(url, {
                    headers: {
                        'Accept': 'application/json',
                    },
                });

                if (!response.ok) {
                    document.getElementById('arqueo-modal-body').innerHTML = '<div class="text-danger">No fue posible cargar el arqueo.</div>';
                    return;
                }

                const payload = await response.json();
                document.getElementById('arqueo-modal-body').innerHTML = `
                    <div class="cash-summary-card rounded-3 p-3 mb-3">
                        <div class="row g-3">
                            <div class="col-md-4"><span class="text-muted d-block small">Monto inicial</span><span class="fw-semibold">Bs. ${Number(payload.opening_amount || 0).toFixed(2)}</span></div>
                            <div class="col-md-4"><span class="text-muted d-block small">Ingresos</span><span class="fw-semibold">Bs. ${Number(payload.total_income || 0).toFixed(2)}</span></div>
                            <div class="col-md-4"><span class="text-muted d-block small">Egresos</span><span class="fw-semibold">Bs. ${Number(payload.total_expense || 0).toFixed(2)}</span></div>
                            <div class="col-md-4"><span class="text-muted d-block small">Ajustes</span><span class="fw-semibold">Bs. ${Number(payload.total_adjustment || 0).toFixed(2)}</span></div>
                            <div class="col-md-4"><span class="text-muted d-block small">Esperado</span><span class="fw-semibold">Bs. ${Number(payload.expected_amount || 0).toFixed(2)}</span></div>
                            <div class="col-md-4"><span class="text-muted d-block small">Contado</span><span class="fw-semibold">${payload.counted_amount !== null ? `Bs. ${Number(payload.counted_amount).toFixed(2)}` : '-'}</span></div>
                            <div class="col-md-4"><span class="text-muted d-block small">Diferencia</span><span class="fw-semibold">Bs. ${Number(payload.difference_amount || 0).toFixed(2)}</span></div>
                        </div>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-sm align-middle">
                            <thead>
                                <tr>
                                    <th>Tipo</th>
                                    <th>Metodo</th>
                                    <th>Monto</th>
                                    <th>Movimientos</th>
                                </tr>
                            </thead>
                            <tbody>
                                ${payload.movements.length
                                    ? payload.movements.map((movement) => `
                                        <tr>
                                            <td>${movement.type_label}</td>
                                            <td>${movement.payment_method_label}</td>
                                            <td class="fw-semibold">${movement.total_amount_formatted}</td>
                                            <td>${movement.total_movements}</td>
                                        </tr>
                                    `).join('')
                                    : '<tr><td colspan="4" class="text-center text-muted">Sin movimientos registrados.</td></tr>'}
                            </tbody>
                        </table>
                    </div>
                `;
            }

            async function handleRequestError(response) {
                let html = 'Ocurrio un error inesperado.';

                try {
                    const payload = await response.json();
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

                if (confirmFallback) {
                    return { isConfirmed: window.confirm(options.text || options.title || '') };
                }

                window.alert(options.text || options.title || '');
                return { isConfirmed: true };
            }

            updateMovementConversionSummary();
        });
    </script>
@endpush
