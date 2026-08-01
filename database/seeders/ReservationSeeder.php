<?php

namespace Database\Seeders;

use App\Models\Customer;
use App\Models\Promotion;
use App\Models\Reservation;
use App\Models\Room;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

class ReservationSeeder extends Seeder
{
    public function run(): void
    {
        $rooms = Room::query()->with('roomType')->where('is_active', true)->get();
        $customers = Customer::query()->where('is_active', true)->get();

        if ($rooms->isEmpty() || $customers->isEmpty()) {
            return;
        }

        $firstReservationRoom = $rooms->firstWhere('number', '101') ?? $rooms->first();
        $secondReservationRoom = $rooms->firstWhere('number', '102')
            ?? $rooms->where('id', '!=', $firstReservationRoom?->id)->first();

        if (! $firstReservationRoom || ! $firstReservationRoom->roomType) {
            return;
        }

        $juanPerez = $customers->firstWhere('full_name', 'Juan Perez Mamani') ?? $customers->first();
        $mariaFernandez = $customers->firstWhere('full_name', 'Maria Fernandez')
            ?? $customers->where('id', '!=', $juanPerez?->id)->first();

        if (! $juanPerez) {
            return;
        }

        $this->createReservation(
            customer: $juanPerez,
            room: $firstReservationRoom,
            checkIn: Carbon::tomorrow(),
            checkOut: Carbon::tomorrow()->copy()->addDays(2),
            adults: 1,
            children: 0,
            status: Reservation::STATUS_PENDING,
            source: 'reception',
            promotion: null,
        );

        if ($secondReservationRoom && $secondReservationRoom->roomType && $mariaFernandez) {
            $promotion = Promotion::query()
                ->where('is_active', true)
                ->whereHas('roomTypes', fn ($query) => $query->where('room_types.id', $secondReservationRoom->room_type_id))
                ->first();

            $this->createReservation(
                customer: $mariaFernandez,
                room: $secondReservationRoom,
                checkIn: Carbon::today()->addDays(3),
                checkOut: Carbon::today()->addDays(5),
                adults: 2,
                children: 0,
                status: Reservation::STATUS_CONFIRMED,
                source: 'whatsapp',
                promotion: $promotion,
            );
        }
    }

    private function createReservation(
        Customer $customer,
        Room $room,
        Carbon $checkIn,
        Carbon $checkOut,
        int $adults,
        int $children,
        string $status,
        string $source,
        ?Promotion $promotion,
    ): void {
        if (! $room->roomType) {
            return;
        }

        $codePrefix = 'RES-'.$checkIn->format('Ymd').'-';
        $latestCode = Reservation::withTrashed()
            ->where('code', 'like', $codePrefix.'%')
            ->latest('id')
            ->value('code');
        $nextNumber = 1;

        if ($latestCode && preg_match('/(\d{4})$/', $latestCode, $matches) === 1) {
            $nextNumber = (int) $matches[1] + 1;
        }

        $reservation = new Reservation([
            'code' => $codePrefix.str_pad((string) $nextNumber, 4, '0', STR_PAD_LEFT),
            'customer_id' => $customer->id,
            'room_id' => $room->id,
            'room_type_id' => $room->room_type_id,
            'promotion_id' => $promotion?->id,
            'check_in' => $checkIn,
            'check_out' => $checkOut,
            'adults' => $adults,
            'children' => $children,
            'base_price' => (float) $room->roomType->base_price,
            'discount_type' => $promotion?->discount_type,
            'discount_value' => (float) ($promotion?->discount_value ?? 0),
            'paid_amount' => 0,
            'status' => $status,
            'source' => $source,
            'confirmed_at' => $status === Reservation::STATUS_CONFIRMED ? now() : null,
        ]);

        $reservation->recalculateTotals();

        Reservation::query()->firstOrCreate(
            ['code' => $reservation->code],
            $reservation->getAttributes(),
        );
    }
}
