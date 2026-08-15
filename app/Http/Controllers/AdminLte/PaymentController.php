<?php

namespace App\Http\Controllers\AdminLte;

use App\Http\Controllers\Controller;
use App\Http\Requests\AdminLte\StorePaymentRequest;
use App\Http\Requests\AdminLte\UpdatePaymentRequest;
use App\Http\Requests\AdminLte\UpdatePaymentStatusRequest;
use App\Models\CashMovement;
use App\Models\CashRegister;
use App\Models\Customer;
use App\Models\HotelSetting;
use App\Models\Payment;
use App\Models\Reservation;
use App\Services\HotelOperations\ReservationLedgerService;
use App\Services\Mail\PaymentMailService;
use App\Services\Mail\ReservationMailService;
use App\Services\Reservations\ReservationExpirationService;
use App\Support\DatabaseDialect;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Yajra\DataTables\Facades\DataTables;

class PaymentController extends Controller
{
    private const DOCUMENT_TYPES = [
        'ci' => 'CI',
        'passport' => 'Pasaporte',
        'nit' => 'NIT',
        'other' => 'Otro',
    ];

    private const PAYMENT_METHODS = [
        'cash' => 'Efectivo',
        'qr' => 'QR',
        'bank' => 'Deposito / Transferencia',
        'card' => 'Tarjeta',
        'other' => 'Otro',
    ];

    private const STATUSES = [
        Payment::STATUS_PENDING => ['label' => 'Pendiente', 'badge' => 'badge text-bg-warning'],
        Payment::STATUS_CONFIRMED => ['label' => 'Confirmado', 'badge' => 'badge text-bg-success'],
        Payment::STATUS_REJECTED => ['label' => 'Rechazado', 'badge' => 'badge text-bg-danger'],
        Payment::STATUS_CANCELLED => ['label' => 'Anulado', 'badge' => 'badge text-bg-secondary'],
        Payment::STATUS_REFUNDED => ['label' => 'Devuelto', 'badge' => 'badge text-bg-info'],
    ];

    private const RESERVATION_STATUSES = [
        Reservation::STATUS_PENDING => 'Pendiente',
        Reservation::STATUS_CONFIRMED => 'Confirmada',
        Reservation::STATUS_CHECKED_IN => 'Ocupada',
        Reservation::STATUS_CHECKED_OUT => 'Salida registrada',
        Reservation::STATUS_CANCELLED => 'Cancelada',
        Reservation::STATUS_EXPIRED => 'Expirada',
        Reservation::STATUS_NO_SHOW => 'No show',
    ];

    public function index(): View
    {
        $this->authorize('viewAny', Payment::class);
        app(ReservationExpirationService::class)->expirePendingReservations();

        $hotelSetting = HotelSetting::current();
        $baseCurrency = $hotelSetting->baseCurrency();
        $requiresOpenCashRegister = $this->requiresOpenCashRegisterForIncome();
        $hasOpenCashRegister = $this->currentUserOpenCashRegister() !== null;

        $reservations = Reservation::query()
            ->with('customer')
            ->whereIn('status', [
                Reservation::STATUS_PENDING,
                Reservation::STATUS_CONFIRMED,
                Reservation::STATUS_CHECKED_IN,
            ])
            ->orderByDesc('created_at')
            ->get();

        if (auth()->user()->can('pagos.ver_propios') && ! auth()->user()->can('pagos.ver')) {
            $reservations = $reservations->filter(
                fn (Reservation $reservation): bool => $reservation->customer?->user_id === auth()->id()
            )->values();
        }

        $payments = Payment::query()
            ->with('customer')
            ->when(auth()->user()->can('pagos.ver_propios') && ! auth()->user()->can('pagos.ver'), function ($query): void {
                $query->whereHas('customer', fn ($customerQuery) => $customerQuery->where('user_id', auth()->id()));
            })
            ->get();

        return view('adminlte.payments.index', [
            'baseCurrency' => $baseCurrency,
            'reservations' => $reservations,
            'supportedCurrencies' => $hotelSetting->supportedCurrencies(),
            'currencySymbols' => $hotelSetting->currencySymbols(),
            'paymentMethods' => self::PAYMENT_METHODS,
            'statuses' => self::STATUSES,
            'requiresOpenCashRegister' => $requiresOpenCashRegister,
            'hasOpenCashRegister' => $hasOpenCashRegister,
            'paymentStats' => [
                'confirmed_applied' => $this->formatMoney((float) $payments
                    ->where('status', Payment::STATUS_CONFIRMED)
                    ->sum('amount_base'), $baseCurrency),
                'pending_applied' => $this->formatMoney((float) $payments
                    ->where('status', Payment::STATUS_PENDING)
                    ->sum('amount_base'), $baseCurrency),
                'pending_count' => $payments->where('status', Payment::STATUS_PENDING)->count(),
                'usd_registered' => $this->formatMoney((float) $payments
                    ->where('currency', 'USD')
                    ->whereIn('status', [Payment::STATUS_PENDING, Payment::STATUS_CONFIRMED])
                    ->sum('amount'), 'USD'),
                'rejected_count' => $payments->where('status', Payment::STATUS_REJECTED)->count(),
            ],
        ]);
    }

