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
        $bobQrUrl = $bankQrUrl ?: $walletQrUrl;
        $usdQrUrl = $walletQrUrl ?: $bankQrUrl;
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
        $selectedCountry = old('country', 'Bolivia');
        $selectedCountryExists = collect($countries)->contains('name', $selectedCountry);
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
                                    <label class="form-label" for="booking-room-type">{{ __('public.booking.prefer_room') }} <span class="text-muted">(opcional)</span></label>
                                    <select id="booking-room-type" name="room_type_id" class="form-select @error('room_type_id') is-invalid @enderror">
                                        <option value="">Ver todas las habitaciones disponibles</option>
                                        @foreach ($roomTypes as $roomType)
                                            <option
                                                value="{{ $roomType->id }}"
                                                data-name="{{ $roomType->name }}"
                                                data-base-price="{{ number_format((float) $roomType->base_price, 2, '.', '') }}"
                                                @selected((int) old('room_type_id', $selectedRoomTypeId) === $roomType->id)
                                            >
                                                {{ $roomType->name }} - Bs. {{ number_format((float) ($roomType->price_bob ?? $roomType->base_price), 2, '.', '') }} / $us {{ number_format((float) ($roomType->price_usd ?? 0), 2, '.', '') }}
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('room_type_id')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                    <div class="booking-field-help">Si no sabes que elegir, deja esta opcion en blanco. El sistema consultara la base de datos y te mostrara solo habitaciones realmente libres.</div>
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
                                    <input type="text" id="nationality" name="nationality" value="{{ old('nationality') }}" class="form-control @error('nationality') is-invalid @enderror">
                                    @error('nationality')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
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
                                    <input type="text" id="whatsapp" name="whatsapp" value="{{ old('whatsapp') }}" class="form-control @error('whatsapp') is-invalid @enderror">
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
                                <div>
                                    <label class="form-label" for="country">{{ __('public.booking.country_label') }}</label>
                                    <select id="country" name="country" class="form-select public-country-select @error('country') is-invalid @enderror" data-country-select data-country-api="https://cdn.simplelocalize.io/public/v1/countries" data-selected-country="{{ $selectedCountry }}" data-placeholder="Selecciona tu pais">
                                        @unless ($selectedCountryExists)
                                            <option value="{{ $selectedCountry }}" selected>{{ $selectedCountry }}</option>
                                        @endunless
                                        @foreach ($countries as $country)
                                            <option value="{{ $country['name'] }}" data-country-code="{{ $country['code'] }}" data-country-flag="https://flagcdn.com/w40/{{ strtolower($country['code']) }}.png" @selected($selectedCountry === $country['name'])>
                                                {{ $country['name'] }}
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('country')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                    <div class="booking-field-help">Busca tu pais por nombre y verifica la bandera antes de continuar.</div>
                                </div>
                                <div>
                                    <label class="form-label" for="city">{{ __('public.booking.city_label') }}</label>
                                    <input type="text" id="city" name="city" value="{{ old('city', $hotelSetting->city) }}" class="form-control @error('city') is-invalid @enderror">
                                    @error('city')
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
                                    <small>Puedes elegir depositar por QR, por transferencia bancaria o por deposito bancario.</small>
                                </div>

                                <div class="booking-payment-options booking-payment-options-wide">
                                    @foreach ($paymentMethods as $paymentMethod)
                                        <label class="booking-payment-card @unless($paymentMethod['available']) is-disabled @endunless" for="preferred-payment-{{ $paymentMethod['value'] }}">
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

                                <div class="booking-payment-path mt-3">
                                    <div class="booking-payment-path-step">
                                        <span>1</span>
                                        <strong>Elige como depositaras</strong>
                                        <small>Selecciona QR, transferencia o deposito bancario.</small>
                                    </div>
                                    <div class="booking-payment-path-step">
                                        <span>2</span>
                                        <strong>Elige la moneda</strong>
                                        <small>Indica si pagaras en bolivianos o dolares antes de ver los datos.</small>
                                    </div>
                                    <div class="booking-payment-path-step">
                                        <span>3</span>
                                        <strong>Deposita y sube tu voucher</strong>
                                        <small>Veras el monto minimo, el total y donde depositar.</small>
                                    </div>
                                </div>

                                <div class="booking-currency-choice mt-3">
                                    <div>
                                        <span class="section-kicker">Moneda del deposito</span>
                                        <h3>En que moneda haras el pago?</h3>
                                        <p>Elige la misma moneda que aparecera en tu comprobante. El sistema usara los precios registrados para esta habitacion.</p>
                                    </div>
                                    <div class="booking-currency-select-wrap">
                                        <span>Moneda</span>
                                        <select id="booking-payment-currency" name="payment_currency" class="form-select @error('payment_currency') is-invalid @enderror" required aria-label="Selecciona la moneda del deposito">
                                            <option value="BOB" @selected(old('payment_currency', $hotelSetting->baseCurrency()) === 'BOB')>Bs. Bolivianos</option>
                                            <option value="USD" @selected(old('payment_currency', $hotelSetting->baseCurrency()) === 'USD')>$us Dolares</option>
                                        </select>
                                        <i class="bi bi-chevron-down" aria-hidden="true"></i>
                                    </div>
                                </div>
                                @error('payment_currency')
                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                @enderror

                                <div class="booking-payment-guide-stack mt-3">
                                    <div class="booking-payment-guide @if ($selectedPaymentMethod !== 'qr') d-none @endif" data-booking-payment-guide="qr">
                                        <div>
                                            <span class="section-kicker">QR del hotel</span>
                                            <h3>Escanea este QR y sube tu comprobante</h3>
                                            <p>Deposita en <strong data-selected-currency-label>Bs. Bolivianos</strong>. Abre el QR que prefieras y guarda la captura del pago.</p>
                                        </div>
                                        @if ($paymentQrUrl)
                                            <div class="booking-qr-grid booking-qr-grid-single">
                                                @if ($bobQrUrl)
                                                    <div class="booking-qr-panel @if ($selectedPaymentCurrency !== 'BOB') d-none @endif" data-qr-currency-panel="BOB">
                                                        <strong>QR para Bs. Bolivianos</strong>
                                                        <small>Usa este QR solo si tu comprobante sera en bolivianos.</small>
                                                        <button type="button" class="booking-qr-preview" data-open-qr-modal data-qr-src="{{ $bobQrUrl }}" data-qr-title="QR para Bs. Bolivianos" aria-label="Ampliar QR para bolivianos">
                                                            <img src="{{ $bobQrUrl }}" alt="QR del hotel para pagos en bolivianos" class="booking-payment-qr">
                                                            <span><i class="bi bi-arrows-fullscreen" aria-hidden="true"></i> Ampliar QR</span>
                                                        </button>
                                                        <div class="booking-qr-actions">
                                                            <a href="{{ $bobQrUrl }}" class="btn btn-public-primary btn-sm" download>
                                                                <i class="bi bi-download" aria-hidden="true"></i> Descargar
                                                            </a>
                                                            <button type="button" class="btn btn-public-outline btn-sm" data-open-qr-modal data-qr-src="{{ $bobQrUrl }}" data-qr-title="QR para Bs. Bolivianos">
                                                                <i class="bi bi-search" aria-hidden="true"></i> Ver grande
                                                            </button>
                                                        </div>
                                                    </div>
                                                @endif
                                                @if ($usdQrUrl)
                                                    <div class="booking-qr-panel @if ($selectedPaymentCurrency !== 'USD') d-none @endif" data-qr-currency-panel="USD">
                                                        <strong>QR para $us Dolares</strong>
                                                        <small>Usa este QR solo si tu comprobante sera en dolares.</small>
                                                        <button type="button" class="booking-qr-preview" data-open-qr-modal data-qr-src="{{ $usdQrUrl }}" data-qr-title="QR para $us Dolares" aria-label="Ampliar QR para dolares">
                                                            <img src="{{ $usdQrUrl }}" alt="QR del hotel para pagos en dolares" class="booking-payment-qr">
                                                            <span><i class="bi bi-arrows-fullscreen" aria-hidden="true"></i> Ampliar QR</span>
                                                        </button>
                                                        <div class="booking-qr-actions">
                                                            <a href="{{ $usdQrUrl }}" class="btn btn-public-primary btn-sm" download>
                                                                <i class="bi bi-download" aria-hidden="true"></i> Descargar
                                                            </a>
                                                            <button type="button" class="btn btn-public-outline btn-sm" data-open-qr-modal data-qr-src="{{ $usdQrUrl }}" data-qr-title="QR para $us Dolares">
                                                                <i class="bi bi-search" aria-hidden="true"></i> Ver grande
                                                            </button>
                                                        </div>
                                                    </div>
                                                @endif
                                            </div>
                                        @else
                                            <div class="booking-payment-missing">El QR aun no esta configurado.</div>
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
                                    <div class="booking-receipt-head">
                                        <div>
                                            <span class="section-kicker">Comprobante</span>
                                            <h3>Sube tu voucher y registra el monto</h3>
                                            <p id="booking-receipt-summary">Primero elige una habitacion para ver cuanto debes depositar.</p>
                                        </div>
                                        <button type="button" class="booking-breakdown-toggle" data-scroll-breakdown>
                                            Ver desglose
                                            <i class="bi bi-arrow-down-circle" aria-hidden="true"></i>
                                        </button>
                                    </div>

                                    <label class="form-label" for="booking-payment-amount">Cuanto estas depositando</label>
                                    <div class="booking-amount-field">
                                        <div class="booking-payment-amount-wrap">
                                            <span id="booking-payment-amount-label">Monto en Bs. Bolivianos</span>
                                            <input type="number" id="booking-payment-amount" name="payment_amount" class="form-control @error('payment_amount') is-invalid @enderror" value="{{ old('payment_amount') }}" min="0.01" step="0.01" placeholder="Ejemplo: 120.00" required>
                                        </div>
                                    </div>
                                    <div class="booking-field-help" id="booking-payment-amount-help">Primero elige una habitacion para ver el anticipo minimo requerido.</div>
                                    <div class="booking-deposit-breakdown" id="booking-deposit-breakdown" hidden>
                                        <div class="booking-deposit-breakdown__head">
                                            <span class="section-kicker">Desglose del deposito</span>
                                            <strong id="booking-exchange-rate-label">Se usan los precios BOB/USD registrados para la habitacion</strong>
                                        </div>
                                        <div class="booking-deposit-breakdown__grid">
                                            <div>
                                                <span>Debes pagar minimo</span>
                                                <strong id="booking-breakdown-min-bob">-</strong>
                                                <small id="booking-breakdown-min-usd">-</small>
                                            </div>
                                            <div>
                                                <span>Total de la estadia</span>
                                                <strong id="booking-breakdown-total-bob">-</strong>
                                                <small id="booking-breakdown-total-usd">-</small>
                                            </div>
                                            <div>
                                                <span>Tu deposito</span>
                                                <strong id="booking-breakdown-payment-base">-</strong>
                                                <small id="booking-breakdown-payment-note">Selecciona moneda y monto.</small>
                                            </div>
                                        </div>
                                    </div>
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
                                    <div><strong>{{ __('public.booking.summary_price_per_night') }}</strong><span id="summary-price-per-night">-</span></div>
                                    <div><strong>{{ __('public.booking.summary_discount') }}</strong><span id="summary-discount">-</span></div>
                                    <div><strong>{{ __('public.booking.summary_required_deposit') }}</strong><span id="summary-deposit">-</span></div>
                                    <div><strong>Monto que estas depositando</strong><span id="summary-payment-amount">-</span></div>
                                    <div><strong>{{ __('public.booking_success.payment_method') }}</strong><span id="summary-payment-method">{{ $selectedPaymentMethodLabel }}</span></div>
                                    <div><strong>{{ __('public.booking_success.total_estimated') }}</strong><span id="summary-total">-</span></div>
                                </div>
                                <div class="booking-inline-alert" id="summary-deposit-help">
                                    {{ __('public.booking.deposit_help_empty') }}
                                </div>
                            </div>

                            <div class="form-check mt-4">
                                <input type="checkbox" id="accept_terms" name="accept_terms" value="1" class="form-check-input @error('accept_terms') is-invalid @enderror" @checked(old('accept_terms')) required>
                                <label class="form-check-label" for="accept_terms">
                                    {{ __('public.booking.accept_terms_label') }}
                                </label>
                                @error('accept_terms')
                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="booking-step-actions booking-step-actions-final">
                                <div class="booking-step-tip">{{ __('public.booking.submit_help') }}</div>
                                <div class="booking-step-action-buttons">
                                    <button type="button" class="btn btn-public-ghost" data-step-prev>{{ __('public.booking.back') }}</button>
                                    <button type="submit" class="btn btn-public-primary" id="submit-booking-button" disabled>{{ __('public.booking.book_button') }}</button>
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
                                <div><span>{{ __('public.booking.side_summary_deposit') }}</span><strong id="side-summary-deposit">-</strong></div>
                                <div><span>{{ __('public.booking.side_summary_total') }}</span><strong id="side-summary-total">-</strong></div>
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
            const submitButton = document.getElementById('submit-booking-button');
            const submitLoader = document.getElementById('booking-submit-loader');
            const qrModal = document.getElementById('booking-qr-modal');
            const qrModalTitle = document.getElementById('booking-qr-modal-title');
            const qrModalImage = document.getElementById('booking-qr-modal-image');
            const qrModalDownload = document.getElementById('booking-qr-modal-download');
            const openQrModalButtons = document.querySelectorAll('[data-open-qr-modal]');
            const closeQrModalButtons = document.querySelectorAll('[data-close-qr-modal]');
            const scrollBreakdownButton = document.querySelector('[data-scroll-breakdown]');
            const paymentMethodFields = document.querySelectorAll('input[name="preferred_payment_method"]');
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
            const depositHelp = document.getElementById('summary-deposit-help');
            const paymentAmountHelp = document.getElementById('booking-payment-amount-help');
            const depositBreakdown = document.getElementById('booking-deposit-breakdown');
            const exchangeRateLabel = document.getElementById('booking-exchange-rate-label');
            const breakdown = {
                minBob: document.getElementById('booking-breakdown-min-bob'),
                minUsd: document.getElementById('booking-breakdown-min-usd'),
                totalBob: document.getElementById('booking-breakdown-total-bob'),
                totalUsd: document.getElementById('booking-breakdown-total-usd'),
                paymentBase: document.getElementById('booking-breakdown-payment-base'),
                paymentNote: document.getElementById('booking-breakdown-payment-note'),
            };
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
                paymentCurrency: document.getElementById('booking-payment-currency'),
                paymentAmount: document.getElementById('booking-payment-amount'),
                receiptImage: document.getElementById('booking-receipt'),
                acceptTerms: document.getElementById('accept_terms'),
            };

            let availabilityLoaded = false;
            let selectedAvailableTypeId = roomTypeSelect.value || null;
            let availabilityTimeout = null;
            let currentStep = 0;
            let currentQuote = null;

            const setAvailabilityMessage = (message) => {
                availabilityStatus.textContent = message;
            };

            const setSummaryDefaults = () => {
                summary.dates.textContent = @json(__('public.booking.summary_dates_empty'));
                summary.nights.textContent = '-';
                summary.pricePerNight.textContent = '-';
                summary.discount.textContent = '-';
                summary.deposit.textContent = '-';
                summary.paymentAmount.textContent = '-';
                summary.total.textContent = '-';
                summary.sideDates.textContent = @json(__('public.booking.summary_dates_empty'));
                summary.sideDeposit.textContent = '-';
                summary.sideTotal.textContent = '-';
                depositHelp.textContent = @json(__('public.booking.deposit_help_empty'));
                paymentAmountHelp.textContent = 'Primero elige una habitacion para ver el anticipo minimo requerido.';
                if (receiptSummary) {
                    receiptSummary.textContent = 'Primero elige una habitacion para ver cuanto debes depositar.';
                }
                depositBreakdown.hidden = true;
                fields.paymentCurrency?.querySelector('option[value="USD"]')?.removeAttribute('disabled');
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

            const selectedPaymentCurrency = () => fields.paymentCurrency?.value || @json($hotelSetting->baseCurrency());

            const oppositeCurrency = (currency) => currency === 'USD' ? 'BOB' : 'USD';

            const currencyName = (currency) => currency === 'USD' ? '$us Dolares' : 'Bs. Bolivianos';

            const syncCurrencyCopy = () => {
                const currency = selectedPaymentCurrency();

                selectedCurrencyLabels.forEach((element) => {
                    element.textContent = currencyName(currency);
                });

                qrCurrencyPanels.forEach((panel) => {
                    panel.classList.toggle('d-none', panel.dataset.qrCurrencyPanel !== currency);
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
                summary.paymentAmount.textContent = amount > 0 ? formatBookingMoney(amount, selectedPaymentCurrency()) : '-';
                syncDepositBreakdown();
            };

            const syncPaymentAmountLimits = (shouldAutofill = false) => {
                if (!currentQuote || !fields.paymentAmount) {
                    return;
                }

                const usdOption = fields.paymentCurrency?.querySelector('option[value="USD"]');

                if (usdOption) {
                    usdOption.disabled = currentQuote.supports_usd === false;
                }

                if (fields.paymentCurrency?.value === 'USD' && currentQuote.supports_usd === false) {
                    fields.paymentCurrency.value = 'BOB';
                }

                const currency = selectedPaymentCurrency();
                const minAmount = quoteAmount('deposit_amount_required', currency);
                const maxAmount = quoteAmount('total_amount', currency);
                const alternateCurrency = oppositeCurrency(currency);

                syncCurrencyCopy();

                fields.paymentAmount.min = minAmount > 0 ? minAmount.toFixed(2) : '0.01';
                fields.paymentAmount.max = maxAmount > 0 ? maxAmount.toFixed(2) : '';
                fields.paymentAmount.placeholder = minAmount > 0 ? formatBookingMoney(minAmount, currency) : 'Ejemplo: 20.00';

                if (shouldAutofill && minAmount > 0 && (!fields.paymentAmount.value || Number(fields.paymentAmount.value) < minAmount || (maxAmount > 0 && Number(fields.paymentAmount.value) > maxAmount))) {
                    fields.paymentAmount.value = minAmount.toFixed(2);
                }

                paymentAmountHelp.textContent = currentQuote.supports_usd === false
                    ? `Debes depositar minimo ${quoteAmountFormatted('deposit_amount_required', currency)}. Esta habitacion aun no tiene precio en dolares registrado.`
                    : `Debes depositar minimo ${quoteAmountFormatted('deposit_amount_required', currency)}. Total estimado: ${quoteAmountFormatted('total_amount', currency)}. Referencia en ${alternateCurrency}: ${quoteAmountFormatted('deposit_amount_required', alternateCurrency)}.`;

                if (receiptSummary) {
                    receiptSummary.textContent = `Para reservar debes depositar minimo ${quoteAmountFormatted('deposit_amount_required', currency)}. El total estimado de tu estadia es ${quoteAmountFormatted('total_amount', currency)}.`;
                }

                syncDepositBreakdown();
            };

            const syncDepositBreakdown = () => {
                if (!currentQuote || !depositBreakdown) {
                    return;
                }

                depositBreakdown.hidden = false;
                const currency = selectedPaymentCurrency();
                const alternateCurrency = oppositeCurrency(currency);
                const minAmount = quoteAmount('deposit_amount_required', currency);
                const totalAmount = quoteAmount('total_amount', currency);

                exchangeRateLabel.textContent = 'Sin conversion automatica: se usa el precio registrado en la moneda elegida.';
                breakdown.minBob.textContent = quoteAmountFormatted('deposit_amount_required', currency);
                breakdown.minUsd.textContent = `Referencia ${alternateCurrency}: ${quoteAmountFormatted('deposit_amount_required', alternateCurrency)}`;
                breakdown.totalBob.textContent = quoteAmountFormatted('total_amount', currency);
                breakdown.totalUsd.textContent = `Referencia ${alternateCurrency}: ${quoteAmountFormatted('total_amount', alternateCurrency)}`;

                const amount = Number(fields.paymentAmount?.value || 0);

                if (!amount || amount <= 0) {
                    breakdown.paymentBase.textContent = '-';
                    breakdown.paymentNote.textContent = 'Selecciona moneda y escribe el monto que figura en tu comprobante.';
                    return;
                }

                breakdown.paymentBase.textContent = formatBookingMoney(amount, currency);
                breakdown.paymentNote.textContent = minAmount > 0 && amount < minAmount
                    ? `Todavia falta ${formatBookingMoney(minAmount - amount, currency)} para cubrir el anticipo minimo.`
                    : `Cubre el anticipo minimo. Puedes depositar hasta ${formatBookingMoney(totalAmount, currency)} si deseas cancelar todo.`;
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

                    if (!selectedAvailableTypeId || !roomTypeSelect.value || String(selectedAvailableTypeId) !== String(roomTypeSelect.value)) {
                        setAvailabilityMessage('Selecciona una habitacion disponible para continuar.');
                        return false;
                    }

                    return true;
                }

                if (currentStep === 2) {
                    return ensureFieldValidity(fields.fullName);
                }

                if (currentStep === 3) {
                    const checkedMethod = document.querySelector('input[name="preferred_payment_method"]:checked');

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
                submitButton.disabled = true;

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

            const renderAvailabilityResults = (roomTypes) => {
                if (!roomTypes.length) {
                    resultsContainer.innerHTML = `
                        <div class="empty-state-public booking-empty-state-rich">
                            <strong>No encontramos habitaciones libres para esos datos.</strong>
                            <span>Cambia las fechas, reduce huespedes o deja el tipo de habitacion en "ver todas".</span>
                        </div>
                    `;
                    return;
                }

                resultsContainer.innerHTML = roomTypes.map((roomType) => `
                    <article class="booking-result-card ${String(selectedAvailableTypeId) === String(roomType.id) ? 'is-selected' : ''}" data-room-type-card="${roomType.id}">
                        <div class="d-flex justify-content-between gap-3 flex-wrap align-items-start">
                            <div>
                                <span class="booking-result-kicker">${@json(__('public.booking.available_room_kicker'))}</span>
                                <h3 class="mb-1">${escapeHtml(roomType.name)}</h3>
                                <div class="text-muted small">${escapeHtml(roomType.capacity_summary)}</div>
                            </div>
                            <div class="price-chip">${roomType.promotion?.final_price_formatted ?? roomType.base_price_formatted}</div>
                        </div>
                        <div class="booking-result-meta mt-3">
                            <span><i class="bi bi-door-open"></i> ${templates.availableCount.replace('__COUNT__', roomType.available_rooms_count)}</span>
                            <span><i class="bi bi-people"></i> ${templates.maxGuests.replace('__COUNT__', roomType.max_guests)}</span>
                            <span><i class="bi bi-cash-stack"></i> ${templates.baseLabel.replace('__PRICE__', roomType.base_price_formatted)}</span>
                            <span><i class="bi bi-currency-dollar"></i> $us ${Number(roomType.price_usd ?? 0).toFixed(2)}</span>
                        </div>
                        <div class="booking-inline-alert mt-3">
                            ${templates.depositHelp
                                .replace('__PERCENTAGE__', roomType.deposit_percentage)
                                .replace('__AMOUNT__', roomType.deposit_amount_required_formatted)}
                        </div>
                        ${roomType.promotion ? `
                            <div class="promo-badge mt-3">
                                ${escapeHtml(roomType.promotion.name)} - ${escapeHtml(roomType.promotion.discount_label)} - ${roomType.promotion.final_price_formatted}
                            </div>
                        ` : ''}
                        <div class="mt-3">
                            <button type="button" class="btn btn-public-outline booking-select-button" data-select-room-type="${roomType.id}" data-room-type-name="${escapeHtml(roomType.name)}">
                                ${String(selectedAvailableTypeId) === String(roomType.id) ? 'Habitacion seleccionada' : @json(__('public.booking.select_room'))}
                            </button>
                        </div>
                    </article>
                `).join('');
            };

            const buildAvailabilityParams = () => {
                const params = new URLSearchParams();
                params.set('check_in', fields.checkIn.value);
                params.set('check_out', fields.checkOut.value);
                params.set('adults', fields.adults.value);
                params.set('children', fields.children.value || '0');

                if (roomTypeSelect.value) {
                    params.set('room_type_id', roomTypeSelect.value);
                }

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
                    const availableRoomTypes = payload.room_types ?? [];
                    const selectedStillAvailable = roomTypeSelect.value
                        ? availableRoomTypes.some((roomType) => String(roomType.id) === String(roomTypeSelect.value))
                        : false;

                    selectedAvailableTypeId = selectedStillAvailable ? roomTypeSelect.value : null;
                    renderAvailabilityResults(availableRoomTypes);
                    setAvailabilityMessage(payload.available ? @json(__('public.booking.ready')) : @json(__('public.booking.availability_none')));

                    if (payload.available && selectedStillAvailable) {
                        await fetchQuote();
                    } else {
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

                const selectedRoomName = roomTypeSelect.selectedOptions[0]?.dataset?.name ?? '-';
                summary.roomType.textContent = selectedRoomName;
                summary.sideRoom.textContent = selectedRoomName;
                summary.pricePerNight.textContent = @json(__('public.booking.searching'));
                summary.discount.textContent = @json(__('public.booking.searching'));
                summary.total.textContent = @json(__('public.booking.searching'));

                const formData = new FormData();
                formData.append('room_type_id', roomTypeSelect.value);
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
                    summary.pricePerNight.textContent = payload.price_per_night_formatted;
                    summary.discount.textContent = payload.discount_amount > 0
                        ? `${payload.discount_amount_formatted}${payload.discount_label ? ` (${payload.discount_label})` : ''}`
                        : '-';
                    summary.deposit.textContent = payload.deposit_amount_required_formatted;
                    summary.total.textContent = payload.total_amount_formatted;
                    summary.sideDates.textContent = formattedDates;
                    summary.sideDeposit.textContent = payload.deposit_amount_required_formatted;
                    summary.sideTotal.textContent = payload.total_amount_formatted;
                    currentQuote = payload;
                    syncPaymentAmountLimits(true);
                    syncPaymentAmountSummary();
                    depositHelp.textContent = templates.depositHelp
                        .replace('__PERCENTAGE__', payload.deposit_percentage)
                        .replace('__AMOUNT__', payload.deposit_amount_required_formatted);
                    submitButton.disabled = !availabilityLoaded;
                    setAvailabilityMessage(@json(__('public.booking.ready')));
                } catch (error) {
                    summary.pricePerNight.textContent = '-';
                    summary.discount.textContent = error.message;
                    summary.deposit.textContent = '-';
                    summary.paymentAmount.textContent = '-';
                    summary.total.textContent = '-';
                    summary.sideDeposit.textContent = '-';
                    summary.sideTotal.textContent = '-';
                    depositHelp.textContent = @json(__('public.booking.deposit_help_empty'));
                    paymentAmountHelp.textContent = 'Primero elige una habitacion para ver el anticipo minimo requerido.';
                    currentQuote = null;
                    submitButton.disabled = true;
                    setAvailabilityMessage(error.message);
                }
            };

            availabilityButton?.addEventListener('click', fetchAvailability);

            document.getElementById('availability-results').addEventListener('click', async (event) => {
                const button = event.target.closest('[data-select-room-type]');

                if (!button) {
                    return;
                }

                roomTypeSelect.value = button.dataset.selectRoomType;
                selectedAvailableTypeId = button.dataset.selectRoomType;
                document.querySelectorAll('[data-room-type-card]').forEach((card) => {
                    card.classList.toggle('is-selected', card.dataset.roomTypeCard === selectedAvailableTypeId);
                });

                await fetchQuote();
                setStep(2);
            });

            [fields.checkIn, fields.checkOut, fields.adults, fields.children, roomTypeSelect].forEach((field) => {
                field.addEventListener('change', () => {
                    if (field === roomTypeSelect) {
                        selectedAvailableTypeId = null;
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

            fields.paymentAmount?.addEventListener('input', () => {
                fields.paymentAmount.setCustomValidity('');
                syncPaymentAmountSummary();
            });

            fields.paymentCurrency?.addEventListener('change', () => {
                fields.paymentAmount?.setCustomValidity('');
                syncCurrencyCopy();

                if (currentQuote) {
                    syncPaymentAmountLimits(true);
                }

                syncPaymentAmountSummary();
            });

            scrollBreakdownButton?.addEventListener('click', () => {
                if (currentQuote) {
                    syncPaymentAmountLimits(false);
                }

                depositBreakdown.hidden = false;
                depositBreakdown.scrollIntoView({ behavior: 'smooth', block: 'center' });
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
            syncPaymentMethodSummary();
            syncPaymentGuides();
        });
    </script>
@endpush
