@extends('adminlte::page')

@section('title', 'Configuracion del Hotel')

@php
    $imagePreview = static fn (?string $path): ?string => $path ? asset('storage/' . $path) : null;
    $mediaStatus = static fn (mixed $value): array => filled($value)
        ? ['label' => 'Cargado', 'class' => 'bg-success-subtle text-success-emphasis']
        : ['label' => 'No configurado', 'class' => 'bg-secondary-subtle text-secondary-emphasis'];
    $configuredMedia = collect([
        $hotelSetting->logo,
        $hotelSetting->favicon,
        $hotelSetting->cover_image,
        $hotelSetting->hero_video,
        $hotelSetting->hero_video_url,
        $hotelSetting->mobile_hero_image,
        $hotelSetting->digital_wallet_qr_image,
        $hotelSetting->bank_qr_image,
    ])->filter(fn ($value) => filled($value))->count();
    $configuredSocialLinks = collect(old('social_links', $hotelSetting->social_links ?? $hotelSetting->publicSocialLinks()))
        ->filter(fn ($link) => is_array($link))
        ->values();
    $configuredContactPeople = collect(old('contact_people', $hotelSetting->contact_people ?? $hotelSetting->publicContactPeople()))
        ->filter(fn ($person) => is_array($person))
        ->values();
    $configuredContactEmails = collect(old('contact_emails', $hotelSetting->contact_emails ?? $hotelSetting->publicContactEmails()))
        ->filter(fn ($contact) => is_array($contact))
        ->values();
    $configuredCurrencies = collect(old('enabled_currencies', $hotelSetting->currencyDefinitions()))
        ->filter(fn ($currency) => is_array($currency))
        ->values();
    $socialNetworkOptions = collect([
        ['label' => 'Facebook', 'icon' => 'bi-facebook'],
        ['label' => 'Instagram', 'icon' => 'bi-instagram'],
        ['label' => 'TikTok', 'icon' => 'bi-tiktok'],
        ['label' => 'YouTube', 'icon' => 'bi-youtube'],
        ['label' => 'WhatsApp', 'icon' => 'bi-whatsapp'],
        ['label' => 'LinkedIn', 'icon' => 'bi-linkedin'],
        ['label' => 'X / Twitter', 'icon' => 'bi-twitter-x'],
        ['label' => 'Reddit', 'icon' => 'bi-reddit'],
        ['label' => 'Airbnb', 'icon' => 'bi-house-heart'],
        ['label' => 'Booking', 'icon' => 'bi-calendar-check'],
        ['label' => 'Tripadvisor', 'icon' => 'bi-compass'],
        ['label' => 'Google Maps', 'icon' => 'bi-geo-alt'],
        ['label' => 'Google Business', 'icon' => 'bi-google'],
        ['label' => 'Pinterest', 'icon' => 'bi-pinterest'],
        ['label' => 'Threads', 'icon' => 'bi-threads'],
        ['label' => 'Telegram', 'icon' => 'bi-telegram'],
        ['label' => 'Sitio web', 'icon' => 'bi-globe2'],
        ['label' => 'Agoda', 'icon' => 'bi-building-check'],
        ['label' => 'Expedia', 'icon' => 'bi-airplane'],
        ['label' => 'Otro enlace', 'icon' => 'bi-link-45deg'],
    ]);
@endphp

@section('content_header')
    <div class="settings-hero">
        <div class="settings-hero-copy">
            <span class="settings-eyebrow">Panel maestro</span>
            <h1 class="m-0">Configuracion del Hotel</h1>
            <p class="mb-0">Administra identidad, portada publica, pagos, monedas, redes sociales y datos de contacto desde una sola pantalla.</p>
        </div>
    </div>
@stop

