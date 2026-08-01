@php
    $hotelSetting = \App\Models\HotelSetting::current();
    $title = trim(($title ?? config('adminlte.title', 'AdminLTE 4')));
    $authType = $authType ?? 'login'; // login | register
    $logoImage = $hotelSetting->logo ? asset('storage/'.$hotelSetting->logo) : null;
    $faviconUrl = $hotelSetting->favicon ? asset('storage/'.$hotelSetting->favicon) : null;
    $faviconExtension = strtolower(pathinfo((string) $hotelSetting->favicon, PATHINFO_EXTENSION));
    $themeCssVariables = collect($hotelSetting->publicThemeCssVariables())
        ->map(fn ($value, $property) => $property.': '.$value)
        ->implode('; ');
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title }}</title>
    @if ($faviconUrl)
        <link rel="icon" href="{{ $faviconUrl }}" sizes="any" @if ($faviconExtension === 'ico') type="image/x-icon" @endif>
        <link rel="shortcut icon" href="{{ $faviconUrl }}" @if ($faviconExtension === 'ico') type="image/x-icon" @endif>
        @if (in_array($faviconExtension, ['png', 'jpg', 'jpeg', 'webp'], true))
            <link rel="apple-touch-icon" href="{{ $faviconUrl }}">
        @endif
    @endif
    {{-- Bootstrap Icons ship via the Vite bundle (imported in resources/css/adminlte.css) --}}
    @vite(['resources/css/adminlte.css', 'resources/js/adminlte.js'])
    @stack('css')
    <style>
        body.hotel-auth-page {
            min-height: 100vh;
            margin: 0;
            color: var(--public-text);
            background:
                radial-gradient(circle at 14% 18%, rgba(var(--public-secondary-rgb), .18), transparent 28%),
                radial-gradient(circle at 88% 10%, rgba(var(--public-accent-rgb), .14), transparent 24%),
                linear-gradient(135deg, var(--public-primary-dark), var(--public-primary));
        }

        .hotel-auth-shell {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: clamp(1rem, 3vw, 2.2rem);
        }

        .hotel-auth-panel {
            overflow: hidden;
            border-radius: 2rem;
            box-shadow: 0 34px 90px rgba(0, 0, 0, .24);
        }

        .hotel-auth-brand {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: .9rem;
            margin-bottom: 1.35rem;
            text-align: left;
        }

        .hotel-auth-logo {
            width: 4.4rem;
            height: 4.4rem;
            border-radius: 1.35rem;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
            color: var(--public-primary);
            background: rgba(var(--public-card-rgb), .94);
            font-weight: 900;
            box-shadow: 0 18px 38px rgba(var(--public-primary-rgb), .16);
        }

        .hotel-auth-logo img {
            width: 100%;
            height: 100%;
            object-fit: contain;
            padding: .18rem;
        }

        .hotel-auth-brand strong,
        .hotel-auth-card-header h2 {
            font-family: Georgia, 'Times New Roman', serif;
        }

        .hotel-auth-brand strong {
            display: block;
            line-height: 1;
            color: var(--public-text);
            font-size: 1.35rem;
        }

        .hotel-auth-brand small {
            color: var(--public-muted);
            font-size: .75rem;
            letter-spacing: .08em;
            text-transform: uppercase;
        }

        .hotel-auth-panel {
            display: flex;
            align-items: center;
            justify-content: center;
            width: min(100%, 520px);
            padding: clamp(1.2rem, 4vw, 3rem);
            background:
                radial-gradient(circle at top right, rgba(var(--public-secondary-rgb), .12), transparent 28%),
                var(--public-card);
        }

        .hotel-auth-card {
            width: 100%;
        }

        .hotel-auth-card-header {
            margin-bottom: 1.5rem;
        }

        .hotel-auth-card-header span {
            display: inline-flex;
            margin-bottom: .5rem;
            color: var(--public-secondary-dark);
            font-size: .74rem;
            font-weight: 900;
            letter-spacing: .16em;
            text-transform: uppercase;
        }

        .hotel-auth-card-header h2 {
            margin: 0;
            color: var(--public-text);
            font-size: clamp(2rem, 4vw, 3rem);
            font-family: Georgia, 'Times New Roman', serif;
            letter-spacing: -.04em;
        }

        .hotel-auth-card-header p {
            margin: .55rem 0 0;
            color: var(--public-muted);
        }

        .hotel-auth-card .form-control,
        .hotel-auth-card .input-group-text {
            min-height: 3.35rem;
            border-color: rgba(var(--public-primary-rgb), .12);
            background: rgba(var(--public-card-rgb), .82);
        }

        .hotel-auth-card .form-control {
            border-radius: 1rem 0 0 1rem;
            color: var(--public-text);
        }

        .hotel-auth-card .input-group-text {
            border-radius: 0 1rem 1rem 0;
            color: var(--public-primary);
        }

        .hotel-auth-card .form-control:focus {
            border-color: rgba(var(--public-primary-rgb), .38);
            box-shadow: 0 0 0 .2rem rgba(var(--public-primary-rgb), .08);
        }

        .hotel-auth-submit {
            min-height: 3.35rem;
            border: 0;
            border-radius: 1rem;
            color: #fff;
            font-weight: 900;
            letter-spacing: .06em;
            text-transform: uppercase;
            background: linear-gradient(135deg, var(--public-primary), var(--public-primary-strong));
            box-shadow: 0 18px 38px rgba(var(--public-primary-rgb), .26);
        }

        .hotel-auth-submit:hover,
        .hotel-auth-submit:focus {
            color: #fff;
            background: linear-gradient(135deg, var(--public-primary-dark), var(--public-primary));
            transform: translateY(-1px);
        }

        .hotel-auth-link {
            color: var(--public-primary);
            font-weight: 800;
        }

        .hotel-auth-link:hover {
            color: var(--public-primary-dark);
        }

        .hotel-auth-support {
            margin-top: 1.25rem;
            padding: 1rem;
            border: 1px solid rgba(var(--public-primary-rgb), .1);
            border-radius: 1.15rem;
            color: var(--public-muted);
            background: rgba(var(--public-primary-rgb), .04);
            font-size: .86rem;
        }

        @media (max-width: 575.98px) {
            .hotel-auth-shell {
                padding: .75rem;
            }

            .hotel-auth-panel {
                border-radius: 1.35rem;
            }
        }
    </style>
</head>
<body class="{{ $authType }}-page hotel-auth-page" style="{{ $themeCssVariables }}">
    <main class="hotel-auth-shell">
        <section class="hotel-auth-panel">
            <div class="hotel-auth-card">
                <a href="{{ url('/') }}" class="hotel-auth-brand" aria-label="{{ $hotelSetting->hotel_name ?: config('adminlte.title') }}">
                    <span class="hotel-auth-logo">
                        @if ($logoImage)
                            <img src="{{ $logoImage }}" alt="{{ $hotelSetting->hotel_name ?: config('adminlte.title') }}">
                        @else
                            {{ \Illuminate\Support\Str::upper(\Illuminate\Support\Str::substr($hotelSetting->hotel_name ?: config('adminlte.title', 'H'), 0, 1)) }}
                        @endif
                    </span>
                    <span>
                        <strong>{{ $hotelSetting->hotel_name ?: config('adminlte.title', 'Hostal Cerro Rico') }}</strong>
                        <small>{{ trim(collect([$hotelSetting->city, $hotelSetting->country])->filter()->implode(', ')) ?: 'Sistema hotelero' }}</small>
                    </span>
                </a>
                @yield('auth_body')
            </div>
        </section>
    </main>
    @stack('js')
</body>
</html>
