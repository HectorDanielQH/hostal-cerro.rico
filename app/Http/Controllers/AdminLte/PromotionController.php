<?php

namespace App\Http\Controllers\AdminLte;

use App\Http\Controllers\Controller;
use App\Http\Requests\AdminLte\StorePromotionRequest;
use App\Http\Requests\AdminLte\UpdatePromotionRequest;
use App\Models\Promotion;
use App\Models\RoomType;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Yajra\DataTables\Facades\DataTables;

class PromotionController extends Controller
{
    private const DISCOUNT_TYPES = [
        'percentage' => 'Porcentaje',
        'fixed' => 'Monto fijo',
    ];

    public function index(): View
    {
        $this->authorize('viewAny', Promotion::class);

        $roomTypes = RoomType::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        $promotions = Promotion::query()->get();

        return view('adminlte.promotions.index', [
            'roomTypes' => $roomTypes,
            'discountTypes' => self::DISCOUNT_TYPES,
            'stats' => [
                'total' => $promotions->count(),
                'active' => $promotions->filter->isCurrentlyActive()->count(),
                'visible' => $promotions->where('show_on_website', true)->count(),
                'expired' => $promotions->filter(fn (Promotion $promotion): bool => ! $promotion->isCurrentlyActive() && (bool) $promotion->is_active)->count(),
                'used' => (int) $promotions->sum('used_count'),
            ],
        ]);
    }

    public function data(): JsonResponse
    {
        $this->authorize('viewAny', Promotion::class);

        $query = Promotion::query()
            ->with('roomTypes')
            ->select('promotions.*');

        return DataTables::eloquent($query)
            ->addColumn('discount_type_label', fn (Promotion $promotion): string => self::DISCOUNT_TYPES[$promotion->discount_type] ?? $promotion->discount_type)
            ->addColumn('discount_label', fn (Promotion $promotion): string => $promotion->discount_type === 'percentage'
                ? rtrim(rtrim(number_format((float) $promotion->discount_value, 2, '.', ''), '0'), '.').'%'
                : 'Bs. '.number_format((float) $promotion->discount_value, 2, '.', ''))
            ->addColumn('date_range_label', function (Promotion $promotion): string {
                if ($promotion->starts_at && $promotion->ends_at) {
                    return $promotion->starts_at->format('d/m/Y').' - '.$promotion->ends_at->format('d/m/Y');
                }

                return 'Sin limite de fechas';
            })
            ->addColumn('date_range_detail', function (Promotion $promotion): string {
                if (! $promotion->starts_at && ! $promotion->ends_at) {
                    return 'Siempre disponible mientras este activa.';
                }

                if ($promotion->starts_at && ! $promotion->ends_at) {
                    return 'Desde '.$promotion->starts_at->format('d/m/Y').', sin fecha de cierre.';
                }

                if (! $promotion->starts_at && $promotion->ends_at) {
                    return 'Disponible hasta '.$promotion->ends_at->format('d/m/Y').'.';
                }

                return 'Del '.$promotion->starts_at->format('d/m/Y').' al '.$promotion->ends_at->format('d/m/Y').'.';
            })
            ->addColumn('usage_label', fn (Promotion $promotion): string => $promotion->maximum_uses
                ? $promotion->used_count.' / '.$promotion->maximum_uses
                : 'Sin limite')
            ->addColumn('usage_percentage', fn (Promotion $promotion): int => $promotion->maximum_uses
                ? min((int) round(((int) $promotion->used_count * 100) / (int) $promotion->maximum_uses), 100)
                : 0)
            ->addColumn('show_on_website_label', fn (Promotion $promotion): string => $promotion->show_on_website ? 'Visible' : 'Oculto')
            ->addColumn('status_label', function (Promotion $promotion): string {
                if (! $promotion->is_active) {
                    return 'Inactiva';
                }

                return $promotion->isCurrentlyActive() ? 'Activa' : 'Vencida';
            })
            ->addColumn('status_badge_class', function (Promotion $promotion): string {
                if (! $promotion->is_active) {
                    return 'badge text-bg-secondary';
                }

                return $promotion->isCurrentlyActive() ? 'badge text-bg-success' : 'badge text-bg-warning';
            })
            ->addColumn('room_type_ids', fn (Promotion $promotion): array => $promotion->roomTypes->pluck('id')->values()->all())
            ->addColumn('room_type_names', fn (Promotion $promotion): array => $promotion->roomTypes->pluck('name')->values()->all())
            ->addColumn('room_type_badges', fn (Promotion $promotion): array => $promotion->roomTypes->map(fn (RoomType $roomType) => [
                'id' => $roomType->id,
                'name' => $roomType->name,
            ])->values()->all())
            ->addColumn('created_at_formatted', fn (Promotion $promotion): string => optional($promotion->created_at)?->format('d/m/Y H:i') ?? '-')
            ->addColumn('can_update', fn (Promotion $promotion): bool => auth()->user()->can('update', $promotion))
            ->addColumn('can_delete', fn (Promotion $promotion): bool => auth()->user()->can('delete', $promotion))
            ->addColumn('update_url', fn (Promotion $promotion): string => route('adminlte.promotions.update', $promotion))
            ->addColumn('delete_url', fn (Promotion $promotion): string => route('adminlte.promotions.destroy', $promotion))
            ->toJson();
    }

