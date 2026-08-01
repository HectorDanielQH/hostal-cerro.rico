<?php

namespace App\Http\Controllers\AdminLte;

use App\Http\Controllers\Controller;
use App\Http\Requests\AdminLte\StoreRoomRequest;
use App\Http\Requests\AdminLte\UpdateRoomRequest;
use App\Models\Room;
use App\Models\RoomType;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Yajra\DataTables\Facades\DataTables;

class RoomController extends Controller
{
    private const STATUSES = [
        'available' => ['label' => 'Disponible', 'badge' => 'badge text-bg-success', 'icon' => 'bi-check2-circle'],
        'occupied' => ['label' => 'Ocupada', 'badge' => 'badge text-bg-danger', 'icon' => 'bi-person-check'],
        'reserved' => ['label' => 'Reservada', 'badge' => 'badge text-bg-warning', 'icon' => 'bi-calendar2-check'],
    ];

    public function index(): View
    {
        $this->authorize('viewAny', Room::class);

        $roomTypes = RoomType::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        $statusCounts = Room::query()
            ->selectRaw('status, count(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        $totalRooms = Room::query()->count();
        $activeRooms = Room::query()->where('is_active', true)->count();

        return view('adminlte.rooms.index', [
            'roomTypes' => $roomTypes,
            'statuses' => self::STATUSES,
            'stats' => [
                'total' => $totalRooms,
                'active' => $activeRooms,
                'inactive' => max($totalRooms - $activeRooms, 0),
                'available' => (int) ($statusCounts['available'] ?? 0),
                'occupied' => (int) ($statusCounts['occupied'] ?? 0),
                'reserved' => (int) ($statusCounts['reserved'] ?? 0),
            ],
        ]);
    }

    public function data(): JsonResponse
    {
        $this->authorize('viewAny', Room::class);

        $query = Room::query()
            ->with('roomType')
            ->select('rooms.*');

        return DataTables::eloquent($query)
            ->addColumn('status_label', fn (Room $room): string => self::STATUSES[$room->status]['label'] ?? ucfirst($room->status))
            ->addColumn('status_badge_class', fn (Room $room): string => self::STATUSES[$room->status]['badge'] ?? 'badge text-bg-secondary')
            ->addColumn('status_icon', fn (Room $room): string => self::STATUSES[$room->status]['icon'] ?? 'bi-door-open')
            ->addColumn('active_label', fn (Room $room): string => $room->is_active ? 'Activo' : 'Inactivo')
            ->addColumn('room_type_name', fn (Room $room): string => $room->roomType?->name ?? '-')
            ->addColumn('room_type_price', fn (Room $room): float => (float) ($room->roomType?->priceBob() ?? 0))
            ->addColumn('room_type_price_formatted', fn (Room $room): string => $room->roomType?->dualPriceLabel() ?? '-')
            ->addColumn('capacity_summary', fn (Room $room): string => sprintf(
                '%d adultos / %d ninos / Max. %d huespedes',
                (int) ($room->roomType?->capacity_adults ?? 0),
                (int) ($room->roomType?->capacity_children ?? 0),
                (int) ($room->roomType?->max_guests ?? 0)
            ))
            ->addColumn('created_at_formatted', fn (Room $room): string => optional($room->created_at)?->format('d/m/Y H:i') ?? '-')
            ->addColumn('can_update', fn (Room $room): bool => auth()->user()->can('update', $room))
            ->addColumn('can_delete', fn (Room $room): bool => auth()->user()->can('delete', $room))
            ->addColumn('can_change_status', fn (Room $room): bool => auth()->user()->can('changeStatus', $room))
            ->addColumn('update_url', fn (Room $room): string => route('adminlte.rooms.update', $room))
            ->addColumn('delete_url', fn (Room $room): string => route('adminlte.rooms.destroy', $room))
            ->addColumn('change_status_url', fn (Room $room): string => route('adminlte.rooms.status', $room))
            ->toJson();
    }

    public function store(StoreRoomRequest $request): JsonResponse
    {
        $this->authorize('create', Room::class);

        $validated = $request->validated();

        Room::create([
            'room_type_id' => $validated['room_type_id'],
            'number' => $validated['number'],
            'floor' => $validated['floor'] ?? null,
            'description' => $validated['description'] ?? null,
            'internal_notes' => $validated['internal_notes'] ?? null,
            'status' => $validated['status'],
            'is_active' => (bool) ($validated['is_active'] ?? false),
        ]);

        return response()->json([
            'message' => 'Habitacion creada correctamente.',
        ]);
    }

    public function update(UpdateRoomRequest $request, Room $room): JsonResponse
    {
        $this->authorize('update', $room);

        $validated = $request->validated();

        $room->update([
            'room_type_id' => $validated['room_type_id'],
            'number' => $validated['number'],
            'floor' => $validated['floor'] ?? null,
            'description' => $validated['description'] ?? null,
            'internal_notes' => $validated['internal_notes'] ?? null,
            'status' => $validated['status'],
            'is_active' => (bool) ($validated['is_active'] ?? false),
        ]);

        return response()->json([
            'message' => 'Habitacion actualizada correctamente.',
        ]);
    }

    public function destroy(Room $room): JsonResponse
    {
        $this->authorize('delete', $room);

        $room->delete();

        return response()->json([
            'message' => 'Habitacion eliminada correctamente.',
        ]);
    }

    public function changeStatus(Request $request, Room $room): JsonResponse
    {
        $this->authorize('changeStatus', $room);

        $validated = $request->validate([
            'status' => ['required', Rule::in(array_keys(self::STATUSES))],
        ]);

        // TODO: Cuando exista el modulo de reservas, bloquear cambios manuales
        // de occupied/reserved si la habitacion tiene una reserva activa.
        $room->update([
            'status' => $validated['status'],
        ]);

        return response()->json([
            'message' => 'Estado de habitacion actualizado correctamente.',
        ]);
    }
}
