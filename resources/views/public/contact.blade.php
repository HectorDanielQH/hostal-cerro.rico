@extends('public.layouts.app')

@section('content')
    @php
        $contactPeople = collect($hotelSetting->publicContactPeople());
        $contactEmails = collect($hotelSetting->publicContactEmails());
        $primaryContact = $contactPeople->first();
        $whatsAppUrl = $primaryContact['whatsapp_url'] ?? null;
        $googleMapsPublicUrl = $hotelSetting->googleMapsPublicUrl();
        $googleMapsEmbedUrl = $hotelSetting->googleMapsEmbedUrl();
    @endphp

    <section class="page-hero-simple">
        <div class="container">
            <span class="section-kicker">{{ __('public.contact.kicker') }}</span>
            <h1>{{ __('public.contact.title') }}</h1>
            <p>{{ __('public.contact.description') }}</p>
        </div>
    </section>

    <section class="section-block">
        <div class="container">
            <div class="row g-4">
                <div class="col-lg-5" data-reveal>
                    <div class="content-panel h-100 contact-info-panel">
                        <h2>{{ __('public.contact.hotel_info') }}</h2>
                        <div class="contact-grid contact-grid-single">
                            <div><strong>{{ __('public.contact.address') }}</strong><span>{{ trim(collect([$hotelSetting->address, $hotelSetting->city, $hotelSetting->country])->filter()->implode(', ')) ?: 'Potosi, Bolivia' }}</span></div>
                            @if ($hotelSetting->phone)<div><strong>{{ __('public.contact.phone') }}</strong><span>{{ $hotelSetting->phone }}</span></div>@endif
                            @if ($hotelSetting->website)<div><strong>{{ __('public.contact.website') }}</strong><span>{{ $hotelSetting->website }}</span></div>@endif
                        </div>
                        <div class="footer-socials mt-4">
                            @if ($hotelSetting->facebook)<a href="{{ $hotelSetting->facebook }}" target="_blank" rel="noopener"><i class="bi bi-facebook"></i></a>@endif
                            @if ($hotelSetting->instagram)<a href="{{ $hotelSetting->instagram }}" target="_blank" rel="noopener"><i class="bi bi-instagram"></i></a>@endif
                            @if ($hotelSetting->tiktok)<a href="{{ $hotelSetting->tiktok }}" target="_blank" rel="noopener"><i class="bi bi-tiktok"></i></a>@endif
                        </div>
                    </div>
                </div>
                <div class="col-lg-7" data-reveal>
                    <div class="content-panel h-100 contact-people-panel">
                        <span class="section-kicker">Atencion directa</span>
                        <h2>Contactate con</h2>
                        <p class="lead-copy">Elige una persona o area del hotel para escribir directamente por WhatsApp.</p>
                        <div class="contact-person-cards">
                            @forelse ($contactPeople as $person)
                                <article class="contact-person-card">
                                    <div class="contact-person-card__photo">
                                        @if ($person['photo_url'])
                                            <img src="{{ $person['photo_url'] }}" alt="{{ $person['name'] }}">
                                        @else
                                            <i class="bi bi-person-circle"></i>
                                        @endif
                                    </div>
                                    <div class="contact-person-card__body">
                                        <span>{{ $person['role'] }}</span>
                                        <h3>{{ $person['name'] }}</h3>
                                        <p>{{ $person['display_phone'] }}</p>
                                    </div>
                                    <a href="{{ $person['whatsapp_url'] }}" target="_blank" rel="noopener" class="btn btn-public-primary">
                                        <i class="bi bi-whatsapp me-1"></i> WhatsApp
                                    </a>
                                </article>
                            @empty
                                <div class="empty-state-public">Aun no hay contactos WhatsApp configurados.</div>
                            @endforelse
                        </div>

                        <div class="contact-email-panel mt-4">
                            <h3>Correos de contacto</h3>
                            <div class="contact-email-list">
                                @forelse ($contactEmails as $contactEmail)
                                    <a href="{{ $contactEmail['mailto_url'] }}">
                                        <i class="bi bi-envelope"></i>
                                        <span>{{ $contactEmail['label'] }}</span>
                                        <strong>{{ $contactEmail['email'] }}</strong>
                                    </a>
                                @empty
                                    <span class="text-muted small">Aun no hay correos de contacto configurados.</span>
                                @endforelse
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            @if ($googleMapsEmbedUrl || $googleMapsPublicUrl)
                <div class="content-panel mt-4" data-reveal>
                    <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-3">
                        <h2 class="mb-0">{{ __('public.contact.location') }}</h2>
                        @if ($googleMapsPublicUrl)
                            <a href="{{ $googleMapsPublicUrl }}" target="_blank" rel="noopener" class="btn btn-public-outline">{{ __('public.contact.open_maps') }}</a>
                        @endif
                    </div>
                    @if ($googleMapsEmbedUrl)
                        <div class="map-frame-wrapper">
                            <iframe src="{{ $googleMapsEmbedUrl }}" loading="lazy" referrerpolicy="no-referrer-when-downgrade" allowfullscreen></iframe>
                        </div>
                    @endif
                </div>
            @endif
        </div>
    </section>
@endsection
