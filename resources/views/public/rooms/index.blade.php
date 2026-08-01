@extends('public.layouts.app')

@section('content')
    @php
        $whatsapp = preg_replace('/\D+/', '', (string) ($hotelSetting->whatsapp ?? ''));
        if ($whatsapp !== '' && ! str_starts_with($whatsapp, '591')) {
            $whatsapp = '591'.$whatsapp;
        }
        $availableCount = (int) $roomTypes->sum('available_rooms_count');
        $startingPrice = $roomTypes->min('public_final_price');
    @endphp

    <section class="page-hero-simple page-hero-rooms-atg">
        <div class="container">
            <div class="rooms-hero-grid">
                <div class="rooms-hero-copy" data-reveal>
                    <span class="section-kicker">{{ __('public.rooms.kicker') }}</span>
                    <h1>{{ __('public.rooms.title') }}</h1>
                    <p>{{ __('public.rooms.description') }}</p>
                    <div class="rooms-hero-actions">
                        <a href="{{ route('public.booking.create') }}" class="btn btn-public-primary">{{ __('public.layout.nav.book_now') }}</a>
                        <a href="{{ route('public.contact') }}" class="btn btn-public-outline">{{ __('public.layout.nav.contact') }}</a>
                    </div>
                </div>
                <div class="rooms-hero-aside" data-reveal>
                    <div class="rooms-hero-stats">
                        <div class="rooms-hero-stat">
                            <strong>{{ $roomTypes->count() }}</strong>
                            <span>{{ __('public.rooms.available_types') }}</span>
                        </div>
                        <div class="rooms-hero-stat">
                            <strong>{{ $availableCount }}</strong>
                            <span>{{ __('public.rooms.free_rooms') }}</span>
                        </div>
                        <div class="rooms-hero-stat">
                            <strong>{{ $startingPrice ? 'Bs. '.number_format((float) $startingPrice, 0, '.', '') : '--' }}</strong>
                            <span>{{ __('public.rooms.reference_rate_short') }}</span>
                        </div>
                    </div>
                    <div class="rooms-hero-note">
                        <span>{{ __('public.rooms.guided_booking') }}</span>
                        <strong>{{ __('public.rooms.compare_summary') }}</strong>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="section-block">
        <div class="container">
            <div class="section-heading section-heading-split rooms-list-heading" data-reveal>
                <div>
                    <span class="section-kicker">{{ __('public.rooms.collection_kicker') }}</span>
                    <h2>{{ __('public.rooms.collection_title') }}</h2>
                </div>
                <div class="rooms-heading-copy">
                    <p>{{ __('public.rooms.collection_description') }}</p>
                </div>
            </div>

            <div class="rooms-editorial-list">
                @forelse ($roomTypes as $roomType)
                    @php
                        $promotion = $roomType->public_promotion;
                        $galleryImages = $roomType->publicGalleryImages();
                        $whatsAppUrl = $whatsapp !== ''
                            ? 'https://wa.me/'.$whatsapp.'?text='.rawurlencode('Hola, quiero consultar disponibilidad de '.$roomType->name.' en '.$hotelSetting->hotel_name.'.')
                            : null;
                    @endphp
                    <article class="public-card room-editorial-card" data-reveal>
                        <div class="room-editorial-media">
                            @if (count($galleryImages))
                                <div class="public-room-gallery" data-public-room-gallery>
                                    @foreach ($galleryImages as $image)
                                        <img src="{{ asset('storage/'.$image) }}" alt="{{ $roomType->name }}" @class(['is-active' => $loop->first])>
                                    @endforeach
                                </div>
                            @else
                                <div class="placeholder-tile h-100"><i class="bi bi-image"></i></div>
                            @endif
                            <div class="room-editorial-overlay">
                                <span class="price-chip">Bs. {{ number_format((float) $roomType->public_final_price, 2, '.', '') }}</span>
                                @if ($promotion)
                                    <span class="promo-badge">{{ __('public.rooms.promo_active') }}</span>
                                @endif
                            </div>
                        </div>
                        <div class="room-editorial-body">
                            <div class="room-editorial-top">
                                <div>
                                    <span class="room-editorial-kicker">{{ __('public.rooms.room_type_label') }}</span>
                                    <h2>{{ $roomType->name }}</h2>
                                </div>
                                <div class="room-editorial-availability">
                                    <strong>{{ $roomType->available_rooms_count }}</strong>
                                    <span>{{ __('public.rooms.available_short') }}</span>
                                </div>
                            </div>

                            <p class="room-editorial-description">{{ \Illuminate\Support\Str::limit($roomType->description ?: __('public.rooms.card_fallback_description'), 180) }}</p>

                            <div class="room-editorial-prices">
                                <div>
                                    <span>{{ __('public.rooms.price_registered') }}</span>
                                    <strong>Bs. {{ number_format((float) ($roomType->price_bob ?? $roomType->base_price), 2, '.', '') }}</strong>
                                </div>
                                <div>
                                    <span>{{ __('public.rooms.usd_rate') }}</span>
                                    <strong>$us {{ number_format((float) ($roomType->price_usd ?? 0), 2, '.', '') }}</strong>
                                </div>
                                <div>
                                    <span>{{ __('public.rooms.deposit_to_confirm') }}</span>
                                    <strong>{{ (int) ($roomType->reservation_deposit_percentage ?? 20) }}%</strong>
                                </div>
                            </div>

                            <div class="room-meta room-meta-atg">
                                <span><i class="bi bi-person"></i> {{ $roomType->capacity_adults }} {{ __('public.rooms.adults') }}</span>
                                <span><i class="bi bi-emoji-smile"></i> {{ $roomType->capacity_children }} {{ __('public.rooms.children') }}</span>
                                <span><i class="bi bi-people"></i> Max. {{ $roomType->max_guests }}</span>
                                <span><i class="bi bi-door-open"></i> {{ $roomType->available_rooms_count }} {{ __('public.rooms.available_short') }}</span>
                            </div>

                            @if ($promotion)
                                <div class="promo-badge room-editorial-promo">
                                    {{ $promotion->name }}: ahorro de Bs. {{ number_format((float) $roomType->public_discount_amount, 2, '.', '') }}
                                </div>
                            @endif

                            @if (is_array($roomType->amenities) && count($roomType->amenities))
                                <div class="amenities-row room-editorial-amenities">
                                    @foreach ($roomType->amenities as $amenity)
                                        <span>{{ $amenity }}</span>
                                    @endforeach
                                </div>
                            @endif

                            <div class="room-editorial-actions">
                                <a href="{{ route('public.booking.create', ['room_type_id' => $roomType->id]) }}" class="btn btn-public-primary">{{ __('public.rooms.book') }}</a>
                                <a href="{{ route('public.rooms.show', $roomType) }}" class="btn btn-public-outline">{{ __('public.rooms.details') }}</a>
                                @if ($whatsAppUrl)
                                    <a href="{{ $whatsAppUrl }}" target="_blank" rel="noopener" class="btn btn-public-ghost">{{ __('public.rooms.book_whatsapp') }}</a>
                                @endif
                            </div>
                        </div>
                    </article>
                @empty
                    <div class="empty-state-public">{{ __('public.rooms.empty') }}</div>
                @endforelse
            </div>
        </div>
    </section>
@endsection