@section('content')
    @if (session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Cerrar"></button>
        </div>
    @endif

    <form method="POST" action="{{ route('adminlte.settings.update') }}" enctype="multipart/form-data" class="settings-shell">
        @csrf
        @method('PUT')

        <div class="row g-3 mb-4">
            <div class="col-md-6 col-xl-3">
                <div class="settings-stat-card">
                    <i class="bi bi-building"></i>
                    <span>Hotel</span>
                    <strong>{{ filled($hotelSetting->hotel_name) ? 'Listo' : 'Pendiente' }}</strong>
                    <small>{{ $hotelSetting->hotel_name ?: 'Sin nombre comercial' }}</small>
                </div>
            </div>
            <div class="col-md-6 col-xl-3">
                <div class="settings-stat-card is-media">
                    <i class="bi bi-images"></i>
                    <span>Medios</span>
                    <strong>{{ $configuredMedia }}/7</strong>
                    <small>Logo, portada, video, movil y QR</small>
                </div>
            </div>
            <div class="col-md-6 col-xl-3">
                <div class="settings-stat-card is-money">
                    <i class="bi bi-currency-exchange"></i>
                    <span>Moneda base</span>
                    <strong>{{ $hotelSetting->baseCurrency() }}</strong>
                    <small>Pagos y reportes principales</small>
                </div>
            </div>
            <div class="col-md-6 col-xl-3">
                <div class="settings-stat-card is-time">
                    <i class="bi bi-cash-stack"></i>
                    <span>Monedas activas</span>
                    <strong>{{ $configuredCurrencies->count() }}</strong>
                    <small>Disponibles para caja, pagos y reportes</small>
                </div>
            </div>
        </div>

        <div class="settings-save-bar">
            <div>
                <strong>Configuracion general del hotel</strong>
                <span>Los cambios impactan en reservas, pagos, comprobantes y frontend publico.</span>
            </div>
            <button type="submit" class="btn settings-save-btn">
                <i class="bi bi-save" aria-hidden="true"></i> Guardar configuracion
            </button>
        </div>

        <div class="row g-3">
        <div class="col-12">
            <x-adminlte-card title="Identidad del hotel" icon="bi bi-building">
                <div class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label" for="hotel_name">Nombre comercial</label>
                        <input type="text" id="hotel_name" name="hotel_name" class="form-control @error('hotel_name') is-invalid @enderror" value="{{ old('hotel_name', $hotelSetting->hotel_name) }}" required>
                        @error('hotel_name')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="col-md-4">
                        <label class="form-label" for="legal_name">Razon social</label>
                        <input type="text" id="legal_name" name="legal_name" class="form-control @error('legal_name') is-invalid @enderror" value="{{ old('legal_name', $hotelSetting->legal_name) }}">
                        @error('legal_name')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="col-md-4">
                        <label class="form-label" for="slogan">Eslogan</label>
                        <input type="text" id="slogan" name="slogan" class="form-control @error('slogan') is-invalid @enderror" value="{{ old('slogan', $hotelSetting->slogan) }}">
                        @error('slogan')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-md-4">
                        <label class="form-label" for="logo">Logo</label>
                        <input type="file" id="logo" name="logo" class="form-control @error('logo') is-invalid @enderror" accept=".jpg,.jpeg,.png,.webp">
                        @error('logo')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                        @php($logoStatus = $mediaStatus($hotelSetting->logo))
                        <div class="media-config-card mt-2" data-media-card data-media-shape="circle">
                            <div class="d-flex align-items-center justify-content-between gap-2 mb-2">
                                <strong>Logo actual</strong>
                                <span class="badge rounded-pill {{ $logoStatus['class'] }}" data-media-status>{{ $logoStatus['label'] }}</span>
                            </div>
                            <div class="media-preview media-preview--circle text-center">
                                @if ($imagePreview($hotelSetting->logo))
                                    <img src="{{ $imagePreview($hotelSetting->logo) }}" alt="Logo actual" data-preview-image>
                                @else
                                    <span class="text-muted" data-preview-empty>Sin imagen cargada</span>
                                @endif
                            </div>
                            <input type="hidden" name="clear_logo" value="0" data-clear-input>
                            <div class="d-flex justify-content-between align-items-center gap-2 mt-3">
                                <small class="text-muted">Sugerencia: usa una imagen circular o centrada, ideal 512x512 px en PNG/WebP.</small>
                                <button type="button" class="btn btn-outline-danger btn-sm" data-clear-trigger @disabled(blank($hotelSetting->logo))>Quitar</button>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label" for="favicon">Favicon</label>
                        <input type="file" id="favicon" name="favicon" class="form-control @error('favicon') is-invalid @enderror" accept=".ico,.png,.jpg,.jpeg,.webp">
                        @error('favicon')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                        @php($faviconStatus = $mediaStatus($hotelSetting->favicon))
                        <div class="media-config-card mt-2" data-media-card data-media-shape="circle">
                            <div class="d-flex align-items-center justify-content-between gap-2 mb-2">
                                <strong>Favicon actual</strong>
                                <span class="badge rounded-pill {{ $faviconStatus['class'] }}" data-media-status>{{ $faviconStatus['label'] }}</span>
                            </div>
                            <div class="media-preview media-preview--circle media-preview--favicon text-center">
                                @if ($imagePreview($hotelSetting->favicon))
                                    <img src="{{ $imagePreview($hotelSetting->favicon) }}" alt="Favicon actual" data-preview-image>
                                @else
                                    <span class="text-muted" data-preview-empty>Sin imagen cargada</span>
                                @endif
                            </div>
                            <input type="hidden" name="clear_favicon" value="0" data-clear-input>
                            <div class="d-flex justify-content-between align-items-center gap-2 mt-3">
                                <small class="text-muted">Sugerencia: icono circular o cuadrado centrado, ideal 512x512 px.</small>
                                <button type="button" class="btn btn-outline-danger btn-sm" data-clear-trigger @disabled(blank($hotelSetting->favicon))>Quitar</button>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label" for="cover_image">Imagen de portada</label>
                        <input type="file" id="cover_image" name="cover_image" class="form-control @error('cover_image') is-invalid @enderror" accept=".jpg,.jpeg,.png,.webp">
                        @error('cover_image')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                        @php($coverStatus = $mediaStatus($hotelSetting->cover_image))
                        <div class="media-config-card mt-2" data-media-card>
                            <div class="d-flex align-items-center justify-content-between gap-2 mb-2">
                                <strong>Portada actual</strong>
                                <span class="badge rounded-pill {{ $coverStatus['class'] }}" data-media-status>{{ $coverStatus['label'] }}</span>
                            </div>
                            <div class="media-preview text-center">
                                @if ($imagePreview($hotelSetting->cover_image))
                                    <img src="{{ $imagePreview($hotelSetting->cover_image) }}" alt="Portada actual" class="img-fluid rounded" style="max-height: 120px;" data-preview-image>
                                @else
                                    <span class="text-muted" data-preview-empty>Sin imagen cargada</span>
                                @endif
                            </div>
                            <input type="hidden" name="clear_cover_image" value="0" data-clear-input>
                            <div class="d-flex justify-content-between align-items-center gap-2 mt-3">
                                <small class="text-muted">Se usa como respaldo visual cuando no hay video.</small>
                                <button type="button" class="btn btn-outline-danger btn-sm" data-clear-trigger @disabled(blank($hotelSetting->cover_image))>Quitar</button>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label" for="hero_video">Video del header</label>
                        <input type="file" id="hero_video" name="hero_video" class="form-control @error('hero_video') is-invalid @enderror" accept=".mp4,.webm,.mov,video/mp4,video/webm,video/quicktime">
                        @error('hero_video')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                        <small class="text-muted d-block mt-1">Opcional. Si cargas un video, el frontend lo usara en el hero por encima de la imagen de portada.</small>
                        @php($videoStatus = $mediaStatus($hotelSetting->hero_video))
                        <div class="media-config-card mt-2" data-media-card>
                            <div class="d-flex align-items-center justify-content-between gap-2 mb-2">
                                <strong>Video actual</strong>
                                <span class="badge rounded-pill {{ $videoStatus['class'] }}" data-media-status>{{ $videoStatus['label'] }}</span>
                            </div>
                            <div class="media-preview text-center">
                                @if ($hotelSetting->hero_video)
                                    <video class="img-fluid rounded" style="max-height: 180px;" controls muted preload="metadata" data-preview-video>
                                        <source src="{{ asset('storage/' . $hotelSetting->hero_video) }}">
                                        Tu navegador no soporta video.
                                    </video>
                                @else
                                    <span class="text-muted" data-preview-empty>Sin video cargado</span>
                                @endif
                            </div>
                            <input type="hidden" name="clear_hero_video" value="0" data-clear-input>
                            <div class="d-flex justify-content-between align-items-center gap-2 mt-3">
                                <small class="text-muted">Ideal para una primera impresion mas fuerte en la portada.</small>
                                <button type="button" class="btn btn-outline-danger btn-sm" data-clear-trigger @disabled(blank($hotelSetting->hero_video))>Quitar</button>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label" for="hero_video_url">Enlace de YouTube para el header</label>
                        <input type="url" id="hero_video_url" name="hero_video_url" class="form-control @error('hero_video_url') is-invalid @enderror" value="{{ old('hero_video_url', $hotelSetting->hero_video_url) }}" placeholder="https://www.youtube.com/watch?v=...">
                        @error('hero_video_url')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                        <small class="text-muted d-block mt-1">Opcional. Si no subes video, el frontend puede usar un enlace de YouTube como fondo del hero.</small>
                        @php($youtubeStatus = $mediaStatus($hotelSetting->hero_video_url))
                        <div class="media-config-card mt-2" data-media-card>
                            <div class="d-flex align-items-center justify-content-between gap-2 mb-2">
                                <strong>Enlace actual</strong>
                                <span class="badge rounded-pill {{ $youtubeStatus['class'] }}" data-media-status>{{ $youtubeStatus['label'] }}</span>
                            </div>
                            <div class="media-preview">
                                @if ($hotelSetting->youtubeHeroEmbedUrl())
                                    <div class="ratio ratio-16x9 rounded overflow-hidden" data-preview-embed>
                                        <iframe src="{{ $hotelSetting->youtubeHeroEmbedUrl() }}" title="Preview video YouTube" allow="autoplay; encrypted-media; picture-in-picture" referrerpolicy="strict-origin-when-cross-origin"></iframe>
                                    </div>
                                @else
                                    <div class="text-center text-muted" data-preview-empty>Sin enlace de YouTube configurado</div>
                                @endif
                            </div>
                            <input type="hidden" name="clear_hero_video_url" value="0" data-clear-input>
                            <div class="d-flex justify-content-between align-items-center gap-2 mt-3">
                                <small class="text-muted">Usalo si prefieres no subir un archivo pesado al sistema.</small>
                                <button type="button" class="btn btn-outline-danger btn-sm" data-clear-trigger @disabled(blank($hotelSetting->hero_video_url))>Quitar</button>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label" for="mobile_hero_image">Imagen ligera para celulares</label>
                        <input type="file" id="mobile_hero_image" name="mobile_hero_image" class="form-control @error('mobile_hero_image') is-invalid @enderror" accept=".jpg,.jpeg,.png,.webp">
                        @error('mobile_hero_image')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                        <small class="text-muted d-block mt-1">Opcional. Si la cargas, en telefonos se mostrara esta imagen en vez del video del header para que la pagina sea mas liviana.</small>
                        @php($mobileHeroStatus = $mediaStatus($hotelSetting->mobile_hero_image))
                        <div class="media-config-card mt-2" data-media-card>
                            <div class="d-flex align-items-center justify-content-between gap-2 mb-2">
                                <strong>Imagen movil actual</strong>
                                <span class="badge rounded-pill {{ $mobileHeroStatus['class'] }}" data-media-status>{{ $mobileHeroStatus['label'] }}</span>
                            </div>
                            <div class="media-preview text-center">
                                @if ($imagePreview($hotelSetting->mobile_hero_image))
                                    <img src="{{ $imagePreview($hotelSetting->mobile_hero_image) }}" alt="Imagen movil actual" class="img-fluid rounded" style="max-height: 180px;" data-preview-image>
                                @else
                                    <span class="text-muted" data-preview-empty>Sin imagen movil configurada</span>
                                @endif
                            </div>
                            <input type="hidden" name="clear_mobile_hero_image" value="0" data-clear-input>
                            <div class="d-flex justify-content-between align-items-center gap-2 mt-3">
                                <small class="text-muted">Recomendado para mejorar velocidad y consumo de datos en celulares.</small>
                                <button type="button" class="btn btn-outline-danger btn-sm" data-clear-trigger @disabled(blank($hotelSetting->mobile_hero_image))>Quitar</button>
                            </div>
                        </div>
                    </div>
                </div>
            </x-adminlte-card>
        </div>

        <div class="col-12">
            <x-adminlte-card title="Tema visual del frontend" icon="bi bi-palette">
                <div class="row g-3 align-items-stretch" data-theme-editor>
                    <div class="col-xl-7">
                        <div class="theme-editor-section">
                            <div>
                                <span class="theme-editor-kicker">Identidad y conversion</span>
                                <h5>Colores que atraen reservas</h5>
                                <p>Controlan el hero, botones importantes, etiquetas premium y llamados a la accion.</p>
                            </div>
                            <div class="row g-3">
                                <div class="col-md-4">
                                    <label class="form-label" for="theme_primary_color">Principal</label>
                                    <div class="theme-color-control">
                                        <input type="color" id="theme_primary_color_picker" class="form-control form-control-color" value="{{ old('theme_primary_color', $hotelSetting->themePrimaryColor()) }}" data-theme-picker="theme_primary_color">
                                        <input type="text" id="theme_primary_color" name="theme_primary_color" class="form-control @error('theme_primary_color') is-invalid @enderror" value="{{ old('theme_primary_color', $hotelSetting->themePrimaryColor()) }}" maxlength="7" data-theme-color="primary">
                                    </div>
                                    @error('theme_primary_color')
                                        <div class="invalid-feedback d-block">{{ $message }}</div>
                                    @enderror
                                    <small class="theme-use-note">Navbar, hero, boton Reservar, overlays y fondos elegantes.</small>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label" for="theme_secondary_color">Secundario</label>
                                    <div class="theme-color-control">
                                        <input type="color" id="theme_secondary_color_picker" class="form-control form-control-color" value="{{ old('theme_secondary_color', $hotelSetting->themeSecondaryColor()) }}" data-theme-picker="theme_secondary_color">
                                        <input type="text" id="theme_secondary_color" name="theme_secondary_color" class="form-control @error('theme_secondary_color') is-invalid @enderror" value="{{ old('theme_secondary_color', $hotelSetting->themeSecondaryColor()) }}" maxlength="7" data-theme-color="secondary">
                                    </div>
                                    @error('theme_secondary_color')
                                        <div class="invalid-feedback d-block">{{ $message }}</div>
                                    @enderror
                                    <small class="theme-use-note">Detalles dorados, badges, lineas decorativas y luces suaves.</small>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label" for="theme_accent_color">Acento</label>
                                    <div class="theme-color-control">
                                        <input type="color" id="theme_accent_color_picker" class="form-control form-control-color" value="{{ old('theme_accent_color', $hotelSetting->themeAccentColor()) }}" data-theme-picker="theme_accent_color">
                                        <input type="text" id="theme_accent_color" name="theme_accent_color" class="form-control @error('theme_accent_color') is-invalid @enderror" value="{{ old('theme_accent_color', $hotelSetting->themeAccentColor()) }}" maxlength="7" data-theme-color="accent">
                                    </div>
                                    @error('theme_accent_color')
                                        <div class="invalid-feedback d-block">{{ $message }}</div>
                                    @enderror
                                    <small class="theme-use-note">Consultar reserva, estados destacados, hover y contraste comercial.</small>
                                </div>
                            </div>
                        </div>

                        <div class="theme-editor-section mt-3">
                            <div>
                                <span class="theme-editor-kicker">Base visual y lectura</span>
                                <h5>Colores que sostienen toda la experiencia</h5>
                                <p>Definen fondo, tarjetas y textos para que la pagina se vea elegante y facil de leer.</p>
                            </div>
                            <div class="row g-3">
                                <div class="col-md-3 col-sm-6">
                                    <label class="form-label" for="theme_background_color">Fondo</label>
                                    <div class="theme-color-control theme-color-control--compact">
                                        <input type="color" id="theme_background_color_picker" class="form-control form-control-color" value="{{ old('theme_background_color', $hotelSetting->themeBackgroundColor()) }}" data-theme-picker="theme_background_color">
                                        <input type="text" id="theme_background_color" name="theme_background_color" class="form-control @error('theme_background_color') is-invalid @enderror" value="{{ old('theme_background_color', $hotelSetting->themeBackgroundColor()) }}" maxlength="7" data-theme-color="background">
                                    </div>
                                    @error('theme_background_color')
                                        <div class="invalid-feedback d-block">{{ $message }}</div>
                                    @enderror
                                    <small class="theme-use-note">Fondo general de paginas publicas.</small>
                                </div>
                                <div class="col-md-3 col-sm-6">
                                    <label class="form-label" for="theme_surface_color">Tarjetas</label>
                                    <div class="theme-color-control theme-color-control--compact">
                                        <input type="color" id="theme_surface_color_picker" class="form-control form-control-color" value="{{ old('theme_surface_color', $hotelSetting->themeSurfaceColor()) }}" data-theme-picker="theme_surface_color">
                                        <input type="text" id="theme_surface_color" name="theme_surface_color" class="form-control @error('theme_surface_color') is-invalid @enderror" value="{{ old('theme_surface_color', $hotelSetting->themeSurfaceColor()) }}" maxlength="7" data-theme-color="surface">
                                    </div>
                                    @error('theme_surface_color')
                                        <div class="invalid-feedback d-block">{{ $message }}</div>
                                    @enderror
                                    <small class="theme-use-note">Cards, formularios y bloques flotantes.</small>
                                </div>
                                <div class="col-md-3 col-sm-6">
                                    <label class="form-label" for="theme_text_color">Texto</label>
                                    <div class="theme-color-control theme-color-control--compact">
                                        <input type="color" id="theme_text_color_picker" class="form-control form-control-color" value="{{ old('theme_text_color', $hotelSetting->themeTextColor()) }}" data-theme-picker="theme_text_color">
                                        <input type="text" id="theme_text_color" name="theme_text_color" class="form-control @error('theme_text_color') is-invalid @enderror" value="{{ old('theme_text_color', $hotelSetting->themeTextColor()) }}" maxlength="7" data-theme-color="text">
                                    </div>
                                    @error('theme_text_color')
                                        <div class="invalid-feedback d-block">{{ $message }}</div>
                                    @enderror
                                    <small class="theme-use-note">Titulos, menu al hacer scroll y textos principales.</small>
                                </div>
                                <div class="col-md-3 col-sm-6">
                                    <label class="form-label" for="theme_muted_color">Texto suave</label>
                                    <div class="theme-color-control theme-color-control--compact">
                                        <input type="color" id="theme_muted_color_picker" class="form-control form-control-color" value="{{ old('theme_muted_color', $hotelSetting->themeMutedColor()) }}" data-theme-picker="theme_muted_color">
                                        <input type="text" id="theme_muted_color" name="theme_muted_color" class="form-control @error('theme_muted_color') is-invalid @enderror" value="{{ old('theme_muted_color', $hotelSetting->themeMutedColor()) }}" maxlength="7" data-theme-color="muted">
                                    </div>
                                    @error('theme_muted_color')
                                        <div class="invalid-feedback d-block">{{ $message }}</div>
                                    @enderror
                                    <small class="theme-use-note">Descripciones, ayudas y textos secundarios.</small>
                                </div>
                            </div>
                        </div>

                        <div class="theme-usage-grid mt-3">
                            <div><span style="background: {{ old('theme_primary_color', $hotelSetting->themePrimaryColor()) }}"></span><strong>Principal</strong><small>Reservar ahora, navbar, hero.</small></div>
                            <div><span style="background: {{ old('theme_secondary_color', $hotelSetting->themeSecondaryColor()) }}"></span><strong>Secundario</strong><small>Lujo, detalles y separadores.</small></div>
                            <div><span style="background: {{ old('theme_accent_color', $hotelSetting->themeAccentColor()) }}"></span><strong>Acento</strong><small>Consultar reserva y estados.</small></div>
                            <div><span style="background: {{ old('theme_surface_color', $hotelSetting->themeSurfaceColor()) }}"></span><strong>Tarjetas</strong><small>Formularios, cards y paneles.</small></div>
                        </div>

                        <div class="alert alert-info border-0 mt-3 mb-0">
                            <strong>Impacto:</strong> esta paleta se refleja en pagina principal, habitaciones, promociones, contacto, reserva y consulta de reserva del cliente.
                        </div>
                    </div>
                    <div class="col-xl-5">
                        <div class="theme-preview-card" data-theme-preview style="--preview-primary: {{ old('theme_primary_color', $hotelSetting->themePrimaryColor()) }}; --preview-secondary: {{ old('theme_secondary_color', $hotelSetting->themeSecondaryColor()) }}; --preview-accent: {{ old('theme_accent_color', $hotelSetting->themeAccentColor()) }}; --preview-bg: {{ old('theme_background_color', $hotelSetting->themeBackgroundColor()) }}; --preview-surface: {{ old('theme_surface_color', $hotelSetting->themeSurfaceColor()) }}; --preview-text: {{ old('theme_text_color', $hotelSetting->themeTextColor()) }}; --preview-muted: {{ old('theme_muted_color', $hotelSetting->themeMutedColor()) }};">
                            <div class="theme-preview-card__nav">
                                <strong>{{ \Illuminate\Support\Str::limit($hotelSetting->hotel_name ?: 'Hostal Cerro Rico', 18) }}</strong>
                                <span>Inicio</span>
                                <span>Habitaciones</span>
                                <em>Reservar</em>
                            </div>
                            <div class="theme-preview-card__body">
                                <small>Vista previa</small>
                                <strong>{{ $hotelSetting->hotel_name ?: 'Hostal Cerro Rico' }}</strong>
                                <p>Experiencia publica personalizada con los colores del hotel.</p>
                                <div class="theme-preview-card__actions">
                                    <span>Reservar ahora</span>
                                    <span>Consultar reserva</span>
                                </div>
                                <div class="theme-preview-room">
                                    <div></div>
                                    <section>
                                        <small>Habitacion destacada</small>
                                        <strong>Suite premium</strong>
                                        <p>Tarjeta, texto y fondo tambien cambian con tu paleta.</p>
                                    </section>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </x-adminlte-card>
        </div>

        <div class="col-lg-6">
            <x-adminlte-card title="Descripcion" icon="bi bi-card-text" class="h-100">
                <div class="row g-3">
                    <div class="col-12">
                        <label class="form-label" for="description_short">Descripcion corta</label>
                        <textarea id="description_short" name="description_short" rows="3" class="form-control @error('description_short') is-invalid @enderror">{{ old('description_short', $hotelSetting->description_short) }}</textarea>
                        @error('description_short')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="col-12">
                        <label class="form-label" for="description_long">Descripcion larga</label>
                        <textarea id="description_long" name="description_long" rows="8" class="form-control @error('description_long') is-invalid @enderror">{{ old('description_long', $hotelSetting->description_long) }}</textarea>
                        @error('description_long')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
            </x-adminlte-card>
        </div>

        <div class="col-lg-6">
            <x-adminlte-card title="Contacto" icon="bi bi-address-book" class="h-100">
                <div class="row g-3">
                    <div class="col-12">
                        <label class="form-label" for="address">Direccion</label>
                        <input type="text" id="address" name="address" class="form-control @error('address') is-invalid @enderror" value="{{ old('address', $hotelSetting->address) }}">
                        @error('address')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="col-md-4">
                        <label class="form-label" for="city">Ciudad</label>
                        <input type="text" id="city" name="city" class="form-control @error('city') is-invalid @enderror" value="{{ old('city', $hotelSetting->city) }}">
                        @error('city')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="col-md-4">
                        <label class="form-label" for="country">Pais</label>
                        <input type="text" id="country" name="country" class="form-control @error('country') is-invalid @enderror" value="{{ old('country', $hotelSetting->country) }}">
                        @error('country')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="col-md-4">
                        <label class="form-label" for="phone">Telefono</label>
                        <input type="text" id="phone" name="phone" class="form-control @error('phone') is-invalid @enderror" value="{{ old('phone', $hotelSetting->phone) }}">
                        @error('phone')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="col-md-4">
                        <label class="form-label" for="website">Sitio web</label>
                        <input type="url" id="website" name="website" class="form-control @error('website') is-invalid @enderror" value="{{ old('website', $hotelSetting->website) }}">
                        @error('website')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <input type="hidden" name="email" value="{{ old('email', $hotelSetting->email) }}">
                    <input type="hidden" name="whatsapp" value="{{ old('whatsapp', $hotelSetting->whatsapp) }}">
                    <div class="col-12">
                        <div class="contact-channels-admin" data-contact-people-admin>
                            <div class="d-flex align-items-start justify-content-between gap-3 mb-3">
                                <div>
                                    <strong>Contactate con</strong>
                                    <p class="text-muted small mb-0">Agrega personas o areas que apareceran en el frontend con foto y boton directo a WhatsApp.</p>
                                </div>
                                <button type="button" class="btn btn-outline-primary btn-sm" data-add-contact-person>
                                    <i class="bi bi-plus-lg me-1" aria-hidden="true"></i> Agregar WhatsApp
                                </button>
                            </div>

                            <div class="contact-people-list" data-contact-people-list>
                                @forelse ($configuredContactPeople as $personIndex => $person)
                                    @php($personPhoto = $person['photo'] ?? '')
                                    <div class="contact-person-row" data-contact-person-row>
                                        <div class="contact-person-photo">
                                            @if ($personPhoto)
                                                <img src="{{ asset('storage/'.$personPhoto) }}" alt="{{ $person['name'] ?? 'Contacto' }}">
                                            @else
                                                <i class="bi bi-person-circle"></i>
                                            @endif
                                        </div>
                                        <div>
                                            <label class="form-label" for="contact-person-name-{{ $personIndex }}">Nombre</label>
                                            <input type="text" id="contact-person-name-{{ $personIndex }}" name="contact_people[{{ $personIndex }}][name]" class="form-control" value="{{ $person['name'] ?? '' }}" placeholder="Recepcion">
                                        </div>
                                        <div>
                                            <label class="form-label" for="contact-person-role-{{ $personIndex }}">Cargo / area</label>
                                            <input type="text" id="contact-person-role-{{ $personIndex }}" name="contact_people[{{ $personIndex }}][role]" class="form-control" value="{{ $person['role'] ?? '' }}" placeholder="Reservas">
                                        </div>
                                        <div>
                                            <label class="form-label" for="contact-person-country-{{ $personIndex }}">Codigo pais</label>
                                            <input type="text" id="contact-person-country-{{ $personIndex }}" name="contact_people[{{ $personIndex }}][country_code]" class="form-control" value="{{ $person['country_code'] ?? '591' }}" placeholder="591">
                                        </div>
                                        <div>
                                            <label class="form-label" for="contact-person-phone-{{ $personIndex }}">Celular</label>
                                            <input type="text" id="contact-person-phone-{{ $personIndex }}" name="contact_people[{{ $personIndex }}][phone]" class="form-control" value="{{ $person['phone'] ?? '' }}" placeholder="70000000">
                                        </div>
                                        <div>
                                            <label class="form-label" for="contact-person-photo-{{ $personIndex }}">Foto</label>
                                            <input type="file" id="contact-person-photo-{{ $personIndex }}" name="contact_people[{{ $personIndex }}][photo]" class="form-control" accept=".jpg,.jpeg,.png,.webp">
                                            <input type="hidden" name="contact_people[{{ $personIndex }}][existing_photo]" value="{{ $personPhoto }}">
                                            <input type="hidden" name="contact_people[{{ $personIndex }}][clear_photo]" value="0" data-clear-person-photo>
                                        </div>
                                        <button type="button" class="btn btn-outline-danger contact-person-row__remove" data-remove-contact-person aria-label="Quitar contacto">
                                            <i class="bi bi-trash" aria-hidden="true"></i>
                                        </button>
                                    </div>
                                @empty
                                    <div class="contact-person-row" data-contact-person-row>
                                        <div class="contact-person-photo"><i class="bi bi-person-circle"></i></div>
                                        <div>
                                            <label class="form-label" for="contact-person-name-0">Nombre</label>
                                            <input type="text" id="contact-person-name-0" name="contact_people[0][name]" class="form-control" placeholder="Recepcion">
                                        </div>
                                        <div>
                                            <label class="form-label" for="contact-person-role-0">Cargo / area</label>
                                            <input type="text" id="contact-person-role-0" name="contact_people[0][role]" class="form-control" placeholder="Reservas">
                                        </div>
                                        <div>
                                            <label class="form-label" for="contact-person-country-0">Codigo pais</label>
                                            <input type="text" id="contact-person-country-0" name="contact_people[0][country_code]" class="form-control" value="591" placeholder="591">
                                        </div>
                                        <div>
                                            <label class="form-label" for="contact-person-phone-0">Celular</label>
                                            <input type="text" id="contact-person-phone-0" name="contact_people[0][phone]" class="form-control" placeholder="70000000">
                                        </div>
                                        <div>
                                            <label class="form-label" for="contact-person-photo-0">Foto</label>
                                            <input type="file" id="contact-person-photo-0" name="contact_people[0][photo]" class="form-control" accept=".jpg,.jpeg,.png,.webp">
                                            <input type="hidden" name="contact_people[0][existing_photo]" value="">
                                            <input type="hidden" name="contact_people[0][clear_photo]" value="0" data-clear-person-photo>
                                        </div>
                                        <button type="button" class="btn btn-outline-danger contact-person-row__remove" data-remove-contact-person aria-label="Quitar contacto">
                                            <i class="bi bi-trash" aria-hidden="true"></i>
                                        </button>
                                    </div>
                                @endforelse
                            </div>
                        </div>
                    </div>
                    <div class="col-12">
                        <div class="contact-emails-admin" data-contact-emails-admin>
                            <div class="d-flex align-items-start justify-content-between gap-3 mb-3">
                                <div>
                                    <strong>Correos de contacto</strong>
                                    <p class="text-muted small mb-0">Agrega los correos o areas a los que el cliente puede escribir.</p>
                                </div>
                                <button type="button" class="btn btn-outline-primary btn-sm" data-add-contact-email>
                                    <i class="bi bi-plus-lg me-1" aria-hidden="true"></i> Agregar correo
                                </button>
                            </div>
                            <div class="contact-emails-list" data-contact-emails-list>
                                @forelse ($configuredContactEmails as $emailIndex => $contactEmail)
                                    <div class="contact-email-row" data-contact-email-row>
                                        <div>
                                            <label class="form-label" for="contact-email-label-{{ $emailIndex }}">Nombre / area</label>
                                            <input type="text" id="contact-email-label-{{ $emailIndex }}" name="contact_emails[{{ $emailIndex }}][label]" class="form-control" value="{{ $contactEmail['label'] ?? '' }}" placeholder="Reservas">
                                        </div>
                                        <div>
                                            <label class="form-label" for="contact-email-email-{{ $emailIndex }}">Correo</label>
                                            <input type="email" id="contact-email-email-{{ $emailIndex }}" name="contact_emails[{{ $emailIndex }}][email]" class="form-control" value="{{ $contactEmail['email'] ?? '' }}" placeholder="reservas@hotel.com">
                                        </div>
                                        <button type="button" class="btn btn-outline-danger contact-email-row__remove" data-remove-contact-email aria-label="Quitar correo">
                                            <i class="bi bi-trash" aria-hidden="true"></i>
                                        </button>
                                    </div>
                                @empty
                                    <div class="contact-email-row" data-contact-email-row>
                                        <div>
                                            <label class="form-label" for="contact-email-label-0">Nombre / area</label>
                                            <input type="text" id="contact-email-label-0" name="contact_emails[0][label]" class="form-control" placeholder="Reservas">
                                        </div>
                                        <div>
                                            <label class="form-label" for="contact-email-email-0">Correo</label>
                                            <input type="email" id="contact-email-email-0" name="contact_emails[0][email]" class="form-control" placeholder="reservas@hotel.com">
                                        </div>
                                        <button type="button" class="btn btn-outline-danger contact-email-row__remove" data-remove-contact-email aria-label="Quitar correo">
                                            <i class="bi bi-trash" aria-hidden="true"></i>
                                        </button>
                                    </div>
                                @endforelse
                            </div>
                        </div>
                    </div>
                </div>
            </x-adminlte-card>
        </div>

        <div class="col-lg-6">
            <x-adminlte-card title="Redes sociales" icon="bi bi-share" class="h-100">
                <div class="row g-3">
                    <div class="col-12">
                        <div class="social-links-admin" data-social-links-admin>
                            <div class="d-flex align-items-start justify-content-between gap-3 mb-3">
                                <div>
                                    <strong>Siguenos en nuestras redes</strong>
                                    <p class="text-muted small mb-0">Agrega las redes que quieras mostrar en el footer publico. El icono se selecciona de una lista pensada para hoteleria y canales digitales.</p>
                                </div>
                                <button type="button" class="btn btn-outline-primary btn-sm" data-add-social-link>
                                    <i class="bi bi-plus-lg me-1" aria-hidden="true"></i> Agregar red
                                </button>
                            </div>

                            <div class="social-links-admin__list" data-social-links-list>
                                @forelse ($configuredSocialLinks as $socialIndex => $socialLink)
                                    <div class="social-link-row" data-social-link-row>
                                        <div>
                                            <label class="form-label" for="social-link-label-{{ $socialIndex }}">Nombre</label>
                                            <input type="text" id="social-link-label-{{ $socialIndex }}" name="social_links[{{ $socialIndex }}][label]" class="form-control @error("social_links.$socialIndex.label") is-invalid @enderror" value="{{ $socialLink['label'] ?? '' }}" placeholder="Facebook">
                                            @error("social_links.$socialIndex.label")
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                        <div>
                                            <label class="form-label" for="social-link-url-{{ $socialIndex }}">Enlace</label>
                                            <input type="url" id="social-link-url-{{ $socialIndex }}" name="social_links[{{ $socialIndex }}][url]" class="form-control @error("social_links.$socialIndex.url") is-invalid @enderror" value="{{ $socialLink['url'] ?? '' }}" placeholder="https://...">
                                            @error("social_links.$socialIndex.url")
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                        <div>
                                            <label class="form-label" for="social-link-icon-{{ $socialIndex }}">Icono</label>
                                            <select id="social-link-icon-{{ $socialIndex }}" name="social_links[{{ $socialIndex }}][icon]" class="form-select social-icon-select @error("social_links.$socialIndex.icon") is-invalid @enderror">
                                                @foreach ($socialNetworkOptions as $socialOption)
                                                    <option value="{{ $socialOption['icon'] }}" data-icon="{{ $socialOption['icon'] }}" data-label="{{ $socialOption['label'] }}" @selected(($socialLink['icon'] ?? 'bi-link-45deg') === $socialOption['icon'])>
                                                        {{ $socialOption['label'] }}
                                                    </option>
                                                @endforeach
                                            </select>
                                            @error("social_links.$socialIndex.icon")
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                        <button type="button" class="btn btn-outline-danger social-link-row__remove" data-remove-social-link aria-label="Quitar red social">
                                            <i class="bi bi-trash" aria-hidden="true"></i>
                                        </button>
                                    </div>
                                @empty
                                    <div class="social-link-row" data-social-link-row>
                                        <div>
                                            <label class="form-label" for="social-link-label-0">Nombre</label>
                                            <input type="text" id="social-link-label-0" name="social_links[0][label]" class="form-control" placeholder="Facebook">
                                        </div>
                                        <div>
                                            <label class="form-label" for="social-link-url-0">Enlace</label>
                                            <input type="url" id="social-link-url-0" name="social_links[0][url]" class="form-control" placeholder="https://...">
                                        </div>
                                        <div>
                                            <label class="form-label" for="social-link-icon-0">Icono</label>
                                            <select id="social-link-icon-0" name="social_links[0][icon]" class="form-select social-icon-select">
                                                @foreach ($socialNetworkOptions as $socialOption)
                                                    <option value="{{ $socialOption['icon'] }}" data-icon="{{ $socialOption['icon'] }}" data-label="{{ $socialOption['label'] }}" @selected($socialOption['icon'] === 'bi-facebook')>
                                                        {{ $socialOption['label'] }}
                                                    </option>
                                                @endforeach
                                            </select>
                                        </div>
                                        <button type="button" class="btn btn-outline-danger social-link-row__remove" data-remove-social-link aria-label="Quitar red social">
                                            <i class="bi bi-trash" aria-hidden="true"></i>
                                        </button>
                                    </div>
                                @endforelse
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label" for="google_maps_url">Enlace de Google Maps</label>
                        <input type="url" id="google_maps_url" name="google_maps_url" class="form-control @error('google_maps_url') is-invalid @enderror" value="{{ old('google_maps_url', $hotelSetting->google_maps_url) }}" placeholder="https://www.google.com/maps/place/...">
                        <div class="form-text">Pega el enlace de compartir de Google Maps o un enlace embed. No pegues solo https://www.google.com/.</div>
                        @error('google_maps_url')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
            </x-adminlte-card>
        </div>

        <div class="col-lg-6">
            <x-adminlte-card title="Monedas del sistema" icon="bi bi-currency-exchange" class="h-100">
                <div class="currencies-admin" data-currencies-admin>
                    <div class="d-flex align-items-start justify-content-between gap-3 mb-3">
                        <div>
                            <strong>Tipos de moneda para contabilizar</strong>
                            <p class="text-muted small mb-0">Agrega todas las monedas que usara el hotel. La moneda marcada como base se usa para saldos principales, caja y reportes.</p>
                        </div>
                        <button type="button" class="btn btn-outline-primary btn-sm" data-add-currency>
                            <i class="bi bi-plus-lg me-1" aria-hidden="true"></i> Agregar moneda
                        </button>
                    </div>

                    <div class="currencies-admin__list" data-currencies-list>
                        @foreach ($configuredCurrencies as $currencyIndex => $currencyRow)
                            <div class="currency-row" data-currency-row>
                                <div>
                                    <label class="form-label" for="currency-code-{{ $currencyIndex }}">Codigo</label>
                                    <input type="text" id="currency-code-{{ $currencyIndex }}" name="enabled_currencies[{{ $currencyIndex }}][code]" class="form-control text-uppercase @error("enabled_currencies.$currencyIndex.code") is-invalid @enderror" value="{{ $currencyRow['code'] ?? '' }}" placeholder="BOB" maxlength="10" required>
                                    @error("enabled_currencies.$currencyIndex.code")
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                                <div>
                                    <label class="form-label" for="currency-name-{{ $currencyIndex }}">Nombre</label>
                                    <input type="text" id="currency-name-{{ $currencyIndex }}" name="enabled_currencies[{{ $currencyIndex }}][name]" class="form-control @error("enabled_currencies.$currencyIndex.name") is-invalid @enderror" value="{{ $currencyRow['name'] ?? '' }}" placeholder="Bolivianos" required>
                                    @error("enabled_currencies.$currencyIndex.name")
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                                <div>
                                    <label class="form-label" for="currency-symbol-{{ $currencyIndex }}">Simbolo</label>
                                    <input type="text" id="currency-symbol-{{ $currencyIndex }}" name="enabled_currencies[{{ $currencyIndex }}][symbol]" class="form-control @error("enabled_currencies.$currencyIndex.symbol") is-invalid @enderror" value="{{ $currencyRow['symbol'] ?? '' }}" placeholder="Bs." maxlength="20" required>
                                    @error("enabled_currencies.$currencyIndex.symbol")
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                                <div class="currency-row__base">
                                    <input type="radio" id="currency-base-{{ $currencyIndex }}" name="currency_base_code" value="{{ $currencyRow['code'] ?? '' }}" class="form-check-input" data-currency-base-radio @checked((bool) ($currencyRow['is_base'] ?? false))>
                                    <label class="form-check-label" for="currency-base-{{ $currencyIndex }}">Base</label>
                                </div>
                                <button type="button" class="btn btn-outline-danger currency-row__remove" data-remove-currency aria-label="Quitar moneda">
                                    <i class="bi bi-trash" aria-hidden="true"></i>
                                </button>
                            </div>
                        @endforeach
                    </div>

                    <div class="form-text mt-2">Ejemplos: BOB / Bs. / Bolivianos, USD / $us / Dolares, EUR / € / Euros.</div>
                </div>
            </x-adminlte-card>
        </div>

        <div class="col-lg-6">
            <x-adminlte-card title="Datos fiscales" icon="bi bi-file-earmark-text" class="h-100">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label" for="tax_name">Nombre fiscal</label>
                        <input type="text" id="tax_name" name="tax_name" class="form-control @error('tax_name') is-invalid @enderror" value="{{ old('tax_name', $hotelSetting->tax_name) }}">
                        @error('tax_name')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="col-md-6">
                        <label class="form-label" for="tax_number">NIT / Numero fiscal</label>
                        <input type="text" id="tax_number" name="tax_number" class="form-control @error('tax_number') is-invalid @enderror" value="{{ old('tax_number', $hotelSetting->tax_number) }}">
                        @error('tax_number')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
            </x-adminlte-card>
        </div>

        <div class="col-lg-6">
            <x-adminlte-card title="Pagos" icon="bi bi-credit-card" class="h-100">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label" for="digital_wallet_qr_image">QR de billetera digital</label>
                        <input type="file" id="digital_wallet_qr_image" name="digital_wallet_qr_image" class="form-control @error('digital_wallet_qr_image') is-invalid @enderror" accept=".jpg,.jpeg,.png,.webp">
                        @error('digital_wallet_qr_image')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                        @php($walletQrStatus = $mediaStatus($hotelSetting->digital_wallet_qr_image ?? $hotelSetting->payment_qr_image))
                        <div class="media-config-card mt-2" data-media-card>
                            <div class="d-flex align-items-center justify-content-between gap-2 mb-2">
                                <strong>QR billetera actual</strong>
                                <span class="badge rounded-pill {{ $walletQrStatus['class'] }}" data-media-status>{{ $walletQrStatus['label'] }}</span>
                            </div>
                            <div class="media-preview text-center">
                                @if ($imagePreview($hotelSetting->digital_wallet_qr_image ?? $hotelSetting->payment_qr_image))
                                    <img src="{{ $imagePreview($hotelSetting->digital_wallet_qr_image ?? $hotelSetting->payment_qr_image) }}" alt="QR de billetera digital actual" class="img-fluid rounded" style="max-height: 180px;" data-preview-image>
                                @else
                                    <span class="text-muted" data-preview-empty>Sin imagen cargada</span>
                                @endif
                            </div>
                            <input type="hidden" name="clear_digital_wallet_qr_image" value="0" data-clear-input>
                            <div class="d-flex justify-content-between align-items-center gap-2 mt-3">
                                <small class="text-muted">Para pagos por billeteras digitales o apps de pago.</small>
                                <button type="button" class="btn btn-outline-danger btn-sm" data-clear-trigger @disabled(blank($hotelSetting->digital_wallet_qr_image ?? $hotelSetting->payment_qr_image))>Quitar</button>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label" for="bank_qr_image">QR de banco local</label>
                        <input type="file" id="bank_qr_image" name="bank_qr_image" class="form-control @error('bank_qr_image') is-invalid @enderror" accept=".jpg,.jpeg,.png,.webp">
                        @error('bank_qr_image')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                        @php($bankQrStatus = $mediaStatus($hotelSetting->bank_qr_image))
                        <div class="media-config-card mt-2" data-media-card>
                            <div class="d-flex align-items-center justify-content-between gap-2 mb-2">
                                <strong>QR banco actual</strong>
                                <span class="badge rounded-pill {{ $bankQrStatus['class'] }}" data-media-status>{{ $bankQrStatus['label'] }}</span>
                            </div>
                            <div class="media-preview text-center">
                                @if ($imagePreview($hotelSetting->bank_qr_image))
                                    <img src="{{ $imagePreview($hotelSetting->bank_qr_image) }}" alt="QR de banco local actual" class="img-fluid rounded" style="max-height: 180px;" data-preview-image>
                                @else
                                    <span class="text-muted" data-preview-empty>Sin imagen cargada</span>
                                @endif
                            </div>
                            <input type="hidden" name="clear_bank_qr_image" value="0" data-clear-input>
                            <div class="d-flex justify-content-between align-items-center gap-2 mt-3">
                                <small class="text-muted">Para QR generado por banco local.</small>
                                <button type="button" class="btn btn-outline-danger btn-sm" data-clear-trigger @disabled(blank($hotelSetting->bank_qr_image))>Quitar</button>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label" for="bank_name">Banco</label>
                        <input type="text" id="bank_name" name="bank_name" class="form-control @error('bank_name') is-invalid @enderror" value="{{ old('bank_name', $hotelSetting->bank_name) }}">
                        @error('bank_name')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="col-md-4">
                        <label class="form-label" for="bank_account_holder">Titular</label>
                        <input type="text" id="bank_account_holder" name="bank_account_holder" class="form-control @error('bank_account_holder') is-invalid @enderror" value="{{ old('bank_account_holder', $hotelSetting->bank_account_holder) }}">
                        @error('bank_account_holder')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="col-md-4">
                        <label class="form-label" for="bank_account_number">Numero de cuenta</label>
                        <input type="text" id="bank_account_number" name="bank_account_number" class="form-control @error('bank_account_number') is-invalid @enderror" value="{{ old('bank_account_number', $hotelSetting->bank_account_number) }}">
                        @error('bank_account_number')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="col-12">
                        <label class="form-label" for="payment_instructions">Instrucciones de pago</label>
                        <textarea id="payment_instructions" name="payment_instructions" rows="6" class="form-control @error('payment_instructions') is-invalid @enderror">{{ old('payment_instructions', $hotelSetting->payment_instructions) }}</textarea>
                        @error('payment_instructions')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
            </x-adminlte-card>
        </div>

        <div class="col-12">
            <div class="d-flex justify-content-end pb-3">
                <button type="submit" class="btn settings-save-btn">
                    <i class="bi bi-save" aria-hidden="true"></i> Guardar configuracion
                </button>
            </div>
        </div>
        </div>
    </form>
@stop

@push('js')
    <script src="{{ asset('vendor/jquery/jquery.min.js') }}"></script>
    <script src="{{ asset('vendor/select2/select2.full.min.js') }}"></script>
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const socialNetworkOptions = @json($socialNetworkOptions->values());
            const normalizeHexColor = (value) => {
                value = String(value || '').trim();

                if (value !== '' && !value.startsWith('#')) {
                    value = `#${value}`;
                }

                return /^#[0-9a-fA-F]{6}$/.test(value) ? value.toLowerCase() : null;
            };

            const themePreview = document.querySelector('[data-theme-preview]');
            const updateThemePreview = () => {
                if (!themePreview) {
                    return;
                }

                const primary = normalizeHexColor(document.querySelector('[data-theme-color="primary"]')?.value);
                const secondary = normalizeHexColor(document.querySelector('[data-theme-color="secondary"]')?.value);
                const accent = normalizeHexColor(document.querySelector('[data-theme-color="accent"]')?.value);
                const background = normalizeHexColor(document.querySelector('[data-theme-color="background"]')?.value);
                const surface = normalizeHexColor(document.querySelector('[data-theme-color="surface"]')?.value);
                const text = normalizeHexColor(document.querySelector('[data-theme-color="text"]')?.value);
                const muted = normalizeHexColor(document.querySelector('[data-theme-color="muted"]')?.value);

                if (primary) themePreview.style.setProperty('--preview-primary', primary);
                if (secondary) themePreview.style.setProperty('--preview-secondary', secondary);
                if (accent) themePreview.style.setProperty('--preview-accent', accent);
                if (background) themePreview.style.setProperty('--preview-bg', background);
                if (surface) themePreview.style.setProperty('--preview-surface', surface);
                if (text) themePreview.style.setProperty('--preview-text', text);
                if (muted) themePreview.style.setProperty('--preview-muted', muted);
            };

            document.querySelectorAll('[data-theme-color]').forEach((input) => {
                const picker = document.querySelector(`[data-theme-picker="${input.name}"]`);

                input.addEventListener('input', () => {
                    const normalized = normalizeHexColor(input.value);

                    if (normalized && picker) {
                        picker.value = normalized;
                    }

                    updateThemePreview();
                });

                input.addEventListener('blur', () => {
                    const normalized = normalizeHexColor(input.value);

                    if (normalized) {
                        input.value = normalized;
                    }
                });
            });

            document.querySelectorAll('[data-theme-picker]').forEach((picker) => {
                const textInput = document.getElementById(picker.dataset.themePicker);

                picker.addEventListener('input', () => {
                    if (textInput) {
                        textInput.value = picker.value.toLowerCase();
                    }

                    updateThemePreview();
                });
            });

            document.querySelectorAll('[data-currencies-admin]').forEach((admin) => {
                const list = admin.querySelector('[data-currencies-list]');
                const addButton = admin.querySelector('[data-add-currency]');

                if (!list || !addButton) {
                    return;
                }

                const nextIndex = () => list.querySelectorAll('[data-currency-row]').length;
                const template = (index) => `
                    <div class="currency-row" data-currency-row>
                        <div>
                            <label class="form-label" for="currency-code-${index}">Codigo</label>
                            <input type="text" id="currency-code-${index}" name="enabled_currencies[${index}][code]" class="form-control text-uppercase" placeholder="EUR" maxlength="10" required>
                        </div>
                        <div>
                            <label class="form-label" for="currency-name-${index}">Nombre</label>
                            <input type="text" id="currency-name-${index}" name="enabled_currencies[${index}][name]" class="form-control" placeholder="Euros" required>
                        </div>
                        <div>
                            <label class="form-label" for="currency-symbol-${index}">Simbolo</label>
                            <input type="text" id="currency-symbol-${index}" name="enabled_currencies[${index}][symbol]" class="form-control" placeholder="€" maxlength="20" required>
                        </div>
                        <div class="currency-row__base">
                            <input type="radio" id="currency-base-${index}" name="currency_base_code" value="" class="form-check-input" data-currency-base-radio>
                            <label class="form-check-label" for="currency-base-${index}">Base</label>
                        </div>
                        <button type="button" class="btn btn-outline-danger currency-row__remove" data-remove-currency aria-label="Quitar moneda">
                            <i class="bi bi-trash" aria-hidden="true"></i>
                        </button>
                    </div>
                `;

                const syncBaseRadios = () => {
                    list.querySelectorAll('[data-currency-row]').forEach((row) => {
                        const codeInput = row.querySelector('input[name$="[code]"]');
                        const radio = row.querySelector('[data-currency-base-radio]');
                        const normalizedCode = (codeInput?.value || '').replace(/[^a-zA-Z0-9]/g, '').toUpperCase();

                        if (codeInput && codeInput.value !== normalizedCode) {
                            codeInput.value = normalizedCode;
                        }

                        if (radio) {
                            radio.value = normalizedCode;
                        }
                    });

                    const radios = [...list.querySelectorAll('[data-currency-base-radio]')];
                    if (radios.length && !radios.some((radio) => radio.checked)) {
                        radios[0].checked = true;
                    }
                };

                addButton.addEventListener('click', () => {
                    list.insertAdjacentHTML('beforeend', template(nextIndex()));
                    syncBaseRadios();
                });

                list.addEventListener('input', (event) => {
                    if (event.target.matches('input[name$="[code]"]')) {
                        syncBaseRadios();
                    }
                });

                list.addEventListener('click', (event) => {
                    const removeButton = event.target.closest('[data-remove-currency]');

                    if (!removeButton) {
                        return;
                    }

                    const rows = list.querySelectorAll('[data-currency-row]');
                    if (rows.length <= 1) {
                        return;
                    }

                    removeButton.closest('[data-currency-row]')?.remove();
                    syncBaseRadios();
                });

                syncBaseRadios();
            });

            document.querySelectorAll('[data-contact-people-admin]').forEach((admin) => {
                const list = admin.querySelector('[data-contact-people-list]');
                const addButton = admin.querySelector('[data-add-contact-person]');

                if (!list || !addButton) {
                    return;
                }

                const nextIndex = () => list.querySelectorAll('[data-contact-person-row]').length;
                const template = (index) => `
                    <div class="contact-person-row" data-contact-person-row>
                        <div class="contact-person-photo"><i class="bi bi-person-circle"></i></div>
                        <div>
                            <label class="form-label" for="contact-person-name-${index}">Nombre</label>
                            <input type="text" id="contact-person-name-${index}" name="contact_people[${index}][name]" class="form-control" placeholder="Recepcion">
                        </div>
                        <div>
                            <label class="form-label" for="contact-person-role-${index}">Cargo / area</label>
                            <input type="text" id="contact-person-role-${index}" name="contact_people[${index}][role]" class="form-control" placeholder="Reservas">
                        </div>
                        <div>
                            <label class="form-label" for="contact-person-country-${index}">Codigo pais</label>
                            <input type="text" id="contact-person-country-${index}" name="contact_people[${index}][country_code]" class="form-control" value="591" placeholder="591">
                        </div>
                        <div>
                            <label class="form-label" for="contact-person-phone-${index}">Celular</label>
                            <input type="text" id="contact-person-phone-${index}" name="contact_people[${index}][phone]" class="form-control" placeholder="70000000">
                        </div>
                        <div>
                            <label class="form-label" for="contact-person-photo-${index}">Foto</label>
                            <input type="file" id="contact-person-photo-${index}" name="contact_people[${index}][photo]" class="form-control" accept=".jpg,.jpeg,.png,.webp">
                            <input type="hidden" name="contact_people[${index}][existing_photo]" value="">
                            <input type="hidden" name="contact_people[${index}][clear_photo]" value="0" data-clear-person-photo>
                        </div>
                        <button type="button" class="btn btn-outline-danger contact-person-row__remove" data-remove-contact-person aria-label="Quitar contacto">
                            <i class="bi bi-trash" aria-hidden="true"></i>
                        </button>
                    </div>
                `;

                addButton.addEventListener('click', () => {
                    list.insertAdjacentHTML('beforeend', template(nextIndex()));
                });

                list.addEventListener('click', (event) => {
                    const removeButton = event.target.closest('[data-remove-contact-person]');

                    if (!removeButton) {
                        return;
                    }

                    const rows = list.querySelectorAll('[data-contact-person-row]');
                    if (rows.length <= 1) {
                        removeButton.closest('[data-contact-person-row]')?.querySelectorAll('input').forEach((input) => {
                            if (input.type !== 'hidden') {
                                input.value = input.name.includes('[country_code]') ? '591' : '';
                            }
                        });
                        return;
                    }

                    const row = removeButton.closest('[data-contact-person-row]');
                    const clearPhotoInput = row?.querySelector('[data-clear-person-photo]');
                    if (clearPhotoInput) {
                        clearPhotoInput.value = '1';
                    }
                    row?.remove();
                });
            });

            document.querySelectorAll('[data-contact-emails-admin]').forEach((admin) => {
                const list = admin.querySelector('[data-contact-emails-list]');
                const addButton = admin.querySelector('[data-add-contact-email]');

                if (!list || !addButton) {
                    return;
                }

                const nextIndex = () => list.querySelectorAll('[data-contact-email-row]').length;
                const template = (index) => `
                    <div class="contact-email-row" data-contact-email-row>
                        <div>
                            <label class="form-label" for="contact-email-label-${index}">Nombre / area</label>
                            <input type="text" id="contact-email-label-${index}" name="contact_emails[${index}][label]" class="form-control" placeholder="Reservas">
                        </div>
                        <div>
                            <label class="form-label" for="contact-email-email-${index}">Correo</label>
                            <input type="email" id="contact-email-email-${index}" name="contact_emails[${index}][email]" class="form-control" placeholder="reservas@hotel.com">
                        </div>
                        <button type="button" class="btn btn-outline-danger contact-email-row__remove" data-remove-contact-email aria-label="Quitar correo">
                            <i class="bi bi-trash" aria-hidden="true"></i>
                        </button>
                    </div>
                `;

                addButton.addEventListener('click', () => {
                    list.insertAdjacentHTML('beforeend', template(nextIndex()));
                });

                list.addEventListener('click', (event) => {
                    const removeButton = event.target.closest('[data-remove-contact-email]');

                    if (!removeButton) {
                        return;
                    }

                    const rows = list.querySelectorAll('[data-contact-email-row]');
                    if (rows.length <= 1) {
                        removeButton.closest('[data-contact-email-row]')?.querySelectorAll('input').forEach((input) => {
                            input.value = '';
                        });
                        return;
                    }

                    removeButton.closest('[data-contact-email-row]')?.remove();
                });
            });

            document.querySelectorAll('[data-social-links-admin]').forEach((admin) => {
                const list = admin.querySelector('[data-social-links-list]');
                const addButton = admin.querySelector('[data-add-social-link]');

                if (!list || !addButton) {
                    return;
                }

                const socialOptionsMarkup = () => socialNetworkOptions.map((option) => `
                    <option value="${option.icon}" data-icon="${option.icon}" data-label="${option.label}">
                        ${option.label}
                    </option>
                `).join('');

                const renderSocialOption = (option) => {
                    if (!option.id) {
                        return option.text;
                    }

                    const element = option.element;
                    const icon = element?.dataset.icon || option.id || 'bi-link-45deg';
                    const label = element?.dataset.label || option.text || 'Red social';

                    return `<span class="social-icon-select-option"><i class="bi ${icon}"></i>${label}</span>`;
                };

                const initializeSocialIconSelects = (scope = list) => {
                    if (typeof window.jQuery !== 'function' || typeof window.jQuery.fn.select2 !== 'function') {
                        return;
                    }

                    window.jQuery(scope).find('.social-icon-select').each(function () {
                        const $select = window.jQuery(this);

                        if ($select.data('select2')) {
                            return;
                        }

                        $select.select2({
                            width: '100%',
                            dropdownParent: $select.closest('.card, .modal, body'),
                            templateResult: renderSocialOption,
                            templateSelection: renderSocialOption,
                            escapeMarkup: (markup) => markup,
                            placeholder: 'Selecciona una red',
                        });

                        $select.on('change', function () {
                            const row = this.closest('[data-social-link-row]');
                            const labelInput = row?.querySelector('input[name$="[label]"]');
                            const selected = this.selectedOptions[0];
                            const label = selected?.dataset.label || selected?.textContent?.trim() || '';

                            if (labelInput && labelInput.value.trim() === '') {
                                labelInput.value = label;
                            }
                        });
                    });
                };

                const nextIndex = () => list.querySelectorAll('[data-social-link-row]').length;
                const template = (index) => `
                    <div class="social-link-row" data-social-link-row>
                        <div>
                            <label class="form-label" for="social-link-label-${index}">Nombre</label>
                            <input type="text" id="social-link-label-${index}" name="social_links[${index}][label]" class="form-control" placeholder="YouTube">
                        </div>
                        <div>
                            <label class="form-label" for="social-link-url-${index}">Enlace</label>
                            <input type="url" id="social-link-url-${index}" name="social_links[${index}][url]" class="form-control" placeholder="https://...">
                        </div>
                        <div>
                            <label class="form-label" for="social-link-icon-${index}">Icono</label>
                            <select id="social-link-icon-${index}" name="social_links[${index}][icon]" class="form-select social-icon-select">
                                ${socialOptionsMarkup()}
                            </select>
                        </div>
                        <button type="button" class="btn btn-outline-danger social-link-row__remove" data-remove-social-link aria-label="Quitar red social">
                            <i class="bi bi-trash" aria-hidden="true"></i>
                        </button>
                    </div>
                `;

                initializeSocialIconSelects();

                addButton.addEventListener('click', () => {
                    list.insertAdjacentHTML('beforeend', template(nextIndex()));
                    const row = list.querySelector('[data-social-link-row]:last-child');
                    initializeSocialIconSelects(row);
                });

                list.addEventListener('click', (event) => {
                    const removeButton = event.target.closest('[data-remove-social-link]');

                    if (!removeButton) {
                        return;
                    }

                    const rows = list.querySelectorAll('[data-social-link-row]');
                    if (rows.length <= 1) {
                        removeButton.closest('[data-social-link-row]')?.querySelectorAll('input').forEach((input) => {
                            input.value = '';
                        });
                        const select = removeButton.closest('[data-social-link-row]')?.querySelector('.social-icon-select');
                        if (select) {
                            select.value = 'bi-facebook';
                            if (typeof window.jQuery === 'function' && window.jQuery(select).data('select2')) {
                                window.jQuery(select).val('bi-facebook').trigger('change');
                            }
                        }

                        return;
                    }

                    removeButton.closest('[data-social-link-row]')?.remove();
                });
            });

            document.querySelectorAll('[data-media-card]').forEach((card) => {
                const clearInput = card.querySelector('[data-clear-input]');
                const clearTrigger = card.querySelector('[data-clear-trigger]');
                const status = card.querySelector('[data-media-status]');
                const fileInput = card.parentElement.querySelector('input[type="file"], input[type="url"]');
                const previewImage = card.querySelector('[data-preview-image]');
                const previewVideo = card.querySelector('[data-preview-video]');
                const previewEmbed = card.querySelector('[data-preview-embed]');
                const previewEmpty = card.querySelector('[data-preview-empty]');
                const previewBox = card.querySelector('.media-preview');

                if (!clearTrigger || !clearInput || !status || !fileInput) {
                    return;
                }

                const markAsRemoved = () => {
                    clearInput.value = '1';
                    fileInput.value = '';
                    status.textContent = 'Se eliminara al guardar';
                    status.className = 'badge rounded-pill bg-warning-subtle text-warning-emphasis';
                    previewImage?.remove();
                    previewVideo?.remove();
                    previewEmbed?.remove();

                    if (previewEmpty) {
                        previewEmpty.textContent = 'Se quitara al guardar cambios';
                    } else {
                        const emptyState = document.createElement('div');
                        emptyState.className = 'text-center text-muted';
                        emptyState.setAttribute('data-preview-empty', '');
                        emptyState.textContent = 'Se quitara al guardar cambios';
                        card.querySelector('.media-preview')?.appendChild(emptyState);
                    }

                    clearTrigger.disabled = true;
                };

                const resetRemoval = () => {
                    clearInput.value = '0';
                    status.textContent = 'Nuevo archivo listo';
                    status.className = 'badge rounded-pill bg-info-subtle text-info-emphasis';
                    clearTrigger.disabled = false;
                };

                clearTrigger.addEventListener('click', markAsRemoved);

                fileInput.addEventListener('change', () => {
                    if (fileInput.type === 'url') {
                        if (fileInput.value.trim() !== '') {
                            resetRemoval();
                        }

                        return;
                    }

                    if (fileInput.files && fileInput.files.length > 0) {
                        resetRemoval();

                        if (fileInput.files[0]?.type?.startsWith('image/') && previewBox) {
                            previewImage?.remove();
                            previewEmpty?.remove();

                            const image = document.createElement('img');
                            image.src = URL.createObjectURL(fileInput.files[0]);
                            image.alt = 'Nueva imagen seleccionada';
                            image.setAttribute('data-preview-image', '');
                            image.addEventListener('load', () => URL.revokeObjectURL(image.src), { once: true });

                            previewBox.appendChild(image);
                        }
                    }
                });
            });
        });
    </script>

    @if (session('success'))
        <script>
            document.addEventListener('DOMContentLoaded', () => {
                if (window.Swal) {
                    window.Swal.fire({
                        icon: 'success',
                        title: 'Correcto',
                        text: @json(session('success')),
                        timer: 1800,
                        showConfirmButton: false,
                    });
                }
            });
        </script>
    @endif
