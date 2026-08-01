@extends('public.layouts.app')

@section('content')
    @php
        $statusMap = [
            'pending' => ['label' => __('public.portal_detail.statuses.pending'), 'class' => 'text-bg-warning'],
            'confirmed' => ['label' => __('public.portal_detail.statuses.confirmed'), 'class' => 'text-bg-primary'],
            'checked_in' => ['label' => __('public.portal_detail.statuses.checked_in'), 'class' => 'text-bg-success'],
            'checked_out' => ['label' => __('public.portal_detail.statuses.checked_out'), 'class' => 'text-bg-secondary'],
            'cancelled' => ['label' => __('public.portal_detail.statuses.cancelled'), 'class' => 'text-bg-danger'],
            'expired' => ['label' => __('public.portal_detail.statuses.expired'), 'class' => 'text-bg-secondary'],
            'no_show' => ['label' => __('public.portal_detail.statuses.no_show'), 'class' => 'text-bg-dark'],
        ];
        $paymentStatusMap = [
            'pending' => ['label' => __('public.portal_detail.payment_statuses.pending'), 'class' => 'text-bg-warning'],
            'confirmed' => ['label' => __('public.portal_detail.payment_statuses.confirmed'), 'class' => 'text-bg-success'],
            'rejected' => ['label' => __('public.portal_detail.payment_statuses.rejected'), 'class' => 'text-bg-danger'],
            'cancelled' => ['label' => __('public.portal_detail.payment_statuses.cancelled'), 'class' => 'text-bg-secondary'],
            'refunded' => ['label' => __('public.portal_detail.payment_statuses.refunded'), 'class' => 'text-bg-info'],
        ];
        $paymentMethodMap = [
            'qr' => __('public.portal_detail.payment_methods.qr'),
            'bank' => __('public.portal_detail.payment_methods.bank'),
            'other' => __('public.portal_detail.payment_methods.other'),
        ];
        $currentStatus = $statusMap[$reservation->status] ?? ['label' => ucfirst($reservation->status), 'class' => 'text-bg-secondary'];
        $roomTypeName = $reservation->roomType?->name ?: $reservation->room?->roomType?->name ?: '-';
        $baseCurrency = $hotelSetting->baseCurrency();
        $whatsapp = preg_replace('/\D+/', '', (string) ($hotelSetting->whatsapp ?? ''));
        if ($whatsapp !== '' && ! str_starts_with($whatsapp, '591')) {
            $whatsapp = '591'.$whatsapp;
        }
        $whatsAppUrl = $whatsapp !== ''
            ? 'https://wa.me/'.$whatsapp.'?text='.rawurlencode('Hola, quiero consultar mi reserva en '.($hotelSetting->hotel_name ?: 'Hostal Cerro Rico').'. Mi codigo es '.$reservation->code.'.')
            : null;
        $canCancel = $reservation->status === 'pending';
    @endphp

    <section class="page-hero-simple">
        <div class="container">
            <span class="section-kicker">{{ __('public.portal_detail.hero_kicker') }}</span>
            <h1>{{ $reservation->code }}</h1>
            <p>{{ __('public.portal_detail.hero_description') }}</p>
        </div>
    </section>

    <section class="section-block">
        <div class="container">
            @if (session('success'))
                <div class="alert alert-success booking-alert mb-4">{{ session('success') }}</div>
            @endif

            @if ($errors->any())
                <div class="alert alert-danger booking-alert mb-4">
                    <ul class="mb-0">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <div class="content-panel mb-4">
                <div class="d-flex flex-wrap justify-content-between gap-3 align-items-start">
                    <div>
                        <span class="section-kicker">{{ __('public.portal_detail.reservation_status') }}</span>
                        <h2 class="mb-2">{{ $reservation->code }}</h2>
                        <span class="badge {{ $currentStatus['class'] }} fs-6">{{ $currentStatus['label'] }}</span>
                    </div>
                    <div class="price-chip">{{ __('public.portal_detail.balance') }} {{ $hotelSetting->formatMoney((float) $reservation->balance_amount, $baseCurrency) }}</div>
                </div>
            </div>

            <div class="row g-4">
                <div class="col-xl-8">
                    <div class="content-panel mb-4">
                        <span class="section-kicker">{{ __('public.portal_detail.guest_kicker') }}</span>
                        <h2 class="mb-3">{{ __('public.portal_detail.guest_title') }}</h2>
                        <div class="booking-summary-grid">
                            <div><strong>{{ __('public.portal_detail.full_name') }}</strong><span>{{ $reservation->customer?->full_name ?: '-' }}</span></div>
                            <div><strong>{{ __('public.portal_detail.document') }}</strong><span>{{ trim(collect([$reservation->customer?->document_type, $reservation->customer?->document_number])->filter()->implode(' - ')) ?: '-' }}</span></div>
                            <div><strong>{{ __('public.portal_detail.email') }}</strong><span>{{ $reservation->customer?->email ?: '-' }}</span></div>
                            <div><strong>{{ __('public.portal_detail.phone_whatsapp') }}</strong><span>{{ $reservation->customer?->whatsapp ?: ($reservation->customer?->phone ?: '-') }}</span></div>
                        </div>
                    </div>

                    <div class="content-panel mb-4">
                        <span class="section-kicker">{{ __('public.portal_detail.booking_kicker') }}</span>
                        <h2 class="mb-3">{{ __('public.portal_detail.booking_title') }}</h2>
                        <div class="booking-summary-grid">
                            <div><strong>{{ __('public.portal_detail.room_type') }}</strong><span>{{ $roomTypeName }}</span></div>
                            <div><strong>{{ __('public.portal_detail.room_number') }}</strong><span>{{ $reservation->room?->number ?: __('public.portal_detail.room_pending') }}</span></div>
                            <div><strong>{{ __('public.portal_detail.check_in') }}</strong><span>{{ optional($reservation->check_in)->format('d/m/Y') }}</span></div>
                            <div><strong>{{ __('public.portal_detail.check_out') }}</strong><span>{{ optional($reservation->check_out)->format('d/m/Y') }}</span></div>
                            <div><strong>{{ __('public.portal_detail.nights') }}</strong><span>{{ $reservation->nights }}</span></div>
                            <div><strong>{{ __('public.portal_detail.guests') }}</strong><span>{{ __('public.portal_detail.guests_summary', ['adults' => $reservation->adults, 'children' => $reservation->children]) }}</span></div>
                            <div><strong>{{ __('public.portal_detail.promotion') }}</strong><span>{{ $reservation->promotion?->name ?: __('public.portal_detail.no_promotion') }}</span></div>
                            <div><strong>{{ __('public.portal_detail.special_requests') }}</strong><span>{{ $reservation->special_requests ?: __('public.portal_detail.no_comments') }}</span></div>
                        </div>
                    </div>

                    <div class="content-panel mb-4">
                        <span class="section-kicker">{{ __('public.portal_detail.summary_kicker') }}</span>
                        <h2 class="mb-3">{{ __('public.portal_detail.summary_title') }}</h2>
                        <div class="booking-summary-grid">
                            <div><strong>{{ __('public.portal_detail.base_price') }}</strong><span>{{ $hotelSetting->formatMoney((float) $reservation->base_price, $baseCurrency) }}</span></div>
                            <div><strong>{{ __('public.portal_detail.discount') }}</strong><span>{{ $hotelSetting->formatMoney((float) $reservation->discount_amount, $baseCurrency) }}</span></div>
                            <div><strong>{{ __('public.portal_detail.price_per_night') }}</strong><span>{{ $hotelSetting->formatMoney((float) $reservation->price_per_night, $baseCurrency) }}</span></div>
                            <div><strong>{{ __('public.portal_detail.total') }}</strong><span>{{ $hotelSetting->formatMoney((float) $reservation->total_amount, $baseCurrency) }}</span></div>
                            <div><strong>{{ __('public.portal_detail.required_deposit') }}</strong><span>{{ $reservation->deposit_percentage }}% - {{ $hotelSetting->formatMoney((float) $reservation->deposit_amount_required, $baseCurrency) }}</span></div>
                            <div><strong>{{ __('public.portal_detail.deposit_pending') }}</strong><span>{{ $hotelSetting->formatMoney($reservation->depositAmountPending(), $baseCurrency) }}</span></div>
                            <div><strong>{{ __('public.portal_detail.paid') }}</strong><span>{{ $hotelSetting->formatMoney((float) $reservation->paid_amount, $baseCurrency) }}</span></div>
                            <div><strong>{{ __('public.portal_detail.balance') }}</strong><span>{{ $hotelSetting->formatMoney((float) $reservation->balance_amount, $baseCurrency) }}</span></div>
                        </div>

                        <div class="booking-inline-alert mt-3">
                            {{ __('public.portal_detail.deposit_notice', [
                                'percentage' => $reservation->deposit_percentage,
                                'amount' => $hotelSetting->formatMoney((float) $reservation->deposit_amount_required, $baseCurrency),
                            ]) }}
                        </div>
                    </div>

                    <div class="content-panel mb-4">
                        <span class="section-kicker">{{ __('public.portal_detail.payments_kicker') }}</span>
                        <h2 class="mb-3">{{ __('public.portal_detail.payments_title') }}</h2>

                        @if ($reservation->payments->isNotEmpty())
                            <div class="portal-payment-list">
                                @foreach ($reservation->payments->sortByDesc('created_at') as $payment)
                                    @php
                                        $paymentStatus = $paymentStatusMap[$payment->status] ?? ['label' => ucfirst($payment->status), 'class' => 'text-bg-secondary'];
                                        $receiptExtension = strtolower(pathinfo((string) $payment->receipt_image, PATHINFO_EXTENSION));
                                    @endphp
                                    <article class="booking-result-card">
                                        <div class="d-flex flex-wrap justify-content-between gap-3 align-items-start">
                                            <div>
                                                <h3 class="mb-1">{{ $payment->code }}</h3>
                                                <div class="small text-muted">{{ $paymentMethodMap[$payment->payment_method] ?? ucfirst($payment->payment_method) }}</div>
                                            </div>
                                            <div class="d-flex align-items-center gap-2 flex-wrap">
                                                <span class="badge {{ $paymentStatus['class'] }}">{{ $paymentStatus['label'] }}</span>
                                                <span class="price-chip">{{ $hotelSetting->formatMoney((float) $payment->amount, $payment->currency ?? $baseCurrency) }}</span>
                                            </div>
                                        </div>
                                        <div class="booking-summary-grid mt-3">
                                            <div><strong>{{ __('public.portal_detail.date') }}</strong><span>{{ optional($payment->payment_date)->format('d/m/Y') ?: '-' }}</span></div>
                                            <div><strong>{{ __('public.portal_detail.reference') }}</strong><span>{{ $payment->reference_number ?: '-' }}</span></div>
                                            <div><strong>{{ __('public.portal_detail.apply_to_balance') }}</strong><span>{{ (float) ($payment->amount_base ?? 0) > 0 ? $hotelSetting->formatMoney((float) $payment->amount_base, $baseCurrency) : __('public.portal_detail.manual_review') }}</span></div>
                                            <div><strong>{{ __('public.portal_detail.notes') }}</strong><span>{{ $payment->notes ?: '-' }}</span></div>
                                            <div><strong>{{ __('public.portal_detail.receipt') }}</strong>
                                                <span>
                                                    @if ($payment->receipt_image)
                                                        <a href="{{ asset('storage/'.$payment->receipt_image) }}" target="_blank" rel="noopener">
                                                            {{ $receiptExtension === 'pdf' ? __('public.portal_detail.view_pdf') : __('public.portal_detail.view_receipt') }}
                                                        </a>
                                                    @else
                                                        -
                                                    @endif
                                                </span>
                                            </div>
                                            @if ($payment->status === 'rejected')
                                                <div><strong>{{ __('public.portal_detail.rejection_reason') }}</strong><span>{{ $payment->rejection_reason ?: __('public.portal_detail.rejected_help') }}</span></div>
                                            @endif
                                        </div>
                                    </article>
                                @endforeach
                            </div>
                        @else
                            <div class="empty-state-public">{{ __('public.portal_detail.no_payments') }}</div>
                        @endif
                    </div>

                    @if ($canCancel)
                        <div class="content-panel">
                            <span class="section-kicker">{{ __('public.portal_detail.cancel_kicker') }}</span>
                            <h2 class="mb-3">{{ __('public.portal_detail.cancel_title') }}</h2>
                            <form method="POST" action="{{ route('public.customer-portal.cancel', $reservation->code) }}" class="booking-form" onsubmit="return confirm(@js(__('public.portal_detail.cancel_confirm')));">
                                @csrf
                                <label class="form-label" for="portal-cancellation-reason">{{ __('public.portal_detail.cancel_reason') }}</label>
                                <textarea id="portal-cancellation-reason" name="cancellation_reason" rows="4" class="form-control @error('cancellation_reason') is-invalid @enderror" placeholder="{{ __('public.portal_detail.cancel_reason_placeholder') }}">{{ old('cancellation_reason') }}</textarea>
                                @error('cancellation_reason')
                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                @enderror
                                <button type="submit" class="btn btn-danger mt-4">{{ __('public.portal_detail.cancel_button') }}</button>
                            </form>
                        </div>
                    @endif
                </div>

                <div class="col-xl-4">
                    <div class="detail-panel booking-side-panel">
                        <span class="section-kicker">{{ __('public.portal_detail.contact_kicker') }}</span>
                        <h2>{{ __('public.portal_detail.contact_title') }}</h2>
                        <p>{{ __('public.portal_detail.contact_description') }}</p>
                        <div class="booking-side-card">
                            <strong>{{ __('public.portal_detail.tracking_code') }}</strong>
                            <div class="promo-badge mt-2">{{ $reservation->code }}</div>
                        </div>
                        @if ($whatsAppUrl)
                            <a href="{{ $whatsAppUrl }}" target="_blank" rel="noopener" class="btn btn-public-primary mt-3">{{ __('public.portal.whatsapp') }}</a>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </section>
@endsection
