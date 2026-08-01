@extends('adminlte::page')

@section('title', 'Roles y permisos')

@section('content_header')
    <div class="roles-hero">
        <div class="roles-hero-copy">
            <span class="roles-eyebrow">Centro de seguridad</span>
            <h1 class="m-0">Roles y permisos</h1>
            <p class="mb-0">Define que puede hacer cada perfil del hotel y protege las operaciones criticas del sistema.</p>
        </div>

        @can('roles.crear')
            <button type="button" class="btn roles-hero-action" id="open-create-role-modal">
                <i class="bi bi-plus-circle" aria-hidden="true"></i>
                Nuevo rol
            </button>
        @endcan
    </div>
@stop

@section('content')
    <div class="roles-shell">
        <div class="row g-3 mb-4">
            <div class="col-md-6 col-xl-3">
                <div class="roles-stat-card">
                    <i class="bi bi-shield-check"></i>
                    <span>Roles activos</span>
                    <strong>{{ $roleStats['total'] }}</strong>
                    <small>Perfiles internos administrables</small>
                </div>
            </div>
            <div class="col-md-6 col-xl-3">
                <div class="roles-stat-card is-system">
                    <i class="bi bi-diagram-3"></i>
                    <span>Roles base</span>
                    <strong>{{ $roleStats['system'] }}</strong>
                    <small>Admin, gerente y recepcion</small>
                </div>
            </div>
            <div class="col-md-6 col-xl-3">
                <div class="roles-stat-card is-permission">
                    <i class="bi bi-key"></i>
                    <span>Permisos</span>
                    <strong>{{ $roleStats['permissions'] }}</strong>
                    <small>Acciones disponibles del sistema</small>
                </div>
            </div>
            <div class="col-md-6 col-xl-3">
                <div class="roles-stat-card is-users">
                    <i class="bi bi-people"></i>
                    <span>Usuarios asignados</span>
                    <strong>{{ $roleStats['assignedUsers'] }}</strong>
                    <small>Personal con rol operativo</small>
                </div>
            </div>
        </div>

        <div class="roles-table-card">
            <div class="roles-section-head">
                <div>
                    <span class="roles-eyebrow">Matriz de permisos</span>
                    <h3>Listado de roles</h3>
                    <p>Controla etiquetas, cantidad de permisos y usuarios vinculados a cada perfil.</p>
                </div>
            </div>
            <div class="table-responsive">
                <table class="table table-hover align-middle w-100 roles-table" id="roles-table">
                    <thead>
                        <tr>
                            <th>Rol</th>
                            <th>Etiqueta</th>
                            <th>Permisos</th>
                            <th>Usuarios</th>
                            <th>Fecha</th>
                            <th class="text-end">Acciones</th>
                        </tr>
                    </thead>
                </table>
            </div>
        </div>
    </div>

    <div class="modal fade" id="create-role-modal" tabindex="-1" aria-labelledby="create-role-modal-label" aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-scrollable">
            <div class="modal-content">
                <form id="create-role-form" action="{{ route('adminlte.roles.store') }}">
                    @csrf
                    <div class="modal-header">
                        <div>
                            <h5 class="modal-title" id="create-role-modal-label">Nuevo rol</h5>
                            <small class="text-muted">Crea el rol y asigna permisos por modulo.</small>
                        </div>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                    </div>
                    <div class="modal-body">
                        <div class="role-form-banner mb-3">
                            <i class="bi bi-shield-lock"></i>
                            <div>
                                <strong>Permisos del personal interno</strong>
                                <span>El rol cliente no se administra aqui porque el cliente no inicia sesion en el panel.</span>
                            </div>
                        </div>
                        <div class="row g-3 mb-4">
                            <div class="col-md-6">
                                <label class="form-label" for="create-role-name">Nombre interno</label>
                                <input type="text" class="form-control" id="create-role-name" name="name" placeholder="manager" required>
                                <div class="form-text">Usa nombres internos para personal, por ejemplo `admin`, `manager` o `receptionist`.</div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label" for="create-role-label">Etiqueta visual</label>
                                <input type="text" class="form-control" id="create-role-label" name="label" placeholder="Gerente">
                            </div>
                        </div>

                        @include('admin.roles.partials.permission-groups', ['formPrefix' => 'create'])
                    </div>
                    <div class="modal-footer d-flex justify-content-between">
                        <span class="text-muted">Permisos seleccionados: <strong data-selected-counter="create">0</strong></span>
                        <div class="d-flex gap-2">
                            <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
                            <button type="submit" class="btn btn-primary">Guardar rol</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="modal fade" id="edit-role-modal" tabindex="-1" aria-labelledby="edit-role-modal-label" aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-scrollable">
            <div class="modal-content">
                <form id="edit-role-form" method="POST">
                    @csrf
                    <input type="hidden" name="_method" value="PUT">
                    <div class="modal-header">
                        <div>
                            <h5 class="modal-title" id="edit-role-modal-label">Editar rol</h5>
                            <small class="text-muted">Actualiza datos y permisos sin salir del listado.</small>
                        </div>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                    </div>
                    <div class="modal-body">
                        <div class="role-form-banner mb-3">
                            <i class="bi bi-key"></i>
                            <div>
                                <strong>Editar permisos con cuidado</strong>
                                <span>Los cambios afectan de inmediato a todos los usuarios asignados a este rol.</span>
                            </div>
                        </div>
                        <div class="row g-3 mb-4">
                            <div class="col-md-6">
                                <label class="form-label" for="edit-role-name">Nombre interno</label>
                                <input type="text" class="form-control" id="edit-role-name" name="name" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label" for="edit-role-label">Etiqueta visual</label>
                                <input type="text" class="form-control" id="edit-role-label" name="label">
                            </div>
                        </div>

                        @include('admin.roles.partials.permission-groups', ['formPrefix' => 'edit'])
                    </div>
                    <div class="modal-footer d-flex justify-content-between">
                        <span class="text-muted">Permisos seleccionados: <strong data-selected-counter="edit">0</strong></span>
                        <div class="d-flex gap-2">
                            <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
                            <button type="submit" class="btn btn-primary">Actualizar rol</button>
                        </div>
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
            --roles-ink: #172033;
            --roles-muted: #667085;
            --roles-line: rgba(15, 23, 42, .08);
            --roles-blue: #2563eb;
            --roles-green: #16a34a;
            --roles-gold: #d6a23d;
            --roles-red: #dc2626;
            --roles-shadow: 0 24px 60px rgba(15, 23, 42, .12);
        }

        .roles-hero {
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
                linear-gradient(135deg, #101827 0%, #253757 52%, #0f172a 100%);
            box-shadow: var(--roles-shadow);
        }

        .roles-hero::after {
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

        .roles-hero-copy,
        .roles-hero-action {
            position: relative;
            z-index: 1;
        }

        .roles-hero h1 {
            font-size: clamp(2.3rem, 5vw, 4rem);
            font-weight: 850;
            letter-spacing: -.05em;
        }

        .roles-hero p {
            max-width: 760px;
            color: rgba(255, 255, 255, .74);
        }

        .roles-eyebrow {
            display: inline-flex;
            margin-bottom: .45rem;
            color: #f6d48e;
            font-size: .72rem;
            font-weight: 850;
            letter-spacing: .16em;
            text-transform: uppercase;
        }

        .roles-hero-action {
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

        .roles-hero-action:hover {
            color: #172033;
            transform: translateY(-1px);
            box-shadow: 0 20px 38px rgba(214, 162, 61, .32);
        }

        .roles-shell {
            margin-top: 1.5rem;
        }

        .roles-stat-card {
            position: relative;
            overflow: hidden;
            min-height: 155px;
            padding: 1.2rem;
            border: 1px solid var(--roles-line);
            border-radius: 26px;
            background: #fff;
            box-shadow: 0 18px 45px rgba(15, 23, 42, .08);
        }

        .roles-stat-card::before {
            content: "";
            position: absolute;
            inset: 0;
            background: linear-gradient(135deg, rgba(37, 99, 235, .09), transparent 58%);
            pointer-events: none;
        }

        .roles-stat-card span,
        .roles-section-head p,
        .roles-stat-card small {
            color: var(--roles-muted);
        }

        .roles-stat-card span,
        .roles-table thead th,
        .roles-section-head .roles-eyebrow {
            font-size: .74rem;
            font-weight: 850;
            letter-spacing: .08em;
            text-transform: uppercase;
        }

        .roles-stat-card strong {
            position: relative;
            display: block;
            margin-top: .7rem;
            color: var(--roles-ink);
            font-size: 2.45rem;
            font-weight: 850;
            letter-spacing: -.06em;
            line-height: 1;
        }

        .roles-stat-card small {
            position: relative;
            display: block;
            margin-top: .45rem;
        }

        .roles-stat-card i {
            position: absolute;
            right: 1rem;
            bottom: .8rem;
            color: rgba(37, 99, 235, .14);
            font-size: 3.3rem;
        }

        .roles-stat-card.is-system::before {
            background: linear-gradient(135deg, rgba(214, 162, 61, .14), transparent 58%);
        }

        .roles-stat-card.is-system i,
        .roles-stat-card.is-permission i {
            color: rgba(214, 162, 61, .22);
        }

        .roles-stat-card.is-users::before {
            background: linear-gradient(135deg, rgba(22, 163, 74, .11), transparent 58%);
        }

        .roles-stat-card.is-users i {
            color: rgba(22, 163, 74, .18);
        }

        .roles-table-card {
            padding: 1.15rem;
            border: 1px solid var(--roles-line);
            border-radius: 30px;
            background: #fff;
            box-shadow: 0 18px 45px rgba(15, 23, 42, .08);
        }

        .roles-section-head {
            display: flex;
            align-items: flex-end;
            justify-content: space-between;
            gap: 1rem;
            margin-bottom: 1rem;
            padding: .25rem .25rem 0;
        }

        .roles-section-head .roles-eyebrow {
            color: #8b5e13;
        }

        .roles-section-head h3 {
            margin: 0;
            color: var(--roles-ink);
            font-weight: 850;
            letter-spacing: -.04em;
        }

        .roles-section-head p {
            margin: .25rem 0 0;
        }

        .roles-table thead th {
            border-bottom: 0;
            color: #64748b;
            white-space: nowrap;
            background: #f8fafc;
        }

        .roles-table tbody td {
            border-color: rgba(15, 23, 42, .06);
            padding-top: 1rem;
            padding-bottom: 1rem;
        }

        .role-token {
            display: inline-flex;
            align-items: center;
            gap: .55rem;
            padding: .46rem .75rem;
            border-radius: 999px;
            color: #1e293b;
            background: #f8fafc;
            border: 1px solid rgba(15, 23, 42, .08);
            font-weight: 850;
        }

        .role-token i {
            color: var(--roles-blue);
        }

        .role-permission-chip {
            display: inline-flex;
            align-items: center;
            gap: .35rem;
            margin: 0 .25rem .3rem 0;
            padding: .38rem .58rem;
            border-radius: 999px;
            color: #334155;
            background: #f8fafc;
            border: 1px solid rgba(15, 23, 42, .08);
            font-size: .78rem;
            font-weight: 750;
        }

        .role-action-group {
            display: inline-flex;
            justify-content: flex-end;
            gap: .35rem;
        }

        .role-action-group .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 36px;
            height: 36px;
            border-radius: 12px;
        }

        .role-form-banner {
            display: flex;
            gap: .85rem;
            padding: 1rem;
            border: 1px solid rgba(37, 99, 235, .12);
            border-radius: 20px;
            color: #1e293b;
            background: linear-gradient(135deg, rgba(37, 99, 235, .08), rgba(214, 162, 61, .08));
        }

        .role-form-banner i {
            display: grid;
            place-items: center;
            flex: 0 0 42px;
            width: 42px;
            height: 42px;
            border-radius: 15px;
            color: var(--roles-blue);
            background: #fff;
            box-shadow: 0 10px 24px rgba(15, 23, 42, .08);
        }

        .role-form-banner strong,
        .role-form-banner span {
            display: block;
        }

        .role-form-banner span {
            color: var(--roles-muted);
        }

        .permission-group-card {
            padding: 1rem;
            border: 1px solid var(--roles-line);
            border-radius: 22px;
            background: linear-gradient(135deg, #f8fafc, #fff);
            box-shadow: 0 12px 28px rgba(15, 23, 42, .06);
        }

        .permission-group-head {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            justify-content: space-between;
            gap: .75rem;
            margin-bottom: 1rem;
        }

        .permission-group-head h6 {
            color: var(--roles-ink);
            font-weight: 850;
        }

        .permission-group-actions {
            display: inline-flex;
            flex-wrap: wrap;
            gap: .35rem;
        }

        .permission-group-actions .btn {
            border-radius: 999px;
            font-weight: 800;
        }

        .permission-check-card {
            display: flex;
            align-items: flex-start;
            gap: .45rem;
            padding: .8rem;
            border: 1px solid rgba(15, 23, 42, .08);
            border-radius: 16px;
            background: #fff;
            transition: border-color .18s ease, box-shadow .18s ease, transform .18s ease;
        }

        .permission-check-card:hover {
            border-color: rgba(37, 99, 235, .28);
            box-shadow: 0 10px 24px rgba(15, 23, 42, .08);
            transform: translateY(-1px);
        }

        .permission-check-card strong {
            color: #1e293b;
            font-size: .86rem;
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

        #create-role-modal .modal-dialog,
        #edit-role-modal .modal-dialog {
            height: calc(100vh - 3.5rem);
        }

        #create-role-modal .modal-content,
        #edit-role-modal .modal-content {
            max-height: 100%;
        }

        #create-role-modal form,
        #edit-role-modal form {
            display: flex;
            flex-direction: column;
            min-height: 0;
            height: 100%;
        }

        #create-role-modal .modal-body,
        #edit-role-modal .modal-body {
            min-height: 0;
            overflow-y: auto;
            overscroll-behavior: contain;
        }

        @media (max-width: 991.98px) {
            .roles-hero {
                align-items: stretch;
                flex-direction: column;
            }

            .roles-hero-action {
                width: 100%;
            }
        }

        @media (max-width: 575.98px) {
            .roles-hero,
            .roles-table-card,
            .roles-stat-card {
                border-radius: 22px;
            }

            .roles-hero {
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
            const createModalElement = document.getElementById('create-role-modal');
            const editModalElement = document.getElementById('edit-role-modal');
            const createModal = window.bootstrap ? new window.bootstrap.Modal(createModalElement) : null;
            const editModal = window.bootstrap ? new window.bootstrap.Modal(editModalElement) : null;
            const createForm = document.getElementById('create-role-form');
            const editForm = document.getElementById('edit-role-form');

            const rolesTable = $('#roles-table').DataTable({
                processing: true,
                serverSide: true,
                ajax: '{{ route('adminlte.roles.data') }}',
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

                            const label = row.visual_name || row.label || data;
                            return `<div class="d-flex flex-column">
                                <span class="role-token align-self-start"><i class="bi bi-shield-check"></i>${label}</span>
                                <small class="text-muted mt-1">${data}</small>
                            </div>`;
                        }
                    },
                    {
                        data: 'label',
                        name: 'label',
                        defaultContent: '',
                        render: (data) => data || '<span class="text-muted">Sin etiqueta</span>',
                    },
                    {
                        data: 'permissions_names',
                        name: 'permissions_count',
                        orderable: false,
                        searchable: false,
                        render: (data, type, row) => {
                            if (type !== 'display') {
                                return row.permissions_count;
                            }

                            if (!Array.isArray(data) || data.length === 0) {
                                return '<span class="text-muted">Sin permisos</span>';
                            }

                            const badges = data.slice(0, 4).map((permission) =>
                                `<span class="role-permission-chip"><i class="bi bi-key"></i>${permission}</span>`
                            ).join('');
                            const extra = data.length > 4 ? `<span class="badge rounded-pill text-bg-secondary">+${data.length - 4}</span>` : '';

                            return `<div>${badges}${extra}</div>`;
                        }
                    },
                    {
                        data: 'users_count',
                        name: 'users_count',
                        className: 'text-center',
                        render: (data, type) => type === 'display'
                            ? `<span class="badge rounded-pill text-bg-light border"><i class="bi bi-people me-1"></i>${data}</span>`
                            : data
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

                            let actions = '<div class="role-action-group" role="group">';

                            if (row.can_update) {
                                actions += `<button type="button" class="btn btn-outline-primary role-edit-btn" title="Editar rol" data-role="${encodeURIComponent(JSON.stringify(row))}">
                                    <i class="bi bi-pencil-square"></i>
                                </button>`;
                            }

                            if (row.can_delete) {
                                actions += `<button type="button" class="btn btn-outline-danger role-delete-btn" title="Eliminar rol" data-url="${row.delete_url}" data-name="${row.name}">
                                    <i class="bi bi-trash"></i>
                                </button>`;
                            }

                            actions += '</div>';

                            return actions;
                        }
                    }
                ],
            });

            document.getElementById('open-create-role-modal')?.addEventListener('click', () => {
                resetRoleForm(createForm, 'create');
                createModal?.show();
            });

            createForm.addEventListener('submit', async (event) => {
                event.preventDefault();
                await submitRoleForm(createForm, createForm.action, 'POST', createModal, 'Rol creado correctamente.');
            });

            editForm.addEventListener('submit', async (event) => {
                event.preventDefault();
                await submitRoleForm(editForm, editForm.action, 'POST', editModal, 'Rol actualizado correctamente.');
            });

            document.addEventListener('click', async (event) => {
                const editButton = event.target.closest('.role-edit-btn');
                if (editButton) {
                    const role = JSON.parse(decodeURIComponent(editButton.dataset.role));
                    fillEditForm(role);
                    editModal?.show();
                    return;
                }

                const deleteButton = event.target.closest('.role-delete-btn');
                if (deleteButton) {
                    await deleteRole(deleteButton.dataset.url, deleteButton.dataset.name);
                }
            });

            document.querySelectorAll('[data-group-action]').forEach((button) => {
                button.addEventListener('click', () => {
                    const form = button.dataset.form;
                    const group = button.dataset.group;
                    const checked = button.dataset.groupAction === 'check';
                    document.querySelectorAll(`[data-permission-group="${form}-${group}"] input[type="checkbox"]`).forEach((checkbox) => {
                        checkbox.checked = checked;
                    });
                    updateCounter(form);
                });
            });

            document.querySelectorAll('input[type="checkbox"][name="permissions[]"]').forEach((checkbox) => {
                checkbox.addEventListener('change', () => {
                    updateCounter(checkbox.dataset.counterForm);
                });
            });

            function fillEditForm(role) {
                resetRoleForm(editForm, 'edit');
                editForm.action = role.update_url;
                editForm.querySelector('[name="name"]').value = role.name ?? '';
                editForm.querySelector('[name="label"]').value = role.label ?? '';

                (role.permissions_names ?? []).forEach((permissionName) => {
                    const checkbox = editForm.querySelector(`input[name="permissions[]"][value="${permissionName}"]`);
                    if (checkbox) {
                        checkbox.checked = true;
                    }
                });

                if (role.name === 'admin') {
                    editForm.querySelector('[name="name"]').setAttribute('readonly', 'readonly');
                }

                updateCounter('edit');
            }

            function resetRoleForm(form, counterKey) {
                form.reset();
                form.querySelector('[name="name"]').removeAttribute('readonly');
                form.querySelectorAll('input[name="permissions[]"]').forEach((checkbox) => {
                    checkbox.checked = false;
                });
                updateCounter(counterKey);
            }

            async function submitRoleForm(form, url, method, modalInstance, successMessage) {
                const formData = new FormData(form);

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
                rolesTable.ajax.reload(null, false);

                await window.Swal.fire({
                    icon: 'success',
                    title: payload.message || successMessage,
                    timer: 1800,
                    showConfirmButton: false,
                });
            }

            async function deleteRole(url, roleName) {
                const confirmation = await window.Swal.fire({
                    icon: 'warning',
                    title: 'Eliminar rol?',
                    text: 'No se puede eliminar el rol admin ni roles con usuarios asignados.',
                    showCancelButton: true,
                    confirmButtonText: 'Si, eliminar',
                    cancelButtonText: 'Cancelar',
                    confirmButtonColor: '#dc3545',
                    cancelButtonColor: '#6c757d',
                });

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

                rolesTable.ajax.reload(null, false);

                await window.Swal.fire({
                    icon: 'success',
                    title: payload.message || `Rol ${roleName} eliminado correctamente.`,
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

                await window.Swal.fire({
                    icon: 'error',
                    title: 'No se pudo completar la accion',
                    html: message,
                });
            }

            function updateCounter(key) {
                const checked = document.querySelectorAll(`input[data-counter-form="${key}"]:checked`).length;
                const counter = document.querySelector(`[data-selected-counter="${key}"]`);
                if (counter) {
                    counter.textContent = checked;
                }
            }

            updateCounter('create');
            updateCounter('edit');
        });
    </script>
@endpush
