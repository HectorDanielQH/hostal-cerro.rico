<?php

namespace App\Http\Controllers\AdminLte;

use App\Http\Controllers\Controller;
use App\Http\Requests\AdminLte\StoreRoleRequest;
use App\Http\Requests\AdminLte\UpdateRoleRequest;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;
use Spatie\Permission\PermissionRegistrar;
use Yajra\DataTables\Facades\DataTables;

class RoleController extends Controller
{
    private const HIDDEN_ADMIN_ROLES = ['client'];

    private const ADMIN_CRITICAL_PERMISSIONS = [
        'dashboard.ver',
        'roles.ver',
        'roles.editar',
        'usuarios.ver',
    ];

    public function index(): View
    {
        $this->authorize('viewAny', Role::class);

        $rolesQuery = Role::query()->whereNotIn('name', self::HIDDEN_ADMIN_ROLES);

        return view('admin.roles.index', [
            'permissionGroups' => $this->permissionGroups(),
            'roleStats' => [
                'total' => (clone $rolesQuery)->count(),
                'system' => (clone $rolesQuery)->whereIn('name', ['admin', 'manager', 'receptionist'])->count(),
                'permissions' => Permission::query()->count(),
                'assignedUsers' => User::query()
                    ->whereHas('roles', fn ($query) => $query->whereNotIn('name', self::HIDDEN_ADMIN_ROLES))
                    ->count(),
            ],
        ]);
    }

    public function data(): JsonResponse
    {
        $this->authorize('viewAny', Role::class);

        $query = Role::query()
            ->with('permissions')
            ->withCount('permissions')
            ->withCount('users')
            ->whereNotIn('name', self::HIDDEN_ADMIN_ROLES);

        return DataTables::eloquent($query)
            ->addColumn('visual_name', fn (Role $role): string => $this->visualRoleName($role))
            ->addColumn('created_at_formatted', fn (Role $role): string => optional($role->created_at)?->format('d/m/Y H:i') ?? '-')
            ->addColumn('permissions', function (Role $role): array {
                return $role->permissions
                    ->map(fn (Permission $permission): array => [
                        'name' => $permission->name,
                        'label' => $permission->label,
                    ])
                    ->values()
                    ->all();
            })
            ->addColumn('permissions_names', fn (Role $role): array => $role->permissions->pluck('name')->values()->all())
            ->addColumn('can_update', fn (Role $role): bool => auth()->user()->can('update', $role))
            ->addColumn('can_delete', fn (Role $role): bool => auth()->user()->can('delete', $role))
            ->addColumn('update_url', fn (Role $role): string => route('adminlte.roles.update', $role))
            ->addColumn('delete_url', fn (Role $role): string => route('adminlte.roles.destroy', $role))
            ->toJson();
    }

    public function store(StoreRoleRequest $request): JsonResponse
    {
        $this->authorize('create', Role::class);

        $validated = $request->validated();

        if (in_array($validated['name'], self::HIDDEN_ADMIN_ROLES, true)) {
            throw ValidationException::withMessages([
                'name' => ['Este rol es interno del portal cliente y no se administra desde este modulo.'],
            ]);
        }

        $attributes = [
            'name' => $validated['name'],
            'guard_name' => 'web',
        ];

        if ($this->hasLabelColumn()) {
            $attributes['label'] = $validated['label'] ?? null;
        }

        $role = Role::create($attributes);
        $role->syncPermissions($validated['permissions'] ?? []);

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        return response()->json([
            'message' => 'Rol creado correctamente.',
        ]);
    }

    public function update(UpdateRoleRequest $request, Role $role): JsonResponse
    {
        $this->authorize('update', $role);

        if (in_array($role->name, self::HIDDEN_ADMIN_ROLES, true)) {
            throw ValidationException::withMessages([
                'role' => ['Este rol es interno del portal cliente y no se administra desde este modulo.'],
            ]);
        }

        $validated = $request->validated();
        $permissions = collect($validated['permissions'] ?? [])->values()->all();

        if (in_array($validated['name'], self::HIDDEN_ADMIN_ROLES, true)) {
            throw ValidationException::withMessages([
                'name' => ['Este rol es interno del portal cliente y no se administra desde este modulo.'],
            ]);
        }

        if ($role->name === 'admin') {
            $validated['name'] = 'admin';
            $this->ensureAdminKeepsCriticalPermissions($permissions);
        }

        $attributes = [
            'name' => $validated['name'],
        ];

        if ($this->hasLabelColumn()) {
            $attributes['label'] = $validated['label'] ?? null;
        }

        $role->update($attributes);
        $role->syncPermissions($permissions);

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        return response()->json([
            'message' => 'Rol actualizado correctamente.',
        ]);
    }

