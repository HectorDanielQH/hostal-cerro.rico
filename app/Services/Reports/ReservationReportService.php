<?php

namespace App\Services\Reports;

use App\Models\Reservation;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class ReservationReportService
{
    public function generate(array $filters): array
    {
        [$dateFrom, $dateTo] = $this->resolveDateRange($filters);

        $query = Reservation::query()
            ->with(['customer', 'room.roomType', 'roomType'])
            ->whereBetween('created_at', [$dateFrom->copy()->startOfDay(), $dateTo->copy()->endOfDay()])
            ->when(
                filled($filters['status'] ?? null),
                fn ($builder) => $builder->where('status', $filters['status'])
            )
            ->when(
                filled($filters['source'] ?? null),
                fn ($builder) => $builder->where('source', $filters['source'])
            )
            ->when(
                filled($filters['room_type_id'] ?? null),
                fn ($builder) => $builder->where('room_type_id', (int) $filters['room_type_id'])
            )
            ->when(
                filled($filters['customer_id'] ?? null),
                fn ($builder) => $builder->where('customer_id', (int) $filters['customer_id'])
            );

        /** @var Collection<int, Reservation> $rows */
        $rows = $query->orderByDesc('created_at')->get();

        return [
            'filters' => [
                'date_from' => $dateFrom->toDateString(),
                'date_to' => $dateTo->toDateString(),
                'status' => $filters['status'] ?? null,
                'source' => $filters['source'] ?? null,
                'room_type_id' => $filters['room_type_id'] ?? null,
                'customer_id' => $filters['customer_id'] ?? null,
            ],
            'summary' => [
                'total_reservations' => $rows->count(),
                'pending' => $rows->where('status', Reservation::STATUS_PENDING)->count(),
                'confirmed' => $rows->where('status', Reservation::STATUS_CONFIRMED)->count(),
                'checked_in' => $rows->where('status', Reservation::STATUS_CHECKED_IN)->count(),
                'checked_out' => $rows->where('status', Reservation::STATUS_CHECKED_OUT)->count(),
                'cancelled' => $rows->where('status', Reservation::STATUS_CANCELLED)->count(),
                'expired' => $rows->where('status', Reservation::STATUS_EXPIRED)->count(),
                'total_amount' => round((float) $rows->sum('total_amount'), 2),
                'paid_amount' => round((float) $rows->sum('paid_amount'), 2),
                'balance_amount' => round((float) $rows->sum('balance_amount'), 2),
            ],
            'rows' => $rows,
        ];
    }

    private function resolveDateRange(array $filters): array
    {
        $dateFrom = filled($filters['date_from'] ?? null)
            ? Carbon::parse($filters['date_from'])
            : now()->startOfMonth();
        $dateTo = filled($filters['date_to'] ?? null)
            ? Carbon::parse($filters['date_to'])
            : now()->endOfMonth();

        return [$dateFrom, $dateTo];
    }
}
