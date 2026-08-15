<?php

namespace App\Http\Controllers\AdminLte;

use App\Http\Controllers\Controller;
use App\Http\Requests\AdminLte\CheckOutRequest;
use App\Http\Requests\AdminLte\StoreReservationRequest;
use App\Http\Requests\AdminLte\UpdateReservationRequest;
use App\Models\CashMovement;
use App\Models\CashRegister;
use App\Models\Customer;
use App\Models\HotelSetting;
use App\Models\Payment;
use App\Models\Promotion;
use App\Models\Reservation;
use App\Models\Room;
use App\Models\RoomType;
use App\Services\Mail\ReservationMailService;
use App\Services\Notifications\ReservationNotificationService;
use App\Services\HotelOperations\ReservationLedgerService;
use App\Services\Reservations\ReservationExpirationService;
use App\Support\DatabaseDialect;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Yajra\DataTables\Facades\DataTables;

class ReservationController extends Controller
{
    private const DOCUMENT_TYPES = [
        'ci' => 'CI',
        'passport' => 'Pasaporte',
        'nit' => 'NIT',
        'other' => 'Otro',
    ];

    private const STATUSES = [
        Reservation::STATUS_PENDING => [
            'label' => 'Pendiente',
            'badge' => 'badge text-bg-warning',
            'icon' => 'bi-hourglass-split',
        ],
        Reservation::STATUS_CONFIRMED => [
            'label' => 'Confirmada',
            'badge' => 'badge text-bg-success',
            'icon' => 'bi-shield-check',
        ],
        Reservation::STATUS_CHECKED_IN => [
            'label' => 'Ocupada',
            'badge' => 'badge text-bg-danger',
            'icon' => 'bi-box-arrow-in-right',
        ],
        Reservation::STATUS_CHECKED_OUT => [
            'label' => 'Salida registrada',
            'badge' => 'badge text-bg-secondary',
            'icon' => 'bi-box-arrow-right',
        ],
        Reservation::STATUS_CANCELLED => [
            'label' => 'Cancelada',
            'badge' => 'badge text-bg-secondary',
            'icon' => 'bi-x-circle',
        ],
        Reservation::STATUS_EXPIRED => [
            'label' => 'Expirada',
            'badge' => 'badge text-bg-secondary',
            'icon' => 'bi-clock-history',
        ],
        Reservation::STATUS_NO_SHOW => [
            'label' => 'No se presento',
            'badge' => 'badge text-bg-dark',
            'icon' => 'bi-person-x',
        ],
    ];

    private const SOURCES = [
        'reception' => 'Recepcion',
        'website' => 'Pagina web',
        'phone' => 'Telefono',
        'whatsapp' => 'WhatsApp',
        'agency' => 'Agencia',
        'other' => 'Otro',
    ];

    private const PAYMENT_METHODS = [
        'cash' => 'Efectivo',
        'qr' => 'QR',
        'bank' => 'Deposito / Transferencia',
        'card' => 'Tarjeta',
        'other' => 'Otro',
    ];

    private const PAYMENT_STATUSES = [
        Payment::STATUS_PENDING => ['label' => 'Pendiente', 'badge' => 'badge text-bg-warning'],
        Payment::STATUS_CONFIRMED => ['label' => 'Confirmado', 'badge' => 'badge text-bg-success'],
        Payment::STATUS_REJECTED => ['label' => 'Rechazado', 'badge' => 'badge text-bg-danger'],
        Payment::STATUS_CANCELLED => ['label' => 'Anulado', 'badge' => 'badge text-bg-secondary'],
        Payment::STATUS_REFUNDED => ['label' => 'Devuelto', 'badge' => 'badge text-bg-info'],
    ];

    public function index(): View
    {
        $this->authorize('viewAny', Reservation::class);
        app(ReservationExpirationService::class)->expirePendingReservations();

        $customers = Customer::query()
            ->where('is_active', true)
            ->orderBy('full_name')
            ->get(['id', 'full_name', 'document_number']);

        $rooms = Room::query()
            ->with('roomType')
            ->where('is_active', true)
            ->orderBy('number')
            ->get();

        $promotions = Promotion::query()
            ->with('roomTypes')
            ->where('is_active', true)
            ->get()
            ->filter(fn (Promotion $promotion): bool => $promotion->isCurrentlyActive())
            ->values();

        $reservations = Reservation::query()
            ->with(['roomType', 'room.roomType', 'payments'])
            ->get();
        $pendingBalances = $this->pendingBalancesByCurrency($reservations);

        $hotelSetting = HotelSetting::current();

        return view('adminlte.reservations.index', [
            'customers' => $customers,
            'paymentPreferences' => Reservation::PREFERRED_PAYMENT_METHODS,
            'paymentMethods' => self::PAYMENT_METHODS,
            'supportedCurrencies' => $hotelSetting->supportedCurrencies(),
            'currencySymbols' => $hotelSetting->currencySymbols(),
            'requiresOpenCashRegister' => $this->requiresOpenCashRegisterForIncome(),
            'hasOpenCashRegister' => $this->currentUserOpenCashRegister() !== null,
            'rooms' => $rooms,
            'promotions' => $promotions,
            'statuses' => [
                Reservation::STATUS_PENDING => self::STATUSES[Reservation::STATUS_PENDING]['label'],
                Reservation::STATUS_CONFIRMED => self::STATUSES[Reservation::STATUS_CONFIRMED]['label'],
            ],
            'sources' => self::SOURCES,
            'canChangePrice' => auth()->user()->can('reservas.cambiar_precio'),
            'canApplyDiscount' => auth()->user()->can('reservas.aplicar_descuento'),
            'stats' => [
                'total' => $reservations->count(),
                'pending' => $reservations->where('status', Reservation::STATUS_PENDING)->count(),
                'confirmed' => $reservations->where('status', Reservation::STATUS_CONFIRMED)->count(),
                'checked_in' => $reservations->where('status', Reservation::STATUS_CHECKED_IN)->count(),
                'expired' => $reservations->where('status', Reservation::STATUS_EXPIRED)->count(),
                'balance' => (float) $reservations->sum('balance_amount'),
                'balance_bob' => $pendingBalances['BOB'],
                'balance_usd' => $pendingBalances['USD'],
                'today_arrivals' => $reservations
                    ->whereIn('status', [Reservation::STATUS_CONFIRMED, Reservation::STATUS_PENDING])
                    ->filter(fn (Reservation $reservation): bool => optional($reservation->check_in)->isToday())
                    ->count(),
            ],
        ]);
    }

