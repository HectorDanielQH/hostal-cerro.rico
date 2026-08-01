<?php

namespace App\Services\Reports;

use App\Models\Payment;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class PaymentReportService
{
    public function generate(array $filters): array
    {
        [$dateFrom, $dateTo] = $this->resolveDateRange($filters);

        /** @var Collection<int, Payment> $rows */
        $rows = Payment::query()
            ->with(['reservation.customer', 'customer', 'confirmedBy'])
            ->whereBetween('created_at', [$dateFrom->copy()->startOfDay(), $dateTo->copy()->endOfDay()])
            ->when(
                filled($filters['status'] ?? null),
                fn ($builder) => $builder->where('status', $filters['status'])
            )
            ->when(
                filled($filters['payment_method'] ?? null),
                fn ($builder) => $builder->where('payment_method', $filters['payment_method'])
            )
            ->when(
                filled($filters['customer_id'] ?? null),
                fn ($builder) => $builder->where('customer_id', (int) $filters['customer_id'])
            )
            ->orderByDesc('created_at')
            ->get();

        return [
            'filters' => [
                'date_from' => $dateFrom->toDateString(),
                'date_to' => $dateTo->toDateString(),
                'status' => $filters['status'] ?? null,
                'payment_method' => $filters['payment_method'] ?? null,
                'customer_id' => $filters['customer_id'] ?? null,
            ],
            'summary' => [
                'total_payments' => $rows->count(),
                'pending_amount' => round((float) $rows->where('status', Payment::STATUS_PENDING)->sum('amount_base'), 2),
                'confirmed_amount' => round((float) $rows->where('status', Payment::STATUS_CONFIRMED)->sum('amount_base'), 2),
                'rejected_amount' => round((float) $rows->where('status', Payment::STATUS_REJECTED)->sum('amount_base'), 2),
                'cancelled_amount' => round((float) $rows->where('status', Payment::STATUS_CANCELLED)->sum('amount_base'), 2),
                'refunded_amount' => round((float) $rows->where('status', Payment::STATUS_REFUNDED)->sum('amount_base'), 2),
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
