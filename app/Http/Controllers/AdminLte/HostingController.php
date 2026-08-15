<?php

namespace App\Http\Controllers\AdminLte;

use App\Http\Controllers\Controller;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
use Throwable;

class HostingController extends Controller
{
    public function index(): View
    {
        return view('adminlte.hosting.index', [
            'actions' => $this->actions(),
            'status' => [
                'environment' => app()->environment(),
                'debug' => config('app.debug') ? 'Activo' : 'Inactivo',
                'app_url' => config('app.url'),
                'php_version' => PHP_VERSION,
                'database' => config('database.connections.'.config('database.default').'.database'),
                'storage_path' => storage_path('app/public'),
                'public_storage_path' => public_path('storage'),
                'storage_exists' => File::exists(public_path('storage')),
                'storage_is_link' => is_link(public_path('storage')),
            ],
        ]);
    }

    public function run(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'action' => ['required', 'string', 'in:'.implode(',', array_keys($this->actions()))],
        ]);

        $action = $this->actions()[$data['action']];

        try {
            $exitCode = Artisan::call($action['command'], $action['parameters']);
            $output = trim(Artisan::output());

            return back()->with('hosting_result', [
                'ok' => $exitCode === 0,
                'title' => $action['title'],
                'command' => $action['display_command'],
                'output' => $output !== '' ? $output : 'Comando ejecutado sin mensajes adicionales.',
                'exit_code' => $exitCode,
            ]);
        } catch (Throwable $exception) {
            report($exception);

            return back()->with('hosting_result', [
                'ok' => false,
                'title' => $action['title'],
                'command' => $action['display_command'],
                'output' => $exception->getMessage(),
                'exit_code' => 1,
            ]);
        }
    }

    private function actions(): array
    {
        return [
            'storage_link' => [
                'title' => 'Vincular imagenes',
                'subtitle' => 'Crea el enlace public/storage',
                'description' => 'Necesario en cPanel para que logos, habitaciones, anuncios y comprobantes carguen correctamente desde el navegador.',
                'command' => 'storage:link',
                'parameters' => [],
                'display_command' => 'php artisan storage:link',
                'icon' => 'bi bi-link-45deg',
                'tone' => 'primary',
                'warning' => 'Usalo despues de subir el sistema o cuando no se vean imagenes guardadas.',
            ],
            'migrate' => [
                'title' => 'Actualizar base de datos',
                'subtitle' => 'Ejecuta migraciones pendientes',
                'description' => 'Aplica nuevas tablas o columnas necesarias despues de subir cambios al hosting.',
                'command' => 'migrate',
                'parameters' => ['--force' => true],
                'display_command' => 'php artisan migrate --force',
                'icon' => 'bi bi-database-check',
                'tone' => 'danger',
                'warning' => 'Antes de usarlo en produccion conviene tener copia de seguridad de la base de datos.',
            ],
            'migrate_seed' => [
                'title' => 'Migrar y cargar base inicial',
                'subtitle' => 'Ejecuta migraciones y seeders',
                'description' => 'Actualiza la base de datos y carga solo la base interna: permisos, roles, turnos, configuracion minima y usuarios del sistema.',
                'command' => 'migrate',
                'parameters' => ['--seed' => true, '--force' => true],
                'display_command' => 'php artisan migrate --seed --force',
                'icon' => 'bi bi-database-fill-gear',
                'tone' => 'danger',
                'warning' => 'No borra datos. Aun asi, en produccion es mejor tener copia de seguridad antes de ejecutarlo.',
            ],
            'optimize_clear' => [
                'title' => 'Limpiar cache',
                'subtitle' => 'Reinicia configuracion temporal',
                'description' => 'Soluciona problemas cuando el hosting sigue mostrando rutas, vistas o configuraciones antiguas.',
                'command' => 'optimize:clear',
                'parameters' => [],
                'display_command' => 'php artisan optimize:clear',
                'icon' => 'bi bi-stars',
                'tone' => 'success',
                'warning' => 'Recomendado despues de modificar .env, rutas, vistas o configuracion.',
            ],
            'config_cache' => [
                'title' => 'Cachear configuracion',
                'subtitle' => 'Optimiza config en produccion',
                'description' => 'Guarda la configuracion de Laravel en cache para que el sistema responda mas rapido.',
                'command' => 'config:cache',
                'parameters' => [],
                'display_command' => 'php artisan config:cache',
                'icon' => 'bi bi-sliders2',
                'tone' => 'secondary',
                'warning' => 'Usalo solo cuando el archivo .env ya este correcto en cPanel.',
            ],
            'route_cache' => [
                'title' => 'Cachear rutas',
                'subtitle' => 'Optimiza el enrutamiento',
                'description' => 'Prepara las rutas para produccion y mejora el tiempo de respuesta.',
                'command' => 'route:cache',
                'parameters' => [],
                'display_command' => 'php artisan route:cache',
                'icon' => 'bi bi-signpost-split',
                'tone' => 'secondary',
                'warning' => 'Si alguna ruta cambia, luego ejecuta Limpiar cache y vuelve a cachear.',
            ],
            'view_cache' => [
                'title' => 'Cachear vistas',
                'subtitle' => 'Compila pantallas Blade',
                'description' => 'Prepara las vistas Blade para reducir errores de cache y mejorar carga inicial.',
                'command' => 'view:cache',
                'parameters' => [],
                'display_command' => 'php artisan view:cache',
                'icon' => 'bi bi-window-stack',
                'tone' => 'secondary',
                'warning' => 'Si cambias una vista, limpia cache o vuelve a ejecutar este proceso.',
            ],
        ];
    }
}
