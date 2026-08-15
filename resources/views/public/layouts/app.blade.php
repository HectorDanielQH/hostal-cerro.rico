@php
    $hotelSetting = $hotelSetting ?? new \App\Models\HotelSetting([
        'hotel_name' => 'Hostal Cerro Rico',
        'city' => 'Potosi',
        'country' => 'Bolivia',
        'currency' => 'BOB',
    ]);
    $siteTitle = $title ?? $hotelSetting->hotel_name;
    $siteDescription = $metaDescription ?? ($hotelSetting->description_short ?: 'Hospedaje comodo y bien ubicado en '.$hotelSetting->city.', '.$hotelSetting->country.'.');
    $coverImage = $hotelSetting->cover_image ? asset('storage/'.$hotelSetting->cover_image) : null;
    $logoImage = $hotelSetting->logo ? asset('storage/'.$hotelSetting->logo) : null;
    $ogImage = $coverImage ?: $logoImage;
    $currentLocale = app()->getLocale();
    $supportedLocales = \App\Http\Middleware\SetPublicLocale::supportedLocales();
    $socialLinks = collect($hotelSetting->publicSocialLinks());
    $publicContactPeople = collect($hotelSetting->publicContactPeople());
    $publicContactEmails = collect($hotelSetting->publicContactEmails());
    $whatsAppUrl = $publicContactPeople->first()['whatsapp_url'] ?? null;
    $googleMapsPublicUrl = $hotelSetting->googleMapsPublicUrl();
    $googleMapsEmbedUrl = $hotelSetting->googleMapsEmbedUrl();
    $themeCssVariables = collect($hotelSetting->publicThemeCssVariables())
        ->map(fn ($value, $property) => $property.': '.$value)
        ->implode('; ');
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', $currentLocale) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $siteTitle }}</title>
    <meta name="description" content="{{ $siteDescription }}">
    <meta property="og:title" content="{{ $siteTitle }}">
    <meta property="og:description" content="{{ $siteDescription }}">
    <meta property="og:type" content="website">
    @if ($ogImage)
        <meta property="og:image" content="{{ $ogImage }}">
    @endif
    @if ($hotelSetting->favicon)
        @php($faviconUrl = asset('storage/'.$hotelSetting->favicon))
        @php($faviconExtension = strtolower(pathinfo((string) $hotelSetting->favicon, PATHINFO_EXTENSION)))
        <link rel="icon" href="{{ $faviconUrl }}" sizes="any" @if ($faviconExtension === 'ico') type="image/x-icon" @endif>
        <link rel="shortcut icon" href="{{ $faviconUrl }}" @if ($faviconExtension === 'ico') type="image/x-icon" @endif>
        @if (in_array($faviconExtension, ['png', 'jpg', 'jpeg', 'webp'], true))
            <link rel="apple-touch-icon" href="{{ $faviconUrl }}">
        @endif
    @endif
    @stack('styles')
    @vite(['resources/css/public.css', 'resources/js/public.js'])
