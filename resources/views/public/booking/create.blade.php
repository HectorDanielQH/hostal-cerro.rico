@extends('public.layouts.app')

@section('content')
    @php
        $today = now()->toDateString();
        $tomorrow = now()->addDay()->toDateString();
        $selectedType = $roomTypes->firstWhere('id', (int) $selectedRoomTypeId);
        $queryCheckIn = request()->query('check_in');
        $queryCheckOut = request()->query('check_out');
        $queryAdults = request()->query('adults');
        $queryChildren = request()->query('children');
        $defaultPaymentMethod = collect($paymentMethods)->firstWhere('available', true)['value'] ?? ($paymentMethods[0]['value'] ?? 'qr');
        $selectedPaymentMethod = old('preferred_payment_method', $defaultPaymentMethod);
        $selectedPaymentCurrency = old('payment_currency', $hotelSetting->baseCurrency());
        $selectedPaymentMethodLabel = collect($paymentMethods)->firstWhere('value', $selectedPaymentMethod)['label'] ?? __('public.messages.payment_other');
        $walletQrPath = $hotelSetting->digital_wallet_qr_image ?: $hotelSetting->payment_qr_image;
        $walletQrUrl = $walletQrPath ? asset('storage/'.$walletQrPath) : null;
        $bankQrUrl = $hotelSetting->bank_qr_image ? asset('storage/'.$hotelSetting->bank_qr_image) : null;
        $paymentQrUrl = $walletQrUrl ?: $bankQrUrl;
        $digitalWalletQrUrl = $walletQrUrl;
        $localBankQrUrl = $bankQrUrl;
        $bankConfigured = filled($hotelSetting->bank_name) || filled($hotelSetting->bank_account_holder) || filled($hotelSetting->bank_account_number);
        $bookingAvailableCountTemplate = __('public.booking.available_count', ['count' => '__COUNT__']);
        $bookingMaxGuestsTemplate = __('public.booking.max_guests', ['count' => '__COUNT__']);
        $bookingBaseLabelTemplate = __('public.booking.base_label', ['price' => '__PRICE__']);
        $bookingDepositHelpTemplate = __('public.booking.deposit_help', ['percentage' => '__PERCENTAGE__', 'amount' => '__AMOUNT__']);
        $bookingDateRangeTemplate = __('public.booking.date_range', ['from' => '__FROM__', 'to' => '__TO__']);
        $bookingNightsCountTemplate = __('public.booking.nights_count', ['count' => '__COUNT__']);
        $countries = [
            ['name' => 'Bolivia', 'code' => 'BO'],
            ['name' => 'Argentina', 'code' => 'AR'],
            ['name' => 'Brasil', 'code' => 'BR'],
            ['name' => 'Chile', 'code' => 'CL'],
            ['name' => 'Colombia', 'code' => 'CO'],
            ['name' => 'Ecuador', 'code' => 'EC'],
            ['name' => 'Paraguay', 'code' => 'PY'],
            ['name' => 'Peru', 'code' => 'PE'],
            ['name' => 'Uruguay', 'code' => 'UY'],
            ['name' => 'Venezuela', 'code' => 'VE'],
            ['name' => 'Mexico', 'code' => 'MX'],
            ['name' => 'Estados Unidos', 'code' => 'US'],
            ['name' => 'Canada', 'code' => 'CA'],
            ['name' => 'Espana', 'code' => 'ES'],
            ['name' => 'Francia', 'code' => 'FR'],
            ['name' => 'Alemania', 'code' => 'DE'],
            ['name' => 'Italia', 'code' => 'IT'],
            ['name' => 'Portugal', 'code' => 'PT'],
            ['name' => 'Reino Unido', 'code' => 'GB'],
            ['name' => 'Paises Bajos', 'code' => 'NL'],
            ['name' => 'Suiza', 'code' => 'CH'],
            ['name' => 'China', 'code' => 'CN'],
            ['name' => 'Japon', 'code' => 'JP'],
            ['name' => 'Corea del Sur', 'code' => 'KR'],
            ['name' => 'Australia', 'code' => 'AU'],
            ['name' => 'Nueva Zelanda', 'code' => 'NZ'],
        ];
        $dialCodes = [
            ['label' => 'Bolivia', 'flag' => '🇧🇴', 'code' => '+591'],
            ['label' => 'Argentina', 'flag' => '🇦🇷', 'code' => '+54'],
            ['label' => 'Brasil', 'flag' => '🇧🇷', 'code' => '+55'],
            ['label' => 'Chile', 'flag' => '🇨🇱', 'code' => '+56'],
            ['label' => 'Colombia', 'flag' => '🇨🇴', 'code' => '+57'],
            ['label' => 'Ecuador', 'flag' => '🇪🇨', 'code' => '+593'],
            ['label' => 'Paraguay', 'flag' => '🇵🇾', 'code' => '+595'],
            ['label' => 'Peru', 'flag' => '🇵🇪', 'code' => '+51'],
            ['label' => 'Uruguay', 'flag' => '🇺🇾', 'code' => '+598'],
            ['label' => 'Venezuela', 'flag' => '🇻🇪', 'code' => '+58'],
            ['label' => 'Mexico', 'flag' => '🇲🇽', 'code' => '+52'],
            ['label' => 'Estados Unidos', 'flag' => '🇺🇸', 'code' => '+1'],
            ['label' => 'Canada', 'flag' => '🇨🇦', 'code' => '+1'],
            ['label' => 'Espana', 'flag' => '🇪🇸', 'code' => '+34'],
            ['label' => 'Francia', 'flag' => '🇫🇷', 'code' => '+33'],
            ['label' => 'Alemania', 'flag' => '🇩🇪', 'code' => '+49'],
            ['label' => 'Italia', 'flag' => '🇮🇹', 'code' => '+39'],
            ['label' => 'Portugal', 'flag' => '🇵🇹', 'code' => '+351'],
            ['label' => 'Reino Unido', 'flag' => '🇬🇧', 'code' => '+44'],
            ['label' => 'Paises Bajos', 'flag' => '🇳🇱', 'code' => '+31'],
            ['label' => 'Suiza', 'flag' => '🇨🇭', 'code' => '+41'],
            ['label' => 'China', 'flag' => '🇨🇳', 'code' => '+86'],
            ['label' => 'Japon', 'flag' => '🇯🇵', 'code' => '+81'],
            ['label' => 'Corea del Sur', 'flag' => '🇰🇷', 'code' => '+82'],
            ['label' => 'Australia', 'flag' => '🇦🇺', 'code' => '+61'],
            ['label' => 'Nueva Zelanda', 'flag' => '🇳🇿', 'code' => '+64'],
        ];
        $dialCodes = [
            ['label' => 'Bolivia', 'country_code' => 'BO', 'code' => '+591'],
            ['label' => 'Argentina', 'country_code' => 'AR', 'code' => '+54'],
            ['label' => 'Brasil', 'country_code' => 'BR', 'code' => '+55'],
            ['label' => 'Chile', 'country_code' => 'CL', 'code' => '+56'],
            ['label' => 'Colombia', 'country_code' => 'CO', 'code' => '+57'],
            ['label' => 'Ecuador', 'country_code' => 'EC', 'code' => '+593'],
            ['label' => 'Paraguay', 'country_code' => 'PY', 'code' => '+595'],
            ['label' => 'Peru', 'country_code' => 'PE', 'code' => '+51'],
            ['label' => 'Uruguay', 'country_code' => 'UY', 'code' => '+598'],
            ['label' => 'Venezuela', 'country_code' => 'VE', 'code' => '+58'],
            ['label' => 'Mexico', 'country_code' => 'MX', 'code' => '+52'],
            ['label' => 'Estados Unidos', 'country_code' => 'US', 'code' => '+1'],
            ['label' => 'Canada', 'country_code' => 'CA', 'code' => '+1'],
            ['label' => 'Espana', 'country_code' => 'ES', 'code' => '+34'],
            ['label' => 'Francia', 'country_code' => 'FR', 'code' => '+33'],
            ['label' => 'Alemania', 'country_code' => 'DE', 'code' => '+49'],
            ['label' => 'Italia', 'country_code' => 'IT', 'code' => '+39'],
            ['label' => 'Portugal', 'country_code' => 'PT', 'code' => '+351'],
            ['label' => 'Reino Unido', 'country_code' => 'GB', 'code' => '+44'],
            ['label' => 'Paises Bajos', 'country_code' => 'NL', 'code' => '+31'],
            ['label' => 'Suiza', 'country_code' => 'CH', 'code' => '+41'],
            ['label' => 'China', 'country_code' => 'CN', 'code' => '+86'],
            ['label' => 'Japon', 'country_code' => 'JP', 'code' => '+81'],
            ['label' => 'Corea del Sur', 'country_code' => 'KR', 'code' => '+82'],
            ['label' => 'Australia', 'country_code' => 'AU', 'code' => '+61'],
            ['label' => 'Nueva Zelanda', 'country_code' => 'NZ', 'code' => '+64'],
        ];
        $selectedNationality = old('nationality', 'Bolivia');
        $selectedNationalityExists = collect($countries)->contains('name', $selectedNationality);
        $selectedCity = old('city', $hotelSetting->city ?: 'Potosi');
        $oldWhatsapp = old('whatsapp', '');
        $selectedWhatsappCode = '+591';
        $selectedWhatsappNumber = $oldWhatsapp;

        if (preg_match('/^(\+\d{1,4})\s*(.*)$/', trim((string) $oldWhatsapp), $whatsappParts) === 1) {
            $selectedWhatsappCode = $whatsappParts[1];
            $selectedWhatsappNumber = trim($whatsappParts[2]);
        }
    @endphp

    <section class="page-hero-simple page-hero-booking-atg">
        <div class="container">
            <div class="booking-hero-grid">
                <div class="booking-hero-copy" data-reveal>
                    <span class="section-kicker">{{ __('public.booking.kicker') }}</span>
                    <h1>{{ __('public.booking.title') }}</h1>
                    <p>{{ __('public.booking.description') }}</p>
                    <div class="booking-hero-actions">
                        <a href="#booking-checkout" class="btn btn-public-primary booking-scroll-cta">
                            Baja para reservar
                            <i class="bi bi-arrow-down-short" aria-hidden="true"></i>
                        </a>
                        <span class="booking-hero-mini-guide">Completa los pasos con el boton Siguiente.</span>
                    </div>
                </div>
                <div class="booking-hero-note" data-reveal>
                    <span>{{ __('public.booking.hero_note_kicker') }}</span>
                    <strong>{{ __('public.booking.hero_note_text') }}</strong>
                </div>
            </div>
        </div>
    </section>

    <section class="section-block" id="booking-checkout">
        <div class="container">
            <div class="booking-wizard-intro" data-reveal>
                <div class="booking-wizard-intro-copy">
                    <span class="section-kicker">{{ __('public.booking.wizard_intro_kicker') }}</span>
                    <h2>{{ __('public.booking.wizard_intro_title') }}</h2>
                </div>
                <div class="booking-wizard-intro-side">
                    <p>{{ __('public.booking.wizard_intro_text') }}</p>
                </div>
            </div>

            <div class="booking-touch-guide" data-reveal>
                <div>
                    <span class="booking-touch-icon"><i class="bi bi-hand-index-thumb" aria-hidden="true"></i></span>
                    <strong>Guia rapida</strong>
                    <small>Llena este paso y presiona <b>Siguiente</b>. El sistema revisa disponibilidad automaticamente.</small>
                </div>
                <a href="#public-booking-form" class="booking-touch-link">Empezar ahora</a>
            </div>

            @if ($errors->any())
                <div class="alert alert-danger booking-alert mb-4" role="alert">
                    <strong>{{ __('public.booking.errors_title') }}</strong>
                    <ul class="mb-0 mt-2">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <div class="booking-success-panel d-none" id="booking-success-panel" role="status" aria-live="polite">
                <div class="booking-success-icon">
                    <i class="bi bi-check2-circle" aria-hidden="true"></i>
                </div>
                <div>
                    <span class="section-kicker">Solicitud enviada</span>
                    <h2>Tu reserva fue enviada correctamente</h2>
                    <p id="booking-success-message">El hotel revisara tu solicitud y te contactara para confirmar.</p>
                    <div class="booking-success-actions">
                        <a href="#" class="btn btn-public-primary" id="booking-success-status-link">Consultar estado</a>
                        <button type="button" class="btn btn-public-outline" id="booking-success-new-request">Hacer otra reserva</button>
                    </div>
                </div>
            </div>

            <div class="booking-progress-board booking-progress-board-compact mb-4" data-reveal>
                <div class="booking-progress-item is-active" data-progress-item data-step-index="0">
                    <span class="booking-progress-number">1</span>
                    <div>
                        <strong>{{ __('public.booking.step1') }}</strong>
                        <small>{{ __('public.booking.trip_data_description') }}</small>
                    </div>
                </div>
                <div class="booking-progress-item" data-progress-item data-step-index="1">
                    <span class="booking-progress-number">2</span>
                    <div>
                        <strong>{{ __('public.booking.step2') }}</strong>
                        <small>{{ __('public.booking.step2_help') }}</small>
                    </div>
                </div>
                <div class="booking-progress-item" data-progress-item data-step-index="2">
                    <span class="booking-progress-number">3</span>
                    <div>
                        <strong>{{ __('public.booking.step3') }}</strong>
                        <small>Indicanos quien realizara la reserva.</small>
                    </div>
                </div>
                <div class="booking-progress-item" data-progress-item data-step-index="3">
                    <span class="booking-progress-number">4</span>
                    <div>
                        <strong>{{ __('public.booking.payment_title') }}</strong>
                        <small>QR, transferencia o deposito bancario.</small>
                    </div>
                </div>
                <div class="booking-progress-item" data-progress-item data-step-index="4">
                    <span class="booking-progress-number">5</span>
                    <div>
                        <strong>{{ __('public.booking.confirmation_title') }}</strong>
                        <small>{{ __('public.booking.accept_terms_label') }}</small>
                    </div>
                </div>
            </div>

            <div class="row g-4">
                <div class="col-xl-8">
                    <form method="POST" action="{{ route('public.booking.store') }}" class="booking-form booking-wizard-form" id="public-booking-form" enctype="multipart/form-data" novalidate>
                        @csrf
                        <input type="hidden" id="booking-room-id" name="room_id" value="{{ $selectedRoomId ?? '' }}">
                        <input type="hidden" id="booking-visitor-location" name="visitor_location" value="{{ old('visitor_location') }}">

                        <div class="booking-step-panel is-active" data-booking-step data-step-index="0" data-step-label="{{ __('public.booking.step1') }}">
                            <div class="booking-step-head">
                                <div>
                                    <span class="section-kicker">Paso 1</span>
                                    <h2>{{ __('public.booking.trip_data_title') }}</h2>
                                    <p>{{ __('public.booking.trip_data_description') }}</p>
                                </div>
                                <div class="booking-step-badge">{{ __('public.booking.step_badge_start') }}</div>
                            </div>

                            <div class="booking-inline-alert mb-3">
                                {{ __('public.booking.room_type_help') }}
                            </div>

                            <div class="booking-step-grid">
                                <div>
                                    <label class="form-label" for="booking-check-in">{{ __('public.booking.check_in_label') }}</label>
                                    <input type="date" id="booking-check-in" name="check_in" min="{{ $today }}" value="{{ old('check_in', $queryCheckIn ?: $today) }}" class="form-control @error('check_in') is-invalid @enderror" required>
                                    @error('check_in')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                                <div>
                                    <label class="form-label" for="booking-check-out">{{ __('public.booking.check_out_label') }}</label>
                                    <input type="date" id="booking-check-out" name="check_out" min="{{ $tomorrow }}" value="{{ old('check_out', $queryCheckOut ?: $tomorrow) }}" class="form-control @error('check_out') is-invalid @enderror" required>
                                    @error('check_out')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                                <div>
                                    <label class="form-label" for="booking-adults">{{ __('public.booking.adults_label') }}</label>
                                    <input type="number" id="booking-adults" name="adults" min="1" max="20" value="{{ old('adults', $queryAdults ?: 1) }}" class="form-control @error('adults') is-invalid @enderror" required>
                                    @error('adults')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                                <div>
                                    <label class="form-label" for="booking-children">{{ __('public.booking.children_label') }}</label>
                                    <input type="number" id="booking-children" name="children" min="0" max="20" value="{{ old('children', $queryChildren ?: 0) }}" class="form-control @error('children') is-invalid @enderror">
                                    @error('children')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                                <div class="booking-step-grid-wide">
                                    <div class="booking-inline-alert">
                                        En el siguiente paso veras habitaciones reales disponibles. Elige la habitacion exacta que quieres solicitar.
                                    </div>
                                    <select id="booking-room-type" name="room_type_id" class="d-none @error('room_type_id') is-invalid @enderror" aria-hidden="true" tabindex="-1">
                                        <option value="">Habitacion pendiente</option>
                                        @foreach ($roomTypes as $roomType)
                                            <option value="{{ $roomType->id }}" data-name="{{ $roomType->name }}" @selected((int) old('room_type_id', $selectedRoomTypeId) === $roomType->id)>{{ $roomType->name }}</option>
                                        @endforeach
                                    </select>
                                    @error('room_type_id')
                                        <div class="invalid-feedback d-block">{{ $message }}</div>
                                    @enderror
                                    @error('room_id')
                                        <div class="invalid-feedback d-block">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>

                            <div class="booking-step-actions">
                                <div class="booking-step-tip">{{ __('public.booking.important') }}</div>
                                <div class="booking-step-action-buttons">
                                    <button type="button" class="btn btn-public-primary" data-step-next>{{ __('public.booking.next') }}</button>
                                </div>
                            </div>
                        </div>

                        <div class="booking-step-panel" data-booking-step data-step-index="1" data-step-label="{{ __('public.booking.step2') }}" aria-hidden="true">
                            <div class="booking-step-head">
                                <div>
                                    <span class="section-kicker">{{ __('public.booking.availability_kicker') }}</span>
                                    <h2>{{ __('public.booking.availability_title') }}</h2>
                                    <p>{{ __('public.booking.availability_help') }}</p>
                                </div>
                                <div class="booking-step-badge" id="availability-status">{{ __('public.booking.availability_auto') }}</div>
                            </div>

                            <div class="booking-step-toolbar">
                                <div class="booking-inline-alert booking-inline-alert-strong">
                                    Elige una habitacion disponible para continuar.
                                </div>
                            </div>

                            <div class="booking-results" id="availability-results">
                                <div class="empty-state-public">{{ __('public.booking.availability_empty') }}</div>
                            </div>

                            <div class="booking-step-actions">
                                <div class="booking-step-tip">Solo puedes avanzar con una habitacion disponible para tus fechas.</div>
                                <div class="booking-step-action-buttons">
                                    <button type="button" class="btn btn-public-ghost" data-step-prev>{{ __('public.booking.back') }}</button>
                                    <button type="button" class="btn btn-public-primary" data-step-next>{{ __('public.booking.next') }}</button>
                                </div>
                            </div>
                        </div>

                        <div class="booking-step-panel" data-booking-step data-step-index="2" data-step-label="{{ __('public.booking.step3') }}" aria-hidden="true">
                            <div class="booking-step-head">
                                <div>
                                    <span class="section-kicker">{{ __('public.booking.guest_data_kicker') }}</span>
                                    <h2>{{ __('public.booking.guest_data_title') }}</h2>
                                    <p>{{ __('public.booking.guest_data_help') }}</p>
                                </div>
                                <div class="booking-step-badge">{{ __('public.booking.step_badge_guest') }}</div>
                            </div>

                            <div class="booking-step-grid">
                                <div class="booking-step-grid-wide">
                                    <label class="form-label" for="full_name">{{ __('public.booking.full_name_label') }}</label>
                                    <input type="text" id="full_name" name="full_name" value="{{ old('full_name') }}" class="form-control @error('full_name') is-invalid @enderror" required>
                                    @error('full_name')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                                <div>
                                    <label class="form-label" for="document_type">{{ __('public.booking.document_type_label') }}</label>
                                    <div class="booking-select-shell">
                                        <select id="document_type" name="document_type" class="form-select @error('document_type') is-invalid @enderror">
                                            <option value="">{{ __('public.booking.document_type_placeholder') }}</option>
                                            <option value="ci" @selected(old('document_type') === 'ci')>CI</option>
                                            <option value="passport" @selected(old('document_type') === 'passport')>Pasaporte</option>
                                            <option value="nit" @selected(old('document_type') === 'nit')>NIT</option>
                                            <option value="other" @selected(old('document_type') === 'other')>Otro</option>
                                        </select>
                                        <span class="booking-select-chevron"><i class="bi bi-chevron-down" aria-hidden="true"></i></span>
                                    </div>
                                    @error('document_type')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                                <div>
                                    <label class="form-label" for="document_number">{{ __('public.booking.document_number_label') }}</label>
                                    <input type="text" id="document_number" name="document_number" value="{{ old('document_number') }}" class="form-control @error('document_number') is-invalid @enderror">
                                    @error('document_number')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                                <div>
                                    <label class="form-label" for="nationality">{{ __('public.booking.nationality_label') }}</label>
                                    <select id="nationality" name="nationality" class="form-select public-country-select @error('nationality') is-invalid @enderror" data-country-select data-country-api="https://cdn.simplelocalize.io/public/v1/countries" data-selected-country="{{ $selectedNationality }}" data-placeholder="Selecciona tu nacionalidad">
                                        @unless ($selectedNationalityExists)
                                            <option value="{{ $selectedNationality }}" selected>{{ $selectedNationality }}</option>
                                        @endunless
                                        @foreach ($countries as $country)
                                            <option value="{{ $country['name'] }}" data-country-code="{{ $country['code'] }}" data-country-flag="https://flagcdn.com/w40/{{ strtolower($country['code']) }}.png" @selected($selectedNationality === $country['name'])>
                                                {{ $country['name'] }}
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('nationality')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                    <div class="booking-field-help">Selecciona tu nacionalidad con la bandera para que el sistema te muestre la forma de pago correcta.</div>
                                </div>
                                <div>
                                    <label class="form-label" for="city">{{ __('public.booking.city_label') }}</label>
                                    <select id="city" name="city" class="form-select public-city-select @error('city') is-invalid @enderror" data-city-select data-city-api="https://countriesnow.space/api/v0.1/countries/cities" data-country-source="#nationality" data-selected-city="{{ $selectedCity }}" data-placeholder="Selecciona tu ciudad">
                                        @if ($selectedCity)
                                            <option value="{{ $selectedCity }}" selected>{{ $selectedCity }}</option>
                                        @endif
                                    </select>
                                    @error('city')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                    <div class="booking-field-help">Primero selecciona tu nacionalidad; luego busca tu ciudad en la lista.</div>
                                </div>
                                <div>
                                    <label class="form-label" for="phone">{{ __('public.booking.phone_label') }}</label>
                                    <input type="text" id="phone" name="phone" value="{{ old('phone') }}" class="form-control @error('phone') is-invalid @enderror">
                                    @error('phone')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                                <div>
                                    <label class="form-label" for="whatsapp">{{ __('public.contact.whatsapp') }}</label>
                                    <input type="hidden" id="whatsapp" name="whatsapp" value="{{ old('whatsapp') }}">
                                    <div class="input-group">
                                        <select id="whatsapp-code" class="form-select public-whatsapp-code-select @error('whatsapp') is-invalid @enderror" data-whatsapp-code-select aria-label="Codigo de pais para WhatsApp">
                                            @foreach ($dialCodes as $dialCode)
                                                <option
                                                    value="{{ $dialCode['code'] }}"
                                                    title="{{ $dialCode['label'] }}"
                                                    data-country-code="{{ $dialCode['country_code'] }}"
                                                    data-country-flag="https://flagcdn.com/w40/{{ strtolower($dialCode['country_code']) }}.png"
                                                    @selected($selectedWhatsappCode === $dialCode['code'])
                                                >
                                                    {{ $dialCode['code'] }}
                                                </option>
                                            @endforeach
                                        </select>
                                        <input type="text" id="whatsapp-number" value="{{ $selectedWhatsappNumber }}" class="form-control @error('whatsapp') is-invalid @enderror" placeholder="Numero de celular" inputmode="tel">
                                    </div>
                                    @error('whatsapp')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                                <div>
                                    <label class="form-label" for="email">{{ __('public.booking.email_label') }}</label>
                                    <input type="email" id="email" name="email" value="{{ old('email') }}" class="form-control @error('email') is-invalid @enderror">
                                    @error('email')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                                <div class="booking-step-grid-wide">
                                    <label class="form-label" for="special_requests">{{ __('public.booking.special_requests_title') }} <span class="text-muted">(opcional)</span></label>
                                    <textarea id="special_requests" name="special_requests" rows="4" class="form-control @error('special_requests') is-invalid @enderror" placeholder="{{ __('public.booking.special_requests_placeholder') }}">{{ old('special_requests') }}</textarea>
                                    @error('special_requests')
                                        <div class="invalid-feedback d-block">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>

                            <div class="booking-step-actions">
                                <div class="booking-step-tip">Con estos datos el hotel podra contactarte para confirmar tu reserva.</div>
                                <div class="booking-step-action-buttons">
                                    <button type="button" class="btn btn-public-ghost" data-step-prev>{{ __('public.booking.back') }}</button>
                                    <button type="button" class="btn btn-public-primary" data-step-next>{{ __('public.booking.next') }}</button>
                                </div>
                            </div>
                        </div>

                        <div class="booking-step-panel" data-booking-step data-step-index="3" data-step-label="{{ __('public.booking.payment_title') }}" aria-hidden="true">
                            <div class="booking-step-head">
                                <div>
                                    <span class="section-kicker">Paso 4</span>
                                    <h2>{{ __('public.booking.payment_title') }}</h2>
                                    <p>{{ __('public.booking.payment_help') }}</p>
                                </div>
                                <div class="booking-step-badge">Pago</div>
                            </div>

                            <div class="booking-payment-gateway-card">
                                <div class="booking-payment-gateway-head">
                                    <span class="section-kicker">{{ __('public.booking.payment_kicker') }}</span>
                                    <strong>Elige como quieres depositar</strong>
                                    <small id="booking-payment-routing-help">El sistema mostrara las opciones correctas segun tu ubicacion y nacionalidad.</small>
                                </div>

                                <div class="booking-payment-options booking-payment-options-wide">
                                    @foreach ($paymentMethods as $paymentMethod)
                                        <label class="booking-payment-card @unless($paymentMethod['available']) is-disabled @endunless" for="preferred-payment-{{ $paymentMethod['value'] }}" data-payment-method-card data-payment-method="{{ $paymentMethod['value'] }}" data-configured="{{ $paymentMethod['available'] ? 'true' : 'false' }}">
                                            <input
                                                class="form-check-input"
                                                type="radio"
                                                name="preferred_payment_method"
                                                id="preferred-payment-{{ $paymentMethod['value'] }}"
                                                value="{{ $paymentMethod['value'] }}"
                                                @checked($selectedPaymentMethod === $paymentMethod['value'] && $paymentMethod['available'])
                                                @disabled(! $paymentMethod['available'])
                                                required
                                            >
                                            <span class="booking-payment-icon">
                                                <i class="bi {{ $paymentMethod['icon'] ?? 'bi-credit-card' }}" aria-hidden="true"></i>
                                            </span>
                                            <span class="booking-payment-card-copy">
                                                <strong>{{ $paymentMethod['label'] }}</strong>
                                                <span>{{ $paymentMethod['description'] }}</span>
                                                @unless ($paymentMethod['available'])
                                                    <span class="booking-payment-unavailable">Este metodo aun no esta configurado por el hotel.</span>
                                                @endunless
                                            </span>
                                        </label>
                                    @endforeach
                                </div>
                                @unless (collect($paymentMethods)->contains('available', true))
                                    <div class="booking-payment-missing mt-3">
                                        El hotel aun no configuro QR ni datos bancarios. Configuralos en Ajustes para activar reservas con comprobante.
                                    </div>
                                @endunless
                                @error('preferred_payment_method')
                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                @enderror

                                <input type="hidden" id="booking-payment-currency" name="payment_currency" value="{{ old('payment_currency', $selectedPaymentCurrency) }}" required>
                                @error('payment_currency')
                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                @enderror

                                <div class="booking-payment-attraction-card booking-payment-attraction-card-compact mt-3">
                                    <div class="booking-payment-attraction-main">
                                        <span id="booking-attraction-percentage">Anticipo minimo</span>
                                        <strong id="booking-attraction-minimum">-</strong>
                                        <small id="booking-attraction-currency" data-selected-currency-label>Bs. Bolivianos</small>
                                    </div>
                                    <div class="booking-payment-attraction-grid">
                                        <div>
                                            <span id="booking-attraction-total-label">Total de tu estadia</span>
                                            <strong id="booking-attraction-total">-</strong>
                                        </div>
                                        <div>
                                            <span>Promocion</span>
                                            <strong id="booking-attraction-discount">Sin promocion</strong>
                                        </div>
                                    </div>
                                    <p id="booking-payment-attraction-copy" class="booking-payment-attraction-copy">
                                        Elige una habitacion y aqui veras cuanto debes depositar para enviar tu solicitud.
                                    </p>
                                </div>

                                <div class="booking-payment-guide-stack mt-3">
                                    <div class="booking-payment-guide @if ($selectedPaymentMethod !== 'qr') d-none @endif" data-booking-payment-guide="qr">
                                        <div>
                                            <span class="section-kicker">QR de billetera digital</span>
                                            <h3>Escanea el QR digital y sube tu comprobante</h3>
                                            <p>Esta opcion se muestra para visitantes fuera de Bolivia o clientes extranjeros. Deposita en <strong data-selected-currency-label>Bs. Bolivianos</strong> y guarda la captura del pago.</p>
                                        </div>
                                        @if ($digitalWalletQrUrl)
                                            <div class="booking-qr-grid booking-qr-grid-single">
                                                <div class="booking-qr-panel" data-qr-currency-panel="ANY">
                                                    <strong>QR de billetera digital</strong>
                                                    <small>Usa este QR para pagos digitales, especialmente si estas fuera de Bolivia.</small>
                                                    <button type="button" class="booking-qr-preview" data-open-qr-modal data-qr-src="{{ $digitalWalletQrUrl }}" data-qr-title="QR de billetera digital" aria-label="Ampliar QR de billetera digital">
                                                        <img src="{{ $digitalWalletQrUrl }}" alt="QR de billetera digital del hotel" class="booking-payment-qr">
                                                        <span><i class="bi bi-arrows-fullscreen" aria-hidden="true"></i> Ampliar QR</span>
                                                    </button>
                                                    <div class="booking-qr-actions">
                                                        <a href="{{ $digitalWalletQrUrl }}" class="btn btn-public-primary btn-sm" download>
                                                            <i class="bi bi-download" aria-hidden="true"></i> Descargar
                                                        </a>
                                                        <button type="button" class="btn btn-public-outline btn-sm" data-open-qr-modal data-qr-src="{{ $digitalWalletQrUrl }}" data-qr-title="QR de billetera digital">
                                                            <i class="bi bi-search" aria-hidden="true"></i> Ver grande
                                                        </button>
                                                    </div>
                                                </div>
                                            </div>
                                        @else
                                            <div class="booking-payment-missing">El QR de billetera digital aun no esta configurado.</div>
                                        @endif
                                    </div>

                                    <div class="booking-payment-guide @if ($selectedPaymentMethod !== 'bank_qr') d-none @endif" data-booking-payment-guide="bank_qr">
                                        <div>
                                            <span class="section-kicker">QR banco local</span>
                                            <h3>Escanea el QR bancario del hotel</h3>
                                            <p>Esta opcion se muestra para clientes bolivianos ubicados en Bolivia. Deposita en <strong data-selected-currency-label>Bs. Bolivianos</strong> y sube tu comprobante.</p>
                                        </div>
                                        @if ($localBankQrUrl)
                                            <div class="booking-qr-grid booking-qr-grid-single">
                                                <div class="booking-qr-panel" data-qr-currency-panel="BOB">
                                                    <strong>QR banco local</strong>
                                                    <small>Usa este QR solo para pagos bancarios locales en Bolivia.</small>
                                                    <button type="button" class="booking-qr-preview" data-open-qr-modal data-qr-src="{{ $localBankQrUrl }}" data-qr-title="QR banco local" aria-label="Ampliar QR banco local">
                                                        <img src="{{ $localBankQrUrl }}" alt="QR banco local del hotel" class="booking-payment-qr">
                                                        <span><i class="bi bi-arrows-fullscreen" aria-hidden="true"></i> Ampliar QR</span>
                                                    </button>
                                                    <div class="booking-qr-actions">
                                                        <a href="{{ $localBankQrUrl }}" class="btn btn-public-primary btn-sm" download>
                                                            <i class="bi bi-download" aria-hidden="true"></i> Descargar
                                                        </a>
                                                        <button type="button" class="btn btn-public-outline btn-sm" data-open-qr-modal data-qr-src="{{ $localBankQrUrl }}" data-qr-title="QR banco local">
                                                            <i class="bi bi-search" aria-hidden="true"></i> Ver grande
                                                        </button>
                                                    </div>
                                                </div>
                                            </div>
                                        @else
                                            <div class="booking-payment-missing">El QR banco local aun no esta configurado.</div>
                                        @endif
                                    </div>

                                    <div class="booking-payment-guide @if (! in_array($selectedPaymentMethod, ['bank_transfer', 'bank_deposit', 'bank'], true)) d-none @endif" data-booking-payment-guide="bank_transfer bank_deposit bank">
                                        <div>
                                            <span class="section-kicker">Cuenta bancaria del hotel</span>
                                            <h3>Deposita o transfiere a esta cuenta</h3>
                                            <p>Realiza tu pago en <strong data-selected-currency-label>Bs. Bolivianos</strong> y conserva el numero de referencia o comprobante.</p>
                                        </div>
                                        @if ($bankConfigured)
                                            <div class="booking-bank-grid">
                                                <div class="booking-bank-item">
                                                    <span><i class="bi bi-bank" aria-hidden="true"></i> Banco</span>
                                                    <strong>{{ $hotelSetting->bank_name ?: '-' }}</strong>
                                                </div>
                                                <div class="booking-bank-item">
                                                    <span><i class="bi bi-person-badge" aria-hidden="true"></i> Titular</span>
                                                    <strong>{{ $hotelSetting->bank_account_holder ?: '-' }}</strong>
                                                </div>
                                                <div class="booking-bank-item booking-bank-item-wide">
                                                    <span><i class="bi bi-credit-card-2-front" aria-hidden="true"></i> Numero de cuenta</span>
                                                    <strong>{{ $hotelSetting->bank_account_number ?: '-' }}</strong>
                                                </div>
                                            </div>
                                        @else
                                            <div class="booking-payment-missing">Los datos bancarios aun no estan configurados.</div>
                                        @endif
                                    </div>
                                </div>

                                <div class="booking-receipt-uploader mt-3">
                                    <input type="hidden" id="booking-payment-amount" name="payment_amount" value="{{ old('payment_amount') }}" min="0.01" step="0.01" required>
                                    @error('payment_amount')
                                        <div class="invalid-feedback d-block">{{ $message }}</div>
                                    @enderror

                                    <label class="form-label" for="booking-receipt">Sube tu comprobante de pago</label>
                                    <input type="file" id="booking-receipt" name="receipt_image" class="form-control @error('receipt_image') is-invalid @enderror" accept=".jpg,.jpeg,.png,.webp,.pdf" required>
                                    <div class="booking-field-help">Formatos permitidos: JPG, PNG, WEBP o PDF. Tamano maximo: 10 MB.</div>
                                    @error('receipt_image')
                                        <div class="invalid-feedback d-block">{{ $message }}</div>
                                    @enderror

                                    <label class="form-label mt-3" for="payment_reference_number">Numero de referencia <span class="text-muted">(opcional)</span></label>
                                    <input type="text" id="payment_reference_number" name="payment_reference_number" class="form-control @error('payment_reference_number') is-invalid @enderror" value="{{ old('payment_reference_number') }}" maxlength="150" placeholder="Ejemplo: codigo de transaccion, deposito o voucher">
                                    @error('payment_reference_number')
                                        <div class="invalid-feedback d-block">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>

                            <div class="booking-step-actions">
                                <div class="booking-step-tip">Luego de enviar, recepcion confirmara la reserva y te indicara el siguiente paso.</div>
                                <div class="booking-step-action-buttons">
                                    <button type="button" class="btn btn-public-ghost" data-step-prev>{{ __('public.booking.back') }}</button>
                                    <button type="button" class="btn btn-public-primary" data-step-next>{{ __('public.booking.review_request') }}</button>
                                </div>
                            </div>
                        </div>

                        <div class="booking-step-panel" data-booking-step data-step-index="4" data-step-label="{{ __('public.booking.confirmation_title') }}" aria-hidden="true">
                            <div class="booking-step-head">
                                <div>
                                    <span class="section-kicker">{{ __('public.booking.confirmation_kicker') }}</span>
                                    <h2>{{ __('public.booking.confirmation_title') }}</h2>
                                    <p>Antes de enviar, revisa que todo este correcto y confirma que entiendes el proceso.</p>
                                </div>
                                <div class="booking-step-badge">{{ __('public.booking.step_badge_final') }}</div>
                            </div>

                            <div class="booking-confirm-grid">
                                <div class="booking-summary-grid" id="booking-summary-grid">
                                    <div><strong>{{ __('public.booking_success.dates') }}</strong><span id="summary-dates">{{ __('public.booking.summary_dates_empty') }}</span></div>
                                    <div><strong>{{ __('public.booking.summary_nights') }}</strong><span id="summary-nights">-</span></div>
                                    <div><strong>{{ __('public.booking_success.room_type') }}</strong><span id="summary-room-type">{{ $selectedType?->name ?: '-' }}</span></div>
                                    <div><strong>{{ __('public.booking.summary_discount') }}</strong><span id="summary-discount">-</span></div>
                                    <div><strong>{{ __('public.booking_success.payment_method') }}</strong><span id="summary-payment-method">{{ $selectedPaymentMethodLabel }}</span></div>
                                </div>
                                <div class="booking-inline-alert" id="summary-deposit-help">
                                    El monto final se muestra aqui, en el paso de pago, segun tu ubicacion, nacionalidad y moneda asignada automaticamente.
                                </div>
                            </div>

                            <div class="booking-accept-card mt-4" data-accept-card>
                                <input type="checkbox" id="accept_terms" name="accept_terms" value="1" class="form-check-input @error('accept_terms') is-invalid @enderror" @checked(old('accept_terms')) required>
                                <label class="booking-accept-card-label" for="accept_terms">
                                    <span class="booking-accept-card-check">
                                        <i class="bi bi-check2" aria-hidden="true"></i>
                                    </span>
                                    <span>
                                        <strong>Marca aqui para confirmar</strong>
                                        <small>{{ __('public.booking.accept_terms_label') }}</small>
                                    </span>
                                </label>
                                @error('accept_terms')
                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="booking-step-actions booking-step-actions-final">
                                <div class="booking-step-tip">{{ __('public.booking.submit_help') }}</div>
                                <div class="booking-step-action-buttons">
                                    <button type="button" class="btn btn-public-ghost" data-step-prev>{{ __('public.booking.back') }}</button>
                                    <button type="submit" class="btn btn-public-primary" id="submit-booking-button" disabled data-ready-text="{{ __('public.booking.book_button') }}" data-waiting-text="Primero marca la confirmacion">Primero marca la confirmacion</button>
                                </div>
                            </div>
                        </div>
                    </form>

                    <div class="booking-submit-loader d-none" id="booking-submit-loader" role="status" aria-live="polite">
                        <div class="booking-submit-loader-card">
                            <div class="booking-submit-spinner" aria-hidden="true"></div>
                            <span class="section-kicker">Enviando solicitud</span>
                            <h3>Espera un momento, por favor</h3>
                            <p>Estamos registrando tu reserva, guardando tu comprobante y notificando a administracion para que puedan aprobarla, observarla o rechazarla.</p>
                        </div>
                    </div>

                    @if ($paymentQrUrl)
                        <div class="booking-qr-modal d-none" id="booking-qr-modal" role="dialog" aria-modal="true" aria-labelledby="booking-qr-modal-title">
                            <div class="booking-qr-modal-backdrop" data-close-qr-modal></div>
                            <div class="booking-qr-modal-card">
                                <button type="button" class="booking-qr-modal-close" data-close-qr-modal aria-label="Cerrar QR">
                                    <i class="bi bi-x-lg" aria-hidden="true"></i>
                                </button>
                                <span class="section-kicker">QR del hotel</span>
                                <h3 id="booking-qr-modal-title">Escanea o descarga el QR</h3>
                                <img src="{{ $paymentQrUrl }}" alt="QR de pago del hotel ampliado" class="booking-qr-modal-image" id="booking-qr-modal-image">
                                <a href="{{ $paymentQrUrl }}" class="btn btn-public-primary" download id="booking-qr-modal-download">
                                    <i class="bi bi-download" aria-hidden="true"></i> Descargar QR
                                </a>
                            </div>
                        </div>
                    @endif
                </div>

                <div class="col-xl-4">
                    <div class="detail-panel sticky-lg-top booking-side-panel booking-side-panel-atg" data-reveal>
                        <span class="section-kicker">{{ __('public.booking.sidebar_kicker') }}</span>
                        <h2>{{ $hotelSetting->hotel_name }}</h2>
                        <p>{{ __('public.booking.sidebar_description') }}</p>

                        <div class="booking-side-card booking-side-current-step">
                            <span>{{ __('public.booking.side_current_step') }}</span>
                            <strong id="wizard-current-step-label">{{ __('public.booking.step1') }}</strong>
                        </div>

                        <div class="booking-side-card">
                            <strong>{{ __('public.booking.side_summary_title') }}</strong>
                                <div class="booking-side-summary">
                                <div><span>{{ __('public.booking.side_summary_dates') }}</span><strong id="side-summary-dates">{{ __('public.booking.summary_dates_empty') }}</strong></div>
                                <div><span>{{ __('public.booking.side_summary_room') }}</span><strong id="side-summary-room">{{ $selectedType?->name ?: '-' }}</strong></div>
                            </div>
                        </div>

                        <div class="booking-side-card">
                            <strong>{{ __('public.booking.sidebar_features_title') }}</strong>
                            <ul class="booking-feature-list mb-0">
                                <li>{{ __('public.booking.sidebar_feature_1') }}</li>
                                <li>{{ __('public.booking.sidebar_feature_2') }}</li>
                                <li>{{ __('public.booking.sidebar_feature_3') }}</li>
                                <li>{{ __('public.booking.sidebar_feature_4') }}</li>
                            </ul>
                        </div>

                        <div class="booking-side-card mt-3">
                            <strong>{{ __('public.booking.sidebar_status_title') }}</strong>
                            <div class="promo-badge mt-2">{{ __('public.booking.sidebar_status_pending') }}</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
