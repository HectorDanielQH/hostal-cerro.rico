@extends('adminlte::page')

@section('title', 'Usuarios')

@section('content_header')
    <div class="users-hero">
        <div class="users-hero-copy">
            <span class="users-eyebrow">Administracion de accesos</span>
            <h1 class="m-0">Usuarios</h1>
            <p class="mb-0">Gestiona cuentas del personal, roles operativos y turnos de recepcion desde un panel claro y seguro.</p>
        </div>

        @can('usuarios.crear')
            <button type="button" class="btn users-hero-action" id="open-create-user-modal">
                <i class="bi bi-person-plus" aria-hidden="true"></i>
                Nuevo usuario
            </button>
        @endcan
    </div>
@stop

@section('content')
    <div class="users-shell">
        <div class="row g-3 mb-4">
            <div class="col-md-6 col-xl-3">
                <div class="users-stat-card">
                    <i class="bi bi-people"></i>
                    <span>Total personal</span>
                    <strong>{{ $userStats['total'] }}</strong>
                    <small>Usuarios internos registrados</small>
                </div>
            </div>
            <div class="col-md-6 col-xl-3">
                <div class="users-stat-card is-active">
                    <i class="bi bi-person-check"></i>
                    <span>Activos</span>
                    <strong>{{ $userStats['active'] }}</strong>
                    <small>Pueden iniciar sesion</small>
                </div>
            </div>
            <div class="col-md-6 col-xl-3">
                <div class="users-stat-card is-muted">
                    <i class="bi bi-person-slash"></i>
                    <span>Inactivos</span>
                    <strong>{{ $userStats['inactive'] }}</strong>
                    <small>Accesos deshabilitados</small>
                </div>
            </div>
            <div class="col-md-6 col-xl-3">
                <div class="users-stat-card is-shift">
                    <i class="bi bi-clock-history"></i>
                    <span>Recepcionistas</span>
                    <strong>{{ $userStats['receptionists'] }}</strong>
                    <small>Con control de turnos</small>
                </div>
            </div>
        </div>

        <div class="users-table-card">
            <div class="users-section-head">
                <div>
                    <span class="users-eyebrow">Directorio operativo</span>
                    <h3>Listado de usuarios</h3>
                    <p>Revisa roles, estado de acceso y turno asignado para recepcionistas.</p>
                </div>
            </div>
            <div class="table-responsive">
                <table class="table table-hover align-middle w-100 users-table" id="users-table">
                    <thead>
                        <tr>
                            <th>Usuario</th>
                            <th>Rol</th>
                            <th>Turno</th>
                            <th>Estado</th>
                            <th>Registro</th>
                            <th class="text-end">Acciones</th>
                        </tr>
                    </thead>
                </table>
            </div>
        </div>
    </div>

    <div class="modal fade" id="create-user-modal" tabindex="-1" aria-labelledby="create-user-modal-label" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-scrollable">
            <div class="modal-content">
                <form id="create-user-form" action="{{ route('adminlte.users.store') }}">
                    @csrf
                    <div class="modal-header">
                        <div>
                            <h5 class="modal-title" id="create-user-modal-label">Nuevo usuario</h5>
                            <small class="text-muted">Crea accesos y asigna el rol operativo desde este panel.</small>
                        </div>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                    </div>
                    <div class="modal-body">
                        <div class="user-form-banner mb-3">
                            <i class="bi bi-shield-lock"></i>
                            <div>
                                <strong>Cuenta interna del hotel</strong>
                                <span>Los clientes no deben iniciar sesion. Esta pantalla es solo para personal autorizado.</span>
                            </div>
                        </div>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label" for="create-user-name">Nombre</label>
                                <input type="text" class="form-control" id="create-user-name" name="name" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label" for="create-user-email">Correo electronico</label>
                                <input type="email" class="form-control" id="create-user-email" name="email" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label" for="create-user-password">Contrasena</label>
                                <input type="password" class="form-control" id="create-user-password" name="password" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label" for="create-user-password-confirmation">Confirmar contrasena</label>
                                <input type="password" class="form-control" id="create-user-password-confirmation" name="password_confirmation" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label" for="create-user-role">Rol</label>
                                <select class="form-select" id="create-user-role" name="role" required>
                                    <option value="">Selecciona un rol</option>
                                    @foreach ($roles as $role)
                                        <option value="{{ $role->name }}">{{ $role->label ?? $role->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-6 user-shift-field is-hidden">
                                <label class="form-label" for="create-user-work-shift-id">Turno de recepcion</label>
                                <select class="form-select" id="create-user-work-shift-id" name="work_shift_id" disabled>
                                    <option value="">Selecciona un turno</option>
                                    @foreach ($workShifts as $workShift)
                                        <option value="{{ $workShift->id }}">{{ $workShift->name }} - {{ $workShift->scheduleLabel() }}</option>
                                    @endforeach
                                </select>
                                <div class="form-text">Obligatorio cuando el rol sea Recepcionista.</div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label d-block">Estado</label>
                                <div class="form-check form-switch mt-2">
                                    <input class="form-check-input" type="checkbox" id="create-user-active" name="is_active" value="1" checked>
                                    <label class="form-check-label" for="create-user-active">Usuario activo</label>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-primary">Guardar usuario</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="modal fade" id="edit-user-modal" tabindex="-1" aria-labelledby="edit-user-modal-label" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-scrollable">
            <div class="modal-content">
                <form id="edit-user-form" method="POST">
                    @csrf
                    <input type="hidden" name="_method" value="PUT">
                    <div class="modal-header">
                        <div>
                            <h5 class="modal-title" id="edit-user-modal-label">Editar usuario</h5>
                            <small class="text-muted">Actualiza datos del usuario sin salir del listado.</small>
                        </div>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                    </div>
                    <div class="modal-body">
                        <div class="user-form-banner mb-3">
                            <i class="bi bi-person-gear"></i>
                            <div>
                                <strong>Actualizacion de acceso</strong>
                                <span>Si cambias el rol a Recepcionista, el turno sera obligatorio.</span>
                            </div>
                        </div>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label" for="edit-user-name">Nombre</label>
                                <input type="text" class="form-control" id="edit-user-name" name="name" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label" for="edit-user-email">Correo electronico</label>
                                <input type="email" class="form-control" id="edit-user-email" name="email" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label" for="edit-user-password">Nueva contrasena</label>
                                <input type="password" class="form-control" id="edit-user-password" name="password">
                                <div class="form-text">Dejalo vacio si no deseas cambiarla.</div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label" for="edit-user-password-confirmation">Confirmar nueva contrasena</label>
                                <input type="password" class="form-control" id="edit-user-password-confirmation" name="password_confirmation">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label" for="edit-user-role">Rol</label>
                                <select class="form-select" id="edit-user-role" name="role" required>
                                    <option value="">Selecciona un rol</option>
                                    @foreach ($roles as $role)
                                        <option value="{{ $role->name }}">{{ $role->label ?? $role->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-6 user-shift-field is-hidden">
                                <label class="form-label" for="edit-user-work-shift-id">Turno de recepcion</label>
                                <select class="form-select" id="edit-user-work-shift-id" name="work_shift_id" disabled>
                                    <option value="">Selecciona un turno</option>
                                    @foreach ($workShifts as $workShift)
                                        <option value="{{ $workShift->id }}">{{ $workShift->name }} - {{ $workShift->scheduleLabel() }}</option>
                                    @endforeach
                                </select>
                                <div class="form-text">Obligatorio cuando el rol sea Recepcionista.</div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label d-block">Estado</label>
                                <div class="form-check form-switch mt-2">
                                    <input class="form-check-input" type="checkbox" id="edit-user-active" name="is_active" value="1">
                                    <label class="form-check-label" for="edit-user-active">Usuario activo</label>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-primary">Actualizar usuario</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@stop

@push('css')
    <link rel="stylesheet" href="https://cdn.datatables.net/2.3.8/css/dataTables.bootstrap5.css">
    <style>
        :root {
            --users-ink: #172033;
            --users-muted: #667085;
            --users-line: rgba(15, 23, 42, .08);
            --users-blue: #2563eb;
            --users-green: #16a34a;
            --users-gold: #d6a23d;
            --users-red: #dc2626;
            --users-shadow: 0 24px 60px rgba(15, 23, 42, .12);
        }

        .users-hero {
            position: relative;
            overflow: hidden;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 1.5rem;
            min-height: 165px;
            padding: 1.8rem;
            border-radius: 30px;
            color: #fff;
            background:
                radial-gradient(circle at 12% 15%, rgba(214, 162, 61, .34), transparent 32%),
                radial-gradient(circle at 82% 20%, rgba(37, 99, 235, .28), transparent 34%),
                linear-gradient(135deg, #111827 0%, #24344e 52%, #0f172a 100%);
            box-shadow: var(--users-shadow);
        }

        .users-hero::after {
            content: "";
            position: absolute;
            right: -90px;
            bottom: -110px;
            width: 360px;
            height: 230px;
            border-radius: 999px;
            background: rgba(255, 255, 255, .1);
            transform: rotate(-12deg);
        }

        .users-hero-copy,
        .users-hero-action {
            position: relative;
            z-index: 1;
        }

        .users-hero h1 {
            font-size: clamp(2.3rem, 5vw, 4rem);
            font-weight: 850;
            letter-spacing: -.05em;
        }

        .users-hero p {
            max-width: 760px;
            color: rgba(255, 255, 255, .74);
        }

        .users-eyebrow {
            display: inline-flex;
            margin-bottom: .45rem;
            color: #f6d48e;
            font-size: .72rem;
            font-weight: 850;
            letter-spacing: .16em;
            text-transform: uppercase;
        }

        .users-hero-action {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: .55rem;
            min-height: 50px;
            padding: .8rem 1.15rem;
            border: 0;
            border-radius: 999px;
            color: #172033;
            background: linear-gradient(135deg, #f8d58d, #f4b740);
            font-weight: 850;
            box-shadow: 0 16px 32px rgba(214, 162, 61, .26);
        }

        .users-hero-action:hover {
            color: #172033;
            transform: translateY(-1px);
            box-shadow: 0 20px 38px rgba(214, 162, 61, .32);
        }

        .users-shell {
            margin-top: 1.5rem;
        }

        .users-stat-card {
            position: relative;
            overflow: hidden;
            min-height: 155px;
            padding: 1.2rem;
            border: 1px solid var(--users-line);
            border-radius: 26px;
            background: #fff;
            box-shadow: 0 18px 45px rgba(15, 23, 42, .08);
        }

        .users-stat-card::before {
            content: "";
            position: absolute;
            inset: 0;
            background: linear-gradient(135deg, rgba(37, 99, 235, .09), transparent 58%);
            pointer-events: none;
        }

        .users-stat-card span,
        .users-section-head p,
        .users-stat-card small {
            color: var(--users-muted);
        }

        .users-stat-card span,
        .users-table thead th,
        .users-section-head .users-eyebrow {
            font-size: .74rem;
            font-weight: 850;
            letter-spacing: .08em;
            text-transform: uppercase;
        }

        .users-stat-card strong {
            position: relative;
            display: block;
            margin-top: .7rem;
            color: var(--users-ink);
            font-size: 2.45rem;
            font-weight: 850;
            letter-spacing: -.06em;
            line-height: 1;
        }

        .users-stat-card small {
            position: relative;
            display: block;
            margin-top: .45rem;
        }

        .users-stat-card i {
            position: absolute;
            right: 1rem;
            bottom: .8rem;
            color: rgba(37, 99, 235, .14);
            font-size: 3.3rem;
        }

        .users-stat-card.is-active::before {
            background: linear-gradient(135deg, rgba(22, 163, 74, .11), transparent 58%);
        }

        .users-stat-card.is-active i {
            color: rgba(22, 163, 74, .18);
        }

        .users-stat-card.is-muted::before {
            background: linear-gradient(135deg, rgba(100, 116, 139, .12), transparent 58%);
        }

        .users-stat-card.is-muted i {
            color: rgba(100, 116, 139, .18);
        }

        .users-stat-card.is-shift::before {
            background: linear-gradient(135deg, rgba(214, 162, 61, .14), transparent 58%);
        }

        .users-stat-card.is-shift i {
            color: rgba(214, 162, 61, .22);
        }

        .users-table-card {
            padding: 1.15rem;
            border: 1px solid var(--users-line);
            border-radius: 30px;
            background: #fff;
            box-shadow: 0 18px 45px rgba(15, 23, 42, .08);
        }

        .users-section-head {
            display: flex;
            align-items: flex-end;
            justify-content: space-between;
            gap: 1rem;
            margin-bottom: 1rem;
            padding: .25rem .25rem 0;
        }

        .users-section-head .users-eyebrow {
            color: #8b5e13;
        }

        .users-section-head h3 {
            margin: 0;
            color: var(--users-ink);
            font-weight: 850;
            letter-spacing: -.04em;
        }

        .users-section-head p {
            margin: .25rem 0 0;
        }

        .users-table thead th {
            border-bottom: 0;
            color: #64748b;
            white-space: nowrap;
            background: #f8fafc;
        }

        .users-table tbody td {
            border-color: rgba(15, 23, 42, .06);
            padding-top: 1rem;
            padding-bottom: 1rem;
        }

        .user-avatar-token {
            width: 44px;
            height: 44px;
            border-radius: 16px;
            background: linear-gradient(135deg, rgba(37, 99, 235, .14), rgba(214, 162, 61, .18));
            color: var(--users-blue);
            box-shadow: inset 0 0 0 1px rgba(37, 99, 235, .12);
        }

        .user-role-badge,
        .user-shift-badge {
            display: inline-flex;
            align-items: center;
            gap: .4rem;
            border-radius: 999px;
            padding: .42rem .7rem;
            font-weight: 800;
        }

        .user-role-badge {
            color: #1e293b;
            background: #f8fafc;
            border: 1px solid rgba(15, 23, 42, .08);
        }

        .user-shift-badge {
            color: #92400e;
            background: rgba(214, 162, 61, .14);
        }

        .user-action-group {
            display: inline-flex;
            justify-content: flex-end;
            gap: .35rem;
        }

        .user-action-group .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 36px;
            height: 36px;
            border-radius: 12px;
        }

        .user-form-banner {
            display: flex;
            gap: .85rem;
            padding: 1rem;
            border: 1px solid rgba(37, 99, 235, .12);
            border-radius: 20px;
            color: #1e293b;
            background: linear-gradient(135deg, rgba(37, 99, 235, .08), rgba(214, 162, 61, .08));
        }

        .user-form-banner i {
            display: grid;
            place-items: center;
            flex: 0 0 42px;
            width: 42px;
            height: 42px;
            border-radius: 15px;
            color: var(--users-blue);
            background: #fff;
            box-shadow: 0 10px 24px rgba(15, 23, 42, .08);
        }

        .user-form-banner strong,
        .user-form-banner span {
            display: block;
        }

        .user-form-banner span {
            color: var(--users-muted);
        }

        .modal-content {
            border: 0;
            border-radius: 24px;
            box-shadow: 0 28px 70px rgba(15, 23, 42, .22);
        }

        .modal-header {
            border-bottom: 1px solid rgba(15, 23, 42, .08);
            background: linear-gradient(135deg, #f8fafc, #fff);
        }

        .modal-footer {
            border-top: 1px solid rgba(15, 23, 42, .08);
            background: #fff;
        }

        #create-user-modal .modal-dialog,
        #edit-user-modal .modal-dialog {
            height: calc(100vh - 3.5rem);
        }

        #create-user-modal .modal-content,
        #edit-user-modal .modal-content {
            max-height: 100%;
        }

        #create-user-modal form,
        #edit-user-modal form {
            display: flex;
            flex-direction: column;
            min-height: 0;
            height: 100%;
        }

        #create-user-modal .modal-body,
        #edit-user-modal .modal-body {
            min-height: 0;
            overflow-y: auto;
            overscroll-behavior: contain;
        }

        .user-shift-field {
            transition: opacity .2s ease, transform .2s ease;
        }

        .user-shift-field.is-hidden {
            display: none !important;
        }

        @media (max-width: 991.98px) {
            .users-hero {
                align-items: stretch;
                flex-direction: column;
            }

            .users-hero-action {
                width: 100%;
            }
        }

        @media (max-width: 575.98px) {
            .users-hero,
            .users-table-card,
            .users-stat-card {
                border-radius: 22px;
            }

            .users-hero {
                padding: 1.1rem;
            }
        }
    </style>
@endpush

@push('js')
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const $ = window.jQuery;
            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content ?? '';
            const createModalElement = document.getElementById('create-user-modal');
            const editModalElement = document.getElementById('edit-user-modal');
            const createModal = window.bootstrap ? new window.bootstrap.Modal(createModalElement) : null;
            const editModal = window.bootstrap ? new window.bootstrap.Modal(editModalElement) : null;
            const createForm = document.getElementById('create-user-form');
            const editForm = document.getElementById('edit-user-form');
            const swal = window.Swal ?? null;
            const receptionistRole = 'receptionist';

            if (typeof $ !== 'function') {
                console.error('jQuery no esta disponible para inicializar la tabla de usuarios.');
                return;
            }

            const usersTable = $('#users-table').DataTable({
                processing: true,
                serverSide: true,
                ajax: '{{ route('adminlte.users.data') }}',
                language: {
                    url: 'https://cdn.datatables.net/plug-ins/1.13.8/i18n/es-ES.json',
                },
                order: [[4, 'desc']],
                columns: [
                    {
                        data: 'name',
                        name: 'name',
                        render: (data, type, row) => {
                            if (type !== 'display') {
                                return data;
                            }

                            return `<div class="d-flex align-items-center gap-3">
                                <span class="user-avatar-token fw-semibold d-inline-flex align-items-center justify-content-center">
                                    ${row.avatar_initial}
                                </span>
                                <div>
                                    <div class="fw-semibold text-dark">${row.name}</div>
                                    <small class="text-muted">${row.email}</small>
                                </div>
                            </div>`;
                        }
                    },
                    {
                        data: 'role_name',
                        name: 'roles.name',
                        render: (data, type, row) => {
                            if (type !== 'display') {
                                return data || '';
                            }

                            if (!data) {
                                return '<span class="text-muted">Sin rol</span>';
                            }

                            return `<span class="user-role-badge"><i class="bi bi-person-badge"></i>${row.role_label || data}</span>`;
                        }
                    },
                    {
                        data: 'work_shift_name',
                        name: 'work_shift_id',
                        render: (data, type, row) => {
                            if (type !== 'display') {
                                return data || '';
                            }

                            if (row.role_name !== receptionistRole) {
                                return '<span class="text-muted">No aplica</span>';
                            }

                            if (!data) {
                                return '<span class="badge text-bg-warning">Sin turno</span>';
                            }

                            return `<div class="user-shift-badge"><i class="bi bi-clock"></i>${data}</div><small class="d-block mt-1 text-muted">${row.work_shift_schedule || ''}</small>`;
                        }
                    },
                    {
                        data: 'is_active',
                        name: 'is_active',
                        className: 'text-center',
                        render: (data, type, row) => {
                            if (type !== 'display') {
                                return data ? 1 : 0;
                            }

                            const badgeClass = data ? 'text-bg-success' : 'text-bg-secondary';
                            const icon = data ? 'bi-check-circle' : 'bi-slash-circle';
                            return `<span class="badge rounded-pill ${badgeClass}"><i class="bi ${icon} me-1"></i>${row.status_label}</span>`;
                        }
                    },
                    { data: 'created_at_formatted', name: 'created_at' },
                    {
                        data: null,
                        orderable: false,
                        searchable: false,
                        className: 'text-end',
                        render: (data, type, row) => {
                            if (type !== 'display') {
                                return '';
                            }

                            let actions = '<div class="user-action-group" role="group">';

                            if (row.can_update) {
                                actions += `<button type="button" class="btn btn-outline-primary user-edit-btn" title="Editar usuario" data-user="${encodeURIComponent(JSON.stringify(row))}">
                                    <i class="bi bi-pencil-square"></i>
                                </button>`;
                            }

                            if (row.can_delete) {
                                actions += `<button type="button" class="btn btn-outline-danger user-delete-btn" title="Desactivar usuario" data-url="${row.delete_url}" data-name="${row.name}">
                                    <i class="bi bi-person-dash"></i>
                                </button>`;
                            }

                            actions += '</div>';

                            return actions;
                        }
                    }
                ],
            });

            document.getElementById('open-create-user-modal')?.addEventListener('click', () => {
                resetUserForm(createForm, true);
                createModal?.show();
            });

            createForm.querySelector('[name="role"]')?.addEventListener('change', () => toggleShiftField(createForm));
            editForm.querySelector('[name="role"]')?.addEventListener('change', () => toggleShiftField(editForm));

            createForm.addEventListener('submit', async (event) => {
                event.preventDefault();
                await submitUserForm(createForm, createForm.action, 'POST', createModal);
            });

            editForm.addEventListener('submit', async (event) => {
                event.preventDefault();
                await submitUserForm(editForm, editForm.action, 'POST', editModal);
            });

            document.addEventListener('click', async (event) => {
                const editButton = event.target.closest('.user-edit-btn');
                if (editButton) {
                    fillEditForm(JSON.parse(decodeURIComponent(editButton.dataset.user)));
                    editModal?.show();
                    return;
                }

                const deleteButton = event.target.closest('.user-delete-btn');
                if (deleteButton) {
                    await deactivateUser(deleteButton.dataset.url, deleteButton.dataset.name);
                }
            });

            function fillEditForm(user) {
                resetUserForm(editForm, false);
                editForm.action = user.update_url;
                editForm.querySelector('[name="name"]').value = user.name ?? '';
                editForm.querySelector('[name="email"]').value = user.email ?? '';
                editForm.querySelector('[name="role"]').value = user.role_name ?? '';
                editForm.querySelector('[name="work_shift_id"]').value = user.work_shift_id ?? '';
                editForm.querySelector('[name="is_active"]').checked = !!user.is_active;
                toggleShiftField(editForm);
            }

            function resetUserForm(form, defaultActive) {
                form.reset();
                const activeField = form.querySelector('[name="is_active"]');
                if (activeField) {
                    activeField.checked = defaultActive;
                }

                toggleShiftField(form);
            }

            function toggleShiftField(form) {
                const roleField = form.querySelector('[name="role"]');
                const shiftWrapper = form.querySelector('.user-shift-field');
                const shiftField = form.querySelector('[name="work_shift_id"]');
                const isReceptionist = roleField?.value === receptionistRole;

                shiftWrapper?.classList.toggle('is-hidden', !isReceptionist);

                if (shiftField) {
                    shiftField.disabled = !isReceptionist;
                    shiftField.required = isReceptionist;

                    if (!isReceptionist) {
                        shiftField.value = '';
                    }
                }
            }

            async function submitUserForm(form, url, method, modalInstance) {
                const formData = new FormData(form);

                if (!form.querySelector('[name="is_active"]').checked) {
                    formData.delete('is_active');
                    formData.append('is_active', '0');
                }

                const response = await fetch(url, {
                    method,
                    headers: {
                        'X-CSRF-TOKEN': csrfToken,
                        'Accept': 'application/json',
                    },
                    body: formData,
                });

                if (!response.ok) {
                    await handleRequestError(response);
                    return;
                }

                const payload = await response.json();

                modalInstance?.hide();
                usersTable.ajax.reload(null, false);

                await fireAlert({
                    icon: 'success',
                    title: payload.message || 'Operacion completada.',
                    timer: 1800,
                    showConfirmButton: false,
                });
            }

            async function deactivateUser(url, userName) {
                const confirmation = await fireAlert({
                    icon: 'warning',
                    title: 'Desactivar usuario?',
                    text: 'El usuario no podra iniciar sesion.',
                    showCancelButton: true,
                    confirmButtonText: 'Si, desactivar',
                    cancelButtonText: 'Cancelar',
                    confirmButtonColor: '#dc3545',
                    cancelButtonColor: '#6c757d',
                }, true);

                if (!confirmation.isConfirmed) {
                    return;
                }

                const formData = new FormData();
                formData.append('_method', 'DELETE');

                const response = await fetch(url, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': csrfToken,
                        'Accept': 'application/json',
                    },
                    body: formData,
                });

                if (!response.ok) {
                    await handleRequestError(response);
                    return;
                }

                const payload = await response.json();
                usersTable.ajax.reload(null, false);

                await fireAlert({
                    icon: 'success',
                    title: payload.message || `Usuario ${userName} desactivado correctamente.`,
                    timer: 1800,
                    showConfirmButton: false,
                });
            }

            async function handleRequestError(response) {
                let message = 'Ocurrio un error inesperado.';

                try {
                    const payload = await response.json();
                    if (response.status === 422 && payload.errors) {
                        const errors = Object.values(payload.errors).flat();
                        message = `<ul class="text-start mb-0">${errors.map((error) => `<li>${error}</li>`).join('')}</ul>`;
                    } else if (payload.message) {
                        message = payload.message;
                    }
                } catch (error) {
                    message = 'No fue posible procesar la respuesta del servidor.';
                }

                await fireAlert({
                    icon: 'error',
                    title: 'No se pudo completar la accion',
                    html: message,
                });
            }

            async function fireAlert(options, confirmFallback = false) {
                if (swal) {
                    return swal.fire(options);
                }

                if (confirmFallback) {
                    return { isConfirmed: window.confirm(options.text || options.title || '') };
                }

                window.alert(options.text || options.title || '');
                return { isConfirmed: true };
            }
        });
    </script>
@endpush