    public function data(): JsonResponse
    {
        $this->authorize('viewAny', Payment::class);
        app(ReservationExpirationService::class)->expirePendingReservations();
        $canConfirmIncome = ! $this->requiresOpenCashRegisterForIncome() || $this->currentUserOpenCashRegister() !== null;

        $query = Payment::query()
            ->with(['reservation.customer', 'reservation.roomType', 'reservation.room.roomType', 'customer', 'createdBy', 'confirmedBy'])
            ->select('payments.*');

        if (auth()->user()->can('pagos.ver_propios') && ! auth()->user()->can('pagos.ver')) {
            $query->whereHas('customer', fn ($customerQuery) => $customerQuery->where('user_id', auth()->id()));
        }

        return DataTables::eloquent($query)
            ->addColumn('reservation_code', fn (Payment $payment): string => $payment->reservation?->code ?? '-')
            ->addColumn('reservation_status', fn (Payment $payment): string => $payment->reservation?->status ?? '-')
            ->addColumn('reservation_status_label', fn (Payment $payment): string => self::RESERVATION_STATUSES[$payment->reservation?->status] ?? ucfirst((string) $payment->reservation?->status))
            ->addColumn('reservation_source_label', fn (Payment $payment): string => $this->reservationSourceLabel($payment->reservation?->source))
            ->addColumn('reservation_requested_payment_label', fn (Payment $payment): string => Reservation::PREFERRED_PAYMENT_METHODS[$payment->reservation?->preferred_payment_method] ?? 'Sin definir')
            ->addColumn('reservation_special_requests', fn (Payment $payment): string => trim((string) ($payment->reservation?->special_requests ?: '')))
            ->addColumn('reservation_check_in_formatted', fn (Payment $payment): string => optional($payment->reservation?->check_in)?->format('d/m/Y') ?? '-')
            ->addColumn('reservation_check_out_formatted', fn (Payment $payment): string => optional($payment->reservation?->check_out)?->format('d/m/Y') ?? '-')
            ->addColumn('reservation_total_raw', fn (Payment $payment): float => (float) ($payment->reservation?->total_amount ?? 0))
            ->addColumn('reservation_paid_raw', fn (Payment $payment): float => (float) ($payment->reservation?->paid_amount ?? 0))
            ->addColumn('reservation_balance_raw', fn (Payment $payment): float => (float) ($payment->reservation?->balance_amount ?? 0))
            ->addColumn('reservation_supports_usd', fn (Payment $payment): bool => $payment->reservation
                && app(ReservationLedgerService::class)->supportsCurrency($payment->reservation, 'USD'))
            ->addColumn('reservation_display_currency', fn (Payment $payment): string => $payment->reservation ? app(ReservationLedgerService::class)->displayCurrency($payment->reservation) : 'BOB')
            ->addColumn('reservation_locked_payment_currency', fn (Payment $payment): ?string => $payment->reservation ? app(ReservationLedgerService::class)->lockedPaymentCurrency($payment->reservation, $payment) : null)
            ->addColumn('reservation_total_usd_raw', fn (Payment $payment): float => $this->reservationAmountInUsd($payment->reservation, (float) ($payment->reservation?->total_amount ?? 0)))
            ->addColumn('reservation_paid_usd_raw', fn (Payment $payment): float => $payment->reservation ? $this->reservationConfirmedUsd($payment->reservation) : 0.0)
            ->addColumn('reservation_balance_usd_raw', fn (Payment $payment): float => $this->reservationAmountInUsd($payment->reservation, (float) ($payment->reservation?->balance_amount ?? 0)))
            ->addColumn('reservation_deposit_required_formatted', fn (Payment $payment): string => $this->formatMoney((float) ($payment->reservation?->deposit_amount_required ?? 0)))
            ->addColumn('reservation_deposit_pending_formatted', fn (Payment $payment): string => $this->formatMoney($payment->reservation?->depositAmountPending() ?? 0))
            ->addColumn('reservation_deposit_required_payment_currency_formatted', fn (Payment $payment): string => $this->formatReservationMoneyForPayment($payment, (float) ($payment->reservation?->deposit_amount_required ?? 0)))
            ->addColumn('reservation_deposit_pending_payment_currency_formatted', fn (Payment $payment): string => $this->formatReservationMoneyForPayment($payment, $payment->reservation?->depositAmountPending() ?? 0))
            ->addColumn('customer_name', fn (Payment $payment): string => $payment->customer?->full_name ?? '-')
            ->addColumn('customer_document', fn (Payment $payment): string => $payment->customer?->document_number ?? '')
            ->addColumn('customer_document_type_label', fn (Payment $payment): string => self::DOCUMENT_TYPES[$payment->customer?->document_type] ?? 'Documento')
            ->addColumn('amount_formatted', fn (Payment $payment): string => $this->formatMoney((float) $payment->amount, $payment->currency))
            ->addColumn('amount_base_formatted', fn (Payment $payment): ?string => (float) ($payment->amount_base ?? 0) > 0 ? $this->formatMoney((float) $payment->amount_base) : null)
            ->addColumn('payment_method_label', fn (Payment $payment): string => self::PAYMENT_METHODS[$payment->payment_method] ?? ucfirst($payment->payment_method))
            ->addColumn('will_confirm_reservation', fn (Payment $payment): bool => $payment->canBeConfirmed()
                && $payment->reservation?->status === Reservation::STATUS_PENDING
                && round((float) $payment->reservation->paid_amount + (float) ($payment->amount_base ?? 0), 2) >= round((float) $payment->reservation->deposit_amount_required, 2))
            ->addColumn('status_label', fn (Payment $payment): string => self::STATUSES[$payment->status]['label'] ?? ucfirst($payment->status))
            ->addColumn('status_badge_class', fn (Payment $payment): string => self::STATUSES[$payment->status]['badge'] ?? 'badge text-bg-secondary')
            ->addColumn('payment_date_formatted', fn (Payment $payment): string => optional($payment->payment_date)?->format('d/m/Y') ?? '-')
            ->addColumn('receipt_url', fn (Payment $payment): ?string => $payment->receipt_image ? route('adminlte.payments.receipt', $payment) : null)
            ->addColumn('created_by_name', fn (Payment $payment): ?string => $payment->createdBy?->name)
            ->addColumn('confirmed_by_name', fn (Payment $payment): ?string => $payment->confirmedBy?->name)
            ->addColumn('confirmed_at_formatted', fn (Payment $payment): string => optional($payment->confirmed_at)?->format('d/m/Y H:i') ?? '-')
            ->addColumn('created_at_formatted', fn (Payment $payment): string => optional($payment->created_at)?->format('d/m/Y H:i') ?? '-')
            ->addColumn('rejection_reason', fn (Payment $payment): ?string => $payment->rejection_reason)
            ->addColumn('notes_raw', fn (Payment $payment): ?string => $payment->notes)
            ->addColumn('reference_number', fn (Payment $payment): ?string => $payment->reference_number)
            ->addColumn('payment_date_raw', fn (Payment $payment): ?string => optional($payment->payment_date)?->toDateString())
            ->addColumn('payment_method_raw', fn (Payment $payment): string => $payment->payment_method)
            ->addColumn('currency_raw', fn (Payment $payment): string => $payment->currency ?? 'BOB')
            ->addColumn('amount_base_raw', fn (Payment $payment): float => (float) ($payment->amount_base ?? $payment->amount))
            ->addColumn('amount_raw', fn (Payment $payment): float => (float) $payment->amount)
            ->addColumn('affects_balance', fn (Payment $payment): bool => (float) ($payment->amount_base ?? 0) > 0)
            ->addColumn('reservation_total_formatted', fn (Payment $payment): string => $this->formatMoney((float) ($payment->reservation?->total_amount ?? 0)))
            ->addColumn('reservation_paid_formatted', fn (Payment $payment): string => $this->formatMoney((float) ($payment->reservation?->paid_amount ?? 0)))
            ->addColumn('reservation_balance_formatted', fn (Payment $payment): string => $this->formatMoney((float) ($payment->reservation?->balance_amount ?? 0)))
            ->addColumn('reservation_total_payment_currency_formatted', fn (Payment $payment): string => $this->formatReservationMoneyForPayment($payment, (float) ($payment->reservation?->total_amount ?? 0)))
            ->addColumn('reservation_paid_payment_currency_formatted', fn (Payment $payment): string => $this->formatReservationPaidForPayment($payment))
            ->addColumn('reservation_balance_payment_currency_formatted', fn (Payment $payment): string => $this->formatReservationMoneyForPayment($payment, (float) ($payment->reservation?->balance_amount ?? 0)))
            ->addColumn('can_update', fn (Payment $payment): bool => auth()->user()->can('update', $payment))
            ->addColumn('can_confirm', fn (Payment $payment): bool => ($canConfirmIncome || ! $this->requiresOpenCashRegisterForPayment($payment))
                && auth()->user()->can('confirm', $payment)
                && $payment->canBeConfirmed()
            )
            ->addColumn('requires_open_cash_register_to_confirm', fn (Payment $payment): bool => $this->requiresOpenCashRegisterForPayment($payment))
            ->addColumn('can_reject', fn (Payment $payment): bool => auth()->user()->can('reject', $payment) && $payment->canBeRejected())
            ->addColumn('can_cancel', fn (Payment $payment): bool => auth()->user()->can('cancel', $payment) && $payment->canBeCancelled())
            ->addColumn('can_refund', fn (Payment $payment): bool => auth()->user()->can('refund', $payment) && $payment->canBeRefunded())
            ->addColumn('can_view_receipt', fn (Payment $payment): bool => $payment->receipt_image !== null && auth()->user()->can('view', $payment))
            ->addColumn('update_url', fn (Payment $payment): string => route('adminlte.payments.update', $payment))
            ->addColumn('confirm_url', fn (Payment $payment): string => route('adminlte.payments.confirm', $payment))
            ->addColumn('reject_url', fn (Payment $payment): string => route('adminlte.payments.reject', $payment))
            ->addColumn('cancel_url', fn (Payment $payment): string => route('adminlte.payments.cancel', $payment))
            ->addColumn('refund_url', fn (Payment $payment): string => route('adminlte.payments.refund', $payment))
            ->toJson();
    }

