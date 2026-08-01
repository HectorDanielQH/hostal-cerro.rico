<?php

namespace App\Http\Controllers\AdminLte;

use App\Http\Controllers\Controller;
use App\Http\Requests\AdminLte\CloseCashRegisterRequest;
use App\Http\Requests\AdminLte\OpenCashRegisterRequest;
use App\Http\Requests\AdminLte\StoreCashMovementRequest;
use App\Models\CashMovement;
use App\Models\CashRegister;
use App\Models\HotelSetting;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\Facades\DataTables;

class CashRegisterController extends Controller
{
    private const MOVEMENT_TYPES = [
        CashMovement::TYPE_INCOME => 'Ingreso',
        CashMovement::TYPE_EXPENSE => 'Egreso',
        CashMovement::TYPE_ADJUSTMENT => 'Ajuste',
    ];

    private const PAYMENT_METHODS = [
        'cash' => 'Efectivo',
        'qr' => 'QR',
        'bank' => 'Deposito / Transferencia',
        'card' => 'Tarjeta',
        'other' => 'Otro',
    ];

    private const STATUSES = [
        CashRegister::STATUS_OPEN => ['label' => 'Abierta', 'badge' => 'badge text-bg-success'],
        CashRegister::STATUS_CLOSED => ['label' => 'Cerrada', 'badge' => 'badge text-bg-secondary'],
        CashRegister::STATUS_CANCELLED => ['label' => 'Anulada', 'badge' => 'badge text-bg-danger'],
    ];

    public function index(): View
    {
        $this->authorize('viewAny', CashRegister::class);

        $assignedWorkShift = auth()->user()
            ?->loadMissing('workShift')
            ->workShift;

        $currentCashRegister = CashRegister::query()
            ->with('user')
            ->where('user_id', auth()->id())
            ->where('status', CashRegister::STATUS_OPEN)
            ->latest('id')
            ->first();

        if ($currentCashRegister) {
            $currentCashRegister->recalculateTotals();
            $currentCashRegister->save();
        }

        $hotelSetting = HotelSetting::current();

        return view('adminlte.cash-registers.index', [
            'baseCurrency' => $hotelSetting->baseCurrency(),
            'currentCashRegister' => $currentCashRegister,
            'assignedWorkShift' => $assignedWorkShift,
            'movementTypes' => self::MOVEMENT_TYPES,
            'paymentMethods' => self::PAYMENT_METHODS,
            'statuses' => self::STATUSES,
            'supportedCurrencies' => $hotelSetting->supportedCurrencies(),
            'currencySymbols' => $hotelSetting->currencySymbols(),
        ]);
    }

