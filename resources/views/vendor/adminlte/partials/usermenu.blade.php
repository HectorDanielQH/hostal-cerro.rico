@php
    $user = auth()->user();
    $name = $user->name ?? ($user->email ?? 'Guest');
    $initial = strtoupper(mb_substr($name, 0, 1));
    $fallbackAvatar = 'data:image/svg+xml;base64,'.base64_encode(
        '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 120 120">'.
        '<rect width="120" height="120" rx="60" fill="#0d6efd"/>'.
        '<text x="60" y="72" text-anchor="middle" font-size="52" font-family="Arial, sans-serif" fill="#ffffff">'.$initial.'</text>'.
        '</svg>'
    );
    $avatar = (config('adminlte.usermenu_image') && ! empty($user?->profile_photo_url))
        ? $user->profile_photo_url
        : $fallbackAvatar;
    $memberSince = $user?->created_at ? $user->created_at->format('M. Y') : null;
@endphp
<li class="nav-item dropdown user-menu">
    <a href="#"
       id="adminlte-usermenu-toggle"
       class="nav-link dropdown-toggle"
       data-bs-toggle="dropdown"
       aria-expanded="false"
       onclick="event.preventDefault(); const menu = this.nextElementSibling; const isOpen = menu.classList.contains('show'); document.querySelectorAll('.user-menu .dropdown-menu.show').forEach(el => el.classList.remove('show')); document.querySelectorAll('.user-menu .dropdown-toggle[aria-expanded=&quot;true&quot;]').forEach(el => el.setAttribute('aria-expanded', 'false')); menu.classList.toggle('show', !isOpen); this.setAttribute('aria-expanded', String(!isOpen));">
        <img src="{{ $avatar }}" class="user-image rounded-circle shadow" alt="{{ $name }}" width="30" height="30">
        <span class="d-none d-md-inline">{{ $name }}</span>
    </a>
    <ul class="dropdown-menu dropdown-menu-lg dropdown-menu-end" aria-labelledby="adminlte-usermenu-toggle">
        {{-- Header --}}
        <li class="user-header text-bg-primary">
            <img src="{{ $avatar }}" class="rounded-circle shadow" alt="{{ $name }}" width="90" height="90">
            <p>
                {{ $name }}
                @if ($memberSince)<small>{{ __('adminlte.member_since') }} {{ $memberSince }}</small>@endif
            </p>
        </li>
        {{-- Footer --}}
        <li class="user-footer d-flex justify-content-between gap-2">
            <a href="{{ url(config('adminlte.usermenu_profile_url') ?: 'admin/profile') }}" class="btn btn-outline-secondary">
                {{ __('adminlte.profile') }}
            </a>
            <a href="#" class="btn btn-outline-danger"
               onclick="event.preventDefault(); document.getElementById('adminlte-logout-form').submit();">
                {{ __('adminlte.sign_out') }}
            </a>
            <form id="adminlte-logout-form" action="{{ url('logout') }}" method="POST" class="d-none">@csrf</form>
        </li>
    </ul>
</li>
<script>
    document.addEventListener('click', function (event) {
        const wrapper = event.target.closest('.user-menu');
        if (wrapper) {
            return;
        }

        document.querySelectorAll('.user-menu .dropdown-menu.show').forEach(function (menu) {
            menu.classList.remove('show');
        });

        document.querySelectorAll('.user-menu .dropdown-toggle[aria-expanded="true"]').forEach(function (toggle) {
            toggle.setAttribute('aria-expanded', 'false');
        });
    });
</script>
