<?php

namespace App\Http\Controllers\AdminLte;

use App\Http\Controllers\Controller;
use App\Http\Requests\AdminLte\StoreRoomTypeRequest;
use App\Http\Requests\AdminLte\UpdateRoomTypeRequest;
use App\Models\RoomType;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Yajra\DataTables\Facades\DataTables;

class RoomTypeController extends Controller
{
    public function index(): View
    {
        $this->authorize('viewAny', RoomType::class);

        $statsQuery = RoomType::query();

        return view('adminlte.room-types.index', [
            'stats' => [
                'total' => (clone $statsQuery)->count(),
                'active' => (clone $statsQuery)->where('is_active', true)->count(),
                'visible' => (clone $statsQuery)->where('show_on_website', true)->count(),
                'average_deposit' => (int) round((float) (clone $statsQuery)->avg('reservation_deposit_percentage')),
                'average_price_bob' => (float) (clone $statsQuery)->avg('price_bob'),
            ],
        ]);
    }

    public function data(): JsonResponse
    {
        $this->authorize('viewAny', RoomType::class);

        $query = RoomType::query()
            ->select('room_types.*')
            ->withCount('rooms');

        return DataTables::eloquent($query)
            ->addColumn('price_bob_formatted', fn (RoomType $roomType): string => 'Bs. '.number_format($roomType->priceBob(), 2, '.', ''))
            ->addColumn('price_usd_formatted', fn (RoomType $roomType): string => '$us '.number_format($roomType->priceUsd(), 2, '.', ''))
            ->addColumn('price_summary_formatted', fn (RoomType $roomType): string => $roomType->dualPriceLabel())
            ->addColumn('deposit_percentage_label', fn (RoomType $roomType): string => $roomType->reservationDepositPercentage().'%')
            ->addColumn('capacity_summary', fn (RoomType $roomType): string => sprintf(
                '%d adultos / %d ninos / Max. %d huespedes',
                (int) $roomType->capacity_adults,
                (int) $roomType->capacity_children,
                (int) $roomType->max_guests
            ))
            ->addColumn('main_image_url', fn (RoomType $roomType): ?string => $roomType->main_image ? asset('storage/'.$roomType->main_image) : null)
            ->addColumn('gallery_image_urls', fn (RoomType $roomType): array => collect($roomType->publicGalleryImages())
                ->map(fn (string $image): string => asset('storage/'.$image))
                ->all())
            ->addColumn('gallery_images_count', fn (RoomType $roomType): int => count($roomType->publicGalleryImages()))
            ->addColumn('amenities_text', fn (RoomType $roomType): string => collect($roomType->amenities ?? [])->filter()->implode(', '))
            ->addColumn('show_on_website_label', fn (RoomType $roomType): string => $roomType->show_on_website ? 'Visible' : 'Oculto')
            ->addColumn('status_label', fn (RoomType $roomType): string => $roomType->is_active ? 'Activo' : 'Inactivo')
            ->addColumn('created_at_formatted', fn (RoomType $roomType): string => optional($roomType->created_at)?->format('d/m/Y H:i') ?? '-')
            ->addColumn('can_update', fn (RoomType $roomType): bool => auth()->user()->can('update', $roomType))
            ->addColumn('can_delete', fn (RoomType $roomType): bool => auth()->user()->can('delete', $roomType))
            ->addColumn('update_url', fn (RoomType $roomType): string => route('adminlte.room-types.update', $roomType))
            ->addColumn('delete_url', fn (RoomType $roomType): string => route('adminlte.room-types.destroy', $roomType))
            ->toJson();
    }