@endsection

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ?? '';
            const availabilityUrl = @json(route('public.booking.availability'));
            const quoteUrl = @json(route('public.booking.quote'));
            const availabilityButton = document.getElementById('availability-button');
            const resultsContainer = document.getElementById('availability-results');
            const availabilityStatus = document.getElementById('availability-status');
            const bookingForm = document.getElementById('public-booking-form');
            const bookingSuccessPanel = document.getElementById('booking-success-panel');
            const bookingSuccessMessage = document.getElementById('booking-success-message');
            const bookingSuccessStatusLink = document.getElementById('booking-success-status-link');
            const bookingSuccessNewRequest = document.getElementById('booking-success-new-request');
            const roomTypeSelect = document.getElementById('booking-room-type');
            const roomIdInput = document.getElementById('booking-room-id');
            const submitButton = document.getElementById('submit-booking-button');
            const submitLoader = document.getElementById('booking-submit-loader');
            const qrModal = document.getElementById('booking-qr-modal');
            const qrModalTitle = document.getElementById('booking-qr-modal-title');
            const qrModalImage = document.getElementById('booking-qr-modal-image');
            const qrModalDownload = document.getElementById('booking-qr-modal-download');
            const acceptTermsCard = document.querySelector('[data-accept-card]');
            const openQrModalButtons = document.querySelectorAll('[data-open-qr-modal]');
            const closeQrModalButtons = document.querySelectorAll('[data-close-qr-modal]');
            const paymentMethodFields = document.querySelectorAll('input[name="preferred_payment_method"]');
            const paymentMethodCards = document.querySelectorAll('[data-payment-method-card]');
            const paymentRoutingHelp = document.getElementById('booking-payment-routing-help');
            const paymentGuides = document.querySelectorAll('[data-booking-payment-guide]');
            const wizardCurrentStepLabel = document.getElementById('wizard-current-step-label');
            const progressItems = Array.from(document.querySelectorAll('[data-progress-item]'));
            const stepPanels = Array.from(document.querySelectorAll('[data-booking-step]'));
            const nextButtons = document.querySelectorAll('[data-step-next]');
            const prevButtons = document.querySelectorAll('[data-step-prev]');

            const summary = {
                dates: document.getElementById('summary-dates'),
                nights: document.getElementById('summary-nights'),
                roomType: document.getElementById('summary-room-type'),
                pricePerNight: document.getElementById('summary-price-per-night'),
                discount: document.getElementById('summary-discount'),
                deposit: document.getElementById('summary-deposit'),
                paymentAmount: document.getElementById('summary-payment-amount'),
                paymentMethod: document.getElementById('summary-payment-method'),
                total: document.getElementById('summary-total'),
                sideDates: document.getElementById('side-summary-dates'),
                sideRoom: document.getElementById('side-summary-room'),
                sideDeposit: document.getElementById('side-summary-deposit'),
                sideTotal: document.getElementById('side-summary-total'),
            };
            const setText = (element, value) => {
                if (element) {
                    element.textContent = value;
                }
            };
            const depositHelp = document.getElementById('summary-deposit-help');
            const paymentAmountHelp = document.getElementById('booking-payment-amount-help');
            const attractionCopy = document.getElementById('booking-payment-attraction-copy');
            const attractionPercentage = document.getElementById('booking-attraction-percentage');
            const attractionMinimum = document.getElementById('booking-attraction-minimum');
            const attractionTotalLabel = document.getElementById('booking-attraction-total-label');
            const attractionTotal = document.getElementById('booking-attraction-total');
            const attractionDiscount = document.getElementById('booking-attraction-discount');
            const selectedCurrencyLabels = document.querySelectorAll('[data-selected-currency-label]');
            const qrCurrencyPanels = document.querySelectorAll('[data-qr-currency-panel]');
            const receiptSummary = document.getElementById('booking-receipt-summary');
            const paymentAmountLabel = document.getElementById('booking-payment-amount-label');
            const templates = {
                availableCount: @json($bookingAvailableCountTemplate),
                maxGuests: @json($bookingMaxGuestsTemplate),
                baseLabel: @json($bookingBaseLabelTemplate),
                depositHelp: @json($bookingDepositHelpTemplate),
                dateRange: @json($bookingDateRangeTemplate),
                nightsCount: @json($bookingNightsCountTemplate),
            };

            const fields = {
                checkIn: document.getElementById('booking-check-in'),
                checkOut: document.getElementById('booking-check-out'),
                adults: document.getElementById('booking-adults'),
                children: document.getElementById('booking-children'),
                fullName: document.getElementById('full_name'),
                nationality: document.getElementById('nationality'),
                whatsapp: document.getElementById('whatsapp'),
                whatsappCode: document.getElementById('whatsapp-code'),
                whatsappNumber: document.getElementById('whatsapp-number'),
                visitorLocation: document.getElementById('booking-visitor-location'),
                paymentCurrency: document.getElementById('booking-payment-currency'),
                paymentAmount: document.getElementById('booking-payment-amount'),
                receiptImage: document.getElementById('booking-receipt'),
                acceptTerms: document.getElementById('accept_terms'),
            };

            let availabilityLoaded = false;
            let selectedAvailableTypeId = roomTypeSelect.value || null;
            let selectedRoomLabel = '';
            let availabilityTimeout = null;
            let currentStep = 0;
            let currentQuote = null;

            const setAvailabilityMessage = (message) => {
                availabilityStatus.textContent = message;
            };

            const setSummaryDefaults = () => {
                summary.dates.textContent = @json(__('public.booking.summary_dates_empty'));
                summary.nights.textContent = '-';
                setText(summary.pricePerNight, '-');
                summary.discount.textContent = '-';
                setText(summary.deposit, '-');
                setText(summary.paymentAmount, '-');
                setText(summary.total, '-');
                summary.sideDates.textContent = @json(__('public.booking.summary_dates_empty'));
                setText(summary.sideDeposit, '-');
                setText(summary.sideTotal, '-');
                depositHelp.textContent = 'El monto se muestra unicamente en el paso de pago.';
                if (paymentAmountHelp) {
                    if (paymentAmountHelp) {
                        paymentAmountHelp.textContent = 'Primero elige una habitacion para ver el anticipo minimo requerido.';
                    }
                }
                if (receiptSummary) {
                    receiptSummary.textContent = 'Primero elige una habitacion para ver cuanto debes depositar.';
                }
                setText(attractionPercentage, 'Anticipo minimo');
                setText(attractionMinimum, '-');
                setText(attractionTotalLabel, 'Total de tu estadia');
                setText(attractionTotal, '-');
                setText(attractionDiscount, 'Sin promocion');
                setText(attractionCopy, 'Elige una habitacion y aqui veras el anticipo minimo y el total final de tu estadia.');
                if (fields.paymentCurrency) {
                    fields.paymentCurrency.value = isLocalBolivianPaymentFlow() ? 'BOB' : 'USD';
                }
                syncCurrencyCopy();
                currentQuote = null;
                submitButton.disabled = true;
            };

            const formatBookingMoney = (amount, currency = null) => {
                const numericAmount = Number(amount || 0);
                const selectedCurrency = currency || fields.paymentCurrency?.value || @json($hotelSetting->baseCurrency());
                const symbol = selectedCurrency === 'USD' ? '$us ' : 'Bs. ';

                return `${symbol}${numericAmount.toFixed(2)}`;
            };

            const visitorCurrency = () => {
                const htmlCurrency = document.documentElement.dataset.publicCurrency;

                return htmlCurrency || 'BOB';
            };

            const selectedPaymentCurrency = () => fields.paymentCurrency?.value || @json($hotelSetting->baseCurrency());

            const currencyName = (currency) => currency === 'USD' ? '$us Dolares' : 'Bs. Bolivianos';

            const normalizeText = (value) => String(value || '')
                .normalize('NFD')
                .replace(/[\u0300-\u036f]/g, '')
                .trim()
                .toLowerCase();

            const isBoliviaText = (value) => ['bolivia', 'boliviana', 'boliviano', 'bo'].includes(normalizeText(value));

            const syncWhatsappValue = () => {
                if (!fields.whatsapp) {
                    return;
                }

                const code = fields.whatsappCode?.value || '';
                const number = (fields.whatsappNumber?.value || '').trim();
                fields.whatsapp.value = number ? `${code} ${number}`.trim() : '';
            };

            const visitorIsInBolivia = () => {
                const htmlCurrency = document.documentElement.dataset.publicCurrency;

                if (htmlCurrency) {
                    return htmlCurrency === 'BOB';
                }

                const timezone = Intl.DateTimeFormat().resolvedOptions().timeZone || '';
                const languages = navigator.languages?.length ? navigator.languages : [navigator.language].filter(Boolean);

                return timezone === 'America/La_Paz' || languages.some((language) => /(^|-)BO$/i.test(language));
            };

            const customerIsBolivian = () => isBoliviaText(fields.nationality?.value);

            const isLocalBolivianPaymentFlow = () => visitorIsInBolivia() && customerIsBolivian();

            const allowedPaymentMethods = () => isLocalBolivianPaymentFlow()
                ? ['bank_qr', 'bank_transfer', 'bank_deposit']
                : ['qr'];

            const syncPaymentRouting = () => {
                const isLocalFlow = isLocalBolivianPaymentFlow();
                const allowedMethods = allowedPaymentMethods();

                if (fields.visitorLocation) {
                    fields.visitorLocation.value = visitorIsInBolivia() ? 'BO' : 'FOREIGN';
                }

                if (paymentRoutingHelp) {
                    paymentRoutingHelp.textContent = isLocalFlow
                        ? 'Como estas en Bolivia y tus datos son bolivianos, se muestran pagos locales: QR banco, transferencia o deposito.'
                        : 'Para evitar confusion, se muestra solo QR de billetera digital para visitantes fuera de Bolivia o clientes extranjeros.';
                }

                paymentMethodCards.forEach((card) => {
                    const method = card.dataset.paymentMethod;
                    const configured = card.dataset.configured === 'true';
                    const allowed = allowedMethods.includes(method);
                    const enabled = configured && allowed;
                    const input = card.querySelector('input[name="preferred_payment_method"]');

                    card.hidden = !allowed;
                    card.classList.toggle('is-disabled', !enabled);

                    if (input) {
                        input.disabled = !enabled;
                        if (!enabled) {
                            input.checked = false;
                        }
                    }
                });

                if (!document.querySelector('input[name="preferred_payment_method"]:checked:not(:disabled)')) {
                    const firstEnabled = Array.from(paymentMethodFields).find((field) => !field.disabled);
                    if (firstEnabled) {
                        firstEnabled.checked = true;
                    }
                }

                if (fields.paymentCurrency) {
                    fields.paymentCurrency.value = isLocalFlow ? 'BOB' : 'USD';
                }

                syncPaymentMethodSummary();
                syncPaymentGuides();
                syncCurrencyCopy();

                if (currentQuote) {
                    syncPaymentAmountLimits(true);
                }
            };

            const syncCurrencyCopy = () => {
                const currency = selectedPaymentCurrency();

                selectedCurrencyLabels.forEach((element) => {
                    element.textContent = currencyName(currency);
                });

                qrCurrencyPanels.forEach((panel) => {
                    const currencies = String(panel.dataset.qrCurrencyPanel || '').split(/\s+/).filter(Boolean);
                    panel.classList.toggle('d-none', !currencies.includes('ANY') && !currencies.includes(currency));
                });

                if (paymentAmountLabel) {
                    paymentAmountLabel.textContent = `Monto en ${currencyName(currency)}`;
                }
            };

            const quoteAmount = (key, currency) => {
                if (!currentQuote) {
                    return 0;
                }

                const suffix = currency === 'USD' ? 'usd' : 'bob';
                return Number(currentQuote[`${key}_${suffix}`] ?? currentQuote[key] ?? 0);
            };

            const quoteAmountFormatted = (key, currency) => {
                if (!currentQuote) {
                    return '-';
                }

                const suffix = currency === 'USD' ? 'usd' : 'bob';
                return currentQuote[`${key}_${suffix}_formatted`] ?? currentQuote[`${key}_formatted`] ?? '-';
            };

            const syncPaymentAmountSummary = () => {
                const amount = Number(fields.paymentAmount?.value || 0);
                setText(summary.paymentAmount, amount > 0 ? formatBookingMoney(amount, selectedPaymentCurrency()) : '-');
            };

            const syncPaymentAmountLimits = (shouldAutofill = false) => {
                if (!currentQuote || !fields.paymentAmount) {
                    return;
                }

                if (fields.paymentCurrency?.value === 'USD' && currentQuote.supports_usd === false) {
                    fields.paymentCurrency.value = 'BOB';
                }

                const currency = selectedPaymentCurrency();
                const minAmount = quoteAmount('deposit_amount_required', currency);
                const maxAmount = quoteAmount('total_amount', currency);
                const discountAmount = quoteAmount('discount_total_amount', currency);
                const discountLabel = currentQuote.discount_label || '';
                const depositPercentage = Number(currentQuote.deposit_percentage || 0);

                syncCurrencyCopy();

                fields.paymentAmount.min = minAmount > 0 ? minAmount.toFixed(2) : '0.01';
                fields.paymentAmount.max = maxAmount > 0 ? maxAmount.toFixed(2) : '';
                fields.paymentAmount.placeholder = minAmount > 0 ? formatBookingMoney(minAmount, currency) : 'Ejemplo: 20.00';

                if (shouldAutofill && minAmount > 0 && (!fields.paymentAmount.value || Number(fields.paymentAmount.value) < minAmount || (maxAmount > 0 && Number(fields.paymentAmount.value) > maxAmount))) {
                    fields.paymentAmount.value = minAmount.toFixed(2);
                }

                if (paymentAmountHelp) {
                    paymentAmountHelp.textContent = currentQuote.supports_usd === false
                        ? `Debes depositar minimo ${quoteAmountFormatted('deposit_amount_required', currency)}. Esta habitacion aun no tiene precio en dolares registrado.`
                        : `Anticipo minimo: ${quoteAmountFormatted('deposit_amount_required', currency)} (${depositPercentage}%). Total de la estadia: ${quoteAmountFormatted('total_amount', currency)}.`;
                }

                if (receiptSummary) {
                    receiptSummary.textContent = `Para reservar debes depositar minimo ${quoteAmountFormatted('deposit_amount_required', currency)}. El total estimado de tu estadia es ${quoteAmountFormatted('total_amount', currency)}.`;
                }

                setText(attractionPercentage, depositPercentage > 0 ? `Anticipo minimo ${depositPercentage}%` : 'Anticipo minimo');
                setText(attractionMinimum, quoteAmountFormatted('deposit_amount_required', currency));
                setText(
                    attractionTotalLabel,
                    discountAmount > 0 && discountLabel
                        ? `Total con ${discountLabel} aplicado`
                        : 'Total de tu estadia'
                );
                setText(attractionTotal, quoteAmountFormatted('total_amount', currency));
                setText(
                    attractionDiscount,
                    discountAmount > 0
                        ? (currentQuote.promotion_name || discountLabel || 'Promocion aplicada')
                        : 'Sin promocion'
                );
                setText(
                    attractionCopy,
                    discountAmount > 0
                        ? 'El total mostrado ya incluye la promocion. Deposita el anticipo minimo para que el hotel revise y confirme tu reserva.'
                        : 'Deposita el anticipo minimo para que el hotel revise y confirme tu reserva.'
                );

            };

            const validatePaymentAmount = () => {
                if (!fields.paymentAmount) {
                    return true;
                }

                const amount = Number(fields.paymentAmount.value || 0);
                const currency = selectedPaymentCurrency();
                const requiredDeposit = quoteAmount('deposit_amount_required', currency);
                const totalAmount = quoteAmount('total_amount', currency);

                fields.paymentAmount.setCustomValidity('');

                if (!amount || amount <= 0) {
                    fields.paymentAmount.setCustomValidity('Indica cuanto estas depositando.');
                } else if (requiredDeposit > 0 && amount < requiredDeposit) {
                    fields.paymentAmount.setCustomValidity(`El anticipo minimo para esta reserva es ${formatBookingMoney(requiredDeposit, currency)}.`);
                } else if (totalAmount > 0 && amount > totalAmount) {
                    fields.paymentAmount.setCustomValidity(`El monto depositado no puede superar el total de ${formatBookingMoney(totalAmount, currency)}.`);
                }

                syncPaymentAmountSummary();

                return ensureFieldValidity(fields.paymentAmount);
            };

            const syncPaymentMethodSummary = () => {
                const checkedMethod = document.querySelector('input[name="preferred_payment_method"]:checked');
                summary.paymentMethod.textContent = checkedMethod
                    ? checkedMethod.closest('label')?.querySelector('strong')?.textContent?.trim() ?? @json(__('public.messages.payment_other'))
                    : @json(__('public.messages.payment_other'));
            };

            const syncPaymentGuides = () => {
                const checkedMethod = document.querySelector('input[name="preferred_payment_method"]:checked');
                const selectedValue = checkedMethod?.value ?? '';

                paymentGuides.forEach((guide) => {
                    const values = String(guide.dataset.bookingPaymentGuide || '').split(/\s+/).filter(Boolean);
                    guide.classList.toggle('d-none', !values.includes(selectedValue));
                });
            };

            const openQrModal = (trigger = null) => {
                if (!qrModal) {
                    return;
                }

                const source = trigger?.dataset?.qrSrc;
                const title = trigger?.dataset?.qrTitle;

                if (source && qrModalImage && qrModalDownload) {
                    qrModalImage.src = source;
                    qrModalDownload.href = source;
                }

                if (title && qrModalTitle) {
                    qrModalTitle.textContent = title;
                }

                qrModal.classList.remove('d-none');
                document.body.classList.add('overflow-hidden');
            };

            const closeQrModal = () => {
                if (!qrModal) {
                    return;
                }

                qrModal.classList.add('d-none');
                document.body.classList.remove('overflow-hidden');
            };

            const hasMinimumSearchData = () => {
                return Boolean(
                    fields.checkIn.value &&
                    fields.checkOut.value &&
                    fields.adults.value &&
                    Number(fields.adults.value) > 0
                );
            };

            const updateWizardUi = () => {
                stepPanels.forEach((panel, index) => {
                    const isActive = index === currentStep;
                    panel.classList.toggle('is-active', isActive);
                    panel.classList.toggle('is-complete', index < currentStep);
                    panel.setAttribute('aria-hidden', isActive ? 'false' : 'true');
                });

                progressItems.forEach((item, index) => {
                    item.classList.toggle('is-active', index === currentStep);
                    item.classList.toggle('is-complete', index < currentStep);
                });

                wizardCurrentStepLabel.textContent = stepPanels[currentStep]?.dataset.stepLabel ?? '';
                window.scrollTo({ top: 0, behavior: 'smooth' });
            };

            const setStep = (index) => {
                currentStep = Math.max(0, Math.min(index, stepPanels.length - 1));
                updateWizardUi();
            };

            const syncAcceptTermsState = () => {
                const isAccepted = Boolean(fields.acceptTerms?.checked);

                acceptTermsCard?.classList.toggle('is-checked', isAccepted);

                if (submitButton) {
                    submitButton.textContent = isAccepted
                        ? (submitButton.dataset.readyText || submitButton.textContent)
                        : (submitButton.dataset.waitingText || submitButton.textContent);
                    submitButton.disabled = !isAccepted || !availabilityLoaded;
                }
            };

            const ensureFieldValidity = (input) => {
                if (!input) {
                    return true;
                }

                if (input.checkValidity()) {
                    return true;
                }

                input.reportValidity();
                return false;
            };

            const escapeHtml = (value) => String(value ?? '').replace(/[&<>"']/g, (character) => ({
                '&': '&amp;',
                '<': '&lt;',
                '>': '&gt;',
                '"': '&quot;',
                "'": '&#039;',
            }[character]));

            const validateCurrentStep = async () => {
                if (currentStep === 0) {
                    if (!ensureFieldValidity(fields.checkIn) || !ensureFieldValidity(fields.checkOut) || !ensureFieldValidity(fields.adults)) {
                        return false;
                    }

                    await fetchAvailability();
                    return true;
                }

                if (currentStep === 1) {
                    if (!availabilityLoaded) {
                        await fetchAvailability();
                    }

                    if (!selectedAvailableTypeId || !roomTypeSelect.value || !roomIdInput.value || String(selectedAvailableTypeId) !== String(roomTypeSelect.value)) {
                        setAvailabilityMessage('Selecciona una habitacion disponible para continuar.');
                        return false;
                    }

                    return true;
                }

                if (currentStep === 2) {
                    return ensureFieldValidity(fields.fullName);
                }

                if (currentStep === 3) {
                    syncPaymentRouting();
                    const checkedMethod = document.querySelector('input[name="preferred_payment_method"]:checked:not(:disabled)');

                    if (!checkedMethod) {
                        paymentMethodFields[0]?.reportValidity();
                        return false;
                    }

                    return validatePaymentAmount() && ensureFieldValidity(fields.receiptImage);
                }

                if (currentStep === 4) {
                    if (submitButton.disabled) {
                        return false;
                    }

                    return ensureFieldValidity(fields.acceptTerms);
                }

                return true;
            };

            const scheduleAvailabilityFetch = () => {
                availabilityLoaded = false;
                syncAcceptTermsState();

                if (availabilityTimeout) {
                    window.clearTimeout(availabilityTimeout);
                }

                if (!hasMinimumSearchData()) {
                    setSummaryDefaults();
                    setAvailabilityMessage(@json(__('public.booking.complete_search_data')));
                    resultsContainer.innerHTML = `<div class="empty-state-public">${@json(__('public.booking.availability_empty'))}</div>`;
                    return;
                }

                availabilityTimeout = window.setTimeout(() => {
                    fetchAvailability();
                }, 450);
            };

            const renderAvailabilityResults = (rooms) => {
                if (!rooms.length) {
                    resultsContainer.innerHTML = `
                        <div class="empty-state-public booking-empty-state-rich">
                            <strong>No encontramos habitaciones libres para esos datos.</strong>
                            <span>Cambia las fechas o reduce la cantidad de huespedes para ver otras habitaciones.</span>
                        </div>
                    `;
                    return;
                }

                resultsContainer.innerHTML = `
                    <div class="booking-room-choice-grid">
                        ${rooms.map((room) => {
                            const selectedRoom = String(roomIdInput.value || '') === String(room.id);
                            const images = Array.isArray(room.gallery_images) ? room.gallery_images : [];
                            const imageHtml = images.length
                                ? `<div class="booking-room-choice-gallery">${images.slice(0, 4).map((src, index) => `<img src="${escapeHtml(src)}" alt="Habitacion ${escapeHtml(room.number)}" class="${index === 0 ? 'is-main' : ''}">`).join('')}</div>`
                                : `<div class="booking-room-choice-placeholder"><i class="bi bi-image"></i></div>`;

                            return `
                            <article class="booking-room-choice booking-room-choice-room ${selectedRoom ? 'is-selected' : ''}" data-room-card="${room.id}">
                                ${imageHtml}
                                <div class="booking-room-choice-body">
                                    <span class="booking-result-kicker">Habitacion disponible</span>
                                    <strong>Habitacion ${escapeHtml(room.number)}</strong>
                                    <small>${escapeHtml(room.room_type_name || 'Habitacion')} ${room.floor ? ` / ${escapeHtml(room.floor)}` : ''}</small>
                                    ${room.description ? `<p>${escapeHtml(room.description)}</p>` : ''}
                                    <div class="booking-result-meta mt-3">
                                        <span><i class="bi bi-people"></i> ${templates.maxGuests.replace('__COUNT__', room.max_guests)}</span>
                                        <span><i class="bi bi-percent"></i> Anticipo: ${room.deposit_percentage}%</span>
                                        ${room.promotion ? `<span><i class="bi bi-tag"></i> ${escapeHtml(room.promotion.name)} - ${escapeHtml(room.promotion.discount_label)}</span>` : ''}
                                    </div>
                                    <button type="button" class="btn btn-public-outline booking-select-button mt-3" data-select-room-type="${room.room_type_id}" data-select-room-id="${room.id}" data-room-type-name="${escapeHtml(room.room_type_name)}" data-room-number="${escapeHtml(room.number)}">
                                        ${selectedRoom ? 'Habitacion seleccionada' : 'Elegir esta habitacion'}
                                    </button>
                                </div>
                            </article>
                            `;
                        }).join('')}
                    </div>
                `;
            };

            const availableRoomLabel = (room) => `Habitacion ${room.number || ''} - ${room.room_type_name || ''}`.trim();

            const bestAvailableRoom = (rooms) => [...rooms].sort((first, second) => {
                const capacityDifference = Number(first.max_guests || 0) - Number(second.max_guests || 0);

                if (capacityDifference !== 0) {
                    return capacityDifference;
                }

                return String(first.number || '').localeCompare(String(second.number || ''), 'es', { numeric: true });
            })[0] || null;

            const selectAvailableRoom = (room) => {
                if (!room) {
                    roomTypeSelect.value = '';
                    selectedAvailableTypeId = null;
                    roomIdInput.value = '';
                    selectedRoomLabel = '';
                    summary.roomType.textContent = '-';
                    summary.sideRoom.textContent = '-';
                    return;
                }

                roomTypeSelect.value = room.room_type_id;
                selectedAvailableTypeId = String(room.room_type_id);
                roomIdInput.value = room.id || '';
                selectedRoomLabel = availableRoomLabel(room);
                summary.roomType.textContent = selectedRoomLabel;
                summary.sideRoom.textContent = selectedRoomLabel;

                document.querySelectorAll('.booking-room-choice').forEach((card) => {
                    card.classList.toggle('is-selected', String(card.dataset.roomCard) === String(room.id));
                });

                document.querySelectorAll('[data-select-room-id]').forEach((button) => {
                    const isSelected = String(button.dataset.selectRoomId) === String(room.id);
                    button.textContent = isSelected ? 'Habitacion seleccionada' : 'Elegir esta habitacion';
                });
            };

            const buildAvailabilityParams = () => {
                const params = new URLSearchParams();
                params.set('check_in', fields.checkIn.value);
                params.set('check_out', fields.checkOut.value);
                params.set('adults', fields.adults.value);
                params.set('children', fields.children.value || '0');

                return params;
            };

            const fetchAvailability = async () => {
                setSummaryDefaults();
                availabilityLoaded = false;
                setAvailabilityMessage(@json(__('public.booking.searching')));
                resultsContainer.innerHTML = `<div class="empty-state-public">${@json(__('public.booking.searching'))}</div>`;

                try {
                    const response = await fetch(`${availabilityUrl}?${buildAvailabilityParams().toString()}`, {
                        headers: {
                            'Accept': 'application/json',
                        },
                    });

                    if (!response.ok) {
                        throw new Error(@json(__('public.booking.availability_none')));
                    }

                    const payload = await response.json();
                    availabilityLoaded = true;
                    const availableRooms = payload.rooms ?? [];
                    const previousRoomId = roomIdInput.value;
                    const selectedStillAvailable = roomIdInput.value
                        ? availableRooms.some((room) => String(room.id) === String(roomIdInput.value))
                        : false;
                    const selectedRoom = selectedStillAvailable
                        ? availableRooms.find((room) => String(room.id) === String(roomIdInput.value))
                        : bestAvailableRoom(availableRooms);

                    selectAvailableRoom(selectedRoom);
                    renderAvailabilityResults(availableRooms);
                    selectAvailableRoom(selectedRoom);

                    if (payload.available && selectedRoom) {
                        const message = previousRoomId && !selectedStillAvailable
                            ? @json(__('public.booking.auto_room_changed'))
                            : @json(__('public.booking.ready'));

                        setAvailabilityMessage(message);
                        await fetchQuote();
                    } else {
                        setAvailabilityMessage(@json(__('public.booking.availability_none')));
                        summary.roomType.textContent = '-';
                        summary.sideRoom.textContent = '-';
                    }
                } catch (error) {
                    setAvailabilityMessage(error.message);
                    resultsContainer.innerHTML = `<div class="empty-state-public">${error.message}</div>`;
                }
            };

            const fetchQuote = async () => {
                if (!roomTypeSelect.value) {
                    setSummaryDefaults();
                    return;
                }

                const selectedRoomName = selectedRoomLabel || (roomTypeSelect.selectedOptions[0]?.dataset?.name ?? '-');
                summary.roomType.textContent = selectedRoomName;
                summary.sideRoom.textContent = selectedRoomName;
                setText(summary.pricePerNight, @json(__('public.booking.searching')));
                summary.discount.textContent = @json(__('public.booking.searching'));
                setText(summary.total, @json(__('public.booking.searching')));

                const formData = new FormData();
                formData.append('room_type_id', roomTypeSelect.value);
                if (roomIdInput.value) {
                    formData.append('room_id', roomIdInput.value);
                }
                formData.append('check_in', fields.checkIn.value);
                formData.append('check_out', fields.checkOut.value);
                formData.append('adults', fields.adults.value);
                formData.append('children', fields.children.value || '0');

                try {
                    const response = await fetch(quoteUrl, {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': csrfToken,
                            'Accept': 'application/json',
                        },
                        body: formData,
                    });

                    const payload = await response.json();

                    if (!response.ok) {
                        throw new Error(payload.message || Object.values(payload.errors || {}).flat()[0] || @json(__('public.booking.availability_none')));
                    }

                    const formattedDates = templates.dateRange
                        .replace('__FROM__', fields.checkIn.value)
                        .replace('__TO__', fields.checkOut.value);

                    summary.dates.textContent = formattedDates;
                    summary.nights.textContent = templates.nightsCount.replace('__COUNT__', payload.nights);
                    setText(summary.pricePerNight, payload.price_per_night_formatted);
                    summary.discount.textContent = payload.discount_amount > 0
                        ? (payload.discount_label || 'Oferta aplicada')
                        : '-';
                    setText(summary.deposit, payload.deposit_amount_required_formatted);
                    setText(summary.total, payload.total_amount_formatted);
                    summary.sideDates.textContent = formattedDates;
                    setText(summary.sideDeposit, payload.deposit_amount_required_formatted);
                    setText(summary.sideTotal, payload.total_amount_formatted);
                    currentQuote = payload;
                    syncPaymentAmountLimits(true);
                    syncPaymentAmountSummary();
                    depositHelp.textContent = 'El monto fue mostrado en el paso de pago. Revisa fechas, habitacion, descuento y metodo antes de enviar.';
                    syncAcceptTermsState();
                    setAvailabilityMessage(@json(__('public.booking.ready')));
                } catch (error) {
                    setText(summary.pricePerNight, '-');
                    summary.discount.textContent = error.message;
                    setText(summary.deposit, '-');
                    setText(summary.paymentAmount, '-');
                    setText(summary.total, '-');
                    setText(summary.sideDeposit, '-');
                    setText(summary.sideTotal, '-');
                    depositHelp.textContent = 'El monto se muestra unicamente en el paso de pago.';
                    paymentAmountHelp.textContent = 'Primero elige una habitacion para ver el anticipo minimo requerido.';
                    currentQuote = null;
                    syncAcceptTermsState();
                    setAvailabilityMessage(error.message);
                }
            };

            availabilityButton?.addEventListener('click', fetchAvailability);

            document.getElementById('availability-results').addEventListener('click', async (event) => {
                const button = event.target.closest('[data-select-room-type]');

                if (!button) {
                    return;
                }

                selectAvailableRoom({
                    id: button.dataset.selectRoomId || '',
                    room_type_id: button.dataset.selectRoomType,
                    room_type_name: button.dataset.roomTypeName || '',
                    number: button.dataset.roomNumber || '',
                });

                await fetchQuote();
                setStep(2);
            });

            [fields.checkIn, fields.checkOut, fields.adults, fields.children, roomTypeSelect].forEach((field) => {
                field.addEventListener('change', () => {
                    if (field === roomTypeSelect) {
                        selectedAvailableTypeId = null;
                        roomIdInput.value = '';
                        selectedRoomLabel = '';
                        const selectedRoomName = roomTypeSelect.selectedOptions[0]?.dataset?.name ?? '-';
                        summary.roomType.textContent = selectedRoomName;
                        summary.sideRoom.textContent = selectedRoomName;
                    }

                    scheduleAvailabilityFetch();
                });

                field.addEventListener('input', () => {
                    if (field === roomTypeSelect) {
                        selectedAvailableTypeId = null;
                        return;
                    }

                    scheduleAvailabilityFetch();
                });
            });

            paymentMethodFields.forEach((field) => {
                field.addEventListener('change', () => {
                    syncPaymentMethodSummary();
                    syncPaymentGuides();
                });
            });

            [fields.nationality].forEach((field) => {
                field?.addEventListener('input', syncPaymentRouting);
                field?.addEventListener('change', syncPaymentRouting);
            });

            if (window.jQuery) {
                [fields.nationality].forEach((field) => {
                    if (field) {
                        window.jQuery(field).on('select2:select select2:clear change', syncPaymentRouting);
                    }
                });
            }

            fields.paymentAmount?.addEventListener('input', () => {
                fields.paymentAmount.setCustomValidity('');
                syncPaymentAmountSummary();
            });

            fields.whatsappCode?.addEventListener('change', syncWhatsappValue);
            fields.whatsappNumber?.addEventListener('input', syncWhatsappValue);
            fields.acceptTerms?.addEventListener('change', syncAcceptTermsState);

            fields.paymentCurrency?.addEventListener('change', () => {
                fields.paymentAmount?.setCustomValidity('');
                syncCurrencyCopy();

                if (currentQuote) {
                    syncPaymentAmountLimits(true);
                }

                syncPaymentAmountSummary();
            });

            openQrModalButtons.forEach((button) => {
                button.addEventListener('click', () => openQrModal(button));
            });

            closeQrModalButtons.forEach((button) => {
                button.addEventListener('click', closeQrModal);
            });

            document.addEventListener('keydown', (event) => {
                if (event.key === 'Escape') {
                    closeQrModal();
                }
            });

            nextButtons.forEach((button) => {
                button.addEventListener('click', async () => {
                    const canContinue = await validateCurrentStep();

                    if (!canContinue) {
                        return;
                    }

                    setStep(currentStep + 1);
                });
            });

            prevButtons.forEach((button) => {
                button.addEventListener('click', () => {
                    setStep(currentStep - 1);
                });
            });

            bookingForm.addEventListener('submit', async (event) => {
                event.preventDefault();

                if (currentStep !== 4) {
                    const canContinue = await validateCurrentStep();

                    if (!canContinue) {
                        return;
                    }

                    setStep(4);
                    return;
                }

                const canSubmit = await validateCurrentStep();

                if (!canSubmit) {
                    return;
                }

                await submitBookingRequest();
            });

            bookingSuccessNewRequest?.addEventListener('click', () => {
                bookingSuccessPanel.classList.add('d-none');
                bookingForm.closest('.row')?.classList.remove('d-none');
                document.querySelector('.booking-progress-board')?.classList.remove('d-none');
                bookingForm.reset();
                selectedAvailableTypeId = null;
                availabilityLoaded = false;
                setSummaryDefaults();
                syncCurrencyCopy();
                syncPaymentMethodSummary();
                syncPaymentGuides();
                setStep(0);
                scheduleAvailabilityFetch();
            });

            async function submitBookingRequest() {
                syncWhatsappValue();
                submitButton.disabled = true;
                submitButton.dataset.originalText = submitButton.dataset.originalText || submitButton.textContent;
                submitButton.innerHTML = '<span class="spinner-border spinner-border-sm me-2" aria-hidden="true"></span>Enviando solicitud...';
                submitLoader?.classList.remove('d-none');
                clearInlineValidation();

                try {
                    const response = await fetch(bookingForm.action, {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': csrfToken,
                            'Accept': 'application/json',
                        },
                        body: new FormData(bookingForm),
                    });

                    const payload = await parseJsonResponse(response);

                    if (!response.ok || payload.nonJson) {
                        showInlineValidation(payload);
                        submitButton.disabled = false;
                        submitLoader?.classList.add('d-none');
                        return;
                    }

                    bookingSuccessMessage.textContent = `${payload.message || 'Tu solicitud fue enviada correctamente.'} Codigo: ${payload.code || '-'}`;
                    bookingSuccessStatusLink.href = payload.status_url || '#';
                    submitLoader?.classList.add('d-none');
                    bookingSuccessPanel.classList.remove('d-none');
                    bookingForm.closest('.row')?.classList.add('d-none');
                    document.querySelector('.booking-progress-board')?.classList.add('d-none');
                    bookingSuccessPanel.scrollIntoView({ behavior: 'smooth', block: 'start' });
                } catch (error) {
                    showInlineValidation({ message: error.message || 'No se pudo enviar la solicitud.' });
                    submitButton.disabled = false;
                    submitLoader?.classList.add('d-none');
                } finally {
                    submitButton.innerHTML = submitButton.dataset.originalText || submitButton.textContent;
                }
            }

            async function parseJsonResponse(response) {
                const contentType = response.headers.get('content-type') || '';

                if (contentType.includes('application/json')) {
                    return response.json();
                }

                return {
                    nonJson: true,
                    message: 'El servidor no devolvio una respuesta valida. Revisa tu sesion e intenta otra vez.',
                };
            }

            function showInlineValidation(payload) {
                const errors = payload.errors || {};
                const firstMessage = payload.message || Object.values(errors).flat()[0] || 'Revisa los datos del formulario.';

                Object.entries(errors).forEach(([field, messages]) => {
                    const input = bookingForm.querySelector(`[name="${field}"]`)
                        || document.querySelector(`[name="${field}"][form="public-booking-form"]`)
                        || document.querySelector(`[name="${field}"]`);

                    if (!input) {
                        return;
                    }

                    input.classList.add('is-invalid');

                    const feedback = document.createElement('div');
                    feedback.className = 'invalid-feedback d-block booking-dynamic-error';
                    feedback.textContent = Array.isArray(messages) ? messages[0] : messages;
                    input.closest('.booking-receipt-uploader, .booking-payment-dock, .booking-step-grid, .booking-special-panel, .form-check, div')?.appendChild(feedback);
                });

                availabilityStatus.textContent = firstMessage;
            }

            function clearInlineValidation() {
                bookingForm.querySelectorAll('.is-invalid').forEach((field) => field.classList.remove('is-invalid'));
                document.querySelectorAll('[form="public-booking-form"].is-invalid').forEach((field) => field.classList.remove('is-invalid'));
                bookingForm.querySelectorAll('.booking-dynamic-error').forEach((error) => error.remove());
                document.querySelectorAll('.booking-payment-side-card .booking-dynamic-error').forEach((error) => error.remove());
            }

            const invalidField = document.querySelector('.is-invalid');

            @if ($selectedType)
                fetchAvailability();
            @else
                summary.roomType.textContent = roomTypeSelect.selectedOptions[0]?.dataset?.name ?? '-';
                summary.sideRoom.textContent = roomTypeSelect.selectedOptions[0]?.dataset?.name ?? '-';
                scheduleAvailabilityFetch();
            @endif

            if (invalidField) {
                const invalidStep = invalidField.closest('[data-booking-step]');

                if (invalidStep) {
                    setStep(Number(invalidStep.dataset.stepIndex || 0));
                }
            } else {
                updateWizardUi();
            }

            syncCurrencyCopy();
            syncPaymentRouting();
            syncPaymentMethodSummary();
            syncPaymentGuides();
            syncAcceptTermsState();
        });
    </script>
@endpush