@endpush

@push('css')
    <link rel="stylesheet" href="{{ asset('vendor/select2/select2.min.css') }}">
    <style>
        :root {
            --settings-ink: #172033;
            --settings-muted: #667085;
            --settings-line: rgba(15, 23, 42, .08);
            --settings-blue: #2563eb;
            --settings-green: #16a34a;
            --settings-gold: #d6a23d;
            --settings-shadow: 0 24px 60px rgba(15, 23, 42, .12);
        }

        .settings-hero {
            position: relative;
            overflow: hidden;
            display: flex;
            align-items: stretch;
            justify-content: space-between;
            gap: 1.5rem;
            min-height: 170px;
            padding: 1.8rem;
            border-radius: 30px;
            color: #fff;
            background:
                radial-gradient(circle at 12% 15%, rgba(214, 162, 61, .34), transparent 32%),
                radial-gradient(circle at 82% 20%, rgba(37, 99, 235, .28), transparent 34%),
                linear-gradient(135deg, #101827 0%, #253757 52%, #0f172a 100%);
            box-shadow: var(--settings-shadow);
        }

        .theme-editor-section {
            padding: 1.05rem;
            border: 1px solid rgba(15, 23, 42, .08);
            border-radius: 1.35rem;
            background:
                radial-gradient(circle at top right, rgba(37, 99, 235, .05), transparent 28%),
                #fff;
            box-shadow: 0 16px 38px rgba(15, 23, 42, .05);
        }

        .theme-editor-section h5 {
            margin: 0;
            color: var(--settings-ink);
            font-weight: 850;
        }

        .theme-editor-section p {
            margin: .25rem 0 1rem;
            color: var(--settings-muted);
            font-size: .9rem;
        }

        .theme-editor-kicker {
            display: inline-flex;
            margin-bottom: .2rem;
            color: var(--settings-blue);
            font-size: .68rem;
            font-weight: 900;
            letter-spacing: .14em;
            text-transform: uppercase;
        }

        .theme-color-control {
            display: grid;
            grid-template-columns: 4.25rem minmax(0, 1fr);
            gap: .65rem;
            align-items: center;
        }

        .theme-color-control--compact {
            grid-template-columns: 3.25rem minmax(0, 1fr);
            gap: .5rem;
        }

        .theme-color-control .form-control-color {
            width: 4.25rem;
            height: 2.7rem;
            padding: .25rem;
            border-radius: 1rem;
        }

        .theme-color-control--compact .form-control-color {
            width: 3.25rem;
        }

        .theme-use-note {
            display: block;
            margin-top: .45rem;
            color: var(--settings-muted);
            font-size: .76rem;
            line-height: 1.35;
        }

        .theme-usage-grid {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: .75rem;
        }

        .theme-usage-grid div {
            display: grid;
            gap: .15rem;
            padding: .8rem;
            border: 1px solid rgba(15, 23, 42, .08);
            border-radius: 1rem;
            background: #fff;
        }

        .theme-usage-grid span {
            width: 2.1rem;
            height: .45rem;
            border-radius: 999px;
            box-shadow: inset 0 0 0 1px rgba(0, 0, 0, .08);
        }

        .theme-usage-grid strong {
            color: var(--settings-ink);
            font-size: .82rem;
        }

        .theme-usage-grid small {
            color: var(--settings-muted);
            font-size: .72rem;
            line-height: 1.25;
        }

        .theme-preview-card {
            min-height: 100%;
            overflow: hidden;
            border-radius: 1.6rem;
            color: var(--preview-text);
            background:
                radial-gradient(circle at 16% 16%, color-mix(in srgb, var(--preview-secondary), transparent 70%), transparent 32%),
                radial-gradient(circle at 90% 8%, color-mix(in srgb, var(--preview-accent), transparent 70%), transparent 26%),
                linear-gradient(180deg, color-mix(in srgb, var(--preview-bg), #fff 28%), var(--preview-bg));
            box-shadow: 0 24px 60px color-mix(in srgb, var(--preview-primary), transparent 72%);
        }

        .theme-preview-card__nav {
            display: flex;
            align-items: center;
            gap: .55rem;
            padding: 1rem;
            color: #fff;
            background: color-mix(in srgb, var(--preview-primary), transparent 12%);
            backdrop-filter: blur(14px);
        }

        .theme-preview-card__nav strong {
            margin-right: auto;
            font-family: Georgia, serif;
            font-size: 1rem;
        }

        .theme-preview-card__nav span,
        .theme-preview-card__nav em {
            display: inline-flex;
            align-items: center;
            min-height: 1.8rem;
            padding: .35rem .65rem;
            border-radius: 999px;
            color: rgba(255, 255, 255, .86);
            background: rgba(255, 255, 255, .11);
            font-size: .68rem;
            font-style: normal;
            font-weight: 800;
            text-transform: uppercase;
        }

        .theme-preview-card__nav em {
            color: var(--preview-primary);
            background: var(--preview-secondary);
        }

        .theme-preview-card__body {
            padding: 1.35rem;
        }

        .theme-preview-card__body small {
            color: var(--preview-secondary);
            font-weight: 800;
            letter-spacing: .14em;
            text-transform: uppercase;
        }

        .theme-preview-card__body strong {
            display: block;
            margin-top: .45rem;
            font-size: clamp(1.8rem, 4vw, 2.5rem);
            line-height: 1;
            font-family: Georgia, serif;
            color: var(--preview-text);
        }

        .theme-preview-card__body p {
            max-width: 26rem;
            margin: .75rem 0 1.2rem;
            color: var(--preview-muted);
        }

        .theme-preview-card__actions {
            display: flex;
            flex-wrap: wrap;
            gap: .7rem;
        }

        .theme-preview-card__actions span {
            display: inline-flex;
            align-items: center;
            min-height: 2.5rem;
            padding: .65rem 1rem;
            border-radius: 999px;
            font-size: .75rem;
            font-weight: 900;
            text-transform: uppercase;
            background: linear-gradient(135deg, var(--preview-secondary), var(--preview-accent));
            color: #fff;
        }

        .theme-preview-card__actions span + span {
            background: var(--preview-accent);
            color: #fff;
        }

        .theme-preview-room {
            display: grid;
            grid-template-columns: 6.5rem minmax(0, 1fr);
            gap: .85rem;
            margin-top: 1.1rem;
            padding: .8rem;
            border: 1px solid color-mix(in srgb, var(--preview-primary), transparent 86%);
            border-radius: 1.15rem;
            background: color-mix(in srgb, var(--preview-surface), transparent 2%);
            box-shadow: 0 14px 34px color-mix(in srgb, var(--preview-primary), transparent 90%);
        }

        .theme-preview-room > div {
            min-height: 5rem;
            border-radius: .95rem;
            background:
                linear-gradient(135deg, color-mix(in srgb, var(--preview-primary), transparent 18%), color-mix(in srgb, var(--preview-secondary), transparent 24%)),
                var(--preview-primary);
        }

        .theme-preview-room section small {
            color: var(--preview-secondary);
        }

        .theme-preview-room section strong {
            margin-top: .1rem;
            font-size: 1.25rem;
            color: var(--preview-primary);
        }

        .theme-preview-room section p {
            margin: .25rem 0 0;
            color: var(--preview-muted);
            font-size: .8rem;
        }

        .social-links-admin {
            padding: 1rem;
            border: 1px solid rgba(15, 23, 42, .08);
            border-radius: 1.25rem;
            background:
                radial-gradient(circle at top right, rgba(37, 99, 235, .05), transparent 28%),
                #fff;
        }

        .social-links-admin__list {
            display: grid;
            gap: .85rem;
        }

        .social-link-row {
            display: grid;
            grid-template-columns: minmax(120px, .8fr) minmax(180px, 1.35fr) minmax(120px, .75fr) auto;
            gap: .75rem;
            align-items: end;
            padding: .85rem;
            border: 1px solid rgba(15, 23, 42, .08);
            border-radius: 1rem;
            background: rgba(248, 250, 252, .78);
        }

        .social-link-row__remove {
            width: 2.65rem;
            height: 2.65rem;
            border-radius: .85rem;
            display: inline-flex;
            align-items: center;
            justify-content: center;
        }

        .currencies-admin {
            padding: 1rem;
            border: 1px solid rgba(15, 23, 42, .08);
            border-radius: 1.25rem;
            background:
                radial-gradient(circle at top right, rgba(214, 162, 61, .08), transparent 30%),
                #fff;
        }

        .currencies-admin__list {
            display: grid;
            gap: .85rem;
        }

        .currency-row {
            display: grid;
            grid-template-columns: minmax(90px, .65fr) minmax(150px, 1fr) minmax(90px, .65fr) auto auto;
            gap: .75rem;
            align-items: end;
            padding: .85rem;
            border: 1px solid rgba(15, 23, 42, .08);
            border-radius: 1rem;
            background: rgba(248, 250, 252, .8);
        }

        .currency-row__base {
            min-height: 2.4rem;
            display: inline-flex;
            align-items: center;
            gap: .4rem;
            padding-bottom: .25rem;
            font-weight: 800;
            color: var(--settings-ink);
        }

        .currency-row__remove {
            width: 2.65rem;
            height: 2.65rem;
            border-radius: .85rem;
            display: inline-flex;
            align-items: center;
            justify-content: center;
        }

        .contact-channels-admin,
        .contact-emails-admin {
            padding: 1rem;
            border: 1px solid rgba(15, 23, 42, .08);
            border-radius: 1.25rem;
            background:
                radial-gradient(circle at top right, rgba(37, 99, 235, .05), transparent 28%),
                #fff;
        }

        .contact-people-list,
        .contact-emails-list {
            display: grid;
            gap: .85rem;
        }

        .contact-person-row {
            display: grid;
            grid-template-columns: 70px minmax(130px, .8fr) minmax(130px, .8fr) 95px minmax(120px, .8fr) minmax(160px, 1fr) auto;
            gap: .75rem;
            align-items: end;
            padding: .85rem;
            border: 1px solid rgba(15, 23, 42, .08);
            border-radius: 1rem;
            background: rgba(248, 250, 252, .82);
        }

        .contact-person-photo {
            width: 62px;
            height: 62px;
            align-self: center;
            overflow: hidden;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border-radius: 18px;
            background: linear-gradient(135deg, rgba(37, 99, 235, .12), rgba(214, 162, 61, .16));
            color: var(--settings-blue);
            font-size: 2rem;
        }

        .contact-person-photo img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .contact-email-row {
            display: grid;
            grid-template-columns: minmax(160px, .75fr) minmax(220px, 1fr) auto;
            gap: .75rem;
            align-items: end;
            padding: .85rem;
            border: 1px solid rgba(15, 23, 42, .08);
            border-radius: 1rem;
            background: rgba(248, 250, 252, .82);
        }

        .contact-person-row__remove,
        .contact-email-row__remove {
            width: 2.65rem;
            height: 2.65rem;
            border-radius: .85rem;
            display: inline-flex;
            align-items: center;
            justify-content: center;
        }

        .social-icon-select-option {
            display: inline-flex;
            align-items: center;
            gap: .55rem;
            font-weight: 700;
        }

        .social-icon-select-option .bi {
            width: 1.15rem;
            color: var(--settings-blue);
            font-size: 1.05rem;
            text-align: center;
        }

        .social-link-row .select2-container {
            width: 100% !important;
        }

        .social-link-row .select2-container--default .select2-selection--single {
            display: flex;
            align-items: center;
            min-height: 38px;
            border-color: #dee2e6;
            border-radius: .75rem;
        }

        .social-link-row .select2-container--default .select2-selection--single .select2-selection__rendered {
            width: 100%;
            padding-left: .75rem;
            color: #212529;
            line-height: 38px;
        }

        .social-link-row .select2-container--default .select2-selection--single .select2-selection__arrow {
            min-height: 38px;
        }

        .select2-dropdown {
            z-index: 2050;
        }

        @media (max-width: 991.98px) {
            .social-link-row {
                grid-template-columns: 1fr;
            }

            .currency-row {
                grid-template-columns: 1fr;
            }

            .contact-person-row,
            .contact-email-row {
                grid-template-columns: 1fr;
            }

            .social-link-row__remove {
                width: 100%;
            }

            .currency-row__remove {
                width: 100%;
            }

            .contact-person-row__remove,
            .contact-email-row__remove {
                width: 100%;
            }
        }

        .settings-hero::after {
            content: "";
            position: absolute;
            right: -90px;
            bottom: -110px;
            width: 360px;
            height: 230px;
            border-radius: 999px;
            background: rgba(255, 255, 255, .1);
            transform: rotate(-12deg);
        }

        .settings-hero-copy {
            position: relative;
            z-index: 1;
        }

        .settings-hero h1 {
            font-size: clamp(2.25rem, 5vw, 4rem);
            font-weight: 850;
            letter-spacing: -.05em;
        }

        .settings-hero p {
            max-width: 780px;
            color: rgba(255, 255, 255, .74);
        }

        .settings-eyebrow {
            display: inline-flex;
            margin-bottom: .45rem;
            color: #f6d48e;
            font-size: .72rem;
            font-weight: 850;
            letter-spacing: .16em;
            text-transform: uppercase;
        }

        .settings-shell {
            margin-top: 1.5rem;
        }

        .settings-stat-card {
            position: relative;
            overflow: hidden;
            min-height: 155px;
            padding: 1.2rem;
            border: 1px solid var(--settings-line);
            border-radius: 26px;
            background: #fff;
            box-shadow: 0 18px 45px rgba(15, 23, 42, .08);
        }

        .settings-stat-card::before {
            content: "";
            position: absolute;
            inset: 0;
            background: linear-gradient(135deg, rgba(37, 99, 235, .09), transparent 58%);
            pointer-events: none;
        }

        .settings-stat-card span,
        .settings-stat-card small {
            color: var(--settings-muted);
        }

        .settings-stat-card span,
        .settings-shell .form-label {
            font-size: .74rem;
            font-weight: 850;
            letter-spacing: .08em;
            text-transform: uppercase;
        }

        .settings-stat-card strong {
            position: relative;
            display: block;
            margin-top: .7rem;
            color: var(--settings-ink);
            font-size: 2rem;
            font-weight: 850;
            letter-spacing: -.05em;
            line-height: 1;
        }

        .settings-stat-card small {
            position: relative;
            display: block;
            margin-top: .45rem;
        }

        .settings-stat-card i {
            position: absolute;
            right: 1rem;
            bottom: .8rem;
            color: rgba(37, 99, 235, .14);
            font-size: 3.3rem;
        }

        .settings-stat-card.is-media::before,
        .settings-stat-card.is-money::before {
            background: linear-gradient(135deg, rgba(214, 162, 61, .14), transparent 58%);
        }

        .settings-stat-card.is-media i,
        .settings-stat-card.is-money i {
            color: rgba(214, 162, 61, .22);
        }

        .settings-stat-card.is-time::before {
            background: linear-gradient(135deg, rgba(22, 163, 74, .11), transparent 58%);
        }

        .settings-stat-card.is-time i {
            color: rgba(22, 163, 74, .18);
        }

        .settings-save-bar {
            position: sticky;
            top: .75rem;
            z-index: 10;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 1rem;
            margin-bottom: 1rem;
            padding: 1rem;
            border: 1px solid var(--settings-line);
            border-radius: 24px;
            background: rgba(255, 255, 255, .86);
            backdrop-filter: blur(14px);
            box-shadow: 0 16px 38px rgba(15, 23, 42, .08);
        }

        .settings-save-bar strong,
        .settings-save-bar span {
            display: block;
        }

        .settings-save-bar strong {
            color: var(--settings-ink);
        }

        .settings-save-bar span {
            color: var(--settings-muted);
        }

        .settings-save-btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: .55rem;
            min-height: 48px;
            padding: .75rem 1.15rem;
            border: 0;
            border-radius: 999px;
            color: #172033;
            background: linear-gradient(135deg, #f8d58d, #f4b740);
            font-weight: 850;
            box-shadow: 0 16px 32px rgba(214, 162, 61, .24);
        }

        .settings-save-btn:hover {
            color: #172033;
            transform: translateY(-1px);
            box-shadow: 0 20px 38px rgba(214, 162, 61, .3);
        }

        .settings-shell .card {
            border: 1px solid var(--settings-line);
            border-radius: 26px;
            box-shadow: 0 18px 45px rgba(15, 23, 42, .07);
        }

        .settings-shell .card-header {
            border-bottom: 1px solid var(--settings-line);
            border-top-left-radius: 26px;
            border-top-right-radius: 26px;
            background: linear-gradient(135deg, #f8fafc, #fff);
        }

        .settings-shell .card-title {
            color: var(--settings-ink);
            font-weight: 850;
        }

        .settings-shell .form-control,
        .settings-shell .form-select {
            min-height: 42px;
            border-radius: 14px;
            border-color: rgba(15, 23, 42, .12);
        }

        .settings-shell textarea.form-control {
            min-height: auto;
        }

        .media-config-card {
            border: 1px solid var(--settings-line);
            border-radius: 22px;
            background: linear-gradient(135deg, #f8fafc, #fff);
            padding: 1rem;
            min-height: 100%;
            box-shadow: 0 12px 28px rgba(15, 23, 42, .06);
        }

        .media-preview {
            min-height: 140px;
            border: 1px dashed rgba(15, 23, 42, .16);
            border-radius: 18px;
            padding: 0.85rem;
            display: flex;
            align-items: center;
            justify-content: center;
            background: rgba(248, 250, 252, .9);
        }

        .media-preview img,
        .media-preview video {
            box-shadow: 0 12px 28px rgba(15, 23, 42, .14);
        }

        .media-preview--circle {
            width: 156px;
            height: 156px;
            min-height: 156px;
            margin-inline: auto;
            border-radius: 50%;
            padding: .45rem;
            overflow: hidden;
            background:
                radial-gradient(circle at 30% 20%, rgba(214, 162, 61, .18), transparent 42%),
                linear-gradient(135deg, rgba(44, 20, 88, .08), rgba(255, 255, 255, .95));
            box-shadow:
                inset 0 0 0 1px rgba(15, 23, 42, .06),
                0 18px 36px rgba(15, 23, 42, .12);
        }

        .media-preview--circle img {
            width: 100%;
            height: 100%;
            border-radius: 50%;
            object-fit: cover;
            object-position: center;
        }

        .media-preview--favicon {
            width: 128px;
            height: 128px;
            min-height: 128px;
        }

        .media-preview--circle [data-preview-empty] {
            max-width: 92px;
            font-size: .78rem;
            font-weight: 800;
            line-height: 1.25;
        }

        @media (max-width: 991.98px) {
            .settings-hero,
            .settings-save-bar {
                align-items: stretch;
                flex-direction: column;
            }

            .settings-save-btn {
                width: 100%;
            }

        }

        @media (max-width: 575.98px) {
            .settings-hero,
            .settings-stat-card,
            .settings-save-bar,
            .settings-shell .card {
                border-radius: 22px;
            }

            .settings-hero {
                padding: 1.1rem;
            }
        }
    </style>
@endpush
