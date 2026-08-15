@php
    $navLeft = app('adminlte')->menu('navbar-left');
    $currentUser = auth()->user();
    $currentUser?->loadMissing('workShift');
    $assignedShift = $currentUser?->workShift;
    $showShiftBadge = $assignedShift || $currentUser?->hasRole('receptionist');
    $shiftIsActive = (bool) ($assignedShift?->is_active ?? false);
    $shiftLabel = $assignedShift
        ? trim($assignedShift->name.' - '.$assignedShift->scheduleLabel())
        : 'Sin turno asignado';
@endphp
<nav class="app-header {{ config('adminlte.classes_topnav', 'navbar-expand bg-body') }} navbar">
    <div class="{{ config('adminlte.classes_topnav_container', 'container-fluid') }}">
        {{-- Left side --}}
        <ul class="navbar-nav">
            <li class="nav-item">
                <a class="nav-link" data-lte-toggle="sidebar" href="#" role="button" aria-label="{{ __('Toggle sidebar') }}">
                    <i class="bi bi-list" aria-hidden="true"></i>
                </a>
            </li>

            @foreach ($navLeft as $item)
                <li class="nav-item d-none d-md-block">
                    <a href="{{ $item['href'] ?? '#' }}" class="nav-link">{{ $item['text'] ?? '' }}</a>
                </li>
            @endforeach
        </ul>

        {{-- Right side --}}
        <ul class="navbar-nav ms-auto">
            @if ($showShiftBadge)
                <li class="nav-item d-flex align-items-center me-2 adminlte-current-shift-item">
                    <span class="adminlte-current-shift {{ $shiftIsActive ? 'is-active' : 'is-warning' }}"
                          title="{{ $shiftIsActive ? 'Turno asignado activo' : 'Turno no asignado o inactivo' }}">
                        <i class="bi bi-clock-history" aria-hidden="true"></i>
                        <span class="adminlte-current-shift__text">
                            <small>Turno</small>
                            <strong>{{ $shiftLabel }}</strong>
                        </span>
                    </span>
                </li>
            @endif
            {{-- Search (opens the ⌘K command palette) — single unified pill --}}
            <li class="nav-item d-flex align-items-center me-lg-2">
                <button type="button" data-adminlte-search aria-label="{{ __('adminlte.search') }}"
                        class="adminlte-search-trigger btn btn-sm border rounded-pill d-flex align-items-center gap-2 px-2 px-lg-3 text-body-secondary">
                    <i class="bi bi-search" aria-hidden="true"></i>
                    <span class="d-none d-lg-inline">{{ __('adminlte.search') }}…</span>
                    <kbd class="d-none d-lg-inline small border rounded px-1 ms-lg-3">⌘K</kbd>
                </button>
            </li>

            {{-- Notifications dropdown --}}
            @include('adminlte::partials.navbar-notifications')

            {{-- Fullscreen toggle --}}
            <li class="nav-item">
                <a class="nav-link" href="#" data-lte-toggle="fullscreen" aria-label="{{ __('Toggle fullscreen') }}">
                    <i data-lte-icon="maximize" class="bi bi-arrows-fullscreen" aria-hidden="true"></i>
                    <i data-lte-icon="minimize" class="bi bi-fullscreen-exit d-none" aria-hidden="true"></i>
                </a>
            </li>

            {{-- Color mode toggle --}}
            @if (config('adminlte.color_mode_toggle', true))
                @include('adminlte::partials.color-mode')
            @endif

            {{-- User menu --}}
            @if (config('adminlte.usermenu_enabled', true))
                @include('adminlte::partials.usermenu')
            @endif
        </ul>
    </div>
</nav>

<style>
    .adminlte-current-shift {
        align-items: center;
        border: 1px solid rgba(13, 110, 253, .16);
        border-radius: 999px;
        display: inline-flex;
        gap: .55rem;
        max-width: 280px;
        min-height: 38px;
        padding: .35rem .75rem .35rem .45rem;
        white-space: nowrap;
    }

    .adminlte-current-shift i {
        align-items: center;
        border-radius: 999px;
        display: inline-flex;
        height: 28px;
        justify-content: center;
        width: 28px;
    }

    .adminlte-current-shift.is-active {
        background: linear-gradient(135deg, rgba(22, 163, 74, .12), rgba(13, 110, 253, .08));
        color: #14532d;
    }

    .adminlte-current-shift.is-active i {
        background: #dcfce7;
        color: #15803d;
    }

    .adminlte-current-shift.is-warning {
        background: linear-gradient(135deg, rgba(245, 158, 11, .16), rgba(248, 250, 252, .92));
        color: #92400e;
    }

    .adminlte-current-shift.is-warning i {
        background: #fef3c7;
        color: #b45309;
    }

    .adminlte-current-shift__text {
        display: grid;
        line-height: 1.05;
        min-width: 0;
    }

    .adminlte-current-shift__text small {
        color: currentColor;
        font-size: .66rem;
        font-weight: 800;
        letter-spacing: .08em;
        opacity: .72;
        text-transform: uppercase;
    }

    .adminlte-current-shift__text strong {
        display: block;
        font-size: .82rem;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    @media (max-width: 991.98px) {
        .adminlte-current-shift {
            max-width: 150px;
            padding-right: .6rem;
        }

        .adminlte-current-shift__text small {
            display: none;
        }
    }

    @media (max-width: 575.98px) {
        .adminlte-current-shift-item {
            display: none !important;
        }
    }
</style>

@include('adminlte::partials.command-palette')
