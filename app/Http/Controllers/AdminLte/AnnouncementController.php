<?php

namespace App\Http\Controllers\AdminLte;

use App\Http\Controllers\Controller;
use App\Http\Requests\AdminLte\StoreAnnouncementRequest;
use App\Http\Requests\AdminLte\UpdateAnnouncementRequest;
use App\Models\Announcement;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Storage;
use Yajra\DataTables\Facades\DataTables;

class AnnouncementController extends Controller
{
    public function index(): View
    {
        abort_unless(auth()->user()->can('configuracion.ver'), 403);

        return view('adminlte.announcements.index', [
            'announcementStats' => [
                'total' => Announcement::query()->count(),
                'active' => Announcement::query()->where('is_active', true)->count(),
                'visible' => Announcement::query()->where('show_on_website', true)->count(),
                'modal' => Announcement::query()->where('show_as_modal', true)->count(),
            ],
        ]);
    }

    public function data(): JsonResponse
    {
        abort_unless(auth()->user()->can('configuracion.ver'), 403);

        return DataTables::eloquent(Announcement::query()->select('announcements.*'))
            ->addColumn('image_url', fn (Announcement $announcement): ?string => $announcement->image ? asset('storage/'.$announcement->image) : null)
            ->addColumn('status_label', function (Announcement $announcement): string {
                if (! $announcement->is_active) {
                    return 'Inactivo';
                }

                return 'Activo';
            })
            ->addColumn('modal_label', fn (Announcement $announcement): string => $announcement->show_as_modal ? 'Modal' : 'Oculto')
            ->addColumn('website_label', fn (Announcement $announcement): string => $announcement->show_on_website ? 'Visible' : 'Oculto')
            ->addColumn('date_range_label', function (Announcement $announcement): string {
                $start = $announcement->starts_at?->format('d/m/Y H:i') ?? 'Sin inicio';
                $end = $announcement->ends_at?->format('d/m/Y H:i') ?? 'Sin fin';

                return $start.' - '.$end;
            })
            ->addColumn('update_url', fn (Announcement $announcement): string => route('adminlte.announcements.update', $announcement))
            ->addColumn('delete_url', fn (Announcement $announcement): string => route('adminlte.announcements.destroy', $announcement))
            ->toJson();
    }

    public function store(StoreAnnouncementRequest $request): JsonResponse
    {
        abort_unless(auth()->user()->can('configuracion.editar'), 403);

        $validated = $this->preparePayload($request->validated());
        $validated['image'] = $request->hasFile('image')
            ? $request->file('image')->store('announcements', 'public')
            : null;

        Announcement::create($validated);

        return response()->json([
            'message' => 'Anuncio creado correctamente.',
        ]);
    }

    public function update(UpdateAnnouncementRequest $request, Announcement $announcement): JsonResponse
    {
        abort_unless(auth()->user()->can('configuracion.editar'), 403);

        $validated = $this->preparePayload($request->validated());
        $validated['image'] = $announcement->image;

        if ($request->hasFile('image')) {
            $validated['image'] = $request->file('image')->store('announcements', 'public');

            if ($announcement->image && Storage::disk('public')->exists($announcement->image)) {
                Storage::disk('public')->delete($announcement->image);
            }
        }

        $announcement->update($validated);

        return response()->json([
            'message' => 'Anuncio actualizado correctamente.',
        ]);
    }

    public function destroy(Announcement $announcement): JsonResponse
    {
        abort_unless(auth()->user()->can('configuracion.editar'), 403);

        if ($announcement->image && Storage::disk('public')->exists($announcement->image)) {
            Storage::disk('public')->delete($announcement->image);
        }

        $announcement->delete();

        return response()->json([
            'message' => 'Anuncio eliminado correctamente.',
        ]);
    }

    private function preparePayload(array $validated): array
    {
        $validated['sort_order'] = (int) ($validated['sort_order'] ?? 0);
        $validated['show_on_website'] = (bool) ($validated['show_on_website'] ?? false);
        $validated['show_as_modal'] = (bool) ($validated['show_as_modal'] ?? false);
        $validated['is_active'] = (bool) ($validated['is_active'] ?? false);

        return $validated;
    }
}
