@extends('adminlte::page')

@section('title', 'Hosting')

@section('content_header')
    <div class="hosting-hero">
        <div>
            <span class="hosting-eyebrow">Panel de despliegue</span>
            <h1 class="m-0">Hosting cPanel</h1>
            <p class="mb-0">Herramientas rapidas para dejar el sistema funcionando despues de subirlo al hosting.</p>
        </div>
        <div class="hosting-hero-badge">
            <i class="bi bi-shield-lock"></i>
            <span>Solo administrador</span>
        </div>
    </div>
@stop

@section('content')
    <div class="hosting-shell">
        @if (session('hosting_result'))
            @php($result = session('hosting_result'))
            <div class="alert {{ $result['ok'] ? 'alert-success' : 'alert-danger' }} hosting-result" role="alert">
                <div class="hosting-result-head">
                    <strong>{{ $result['ok'] ? 'Accion completada' : 'No se pudo completar' }}: {{ $result['title'] }}</strong>
                    <span>Codigo: {{ $result['exit_code'] }}</span>
                </div>
                <div class="hosting-command">{{ $result['command'] }}</div>
                <pre>{{ $result['output'] }}</pre>
            </div>
        @endif

        <div class="row g-3 mb-4">
            <div class="col-md-6 col-xl-3">
                <div class="hosting-status-card">
                    <i class="bi bi-globe2"></i>
                    <span>URL del sistema</span>
                    <strong>{{ $status['app_url'] ?: 'Sin configurar' }}</strong>
                    <small>APP_URL del archivo .env</small>
                </div>
            </div>
            <div class="col-md-6 col-xl-3">
                <div class="hosting-status-card">
                    <i class="bi bi-server"></i>
                    <span>Ambiente</span>
                    <strong>{{ $status['environment'] }}</strong>
                    <small>Debug: {{ $status['debug'] }}</small>
                </div>
            </div>
            <div class="col-md-6 col-xl-3">
                <div class="hosting-status-card">
                    <i class="bi bi-code-slash"></i>
                    <span>PHP</span>
                    <strong>{{ $status['php_version'] }}</strong>
                    <small>Version detectada por Laravel</small>
                </div>
            </div>
            <div class="col-md-6 col-xl-3">
                <div class="hosting-status-card {{ $status['storage_exists'] ? 'is-ok' : 'is-warning' }}">
                    <i class="bi bi-folder-symlink"></i>
                    <span>Imagenes publicas</span>
                    <strong>{{ $status['storage_exists'] ? 'Vinculado' : 'Pendiente' }}</strong>
                    <small>{{ $status['storage_is_link'] ? 'Enlace simbolico activo' : 'Revisar public/storage' }}</small>
                </div>
            </div>
        </div>

        <div class="hosting-notice">
            <i class="bi bi-info-circle"></i>
            <div>
                <strong>Orden recomendado al subir a cPanel</strong>
                <p>Primero configura el archivo <code>.env</code>, despues ejecuta <code>storage:link</code>, luego <code>migrate --force</code> y al final limpia/cachea si todo esta correcto.</p>
            </div>
        </div>

        <div class="row g-3">
            @foreach ($actions as $key => $action)
                <div class="col-lg-6 col-xl-4">
                    <div class="hosting-action-card is-{{ $action['tone'] }}">
                        <div class="hosting-action-icon">
                            <i class="{{ $action['icon'] }}"></i>
                        </div>
                        <div class="hosting-action-body">
                            <span>{{ $action['subtitle'] }}</span>
                            <h3>{{ $action['title'] }}</h3>
                            <p>{{ $action['description'] }}</p>
                            <div class="hosting-action-command">{{ $action['display_command'] }}</div>
                            <small>{{ $action['warning'] }}</small>
                        </div>
                        <form method="POST" action="{{ route('adminlte.hosting.run') }}" data-hosting-form data-hosting-title="{{ $action['title'] }}">
                            @csrf
                            <input type="hidden" name="action" value="{{ $key }}">
                            <button type="submit" class="btn hosting-action-btn">
                                <i class="bi bi-play-circle"></i>
                                Ejecutar
                            </button>
                        </form>
                    </div>
                </div>
            @endforeach
        </div>

        <div class="hosting-paths-card mt-4">
            <h2>Rutas importantes</h2>
            <p>Estos datos ayudan a revisar problemas tipicos de imagenes en cPanel.</p>
            <div class="row g-3">
                <div class="col-lg-6">
                    <label>Origen de archivos publicos</label>
                    <code>{{ $status['storage_path'] }}</code>
                </div>
                <div class="col-lg-6">
                    <label>Destino visible en navegador</label>
                    <code>{{ $status['public_storage_path'] }}</code>
                </div>
                <div class="col-lg-6">
                    <label>Base de datos configurada</label>
                    <code>{{ $status['database'] ?: 'Sin detectar' }}</code>
                </div>
                <div class="col-lg-6">
                    <label>Estado del enlace</label>
                    <code>{{ $status['storage_exists'] ? 'public/storage existe' : 'public/storage no existe' }}</code>
                </div>
            </div>
        </div>
    </div>