    public function store(StorePromotionRequest $request): JsonResponse
    {
        $this->authorize('create', Promotion::class);

        $validated = $request->validated();

        $promotion = Promotion::create([
            'name' => $validated['name'],
            'slug' => $this->generateUniqueSlug($validated['name']),
            'description' => $validated['description'] ?? null,
            'discount_type' => $validated['discount_type'],
            'discount_value' => $validated['discount_value'],
            'starts_at' => $validated['starts_at'] ?? null,
            'ends_at' => $validated['ends_at'] ?? null,
            'minimum_nights' => $validated['minimum_nights'] ?? null,
            'maximum_uses' => $validated['maximum_uses'] ?? null,
            'used_count' => 0,
            'show_on_website' => (bool) ($validated['show_on_website'] ?? false),
            'is_active' => (bool) ($validated['is_active'] ?? false),
        ]);

        $promotion->roomTypes()->sync($validated['room_type_ids']);

        return response()->json([
            'message' => 'Promocion creada correctamente.',
        ]);
    }

    public function update(UpdatePromotionRequest $request, Promotion $promotion): JsonResponse
    {
        $this->authorize('update', $promotion);

        $validated = $request->validated();
        $nameChanged = $promotion->name !== $validated['name'];

        $promotion->update([
            'name' => $validated['name'],
            'slug' => $nameChanged ? $this->generateUniqueSlug($validated['name'], $promotion->id) : $promotion->slug,
            'description' => $validated['description'] ?? null,
            'discount_type' => $validated['discount_type'],
            'discount_value' => $validated['discount_value'],
            'starts_at' => $validated['starts_at'] ?? null,
            'ends_at' => $validated['ends_at'] ?? null,
            'minimum_nights' => $validated['minimum_nights'] ?? null,
            'maximum_uses' => $validated['maximum_uses'] ?? null,
            'show_on_website' => (bool) ($validated['show_on_website'] ?? false),
            'is_active' => (bool) ($validated['is_active'] ?? false),
        ]);

        $promotion->roomTypes()->sync($validated['room_type_ids']);

        return response()->json([
            'message' => 'Promocion actualizada correctamente.',
        ]);
    }

    public function destroy(Promotion $promotion): JsonResponse
    {
        $this->authorize('delete', $promotion);

        $promotion->delete();

        return response()->json([
            'message' => 'Promocion eliminada correctamente.',
        ]);
    }

    public function preview(Request $request): JsonResponse
    {
        $request->validate([
            'room_type_id' => ['required', 'exists:room_types,id'],
            'discount_type' => ['required', Rule::in(array_keys(self::DISCOUNT_TYPES))],
            'discount_value' => ['required', 'numeric', 'min:0.01'],
        ]);

        $roomType = RoomType::query()->findOrFail($request->integer('room_type_id'));
        $basePrice = (float) $roomType->base_price;
        $discountAmount = $request->input('discount_type') === 'percentage'
            ? min(($basePrice * (float) $request->input('discount_value')) / 100, $basePrice)
            : min((float) $request->input('discount_value'), $basePrice);
        $finalPrice = max($basePrice - $discountAmount, 0);

        return response()->json([
            'base_price' => $basePrice,
            'discount_amount' => $discountAmount,
            'final_price' => $finalPrice,
            'label' => sprintf(
                'Bs. %s - Bs. %s = Bs. %s',
                number_format($basePrice, 2, '.', ''),
                number_format($discountAmount, 2, '.', ''),
                number_format($finalPrice, 2, '.', '')
            ),
        ]);
    }

    private function generateUniqueSlug(string $name, ?int $ignoreId = null): string
    {
        $baseSlug = Str::slug($name);
        $slug = $baseSlug;
        $counter = 2;

        while (
            Promotion::withTrashed()
                ->where('slug', $slug)
                ->when($ignoreId, fn ($query) => $query->where('id', '!=', $ignoreId))
                ->exists()
        ) {
            $slug = $baseSlug.'-'.$counter;
            $counter++;
        }

        return $slug;
    }
}
