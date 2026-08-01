@php
    $layoutFixed = config('adminlte.layout_fixed_sidebar');
    $fixedHeader = config('adminlte.layout_fixed_navbar');
    $fixedFooter = config('adminlte.layout_fixed_footer');
    $rtl = config('adminlte.layout_rtl', false);
    $sidebarBreakpoint = config('adminlte.sidebar_breakpoint', 'lg');
    $sidebarMini = config('adminlte.sidebar_mini');
    $sidebarCollapse = config('adminlte.sidebar_collapse');

    $bodyClasses = collect([
        $layoutFixed ? 'layout-fixed' : null,
        $fixedHeader ? 'fixed-header' : null,
        $fixedFooter ? 'fixed-footer' : null,
        'sidebar-expand-'.$sidebarBreakpoint,
        $sidebarMini ? 'sidebar-mini' : null,
        $sidebarCollapse ? 'sidebar-collapse' : null,
        'bg-body-tertiary',
        config('adminlte.classes_body'),
    ])->filter()->implode(' ');

    $titlePrefix = config('adminlte.title_prefix', '');
    $titlePostfix = config('adminlte.title_postfix', '');
    $title = trim($titlePrefix.' '.($title ?? config('adminlte.title', 'AdminLTE 4')).' '.$titlePostfix);
    $hotelSetting = \App\Models\HotelSetting::current();
    $faviconUrl = $hotelSetting->favicon ? asset('storage/'.$hotelSetting->favicon) : null;
    $faviconExtension = strtolower(pathinfo((string) $hotelSetting->favicon, PATHINFO_EXTENSION));
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}"
      dir="{{ $rtl ? 'rtl' : 'ltr' }}"
      @isset($darkMode) data-bs-theme="dark" @endisset>
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

    <script>
        (() => {
            try {
                const storedTheme = localStorage.getItem('adminlte-color-mode') || 'auto';
                const resolvedTheme = storedTheme === 'auto'
                    ? (window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light')
                    : storedTheme;

                document.documentElement.setAttribute('data-bs-theme', resolvedTheme);
                document.documentElement.setAttribute('data-theme-preference', storedTheme);
            } catch (error) {
                document.documentElement.setAttribute('data-bs-theme', 'light');
                document.documentElement.setAttribute('data-theme-preference', 'auto');
            }
        })();
    </script>

    @hasSection('adminlte_css')
        @yield('adminlte_css')
    @endif

    {{-- Compiled AdminLTE + Bootstrap from your Vite pipeline --}}
    @vite(['resources/css/adminlte.css', 'resources/js/adminlte.js', 'resources/js/app.js'])

    @if ($rtl)
        {{-- AdminLTE ships a prebuilt RTL stylesheet; published by adminlte:install. --}}
        <link rel="stylesheet" href="{{ asset('vendor/adminlte/css/adminlte.rtl.min.css') }}">
    @endif

    @stack('css')
    @yield('css')
    @pluginStyles
</head>
<body class="{{ $bodyClasses }}">
    @include('adminlte::partials.preloader')

    @include('adminlte::partials.impersonation-banner')

    <div class="app-wrapper">
        @include('adminlte::partials.navbar')
        @include('adminlte::partials.sidebar')

        <main class="app-main">
            @hasSection('content_header')
                <div class="app-content-header {{ config('adminlte.classes_content_header') }}">
                    <div class="container-fluid">
                        @yield('content_header')
                    </div>
                </div>
            @endif

            <div class="app-content {{ config('adminlte.classes_content') }}">
                <div class="container-fluid">
                    @yield('content')
                </div>
            </div>
        </main>

        @include('adminlte::partials.footer')
        @include('adminlte::partials.control-sidebar')
    </div>

    @pluginScripts
    @stack('js')
    @yield('js')
</body>
</html>