    public function reservationSearch(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Payment::class);

        $term = trim((string) $request->query('term', ''));
        $page = max((int) $request->query('page', 1), 1);
        $perPage = 12;

        $reservations = Reservation::query()
            ->with('customer')
            ->whereIn('status', [
                Reservation::STATUS_PENDING,
                Reservation::STATUS_CONFIRMED,
                Reservation::STATUS_CHECKED_IN,
            ])
            ->when(auth()->user()->can('pagos.ver_propios') && ! auth()->user()->can('pagos.ver'), function ($query): void {
                $query->whereHas('customer', fn ($customerQuery) => $customerQuery->where('user_id', auth()->id()));
            })
            ->when($term !== '', function ($query) use ($term): void {
                $query->where(function ($searchQuery) use ($term): void {
                    $searchQuery
                        ->where('code', DatabaseDialect::caseInsensitiveLikeOperator(), "%{$term}%")
                        ->orWhereHas('customer', function ($customerQuery) use ($term): void {
                            DatabaseDialect::whereAnyLike($customerQuery, [
                                'full_name',
                                'document_number',
                                'phone',
                                'whatsapp',
                                'email',
                            ], $term);
                        });
                });
            })
            ->orderByDesc('created_at')
            ->with(['customer', 'roomType', 'room.roomType', 'payments'])
            ->paginate($perPage, ['id', 'code', 'customer_id', 'room_id', 'room_type_id', 'status', 'total_amount', 'paid_amount', 'balance_amount', 'created_at'], 'page', $page);