</head>
<body class="public-body {{ request()->routeIs('public.home') ? 'public-home' : 'public-inner' }} {{ request()->routeIs('public.booking.*') ? 'public-booking-page' : '' }}" style="{{ $themeCssVariables }}" data-public-currency-root data-default-currency="{{ $hotelSetting->baseCurrency() }}" data-local-country="{{ $hotelSetting->country ?: 'Bolivia' }}">
    <div class="public-topbar">
        <div class="container public-topbar-inner">
            <div class="topbar-copy">
                @if ($hotelSetting->phone)
                    <a href="tel:{{ preg_replace('/\s+/', '', $hotelSetting->phone) }}">{{ $hotelSetting->phone }}</a>
                @endif
                @if ($hotelSetting->email)
                    <a href="mailto:{{ $hotelSetting->email }}">{{ $hotelSetting->email }}</a>
                @endif
            </div>
            <div class="topbar-actions">
                @if ($whatsAppUrl)
                    <a href="{{ $whatsAppUrl }}" target="_blank" rel="noopener">{{ __('public.layout.footer.whatsapp') }}</a>
                @endif
            </div>
        </div>
    </div>
    <header class="public-header" data-public-header>
        <div class="container">
            <nav class="navbar navbar-expand-xxl public-navbar-shell">
                <a class="navbar-brand d-flex align-items-center gap-3" href="{{ route('public.home') }}">
                    <span class="brand-mark">
                        @if ($logoImage)
                            <img src="{{ $logoImage }}" alt="{{ $hotelSetting->hotel_name }}" class="brand-logo">
                        @else
                            <span class="brand-fallback">{{ \Illuminate\Support\Str::upper(\Illuminate\Support\Str::substr($hotelSetting->hotel_name, 0, 1)) }}</span>
                        @endif
                    </span>
                    <span>
                        <span class="d-block brand-title">{{ $hotelSetting->hotel_name ?: 'Hostal Cerro Rico' }}</span>
                    </span>
                </a>
                <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#publicNavbar" aria-controls="publicNavbar" aria-expanded="false" aria-label="{{ __('public.layout.aria_open_menu') }}">
                    <span class="navbar-toggler-icon"></span>
                </button>
                <div class="collapse navbar-collapse" id="publicNavbar">
                    <ul class="navbar-nav ms-auto mb-2 mb-lg-0 align-items-lg-center public-nav-links">
                        <li class="nav-item"><a class="nav-link {{ request()->routeIs('public.home') ? 'active' : '' }}" href="{{ route('public.home') }}">{{ __('public.layout.nav.home') }}</a></li>
                        <li class="nav-item"><a class="nav-link {{ request()->routeIs('public.rooms.*') ? 'active' : '' }}" href="{{ route('public.rooms.index') }}">{{ __('public.layout.nav.rooms') }}</a></li>
                        <li class="nav-item"><a class="nav-link {{ request()->routeIs('public.promotions.*') ? 'active' : '' }}" href="{{ route('public.promotions.index') }}">{{ __('public.layout.nav.promotions') }}</a></li>
                        <li class="nav-item"><a class="nav-link {{ request()->routeIs('public.contact') ? 'active' : '' }}" href="{{ route('public.contact') }}">{{ __('public.layout.nav.contact') }}</a></li>
                    </ul>
                    <div class="public-navbar-actions ms-lg-4">
                        <div class="nav-item">
                            <form method="GET" action="{{ url()->current() }}" class="public-language-form">
                                @foreach (request()->except('lang') as $queryKey => $queryValue)
                                    @if (is_array($queryValue))
                                        @foreach ($queryValue as $nestedValue)
                                            <input type="hidden" name="{{ $queryKey }}[]" value="{{ $nestedValue }}">
                                        @endforeach
                                    @else
                                        <input type="hidden" name="{{ $queryKey }}" value="{{ $queryValue }}">
                                    @endif
                                @endforeach
                                <label for="public-language-switcher" class="small text-muted">{{ __('public.layout.language') }}</label>
                                <select id="public-language-switcher" name="lang" class="form-select form-select-sm" onchange="this.form.submit()">
                                    @foreach ($supportedLocales as $localeCode)
                                        <option value="{{ $localeCode }}" @selected($currentLocale === $localeCode)>{{ __('public.locales.'.$localeCode) }}</option>
                                    @endforeach
                                </select>
                            </form>
                        </div>
                        <div class="public-navbar-cta-group">
                            <a class="btn btn-book-now" href="{{ route('public.booking.create') }}">
                                {{ __('public.layout.nav.book_now') }}
                            </a>
                            <a class="btn btn-booking-status" href="{{ route('public.customer-portal.search') }}">
                                {{ __('public.layout.nav.check_booking') }}
                            </a>
                        </div>
                    </div>
                </div>
            </nav>
        </div>
    </header>

    <main>
        @yield('content')
    </main>

    <footer class="public-footer">
        <div class="container">
            <div class="footer-premium-grid">
                <div class="footer-premium-panel footer-location-panel">
                    <div>
                        <span class="section-kicker footer-kicker">{{ __('public.layout.footer.location_kicker') }}</span>
                        <h3 class="footer-title">{{ __('public.layout.footer.location_title') }}</h3>
                    </div>
                    @if ($googleMapsEmbedUrl)
                        <div class="footer-map-frame">
                            <iframe src="{{ $googleMapsEmbedUrl }}" loading="lazy" referrerpolicy="no-referrer-when-downgrade" allowfullscreen></iframe>
                        </div>
                    @else
                        <p class="footer-copy mb-0">{{ trim(collect([$hotelSetting->address, $hotelSetting->city, $hotelSetting->country])->filter()->implode(', ')) ?: 'Potosi, Bolivia' }}</p>
                    @endif
                </div>
            </div>
            <div class="row g-4">
                <div class="col-lg-4">
                    <span class="section-kicker footer-kicker">{{ $hotelSetting->city ?: 'Potosi' }}, {{ $hotelSetting->country ?: 'Bolivia' }}</span>
                    <h3 class="footer-title">{{ $hotelSetting->hotel_name ?: 'Hostal Cerro Rico' }}</h3>
                    <p class="footer-copy mb-0">{{ $hotelSetting->description_short ?: 'Hospedaje comodo, calido y bien ubicado para descubrir Potosi con tranquilidad.' }}</p>
                </div>
                <div class="col-sm-6 col-lg-3">
                    <h4 class="footer-heading">{{ __('public.layout.footer.contact') }}</h4>
                    <ul class="footer-list">
                        <li>{{ $hotelSetting->address ?: 'Potosi, Bolivia' }}</li>
                        @if ($hotelSetting->phone)<li><a href="tel:{{ preg_replace('/\s+/', '', $hotelSetting->phone) }}">{{ $hotelSetting->phone }}</a></li>@endif
                        @foreach ($publicContactEmails->take(2) as $contactEmail)
                            <li><a href="{{ $contactEmail['mailto_url'] }}">{{ $contactEmail['label'] }}: {{ $contactEmail['email'] }}</a></li>
                        @endforeach
                        @if ($hotelSetting->website)<li><a href="{{ $hotelSetting->website }}" target="_blank" rel="noopener">{{ $hotelSetting->website }}</a></li>@endif
                    </ul>
                </div>
                <div class="col-sm-6 col-lg-2">
                    <h4 class="footer-heading">{{ __('public.layout.footer.navigation') }}</h4>
                    <ul class="footer-list">
                        <li><a href="{{ route('public.home') }}">{{ __('public.layout.nav.home') }}</a></li>
                        <li><a href="{{ route('public.rooms.index') }}">{{ __('public.layout.nav.rooms') }}</a></li>
                        <li><a href="{{ route('public.promotions.index') }}">{{ __('public.layout.nav.promotions') }}</a></li>
                        <li><a href="{{ route('public.contact') }}">{{ __('public.layout.nav.contact') }}</a></li>
                    </ul>
                </div>
                <div class="col-lg-3">
                    <h4 class="footer-heading">Siguenos en nuestras redes</h4>
                    <div class="footer-socials">
                        @forelse ($socialLinks as $socialLink)
                            <a href="{{ $socialLink['url'] }}" target="_blank" rel="noopener" aria-label="{{ $socialLink['label'] }}">
                                <i class="bi {{ $socialLink['icon'] }}"></i>
                                <span class="visually-hidden">{{ $socialLink['label'] }}</span>
                            </a>
                        @empty
                            <span class="text-muted small">{{ __('public.layout.footer.soon') }}</span>
                        @endforelse
                    </div>
                    @if ($whatsAppUrl)
                        <a class="btn btn-footer-whatsapp mt-3" href="{{ $whatsAppUrl }}" target="_blank" rel="noopener">
                            {{ __('public.layout.footer.whatsapp') }}
                        </a>
                    @endif
                </div>
            </div>
            <div class="footer-bottom">
                <span>&copy; {{ now()->year }} {{ $hotelSetting->hotel_name ?: 'Hostal Cerro Rico' }}. {{ __('public.layout.footer.rights') }}</span>
            </div>
        </div>
    </footer>

    @if ($whatsAppUrl)
        <a class="whatsapp-float" href="{{ $whatsAppUrl }}" target="_blank" rel="noopener" aria-label="{{ __('public.layout.whatsapp_aria') }}">
            <i class="bi bi-whatsapp"></i>
        </a>
    @endif
    @stack('scripts')
</body>
</html>
