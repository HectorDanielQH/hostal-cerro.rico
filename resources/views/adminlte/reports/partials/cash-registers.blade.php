@php
    $filters = $report['filters'];
    $summary = $report['summary'];
@endphp
<div class="report-panel" data-report-panel="cash-registers">
    <form class="row g-3 report-filter-form" data-report-form="cash-registers" action="{{ route('adminlte.reports.cash-registers') }}" method="GET">
        <div class="col-md-3"><label class="form-label">Desde</label><input type="date" name="date_from" value="{{ $filters['date_from'] }}" class="form-control"></div>
        <div class="col-md-3"><label class="form-label">Hasta</label><input type="date" name="date_to" value="{{ $filters['date_to'] }}" class="form-control"></div>
        <div class="col-md-3"><label class="form-label">Usuario</label><select name="user_id" class="form-select"><option value="">Todos</option>@foreach ($filterOptions['users'] as $user)<option value="{{ $user->id }}" @selected((string) ($filters['user_id'] ?? '') === (string) $user->id)>{{ $user->name }}</option>@endforeach</select></div>
        <div class="col-md-3"><label class="form-label">Estado</label><select name="status" class="form-select"><option value="">Todos</option>@foreach ($filterOptions['cashRegisterStatuses'] as $value => $label)<option value="{{ $value }}" @selected(($filters['status'] ?? null) === $value)>{{ $label }}</option>@endforeach</select></div>
        <div class="col-12 d-flex gap-2 flex-wrap"><button type="submit" class="btn btn-primary">Buscar</button><button type="button" class="btn btn-outline-secondary" data-report-reset="cash-registers">Limpiar</button>@if($canExport)<a href="{{ route('adminlte.reports.cash-registers.export', $filters) }}" class="btn btn-success">Exportar CSV</a>@endif</div>
    </form>
    <div class="row g-3 mt-1">
        <div class="col-md-3"><div class="small-box text-bg-primary"><div class="inner"><h3>Bs. {{ number_format($summary['total_opening'], 2, '.', '') }}</h3><p>Apertura</p></div></div></div>
        <div class="col-md-3"><div class="small-box text-bg-success"><div class="inner"><h3>Bs. {{ number_format($summary['total_income'], 2, '.', '') }}</h3><p>Ingresos</p></div></div></div>
        <div class="col-md-3"><div class="small-box text-bg-danger"><div class="inner"><h3>Bs. {{ number_format($summary['total_expense'], 2, '.', '') }}</h3><p>Egresos</p></div></div></div>
        <div class="col-md-3"><div class="small-box text-bg-secondary"><div class="inner"><h3>Bs. {{ number_format($summary['total_difference'], 2, '.', '') }}</h3><p>Diferencia</p></div></div></div>
    </div>
    <div class="table-responsive mt-3">
        <table class="table table-striped table-bordered align-middle">
            <thead><tr><th>Codigo</th><th>Usuario</th><th>Turno</th><th>Apertura</th><th>Cierre</th><th>Inicial</th><th>Ingresos</th><th>Egresos</th><th>Ajustes</th><th>Esperado</th><th>Contado</th><th>Diferencia</th><th>Estado</th></tr></thead>
            <tbody>
            @forelse ($report['rows'] as $cashRegister)
                <tr>
                    <td>{{ $cashRegister->code }}</td>
                    <td>{{ $cashRegister->user?->name }}</td>
                    <td>{{ $cashRegister->shift_name ?: '-' }}</td>
                    <td>{{ optional($cashRegister->opened_at)->format('d/m/Y H:i') }}</td>
                    <td>{{ optional($cashRegister->closed_at)->format('d/m/Y H:i') ?: '-' }}</td>
                    <td>Bs. {{ number_format((float) $cashRegister->opening_amount, 2, '.', '') }}</td>
                    <td>Bs. {{ number_format((float) $cashRegister->total_income, 2, '.', '') }}</td>
                    <td>Bs. {{ number_format((float) $cashRegister->total_expense, 2, '.', '') }}</td>
                    <td>Bs. {{ number_format((float) $cashRegister->total_adjustment, 2, '.', '') }}</td>
                    <td>Bs. {{ number_format((float) $cashRegister->expected_amount, 2, '.', '') }}</td>
                    <td>Bs. {{ number_format((float) $cashRegister->counted_amount, 2, '.', '') }}</td>
                    <td>Bs. {{ number_format((float) $cashRegister->difference_amount, 2, '.', '') }}</td>
                    <td>{{ $filterOptions['cashRegisterStatuses'][$cashRegister->status] ?? $cashRegister->status }}</td>
                </tr>
            @empty
                <tr><td colspan="13" class="text-center text-muted">No hay cajas para los filtros seleccionados.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>
