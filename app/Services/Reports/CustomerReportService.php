<?php

namespace App\Services\Reports;

use App\Models\Customer;
use App\Models\Payment;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class CustomerReportService
{
    public function generate(array $filters): array
    {
        [$dateFrom, $dateTo] = $this->resolveDateRange($filters);
        $nationality = $filters['nationality'] ?? null;
        $isCompany = $filters['is_company'] ?? null;
        $isActive = $filters['is_active'] ?? null;

        /** @var Collection<int, Customer> $rows */
        $rows = Customer::query()
            ->with([
                'reservations' => fn ($query) => $query->whereBetween('created_at', [$dateFrom->copy()->startOfDay(), $dateTo->copy()->endOfDay()]),
                'payments' => fn ($query) => $query
                    ->where('status', Payment::STATUS_CONFIRMED)
                    ->whereBetween('created_at', [$dateFrom->copy()->startOfDay(), $dateTo->copy()->endOfDay()]),
            ])
            ->when(
                filled($nationality),
                fn ($builder) => $builder->where('nationality', 'like', '%'.$nationality.'%')
            )
            ->when(
                $isCompany !== null && $isCompany !== '',
                fn ($builder) => $builder->where('is_company', (bool) $isCompany)
            )
            ->when(
                $isActive !== null && $isActive !== '',
                fn ($builder) => $builder->where('is_active', (bool) $isActive)
            )
            ->orderBy('full_name')
            ->get();

        return [
            'filters' => [
                'date_from' => $dateFrom->toDateString(),
                'date_to' => $dateTo->toDateString(),
                'nationality' => $nationality,
                'is_company' => $isCompany,
                'is_active' => $isActive,
            ],
            'summary' => [
                'total_customers' => $rows->count(),
                'active_customers' => $rows->where('is_active', true)->count(),
                'foreign_customers' => $rows->where('is_foreign', true)->count(),
                'companies' => $rows->where('is_company', true)->count(),
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
