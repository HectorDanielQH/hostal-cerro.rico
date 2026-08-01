<?php

namespace Database\Seeders;

use App\Models\Permission;
use Illuminate\Database\Seeder;
use Spatie\Permission\PermissionRegistrar;

class PermissionSeeder extends Seeder
{
    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $permissions = [
            'dashboard.ver',
            'usuarios.ver',
            'usuarios.crear',
            'usuarios.editar',
            'usuarios.eliminar',
            'roles.ver',
            'roles.crear',
            'roles.editar',
            'roles.eliminar',
            'configuracion.ver',
            'configuracion.editar',
            'tipos_habitacion.ver',
            'tipos_habitacion.crear',
            'tipos_habitacion.editar',
            'tipos_habitacion.eliminar',
            'habitaciones.ver',
            'habitaciones.crear',
            'habitaciones.editar',
            'habitaciones.eliminar',
            'habitaciones.estado',
            'clientes.ver',
            'clientes.ver_propios',
            'clientes.crear',
            'clientes.editar',
            'clientes.eliminar',
            'reservas.ver',
            'reservas.ver_propias',
            'reservas.crear',
            'reservas.editar',
            'reservas.cancelar',
            'reservas.confirmar',
            'reservas.checkin',
            'reservas.checkout',
            'reservas.aplicar_descuento',
            'reservas.cambiar_precio',
            'pagos.ver',
            'pagos.crear',
            'pagos.confirmar',
            'pagos.rechazar',
            'pagos.anular',
            'pagos.devolver',
            'pagos.cambiar_monto',
            'pagos.ver_propios',
            'pagos.realizar',
            'pagos.subir_comprobante',
            'caja.ver',
            'caja.abrir',
            'caja.cerrar',
            'caja.arqueo',
            'caja.ver_todos',
            'caja.ajustar',
            'promociones.ver',
            'promociones.crear',
            'promociones.editar',
            'promociones.eliminar',
            'reportes.ver',
            'reportes.exportar',
            'perfil.ver',
            'perfil.editar',
        ];

        foreach ($permissions as $name) {
            Permission::firstOrCreate([
                'name' => $name,
                'guard_name' => 'web',
            ]);
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