    public function store(StoreRoomTypeRequest $request): JsonResponse
    {
        $this->authorize('create', RoomType::class);

        $validated = $request->validated();

        $galleryImages = $this->storeGalleryImages($request);

        $roomType = RoomType::create([
            'name' => $validated['name'],
            'slug' => $this->generateUniqueSlug($validated['name']),
            'description' => $validated['description'] ?? null,
            'base_price' => $validated['price_bob'],
            'price_bob' => $validated['price_bob'],
            'price_usd' => $validated['price_usd'],
            'reservation_deposit_percentage' => (int) $validated['reservation_deposit_percentage'],
            'capacity_adults' => $validated['capacity_adults'],
            'capacity_children' => (int) ($validated['capacity_children'] ?? 0),
            'max_guests' => $validated['max_guests'],
            'main_image' => $galleryImages[0] ?? null,
            'gallery_images' => $galleryImages,
            'amenities' => $this->parseAmenities($validated['amenities'] ?? null),
            'show_on_website' => (bool) ($validated['show_on_website'] ?? false),
            'is_active' => (bool) ($validated['is_active'] ?? false),
        ]);

        return response()->json([
            'message' => 'Tipo de habitacion creado correctamente.',
            'room_type' => $roomType,
        ]);
    }

    public function update(UpdateRoomTypeRequest $request, RoomType $roomType): JsonResponse
    {
        $this->authorize('update', $roomType);

        $validated = $request->validated();
        $nameChanged = $roomType->name !== $validated['name'];
        $galleryImages = $this->storeGalleryImages($request, $roomType);

        $roomType->update([
            'name' => $validated['name'],
            'slug' => $nameChanged ? $this->generateUniqueSlug($validated['name'], $roomType->id) : $roomType->slug,
            'description' => $validated['description'] ?? null,
            'base_price' => $validated['price_bob'],
            'price_bob' => $validated['price_bob'],
            'price_usd' => $validated['price_usd'],
            'reservation_deposit_percentage' => (int) $validated['reservation_deposit_percentage'],
            'capacity_adults' => $validated['capacity_adults'],
            'capacity_children' => (int) ($validated['capacity_children'] ?? 0),
            'max_guests' => $validated['max_guests'],
            'main_image' => $galleryImages[0] ?? $roomType->main_image,
            'gallery_images' => $galleryImages,
            'amenities' => $this->parseAmenities($validated['amenities'] ?? null),
            'show_on_website' => (bool) ($validated['show_on_website'] ?? false),
            'is_active' => (bool) ($validated['is_active'] ?? false),
        ]);

        return response()->json([
            'message' => 'Tipo de habitacion actualizado correctamente.',
        ]);
    }

    public function destroy(RoomType $roomType): JsonResponse
    {
        $this->authorize('delete', $roomType);

        $roomType->delete();

        return response()->json([
            'message' => 'Tipo de habitacion eliminado correctamente.',
        ]);
    }

    private function generateUniqueSlug(string $name, ?int $ignoreId = null): string
    {
        $baseSlug = Str::slug($name);
        $slug = $baseSlug;
        $counter = 2;

        while (
            RoomType::withTrashed()
                ->where('slug', $slug)
                ->when($ignoreId, fn ($query) => $query->where('id', '!=', $ignoreId))
                ->exists()
        ) {
            $slug = $baseSlug.'-'.$counter;
            $counter++;
        }

        return $slug;
    }

    private function parseAmenities(?string $amenities): array
    {
        if (! $amenities) {
            return [];
        }

        return collect(preg_split('/\r\n|\r|\n/', $amenities))
            ->map(fn (string $item): string => trim($item))
            ->filter()
            ->values()
            ->all();
    }

    private function storeGalleryImages(StoreRoomTypeRequest|UpdateRoomTypeRequest $request, ?RoomType $roomType = null): array
    {
        if (! $request->hasFile('gallery_images')) {
            return $roomType?->publicGalleryImages() ?? [];
        }

        $oldImages = $roomType?->publicGalleryImages() ?? [];
        $newImages = [];

        foreach (array_slice($request->file('gallery_images', []), 0, 4) as $image) {
            $originalName = pathinfo($image->getClientOriginalName(), PATHINFO_FILENAME);
            $filename = Str::slug($originalName ?: ($request->input('name', 'room-type'))).'-'.Str::lower(Str::random(8)).'.'.$image->getClientOriginalExtension();
            $newImages[] = $image->storeAs('room-types', $filename, 'public');
        }

        foreach ($oldImages as $oldImage) {
            if (! in_array($oldImage, $newImages, true) && Storage::disk('public')->exists($oldImage)) {
                Storage::disk('public')->delete($oldImage);
            }
        }

        return $newImages;
    }
}
