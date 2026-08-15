<?php

namespace App\Services\Reports;

use App\Models\HotelSetting;
use App\Models\Reservation;
use App\Models\ReservationGuest;
use App\Models\Room;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class HotelChamberReportService
{
    public function generate(array $filters): array
    {
        [$dateFrom, $dateTo] = $this->resolveDateRange($filters);

        /** @var Collection<int, Reservation> $reservations */
        $reservations = Reservation::query()
            ->with(['customer', 'guests', 'payments', 'room.roomType', 'roomType'])
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
            ->flatMap(fn (Reservation $reservation): array => $this->rowsForReservation($reservation))
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
                'total_guests' => $rows->count(),
                'total_reservations' => $rows->pluck('reservation_code')->unique()->count(),
                'currently_hosted' => $rows->where('is_currently_hosted', true)->count(),
                'foreign_guests' => $rows->where('is_foreign', true)->count(),
                'overstayed' => $rows->where('is_overstayed', true)->count(),
                'extended' => $rows->where('is_extended', true)->count(),
            ],
            'official_headings' => $this->officialHeadings(),
            'official_rows' => $rows->map(fn (array $row): array => $this->officialRow($row))->values(),
            'general_headings' => $this->generalHeadings(),
            'general_rows' => [$this->generalRow()],
            'catalog_rows' => $this->catalogRows(),
            'rows' => $rows,
        ];
    }

    private function rowsForReservation(Reservation $reservation): array
    {
        $rows = [$this->row($reservation, null)];

        foreach ($reservation->guests as $guest) {
            $rows[] = $this->row($reservation, $guest);
        }

        return $rows;
    }

    private function row(Reservation $reservation, ?ReservationGuest $guest): array
    {
        $customer = $reservation->customer;
        $hotel = HotelSetting::current();
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
        $isCompanion = $guest !== null;
        $guestName = $isCompanion ? $guest->full_name : ($customer?->full_name ?? 'Sin huesped');
        $documentType = strtoupper((string) (($isCompanion ? $guest->document_type : $customer?->document_type) ?: ''));
        $documentNumber = $isCompanion ? $guest->document_number : $customer?->document_number;
        $nationality = $isCompanion
            ? ($guest->nationality ?: $guest->country)
            : ($customer?->nationality ?: $customer?->country);
        $country = $isCompanion ? $guest->country : $customer?->country;
        $isForeign = $this->isForeign($nationality, $country, (bool) ($customer?->is_foreign ?? false), $isCompanion);
        $confirmedPayment = $reservation->payments
            ->where('status', 'confirmed')
            ->sortByDesc('confirmed_at')
            ->first();

        return [
            'hotel_tax_number' => $hotel->tax_number ?: '',
            'branch_code' => '0',
            'reservation_code' => $reservation->code,
            'guest_name' => $guestName,
            'guest_kind' => $isCompanion ? 'Acompanante' : 'Titular',
            'document_type' => $documentType,
            'document_number' => $documentNumber,
            'nationality' => $this->officialNationality($nationality, $country, $isForeign),
            'country' => $country ?: 'No registrado',
            'city' => $customer?->city ?: 'No registrada',
            'phone' => $customer?->phone ?: $customer?->whatsapp,
            'email' => $customer?->email,
            'is_foreign' => $isForeign,
            'room_number' => $reservation->room?->number ?? '-',
            'room_type' => $reservation->roomType?->name ?? $reservation->room?->roomType?->name ?? '-',
            'check_in' => optional($reservation->check_in)?->format('d/m/Y'),
            'check_out' => optional($reservation->check_out)?->format('d/m/Y'),
            'official_check_in' => optional($reservation->check_in)?->format('d/m/Y'),
            'official_check_out' => optional($reservation->checked_out_at ?: $reservation->check_out)?->format('d/m/Y'),
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
            'official_observation' => $this->officialObservation($reservation, $guest, $isOverstayed, $isExtended),
            'invoice_number' => '',
            'invoice_cuf' => '',
            'beneficiary_tax_number' => $this->beneficiaryTaxNumber($customer),
            'last_payment_reference' => $confirmedPayment?->reference_number,
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

    public function officialHeadings(): array
    {
        return [
            'NIT (14)',
            'CASA MATRIZ O SUCURSAL (2)',
            'NUMERO CI, CARNET EXTRANJERO PASAPORTE O EQUIVALENTE HUESPED (20)',
            'NACIONALIDAD(50)',
            'FECHA INGRESO (DD/MM/AAAA)',
            'FECHA SALIDA (DD/MM/AAAA)',
            'NUMERO FACTURA',
            'CODIGO UNICO FACTURACION CUF O NUMERO AUTORIZACION (70)',
            'OBSERVACIONES (260)',
            'NIT O CI BENEFICIARIO FACT (15)',
        ];
    }

    public function generalHeadings(): array
    {
        return [
            'NIT(14)',
            'CASA MATRIZ O SUCURSAL (2)',
            'RAZON SOCIAL (250)',
            'NOMBRE COMERCIAL (250)',
            'DIRECCION (250)',
            'TIPO DE ESTABLECIMIENTO (2)',
            'DESCRIPCION TIPO DE ESTABLECIMIENTO (100)',
            'CATEGORIA (1)',
            'NUMETO TOTAL DE HABITACIONES (3)',
            'NUMERO TOTAL DE DEPARTAMENTOS (3)',
            'NUMERO TOTAL DE CABAÑAS O BUNGALOS (3)',
            'MODALIDAD DE REGISTRO DEL HUESPED (1)',
            'DESCRIPCION MODALIDAD SISTEMA REGISTRO (200)',
        ];
    }

    private function officialRow(array $row): array
    {
        return [
            $row['hotel_tax_number'],
            $row['branch_code'],
            $row['document_number'] ?: 'SIN DOCUMENTO',
            $row['nationality'],
            $row['official_check_in'],
            $row['official_check_out'],
            $row['invoice_number'],
            $row['invoice_cuf'],
            $row['official_observation'],
            $row['beneficiary_tax_number'],
        ];
    }

    private function generalRow(): array
    {
        $hotel = HotelSetting::current();

        return [
            $hotel->tax_number ?: '',
            '0',
            mb_strtoupper($hotel->legal_name ?: $hotel->hotel_name ?: 'HOSTAL CERRO RICO'),
            mb_strtoupper($hotel->hotel_name ?: 'HOSTAL CERRO RICO'),
            mb_strtoupper($hotel->address ?: trim(($hotel->city ?: 'Potosi').', '.($hotel->country ?: 'Bolivia'), ', ')),
            '5 HOSTAL',
            'HOSTAL',
            '3 3 ESTRELLA',
            Room::query()->count(),
            0,
            0,
            'TARJETAS DE REGISTRO',
            'SISTEMA HOTELERO',
        ];
    }

    private function catalogRows(): array
    {
        return [
            ['TIPO DE ESTABLECIMIENTO', '', ''],
            [1, 'HOTEL', '1 HOTEL'],
            [2, 'APART HOTEL', '2 APART HOTEL'],
            [3, 'HOTEL BOUTIQUE', '3 HOTEL BOUTIQUE'],
            [4, 'RESORT', '4 RESORT'],
            [5, 'HOSTAL', '5 HOSTAL'],
            [6, 'RESIDENCIAL', '6 RESIDENCIAL'],
            [7, 'ALOJAMIENTO', '7 ALOJAMIENTO'],
            [8, 'HOTEL DE AEROPUERTO', '8 HOTEL DE AEROPUERTO'],
            [9, 'CASA DE HUESPEDES', '9 CASA DE HUESPEDES'],
            [10, 'OTROS DE PERNOCTE O ALOJAMIENTO TEMPORAL', '10 OTROS DE PERNOCTE O ALOJAMIENTO TEMPORAL'],
            ['', '', ''],
            ['CATEGORIA ESTABLECIMIENTO', '', ''],
            [1, '1 ESTRELLA', '1 1 ESTRELLA'],
            [2, '2 ESTRELLA', '2 2 ESTRELLA'],
            [3, '3 ESTRELLA', '3 3 ESTRELLA'],
            [4, '4 ESTRELLA', '4 4 ESTRELLA'],
            [5, '5 ESTRELLA', '5 5 ESTRELLA'],
            [6, 'CLASE A', '6 CLASE A'],
            [7, 'CLASE B', '7 CLASE B'],
            [8, 'CATEGORIA UNICA', '8 CATEGORIA UNICA'],
            [9, 'SIN CATEGORIA', '9 SIN CATEGORIA'],
        ];
    }

    private function officialObservation(Reservation $reservation, ?ReservationGuest $guest, bool $isOverstayed, bool $isExtended): string
    {
        $parts = [
            'Reserva '.$reservation->code,
            'Hab. '.($reservation->room?->number ?? '-'),
            $guest ? 'Acompanante' : 'Titular',
        ];

        if ($isOverstayed) {
            $parts[] = 'Se paso de salida';
        } elseif ($isExtended) {
            $parts[] = 'Salida extendida';
        }

        if ($guest && blank($guest->document_number)) {
            $parts[] = 'Falta documento de acompanante';
        } elseif (! $guest && blank($reservation->customer?->document_number)) {
            $parts[] = 'Falta documento de huesped';
        }

        if ($reservation->payments->where('status', 'confirmed')->isEmpty()) {
            $parts[] = 'Sin pago confirmado';
        }

        $observation = implode(' - ', $parts);

        return mb_substr($observation, 0, 260);
    }

    private function beneficiaryTaxNumber(mixed $customer): string
    {
        if (! $customer) {
            return '';
        }

        if (($customer->is_company ?? false) && filled($customer->tax_number)) {
            return (string) $customer->tax_number;
        }

        return (string) ($customer->tax_number ?: $customer->document_number ?: '');
    }

    private function isForeign(?string $nationality, ?string $country, bool $customerFlag, bool $isCompanion): bool
    {
        $text = mb_strtolower(trim(($nationality ?? '').' '.($country ?? '')));

        if ($text !== '') {
            return ! str_contains($text, 'bolivia') && ! str_contains($text, 'bolivian');
        }

        return $isCompanion ? false : $customerFlag;
    }

    private function officialNationality(?string $nationality, ?string $country, bool $isForeign): string
    {
        $value = trim((string) ($nationality ?: $country));

        if ($value === '') {
            return $isForeign ? 'EXTRANJERO' : 'BOLIVIANO';
        }

        $normalized = mb_strtoupper($value);

        return match (true) {
            str_contains($normalized, 'BOLIVIA') => 'BOLIVIANO',
            str_contains($normalized, 'ARGENTINA') => 'ARGENTINO',
            str_contains($normalized, 'CHILE') => 'CHILENO',
            str_contains($normalized, 'PERU') || str_contains($normalized, 'PERÚ') => 'PERUANO',
            str_contains($normalized, 'BRASIL') => 'BRASILENO',
            str_contains($normalized, 'PARAGUAY') => 'PARAGUAYO',
            str_contains($normalized, 'URUGUAY') => 'URUGUAYO',
            str_contains($normalized, 'COLOMBIA') => 'COLOMBIANO',
            str_contains($normalized, 'ECUADOR') => 'ECUATORIANO',
            str_contains($normalized, 'VENEZUELA') => 'VENEZOLANO',
            str_contains($normalized, 'ESTADOS UNIDOS') || $normalized === 'USA' || $normalized === 'US' => 'USA',
            default => $normalized,
        };
    }

    private function resolveDateRange(array $filters): array
    {
        $dateFrom = filled($filters['date_from'] ?? null)
            ? Carbon::parse($filters['date_from'])
            : now()->startOfMonth();
        $dateTo = filled($filters['date_to'] ?? null)
            ? Carbon::parse($filters['date_to'])
            : $dateFrom->copy()->endOfMonth();

        return [$dateFrom, $dateTo];
    }
}
