<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Http\Requests\Public\StorePublicBookingRequest;
use App\Models\Customer;
use App\Models\HotelSetting;
use App\Models\Payment;
use App\Models\Promotion;
use App\Models\Reservation;
use App\Models\Room;
use App\Models\RoomType;
use App\Services\Mail\ReservationMailService;
use App\Services\Notifications\ReservationNotificationService;
use App\Services\Reservations\ReservationExpirationService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class BookingController extends Controller
{
    private const CUSTOMER_PORTAL_SESSION_KEY = 'customer_portal_reservation_code';

    public function create(Request $request): View
    {
        app(ReservationExpirationService::class)->expirePendingReservations();

        $hotelSetting = $this->hotelSetting();
        $roomTypes = RoomType::query()
            ->where('is_active', true)
            ->where('show_on_website', true)
            ->orderBy('base_price')
            ->orderBy('name')
            ->get()
            ->map(fn (RoomType $roomType) => $this->appendPublicRoomTypeMeta($roomType));

        $selectedRoomTypeId = $roomTypes->contains('id', (int) $request->query('room_type_id'))
            ? (int) $request->query('room_type_id')
            : null;

        return view('public.booking.create', [
            'hotelSetting' => $hotelSetting,
            'paymentMethods' => $this->publicPaymentMethods($hotelSetting),
            'roomTypes' => $roomTypes,
            'selectedRoomTypeId' => old('room_type_id', $selectedRoomTypeId),
            'title' => __('public.meta.booking_title', ['hotel' => $hotelSetting->hotel_name]),
            'metaDescription' => __('public.meta.booking_description', [
                'hotel' => $hotelSetting->hotel_name,
                'city' => $hotelSetting->city,
                'country' => $hotelSetting->country,
            ]),
        ]);
    }

    public function availability(Request $request): JsonResponse
    {
        app(ReservationExpirationService::class)->expirePendingReservations();

        $validated = $request->validate([
            'check_in' => ['required', 'date', 'after_or_equal:today'],
            'check_out' => ['required', 'date', 'after:check_in'],
            'adults' => ['required', 'integer', 'min:1', 'max:20'],
            'children' => ['nullable', 'integer', 'min:0', 'max:20'],
            'room_type_id' => ['nullable', 'exists:room_types,id'],
        ]);

        $checkIn = Carbon::parse($validated['check_in']);
        $checkOut = Carbon::parse($validated['check_out']);
        $adults = (int) $validated['adults'];
        $children = (int) ($validated['children'] ?? 0);
        $guests = $adults + $children;

        $roomTypes = RoomType::query()
            ->where('is_active', true)
            ->where('show_on_website', true)
            ->when(
                ! empty($validated['room_type_id']),
                fn ($query) => $query->where('id', (int) $validated['room_type_id'])
            )
            ->where('max_guests', '>=', $guests)
            ->orderBy('base_price')
            ->orderBy('name')
            ->get();

        $availableRoomTypes = $roomTypes
            ->map(function (RoomType $roomType) use ($checkIn, $checkOut, $adults, $children): ?array {
                $availableRoomsCount = $this->availableRoomsQuery($roomType, $checkIn, $checkOut)->count();

                if ($availableRoomsCount < 1) {
                    return null;
                }

                $quote = $this->calculateQuote($roomType, $checkIn, $checkOut);
                $promotion = $quote['promotion'];

                return [
                    'id' => $roomType->id,
                    'name' => $roomType->name,
                    'slug' => $roomType->slug,
                    'deposit_percentage' => $quote['deposit_percentage'],
                    'deposit_amount_required' => $quote['deposit_amount_required'],
                    'deposit_amount_required_formatted' => $quote['deposit_amount_required_formatted'],
                    'base_price' => (float) $roomType->base_price,
                    'base_price_formatted' => $this->formatMoney((float) $roomType->base_price),
                    'price_bob' => (float) ($roomType->price_bob ?? $roomType->base_price),
                    'price_usd' => (float) ($roomType->price_usd ?? 0),
                    'available_rooms_count' => $availableRoomsCount,
                    'max_guests' => (int) $roomType->max_guests,
                    'capacity_summary' => __('public.booking.capacity_summary', [
                        'adults' => $adults,
                        'children' => $children,
                        'max' => (int) $roomType->max_guests,
                    ]),
                    'promotion' => $promotion ? [
                        'id' => $promotion->id,
                        'name' => $promotion->name,
                        'discount_label' => $quote['discount_label'],
                        'final_price' => $quote['price_per_night'],
                        'final_price_formatted' => $quote['price_per_night_formatted'],
                    ] : null,
                ];
            })
            ->filter()
            ->values();

        return response()->json([
            'available' => $availableRoomTypes->isNotEmpty(),
            'room_types' => $availableRoomTypes,
        ]);
    }

    public function quote(Request $request): JsonResponse
    {
        app(ReservationExpirationService::class)->expirePendingReservations();

        $validated = $request->validate([
            'room_type_id' => ['required', 'exists:room_types,id'],
            'check_in' => ['required', 'date', 'after_or_equal:today'],
            'check_out' => ['required', 'date', 'after:check_in'],
            'adults' => ['required', 'integer', 'min:1', 'max:20'],
            'children' => ['nullable', 'integer', 'min:0', 'max:20'],
            'promotion_id' => ['nullable', 'exists:promotions,id'],
        ]);

        $roomType = RoomType::query()
            ->where('is_active', true)
            ->where('show_on_website', true)
            ->findOrFail((int) $validated['room_type_id']);

        $checkIn = Carbon::parse($validated['check_in']);
        $checkOut = Carbon::parse($validated['check_out']);
        $guests = (int) $validated['adults'] + (int) ($validated['children'] ?? 0);

        if ($guests > (int) $roomType->max_guests) {
            throw ValidationException::withMessages([
                'adults' => __('public.messages.guest_limit'),
            ]);
        }

        if (! $this->findAvailableRoom($roomType, $checkIn, $checkOut)) {
            throw ValidationException::withMessages([
                'room_type_id' => __('public.messages.no_rooms_selected_range'),
            ]);
        }

        $quote = $this->calculateQuote($roomType, $checkIn, $checkOut, $validated['promotion_id'] ?? null);

        return response()->json([
            'nights' => $quote['nights'],
            'base_price' => $quote['base_price'],
            'base_price_formatted' => $quote['base_price_formatted'],
            'promotion_id' => $quote['promotion']?->id,
            'promotion_name' => $quote['promotion']?->name,
            'discount_type' => $quote['discount_type'],
            'discount_value' => $quote['discount_value'],
            'discount_label' => $quote['discount_label'],
            'discount_amount' => $quote['discount_amount'],
            'discount_amount_formatted' => $quote['discount_amount_formatted'],
            'price_per_night' => $quote['price_per_night'],
            'price_per_night_formatted' => $quote['price_per_night_formatted'],
            'total_amount' => $quote['total_amount'],
            'total_amount_formatted' => $quote['total_amount_formatted'],
            'total_amount_bob' => $quote['total_amount_bob'],
            'total_amount_bob_formatted' => $quote['total_amount_bob_formatted'],
            'total_amount_usd' => $quote['total_amount_usd'],
            'total_amount_usd_formatted' => $quote['total_amount_usd_formatted'],
            'deposit_percentage' => $quote['deposit_percentage'],
            'deposit_amount_required' => $quote['deposit_amount_required'],
            'deposit_amount_required_formatted' => $quote['deposit_amount_required_formatted'],
            'deposit_amount_required_bob' => $quote['deposit_amount_required_bob'],
            'deposit_amount_required_bob_formatted' => $quote['deposit_amount_required_bob_formatted'],
            'deposit_amount_required_usd' => $quote['deposit_amount_required_usd'],
            'deposit_amount_required_usd_formatted' => $quote['deposit_amount_required_usd_formatted'],
            'exchange_rate' => $quote['exchange_rate'],
            'price_equivalence_label' => $quote['price_equivalence_label'],
            'balance_amount' => $quote['balance_amount'],
            'label' => $quote['label'],
        ]);
    }

    public function store(StorePublicBookingRequest $request): RedirectResponse|JsonResponse
    {
        app(ReservationExpirationService::class)->expirePendingReservations();

        $validated = $request->validated();

        $reservation = DB::transaction(function () use ($validated, $request): Reservation {
            $roomType = RoomType::query()
                ->where('is_active', true)
                ->where('show_on_website', true)
                ->findOrFail((int) $validated['room_type_id']);

            $checkIn = Carbon::parse($validated['check_in']);
            $checkOut = Carbon::parse($validated['check_out']);
            $room = $this->findAvailableRoom($roomType, $checkIn, $checkOut);

            if (! $room) {
                throw ValidationException::withMessages([
                    'room_type_id' => __('public.messages.availability_changed'),
                ]);
            }

            $customer = $this->resolveCustomer($validated);
            $quote = $this->calculateQuote($roomType, $checkIn, $checkOut);
            $paymentAmount = round((float) $validated['payment_amount'], 2);
            $hotelSetting = HotelSetting::current();
            $baseCurrency = $hotelSetting->baseCurrency();
            $paymentCurrency = $hotelSetting->normalizeCurrency($validated['payment_currency'] ?? $baseCurrency);
            $priceEquivalenceRate = (float) $quote['exchange_rate'];
            $paymentAmountBase = $paymentCurrency === 'USD'
                ? round($paymentAmount * $priceEquivalenceRate, 2)
                : $paymentAmount;
            $requiredAmount = $paymentCurrency === 'USD'
                ? (float) $quote['deposit_amount_required_usd']
                : (float) $quote['deposit_amount_required_bob'];
            $maximumAmount = $paymentCurrency === 'USD'
                ? (float) $quote['total_amount_usd']
                : (float) $quote['total_amount_bob'];

            if ($paymentCurrency === 'USD' && (! $quote['supports_usd'] || $requiredAmount <= 0 || $maximumAmount <= 0)) {
                throw ValidationException::withMessages([
                    'payment_currency' => 'Esta habitacion aun no tiene precio en dolares registrado. Selecciona bolivianos o actualiza el tipo de habitacion desde administracion.',
                ]);
            }

            if ($paymentAmount < $requiredAmount) {
                throw ValidationException::withMessages([
                    'payment_amount' => 'El anticipo minimo para esta reserva es '.$quote['deposit_amount_required_bob_formatted'].' o '.$quote['deposit_amount_required_usd_formatted'].'.',
                ]);
            }

            if ($paymentAmount > $maximumAmount) {
                throw ValidationException::withMessages([
                    'payment_amount' => 'El monto depositado no puede superar el total de '.$quote['total_amount_bob_formatted'].' o '.$quote['total_amount_usd_formatted'].'.',
                ]);
            }

            $reservation = Reservation::create([
                'code' => $this->generateReservationCode(),
                'customer_id' => $customer->id,
                'room_id' => $room->id,
                'room_type_id' => $roomType->id,
                'promotion_id' => $quote['promotion']?->id,
                'check_in' => $checkIn->toDateString(),
                'check_out' => $checkOut->toDateString(),
                'nights' => $quote['nights'],
                'adults' => (int) $validated['adults'],
                'children' => (int) ($validated['children'] ?? 0),
                'base_price' => $quote['base_price'],
                'discount_type' => $quote['discount_type'],
                'discount_value' => $quote['discount_value'],
                'discount_amount' => $quote['discount_amount'],
                'price_per_night' => $quote['price_per_night'],
                'total_amount' => $quote['total_amount'],
                'deposit_percentage' => $quote['deposit_percentage'],
                'deposit_amount_required' => $quote['deposit_amount_required'],
                'paid_amount' => 0,
                'balance_amount' => $quote['total_amount'],
                'status' => Reservation::STATUS_PENDING,
                'source' => 'website',
                'preferred_payment_method' => $validated['preferred_payment_method'],
                'special_requests' => $validated['special_requests'] ?? null,
                'created_by' => null,
                'updated_by' => null,
            ]);

            if ($quote['promotion']) {
                $quote['promotion']->increment('used_count');
            }

            Payment::create([
                'code' => $this->generatePaymentCode(),
                'reservation_id' => $reservation->id,
                'customer_id' => $customer->id,
                'amount' => $paymentAmount,
                'currency' => $paymentCurrency,
                'exchange_rate' => $paymentCurrency === 'USD' ? $priceEquivalenceRate : 1,
                'amount_base' => $paymentAmountBase,
                'payment_method' => $this->paymentMethodForLedger((string) $validated['preferred_payment_method']),
                'status' => Payment::STATUS_PENDING,
                'payment_date' => now()->toDateString(),
                'reference_number' => $validated['payment_reference_number'] ?? null,
                'receipt_image' => $this->storeReceiptImage($request),
                'notes' => sprintf(
                    'Comprobante enviado desde reserva web. Preferencia: %s. Moneda declarada: %s. Equivalencia usada segun precios registrados del tipo de habitacion: 1 USD = Bs. %s.',
                    $validated['preferred_payment_method'],
                    $paymentCurrency,
                    number_format($priceEquivalenceRate, 4, '.', '')
                ),
                'created_by' => null,
            ]);

            return $reservation;
        });

        $notificationWarning = null;

        try {
            app(ReservationNotificationService::class)->newReservation($reservation);
        } catch (\Throwable $exception) {
            report($exception);
            $notificationWarning = ' Tu reserva fue registrada, pero no se pudo enviar la notificacion interna automaticamente. El hotel igualmente podra verla en el panel administrativo.';
        }

        $mailResult = app(ReservationMailService::class)->sendCreatedEmails($reservation);
        $mailWarning = $this->mailWarning(
            (bool) ($mailResult['customer'] ?? true),
            ((int) ($mailResult['staff_failed'] ?? 0)) === 0
        );
        session([self::CUSTOMER_PORTAL_SESSION_KEY => $reservation->code]);
        $portalUrl = $this->signedPortalUrl($reservation);

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Tu solicitud y comprobante fueron enviados correctamente. El hotel revisara el pago y te contactara para confirmar.'.($notificationWarning ?? '').$mailWarning,
                'code' => $reservation->code,
                'status_url' => $portalUrl,
            ]);
        }

        return redirect()->route('public.booking.success', $reservation->code);
    }

    public function success(Reservation $reservation): View
    {
        abort_if($reservation->status === Reservation::STATUS_CANCELLED, 404);

        $reservation->loadMissing(['customer', 'room.roomType', 'roomType', 'promotion']);
        $hotelSetting = $this->hotelSetting();

        return view('public.booking.success', [
            'hotelSetting' => $hotelSetting,
            'paymentMethodLabel' => match ($reservation->preferred_payment_method) {
                'qr' => __('public.portal_detail.payment_methods.qr'),
                'bank_transfer' => __('public.messages.payment_bank_transfer_label'),
                'bank_deposit' => __('public.messages.payment_bank_deposit_label'),
                'bank' => __('public.messages.payment_bank_label'),
                'other' => __('public.messages.payment_other'),
                default => __('public.messages.payment_other'),
            },
            'reservation' => $reservation,
            'portalUrl' => $this->signedPortalUrl($reservation),
            'title' => __('public.meta.booking_success_title', ['hotel' => $hotelSetting->hotel_name]),
            'metaDescription' => __('public.meta.booking_success_description', ['hotel' => $hotelSetting->hotel_name]),
        ]);
    }

    private function signedPortalUrl(Reservation $reservation): string
    {
        return URL::temporarySignedRoute(
            'public.customer-portal.show',
            now()->addDays(180),
            ['reservation' => $reservation->code]
        );
    }

    private function mailWarning(bool ...$mailResults): string
    {
        return in_array(false, $mailResults, true)
            ? ' La reserva fue guardada, pero no se pudo enviar uno o mas correos. El hotel igualmente puede verla en el panel.'
            : '';
    }

    private function hotelSetting(): HotelSetting
    {
        return HotelSetting::query()->first() ?? new HotelSetting([
            'hotel_name' => 'Hostal Cerro Rico',
            'city' => 'Potosi',
            'country' => 'Bolivia',
            'currency' => 'BOB',
            'is_active' => true,
        ]);
    }

    private function appendPublicRoomTypeMeta(RoomType $roomType): RoomType
    {
        $roomType->setAttribute('public_promotion', $this->getBestPromotionForRoomType($roomType));

        return $roomType;
    }

    private function publicPaymentMethods(HotelSetting $hotelSetting): array
    {
        return [
            [
                'value' => 'qr',
                'label' => 'Depositar por QR',
                'description' => __('public.messages.payment_qr_description'),
                'icon' => 'bi-qr-code',
                'available' => filled($hotelSetting->digital_wallet_qr_image)
                    || filled($hotelSetting->bank_qr_image)
                    || filled($hotelSetting->payment_qr_image),
            ],
            [
                'value' => 'bank_transfer',
                'label' => __('public.messages.payment_bank_transfer_label'),
                'description' => __('public.messages.payment_bank_transfer_description'),
                'icon' => 'bi-bank',
                'available' => $this->hasBankDetails($hotelSetting),
            ],
            [
                'value' => 'bank_deposit',
                'label' => __('public.messages.payment_bank_deposit_label'),
                'description' => __('public.messages.payment_bank_deposit_description'),
                'icon' => 'bi-receipt',
                'available' => $this->hasBankDetails($hotelSetting),
            ],
        ];
    }

    private function hasBankDetails(HotelSetting $hotelSetting): bool
    {
        return filled($hotelSetting->bank_name)
            || filled($hotelSetting->bank_account_holder)
            || filled($hotelSetting->bank_account_number);
    }

    private function getBestPromotionForRoomType(RoomType $roomType): ?Promotion
    {
        return $roomType->promotions()
            ->where('is_active', true)
            ->where('show_on_website', true)
            ->get()
            ->filter(fn (Promotion $promotion): bool => $promotion->isCurrentlyActive())
            ->sortByDesc(fn (Promotion $promotion): float => $promotion->calculateDiscount((float) $roomType->base_price))
            ->first();
    }

    private function calculatePromotionDiscount(Promotion $promotion, float $basePrice): array
    {
        $discountAmount = round($promotion->calculateDiscount($basePrice), 2);
        $finalPrice = round(max($basePrice - $discountAmount, 0), 2);

        return [
            'discount_type' => $promotion->discount_type,
            'discount_value' => (float) $promotion->discount_value,
            'discount_amount' => $discountAmount,
            'price_per_night' => $finalPrice,
            'discount_label' => $promotion->discount_type === 'percentage'
                ? rtrim(rtrim(number_format((float) $promotion->discount_value, 2, '.', ''), '0'), '.').'%'
                : $this->formatMoney((float) $promotion->discount_value),
        ];
    }

    private function calculateQuote(RoomType $roomType, Carbon $checkIn, Carbon $checkOut, int|string|null $requestedPromotionId = null): array
    {
        $nights = max($checkIn->diffInDays($checkOut), 1);
        $basePrice = $roomType->priceBob();
        $basePriceUsd = $roomType->priceUsd();
        $promotion = null;

        $promotions = $roomType->promotions()
            ->where('is_active', true)
            ->where('show_on_website', true)
            ->get()
            ->filter(function (Promotion $candidate) use ($nights): bool {
                if (! $candidate->isCurrentlyActive()) {
                    return false;
                }

                return $candidate->minimum_nights === null || $nights >= (int) $candidate->minimum_nights;
            });

        if ($requestedPromotionId) {
            $promotion = $promotions->firstWhere('id', (int) $requestedPromotionId);

            if (! $promotion) {
                throw ValidationException::withMessages([
                    'promotion_id' => __('public.messages.promotion_not_applicable'),
                ]);
            }
        } else {
            $promotion = $promotions
                ->sortByDesc(fn (Promotion $candidate): float => $candidate->calculateDiscount($basePrice))
                ->first();
        }

        $discountType = null;
        $discountValue = 0.0;
        $discountAmount = 0.0;
        $discountAmountUsd = 0.0;
        $pricePerNight = $basePrice;
        $pricePerNightUsd = $basePriceUsd;
        $discountLabel = null;

        if ($promotion) {
            $discount = $this->calculatePromotionDiscount($promotion, $basePrice);
            $discountType = $discount['discount_type'];
            $discountValue = $discount['discount_value'];
            $discountAmount = $discount['discount_amount'];
            $pricePerNight = $discount['price_per_night'];
            $discountLabel = $discount['discount_label'];

            $discountRatio = $basePrice > 0 ? $pricePerNight / $basePrice : 1;
            $pricePerNightUsd = round(max($basePriceUsd * $discountRatio, 0), 2);
            $discountAmountUsd = round(max($basePriceUsd - $pricePerNightUsd, 0), 2);
        }

        $totalAmount = round($pricePerNight * $nights, 2);
        $totalAmountUsd = round($pricePerNightUsd * $nights, 2);
        $depositPercentage = $roomType->reservationDepositPercentage();
        $depositAmountRequired = round(($totalAmount * $depositPercentage) / 100, 2);
        $depositAmountRequiredUsd = round(($totalAmountUsd * $depositPercentage) / 100, 2);
        $priceEquivalenceRate = $totalAmountUsd > 0 ? round($totalAmount / $totalAmountUsd, 4) : 1.0;

        return [
            'promotion' => $promotion,
            'nights' => $nights,
            'base_price' => $basePrice,
            'base_price_formatted' => $this->formatMoney($basePrice),
            'base_price_usd' => $basePriceUsd,
            'base_price_usd_formatted' => $this->formatMoney($basePriceUsd, 'USD'),
            'discount_type' => $discountType,
            'discount_value' => $discountValue,
            'discount_label' => $discountLabel,
            'discount_amount' => $discountAmount,
            'discount_amount_formatted' => $this->formatMoney($discountAmount),
            'discount_amount_usd' => $discountAmountUsd,
            'discount_amount_usd_formatted' => $this->formatMoney($discountAmountUsd, 'USD'),
            'price_per_night' => $pricePerNight,
            'price_per_night_formatted' => $this->formatMoney($pricePerNight),
            'price_per_night_usd' => $pricePerNightUsd,
            'price_per_night_usd_formatted' => $this->formatMoney($pricePerNightUsd, 'USD'),
            'total_amount' => $totalAmount,
            'total_amount_formatted' => $this->formatMoney($totalAmount),
            'total_amount_bob' => $totalAmount,
            'total_amount_bob_formatted' => $this->formatMoney($totalAmount),
            'total_amount_usd' => $totalAmountUsd,
            'total_amount_usd_formatted' => $this->formatMoney($totalAmountUsd, 'USD'),
            'deposit_percentage' => $depositPercentage,
            'deposit_amount_required' => $depositAmountRequired,
            'deposit_amount_required_formatted' => $this->formatMoney($depositAmountRequired),
            'deposit_amount_required_bob' => $depositAmountRequired,
            'deposit_amount_required_bob_formatted' => $this->formatMoney($depositAmountRequired),
            'deposit_amount_required_usd' => $depositAmountRequiredUsd,
            'deposit_amount_required_usd_formatted' => $this->formatMoney($depositAmountRequiredUsd, 'USD'),
            'supports_usd' => $totalAmountUsd > 0,
            'exchange_rate' => $priceEquivalenceRate,
            'price_equivalence_label' => 'Equivalencia calculada con precios registrados del tipo de habitacion',
            'balance_amount' => $totalAmount,
            'label' => __('public.booking.quote_label', [
                'nights' => $nights,
                'price' => $this->formatMoney($pricePerNight),
                'total' => $this->formatMoney($totalAmount),
            ]),
        ];
    }

    private function findAvailableRoom(RoomType $roomType, Carbon $checkIn, Carbon $checkOut): ?Room
    {
        return $this->availableRoomsQuery($roomType, $checkIn, $checkOut)->first();
    }

    private function availableRoomsQuery(RoomType $roomType, Carbon $checkIn, Carbon $checkOut)
    {
        return Room::query()
            ->where('room_type_id', $roomType->id)
            ->where('is_active', true)
            ->whereDoesntHave('reservations', function ($query) use ($checkIn, $checkOut): void {
                $query->whereIn('status', Reservation::ACTIVE_STATUSES)
                    ->where('check_in', '<', $checkOut->toDateString())
                    ->where('check_out', '>', $checkIn->toDateString());
            })
            ->orderBy('number');
    }

    private function resolveCustomer(array $validated): Customer
    {
        $email = filled($validated['email'] ?? null) ? Str::lower(trim((string) $validated['email'])) : null;
        $documentType = $validated['document_type'] ?? null;
        $documentNumber = filled($validated['document_number'] ?? null) ? trim((string) $validated['document_number']) : null;

        $customer = null;

        if ($email) {
            $customer = Customer::query()->whereRaw('LOWER(email) = ?', [$email])->first();
        }

        if (! $customer && $documentType && $documentNumber) {
            $customer = Customer::query()
                ->where('document_type', $documentType)
                ->where('document_number', $documentNumber)
                ->first();
        }

        $payload = [
            'full_name' => trim((string) $validated['full_name']),
            'document_type' => $documentType,
            'document_number' => $documentNumber,
            'nationality' => $this->nullableString($validated['nationality'] ?? null),
            'phone' => $this->nullableString($validated['phone'] ?? null),
            'whatsapp' => $this->nullableString($validated['whatsapp'] ?? null),
            'email' => $email,
            'country' => $this->nullableString($validated['country'] ?? null),
            'city' => $this->nullableString($validated['city'] ?? null),
            'is_foreign' => $this->isForeign($validated),
            'is_active' => true,
        ];

        if (! $customer) {
            return Customer::query()->create($payload);
        }

        foreach ($payload as $field => $value) {
            if (in_array($field, ['is_foreign', 'is_active'], true)) {
                $customer->{$field} = $value;
                continue;
            }

            if (blank($customer->{$field}) && filled($value)) {
                $customer->{$field} = $value;
            }
        }

        $customer->save();

        return $customer;
    }

    private function generateReservationCode(): string
    {
        $prefix = 'RES-'.now()->format('Ymd').'-';
        $latestCode = Reservation::withTrashed()
            ->where('code', 'like', $prefix.'%')
            ->latest('id')
            ->value('code');

        $lastNumber = 0;
        if ($latestCode && preg_match('/(\d{4})$/', $latestCode, $matches) === 1) {
            $lastNumber = (int) $matches[1];
        }

        return $prefix.str_pad((string) ($lastNumber + 1), 4, '0', STR_PAD_LEFT);
    }

    private function formatMoney(float $amount, ?string $currency = 'BOB'): string
    {
        $symbol = $currency === 'USD' ? '$us ' : 'Bs. ';

        return $symbol.number_format($amount, 2, '.', '');
    }

    private function generatePaymentCode(): string
    {
        $prefix = 'PAY-'.now()->format('Ymd').'-';
        $latestCode = Payment::withTrashed()
            ->where('code', 'like', $prefix.'%')
            ->latest('id')
            ->value('code');

        $lastNumber = 0;
        if ($latestCode && preg_match('/(\d{4})$/', $latestCode, $matches) === 1) {
            $lastNumber = (int) $matches[1];
        }

        return $prefix.str_pad((string) ($lastNumber + 1), 4, '0', STR_PAD_LEFT);
    }

    private function storeReceiptImage(StorePublicBookingRequest $request): string
    {
        $file = $request->file('receipt_image');
        $extension = strtolower((string) $file->getClientOriginalExtension());
        $filename = Str::uuid()->toString().'-'.time().'.'.$extension;

        return $file->storeAs('payments/receipts', $filename, 'public');
    }

    private function paymentMethodForLedger(string $preferredMethod): string
    {
        return match ($preferredMethod) {
            'qr' => 'qr',
            'bank_transfer', 'bank_deposit', 'bank' => 'bank',
            default => 'other',
        };
    }

    private function nullableString(mixed $value): ?string
    {
        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }

    private function isForeign(array $validated): bool
    {
        $country = Str::lower(trim((string) ($validated['country'] ?? '')));
        $nationality = Str::lower(trim((string) ($validated['nationality'] ?? '')));

        return ($country !== '' && $country !== 'bolivia')
            || ($nationality !== '' && $nationality !== 'boliviana' && $nationality !== 'boliviano' && $nationality !== 'bolivia');
    }
}