    public function data(): JsonResponse
    {
        $this->authorize('viewAny', CashRegister::class);

        $query = CashRegister::query()
            ->with(['user', 'createdBy', 'closedBy'])
            ->select('cash_registers.*');

        if (! auth()->user()->can('caja.ver_todos')) {
            $query->where('user_id', auth()->id());
        }

        return DataTables::eloquent($query)
            ->addColumn('user_name', fn (CashRegister $cashRegister): string => $cashRegister->user?->name ?? '-')
            ->addColumn('opened_at_formatted', fn (CashRegister $cashRegister): string => optional($cashRegister->opened_at)?->format('d/m/Y H:i') ?? '-')
            ->addColumn('closed_at_formatted', fn (CashRegister $cashRegister): string => optional($cashRegister->closed_at)?->format('d/m/Y H:i') ?? '-')
            ->addColumn('opening_amount_formatted', fn (CashRegister $cashRegister): string => $this->formatMoney((float) $cashRegister->opening_amount))
            ->addColumn('total_income_formatted', fn (CashRegister $cashRegister): string => $this->formatMoney((float) $cashRegister->total_income))
            ->addColumn('total_expense_formatted', fn (CashRegister $cashRegister): string => $this->formatMoney((float) $cashRegister->total_expense))
            ->addColumn('total_adjustment_formatted', fn (CashRegister $cashRegister): string => $this->formatMoney((float) $cashRegister->total_adjustment))
            ->addColumn('expected_amount_formatted', fn (CashRegister $cashRegister): string => $this->formatMoney((float) $cashRegister->expected_amount))
            ->addColumn('counted_amount_formatted', fn (CashRegister $cashRegister): string => $cashRegister->counted_amount !== null ? $this->formatMoney((float) $cashRegister->counted_amount) : '-')
            ->addColumn('difference_amount_formatted', fn (CashRegister $cashRegister): string => $this->formatMoney((float) $cashRegister->difference_amount))
            ->addColumn('status_label', fn (CashRegister $cashRegister): string => self::STATUSES[$cashRegister->status]['label'] ?? ucfirst($cashRegister->status))
            ->addColumn('status_badge_class', fn (CashRegister $cashRegister): string => self::STATUSES[$cashRegister->status]['badge'] ?? 'badge text-bg-secondary')
            ->addColumn('can_close', fn (CashRegister $cashRegister): bool => auth()->user()->can('close', $cashRegister) && $cashRegister->canBeClosed())
            ->addColumn('can_arqueo', fn (CashRegister $cashRegister): bool => auth()->user()->can('arqueo', $cashRegister))
            ->addColumn('close_url', fn (CashRegister $cashRegister): string => route('adminlte.cash-registers.close', $cashRegister))
            ->addColumn('movements_url', fn (CashRegister $cashRegister): string => route('adminlte.cash-registers.movements', $cashRegister))
            ->addColumn('arqueo_url', fn (CashRegister $cashRegister): string => route('adminlte.cash-registers.arqueo', $cashRegister))
            ->toJson();
    }

    public function movementsData(CashRegister $cashRegister): JsonResponse
    {
        $this->authorize('view', $cashRegister);

        $query = $cashRegister->movements()
            ->with(['createdBy', 'payment.reservation.customer'])
            ->select('cash_movements.*');

        return DataTables::eloquent($query)
            ->addColumn('type_label', fn (CashMovement $movement): string => self::MOVEMENT_TYPES[$movement->type] ?? ucfirst($movement->type))
            ->addColumn('amount_formatted', fn (CashMovement $movement): string => $this->formatMoney((float) $movement->amount, $movement->currency))
            ->addColumn('amount_base_formatted', fn (CashMovement $movement): ?string => (float) ($movement->amount_base ?? 0) > 0 ? $this->formatMoney((float) $movement->amount_base) : null)
            ->addColumn('payment_method_label', fn (CashMovement $movement): string => $movement->payment_method ? (self::PAYMENT_METHODS[$movement->payment_method] ?? ucfirst($movement->payment_method)) : '-')
            ->addColumn('currency_label', fn (CashMovement $movement): string => $movement->currency ?? 'BOB')
            ->addColumn('movement_date_formatted', fn (CashMovement $movement): string => optional($movement->movement_date)?->format('d/m/Y H:i') ?? '-')
            ->addColumn('created_by_name', fn (CashMovement $movement): ?string => $movement->createdBy?->name)
            ->addColumn('affects_base', fn (CashMovement $movement): bool => (float) ($movement->amount_base ?? 0) > 0)
            ->addColumn('payment_code', fn (CashMovement $movement): ?string => $movement->payment?->code)
            ->addColumn('reservation_code', fn (CashMovement $movement): ?string => $movement->payment?->reservation?->code)
            ->addColumn('customer_name', fn (CashMovement $movement): ?string => $movement->payment?->reservation?->customer?->full_name)
            ->toJson();
    }