    public function data(): JsonResponse
    {
        $this->authorize('viewAny', Reservation::class);
        app(ReservationExpirationService::class)->expirePendingReservations();
        $canConfirmIncome = ! $this->requiresOpenCashRegisterForIncome() || $this->currentUserOpenCashRegister() !== null;

        $query = Reservation::query()
            ->with(['customer', 'room.roomType', 'roomType', 'promotion', 'payments'])
            ->select('reservations.*');

        return DataTables::eloquent($query)
            ->editColumn('check_in', fn (Reservation $reservation): string => optional($reservation->check_in)?->toDateString() ?? '')
            ->editColumn('check_out', fn (Reservation $reservation): string => optional($reservation->check_out)?->toDateString() ?? '')
            ->editColumn('base_price', fn (Reservation $reservation): float => (float) $reservation->base_price)
            ->editColumn('discount_value', fn (Reservation $reservation): float => (float) $reservation->discount_value)
            ->editColumn('discount_amount', fn (Reservation $reservation): float => (float) $reservation->discount_amount)
            ->editColumn('price_per_night', fn (Reservation $reservation): float => (float) $reservation->price_per_night)
            ->editColumn('total_amount', fn (Reservation $reservation): float => (float) $reservation->total_amount)
            ->editColumn('deposit_amount_required', fn (Reservation $reservation): float => (float) $reservation->deposit_amount_required)
            ->editColumn('paid_amount', fn (Reservation $reservation): float => (float) $reservation->paid_amount)
            ->editColumn('balance_amount', fn (Reservation $reservation): float => (float) $reservation->balance_amount)
            ->addColumn('customer_name', fn (Reservation $reservation): string => $reservation->customer?->full_name ?? '-')
            ->addColumn('customer_document', fn (Reservation $reservation): string => $reservation->customer?->document_number ?? '')
            ->addColumn('customer_document_type_label', fn (Reservation $reservation): string => self::DOCUMENT_TYPES[$reservation->customer?->document_type] ?? 'Documento')
            ->addColumn('room_number', fn (Reservation $reservation): string => $reservation->room?->number ?? '-')
            ->addColumn('room_type_name', fn (Reservation $reservation): string => $reservation->roomType?->name ?? $reservation->room?->roomType?->name ?? '-')
            ->addColumn('check_in_formatted', fn (Reservation $reservation): string => optional($reservation->check_in)?->format('d/m/Y') ?? '-')
            ->addColumn('check_out_formatted', fn (Reservation $reservation): string => optional($reservation->check_out)?->format('d/m/Y') ?? '-')
            ->addColumn('guests_summary', fn (Reservation $reservation): string => sprintf('%d adulto(s) / %d nino(s)', (int) $reservation->adults, (int) $reservation->children))
            ->addColumn('base_price_formatted', fn (Reservation $reservation): string => $this->formatMoney((float) $reservation->base_price))
            ->addColumn('discount_label', function (Reservation $reservation): string {
                if ((float) $reservation->discount_amount <= 0) {
                    return '';
                }

                if ($reservation->discount_type === 'percentage') {
                    return 'Desc. '.rtrim(rtrim(number_format((float) $reservation->discount_value, 2, '.', ''), '0'), '.').'%';
                }

                if ($reservation->discount_type === 'fixed') {
                    return 'Desc. '.$this->formatMoney((float) $reservation->discount_value);
                }

                return 'Descuento aplicado';
            })
            ->addColumn('price_per_night_formatted', fn (Reservation $reservation): string => $this->formatMoney((float) $reservation->price_per_night))
            ->addColumn('total_amount_formatted', fn (Reservation $reservation): string => $this->formatMoney((float) $reservation->total_amount))
            ->addColumn('deposit_amount_required_formatted', fn (Reservation $reservation): string => $this->formatMoney((float) $reservation->deposit_amount_required))
            ->addColumn('deposit_pending_formatted', fn (Reservation $reservation): string => $this->formatMoney($reservation->depositAmountPending()))
            ->addColumn('paid_amount_formatted', fn (Reservation $reservation): string => $this->formatMoney((float) $reservation->paid_amount))
            ->addColumn('balance_amount_formatted', fn (Reservation $reservation): string => $this->formatMoney((float) $reservation->balance_amount))
            ->addColumn('display_currency', fn (Reservation $reservation): string => $this->reservationDisplayCurrency($reservation))
            ->addColumn('display_total_amount_formatted', fn (Reservation $reservation): string => $this->formatMoneyForReservationCurrency($reservation, (float) $reservation->total_amount))
            ->addColumn('display_price_per_night_formatted', fn (Reservation $reservation): string => $this->formatMoneyForReservationCurrency($reservation, (float) $reservation->price_per_night))
            ->addColumn('display_paid_amount_formatted', fn (Reservation $reservation): string => $this->formatReservationPaidAmount($reservation))
            ->addColumn('display_balance_amount_formatted', fn (Reservation $reservation): string => $this->formatMoneyForReservationCurrency($reservation, (float) $reservation->balance_amount))
            ->addColumn('display_deposit_summary_label', fn (Reservation $reservation): string => sprintf(
                '%d%% | %s | Pendiente %s',
                $reservation->normalizedDepositPercentage(),
                $this->formatMoneyForReservationCurrency($reservation, (float) $reservation->deposit_amount_required),
                $this->formatMoneyForReservationCurrency($reservation, $reservation->depositAmountPending())
            ))
            ->addColumn('deposit_summary_label', fn (Reservation $reservation): string => sprintf(
                '%d%% | %s | Pendiente %s',
                $reservation->normalizedDepositPercentage(),
                $this->formatMoney((float) $reservation->deposit_amount_required),
                $this->formatMoney($reservation->depositAmountPending())
            ))
            ->addColumn('status_label', fn (Reservation $reservation): string => self::STATUSES[$reservation->status]['label'] ?? ucfirst($reservation->status))
            ->addColumn('status_badge_class', fn (Reservation $reservation): string => self::STATUSES[$reservation->status]['badge'] ?? 'badge text-bg-secondary')
            ->addColumn('status_icon', fn (Reservation $reservation): string => self::STATUSES[$reservation->status]['icon'] ?? 'bi-calendar-check')
            ->addColumn('source_label', fn (Reservation $reservation): string => self::SOURCES[$reservation->source] ?? ucfirst($reservation->source))
            ->addColumn('preferred_payment_method_label', fn (Reservation $reservation): string => Reservation::PREFERRED_PAYMENT_METHODS[$reservation->preferred_payment_method] ?? 'Sin definir')
            ->addColumn('promotion_name', fn (Reservation $reservation): ?string => $reservation->promotion?->name)
            ->addColumn('payments_count', fn (Reservation $reservation): int => $reservation->payments->count())
            ->addColumn('pending_payments_count', fn (Reservation $reservation): int => $reservation->payments->where('status', Payment::STATUS_PENDING)->count())
            ->addColumn('latest_payment_status_label', function (Reservation $reservation): string {
                $payment = $reservation->payments->sortByDesc('id')->first();

                return $payment ? (self::PAYMENT_STATUSES[$payment->status]['label'] ?? ucfirst($payment->status)) : 'Sin pagos';
            })
            ->addColumn('latest_payment_badge_class', function (Reservation $reservation): string {
                $payment = $reservation->payments->sortByDesc('id')->first();

                return $payment ? (self::PAYMENT_STATUSES[$payment->status]['badge'] ?? 'badge text-bg-secondary') : 'badge text-bg-light text-dark';
            })
            ->addColumn('payments', fn (Reservation $reservation): array => $this->serializeReservationPayments($reservation, $canConfirmIncome))
            ->addColumn('payment_progress_percentage', fn (Reservation $reservation): int => (float) $reservation->total_amount > 0
                ? min((int) round(((float) $reservation->paid_amount * 100) / (float) $reservation->total_amount), 100)
                : 0)
            ->addColumn('created_at_formatted', fn (Reservation $reservation): string => optional($reservation->created_at)?->format('d/m/Y H:i') ?? '-')
            ->addColumn('expired_at_formatted', fn (Reservation $reservation): string => optional($reservation->expired_at)?->format('d/m/Y H:i') ?? '-')
            ->addColumn('can_update', fn (Reservation $reservation): bool => auth()->user()->can('update', $reservation)
                && (! in_array($reservation->status, [Reservation::STATUS_CHECKED_OUT, Reservation::STATUS_CANCELLED, Reservation::STATUS_EXPIRED], true) || auth()->user()->hasRole('admin')))
            ->addColumn('can_cancel', fn (Reservation $reservation): bool => auth()->user()->can('cancel', $reservation) && $reservation->canBeCancelled())
            ->addColumn('can_confirm', fn (Reservation $reservation): bool => auth()->user()->can('confirm', $reservation)
                && $reservation->canBeConfirmed()
                && $reservation->hasRequiredDeposit())
            ->addColumn('can_confirm_blocked', fn (Reservation $reservation): bool => auth()->user()->can('confirm', $reservation)
                && $reservation->canBeConfirmed()
                && ! $reservation->hasRequiredDeposit())
            ->addColumn('confirm_blocked_message', fn (Reservation $reservation): string => sprintf(
                'Falta confirmar anticipo. Requerido: %s. Pendiente: %s.',
                $this->formatMoney((float) $reservation->deposit_amount_required),
                $this->formatMoney($reservation->depositAmountPending())
            ))
            ->addColumn('can_checkin', fn (Reservation $reservation): bool => auth()->user()->can('checkIn', $reservation) && $reservation->canCheckIn())
            ->addColumn('can_checkout', fn (Reservation $reservation): bool => auth()->user()->can('checkOut', $reservation) && $reservation->canCheckOut())
            ->addColumn('update_url', fn (Reservation $reservation): string => route('adminlte.reservations.update', $reservation))
            ->addColumn('cancel_url', fn (Reservation $reservation): string => route('adminlte.reservations.cancel', $reservation))
            ->addColumn('confirm_url', fn (Reservation $reservation): string => route('adminlte.reservations.confirm', $reservation))
            ->addColumn('checkin_url', fn (Reservation $reservation): string => route('adminlte.reservations.check-in', $reservation))
            ->addColumn('checkout_url', fn (Reservation $reservation): string => route('adminlte.reservations.check-out', $reservation))
            ->toJson();
    }