        return response()->json([
            'results' => $reservations->getCollection()
                ->map(fn (Reservation $reservation): array => $this->reservationSelectPayload($reservation))
                ->values(),
            'pagination' => [
                'more' => $reservations->hasMorePages(),
            ],
        ]);
    }

    public function store(StorePaymentRequest $request): JsonResponse
    {
        $this->authorize('create', Payment::class);
        app(ReservationExpirationService::class)->expirePendingReservations();

        $validated = $request->validated();
        $payment = null;
        $reservationConfirmed = false;

        DB::transaction(function () use ($validated, $request, &$payment, &$reservationConfirmed): void {
            $ledger = app(ReservationLedgerService::class);
            $hotelSetting = HotelSetting::current();
            $reservation = Reservation::query()->with('customer')->findOrFail($validated['reservation_id']);
            $currency = $hotelSetting->normalizeCurrency($validated['currency']);
            $ledger->ensurePaymentCurrencyMatchesReservation($reservation, $currency);
            $amountBase = $ledger->paymentAmountForReservationBalance($reservation, (float) $validated['amount'], $currency);
            $exchangeRate = $currency === 'USD' ? $ledger->reservationUsdToBobRate($reservation) : 1.0;

            if (! $ledger->supportsCurrency($reservation, $currency)) {
                abort(422, 'La reserva seleccionada no tiene precio configurado para '.$currency.'. Registra el pago en bolivianos o actualiza el tipo de habitacion.');
            }

            if (in_array($reservation->status, [Reservation::STATUS_CANCELLED, Reservation::STATUS_EXPIRED], true)) {
                abort(422, 'No se pueden registrar pagos para una reserva cancelada o expirada.');
            }

            if (
                $reservation->status === Reservation::STATUS_CHECKED_OUT
                && (float) $reservation->balance_amount <= 0
            ) {
                abort(422, 'La reserva seleccionada ya fue pagada completamente.');
            }

            if (
                $amountBase > (float) $reservation->balance_amount
                && ! auth()->user()->can('pagos.cambiar_monto')
            ) {
                abort(422, 'El monto no puede ser mayor al saldo pendiente.');
            }

            $payment = Payment::create([
                'code' => $this->generatePaymentCode(),
                'reservation_id' => $reservation->id,
                'customer_id' => $reservation->customer_id,
                'amount' => round((float) $validated['amount'], 2),
                'currency' => $currency,
                'exchange_rate' => $exchangeRate,
                'amount_base' => $amountBase,
                'payment_method' => $validated['payment_method'],
                'status' => Payment::STATUS_PENDING,
                'payment_date' => $validated['payment_date'] ?? null,
                'reference_number' => $validated['reference_number'] ?? null,
                'receipt_image' => $this->storeReceiptImage($request),
                'notes' => $validated['notes'] ?? null,
                'created_by' => auth()->id(),
            ]);

            $reservationConfirmed = $this->confirmPaymentAndSyncReservation($payment);
        });

        if ($payment) {
            $payment->refresh();
            $paymentEmailSent = app(PaymentMailService::class)->sendStatusEmail($payment, Payment::STATUS_CONFIRMED);

            if ($reservationConfirmed) {
                $reservationEmailSent = app(ReservationMailService::class)->sendConfirmedEmail($payment->reservation()->firstOrFail());
            }
        }

        $mailWarning = $this->mailWarning($paymentEmailSent ?? true, $reservationEmailSent ?? true);

        return response()->json([
            'message' => ($reservationConfirmed
                ? 'Pago registrado y confirmado correctamente. La reserva tambien fue aprobada porque cubre el anticipo requerido.'
                : 'Pago registrado y confirmado correctamente. La reserva seguira pendiente hasta completar el anticipo requerido.').$mailWarning,
        ]);
    }

    public function update(UpdatePaymentRequest $request, Payment $payment): JsonResponse
    {
        $this->authorize('update', $payment);

        $validated = $request->validated();

        DB::transaction(function () use ($payment, $validated, $request): void {
            if (! $payment->canBeUpdated()) {
                abort(422, 'Solo se pueden editar pagos pendientes, rechazados o confirmados.');
            }

            $ledger = app(ReservationLedgerService::class);
            $hotelSetting = HotelSetting::current();
            $reservation = Reservation::query()->findOrFail($payment->reservation_id);
            $currency = $hotelSetting->normalizeCurrency($validated['currency']);
            $ledger->ensurePaymentCurrencyMatchesReservation($reservation, $currency, $payment);
            $amountBase = $ledger->paymentAmountForReservationBalance($reservation, (float) $validated['amount'], $currency);
            $exchangeRate = $currency === 'USD' ? $ledger->reservationUsdToBobRate($reservation) : 1.0;

            if (! $ledger->supportsCurrency($reservation, $currency)) {
                abort(422, 'La reserva seleccionada no tiene precio configurado para '.$currency.'. Registra el pago en bolivianos o actualiza el tipo de habitacion.');
            }

            $currentConfirmedBase = $payment->status === Payment::STATUS_CONFIRMED
                ? (float) ($payment->amount_base ?? 0)
                : 0.0;
            $availableBalance = round((float) $reservation->balance_amount + $currentConfirmedBase, 2);

            if (
                $amountBase > $availableBalance
                && ! auth()->user()->can('pagos.cambiar_monto')
            ) {
                abort(422, 'El monto no puede ser mayor al saldo pendiente.');
            }

            if ($payment->status === Payment::STATUS_CONFIRMED) {
                $this->ensureReservationPaymentFloor($reservation, $payment, $amountBase);
            }

            $payment->update([
                'amount' => round((float) $validated['amount'], 2),
                'currency' => $currency,
                'exchange_rate' => $exchangeRate,
                'amount_base' => $amountBase,
                'payment_method' => $validated['payment_method'],
                'payment_date' => $validated['payment_date'] ?? null,
                'reference_number' => $validated['reference_number'] ?? null,
                'receipt_image' => $this->storeReceiptImage($request, $payment->receipt_image),
                'notes' => $validated['notes'] ?? null,
            ]);

            if ($payment->status === Payment::STATUS_CONFIRMED) {
                $this->syncPaymentCashMovements($payment);
                $ledger->syncReservationAmounts($reservation);
            }
        });

        return response()->json([
            'message' => 'Pago actualizado correctamente.',
        ]);
    }

    public function confirm(Payment $payment): JsonResponse
    {
        $this->authorize('confirm', $payment);
        app(ReservationExpirationService::class)->expirePendingReservations();
        $payment->refresh();

        if (! $payment->canBeConfirmed()) {
            return response()->json([
                'message' => 'Solo se pueden confirmar pagos pendientes u observados.',
            ], 422);
        }

        $reservationConfirmed = false;

        DB::transaction(function () use ($payment, &$reservationConfirmed): void {
            $reservationConfirmed = $this->confirmPaymentAndSyncReservation($payment);
        });

        $payment->refresh();
        $paymentEmailSent = app(PaymentMailService::class)->sendStatusEmail($payment, Payment::STATUS_CONFIRMED);

        if ($reservationConfirmed) {
            $reservationEmailSent = app(ReservationMailService::class)->sendConfirmedEmail($payment->reservation()->firstOrFail());
        }

        $mailWarning = $this->mailWarning($paymentEmailSent, $reservationEmailSent ?? true);

        return response()->json([
            'message' => ($reservationConfirmed
                ? 'Pago confirmado correctamente. La reserva tambien fue aprobada porque cubre el anticipo requerido.'
                : 'Pago confirmado correctamente. La reserva seguira pendiente hasta completar el anticipo requerido.').$mailWarning,
        ]);
    }

    public function reject(UpdatePaymentStatusRequest $request, Payment $payment): JsonResponse
    {
        $this->authorize('reject', $payment);

        if (! $payment->canBeRejected()) {
            return response()->json([
                'message' => 'Solo se pueden rechazar pagos pendientes.',
            ], 422);
        }

        $validated = $request->validated();

        DB::transaction(function () use ($payment, $validated): void {
            $payment->update([
                'status' => Payment::STATUS_REJECTED,
                'rejected_at' => now(),
                'rejected_by' => auth()->id(),
                'rejection_reason' => $validated['reason'] ?? null,
            ]);

            app(ReservationLedgerService::class)->syncReservationAmounts($payment->reservation()->firstOrFail());
        });

        $emailSent = app(PaymentMailService::class)->sendStatusEmail($payment, Payment::STATUS_REJECTED);

        return response()->json([
            'message' => 'Pago rechazado correctamente.'.$this->mailWarning($emailSent),
        ]);
    }

    public function cancel(UpdatePaymentStatusRequest $request, Payment $payment): JsonResponse
    {
        $this->authorize('cancel', $payment);

        if (! $payment->canBeCancelled()) {
            return response()->json([
                'message' => 'Solo se pueden anular pagos pendientes o confirmados.',
            ], 422);
        }

        $validated = $request->validated();

        DB::transaction(function () use ($payment, $validated): void {
            if ($payment->status === Payment::STATUS_CONFIRMED) {
                $this->ensureReservationPaymentFloor($payment->reservation()->firstOrFail(), $payment, 0.0);
            }

            $payment->update([
                'status' => Payment::STATUS_CANCELLED,
                'cancelled_at' => now(),
                'cancelled_by' => auth()->id(),
                'cancellation_reason' => $validated['reason'] ?? null,
            ]);

            app(ReservationLedgerService::class)->syncReservationAmounts($payment->reservation()->firstOrFail());
        });

        $emailSent = app(PaymentMailService::class)->sendStatusEmail($payment, Payment::STATUS_CANCELLED);

        return response()->json([
            'message' => 'Pago anulado correctamente.'.$this->mailWarning($emailSent),
        ]);
    }

    public function refund(UpdatePaymentStatusRequest $request, Payment $payment): JsonResponse
    {
        $this->authorize('refund', $payment);

        if (! $payment->canBeRefunded()) {
            return response()->json([
                'message' => 'Solo se pueden devolver pagos confirmados.',
            ], 422);
        }

        $validated = $request->validated();

        DB::transaction(function () use ($payment, $validated): void {
            $this->ensureReservationPaymentFloor($payment->reservation()->firstOrFail(), $payment, 0.0);

            $payment->update([
                'status' => Payment::STATUS_REFUNDED,
                'refunded_at' => now(),
                'refunded_by' => auth()->id(),
                'refund_reason' => $validated['reason'] ?? null,
            ]);

            app(ReservationLedgerService::class)->syncReservationAmounts($payment->reservation()->firstOrFail());
        });

        $emailSent = app(PaymentMailService::class)->sendStatusEmail($payment, Payment::STATUS_REFUNDED);

        return response()->json([
            'message' => 'Pago marcado como devuelto correctamente.'.$this->mailWarning($emailSent),
        ]);
    }

    public function showReceipt(Payment $payment): RedirectResponse
    {
        $this->authorize('view', $payment);

        if (! $payment->receipt_image || ! Storage::disk('public')->exists($payment->receipt_image)) {
            abort(404);
        }

        return redirect(asset('storage/'.$payment->receipt_image));
    }

    private function storeReceiptImage(Request $request, ?string $oldPath = null): ?string
    {
        if (! $request->hasFile('receipt_image')) {
            return $oldPath;
        }

        $file = $request->file('receipt_image');
        $extension = strtolower((string) $file->getClientOriginalExtension());
        $filename = Str::uuid()->toString().'-'.time().'.'.$extension;

        $newPath = $file->storeAs('payments/receipts', $filename, 'public');

        if ($oldPath && Storage::disk('public')->exists($oldPath)) {
            Storage::disk('public')->delete($oldPath);
        }

        return $newPath;
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

    private function confirmPaymentAndSyncReservation(Payment $payment): bool
    {
        $cashRegister = $this->currentUserOpenCashRegister();

        if (! $cashRegister && $this->requiresOpenCashRegisterForPayment($payment)) {
            abort(422, 'Debe abrir caja antes de registrar o confirmar ingresos.');
        }

        $reservation = $payment->reservation()->firstOrFail();

        if ($reservation->status === Reservation::STATUS_CANCELLED) {
            abort(422, 'No se puede confirmar un pago de una reserva cancelada.');
        }

        if ($reservation->status === Reservation::STATUS_EXPIRED) {
            $reservation->update([
                'status' => Reservation::STATUS_PENDING,
                'expired_at' => null,
                'cancellation_reason' => null,
                'updated_by' => auth()->id(),
            ]);
        }

        $payment->update([
            'status' => Payment::STATUS_CONFIRMED,
            'confirmed_at' => now(),
            'confirmed_by' => auth()->id(),
            'rejected_at' => null,
            'rejected_by' => null,
            'rejection_reason' => null,
        ]);

        $cashMovementExists = CashMovement::query()
            ->where('payment_id', $payment->id)
            ->exists();

        if ($cashRegister && ! $cashMovementExists) {
            CashMovement::create([
                'cash_register_id' => $cashRegister->id,
                'payment_id' => $payment->id,
                'user_id' => auth()->id(),
                'type' => CashMovement::TYPE_INCOME,
                'concept' => 'Pago de reserva '.$payment->reservation?->code,
                'amount' => $payment->amount,
                'currency' => $payment->currency ?? 'BOB',
                'exchange_rate' => $payment->exchange_rate ?? 1,
                'amount_base' => $payment->amount_base ?? 0,
                'payment_method' => $payment->payment_method,
                'movement_date' => now(),
                'notes' => $payment->notes,
                'created_by' => auth()->id(),
            ]);
        }

        if ($cashRegister) {
            $cashRegister->recalculateTotals();
            $cashRegister->save();
        }

        $reservation = $payment->reservation()->with('room')->firstOrFail();
        app(ReservationLedgerService::class)->syncReservationAmounts($reservation);
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

        return true;
    }

    private function ensureReservationPaymentFloor(Reservation $reservation, Payment $payment, float $replacementAmountBase): void
    {
        if (! in_array($reservation->status, [
            Reservation::STATUS_CONFIRMED,
            Reservation::STATUS_CHECKED_IN,
            Reservation::STATUS_CHECKED_OUT,
        ], true)) {
            return;
        }

        $currentConfirmedBase = $payment->status === Payment::STATUS_CONFIRMED
            ? (float) ($payment->amount_base ?? 0)
            : 0.0;
        $paidAfterChange = round((float) $reservation->paid_amount - $currentConfirmedBase + $replacementAmountBase, 2);

        if (
            in_array($reservation->status, [Reservation::STATUS_CONFIRMED, Reservation::STATUS_CHECKED_IN], true)
            && $paidAfterChange < round((float) $reservation->deposit_amount_required, 2)
        ) {
            abort(422, sprintf(
                'No se puede dejar la reserva por debajo del anticipo minimo requerido. Requerido: %s.',
                $this->formatMoney((float) $reservation->deposit_amount_required)
            ));
        }

        if (
            $reservation->status === Reservation::STATUS_CHECKED_OUT
            && $paidAfterChange < round((float) $reservation->total_amount, 2)
        ) {
            abort(422, sprintf(
                'No se puede dejar una reserva con salida registrada y saldo pendiente. Total requerido: %s.',
                $this->formatMoney((float) $reservation->total_amount)
            ));
        }
    }

    private function syncPaymentCashMovements(Payment $payment): void
    {
        $payment->loadMissing('reservation');
        $movements = $payment->cashMovements()->get();

        if ($movements->isEmpty()) {
            $cashRegister = $this->currentUserOpenCashRegister();

            if (! $cashRegister && $this->requiresOpenCashRegisterForIncome()) {
                abort(422, 'Debe abrir caja antes de editar un pago confirmado sin movimiento de caja asociado.');
            }

            if ($cashRegister) {
                CashMovement::create([
                    'cash_register_id' => $cashRegister->id,
                    'payment_id' => $payment->id,
                    'user_id' => auth()->id(),
                    'type' => CashMovement::TYPE_INCOME,
                    'concept' => 'Pago de reserva '.$payment->reservation?->code,
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

            return;
        }

        $movements->each(function (CashMovement $movement) use ($payment): void {
            $movement->update([
                'amount' => $payment->amount,
                'currency' => $payment->currency ?? 'BOB',
                'exchange_rate' => $payment->exchange_rate ?? 1,
                'amount_base' => $payment->amount_base ?? 0,
                'payment_method' => $payment->payment_method,
                'notes' => $payment->notes,
            ]);

            $movement->cashRegister?->recalculateTotals();
            $movement->cashRegister?->save();
        });
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

    private function mailWarning(bool ...$mailResults): string
    {
        return in_array(false, $mailResults, true)
            ? ' La operacion se guardo, pero no se pudo enviar uno o mas correos. Revisa la configuracion SMTP y el email del cliente.'
            : '';
    }

    private function formatMoney(float $amount, ?string $currency = null): string
    {
        return HotelSetting::current()->formatMoney($amount, $currency);
    }

    private function formatReservationMoneyForPayment(Payment $payment, float $amountInBob): string
    {
        $currency = strtoupper(trim((string) ($payment->currency ?: 'BOB')));

        if ($currency !== 'USD' || ! $payment->reservation) {
            return $this->formatMoney($amountInBob);
        }

        $rate = app(ReservationLedgerService::class)->reservationUsdToBobRate($payment->reservation);
        $amount = $rate > 0 ? round($amountInBob / $rate, 2) : 0.0;

        return $this->formatMoney($amount, 'USD');
    }

    private function formatReservationPaidForPayment(Payment $payment): string
    {
        $currency = strtoupper(trim((string) ($payment->currency ?: 'BOB')));

        if ($currency !== 'USD' || ! $payment->reservation) {
            return $this->formatMoney((float) ($payment->reservation?->paid_amount ?? 0));
        }

        $payment->reservation->loadMissing('payments');

        $confirmedUsd = (float) $payment->reservation->payments
            ->where('status', Payment::STATUS_CONFIRMED)
            ->filter(fn (Payment $reservationPayment): bool => strtoupper(trim((string) $reservationPayment->currency)) === 'USD')
            ->sum('amount');

        return $this->formatMoney($confirmedUsd, 'USD');
    }

    private function reservationAmountInUsd(?Reservation $reservation, float $amountInBob): float
    {
        if (! $reservation) {
            return 0.0;
        }

        $rate = app(ReservationLedgerService::class)->reservationUsdToBobRate($reservation);

        return $rate > 0 ? round($amountInBob / $rate, 2) : 0.0;
    }

    private function reservationConfirmedUsd(Reservation $reservation): float
    {
        $reservation->loadMissing('payments');

        return (float) $reservation->payments
            ->where('status', Payment::STATUS_CONFIRMED)
            ->filter(fn (Payment $payment): bool => strtoupper(trim((string) $payment->currency)) === 'USD')
            ->sum('amount');
    }

    private function reservationSelectPayload(Reservation $reservation): array
    {
        $customer = $reservation->customer;
        $documentType = self::DOCUMENT_TYPES[$customer?->document_type] ?? 'Documento';
        $documentLabel = $customer?->document_number ? $documentType.' '.$customer->document_number : '';
        $customerLabel = trim(($customer?->full_name ?? 'Sin cliente').($documentLabel ? ' - '.$documentLabel : ''));
        $ledger = app(ReservationLedgerService::class);
        $usdRate = $ledger->reservationUsdToBobRate($reservation);
        $displayCurrency = $ledger->displayCurrency($reservation);
        $totalUsd = $usdRate > 0 ? round((float) $reservation->total_amount / $usdRate, 2) : 0.0;
        $balanceUsd = $usdRate > 0 ? round((float) $reservation->balance_amount / $usdRate, 2) : 0.0;
        $paidUsd = $this->reservationConfirmedUsd($reservation);

        return [
            'id' => $reservation->id,
            'text' => $reservation->code.' - '.$customerLabel,
            'code' => $reservation->code,
            'customer_name' => $customer?->full_name ?? 'Sin cliente',
            'customer_document_type' => $documentType,
            'customer_document' => $customer?->document_number,
            'customer_phone' => $customer?->phone ?: $customer?->whatsapp,
            'customer_email' => $customer?->email,
            'status' => $reservation->status,
            'total_amount' => (float) $reservation->total_amount,
            'paid_amount' => (float) $reservation->paid_amount,
            'balance_amount' => (float) $reservation->balance_amount,
            'supports_usd' => $usdRate > 0,
            'display_currency' => $displayCurrency,
            'locked_payment_currency' => $ledger->lockedPaymentCurrency($reservation),
            'total_amount_usd' => $totalUsd,
            'paid_amount_usd' => $paidUsd,
            'balance_amount_usd' => $balanceUsd,
        ];
    }

    private function reservationSourceLabel(?string $source): string
    {
        return match ($source) {
            'website' => 'Solicitud web',
            'reception' => 'Recepcion',
            'phone' => 'Telefono',
            'whatsapp' => 'WhatsApp',
            'agency' => 'Agencia',
            'other' => 'Otro',
            default => 'Sin origen',
        };
    }
}
