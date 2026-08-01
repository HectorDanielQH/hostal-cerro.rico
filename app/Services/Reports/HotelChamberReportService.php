<?php

namespace App\Services\Reports;

use App\Models\Reservation;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class HotelChamberReportService
{
    public function generate(array $filters): array
    {
        [$dateFrom, $dateTo] = $this->resolveDateRange($filters);

        /** @var Collection<int, Reservation> $reservations */
        $reservations = Reservation::query()
            ->with(['customer', 'room.roomType', 'roomType'])
            ->whereIn('status', [
                Reservation::STATUS_CONFIRMED,
                Reservation::STATUS_CHECKED_IN,
                Reservation::STATUS_CHECKED_OUT,
            ])
            ->where('check_in', '<=', $dateTo->toDateString())
            ->where('check_out', '>=', $dateFrom->toDateString())
            ->when(
                filled($filters['room_type_id'] ?? null),
                fn ($builder) => $builder->where('room_type_id', (int) $filters['room_type_id'])
            )
            ->when(
                filled($filters['nationality'] ?? null),
                fn ($builder) => $builder->whereHas('customer', fn ($customerQuery) => $customerQuery
                    ->where('nationality', 'like', '%'.$filters['nationality'].'%'))
            )
            ->orderBy('check_in')
            ->orderBy('check_out')
            ->get();

        $rows = $reservations
            ->map(fn (Reservation $reservation): array => $this->row($reservation))
            ->filter(function (array $row) use ($filters): bool {
                return match ($filters['lodging_status'] ?? 'all') {
                    'currently_hosted' => $row['is_currently_hosted'],
                    'overstayed' => $row['is_overstayed'],
                    'extended' => $row['is_extended'],
                    'checked_out' => $row['reservation_status'] === Reservation::STATUS_CHECKED_OUT,
                    default => true,
                };
            })
            ->values();

        return [
            'filters' => [
                'date_from' => $dateFrom->toDateString(),
                'date_to' => $dateTo->toDateString(),
                'room_type_id' => $filters['room_type_id'] ?? null,
                'nationality' => $filters['nationality'] ?? null,
                'lodging_status' => $filters['lodging_status'] ?? 'all',
            ],
            'summary' => [
                'total_guests' => $rows->sum('total_guests'),
                'total_reservations' => $rows->count(),
                'currently_hosted' => $rows->where('is_currently_hosted', true)->count(),
                'foreign_guests' => $rows->where('is_foreign', true)->sum('total_guests'),
                'overstayed' => $rows->where('is_overstayed', true)->count(),
                'extended' => $rows->where('is_extended', true)->count(),
            ],
            'rows' => $rows,
        ];
    }

    private function row(Reservation $reservation): array
    {
        $customer = $reservation->customer;
        $scheduledCheckOut = $reservation->check_out ? Carbon::parse($reservation->check_out)->startOfDay() : null;
        $realCheckOut = $reservation->checked_out_at ? Carbon::parse($reservation->checked_out_at)->startOfDay() : null;
        $today = now()->startOfDay();
        $isCurrentlyHosted = $reservation->status === Reservation::STATUS_CHECKED_IN;
        $isOverstayed = $isCurrentlyHosted && $scheduledCheckOut && $today->greaterThan($scheduledCheckOut);
        $isExtended = $realCheckOut && $scheduledCheckOut && $realCheckOut->greaterThan($scheduledCheckOut);
        $actualStayUntil = $realCheckOut ?: ($isCurrentlyHosted ? $today : $scheduledCheckOut);
        $actualNights = $reservation->check_in && $actualStayUntil
            ? max(Carbon::parse($reservation->check_in)->startOfDay()->diffInDays($actualStayUntil), 1)
            : (int) $reservation->nights;

        return [
            'reservation_code' => $reservation->code,
            'guest_name' => $customer?->full_name ?? 'Sin huesped',
            'document_type' => strtoupper((string) ($customer?->document_type ?: '')),
            'document_number' => $customer?->document_number,
            'nationality' => $customer?->nationality ?: ($customer?->country ?: 'No registrada'),
            'country' => $customer?->country ?: 'No registrado',
            'city' => $customer?->city ?: 'No registrada',
            'phone' => $customer?->phone ?: $customer?->whatsapp,
            'email' => $customer?->email,
            'is_foreign' => (bool) ($customer?->is_foreign ?? false),
            'room_number' => $reservation->room?->number ?? '-',
            'room_type' => $reservation->roomType?->name ?? $reservation->room?->roomType?->name ?? '-',
            'check_in' => optional($reservation->check_in)?->format('d/m/Y'),
            'check_out' => optional($reservation->check_out)?->format('d/m/Y'),
            'checked_in_at' => optional($reservation->checked_in_at)?->format('d/m/Y H:i'),
            'checked_out_at' => optional($reservation->checked_out_at)?->format('d/m/Y H:i'),
            'reserved_nights' => (int) $reservation->nights,
            'actual_nights' => $actualNights,
            'adults' => (int) $reservation->adults,
            'children' => (int) $reservation->children,
            'total_guests' => (int) $reservation->adults + (int) $reservation->children,
            'reservation_status' => $reservation->status,
            'status_label' => $this->statusLabel($reservation->status),
            'is_currently_hosted' => $isCurrentlyHosted,
            'is_overstayed' => $isOverstayed,
            'is_extended' => $isExtended,
            'operational_observation' => $this->observation($reservation, $isOverstayed, $isExtended),
            'source' => $reservation->source,
            'special_requests' => $reservation->special_requests,
        ];
    }

    private function statusLabel(string $status): string
    {
        return [
            Reservation::STATUS_CONFIRMED => 'Confirmada / por llegar',
            Reservation::STATUS_CHECKED_IN => 'Hospedado',
            Reservation::STATUS_CHECKED_OUT => 'Salida registrada',
        ][$status] ?? ucfirst($status);
    }

    private function observation(Reservation $reservation, bool $isOverstayed, bool $isExtended): string
    {
        if ($isOverstayed) {
            return 'Se paso de la fecha de salida programada';
        }

        if ($isExtended) {
            return 'Salida extendida respecto a la fecha programada';
        }

        if ($reservation->status === Reservation::STATUS_CHECKED_IN) {
            return 'Actualmente hospedado';
        }

        if ($reservation->status === Reservation::STATUS_CONFIRMED) {
            return 'Reserva confirmada pendiente de ingreso';
        }

        return 'Sin observaciones';
    }

    private function resolveDateRange(array $filters): array
    {
        $dateFrom = filled($filters['date_from'] ?? null)
            ? Carbon::parse($filters['date_from'])
            : now();
        $dateTo = filled($filters['date_to'] ?? null)
            ? Carbon::parse($filters['date_to'])
            : $dateFrom->copy();

        return [$dateFrom, $dateTo];
    }
}
