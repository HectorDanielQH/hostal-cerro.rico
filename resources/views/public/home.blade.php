@extends('public.layouts.app')

@section('content')
    @php
        $coverImage = $hotelSetting->cover_image ? asset('storage/'.$hotelSetting->cover_image) : null;
        $heroVideo = $hotelSetting->hero_video ? asset('storage/'.$hotelSetting->hero_video) : null;
        $heroYoutubeEmbedUrl = $hotelSetting->youtubeHeroEmbedUrl();
        $mobileHeroImage = $hotelSetting->mobile_hero_image ? asset('storage/'.$hotelSetting->mobile_hero_image) : $coverImage;
        $logoImage = $hotelSetting->logo ? asset('storage/'.$hotelSetting->logo) : null;
        $whatsAppUrl = collect($hotelSetting->publicContactPeople())->first()['whatsapp_url'] ?? null;
        $googleMapsPublicUrl = $hotelSetting->googleMapsPublicUrl();
        $heroCheckIn = now()->toDateString();
        $heroCheckOut = now()->addDay()->toDateString();
    @endphp

    @if (($heroVideo || $heroYoutubeEmbedUrl) && ! $mobileHeroImage)
        <div class="page-loader" data-hero-loader>
            <div class="hero-loader-inner">
                <span class="hero-loader-mark"></span>
                <span class="hero-loader-line">Preparando la experiencia</span>
            </div>
        </div>
    @endif

    @if ($activeAnnouncements->isNotEmpty())
        <div class="modal fade public-announcement-modal" id="public-announcements-modal" tabindex="-1" aria-hidden="true" data-public-announcements-modal>
            <div class="modal-dialog modal-dialog-centered modal-xl">
                <div class="modal-content">
                    <div class="modal-body p-0">
                        <button type="button" class="btn-close public-announcement-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                        <div id="public-announcements-carousel" class="carousel slide" data-bs-ride="carousel" data-bs-interval="7000">
                            @if ($activeAnnouncements->count() > 1)
                                <div class="carousel-indicators">
                                    @foreach ($activeAnnouncements as $announcement)
                                        <button type="button" data-bs-target="#public-announcements-carousel" data-bs-slide-to="{{ $loop->index }}" class="{{ $loop->first ? 'active' : '' }}" aria-current="{{ $loop->first ? 'true' : 'false' }}" aria-label="Anuncio {{ $loop->iteration }}"></button>
                                    @endforeach
                                </div>
                            @endif
                            <div class="carousel-inner">
                                @foreach ($activeAnnouncements as $announcement)
                                    <div class="carousel-item {{ $loop->first ? 'active' : '' }}">
                                        <div class="public-announcement-card">
                                            <div class="public-announcement-media">
                                                @if ($announcement->image)
                                                    <img src="{{ asset('storage/'.$announcement->image) }}" alt="{{ $announcement->title }}">
                                                @else
                                                    <div class="public-announcement-placeholder"></div>
                                                @endif
                                            </div>
                                            <div class="public-announcement-copy">
                                                <span class="public-announcement-kicker">Anuncio especial</span>
                                                <h2>{{ $announcement->title }}</h2>
                                                @if ($announcement->subtitle)
                                                    <p class="public-announcement-subtitle">{{ $announcement->subtitle }}</p>
                                                @endif
                                                @if ($announcement->content)
                                                    <div class="public-announcement-content">{{ $announcement->content }}</div>
                                                @endif
                                                <div class="public-announcement-actions">
                                                    @if ($announcement->button_label && $announcement->button_url)
                                                        <a href="{{ $announcement->button_url }}" class="btn btn-public-primary" target="_blank" rel="noopener">{{ $announcement->button_label }}</a>
                                                    @endif
                                                    <button type="button" class="btn btn-public-ghost" data-bs-dismiss="modal">{{ __('public.booking.next') }}</button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                            @if ($activeAnnouncements->count() > 1)
                                <button class="carousel-control-prev" type="button" data-bs-target="#public-announcements-carousel" data-bs-slide="prev">
                                    <span class="carousel-control-prev-icon" aria-hidden="true"></span>
                                    <span class="visually-hidden">Anterior</span>
                                </button>
                                <button class="carousel-control-next" type="button" data-bs-target="#public-announcements-carousel" data-bs-slide="next">
                                    <span class="carousel-control-next-icon" aria-hidden="true"></span>
                                    <span class="visually-hidden">Siguiente</span>
                                </button>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @endif

    <section class="hero-section" style="{{ $coverImage ? 'background-image: linear-gradient(180deg, rgba(18, 30, 29, 0.28), rgba(18, 30, 29, 0.72)), url('.$coverImage.');' : '' }}">
        @if ($heroVideo || $heroYoutubeEmbedUrl || $mobileHeroImage)
            <div class="hero-media" data-hero-media @if($mobileHeroImage) data-mobile-hero-image="true" @endif>
                @if ($mobileHeroImage)
                    <img
                        src="{{ $mobileHeroImage }}"
                        alt="{{ $hotelSetting->hotel_name }}"
                        class="hero-mobile-image"
                        data-hero-mobile-image
                        loading="eager"
                        fetchpriority="high"
                    >
                @endif
                @if ($heroVideo)
                    <video class="hero-video" data-hero-video autoplay muted loop playsinline preload="none" poster="{{ $mobileHeroImage ?: $coverImage }}">
                        <source data-src="{{ $heroVideo }}">
                    </video>
                @elseif ($heroYoutubeEmbedUrl)
                    <iframe
                        class="hero-youtube-frame"
                        data-hero-iframe
                        data-src="{{ $heroYoutubeEmbedUrl }}"
                        title="{{ $hotelSetting->hotel_name }}"
                        allow="autoplay; encrypted-media; picture-in-picture"
                        referrerpolicy="strict-origin-when-cross-origin"
                    ></iframe>
                @endif
            </div>
        @endif
        <div class="container hero-shell hero-shell-atg">
            <div class="hero-stage">
                <div class="hero-copy hero-copy-atg" data-reveal>
                    <span class="hero-kicker">{{ __('public.home.hero_kicker', ['city' => $hotelSetting->city ?: 'Potosi']) }}</span>
                    <div class="hero-brand-lockup">
                        <span class="hero-brand-mark">
                            @if ($logoImage)
                                <img src="{{ $logoImage }}" alt="{{ $hotelSetting->hotel_name }}">
                            @else
                                <span class="brand-fallback">{{ \Illuminate\Support\Str::upper(\Illuminate\Support\Str::substr($hotelSetting->hotel_name, 0, 1)) }}</span>
                            @endif
                        </span>
                        <div class="hero-brand-copy">
                            <h1>{{ $hotelSetting->hotel_name ?: 'Hostal Cerro Rico' }}</h1>
                        </div>
                    </div>
                    <p>{{ $hotelSetting->slogan ?: ($hotelSetting->description_short ?: 'Descansa con comodidad, ubicacion estrategica y atencion cercana en el corazon de la ciudad.') }}</p>
                    <div class="hero-actions">
                        <a href="{{ route('public.booking.create') }}" class="btn btn-public-primary">{{ __('public.layout.nav.book_now') }}</a>
                        <a href="{{ route('public.customer-portal.search') }}" class="btn btn-booking-status hero-booking-status-button">{{ __('public.layout.nav.check_booking') }}</a>
                        <a href="{{ route('public.rooms.index') }}" class="btn btn-public-outline hero-view-rooms-button">{{ __('public.home.view_rooms') }}</a>
                    </div>
                    <div class="hero-facts">
                        <div class="hero-fact">
                            <strong>{{ $featuredRoomTypes->count() > 0 ? $featuredRoomTypes->count() : '5+' }}</strong>
                            <span>{{ __('public.home.hero_fact_room_types') }}</span>
                        </div>
                        <div class="hero-fact">
                            <strong>{{ $activePromotions->count() > 0 ? $activePromotions->count() : '24/7' }}</strong>
                            <span>{{ $activePromotions->count() > 0 ? __('public.home.hero_fact_active_promotions') : __('public.home.hero_fact_personalized_service') }}</span>
                        </div>
                    </div>
                </div>

                <div class="hero-booking-side" data-reveal>
                    <div class="hero-booking-panel hero-booking-panel-atg">
                        <div class="hero-booking-intro">
                            <span class="section-kicker">{{ __('public.home.booking_kicker') }}</span>
                            <h2>{{ __('public.home.booking_title') }}</h2>
                            <p class="hero-search-note">{{ __('public.home.hero_search_note') }}</p>
                        </div>
                        <form action="{{ route('public.booking.create') }}" method="GET" class="hero-booking-form hero-booking-form-stack">
                            <div class="hero-booking-grid">
                                <div>
                                    <label for="hero-check-in">{{ __('public.home.check_in_short') }}</label>
                                    <input id="hero-check-in" type="date" name="check_in" value="{{ $heroCheckIn }}" min="{{ $heroCheckIn }}" class="form-control" required>
                                </div>
                                <div>
                                    <label for="hero-check-out">{{ __('public.home.check_out_short') }}</label>
                                    <input id="hero-check-out" type="date" name="check_out" value="{{ $heroCheckOut }}" min="{{ $heroCheckOut }}" class="form-control" required>
                                </div>
                                <div>
                                    <label for="hero-adults">{{ __('public.booking.adults_label') }}</label>
                                    <input id="hero-adults" type="number" name="adults" value="2" min="1" max="20" class="form-control" required>
                                </div>
                                <div>
                                    <label for="hero-children">{{ __('public.booking.children_label') }}</label>
                                    <input id="hero-children" type="number" name="children" value="0" min="0" max="20" class="form-control">
                                </div>
                            </div>
                            <button type="submit" class="btn btn-public-primary hero-booking-submit">{{ __('public.layout.nav.book_now') }}</button>
                        </form>
                        <div class="hero-insight-card">
                            <span>{{ __('public.home.hero_insight_kicker') }}</span>
                            <strong>{{ __('public.home.hero_insight_text') }}</strong>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    {{--
    <section class="section-block section-overlap">
        <div class="container">
            <div class="experience-ribbon signature-ribbon" data-reveal>
                <div class="signature-copy">
                    <span class="section-kicker">{{ __('public.home.experience_kicker') }}</span>
                    <h2>{{ __('public.home.experience_title') }}</h2>
                    <p>{{ $hotelSetting->description_long ?: __('public.home.experience_default_copy') }}</p>
                    <div class="signature-points">
                        <span><i class="bi bi-check2-circle"></i> {{ __('public.home.experience_point_1') }}</span>
                        <span><i class="bi bi-wallet2"></i> {{ __('public.home.experience_point_2') }}</span>
                        <span><i class="bi bi-translate"></i> {{ __('public.home.experience_point_3') }}</span>
                    </div>
                </div>
                <div class="signature-media">
                    @if ($coverImage)
                        <img src="{{ $coverImage }}" alt="{{ $hotelSetting->hotel_name }}">
                    @elseif ($logoImage)
                        <img src="{{ $logoImage }}" alt="{{ $hotelSetting->hotel_name }}">
                    @else
                        <div class="placeholder-tile large"><i class="bi bi-stars"></i></div>
                    @endif
                    <div class="signature-media-card">
                        <span>Diseño orientado a conversion</span>
                        <strong>Reserva, confirma y acompana al huesped con una experiencia mas premium.</strong>
                    </div>
                </div>
            </div>

            <div class="feature-banner-grid">
                <article class="feature-banner feature-banner-dark" data-reveal>
                    <span class="section-kicker">{{ __('public.home.feature_1_kicker') }}</span>
                    <h3>{{ __('public.home.feature_1_title') }}</h3>
                    <p>{{ __('public.home.feature_1_text') }}</p>
                </article>
                <article class="feature-banner" data-reveal>
                    <span class="section-kicker">{{ __('public.home.feature_2_kicker') }}</span>
                    <h3>{{ __('public.home.feature_2_title') }}</h3>
                </article>
                <article class="feature-banner" data-reveal>
                    <span class="section-kicker">{{ __('public.home.feature_3_kicker') }}</span>
                    <h3>{{ __('public.home.feature_3_title') }}</h3>
                </article>
            </div>

            <div class="editorial-grid">
                <article class="editorial-card editorial-card-large" data-reveal>
                    <span class="section-kicker">{{ __('public.home.why_choose_kicker') }}</span>
                    <h3>{{ __('public.home.why_choose_title') }}</h3>
                    <p>{{ __('public.home.why_choose_text') }}</p>
                    <div class="editorial-points">
                        <span><i class="bi bi-stars"></i> {{ __('public.home.why_choose_point_1') }}</span>
                        <span><i class="bi bi-phone"></i> {{ __('public.home.why_choose_point_2') }}</span>
                        <span><i class="bi bi-calendar-check"></i> {{ __('public.home.why_choose_point_3') }}</span>
                    </div>
                </article>
                <article class="editorial-card" data-reveal>
                    <i class="bi bi-geo-alt"></i>
                    <h3>{{ __('public.home.editorial_2_title') }}</h3>
                    <p>{{ __('public.home.editorial_2_text') }}</p>
                </article>
                <article class="editorial-card" data-reveal>
                    <i class="bi bi-moon-stars"></i>
                    <h3>{{ __('public.home.editorial_3_title') }}</h3>
                    <p>{{ __('public.home.editorial_3_text') }}</p>
                </article>
                <article class="editorial-card" data-reveal>
                    <i class="bi bi-person-hearts"></i>
                    <h3>{{ __('public.home.editorial_4_title') }}</h3>
                    <p>{{ __('public.home.editorial_4_text') }}</p>
                </article>
                <article class="editorial-card" data-reveal>
                    <i class="bi bi-lightning-charge"></i>
                    <h3>{{ __('public.home.editorial_5_title') }}</h3>
                    <p>{{ __('public.home.editorial_5_text') }}</p>
                </article>
            </div>
        </div>
    </section>

    --}}

    <section class="section-block section-soft">
        <div class="container">
            <div class="section-heading section-heading-split">
                <div>
                    <span class="section-kicker">{{ __('public.home.featured_rooms_kicker') }}</span>
                    <h2>{{ __('public.home.featured_rooms_title') }}</h2>
                </div>
                <a href="{{ route('public.rooms.index') }}" class="btn btn-public-outline">{{ __('public.home.view_all_rooms') }}</a>
            </div>
            <div class="showcase-grid">
                @forelse ($featuredRoomTypes as $roomType)
                    @php
                        $galleryImages = $roomType->publicGalleryImages();
                    @endphp
                    <article class="public-card showcase-card" data-reveal>
                        <div class="showcase-media">
                            @if (count($galleryImages))
                                <div class="public-room-gallery" data-public-room-gallery>
                                    @foreach ($galleryImages as $image)
                                        <img src="{{ asset('storage/'.$image) }}" alt="{{ $roomType->name }}" @class(['is-active' => $loop->first])>
                                    @endforeach
                                </div>
                            @else
                                <div class="placeholder-tile"><i class="bi bi-image"></i></div>
                            @endif
                            <div class="showcase-overlay">
                                <span class="price-chip">Bs. {{ number_format((float) $roomType->public_final_price, 2, '.', '') }}</span>
                            </div>
                        </div>
                        <div class="public-card-body">
                            <div class="showcase-header">
                                <div>
                                    <h3>{{ $roomType->name }}</h3>
                                    <p>{{ \Illuminate\Support\Str::limit($roomType->description ?: __('public.home.room_card_fallback'), 120) }}</p>
                                </div>
                            </div>
                            <div class="small text-muted mb-3">{{ __('public.home.reference_price') }}: Bs. {{ number_format((float) ($roomType->price_bob ?? $roomType->base_price), 2, '.', '') }} / $us {{ number_format((float) ($roomType->price_usd ?? 0), 2, '.', '') }}</div>
                            <div class="room-meta">
                                <span><i class="bi bi-people"></i> {{ __('public.home.max_guests', ['count' => $roomType->max_guests]) }}</span>
                                <span><i class="bi bi-door-open"></i> {{ __('public.home.available_count', ['count' => $roomType->available_rooms_count]) }}</span>
                            </div>
                            @if ($roomType->public_promotion)
                                <div class="promo-badge mt-3">{{ __('public.home.active_promo') }}: {{ $roomType->public_promotion->name }}</div>
                            @endif
                            <div class="showcase-actions">
                                <a href="{{ route('public.booking.create', ['room_type_id' => $roomType->id]) }}" class="btn btn-public-primary">{{ __('public.home.book') }}</a>
                                <a href="{{ route('public.rooms.show', $roomType) }}" class="btn btn-public-ghost">{{ __('public.home.detail') }}</a>
                            </div>
                        </div>
                    </article>
                @empty
                    <div class="empty-state-public">{{ __('public.home.rooms_empty') }}</div>
                @endforelse
            </div>
        </div>
    </section>

    <section class="section-block">
        <div class="container">
            <div class="section-heading section-heading-split">
                <div>
                    <span class="section-kicker">{{ __('public.home.promotions_kicker') }}</span>
                    <h2>{{ __('public.home.promotions_title') }}</h2>
                </div>
                <a href="{{ route('public.promotions.index') }}" class="btn btn-public-outline">{{ __('public.home.view_promotions') }}</a>
            </div>
            <div class="mosaic-grid">
                @forelse ($activePromotions as $promotion)
                    <article class="mosaic-card" data-reveal>
                        <span class="promo-badge">{{ $promotion->discount_type === 'percentage' ? rtrim(rtrim(number_format((float) $promotion->discount_value, 2, '.', ''), '0'), '.').'%' : 'Bs. '.number_format((float) $promotion->discount_value, 2, '.', '') }} {{ __('public.home.discount_suffix') }}</span>
                        <h3>{{ $promotion->name }}</h3>
                        <p>{{ $promotion->description ?: __('public.home.promotion_fallback') }}</p>
                        <div class="promotion-dates">
                            {{ __('public.promotions.validity') }}:
                            {{ $promotion->starts_at ? $promotion->starts_at->format('d/m/Y') : __('public.promotions.immediate') }}
                            -
                            {{ $promotion->ends_at ? $promotion->ends_at->format('d/m/Y') : __('public.promotions.no_limit') }}
                        </div>
                    </article>
                @empty
                    <div class="empty-state-public">{{ __('public.promotions.empty') }}</div>
                @endforelse
            </div>

            <div class="booking-split-callout" data-reveal>
                <div class="booking-split-copy">
                    <span class="section-kicker">{{ __('public.home.booking_callout_kicker') }}</span>
                    <h2>{{ __('public.home.booking_callout_title') }}</h2>
                    <p>{{ __('public.home.booking_callout_text') }}</p>
                </div>
                <div class="booking-split-actions">
                    <a href="{{ route('public.booking.create') }}" class="btn btn-public-primary">{{ __('public.home.start_booking') }}</a>
                    @if ($whatsAppUrl)
                        <a href="{{ $whatsAppUrl }}" target="_blank" rel="noopener" class="btn btn-public-ghost">{{ __('public.home.contact_whatsapp') }}</a>
                    @endif
                </div>
            </div>
        </div>
    </section>

    <section class="section-block">
        <div class="container">
            <div class="luxury-quote-panel" data-reveal>
                <div class="luxury-quote-copy">
                    <span class="section-kicker">{{ __('public.home.presentation_kicker') }}</span>
                    <h2>{{ __('public.home.presentation_title') }}</h2>
                    <p>{{ __('public.home.presentation_text') }}</p>
                </div>
                <div class="luxury-quote-badge">
                    <strong>100%</strong>
                    <span>{{ __('public.home.presentation_badge') }}</span>
                </div>
            </div>
        </div>
    </section>

    <section class="section-block">
        <div class="container">
            <div class="contact-panel premium-contact-panel" data-reveal>
                <div>
                    <span class="section-kicker">{{ __('public.home.quick_contact_kicker') }}</span>
                    <h2>{{ __('public.home.quick_contact_title') }}</h2>
                    <div class="contact-grid">
                        @if ($hotelSetting->whatsapp)<div><strong>{{ __('public.contact.whatsapp') }}</strong><span>{{ $hotelSetting->whatsapp }}</span></div>@endif
                        @if ($hotelSetting->phone)<div><strong>{{ __('public.contact.phone') }}</strong><span>{{ $hotelSetting->phone }}</span></div>@endif
                        @if ($hotelSetting->email)<div><strong>{{ __('public.contact.email') }}</strong><span>{{ $hotelSetting->email }}</span></div>@endif
                        <div><strong>{{ __('public.home.address_label') }}</strong><span>{{ trim(collect([$hotelSetting->address, $hotelSetting->city, $hotelSetting->country])->filter()->implode(', ')) ?: 'Potosi, Bolivia' }}</span></div>
                    </div>
                </div>
                <div class="contact-actions">
                    <a href="{{ route('public.booking.create') }}" class="btn btn-public-primary">{{ __('public.home.book_online') }}</a>
                    @if ($whatsAppUrl)
                        <a href="{{ $whatsAppUrl }}" target="_blank" rel="noopener" class="btn btn-public-ghost">{{ __('public.contact.whatsapp') }}</a>
                    @endif
                </div>
            </div>
        </div>
    </section>

    <section class="section-block cta-strip">
        <div class="container cta-strip-inner">
            <div>
                <span class="section-kicker">{{ __('public.home.cta_kicker') }}</span>
                <h2>{{ __('public.home.cta_title') }}</h2>
            </div>
            <div class="hero-actions">
                <a href="{{ route('public.booking.create') }}" class="btn btn-public-primary">{{ __('public.home.start_booking') }}</a>
                @if ($whatsAppUrl)
                    <a href="{{ $whatsAppUrl }}" target="_blank" rel="noopener" class="btn btn-public-outline">{{ __('public.rooms.book_whatsapp') }}</a>
                @endif
            </div>
        </div>
    </section>
@endsection
