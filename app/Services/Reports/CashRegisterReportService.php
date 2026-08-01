<?php

namespace App\Services\Reports;

use App\Models\CashRegister;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class CashRegisterReportService
{
    public function generate(array $filters): array
    {
        [$dateFrom, $dateTo] = $this->resolveDateRange($filters);

        /** @var Collection<int, CashRegister> $rows */
        $rows = CashRegister::query()
            ->with(['user', 'closedBy'])
            ->whereBetween('opened_at', [$dateFrom->copy()->startOfDay(), $dateTo->copy()->endOfDay()])
            ->when(
                filled($filters['user_id'] ?? null),
                fn ($builder) => $builder->where('user_id', (int) $filters['user_id'])
            )
            ->when(
                filled($filters['status'] ?? null),
                fn ($builder) => $builder->where('status', $filters['status'])
            )
            ->orderByDesc('opened_at')
            ->get();

        return [
            'filters' => [
                'date_from' => $dateFrom->toDateString(),
                'date_to' => $dateTo->toDateString(),
                'user_id' => $filters['user_id'] ?? null,
                'status' => $filters['status'] ?? null,
            ],
            'summary' => [
                'total_opening' => round((float) $rows->sum('opening_amount'), 2),
                'total_income' => round((float) $rows->sum('total_income'), 2),
                'total_expense' => round((float) $rows->sum('total_expense'), 2),
                'total_adjustment' => round((float) $rows->sum('total_adjustment'), 2),
                'total_expected' => round((float) $rows->sum('expected_amount'), 2),
                'total_counted' => round((float) $rows->sum('counted_amount'), 2),
                'total_difference' => round((float) $rows->sum('difference_amount'), 2),
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
