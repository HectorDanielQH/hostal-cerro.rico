@extends('public.layouts.app')

@section('content')
    @php
        $whatsapp = preg_replace('/\D+/', '', (string) ($hotelSetting->whatsapp ?? ''));
        if ($whatsapp !== '' && ! str_starts_with($whatsapp, '591')) {
            $whatsapp = '591'.$whatsapp;
        }
        $whatsAppUrl = $whatsapp !== ''
            ? 'https://wa.me/'.$whatsapp.'?text='.rawurlencode('Hola, realice una solicitud de reserva en '.($hotelSetting->hotel_name ?: 'Hostal Cerro Rico').'. Mi codigo es '.$reservation->code.'. Quisiera confirmar mi reserva.')
            : null;
        $roomTypeName = $reservation->roomType?->name ?: $reservation->room?->roomType?->name ?: __('public.booking_success.room_type');
    @endphp

    <section class="page-hero-simple">
        <div class="container">
            <span class="section-kicker">{{ __('public.booking_success.kicker') }}</span>
            <h1>{{ __('public.booking_success.title') }}</h1>
            <p>{{ __('public.booking_success.description') }}</p>
        </div>
    </section>

    <section class="section-block">
        <div class="container">
            <div class="row g-4 align-items-start">
                <div class="col-lg-8" data-reveal>
                    <div class="content-panel">
                        <div class="d-flex flex-wrap justify-content-between gap-3 align-items-start mb-4">
                            <div>
                                <span class="section-kicker">{{ __('public.booking_success.code_kicker') }}</span>
                                <h2 class="mb-1">{{ $reservation->code }}</h2>
                                <p class="mb-0">
                                    {{ __('public.booking_success.status_label') }}:
                                    <span class="promo-badge">{{ __('public.booking_success.status_pending') }}</span>
                                </p>
                            </div>
                            <div class="price-chip">{{ 'Bs. '.number_format((float) $reservation->total_amount, 2, '.', '') }}</div>
                        </div>

                        <div class="booking-summary-grid">
                            <div><strong>{{ __('public.booking_success.customer') }}</strong><span>{{ $reservation->customer?->full_name ?: '-' }}</span></div>
                            <div><strong>{{ __('public.booking_success.room_type') }}</strong><span>{{ $roomTypeName }}</span></div>
                            <div><strong>{{ __('public.booking_success.dates') }}</strong><span>{{ __('public.booking_success.date_range', ['from' => optional($reservation->check_in)->format('d/m/Y'), 'to' => optional($reservation->check_out)->format('d/m/Y')]) }}</span></div>
                            <div><strong>{{ __('public.booking_success.nights') }}</strong><span>{{ __('public.booking_success.nights_count', ['count' => $reservation->nights]) }}</span></div>
                            <div><strong>{{ __('public.booking_success.payment_method') }}</strong><span>{{ $paymentMethodLabel }}</span></div>
                            <div><strong>{{ __('public.booking_success.total_estimated') }}</strong><span>Bs. {{ number_format((float) $reservation->total_amount, 2, '.', '') }}</span></div>
                            <div><strong>{{ __('public.booking_success.required_deposit') }}</strong><span>{{ $reservation->deposit_percentage }}% - Bs. {{ number_format((float) $reservation->deposit_amount_required, 2, '.', '') }}</span></div>
                            <div><strong>{{ __('public.booking_success.balance_pending') }}</strong><span>Bs. {{ number_format((float) $reservation->balance_amount, 2, '.', '') }}</span></div>
                        </div>

                        <div class="booking-inline-alert mt-4">
                            {{ __('public.booking_success.deposit_notice', [
                                'percentage' => $reservation->deposit_percentage,
                                'amount' => 'Bs. '.number_format((float) $reservation->deposit_amount_required, 2, '.', ''),
                            ]) }}
                        </div>

                        <div class="hero-actions mt-4">
                            <a href="{{ $portalUrl }}" class="btn btn-public-primary">{{ __('public.booking_success.check_status') }}</a>
                            @if ($whatsAppUrl)
                                <a href="{{ $whatsAppUrl }}" target="_blank" rel="noopener" class="btn btn-public-primary">{{ __('public.booking_success.whatsapp') }}</a>
                            @endif
                            <a href="{{ route('public.home') }}" class="btn btn-public-outline">{{ __('public.booking_success.back_home') }}</a>
                        </div>
                    </div>
                </div>

                <div class="col-lg-4" data-reveal>
                    <div class="detail-panel booking-side-panel">
                        <span class="section-kicker">{{ __('public.booking_success.next_step_kicker') }}</span>
                        <h2>{{ __('public.booking_success.next_step_title') }}</h2>
                        <ul class="booking-feature-list mb-0">
                            <li>{{ __('public.booking_success.step_1') }}</li>
                            <li>{{ __('public.booking_success.step_2') }}</li>
                            <li>{{ __('public.booking_success.step_3', ['code' => $reservation->code]) }}</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </section>
@endsection