    public function open(OpenCashRegisterRequest $request): JsonResponse
    {
        $this->authorize('open', CashRegister::class);

        $validated = $request->validated();

        DB::transaction(function () use ($validated): void {
            $alreadyOpen = CashRegister::query()
                ->where('user_id', auth()->id())
                ->where('status', CashRegister::STATUS_OPEN)
                ->exists();

            if ($alreadyOpen) {
                abort(422, 'Ya tienes una caja abierta y no puedes abrir otra.');
            }

            $assignedWorkShift = auth()->user()
                ?->loadMissing('workShift')
                ->workShift;

            $shiftName = $assignedWorkShift
                ? $assignedWorkShift->name.' ('.$assignedWorkShift->scheduleLabel().')'
                : ($validated['shift_name'] ?? null);

            CashRegister::create([
                'code' => $this->generateCashRegisterCode(),
                'user_id' => auth()->id(),
                'opened_at' => now(),
                'opening_amount' => round((float) $validated['opening_amount'], 2),
                'expected_amount' => round((float) $validated['opening_amount'], 2),
                'status' => CashRegister::STATUS_OPEN,
                'shift_name' => $shiftName,
                'opening_notes' => $validated['opening_notes'] ?? null,
                'created_by' => auth()->id(),
            ]);
        });

        return response()->json([
            'message' => 'Caja abierta correctamente.',
        ]);
    }

    public function close(CloseCashRegisterRequest $request, CashRegister $cashRegister): JsonResponse
    {
        $this->authorize('close', $cashRegister);

        if (! $cashRegister->canBeClosed()) {
            return response()->json([
                'message' => 'Solo se pueden cerrar cajas abiertas.',
            ], 422);
        }

        $validated = $request->validated();

        DB::transaction(function () use ($cashRegister, $validated): void {
            $cashRegister->counted_amount = round((float) $validated['counted_amount'], 2);
            $cashRegister->recalculateTotals();
            $cashRegister->closed_at = now();
            $cashRegister->closed_by = auth()->id();
            $cashRegister->closing_notes = $validated['closing_notes'] ?? null;
            $cashRegister->status = CashRegister::STATUS_CLOSED;
            $cashRegister->save();
        });

        return response()->json([
            'message' => 'Caja cerrada correctamente.',
        ]);
    }

    public function storeMovement(StoreCashMovementRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $cashRegister = CashRegister::query()->findOrFail($validated['cash_register_id']);
        $this->authorize('adjust', $cashRegister);

        if (! $cashRegister->isOpen()) {
            return response()->json([
                'message' => 'Solo se pueden registrar movimientos en cajas abiertas.',
            ], 422);
        }

        DB::transaction(function () use ($validated, $cashRegister): void {
            $hotelSetting = HotelSetting::current();
            $currency = $hotelSetting->normalizeCurrency($validated['currency']);
            $amountBase = $hotelSetting->amountForBaseLedger((float) $validated['amount'], $currency);

            CashMovement::create([
                'cash_register_id' => $cashRegister->id,
                'payment_id' => null,
                'user_id' => $cashRegister->user_id,
                'type' => $validated['type'],
                'concept' => $validated['concept'],
                'amount' => round((float) $validated['amount'], 2),
                'currency' => $currency,
                'exchange_rate' => 1,
                'amount_base' => $amountBase,
                'payment_method' => $validated['payment_method'] ?? null,
                'movement_date' => now(),
                'notes' => $validated['notes'] ?? null,
                'created_by' => auth()->id(),
            ]);

            $cashRegister->recalculateTotals();
            $cashRegister->save();
        });

        return response()->json([
            'message' => 'Movimiento registrado correctamente.',
        ]);
    }

    public function current(): JsonResponse
    {
        $this->authorize('viewAny', CashRegister::class);

        $cashRegister = CashRegister::query()
            ->with('user')
            ->where('user_id', auth()->id())
            ->where('status', CashRegister::STATUS_OPEN)
            ->latest('id')
            ->first();

        if (! $cashRegister) {
            return response()->json(null);
        }

        $cashRegister->recalculateTotals();
        $cashRegister->save();

        return response()->json($this->serializeCashRegister($cashRegister));
    }

