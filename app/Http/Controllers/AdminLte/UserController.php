<?php

namespace App\Http\Controllers\AdminLte;

use App\Http\Controllers\Controller;
use App\Http\Requests\AdminLte\StoreUserRequest;
use App\Http\Requests\AdminLte\UpdateUserRequest;
use App\Models\Role;
use App\Models\User;
use App\Models\WorkShift;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Yajra\DataTables\Facades\DataTables;

class UserController extends Controller
{
    private const HIDDEN_ADMIN_ROLES = ['client'];

    public function index(): View
    {
        $this->authorize('viewAny', User::class);

        $staffQuery = User::query()
            ->whereDoesntHave('roles', fn ($query) => $query->whereIn('name', self::HIDDEN_ADMIN_ROLES));

        return view('admin.users.index', [
            'roles' => Role::query()
                ->whereNotIn('name', self::HIDDEN_ADMIN_ROLES)
                ->orderBy('name')
                ->get(),
            'workShifts' => WorkShift::query()
                ->where('is_active', true)
                ->orderBy('starts_at')
                ->orderBy('name')
                ->get(),
            'userStats' => [
                'total' => (clone $staffQuery)->count(),
                'active' => (clone $staffQuery)->where('is_active', true)->count(),
                'inactive' => (clone $staffQuery)->where('is_active', false)->count(),
                'receptionists' => (clone $staffQuery)->whereHas('roles', fn ($query) => $query->where('name', 'receptionist'))->count(),
            ],
        ]);
    }

    public function data(): JsonResponse
    {
        $this->authorize('viewAny', User::class);

        $query = User::query()
            ->with(['roles', 'workShift'])
            ->whereDoesntHave('roles', fn ($query) => $query->whereIn('name', self::HIDDEN_ADMIN_ROLES))
            ->select('users.*');

        return DataTables::eloquent($query)
            ->addColumn('avatar_initial', fn (User $user): string => strtoupper(substr($user->name, 0, 1)))
            ->addColumn('role_name', fn (User $user): ?string => $user->roles->first()?->name)
            ->addColumn('role_label', fn (User $user): ?string => $user->roles->first()?->label)
            ->addColumn('work_shift_id', fn (User $user): ?int => $user->work_shift_id)
            ->addColumn('work_shift_name', fn (User $user): ?string => $user->workShift?->name)
            ->addColumn('work_shift_schedule', fn (User $user): ?string => $user->workShift?->scheduleLabel())
            ->addColumn('status_label', fn (User $user): string => $user->is_active ? 'Activo' : 'Inactivo')
            ->addColumn('created_at_formatted', fn (User $user): string => optional($user->created_at)?->format('d/m/Y H:i') ?? '-')
            ->addColumn('can_update', fn (User $user): bool => auth()->user()->can('update', $user))
            ->addColumn('can_delete', fn (User $user): bool => auth()->user()->can('delete', $user))
            ->addColumn('update_url', fn (User $user): string => route('adminlte.users.update', $user))
            ->addColumn('delete_url', fn (User $user): string => route('adminlte.users.destroy', $user))
            ->toJson();
    }

    public function store(StoreUserRequest $request): JsonResponse
    {
        $this->authorize('create', User::class);

        $validated = $request->validated();

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => $validated['password'],
            'is_active' => (bool) ($validated['is_active'] ?? true),
            'work_shift_id' => $validated['role'] === 'receptionist' ? $validated['work_shift_id'] : null,
        ]);

        $user->syncRoles([$validated['role']]);

        return response()->json([
            'message' => 'Usuario creado correctamente.',
        ]);
    }

    public function update(UpdateUserRequest $request, User $user): JsonResponse
    {
        $this->authorize('update', $user);

        $validated = $request->validated();
        $isActive = (bool) ($validated['is_active'] ?? false);

        if (auth()->id() === $user->id && ! $isActive) {
            return response()->json([
                'message' => 'No puedes desactivarte a ti mismo.',
                'errors' => [
                    'is_active' => ['No puedes desactivarte a ti mismo.'],
                ],
            ], 422);
        }

        $payload = [
            'name' => $validated['name'],
            'email' => $validated['email'],
            'is_active' => $isActive,
            'work_shift_id' => $validated['role'] === 'receptionist' ? $validated['work_shift_id'] : null,
        ];

        if (! empty($validated['password'])) {
            $payload['password'] = $validated['password'];
        }

        $user->update($payload);
        $user->syncRoles([$validated['role']]);

        return response()->json([
            'message' => 'Usuario actualizado correctamente.',
        ]);
    }

    public function destroy(User $user): JsonResponse
    {
        $this->authorize('delete', $user);

        if (auth()->id() === $user->id) {
            return response()->json([
                'message' => 'No puedes desactivarte a ti mismo.',
                'errors' => [
                    'user' => ['No puedes desactivarte a ti mismo.'],
                ],
            ], 422);
        }

        $user->update([
            'is_active' => false,
        ]);

        return response()->json([
            'message' => 'Usuario desactivado correctamente.',
        ]);
    }
}
