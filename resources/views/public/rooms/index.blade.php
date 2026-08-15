@extends('public.layouts.app')

@section('content')
    @php
        $availableRoomGroups = $availableRoomsByType ?? collect();
        $availableCount = (int) ($availableRooms ?? collect())->count();
        $activePromotionCount = $availableRoomGroups->filter(fn ($group) => $group['roomType']?->public_promotion)->count();
        $cleanPublicText = fn (?string $text, string $fallback): string => trim((string) preg_replace(
            '/(?:Bs\.?\s*\d+(?:[.,]\d+)?|\$us\s*\d+(?:[.,]\d+)?)/i',
            'consulta la oferta disponible',
            $text ?: $fallback
        ));
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
                            <strong>{{ $availableCount }}</strong>
                            <span>{{ __('public.rooms.available_rooms_count') }}</span>
                        </div>
                        <div class="rooms-hero-stat">
                            <strong>{{ $availableRoomGroups->count() }}</strong>
                            <span>{{ __('public.rooms.available_types') }}</span>
                        </div>
                        <div class="rooms-hero-stat">
                            <strong>{{ $activePromotionCount }}</strong>
                            <span>{{ __('public.rooms.active_offers') }}</span>
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
            @if ($availableRoomGroups->isNotEmpty())
                <div class="section-heading section-heading-split rooms-list-heading" data-reveal>
                    <div>
                        <span class="section-kicker">{{ __('public.rooms.available_physical_kicker') }}</span>
                        <h2>{{ __('public.rooms.available_physical_title') }}</h2>
                    </div>
                    <div class="rooms-heading-copy">
                        <p>{{ __('public.rooms.available_physical_text') }}</p>
                    </div>
                </div>

                <div class="home-room-type-showcase rooms-room-type-showcase">
                    @foreach ($availableRoomGroups as $group)
                        @php
                            $roomType = $group['roomType'];
                            $promotionLabel = $roomType?->public_promotion
                                ? ($roomType->public_promotion->discount_type === 'percentage'
                                    ? rtrim(rtrim(number_format((float) $roomType->public_promotion->discount_value, 2, '.', ''), '0'), '.').'%'
                                    : 'Descuento directo')
                                : null;
                            $typeDescription = $cleanPublicText($roomType?->description, __('public.rooms.card_fallback_description'));
                        @endphp
                        <article class="home-room-type-block" data-reveal>
                            <div class="home-room-type-block__head">
                                <div>
                                    <span class="section-kicker">{{ __('public.rooms.available_now') }}</span>
                                    <h3>{{ $roomType?->name ?? __('public.rooms.physical_room') }}</h3>
                                    <p>{{ \Illuminate\Support\Str::limit($typeDescription, 170) }}</p>
                                </div>
                                <div class="home-room-type-block__meta">
                                    <span>{{ $group['available_count'] }} {{ __('public.rooms.free_rooms') }}</span>
                                    <span>{{ __('public.rooms.max_guests_label') }} {{ $roomType?->max_guests ?? 0 }}</span>
                                    @if ($promotionLabel)
                                        <span class="promo-badge">{{ __('public.rooms.offer_label') }} {{ $promotionLabel }}</span>
                                    @endif
                                </div>
                            </div>
                            <div @class([
                                'home-room-cards',
                                'home-room-cards--single' => $group['rooms']->count() === 1,
                                'home-room-cards--pair' => $group['rooms']->count() === 2,
                            ])>
                                @foreach ($group['rooms'] as $room)
                                    @php
                                        $galleryImages = $room->public_gallery_images ?? [];
                                        $roomDescription = $cleanPublicText($room->description, $typeDescription);
                                    @endphp
                                    <article class="home-room-card" data-reveal>
                                        <div class="home-room-card__media">
                                            @if (count($galleryImages))
                                                <div class="public-room-gallery" data-public-room-gallery>
                                                    @foreach ($galleryImages as $image)
                                                        <img src="{{ asset('storage/'.$image) }}" alt="{{ $roomType?->name }} {{ $room->number }}" @class(['is-active' => $loop->first])>
                                                    @endforeach
                                                </div>
                                            @else
                                                <div class="placeholder-tile"><i class="bi bi-image"></i></div>
                                            @endif
                                            <span>{{ __('public.rooms.available_now') }}</span>
                                        </div>
                                        <div class="home-room-card__body">
                                            <small>{{ __('public.rooms.physical_room') }}</small>
                                            <strong>{{ $room->number }}</strong>
                                            <span>{{ $room->floor ? __('public.rooms.floor_label').' '.$room->floor : ($roomType?->name ?? '') }}</span>
                                            <p>{{ \Illuminate\Support\Str::limit($roomDescription, 105) }}</p>
                                            <div class="home-room-card__actions">
                                                <a href="{{ route('public.booking.create', ['room_id' => $room->id]) }}" class="btn btn-public-primary btn-public-sm">{{ __('public.rooms.book_this_room') }}</a>
                                                @if ($roomType)
                                                    <a href="{{ route('public.rooms.show', $roomType) }}" class="btn btn-public-outline btn-public-sm">{{ __('public.rooms.details') }}</a>
                                                @endif
                                            </div>
                                        </div>
                                    </article>
                                @endforeach
                            </div>
                        </article>
                    @endforeach
                </div>
            @else
                <div class="empty-state-public">{{ __('public.rooms.empty') }}</div>
            @endif

        </div>
    </section>
@endsection
