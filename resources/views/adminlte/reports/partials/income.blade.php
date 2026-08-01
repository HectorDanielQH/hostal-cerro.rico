@php
    $filters = $report['filters'];
    $summary = $report['summary'];
@endphp
<div class="report-panel" data-report-panel="income">
    <form class="row g-3 report-filter-form" data-report-form="income" action="{{ route('adminlte.reports.income') }}" method="GET">
        <div class="col-md-3"><label class="form-label">Desde</label><input type="date" name="date_from" value="{{ $filters['date_from'] }}" class="form-control"></div>
        <div class="col-md-3"><label class="form-label">Hasta</label><input type="date" name="date_to" value="{{ $filters['date_to'] }}" class="form-control"></div>
        <div class="col-md-3"><label class="form-label">Metodo</label><select name="payment_method" class="form-select"><option value="">Todos</option>@foreach ($filterOptions['paymentMethods'] as $value => $label)<option value="{{ $value }}" @selected(($filters['payment_method'] ?? null) === $value)>{{ $label }}</option>@endforeach</select></div>
        <div class="col-md-3"><label class="form-label">Tipo habitacion</label><select name="room_type_id" class="form-select"><option value="">Todos</option>@foreach ($filterOptions['roomTypes'] as $roomType)<option value="{{ $roomType->id }}" @selected((string) ($filters['room_type_id'] ?? '') === (string) $roomType->id)>{{ $roomType->name }}</option>@endforeach</select></div>
        <div class="col-12 d-flex gap-2 flex-wrap">
            <button type="submit" class="btn btn-primary">Buscar</button>
            <button type="button" class="btn btn-outline-secondary" data-report-reset="income">Limpiar</button>
            @if ($canExport)
                <a href="{{ route('adminlte.reports.income.export', $filters) }}" class="btn btn-success">Exportar CSV</a>
            @endif
        </div>
    </form>
    <div class="row g-3 mt-1">
        <div class="col-md-4"><div class="small-box text-bg-success"><div class="inner"><h3>Bs. {{ number_format($summary['total_confirmed_income'], 2, '.', '') }}</h3><p>Ingresos confirmados</p></div></div></div>
        <div class="col-md-4"><div class="small-box text-bg-primary"><div class="inner"><h3>{{ $summary['total_reservations'] }}</h3><p>Reservas del periodo</p></div></div></div>
        <div class="col-md-4"><div class="small-box text-bg-warning"><div class="inner"><h3>Bs. {{ number_format($summary['pending_balance'], 2, '.', '') }}</h3><p>Saldo pendiente</p></div></div></div>
    </div>
    <div class="row g-4 mt-2">
        <div class="col-lg-4">
            <div class="card"><div class="card-header"><strong>Por metodo de pago</strong></div><div class="card-body"><ul class="list-group list-group-flush">@forelse($report['by_payment_method'] as $row)<li class="list-group-item d-flex justify-content-between"><span>{{ $filterOptions['paymentMethods'][$row['payment_method']] ?? $row['payment_method'] }}</span><strong>Bs. {{ number_format($row['amount'], 2, '.', '') }}</strong></li>@empty<li class="list-group-item text-muted">Sin datos.</li>@endforelse</ul></div></div>
        </div>
        <div class="col-lg-4">
            <div class="card"><div class="card-header"><strong>Por tipo de habitacion</strong></div><div class="card-body"><ul class="list-group list-group-flush">@forelse($report['by_room_type'] as $row)<li class="list-group-item d-flex justify-content-between"><span>{{ $row['room_type_name'] }}</span><strong>Bs. {{ number_format($row['amount'], 2, '.', '') }}</strong></li>@empty<li class="list-group-item text-muted">Sin datos.</li>@endforelse</ul></div></div>
        </div>
        <div class="col-lg-4">
            <div class="card"><div class="card-header"><strong>Por dia</strong></div><div class="card-body"><ul class="list-group list-group-flush">@forelse($report['by_day'] as $row)<li class="list-group-item d-flex justify-content-between"><span>{{ $row['date'] }}</span><strong>Bs. {{ number_format($row['amount'], 2, '.', '') }}</strong></li>@empty<li class="list-group-item text-muted">Sin datos.</li>@endforelse</ul></div></div>
        </div>
    </div>
</div>
