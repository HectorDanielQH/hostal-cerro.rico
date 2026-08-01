<?php

namespace App\Http\Controllers\AdminLte;

use App\Http\Controllers\Controller;
use App\Models\WorkShift;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Yajra\DataTables\Facades\DataTables;

class WorkShiftController extends Controller
{
    public function index(): View
    {
        abort_unless(auth()->user()?->can('usuarios.ver'), 403);

        return view('adminlte.work-shifts.index');
    }

    public function data(): JsonResponse
    {
        abort_unless(auth()->user()?->can('usuarios.ver'), 403);

        return DataTables::eloquent(WorkShift::query()->select('work_shifts.*'))
            ->addColumn('schedule_label', fn (WorkShift $workShift): string => $workShift->scheduleLabel())
            ->addColumn('users_count', fn (WorkShift $workShift): int => $workShift->users()->count())
            ->addColumn('status_label', fn (WorkShift $workShift): string => $workShift->is_active ? 'Activo' : 'Inactivo')
            ->addColumn('created_at_formatted', fn (WorkShift $workShift): string => optional($workShift->created_at)?->format('d/m/Y H:i') ?? '-')
            ->addColumn('can_update', fn (): bool => auth()->user()->can('usuarios.editar'))
            ->addColumn('can_delete', fn (): bool => auth()->user()->can('usuarios.eliminar'))
            ->addColumn('update_url', fn (WorkShift $workShift): string => route('adminlte.work-shifts.update', $workShift))
            ->addColumn('delete_url', fn (WorkShift $workShift): string => route('adminlte.work-shifts.destroy', $workShift))
            ->toJson();
    }

    public function store(Request $request): JsonResponse
    {
        abort_unless(auth()->user()?->can('usuarios.crear'), 403);

        WorkShift::create($this->validatedPayload($request));

        return response()->json([
            'message' => 'Turno registrado correctamente.',
        ]);
    }

    public function update(Request $request, WorkShift $workShift): JsonResponse
    {
        abort_unless(auth()->user()?->can('usuarios.editar'), 403);

        $workShift->update($this->validatedPayload($request, $workShift));

        return response()->json([
            'message' => 'Turno actualizado correctamente.',
        ]);
    }

    public function destroy(WorkShift $workShift): JsonResponse
    {
        abort_unless(auth()->user()?->can('usuarios.eliminar'), 403);

        if ($workShift->users()->exists()) {
            $workShift->update(['is_active' => false]);

            return response()->json([
                'message' => 'No se puede eliminar un turno asignado a usuarios. Se desactivo para conservar el historial.',
            ]);
        }

        $workShift->delete();

        return response()->json([
            'message' => 'Turno eliminado correctamente.',
        ]);
    }

    private function validatedPayload(Request $request, ?WorkShift $workShift = null): array
    {
        return $request->validate([
            'name' => [
                'required',
                'string',
                'max:100',
                Rule::unique('work_shifts', 'name')->ignore($workShift?->id),
            ],
            'starts_at' => ['required', 'date_format:H:i'],
            'ends_at' => ['required', 'date_format:H:i'],
            'description' => ['nullable', 'string', 'max:1000'],
            'is_active' => ['nullable', 'boolean'],
        ]);
    }
}