    public function arqueo(CashRegister $cashRegister): JsonResponse
    {
        $this->authorize('arqueo', $cashRegister);

        $cashRegister->recalculateTotals();
        $cashRegister->save();

        $movementsGrouped = $cashRegister->movements()
            ->selectRaw('type, COALESCE(payment_method, ?) as payment_method, SUM(amount) as total_amount, COUNT(*) as total_movements', ['without_method'])
            ->selectRaw('COALESCE(currency, ?) as currency', ['BOB'])
            ->selectRaw('SUM(amount_base) as total_amount_base')
            ->groupBy('type', 'payment_method', 'currency')
            ->get()
            ->map(fn ($row) => [
                'type' => $row->type,
                'type_label' => self::MOVEMENT_TYPES[$row->type] ?? ucfirst($row->type),
                'payment_method' => $row->payment_method,
                'currency' => $row->currency,
                'payment_method_label' => $row->payment_method === 'without_method'
                    ? 'Sin metodo'
                    : (self::PAYMENT_METHODS[$row->payment_method] ?? ucfirst((string) $row->payment_method)),
                'total_amount' => (float) $row->total_amount,
                'total_amount_formatted' => $this->formatMoney((float) $row->total_amount, $row->currency),
                'total_amount_base_formatted' => $this->formatMoney((float) $row->total_amount_base),
                'total_movements' => (int) $row->total_movements,
            ])
            ->values();

        return response()->json([
            'opening_amount' => (float) $cashRegister->opening_amount,
            'total_income' => (float) $cashRegister->total_income,
            'total_expense' => (float) $cashRegister->total_expense,
            'total_adjustment' => (float) $cashRegister->total_adjustment,
            'expected_amount' => (float) $cashRegister->expected_amount,
            'counted_amount' => $cashRegister->counted_amount !== null ? (float) $cashRegister->counted_amount : null,
            'difference_amount' => (float) $cashRegister->difference_amount,
            'movements' => $movementsGrouped,
        ]);
    }

    private function serializeCashRegister(CashRegister $cashRegister): array
    {
        return [
            'id' => $cashRegister->id,
            'code' => $cashRegister->code,
            'user_name' => $cashRegister->user?->name ?? '-',
            'shift_name' => $cashRegister->shift_name,
            'opened_at' => optional($cashRegister->opened_at)?->toDateTimeString(),
            'opened_at_formatted' => optional($cashRegister->opened_at)?->format('d/m/Y H:i') ?? '-',
            'opening_amount' => (float) $cashRegister->opening_amount,
            'opening_amount_formatted' => $this->formatMoney((float) $cashRegister->opening_amount),
            'total_income' => (float) $cashRegister->total_income,
            'total_income_formatted' => $this->formatMoney((float) $cashRegister->total_income),
            'total_expense' => (float) $cashRegister->total_expense,
            'total_expense_formatted' => $this->formatMoney((float) $cashRegister->total_expense),
            'total_adjustment' => (float) $cashRegister->total_adjustment,
            'total_adjustment_formatted' => $this->formatMoney((float) $cashRegister->total_adjustment),
            'expected_amount' => (float) $cashRegister->expected_amount,
            'expected_amount_formatted' => $this->formatMoney((float) $cashRegister->expected_amount),
            'counted_amount' => $cashRegister->counted_amount !== null ? (float) $cashRegister->counted_amount : null,
            'counted_amount_formatted' => $cashRegister->counted_amount !== null ? $this->formatMoney((float) $cashRegister->counted_amount) : '-',
            'difference_amount' => (float) $cashRegister->difference_amount,
            'difference_amount_formatted' => $this->formatMoney((float) $cashRegister->difference_amount),
            'status' => $cashRegister->status,
        ];
    }

    private function generateCashRegisterCode(): string
    {
        $prefix = 'CASH-'.now()->format('Ymd').'-';
        $latestCode = CashRegister::withTrashed()
            ->where('code', 'like', $prefix.'%')
            ->latest('id')
            ->value('code');

        $lastNumber = 0;
        if ($latestCode && preg_match('/(\d{4})$/', $latestCode, $matches) === 1) {
            $lastNumber = (int) $matches[1];
        }

        return $prefix.str_pad((string) ($lastNumber + 1), 4, '0', STR_PAD_LEFT);
    }

    private function formatMoney(float $amount, ?string $currency = null): string
    {
        return HotelSetting::current()->formatMoney($amount, $currency);
    }
}
