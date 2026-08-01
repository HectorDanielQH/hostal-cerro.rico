<?php

namespace App\Services\Reports;

use App\Models\Payment;
use App\Models\Reservation;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class IncomeReportService
{
    public function generate(array $filters): array
    {
        [$dateFrom, $dateTo] = $this->resolveDateRange($filters);

        $confirmedPayments = Payment::query()
            ->with(['reservation.roomType'])
            ->where('status', Payment::STATUS_CONFIRMED)
            ->whereBetween('payment_date', [$dateFrom->toDateString(), $dateTo->toDateString()])
            ->when(
                filled($filters['payment_method'] ?? null),
                fn ($builder) => $builder->where('payment_method', $filters['payment_method'])
            )
            ->when(
                filled($filters['room_type_id'] ?? null),
                fn ($builder) => $builder->whereHas('reservation', fn ($reservationQuery) => $reservationQuery->where('room_type_id', (int) $filters['room_type_id']))
            )
            ->orderBy('payment_date')
            ->get();

        $reservations = Reservation::query()
            ->whereBetween('created_at', [$dateFrom->copy()->startOfDay(), $dateTo->copy()->endOfDay()])
            ->when(
                filled($filters['room_type_id'] ?? null),
                fn ($builder) => $builder->where('room_type_id', (int) $filters['room_type_id'])
            )
            ->get();

        $byPaymentMethod = $confirmedPayments
            ->groupBy('payment_method')
            ->map(fn (Collection $payments, string $method) => [
                'payment_method' => $method,
                'amount' => round((float) $payments->sum('amount_base'), 2),
            ])
            ->values();

        $byRoomType = $confirmedPayments
            ->groupBy(fn (Payment $payment) => $payment->reservation?->roomType?->name ?? 'Sin tipo')
            ->map(fn (Collection $payments, string $roomTypeName) => [
                'room_type_name' => $roomTypeName,
                'amount' => round((float) $payments->sum('amount_base'), 2),
            ])
            ->values();

        $byDay = $confirmedPayments
            ->groupBy(fn (Payment $payment) => optional($payment->payment_date)?->format('Y-m-d') ?? 'Sin fecha')
            ->map(fn (Collection $payments, string $date) => [
                'date' => $date,
                'amount' => round((float) $payments->sum('amount_base'), 2),
            ])
            ->values();

        return [
            'filters' => [
                'date_from' => $dateFrom->toDateString(),
                'date_to' => $dateTo->toDateString(),
                'payment_method' => $filters['payment_method'] ?? null,
                'room_type_id' => $filters['room_type_id'] ?? null,
            ],
            'summary' => [
                'total_reservations' => $reservations->count(),
                'total_confirmed_income' => round((float) $confirmedPayments->sum('amount_base'), 2),
                'pending_balance' => round((float) $reservations->sum('balance_amount'), 2),
                'cash' => round((float) $confirmedPayments->where('payment_method', 'cash')->sum('amount_base'), 2),
                'qr' => round((float) $confirmedPayments->where('payment_method', 'qr')->sum('amount_base'), 2),
                'bank' => round((float) $confirmedPayments->where('payment_method', 'bank')->sum('amount_base'), 2),
                'card' => round((float) $confirmedPayments->where('payment_method', 'card')->sum('amount_base'), 2),
                'other' => round((float) $confirmedPayments->where('payment_method', 'other')->sum('amount_base'), 2),
            ],
            'by_payment_method' => $byPaymentMethod,
            'by_room_type' => $byRoomType,
            'by_day' => $byDay,
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
