@php
    use ColorlibHQ\AdminLte\Support\NavbarData;

    // Real unread notifications when the `notifications` table exists, otherwise
    // the config-driven demo data. See NavbarData for the fallback logic.
    $notifications = NavbarData::notifications();
    $notificationCount = NavbarData::notificationCount();
    $notificationsUrl = \Illuminate\Support\Facades\Route::has('adminlte.notifications.index')
        ? route('adminlte.notifications.index')
        : '#';
@endphp
<li class="nav-item dropdown admin-notifications-menu">
    <a class="nav-link admin-notification-toggle" data-bs-toggle="dropdown" href="#" aria-label="{{ __('adminlte.notifications') }}">
        <i class="bi bi-bell-fill" aria-hidden="true"></i>
        @if ($notificationCount > 0)
            <span class="navbar-badge badge text-bg-warning">{{ $notificationCount }}</span>
        @endif
    </a>
    <div class="dropdown-menu dropdown-menu-end admin-notifications-dropdown">
        <div class="admin-notifications-head">
            <span class="admin-notifications-title">{{ __('adminlte.notifications') }}</span>
            <span class="admin-notifications-count">{{ $notificationCount }}</span>
        </div>
        <div class="admin-notifications-list">
        @forelse ($notifications as $note)
            <a href="{{ $note['url'] ?? '#' }}" class="dropdown-item admin-notification-item">
                <span class="admin-notification-icon">
                    <i class="{{ $note['icon'] ?? 'bi bi-bell-fill' }}" aria-hidden="true"></i>
                </span>
                <span class="admin-notification-body">
                    <span class="admin-notification-text">{{ $note['text'] }}</span>
                    @if ($note['time'] ?? null)
                        <span class="admin-notification-time">
                            <i class="bi bi-clock" aria-hidden="true"></i>
                            {{ $note['time'] }}
                        </span>
                    @endif
                </span>
            </a>
        @empty
            <span class="dropdown-item admin-notification-empty">
                <i class="bi bi-bell-slash" aria-hidden="true"></i>
                {{ __('adminlte.no_notifications') }}
            </span>
        @endforelse
        </div>
        <a href="{{ $notificationsUrl }}" class="dropdown-item dropdown-footer admin-notifications-footer">
            {{ __('adminlte.see_all_notifications') }}
            <i class="bi bi-arrow-right-short" aria-hidden="true"></i>
        </a>
    </div>
</li>