    public function customerSearch(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Reservation::class);

        $term = trim((string) $request->query('term', ''));
        $page = max((int) $request->query('page', 1), 1);
        $perPage = 12;

        if (mb_strlen($term) < 3) {
            return response()->json([
                'results' => [],
                'pagination' => [
                    'more' => false,
                ],
            ]);
        }

        $customers = Customer::query()
            ->where('is_active', true)
            ->where(fn ($searchQuery) => DatabaseDialect::whereAnyLike($searchQuery, [
                'full_name',
                'document_number',
                'phone',
                'whatsapp',
                'email',
            ], $term))
            ->orderBy('full_name')
            ->paginate($perPage, ['id', 'full_name', 'document_type', 'document_number', 'phone', 'whatsapp', 'email'], 'page', $page);

        return response()->json([
            'results' => $customers->getCollection()
                ->map(fn (Customer $customer): array => [
                    'id' => $customer->id,
                    'text' => $this->customerSelectLabel($customer),
                    'name' => $customer->full_name,
                    'document_type' => self::DOCUMENT_TYPES[$customer->document_type] ?? 'Otro',
                    'document' => $customer->document_number,
                    'phone' => $customer->phone ?: $customer->whatsapp,
                    'email' => $customer->email,
                ])
                ->values(),
            'pagination' => [
                'more' => $customers->hasMorePages(),
            ],
        ]);
    }

    public function agenda(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Reservation::class);
        app(ReservationExpirationService::class)->expirePendingReservations();

        $validated = $request->validate([
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date', 'after_or_equal:date_from'],
        ]);

        $dateFrom = ! empty($validated['date_from'])
            ? Carbon::parse($validated['date_from'])->startOfDay()
            : now()->startOfMonth();
        $dateTo = ! empty($validated['date_to'])
            ? Carbon::parse($validated['date_to'])->endOfDay()
            : now()->endOfMonth();

        $reservations = Reservation::query()
            ->with(['customer', 'room.roomType', 'roomType', 'promotion'])
            ->whereIn('status', [
                Reservation::STATUS_PENDING,
                Reservation::STATUS_CONFIRMED,
                Reservation::STATUS_CHECKED_IN,
            ])
            ->where('check_in', '<=', $dateTo->toDateString())
            ->where('check_out', '>=', $dateFrom->toDateString())
            ->orderBy('check_in')
            ->orderBy('check_out')
            ->get()
            ->map(fn (Reservation $reservation): array => [
                'id' => $reservation->id,
                'code' => $reservation->code,
                'status' => $reservation->status,
                'status_label' => self::STATUSES[$reservation->status]['label'] ?? ucfirst((string) $reservation->status),
                'status_badge_class' => self::STATUSES[$reservation->status]['badge'] ?? 'badge text-bg-secondary',
                'source_label' => self::SOURCES[$reservation->source] ?? ucfirst((string) $reservation->source),
                'check_in' => optional($reservation->check_in)?->toDateString(),
                'check_out' => optional($reservation->check_out)?->toDateString(),
                'check_in_formatted' => optional($reservation->check_in)?->format('d/m/Y') ?? '-',
                'check_out_formatted' => optional($reservation->check_out)?->format('d/m/Y') ?? '-',
                'nights' => (int) $reservation->nights,
                'customer_name' => $reservation->customer?->full_name ?? 'Sin cliente',
                'customer_document' => $reservation->customer?->document_number,
                'customer_document_type_label' => self::DOCUMENT_TYPES[$reservation->customer?->document_type] ?? 'Documento',
                'customer_phone' => $reservation->customer?->phone,
                'customer_whatsapp' => $reservation->customer?->whatsapp,
                'customer_email' => $reservation->customer?->email,
                'room_number' => $reservation->room?->number ?? '-',
                'room_type_name' => $reservation->roomType?->name ?? $reservation->room?->roomType?->name ?? '-',
                'guests_summary' => sprintf('%d adulto(s) / %d nino(s)', (int) $reservation->adults, (int) $reservation->children),
                'total_amount_formatted' => $this->formatMoneyForReservationCurrency($reservation, (float) $reservation->total_amount),
                'paid_amount_formatted' => $this->formatReservationPaidAmount($reservation),
                'balance_amount_formatted' => $this->formatMoneyForReservationCurrency($reservation, (float) $reservation->balance_amount),
                'deposit_pending_formatted' => $this->formatMoneyForReservationCurrency($reservation, $reservation->depositAmountPending()),
                'payment_method_label' => Reservation::PREFERRED_PAYMENT_METHODS[$reservation->preferred_payment_method] ?? 'Sin definir',
                'promotion_name' => $reservation->promotion?->name,
                'special_requests' => trim((string) ($reservation->special_requests ?: '')),
            ])
            ->values();

        return response()->json([
            'date_from' => $dateFrom->toDateString(),
            'date_to' => $dateTo->toDateString(),
            'summary' => [
                'total' => $reservations->count(),
                'pending' => $reservations->where('status', Reservation::STATUS_PENDING)->count(),
                'confirmed' => $reservations->where('status', Reservation::STATUS_CONFIRMED)->count(),
                'checked_in' => $reservations->where('status', Reservation::STATUS_CHECKED_IN)->count(),
            ],
            'reservations' => $reservations,
        ]);
    }

    public function store(StoreReservationRequest $request): JsonResponse
    {
        $this->authorize('create', Reservation::class);

        $validated = $request->validated();

        $reservationConfirmedByPayment = false;

        $reservation = DB::transaction(function () use ($validated, &$reservationConfirmedByPayment): Reservation {
            $room = Room::query()->with('roomType')->findOrFail($validated['room_id']);
            $promotion = ! empty($validated['promotion_id'])
                ? Promotion::query()->find($validated['promotion_id'])
                : null;
            $quote = $this->buildQuotePayload($room, $validated, $promotion);

            $reservation = new Reservation([
                'code' => $this->generateReservationCode(),
                'customer_id' => $validated['customer_id'],
                'room_id' => $room->id,
                'room_type_id' => $room->room_type_id,
                'promotion_id' => $promotion?->id,
                'check_in' => $validated['check_in'],
                'check_out' => $validated['check_out'],
                'adults' => $validated['adults'],
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
                'source' => $validated['source'] ?? 'reception',
                'preferred_payment_method' => $validated['preferred_payment_method'] ?? null,
                'special_requests' => $validated['special_requests'] ?? null,
                'internal_notes' => $validated['internal_notes'] ?? null,
                'created_by' => auth()->id(),
            ]);

            $reservation->save();

            if ($promotion) {
                $promotion->increment('used_count');
            }

            if ($reservation->status === Reservation::STATUS_CONFIRMED && $room->status === 'available') {
                $room->update(['status' => 'reserved']);
            }

            $initialPaymentAmount = round((float) ($validated['initial_payment_amount'] ?? 0), 2);

            if ($initialPaymentAmount > 0) {
                $reservationConfirmedByPayment = $this->registerInitialPaymentForReservation($reservation, $validated);
            }

            return $reservation;
        });

        app(ReservationNotificationService::class)->newReservation($reservation);
        $createdMailResult = app(ReservationMailService::class)->sendCreatedEmails($reservation);

        if ($reservation->status === Reservation::STATUS_CONFIRMED) {
            $confirmedMailSent = app(ReservationMailService::class)->sendConfirmedEmail($reservation);
        }

        return response()->json([
            'message' => ($reservationConfirmedByPayment
                ? 'Reserva creada con pago inicial y confirmada correctamente.'
                : 'Reserva creada correctamente.').$this->mailWarning(
                (bool) ($createdMailResult['customer'] ?? true),
                ((int) ($createdMailResult['staff_failed'] ?? 0)) === 0,
                $confirmedMailSent ?? true
            ),
            'id' => $reservation->id,
            'guest_update_url' => route('adminlte.front-desk.reservations.guests', $reservation),
        ]);
    }

    public function update(UpdateReservationRequest $request, Reservation $reservation): JsonResponse
    {
        $this->authorize('update', $reservation);

        $validated = $request->validated();
        $paymentRegisteredWhileEditing = false;
        $reservationConfirmedByPayment = false;

        DB::transaction(function () use ($validated, $reservation, &$paymentRegisteredWhileEditing, &$reservationConfirmedByPayment): void {
            if (
                in_array($reservation->status, [Reservation::STATUS_CHECKED_OUT, Reservation::STATUS_CANCELLED, Reservation::STATUS_EXPIRED], true)
                && ! auth()->user()->hasRole('admin')
            ) {
                abort(422, 'No se puede editar una reserva finalizada, cancelada o expirada.');
            }

            $room = Room::query()->with('roomType')->findOrFail($validated['room_id']);
            $newPromotion = ! empty($validated['promotion_id'])
                ? Promotion::query()->find($validated['promotion_id'])
                : null;
            $oldPromotion = $reservation->promotion_id
                ? Promotion::query()->find($reservation->promotion_id)
                : null;
            $confirmedPaidAmount = app(ReservationLedgerService::class)->confirmedAmount($reservation);
            $quotePayload = array_merge($validated, [
                'paid_amount' => $confirmedPaidAmount,
            ]);
            $quote = $this->buildQuotePayload($room, $quotePayload, $newPromotion);
            $newStatus = $validated['status'] ?? $reservation->status;
            $paymentAmount = round((float) ($validated['initial_payment_amount'] ?? 0), 2);
            $statusForSave = $paymentAmount > 0 && $newStatus === Reservation::STATUS_CONFIRMED
                ? Reservation::STATUS_PENDING
                : $newStatus;

            $reservation->fill([
                'customer_id' => $validated['customer_id'],
                'room_id' => $room->id,
                'room_type_id' => $room->room_type_id,
                'promotion_id' => $newPromotion?->id,
                'check_in' => $validated['check_in'],
                'check_out' => $validated['check_out'],
                'adults' => $validated['adults'],
                'children' => (int) ($validated['children'] ?? 0),
                'base_price' => $quote['base_price'],
                'discount_type' => $quote['discount_type'],
                'discount_value' => $quote['discount_value'],
                'discount_amount' => $quote['discount_amount'],
                'price_per_night' => $quote['price_per_night'],
                'total_amount' => $quote['total_amount'],
                'deposit_percentage' => $quote['deposit_percentage'],
                'deposit_amount_required' => $quote['deposit_amount_required'],
                'paid_amount' => round($confirmedPaidAmount, 2),
                'balance_amount' => max(round($quote['total_amount'] - $confirmedPaidAmount, 2), 0),
                'status' => $statusForSave,
                'source' => $validated['source'] ?? $reservation->source,
                'preferred_payment_method' => $validated['preferred_payment_method'] ?? $reservation->preferred_payment_method,
                'special_requests' => $validated['special_requests'] ?? null,
                'internal_notes' => $validated['internal_notes'] ?? null,
                'updated_by' => auth()->id(),
            ]);

            if ($statusForSave === Reservation::STATUS_CONFIRMED && $reservation->confirmed_at === null) {
                $reservation->confirmed_at = now();
            }

            if ($statusForSave === Reservation::STATUS_PENDING) {
                $reservation->confirmed_at = null;
            }

            if (in_array($statusForSave, [Reservation::STATUS_PENDING, Reservation::STATUS_CONFIRMED], true)) {
                $reservation->expired_at = null;
            }

            $reservation->save();

            if ($paymentAmount > 0) {
                $paymentRegisteredWhileEditing = true;
                $reservationConfirmedByPayment = $this->registerInitialPaymentForReservation($reservation, $validated);
                $reservation->refresh();
            }

            if (
                $newStatus === Reservation::STATUS_CONFIRMED
                && round((float) $reservation->paid_amount, 2) < round((float) $reservation->deposit_amount_required, 2)
            ) {
                abort(422, sprintf(
                    'No se puede actualizar la reserva como confirmada. Debe tener al menos %d%% de anticipo (%s).',
                    $reservation->normalizedDepositPercentage(),
                    $this->formatMoney((float) $reservation->deposit_amount_required)
                ));
            }

            if ($newStatus === Reservation::STATUS_CONFIRMED && $reservation->status !== Reservation::STATUS_CONFIRMED) {
                $reservation->update([
                    'status' => Reservation::STATUS_CONFIRMED,
                    'confirmed_at' => $reservation->confirmed_at ?? now(),
                    'updated_by' => auth()->id(),
                ]);
            }

            $this->syncPromotionUsage($oldPromotion, $newPromotion);

            if ($newStatus === Reservation::STATUS_CONFIRMED && $room->status === 'available') {
                $room->update(['status' => 'reserved']);
            }
        });

        return response()->json([
            'message' => $reservationConfirmedByPayment
                ? 'Reserva actualizada, pago registrado y reserva confirmada correctamente.'
                : ($paymentRegisteredWhileEditing
                    ? 'Reserva actualizada y pago registrado correctamente.'
                    : 'Reserva actualizada correctamente.'),
        ]);
    }

    public function cancel(Request $request, Reservation $reservation): JsonResponse
    {
        $this->authorize('cancel', $reservation);

        $validated = $request->validate([
            'cancellation_reason' => ['nullable', 'string', 'max:1000'],
        ]);

        if (! $reservation->canBeCancelled()) {
            return response()->json([
                'message' => 'Solo se pueden cancelar reservas pendientes o confirmadas.',
            ], 422);
        }

        DB::transaction(function () use ($reservation, $validated): void {
            $reservation->update([
                'status' => Reservation::STATUS_CANCELLED,
                'cancelled_at' => now(),
                'cancellation_reason' => $validated['cancellation_reason'] ?? null,
                'updated_by' => auth()->id(),
            ]);

            $room = $reservation->room;
            if ($room && ! $this->roomHasOtherActiveReservations($room, $reservation->id)) {
                $room->update(['status' => 'available']);
            }
        });

        return response()->json([
            'message' => 'Reserva cancelada correctamente.',
        ]);
    }

    public function confirm(Reservation $reservation): JsonResponse
    {
        $this->authorize('confirm', $reservation);

        if (! $reservation->canBeConfirmed()) {
            return response()->json([
                'message' => 'Solo se pueden confirmar reservas pendientes.',
            ], 422);
        }

        DB::transaction(function () use ($reservation): void {
            if (! $reservation->hasRequiredDeposit()) {
                abort(422, sprintf(
                    'No se puede confirmar la reserva. Debe tener al menos %d%% de anticipo confirmado (%s). Falta %s.',
                    $reservation->normalizedDepositPercentage(),
                    $this->formatMoney((float) $reservation->deposit_amount_required),
                    $this->formatMoney($reservation->depositAmountPending())
                ));
            }

            $reservation->update([
                'status' => Reservation::STATUS_CONFIRMED,
                'confirmed_at' => now(),
                'updated_by' => auth()->id(),
            ]);

            if ($reservation->room && $reservation->room->status === 'available') {
                $reservation->room->update(['status' => 'reserved']);
            }
        });

        $emailSent = app(ReservationMailService::class)->sendConfirmedEmail($reservation);

        return response()->json([
            'message' => 'Reserva confirmada correctamente.'.$this->mailWarning($emailSent),
        ]);
    }

    public function checkIn(Reservation $reservation): JsonResponse
    {
        $this->authorize('checkIn', $reservation);

        if (! $reservation->canCheckIn()) {
            return response()->json([
                'message' => 'Solo se puede registrar entrada a reservas confirmadas.',
            ], 422);
        }

        DB::transaction(function () use ($reservation): void {
            $reservation->update([
                'status' => Reservation::STATUS_CHECKED_IN,
                'checked_in_at' => now(),
                'updated_by' => auth()->id(),
            ]);

            if ($reservation->room) {
                $reservation->room->update(['status' => 'occupied']);
            }
        });

        return response()->json([
            'message' => 'Entrada registrada correctamente.',
        ]);
    }

    public function checkOut(CheckOutRequest $request, Reservation $reservation): JsonResponse
    {
        $this->authorize('checkOut', $reservation);

        if (! $reservation->canCheckOut()) {
            return response()->json([
                'message' => 'Solo se puede registrar salida a reservas con entrada activa.',
            ], 422);
        }

        $validated = $request->validated();
        $forceCheckout = filter_var($validated['force_checkout'] ?? false, FILTER_VALIDATE_BOOL);

        app(ReservationLedgerService::class)->syncReservationAmounts($reservation);
        $reservation->refresh();

        if ((float) $reservation->balance_amount > 0 && ! $forceCheckout) {
            return response()->json([
                'message' => 'La reserva tiene saldo pendiente. Confirme si desea registrar la salida de todas formas.',
                'requires_force_checkout' => true,
            ], 422);
        }

        DB::transaction(function () use ($reservation): void {
            $reservation->update([
                'status' => Reservation::STATUS_CHECKED_OUT,
                'checked_out_at' => now(),
                'updated_by' => auth()->id(),
            ]);

            if ($reservation->room) {
                $reservation->room->update(['status' => 'available']);
            }
        });

        return response()->json([
            'message' => 'Salida registrada correctamente. La habitacion quedo disponible.',
        ]);
    }

    public function quote(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'room_id' => ['required', 'exists:rooms,id'],
            'check_in' => ['required', 'date'],
            'check_out' => ['required', 'date', 'after:check_in'],
            'promotion_id' => ['nullable', 'exists:promotions,id'],
            'discount_type' => ['nullable', 'in:percentage,fixed'],
            'discount_value' => ['nullable', 'numeric', 'min:0'],
            'base_price' => ['nullable', 'numeric', 'min:0'],
            'base_price_currency' => ['nullable', 'in:BOB,USD'],
        ]);

        if (
            ! auth()->user()->can('reservas.aplicar_descuento')
            && ($request->filled('discount_type') || $request->filled('discount_value'))
        ) {
            return response()->json([
                'message' => 'No tienes permiso para aplicar descuentos manuales.',
            ], 403);
        }

        $room = Room::query()->with('roomType')->findOrFail((int) $validated['room_id']);
        $promotion = ! empty($validated['promotion_id'])
            ? Promotion::query()->with('roomTypes')->findOrFail((int) $validated['promotion_id'])
            : null;
        $quote = $this->buildQuotePayload($room, $validated, $promotion);

        return response()->json($quote);
    }

    public function availableRooms(Request $request): JsonResponse
    {
        $request->validate([
            'check_in' => ['required', 'date'],
            'check_out' => ['required', 'date', 'after:check_in'],
            'adults' => ['required', 'integer', 'min:1', 'max:20'],
            'children' => ['nullable', 'integer', 'min:0', 'max:20'],
        ]);

        $checkIn = Carbon::parse($request->input('check_in'));
        $checkOut = Carbon::parse($request->input('check_out'));
        $guests = (int) $request->input('adults', 0) + (int) $request->input('children', 0);

        $rooms = Room::query()
            ->with('roomType')
            ->where('is_active', true)
            ->where('status', 'available')
            ->whereHas('roomType', fn ($query) => $query->where('max_guests', '>=', $guests))
            ->whereDoesntHave('reservations', function ($query) use ($checkIn, $checkOut): void {
                $query->whereIn('status', Reservation::ACTIVE_STATUSES)
                    ->where('check_in', '<', $checkOut->toDateString())
                    ->where('check_out', '>', $checkIn->toDateString());
            })
            ->orderBy('number')
            ->get()
            ->map(function (Room $room): array {
                return [
                    'id' => $room->id,
                    'number' => $room->number,
                    'room_type_id' => $room->room_type_id,
                    'room_type_name' => $room->roomType?->name ?? '-',
                    'base_price' => (float) ($room->roomType?->base_price ?? 0),
                    'price_bob' => (float) ($room->roomType?->priceBob() ?? $room->roomType?->base_price ?? 0),
                    'price_usd' => (float) ($room->roomType?->priceUsd() ?? 0),
                    'reservation_deposit_percentage' => (int) ($room->roomType?->reservationDepositPercentage() ?? 20),
                    'max_guests' => (int) ($room->roomType?->max_guests ?? 0),
                    'label' => sprintf(
                        'Hab. %s - %s - %s - Max. %d huespedes',
                        $room->number,
                        $room->roomType?->name ?? 'Sin tipo',
                        $this->formatMoney((float) ($room->roomType?->base_price ?? 0)),
                        (int) ($room->roomType?->max_guests ?? 0)
                    ),
                ];
            })
            ->values();

        return response()->json([
            'rooms' => $rooms,
        ]);
    }

    private function buildQuotePayload(Room $room, array $payload, ?Promotion $promotion = null): array
    {
        $checkIn = Carbon::parse($payload['check_in']);
        $checkOut = Carbon::parse($payload['check_out']);
        $nights = max($checkIn->diffInDays($checkOut), 1);
        $basePrice = auth()->user()->can('reservas.cambiar_precio') && array_key_exists('base_price', $payload) && $payload['base_price'] !== null && $payload['base_price'] !== ''
            ? $this->normalizeManualBasePrice($room, (float) $payload['base_price'], $payload['base_price_currency'] ?? 'BOB')
            : round((float) ($room->roomType?->base_price ?? 0), 2);

        $discountType = null;
        $discountValue = 0.0;

        if ($promotion) {
            if (! $promotion->roomTypes()->where('room_types.id', $room->room_type_id)->exists()) {
                throw ValidationException::withMessages([
                    'promotion_id' => 'La promocion no aplica para el tipo de habitacion seleccionado.',
                ]);
            }

            if (! $promotion->isCurrentlyActive()) {
                throw ValidationException::withMessages([
                    'promotion_id' => 'La promocion seleccionada no esta vigente.',
                ]);
            }

            if ($promotion->minimum_nights !== null && $nights < (int) $promotion->minimum_nights) {
                throw ValidationException::withMessages([
                    'promotion_id' => 'La promocion requiere una cantidad minima de noches mayor a la seleccionada.',
                ]);
            }

            $discountType = $promotion->discount_type;
            $discountValue = (float) $promotion->discount_value;
        } elseif (
            auth()->user()->can('reservas.aplicar_descuento')
            && ! empty($payload['discount_type'])
            && isset($payload['discount_value'])
        ) {
            $discountType = $payload['discount_type'];
            $discountValue = round((float) $payload['discount_value'], 2);
        }

        $reservation = new Reservation([
            'check_in' => $checkIn,
            'check_out' => $checkOut,
            'base_price' => $basePrice,
            'discount_type' => $discountType,
            'discount_value' => $discountValue,
            'deposit_percentage' => $room->roomType?->reservationDepositPercentage() ?? 20,
            'paid_amount' => round((float) ($payload['paid_amount'] ?? 0), 2),
        ]);

        $reservation->recalculateTotals();
        $quoteByCurrency = $this->quoteAmountsByCurrency($reservation, $room, $payload);

        return [
            'base_price' => (float) $reservation->base_price,
            'nights' => (int) $reservation->nights,
            'discount_type' => $discountType,
            'discount_value' => (float) $reservation->discount_value,
            'discount_amount' => (float) $reservation->discount_amount,
            'price_per_night' => (float) $reservation->price_per_night,
            'total_amount' => (float) $reservation->total_amount,
            'deposit_percentage' => $reservation->normalizedDepositPercentage(),
            'deposit_amount_required' => (float) $reservation->deposit_amount_required,
            'paid_amount' => (float) $reservation->paid_amount,
            'balance_amount' => (float) $reservation->balance_amount,
            'quote_by_currency' => $quoteByCurrency,
            'label' => sprintf(
                '%d noche(s) | %s base | %s desc. | %s final x noche | Total %s',
                (int) $reservation->nights,
                $this->formatMoney((float) $reservation->base_price),
                $this->formatMoney((float) $reservation->discount_amount),
                $this->formatMoney((float) $reservation->price_per_night),
                $this->formatMoney((float) $reservation->total_amount)
            ),
        ];
    }

    private function quoteAmountsByCurrency(Reservation $reservation, Room $room, array $payload): array
    {
        $hotelSetting = HotelSetting::current();
        $symbols = $hotelSetting->currencySymbols();
        $currencies = [
            'BOB' => [
                'symbol' => $symbols['BOB'] ?? 'Bs.',
                'price_per_night' => (float) $reservation->price_per_night,
                'total_amount' => (float) $reservation->total_amount,
                'deposit_amount_required' => (float) $reservation->deposit_amount_required,
            ],
        ];

        $room->loadMissing('roomType');
        $usdBasePrice = auth()->user()->can('reservas.cambiar_precio')
            && ($payload['base_price_currency'] ?? 'BOB') === 'USD'
            && isset($payload['base_price'])
            && $payload['base_price'] !== ''
                ? round((float) $payload['base_price'], 2)
                : (float) ($room->roomType?->priceUsd() ?? 0);

        if ($usdBasePrice <= 0 || (float) $reservation->base_price <= 0) {
            return $currencies;
        }

        $ratio = (float) $reservation->price_per_night / (float) $reservation->base_price;
        $usdPricePerNight = round($usdBasePrice * $ratio, 2);
        $usdTotal = round($usdPricePerNight * (int) $reservation->nights, 2);

        $currencies['USD'] = [
            'symbol' => $symbols['USD'] ?? '$us',
            'price_per_night' => $usdPricePerNight,
            'total_amount' => $usdTotal,
            'deposit_amount_required' => round($usdTotal * ($reservation->normalizedDepositPercentage() / 100), 2),
        ];

        return $currencies;
    }

    private function roomHasOtherActiveReservations(Room $room, ?int $ignoreReservationId = null): bool
    {
        return $room->reservations()
            ->whereIn('status', Reservation::ACTIVE_STATUSES)
            ->when($ignoreReservationId, fn ($query) => $query->where('id', '!=', $ignoreReservationId))
            ->exists();
    }

    private function syncPromotionUsage(?Promotion $oldPromotion, ?Promotion $newPromotion): void
    {
        if ($oldPromotion?->id === $newPromotion?->id) {
            return;
        }

        if ($oldPromotion && $oldPromotion->used_count > 0) {
            $oldPromotion->decrement('used_count');
        }

        if ($newPromotion) {
            $newPromotion->increment('used_count');
        }
    }

    private function normalizeManualBasePrice(Room $room, float $amount, string $currency): float
    {
        if (strtoupper($currency) !== 'USD') {
            return round($amount, 2);
        }

        $priceBob = (float) ($room->roomType?->priceBob() ?? $room->roomType?->base_price ?? 0);
        $priceUsd = (float) ($room->roomType?->priceUsd() ?? 0);
        $exchangeRate = $priceBob > 0 && $priceUsd > 0
            ? $priceBob / $priceUsd
            : 6.96;

        return round($amount * $exchangeRate, 2);
    }

    private function registerInitialPaymentForReservation(Reservation $reservation, array $validated): bool
    {
        $ledger = app(ReservationLedgerService::class);
        $hotelSetting = HotelSetting::current();
        $currency = $hotelSetting->normalizeCurrency($validated['initial_payment_currency'] ?? $hotelSetting->baseCurrency());
        $amount = round((float) ($validated['initial_payment_amount'] ?? 0), 2);
        $amountBase = $ledger->paymentAmountForReservationBalance($reservation, $amount, $currency);
        $exchangeRate = $currency === 'USD' ? $ledger->reservationUsdToBobRate($reservation) : 1.0;

        if (! $ledger->supportsCurrency($reservation, $currency)) {
            abort(422, 'La reserva no tiene precio configurado para '.$currency.'. Registra el pago en otra moneda o actualiza el tipo de habitacion.');
        }

        if ($amountBase > (float) $reservation->balance_amount && ! auth()->user()->can('pagos.cambiar_monto')) {
            abort(422, 'El pago inicial no puede ser mayor al saldo total de la reserva.');
        }

        if (
            $reservation->status === Reservation::STATUS_PENDING
            && round((float) $reservation->paid_amount + $amountBase, 2) < round((float) $reservation->deposit_amount_required, 2)
        ) {
            abort(422, sprintf(
                'El pago inicial no cubre el anticipo minimo requerido. Debe cubrir al menos %s.',
                $this->formatMoney($reservation->depositAmountPending())
            ));
        }

        $cashRegister = $this->currentUserOpenCashRegister();

        if (! $cashRegister && $this->requiresOpenCashRegisterForIncome()) {
            abort(422, 'Debe abrir caja antes de registrar pagos iniciales.');
        }

        $payment = Payment::create([
            'code' => $this->generatePaymentCode(),
            'reservation_id' => $reservation->id,
            'customer_id' => $reservation->customer_id,
            'amount' => $amount,
            'currency' => $currency,
            'exchange_rate' => $exchangeRate,
            'amount_base' => $amountBase,
            'payment_method' => $validated['initial_payment_method'] ?? 'cash',
            'status' => Payment::STATUS_CONFIRMED,
            'payment_date' => now()->toDateString(),
            'reference_number' => $validated['initial_payment_reference'] ?? null,
            'notes' => $validated['initial_payment_notes'] ?? 'Pago inicial registrado al crear la reserva.',
            'confirmed_at' => now(),
            'created_by' => auth()->id(),
            'confirmed_by' => auth()->id(),
        ]);

        if ($cashRegister) {
            CashMovement::create([
                'cash_register_id' => $cashRegister->id,
                'payment_id' => $payment->id,
                'user_id' => auth()->id(),
                'type' => CashMovement::TYPE_INCOME,
                'concept' => 'Pago inicial de reserva '.$reservation->code,
                'amount' => $payment->amount,
                'currency' => $payment->currency ?? 'BOB',
                'exchange_rate' => $payment->exchange_rate ?? 1,
                'amount_base' => $payment->amount_base ?? 0,
                'payment_method' => $payment->payment_method,
                'movement_date' => now(),
                'notes' => $payment->notes,
                'created_by' => auth()->id(),
            ]);

            $cashRegister->recalculateTotals();
            $cashRegister->save();
        }

        $ledger->syncReservationAmounts($reservation);
        $reservation->refresh();

        if ($reservation->status !== Reservation::STATUS_PENDING || ! $reservation->hasRequiredDeposit()) {
            return false;
        }

        $reservation->update([
            'status' => Reservation::STATUS_CONFIRMED,
            'confirmed_at' => now(),
            'updated_by' => auth()->id(),
        ]);

        if ($reservation->room && $reservation->room->status === 'available') {
            $reservation->room->update(['status' => 'reserved']);
        }

        $reservation->refresh();

        return true;
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

    private function customerSelectLabel(Customer $customer): string
    {
        $documentType = self::DOCUMENT_TYPES[$customer->document_type] ?? 'Otro';
        $details = collect([
            $customer->document_number ? $documentType.' '.$customer->document_number : null,
            $customer->phone ?: $customer->whatsapp,
            $customer->email,
        ])->filter()->implode(' | ');

        return trim($customer->full_name.($details ? ' - '.$details : ''));
    }

    private function reservationDisplayCurrency(Reservation $reservation): string
    {
        $reservation->loadMissing('payments');

        $payment = $reservation->payments
            ->whereIn('status', [Payment::STATUS_PENDING, Payment::STATUS_CONFIRMED])
            ->sortByDesc('id')
            ->first();

        $currency = strtoupper(trim((string) ($payment?->currency ?: 'BOB')));

        if ($currency === 'USD' && app(ReservationLedgerService::class)->supportsCurrency($reservation, 'USD')) {
            return 'USD';
        }

        return 'BOB';
    }

    private function pendingBalancesByCurrency($reservations): array
    {
        return $reservations->reduce(function (array $carry, Reservation $reservation): array {
            $balance = (float) $reservation->balance_amount;

            if ($balance <= 0) {
                return $carry;
            }

            if ($this->reservationDisplayCurrency($reservation) === 'USD') {
                $rate = app(ReservationLedgerService::class)->reservationUsdToBobRate($reservation);
                $carry['USD'] += $rate > 0 ? round($balance / $rate, 2) : 0.0;

                return $carry;
            }

            $carry['BOB'] += $balance;

            return $carry;
        }, ['BOB' => 0.0, 'USD' => 0.0]);
    }

    private function formatMoneyForReservationCurrency(Reservation $reservation, float $amountInBob): string
    {
        $currency = $this->reservationDisplayCurrency($reservation);

        if ($currency !== 'USD') {
            return $this->formatMoney($amountInBob);
        }

        $rate = app(ReservationLedgerService::class)->reservationUsdToBobRate($reservation);
        $amount = $rate > 0 ? round($amountInBob / $rate, 2) : 0.0;

        return '$us '.number_format($amount, 2, '.', '');
    }

    private function formatReservationPaidAmount(Reservation $reservation): string
    {
        $currency = $this->reservationDisplayCurrency($reservation);

        if ($currency !== 'USD') {
            return $this->formatMoney((float) $reservation->paid_amount);
        }

        $reservation->loadMissing('payments');

        $confirmedUsd = (float) $reservation->payments
            ->where('status', Payment::STATUS_CONFIRMED)
            ->filter(fn (Payment $payment): bool => strtoupper(trim((string) $payment->currency)) === 'USD')
            ->sum('amount');

        return '$us '.number_format($confirmedUsd, 2, '.', '');
    }

    private function serializeReservationPayments(Reservation $reservation, bool $canConfirmIncome): array
    {
        return $reservation->payments
            ->sortByDesc('id')
            ->values()
            ->map(function (Payment $payment) use ($reservation, $canConfirmIncome): array {
                $willConfirmReservation = $payment->canBeConfirmed()
                    && $reservation->status === Reservation::STATUS_PENDING
                    && round((float) $reservation->paid_amount + (float) ($payment->amount_base ?? 0), 2) >= round((float) $reservation->deposit_amount_required, 2);

                return [
                    'id' => $payment->id,
                    'code' => $payment->code,
                    'amount' => (float) $payment->amount,
                    'currency' => $payment->currency ?? 'BOB',
                    'amount_formatted' => $this->formatMoneyByCurrency((float) $payment->amount, $payment->currency),
                    'amount_base_formatted' => (float) ($payment->amount_base ?? 0) > 0
                        ? $this->formatMoney((float) $payment->amount_base)
                        : null,
                    'payment_method' => $payment->payment_method,
                    'payment_method_label' => self::PAYMENT_METHODS[$payment->payment_method] ?? ucfirst((string) $payment->payment_method),
                    'status' => $payment->status,
                    'status_label' => self::PAYMENT_STATUSES[$payment->status]['label'] ?? ucfirst((string) $payment->status),
                    'status_badge_class' => self::PAYMENT_STATUSES[$payment->status]['badge'] ?? 'badge text-bg-secondary',
                    'payment_date_formatted' => optional($payment->payment_date)?->format('d/m/Y') ?? '-',
                    'payment_date_raw' => optional($payment->payment_date)?->toDateString(),
                    'created_at_formatted' => optional($payment->created_at)?->format('d/m/Y H:i') ?? '-',
                    'reference_number' => $payment->reference_number,
                    'notes_raw' => $payment->notes,
                    'rejection_reason' => $payment->rejection_reason,
                    'receipt_url' => $payment->receipt_image ? route('adminlte.payments.receipt', $payment) : null,
                    'will_confirm_reservation' => $willConfirmReservation,
                    'can_update' => auth()->user()->can('update', $payment),
                    'can_confirm' => ($canConfirmIncome || ! $this->requiresOpenCashRegisterForPayment($payment))
                        && auth()->user()->can('confirm', $payment)
                        && $payment->canBeConfirmed(),
                    'requires_open_cash_register_to_confirm' => $this->requiresOpenCashRegisterForPayment($payment),
                    'can_reject' => auth()->user()->can('reject', $payment) && $payment->canBeRejected(),
                    'can_cancel' => auth()->user()->can('cancel', $payment) && $payment->canBeCancelled(),
                    'can_refund' => auth()->user()->can('refund', $payment) && $payment->canBeRefunded(),
                    'update_url' => route('adminlte.payments.update', $payment),
                    'confirm_url' => route('adminlte.payments.confirm', $payment),
                    'reject_url' => route('adminlte.payments.reject', $payment),
                    'cancel_url' => route('adminlte.payments.cancel', $payment),
                    'refund_url' => route('adminlte.payments.refund', $payment),
                ];
            })
            ->all();
    }

    private function currentUserOpenCashRegister(): ?CashRegister
    {
        return CashRegister::query()
            ->where('user_id', auth()->id())
            ->where('status', CashRegister::STATUS_OPEN)
            ->first();
    }

    private function requiresOpenCashRegisterForIncome(): bool
    {
        $user = auth()->user();

        return $user !== null
            && $user->can('caja.abrir')
            && ! $user->can('caja.ver_todos');
    }

    private function requiresOpenCashRegisterForPayment(Payment $payment): bool
    {
        if (! $this->requiresOpenCashRegisterForIncome()) {
            return false;
        }

        return in_array($payment->payment_method, ['cash', 'card', 'other'], true);
    }

    private function formatMoney(float $amount): string
    {
        return 'Bs. '.number_format($amount, 2, '.', '');
    }

    private function formatMoneyByCurrency(float $amount, ?string $currency): string
    {
        $currency = strtoupper(trim((string) ($currency ?: 'BOB')));

        if ($currency === 'USD') {
            return '$us '.number_format($amount, 2, '.', '');
        }

        return $this->formatMoney($amount);
    }

    private function mailWarning(bool ...$mailResults): string
    {
        return in_array(false, $mailResults, true)
            ? ' La operacion se guardo, pero no se pudo enviar uno o mas correos. Revisa la configuracion SMTP y el email del cliente.'
            : '';
    }
}
