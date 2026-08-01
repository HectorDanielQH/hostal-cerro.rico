<?php

namespace App\Http\Controllers\AdminLte;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use App\Models\Reservation;
use App\Services\Reservations\ReservationExpirationService;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class DashboardController extends Controller
{
    public function index(): View
    {
        app(ReservationExpirationService::class)->expirePendingReservations();

        $today = Carbon::today();
        $tomorrow = Carbon::tomorrow();
        $monthStart = $today->copy()->startOfMonth();

        return view('adminlte.dashboard.index', [
            'stats' => [
                'arrivals_today' => $this->countReservationsForDate('check_in', $today, [Reservation::STATUS_CONFIRMED]),
                'departures_today' => $this->countReservationsForDate('check_out', $today, [Reservation::STATUS_CHECKED_IN]),
                'occupied_rooms' => $this->countRoomsByStatus('occupied'),
                'available_rooms' => $this->countRoomsByStatus('available'),
                'active_rooms' => $this->countActiveRooms(),
                'pending_reservations' => $this->countReservationsByStatus(Reservation::STATUS_PENDING),
                'pending_payments' => $this->countPaymentsByStatus(Payment::STATUS_PENDING),
                'open_cash_registers' => $this->countWhere('cash_registers', 'status', 'open'),
                'today_revenue' => $this->sumConfirmedPayments($today, $tomorrow),
                'month_revenue' => $this->sumConfirmedPayments($monthStart, $tomorrow),
                'pending_balance' => $this->sumReservations('balance_amount', Reservation::ACTIVE_STATUSES),
            ],
            'roomStatuses' => $this->roomStatuses(),
            'reservationStatuses' => $this->reservationStatuses(),
            'recentReservations' => $this->recentReservations(),
            'pendingPayments' => $this->pendingPayments(),
            'cashRegister' => $this->currentCashRegister(),
            'assignedWorkShift' => $this->assignedWorkShift(),
        ]);
    }

    protected function countActiveRooms(): int
    {
        if (! $this->hasTable('rooms')) {
            return 0;
        }

        $query = DB::table('rooms');

        if ($this->hasColumn('rooms', 'is_active')) {
            $query->where('is_active', true);
        }

        if ($this->hasColumn('rooms', 'deleted_at')) {
            $query->whereNull('deleted_at');
        }

        return (int) $query->count();
    }

    protected function countRoomsByStatus(string $status): int
    {
        if (! $this->hasTable('rooms') || ! $this->hasColumn('rooms', 'status')) {
            return 0;
        }

        $query = DB::table('rooms')->where('status', $status);

        if ($this->hasColumn('rooms', 'is_active')) {
            $query->where('is_active', true);
        }

        if ($this->hasColumn('rooms', 'deleted_at')) {
            $query->whereNull('deleted_at');
        }

        return (int) $query->count();
    }

    /**
     * @param  array<int, string>  $statuses
     */
    protected function countReservationsForDate(string $column, Carbon $date, array $statuses): int
    {
        if (! $this->hasTable('reservations') || ! $this->hasColumn('reservations', $column)) {
            return 0;
        }

        $query = DB::table('reservations')->whereDate($column, $date);

        if ($this->hasColumn('reservations', 'status')) {
            $query->whereIn('status', $statuses);
        }

        if ($this->hasColumn('reservations', 'deleted_at')) {
            $query->whereNull('deleted_at');
        }

        return (int) $query->count();
    }

    protected function countReservationsByStatus(string $status): int
    {
        return $this->countWhere('reservations', 'status', $status, true);
    }

    protected function countPaymentsByStatus(string $status): int
    {
        return $this->countWhere('payments', 'status', $status, true);
    }

    /**
     * @param  mixed  $value
     */
    protected function countWhere(string $table, string $column, $value, bool $ignoreDeleted = false): int
    {
        if (! $this->hasTable($table) || ! $this->hasColumn($table, $column)) {
            return 0;
        }

        $query = DB::table($table)->where($column, $value);

        if ($ignoreDeleted && $this->hasColumn($table, 'deleted_at')) {
            $query->whereNull('deleted_at');
        }

        return (int) $query->count();
    }

    protected function sumConfirmedPayments(Carbon $from, Carbon $to): float
    {
        if (! $this->hasTable('payments')) {
            return 0;
        }

        $amountColumn = $this->hasColumn('payments', 'amount_base') ? 'amount_base' : 'amount';

        if (! $this->hasColumn('payments', $amountColumn)) {
            return 0;
        }

        $query = DB::table('payments')->where('status', Payment::STATUS_CONFIRMED);

        if ($this->hasColumn('payments', 'payment_date')) {
            $query->whereDate('payment_date', '>=', $from)->whereDate('payment_date', '<', $to);
        } else {
            $query->where('created_at', '>=', $from)->where('created_at', '<', $to);
        }

        if ($this->hasColumn('payments', 'deleted_at')) {
            $query->whereNull('deleted_at');
        }

        return (float) $query->sum($amountColumn);
    }

    /**
     * @param  array<int, string>  $statuses
     */
    protected function sumReservations(string $column, array $statuses): float
    {
        if (! $this->hasTable('reservations') || ! $this->hasColumn('reservations', $column)) {
            return 0;
        }

        $query = DB::table('reservations');

        if ($this->hasColumn('reservations', 'status')) {
            $query->whereIn('status', $statuses);
        }

        if ($this->hasColumn('reservations', 'deleted_at')) {
            $query->whereNull('deleted_at');
        }

        return (float) $query->sum($column);
    }

    /**
     * @return array<string, int>
     */
    protected function roomStatuses(): array
    {
        if (! $this->hasTable('rooms') || ! $this->hasColumn('rooms', 'status')) {
            return [];
        }

        $query = DB::table('rooms')
            ->select('status', DB::raw('count(*) as total'))
            ->groupBy('status');

        if ($this->hasColumn('rooms', 'deleted_at')) {
            $query->whereNull('deleted_at');
        }

        return $query->pluck('total', 'status')->map(fn ($total) => (int) $total)->all();
    }

    /**
     * @return array<string, int>
     */
    protected function reservationStatuses(): array
    {
        if (! $this->hasTable('reservations') || ! $this->hasColumn('reservations', 'status')) {
            return [];
        }

        $query = DB::table('reservations')
            ->select('status', DB::raw('count(*) as total'))
            ->groupBy('status');

        if ($this->hasColumn('reservations', 'deleted_at')) {
            $query->whereNull('deleted_at');
        }

        return $query->pluck('total', 'status')->map(fn ($total) => (int) $total)->all();
    }

    /**
     * @return array<int, object>
     */
    protected function recentReservations(): array
    {
        if (! $this->hasTable('reservations')) {
            return [];
        }

        $query = DB::table('reservations')
            ->leftJoin('customers', 'customers.id', '=', 'reservations.customer_id')
            ->leftJoin('rooms', 'rooms.id', '=', 'reservations.room_id')
            ->select([
                'reservations.code',
                'reservations.status',
                'reservations.check_in',
                'reservations.check_out',
                'reservations.total_amount',
                'reservations.balance_amount',
                'customers.full_name as customer_name',
                'rooms.number as room_number',
            ])
            ->orderByDesc('reservations.created_at')
            ->limit(6);

        if ($this->hasColumn('reservations', 'deleted_at')) {
            $query->whereNull('reservations.deleted_at');
        }

        return $query->get()->all();
    }

    /**
     * @return array<int, object>
     */
    protected function pendingPayments(): array
    {
        if (! $this->hasTable('payments')) {
            return [];
        }

        $amountColumn = $this->hasColumn('payments', 'amount_base') ? 'payments.amount_base' : 'payments.amount';

        $query = DB::table('payments')
            ->leftJoin('reservations', 'reservations.id', '=', 'payments.reservation_id')
            ->leftJoin('customers', 'customers.id', '=', 'payments.customer_id')
            ->where('payments.status', Payment::STATUS_PENDING)
            ->select([
                'payments.code',
                'payments.payment_method',
                'payments.created_at',
                DB::raw($amountColumn.' as amount_base'),
                'reservations.code as reservation_code',
                'customers.full_name as customer_name',
            ])
            ->orderByDesc('payments.created_at')
            ->limit(5);

        if ($this->hasColumn('payments', 'deleted_at')) {
            $query->whereNull('payments.deleted_at');
        }

        return $query->get()->all();
    }

    protected function currentCashRegister(): ?object
    {
        if (! $this->hasTable('cash_registers')) {
            return null;
        }

        $query = DB::table('cash_registers')
            ->leftJoin('users', 'users.id', '=', 'cash_registers.user_id')
            ->where('cash_registers.status', 'open')
            ->where('cash_registers.user_id', auth()->id())
            ->select([
                'cash_registers.code',
                'cash_registers.opened_at',
                'cash_registers.opening_amount',
                'cash_registers.expected_amount',
                'cash_registers.shift_name',
                'users.name as opened_by_name',
            ])
            ->orderByDesc('cash_registers.opened_at');

        return $query->first();
    }

    protected function assignedWorkShift(): ?object
    {
        if (
            ! auth()->check()
            || ! $this->hasTable('users')
            || ! $this->hasTable('work_shifts')
            || ! $this->hasColumn('users', 'work_shift_id')
        ) {
            return null;
        }

        return DB::table('users')
            ->leftJoin('work_shifts', 'work_shifts.id', '=', 'users.work_shift_id')
            ->where('users.id', auth()->id())
            ->whereNotNull('users.work_shift_id')
            ->whereNotNull('work_shifts.id')
            ->whereNull('work_shifts.deleted_at')
            ->select([
                'work_shifts.name',
                'work_shifts.starts_at',
                'work_shifts.ends_at',
                'work_shifts.is_active',
            ])
            ->first();
    }

    protected function hasTable(string $table): bool
    {
        return Schema::hasTable($table);
    }

    protected function hasColumn(string $table, string $column): bool
    {
        return Schema::hasTable($table) && Schema::hasColumn($table, $column);
    }
}
