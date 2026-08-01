<?php

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\PermissionRegistrar;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $hasLabel = Schema::hasColumn(config('permission.table_names.roles'), 'label');

        $roles = [
            'admin' => 'Administrator',
            'manager' => 'Gerente',
            'receptionist' => 'Recepcionista',
            'client' => 'Cliente',
        ];

        foreach ($roles as $name => $label) {
            $attributes = ['name' => $name, 'guard_name' => 'web'];

            if ($hasLabel) {
                $attributes['label'] = $label;
            }

            Role::updateOrCreate(
                ['name' => $name, 'guard_name' => 'web'],
                $attributes
            );
        }

        $admin = Role::findByName('admin', 'web');
        $manager = Role::findByName('manager', 'web');
        $receptionist = Role::findByName('receptionist', 'web');
        $client = Role::findByName('client', 'web');

        $allPermissions = Permission::query()->pluck('name')->all();

        $admin->syncPermissions($allPermissions);

        $manager->syncPermissions([
            'dashboard.ver',
            'configuracion.ver',
            'tipos_habitacion.ver',
            'habitaciones.ver',
            'clientes.ver',
            'reservas.ver',
            'reservas.confirmar',
            'reservas.cancelar',
            'reservas.aplicar_descuento',
            'pagos.ver',
            'pagos.confirmar',
            'pagos.rechazar',
            'pagos.anular',
            'pagos.devolver',
            'caja.ver',
            'caja.arqueo',
            'caja.ver_todos',
            'promociones.ver',
            'reportes.ver',
            'reportes.exportar',
            'perfil.ver',
            'perfil.editar',
        ]);

        $receptionist->syncPermissions([
            'dashboard.ver',
            'tipos_habitacion.ver',
            'habitaciones.ver',
            'habitaciones.estado',
            'clientes.ver',
            'clientes.crear',
            'clientes.editar',
            'reservas.ver',
            'reservas.crear',
            'reservas.editar',
            'reservas.cancelar',
            'reservas.confirmar',
            'reservas.checkin',
            'reservas.checkout',
            'pagos.ver',
            'pagos.crear',
            'pagos.confirmar',
            'pagos.rechazar',
            'caja.ver',
            'caja.abrir',
            'caja.cerrar',
            'caja.arqueo',
            'perfil.ver',
            'perfil.editar',
        ]);

        $client->syncPermissions([
            'reservas.crear',
            'reservas.ver_propias',
            'reservas.cancelar',
            'pagos.ver_propios',
            'pagos.realizar',
            'pagos.subir_comprobante',
            'clientes.ver_propios',
            'perfil.ver',
            'perfil.editar',
        ]);

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
