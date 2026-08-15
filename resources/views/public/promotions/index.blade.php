@extends('public.layouts.app')

@section('content')
    @php
        $whatsapp = preg_replace('/\D+/', '', (string) ($hotelSetting->whatsapp ?? ''));
        if ($whatsapp !== '' && ! str_starts_with($whatsapp, '591')) {
            $whatsapp = '591'.$whatsapp;
        }
    @endphp

    <section class="page-hero-simple">
        <div class="container">
            <span class="section-kicker">{{ __('public.promotions.kicker') }}</span>
            <h1>{{ __('public.promotions.title') }}</h1>
            <p>{{ __('public.promotions.description') }}</p>
        </div>
    </section>

    <section class="section-block">
        <div class="container">
            <div class="row g-4">
                @forelse ($promotions as $promotion)
                    @php
                        $whatsAppUrl = $whatsapp !== ''
                            ? 'https://wa.me/'.$whatsapp.'?text='.rawurlencode('Hola, quiero consultar la promocion '.$promotion->name.' en '.$hotelSetting->hotel_name.'.')
                            : null;
                        $publicPromotionDescription = preg_replace(
                            '/(?:Bs\.?\s*\d+(?:[.,]\d+)?|\$us\s*\d+(?:[.,]\d+)?)/i',
                            'descuento especial',
                            (string) ($promotion->description ?: __('public.promotions.fallback_description'))
                        );
                    @endphp
                    <div class="col-lg-6" data-reveal>
                        <article class="public-card promotion-list-card">
                            <div class="public-card-body">
                                <div class="d-flex flex-wrap justify-content-between gap-3 align-items-start">
                                    <div>
                                        <span class="section-kicker">{{ __('public.promotions.active') }}</span>
                                        <h2>{{ $promotion->name }}</h2>
                                    </div>
                                    <span class="price-chip">
                                        {{ $promotion->discount_type === 'percentage' ? rtrim(rtrim(number_format((float) $promotion->discount_value, 2, '.', ''), '0'), '.').'%' : 'Descuento directo' }}
                                    </span>
                                </div>
                                <p>{{ $publicPromotionDescription }}</p>
                                <div class="promotion-dates mb-3">
                                    {{ __('public.promotions.validity') }}: {{ $promotion->starts_at ? $promotion->starts_at->format('d/m/Y') : __('public.promotions.immediate') }} -
                                    {{ $promotion->ends_at ? $promotion->ends_at->format('d/m/Y') : __('public.promotions.no_limit') }}
                                </div>
                                <div class="amenities-row">
                                    @forelse ($promotion->roomTypes as $roomType)
                                        <span>{{ $roomType->name }}</span>
                                    @empty
                                        <span>{{ __('public.promotions.check_types') }}</span>
                                    @endforelse
                                </div>
                                @if ($whatsAppUrl)
                                    <a href="{{ $whatsAppUrl }}" target="_blank" rel="noopener" class="btn btn-public-primary mt-4">{{ __('public.promotions.whatsapp') }}</a>
                                @endif
                            </div>
                        </article>
                    </div>
                @empty
                    <div class="col-12">
                        <div class="empty-state-public">{{ __('public.promotions.empty') }}</div>
                    </div>
                @endforelse
            </div>
        </div>
    </section>
@endsection