@stop

@push('css')
    <style>
        .hosting-hero {
            align-items: center;
            background:
                radial-gradient(circle at top right, rgba(37, 99, 235, .22), transparent 34%),
                linear-gradient(135deg, #101827 0%, #1f2937 48%, #312e81 100%);
            border-radius: 28px;
            box-shadow: 0 24px 60px rgba(15, 23, 42, .18);
            color: #fff;
            display: flex;
            justify-content: space-between;
            overflow: hidden;
            padding: 2rem;
        }

        .hosting-eyebrow {
            color: #bfdbfe;
            display: block;
            font-size: .78rem;
            font-weight: 800;
            letter-spacing: .16em;
            margin-bottom: .45rem;
            text-transform: uppercase;
        }

        .hosting-hero h1 {
            font-size: clamp(2rem, 4vw, 3.2rem);
            font-weight: 900;
        }

        .hosting-hero p {
            color: rgba(255, 255, 255, .78);
            max-width: 760px;
        }

        .hosting-hero-badge {
            align-items: center;
            backdrop-filter: blur(14px);
            background: rgba(255, 255, 255, .12);
            border: 1px solid rgba(255, 255, 255, .2);
            border-radius: 999px;
            display: inline-flex;
            font-weight: 800;
            gap: .55rem;
            padding: .85rem 1rem;
            white-space: nowrap;
        }

        .hosting-shell {
            padding-bottom: 2rem;
        }

        .hosting-result,
        .hosting-status-card,
        .hosting-notice,
        .hosting-action-card,
        .hosting-paths-card {
            border: 1px solid rgba(15, 23, 42, .08);
            border-radius: 24px;
            box-shadow: 0 18px 45px rgba(15, 23, 42, .08);
        }

        .hosting-result {
            margin-bottom: 1rem;
            padding: 1rem;
        }

        .hosting-result-head {
            align-items: center;
            display: flex;
            gap: 1rem;
            justify-content: space-between;
            margin-bottom: .75rem;
        }

        .hosting-command,
        .hosting-action-command {
            background: #0f172a;
            border-radius: 14px;
            color: #dbeafe;
            font-family: ui-monospace, SFMono-Regular, Consolas, monospace;
            font-size: .86rem;
            padding: .65rem .8rem;
        }

        .hosting-result pre {
            background: rgba(15, 23, 42, .06);
            border-radius: 14px;
            margin: .75rem 0 0;
            max-height: 320px;
            overflow: auto;
            padding: 1rem;
            white-space: pre-wrap;
        }

        .hosting-status-card {
            background: #fff;
            height: 100%;
            padding: 1.2rem;
        }

        .hosting-status-card i {
            color: #2563eb;
            font-size: 1.7rem;
        }

        .hosting-status-card span,
        .hosting-action-body span,
        .hosting-paths-card label {
            color: #64748b;
            display: block;
            font-size: .78rem;
            font-weight: 800;
            letter-spacing: .1em;
            margin-top: .65rem;
            text-transform: uppercase;
        }

        .hosting-status-card strong {
            color: #0f172a;
            display: block;
            font-size: 1.1rem;
            margin-top: .3rem;
            word-break: break-word;
        }

        .hosting-status-card small,
        .hosting-action-body small,
        .hosting-paths-card p {
            color: #64748b;
        }

        .hosting-status-card.is-ok i {
            color: #16a34a;
        }

        .hosting-status-card.is-warning i {
            color: #f59e0b;
        }

        .hosting-notice {
            align-items: flex-start;
            background: linear-gradient(135deg, #eff6ff, #fff);
            display: flex;
            gap: 1rem;
            margin-bottom: 1rem;
            padding: 1.1rem;
        }

        .hosting-notice i {
            color: #2563eb;
            font-size: 1.5rem;
        }

        .hosting-notice p {
            color: #475569;
            margin: .25rem 0 0;
        }

        .hosting-action-card {
            background: #fff;
            display: flex;
            flex-direction: column;
            gap: 1rem;
            height: 100%;
            padding: 1.25rem;
            position: relative;
        }

        .hosting-action-card::before {
            background: #2563eb;
            border-radius: 999px;
            content: "";
            height: 4px;
            left: 1.25rem;
            position: absolute;
            right: 1.25rem;
            top: 0;
        }

        .hosting-action-card.is-danger::before {
            background: #dc2626;
        }

        .hosting-action-card.is-success::before {
            background: #16a34a;
        }

        .hosting-action-card.is-secondary::before {
            background: #475569;
        }

        .hosting-action-icon {
            align-items: center;
            background: #eff6ff;
            border-radius: 20px;
            color: #2563eb;
            display: inline-flex;
            font-size: 1.7rem;
            height: 56px;
            justify-content: center;
            width: 56px;
        }

        .hosting-action-card.is-danger .hosting-action-icon {
            background: #fef2f2;
            color: #dc2626;
        }

        .hosting-action-card.is-success .hosting-action-icon {
            background: #ecfdf5;
            color: #16a34a;
        }

        .hosting-action-card.is-secondary .hosting-action-icon {
            background: #f1f5f9;
            color: #475569;
        }

        .hosting-action-body h3 {
            color: #0f172a;
            font-size: 1.35rem;
            font-weight: 900;
            margin: .35rem 0 .5rem;
        }

        .hosting-action-body p {
            color: #475569;
            min-height: 72px;
        }

        .hosting-action-btn {
            background: #111827;
            border: 0;
            border-radius: 16px;
            color: #fff;
            font-weight: 800;
            padding: .85rem 1rem;
            width: 100%;
        }

        .hosting-action-btn:hover {
            background: #2563eb;
            color: #fff;
        }

        .hosting-paths-card {
            background: #fff;
            padding: 1.25rem;
        }

        .hosting-paths-card h2 {
            color: #0f172a;
            font-size: 1.25rem;
            font-weight: 900;
        }

        .hosting-paths-card code {
            background: #f8fafc;
            border: 1px solid rgba(15, 23, 42, .08);
            border-radius: 14px;
            color: #0f172a;
            display: block;
            margin-top: .35rem;
            overflow: auto;
            padding: .75rem;
        }

        @media (max-width: 768px) {
            .hosting-hero,
            .hosting-result-head,
            .hosting-notice {
                display: block;
            }

            .hosting-hero-badge {
                margin-top: 1rem;
            }
        }
    </style>
@endpush

@push('js')
    <script>
        document.querySelectorAll('[data-hosting-form]').forEach((form) => {
            form.addEventListener('submit', async (event) => {
                const title = form.dataset.hostingTitle || 'esta accion';

                if (!window.Swal) {
                    if (!window.confirm(`Ejecutar ${title}?`)) {
                        event.preventDefault();
                    }

                    return;
                }

                event.preventDefault();

                const result = await Swal.fire({
                    title: `Ejecutar ${title}?`,
                    text: 'Esta accion se ejecutara directamente en Laravel. Revisa que el hosting y el .env esten correctos.',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonText: 'Si, ejecutar',
                    cancelButtonText: 'Cancelar',
                    confirmButtonColor: '#2563eb',
                });

                if (!result.isConfirmed) {
                    return;
                }

                Swal.fire({
                    title: 'Ejecutando...',
                    text: 'Espera un momento, Laravel esta procesando la accion.',
                    allowOutsideClick: false,
                    didOpen: () => Swal.showLoading(),
                });

                form.submit();
            });
        });
    </script>
@endpush
