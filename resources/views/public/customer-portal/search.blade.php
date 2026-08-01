@extends('public.layouts.app')

@section('content')
    @php
        $whatsapp = preg_replace('/\D+/', '', (string) ($hotelSetting->whatsapp ?? ''));
        if ($whatsapp !== '' && ! str_starts_with($whatsapp, '591')) {
            $whatsapp = '591'.$whatsapp;
        }
        $whatsAppUrl = $whatsapp !== ''
            ? 'https://wa.me/'.$whatsapp.'?text='.rawurlencode('Hola, quiero consultar mi reserva en '.($hotelSetting->hotel_name ?: 'Hostal Cerro Rico').'.')
            : null;
    @endphp

    <section class="page-hero-simple">
        <div class="container">
            <span class="section-kicker">{{ __('public.portal.kicker') }}</span>
            <h1>{{ __('public.portal.title') }}</h1>
            <p>{{ __('public.portal.description') }}</p>
        </div>
    </section>

    <section class="section-block">
        <div class="container">
            <div class="booking-steps-guide mb-4">
                <article class="booking-step-card">
                    <span class="booking-step-number">1</span>
                    <h3>{{ __('public.portal.step1_title') }}</h3>
                    <p>{{ __('public.portal.step1_description') }}</p>
                </article>
                <article class="booking-step-card">
                    <span class="booking-step-number">2</span>
                    <h3>{{ __('public.portal.step2_title') }}</h3>
                    <p>{{ __('public.portal.step2_description') }}</p>
                </article>
                <article class="booking-step-card">
                    <span class="booking-step-number">3</span>
                    <h3>{{ __('public.portal.step3_title') }}</h3>
                    <p>{{ __('public.portal.step3_description') }}</p>
                </article>
            </div>
            @if (session('success'))
                <div class="alert alert-success booking-alert mb-4">{{ session('success') }}</div>
            @endif

            @if ($errors->has('access'))
                <div class="alert alert-danger booking-alert mb-4">{{ $errors->first('access') }}</div>
            @endif

            <div class="row g-4 align-items-start">
                <div class="col-lg-7" data-reveal>
                    <div class="content-panel">
                        <span class="section-kicker">{{ __('public.portal.search_booking') }}</span>
                        <h2 class="mb-3">{{ __('public.portal.easy_view_title') }}</h2>
                        <div class="booking-inline-alert mb-3">
                            {{ __('public.portal.no_email_help') }}
                        </div>
                        <form method="POST" action="{{ route('public.customer-portal.find') }}" class="booking-form">
                            @csrf
                            <div class="row g-3">
                                <div class="col-12">
                                    <label class="form-label" for="reservation-code">{{ __('public.portal.code') }}</label>
                                    <input type="text" id="reservation-code" name="code" value="{{ old('code') }}" class="form-control @error('code') is-invalid @enderror" placeholder="RES-20260704-0001" required>
                                    @error('code')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                                <div class="col-12">
                                    <label class="form-label" for="reservation-contact">{{ __('public.portal.contact') }}</label>
                                    <input type="text" id="reservation-contact" name="contact" value="{{ old('contact') }}" class="form-control @error('contact') is-invalid @enderror" placeholder="{{ __('public.portal.contact_placeholder') }}" required>
                                    @error('contact')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>

                            <div class="d-flex flex-wrap gap-3 mt-4">
                                <button type="submit" class="btn btn-public-primary">{{ __('public.portal.view_booking') }}</button>
                                @if ($whatsAppUrl)
                                    <a href="{{ $whatsAppUrl }}" target="_blank" rel="noopener" class="btn btn-public-outline">{{ __('public.portal.whatsapp') }}</a>
                                @endif
                            </div>
                        </form>
                    </div>
                </div>

                <div class="col-lg-5" data-reveal>
                    <div class="detail-panel booking-side-panel">
                        <span class="section-kicker">{{ __('public.portal.help') }}</span>
                        <h2>{{ __('public.portal.before_start') }}</h2>
                        <ul class="booking-feature-list">
                            <li>{{ __('public.portal.before_start_item_1') }}</li>
                            <li>{{ __('public.portal.before_start_item_2') }}</li>
                            <li>{{ __('public.portal.before_start_item_3') }}</li>
                        </ul>
                        @if ($whatsAppUrl)
                            <a href="{{ $whatsAppUrl }}" target="_blank" rel="noopener" class="btn btn-public-outline mt-3">{{ __('public.portal.need_help') }}</a>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </section>
@endsection
