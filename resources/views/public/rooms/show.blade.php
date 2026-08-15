@extends('public.layouts.app')

@section('content')
    @php
        $whatsapp = preg_replace('/\D+/', '', (string) ($hotelSetting->whatsapp ?? ''));
        if ($whatsapp !== '' && ! str_starts_with($whatsapp, '591')) {
            $whatsapp = '591'.$whatsapp;
        }
        $whatsAppUrl = $whatsapp !== ''
            ? 'https://wa.me/'.$whatsapp.'?text='.rawurlencode('Hola, quiero consultar disponibilidad de '.$roomType->name.' en '.$hotelSetting->hotel_name.'.')
            : null;
        $galleryImages = $roomType->publicGalleryImages();
        $promotionLabel = $roomType->public_promotion
            ? ($roomType->public_promotion->discount_type === 'percentage'
                ? rtrim(rtrim(number_format((float) $roomType->public_promotion->discount_value, 2, '.', ''), '0'), '.').'%'
                : 'Descuento directo')
            : null;
    @endphp

    <section class="page-hero-simple page-hero-room">
        <div class="container">
            <span class="section-kicker">{{ __('public.rooms.detail_kicker') }}</span>
            <h1>{{ $roomType->name }}</h1>
            <p>{{ $roomType->description ?: __('public.rooms.detail_fallback_description') }}</p>
        </div>
    </section>

    <section class="section-block">
        <div class="container">
            <div class="row g-5 align-items-start">
                <div class="col-lg-7" data-reveal>
                    <div class="detail-visual">
                        @if (count($galleryImages))
                            <div class="public-room-gallery public-room-gallery--detail" data-public-room-gallery>
                                @foreach ($galleryImages as $image)
                                    <img src="{{ asset('storage/'.$image) }}" alt="{{ $roomType->name }}" @class(['is-active' => $loop->first])>
                                @endforeach
                            </div>
                        @else
                            <div class="placeholder-tile large"><i class="bi bi-image"></i></div>
                        @endif
                    </div>
                </div>
                <div class="col-lg-5" data-reveal>
                    <div class="detail-panel room-detail-panel">
                        <div class="price-block">
                            <span class="section-kicker">Reserva guiada</span>
                            <div class="detail-price">Consulta disponibilidad</div>
                            @if ($roomType->public_promotion)
                                <div class="promo-badge">{{ __('public.rooms.promo_active') }}: {{ $roomType->public_promotion->name }}</div>
                                <div class="small text-muted mt-2">Oferta: {{ $promotionLabel }}</div>
                            @endif
                        </div>
                        <div class="detail-meta">
                            <div><strong>{{ __('public.rooms.adults') }}:</strong> {{ $roomType->capacity_adults }}</div>
                            <div><strong>{{ __('public.rooms.children') }}:</strong> {{ $roomType->capacity_children }}</div>
                            <div><strong>{{ __('public.rooms.max_guests_label') }}:</strong> {{ $roomType->max_guests }}</div>
                            <div><strong>{{ __('public.rooms.available_rooms') }}:</strong> {{ $roomType->available_rooms_count }}</div>
                        </div>
                        <div class="hero-actions mt-4">
                            <a href="{{ route('public.rooms.index') }}" class="btn btn-public-primary">Ver habitaciones disponibles</a>
                            @if ($whatsAppUrl)
                                <a href="{{ $whatsAppUrl }}" target="_blank" rel="noopener" class="btn btn-public-outline">{{ __('public.rooms.book_whatsapp') }}</a>
                            @endif
                            <a href="{{ route('public.contact') }}" class="btn btn-public-outline">{{ __('public.rooms.check_availability') }}</a>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row g-4 mt-2">
                <div class="col-lg-8" data-reveal>
                    <div class="content-panel">
                        <h2>{{ __('public.rooms.description_title') }}</h2>
                        <p>{{ $roomType->description ?: __('public.rooms.description_fallback') }}</p>
                    </div>
                </div>
                <div class="col-lg-4" data-reveal>
                    <div class="content-panel">
                        <h2>{{ __('public.rooms.amenities') }}</h2>
                        @if (is_array($roomType->amenities) && count($roomType->amenities))
                            <div class="amenities-grid">
                                @foreach ($roomType->amenities as $amenity)
                                    <span>{{ $amenity }}</span>
                                @endforeach
                            </div>
                        @else
                            <p class="mb-0 text-muted">{{ __('public.rooms.amenities_help') }}</p>
                        @endif
                    </div>
                </div>
            </div>

            @if ($applicablePromotions->isNotEmpty())
                <div class="content-panel mt-4" data-reveal>
                    <h2>{{ __('public.rooms.promotions') }}</h2>
                    <div class="row g-3">
                        @foreach ($applicablePromotions as $promotion)
                            <div class="col-md-6">
                                <div class="promotion-inline-card">
                                    <strong>{{ $promotion->name }}</strong>
                                    <p class="mb-2">{{ $promotion->description ?: __('public.rooms.promotion_fallback') }}</p>
                                    <span class="promo-badge">
                                        {{ $promotion->discount_type === 'percentage' ? rtrim(rtrim(number_format((float) $promotion->discount_value, 2, '.', ''), '0'), '.').'%' : 'Descuento directo' }} {{ __('public.rooms.discount_suffix') }}
                                    </span>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif
        </div>
    </section>
@endsection
