<?php

namespace App\Services\Reports;

use App\Models\Reservation;
use App\Models\Room;
use App\Models\RoomType;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class OccupancyReportService
{
    public function generate(array $filters): array
    {
        [$dateFrom, $dateTo] = $this->resolveDateRange($filters);

        /** @var Collection<int, Room> $rooms */
        $rooms = Room::query()
            ->with('roomType')
            ->where('is_active', true)
            ->when(
                filled($filters['room_type_id'] ?? null),
                fn ($builder) => $builder->where('room_type_id', (int) $filters['room_type_id'])
            )
            ->get();

        $totalRooms = $rooms->count();

        $activeReservations = Reservation::query()
            ->with(['customer', 'room', 'roomType'])
            ->whereIn('status', Reservation::ACTIVE_STATUSES)
            ->where('check_in', '<', $dateTo->toDateString())
            ->where('check_out', '>', $dateFrom->toDateString())
            ->when(
                filled($filters['room_type_id'] ?? null),
                fn ($builder) => $builder->where('room_type_id', (int) $filters['room_type_id'])
            )
            ->orderBy('check_in')
            ->get();

        $byRoomType = RoomType::query()
            ->where('is_active', true)
            ->when(
                filled($filters['room_type_id'] ?? null),
                fn ($builder) => $builder->where('id', (int) $filters['room_type_id'])
            )
            ->get()
            ->map(function (RoomType $roomType) use ($rooms, $activeReservations): array {
                $typeRooms = $rooms->where('room_type_id', $roomType->id);
                $occupiedCount = $typeRooms->where('status', 'occupied')->count();
                $totalTypeRooms = $typeRooms->count();

                return [
                    'room_type_name' => $roomType->name,
                    'total_rooms' => $totalTypeRooms,
                    'occupied' => $occupiedCount,
                    'reserved' => $typeRooms->where('status', 'reserved')->count(),
                    'available' => $typeRooms->where('status', 'available')->count(),
                    'active_reservations' => $activeReservations->where('room_type_id', $roomType->id)->count(),
                    'occupancy_rate' => $totalTypeRooms > 0
                        ? round(($occupiedCount / $totalTypeRooms) * 100, 2)
                        : 0,
                ];
            })
            ->values();

        return [
            'filters' => [
                'date_from' => $dateFrom->toDateString(),
                'date_to' => $dateTo->toDateString(),
                'room_type_id' => $filters['room_type_id'] ?? null,
            ],
            'summary' => [
                'total_rooms' => $totalRooms,
                'available' => $rooms->where('status', 'available')->count(),
                'occupied' => $rooms->where('status', 'occupied')->count(),
                'reserved' => $rooms->where('status', 'reserved')->count(),
                'occupancy_rate' => $totalRooms > 0
                    ? round(($rooms->where('status', 'occupied')->count() / $totalRooms) * 100, 2)
                    : 0,
            ],
            'by_room_type' => $byRoomType,
            'active_reservations' => $activeReservations,
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