    public function destroy(Role $role): JsonResponse
    {
        abort_unless(auth()->user()?->can('roles.eliminar'), 403);

        if ($role->name === 'admin' || in_array($role->name, self::HIDDEN_ADMIN_ROLES, true)) {
            throw ValidationException::withMessages([
                'role' => ['Este rol del sistema no puede eliminarse desde administracion.'],
            ]);
        }

        if ($role->users()->exists()) {
            throw ValidationException::withMessages([
                'role' => ['No puedes eliminar un rol con usuarios asignados.'],
            ]);
        }

        $role->delete();

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        return response()->json([
            'message' => 'Rol eliminado correctamente.',
        ]);
    }

    private function permissionGroups(): array
    {
        $definitions = [
            'Dashboard' => ['dashboard.ver'],
            'Usuarios' => ['usuarios.ver', 'usuarios.crear', 'usuarios.editar', 'usuarios.eliminar'],
            'Roles y permisos' => ['roles.ver', 'roles.crear', 'roles.editar', 'roles.eliminar'],
            'Configuracion' => ['configuracion.ver', 'configuracion.editar'],
            'Tipos de habitacion' => ['tipos_habitacion.ver', 'tipos_habitacion.crear', 'tipos_habitacion.editar', 'tipos_habitacion.eliminar'],
            'Habitaciones' => ['habitaciones.ver', 'habitaciones.crear', 'habitaciones.editar', 'habitaciones.eliminar', 'habitaciones.estado'],
            'Clientes' => ['clientes.ver', 'clientes.ver_propios', 'clientes.crear', 'clientes.editar', 'clientes.eliminar'],
            'Reservas' => ['reservas.ver', 'reservas.ver_propias', 'reservas.crear', 'reservas.editar', 'reservas.cancelar', 'reservas.confirmar', 'reservas.checkin', 'reservas.checkout', 'reservas.aplicar_descuento', 'reservas.cambiar_precio'],
            'Pagos' => ['pagos.ver', 'pagos.crear', 'pagos.confirmar', 'pagos.rechazar', 'pagos.anular', 'pagos.devolver', 'pagos.cambiar_monto', 'pagos.ver_propios', 'pagos.realizar', 'pagos.subir_comprobante'],
            'Caja' => ['caja.ver', 'caja.abrir', 'caja.cerrar', 'caja.arqueo', 'caja.ver_todos', 'caja.ajustar'],
            'Promociones' => ['promociones.ver', 'promociones.crear', 'promociones.editar', 'promociones.eliminar'],
            'Reportes' => ['reportes.ver', 'reportes.exportar'],
            'Perfil' => ['perfil.ver', 'perfil.editar'],
        ];

        $permissions = Permission::query()
            ->orderBy('name')
            ->get()
            ->keyBy('name');

        $groups = [];

        foreach ($definitions as $group => $names) {
            $items = collect($names)
                ->map(fn (string $name): ?Permission => $permissions->get($name))
                ->filter()
                ->values();

            if ($items->isNotEmpty()) {
                $groups[$group] = $items;
            }
        }

        return $groups;
    }

    private function ensureAdminKeepsCriticalPermissions(array $permissions): void
    {
        $missing = collect(self::ADMIN_CRITICAL_PERMISSIONS)
            ->reject(fn (string $permission): bool => in_array($permission, $permissions, true))
            ->values();

        if ($missing->isNotEmpty()) {
            throw ValidationException::withMessages([
                'permissions' => [
                    'El rol admin no puede quedar sin los permisos criticos: '.$missing->implode(', ').'.',
                ],
            ]);
        }
    }

    private function hasLabelColumn(): bool
    {
        return Schema::hasColumn((new Role())->getTable(), 'label');
    }

    private function visualRoleName(Role $role): string
    {
        return match ($role->name) {
            'admin' => 'Administrador',
            'manager' => 'Gerente',
            'receptionist' => 'Recepcionista',
            default => $role->label ?: $role->name,
        };
    }
}
