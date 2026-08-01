@extends('adminlte::page')

@section('title', 'Habitaciones')

@section('content_header')
    <div class="rooms-hero">
        <div class="rooms-hero__content">
            <span class="rooms-kicker">Operacion hotelera</span>
            <h1>Habitaciones</h1>
            <p>Administra las habitaciones fisicas, su disponibilidad, ocupacion, reservas, mantenimiento y asociacion comercial con cada tipo de habitacion.</p>
        </div>

        @can('habitaciones.crear')
            <button type="button" class="btn btn-rooms-primary" id="open-create-room-modal">
                <i class="bi bi-plus-lg me-2" aria-hidden="true"></i> Nueva habitacion
            </button>
        @endcan
    </div>
@stop

@section('content')
    <div class="row g-3 mb-4">
        <div class="col-6 col-xl-3">
            <div class="room-stat">
                <span class="room-stat__icon bg-gradient-indigo"><i class="bi bi-building"></i></span>
                <div><small>Total</small><strong>{{ number_format($stats['total'] ?? 0) }}</strong></div>
            </div>
        </div>
        <div class="col-6 col-xl-3">
            <div class="room-stat">
                <span class="room-stat__icon bg-gradient-green"><i class="bi bi-check2-circle"></i></span>
                <div><small>Disponibles</small><strong>{{ number_format($stats['available'] ?? 0) }}</strong></div>
            </div>
        </div>
        <div class="col-6 col-xl-3">
            <div class="room-stat">
                <span class="room-stat__icon bg-gradient-gold"><i class="bi bi-calendar2-check"></i></span>
                <div><small>Reservadas</small><strong>{{ number_format($stats['reserved'] ?? 0) }}</strong></div>
            </div>
        </div>
        <div class="col-6 col-xl-3">
            <div class="room-stat">
                <span class="room-stat__icon bg-gradient-copper"><i class="bi bi-house-lock"></i></span>
                <div><small>Ocupadas</small><strong>{{ number_format($stats['occupied'] ?? 0) }}</strong></div>
            </div>
        </div>
    </div>

    <div class="rooms-panel">
        <div class="rooms-panel__header">
            <div>
                <span class="rooms-kicker text-primary">Mapa operativo</span>
                <h2>Control de habitaciones reales</h2>
                <p>Consulta precios heredados del tipo, estado actual, piso y disponibilidad operativa.</p>
            </div>
            <div class="rooms-panel__summary">
                <span>Activas</span>
                <strong>{{ number_format($stats['active'] ?? 0) }}</strong>
                <small>{{ number_format($stats['inactive'] ?? 0) }} inactivas</small>
            </div>
        </div>

        <div class="table-responsive">
            <table class="table table-hover align-middle w-100" id="rooms-table">
                <thead>
                    <tr>
                        <th>Habitacion</th>
                        <th>Tipo</th>
                        <th>Precios</th>
                        <th>Capacidad</th>
                        <th>Estado</th>
                        <th>Activo</th>
                        <th>Registro</th>
                        <th class="text-end">Acciones</th>
                    </tr>
                </thead>
            </table>
        </div>
    </div>

    <div class="modal fade" id="create-room-modal" tabindex="-1" aria-labelledby="create-room-modal-label" aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-scrollable">
            <div class="modal-content">
                <form id="create-room-form" action="{{ route('adminlte.rooms.store') }}">
                    @csrf
                    <div class="modal-header">
                        <div>
                            <span class="rooms-kicker text-primary">Nuevo registro fisico</span>
                            <h5 class="modal-title" id="create-room-modal-label">Nueva habitacion</h5>
                            <small class="text-muted">Registra numero, piso, tipo comercial y estado inicial.</small>
                        </div>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                    </div>
                    <div class="modal-body">
                        @include('adminlte.rooms.partials.form-fields', ['prefix' => 'create', 'roomTypes' => $roomTypes, 'statuses' => $statuses])
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-rooms-primary">Guardar habitacion</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="modal fade" id="edit-room-modal" tabindex="-1" aria-labelledby="edit-room-modal-label" aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-scrollable">
            <div class="modal-content">
                <form id="edit-room-form" method="POST">
                    @csrf
                    <input type="hidden" name="_method" value="PUT">
                    <div class="modal-header">
                        <div>
                            <span class="rooms-kicker text-primary">Edicion operativa</span>
                            <h5 class="modal-title" id="edit-room-modal-label">Editar habitacion</h5>
                            <small class="text-muted">Actualiza informacion operativa sin salir del listado.</small>
                        </div>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                    </div>
                    <div class="modal-body">
                        @include('adminlte.rooms.partials.form-fields', ['prefix' => 'edit', 'roomTypes' => $roomTypes, 'statuses' => $statuses])
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-rooms-primary">Actualizar habitacion</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@stop

@push('css')
    <link rel="stylesheet" href="https://cdn.datatables.net/2.3.8/css/dataTables.bootstrap5.css">
    <style>
        .rooms-hero {
            position: relative;
            overflow: hidden;
            border-radius: 1.75rem;
            padding: 1.5rem;
            color: #fff;
            background:
                radial-gradient(circle at top right, rgba(37, 150, 117, 0.5), transparent 34%),
                linear-gradient(135deg, #111827 0%, #173d67 48%, #32115f 100%);
            box-shadow: 0 1.5rem 4rem rgba(17, 24, 39, 0.18);
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 1rem;
        }

        .rooms-hero::after {
            content: "";
            position: absolute;
            right: -6rem;
            bottom: -7rem;
            width: 18rem;
            height: 18rem;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.08);
            pointer-events: none;
        }

        .rooms-hero__content {
            position: relative;
            z-index: 1;
            max-width: 780px;
        }

        .rooms-hero h1 {
            margin: 0;
            font-size: clamp(1.8rem, 3vw, 2.75rem);
            font-weight: 850;
            letter-spacing: -0.04em;
        }

        .rooms-hero p {
            max-width: 700px;
            margin: 0.55rem 0 0;
            color: rgba(255, 255, 255, 0.78);
        }

        .rooms-kicker {
            display: inline-flex;
            align-items: center;
            gap: 0.45rem;
            margin-bottom: 0.4rem;
            font-size: 0.72rem;
            font-weight: 850;
            letter-spacing: 0.14em;
            text-transform: uppercase;
            color: rgba(255, 255, 255, 0.72);
        }

        .rooms-kicker::before {
            content: "";
            width: 1.85rem;
            height: 1px;
            background: currentColor;
            opacity: 0.75;
        }

        .btn-rooms-primary {
            position: relative;
            z-index: 1;
            border: 0;
            border-radius: 999px;
            padding: 0.78rem 1.15rem;
            color: #fff;
            font-weight: 850;
            letter-spacing: 0.02em;
            background: linear-gradient(135deg, #1e8d68, #245f9d);
            box-shadow: 0 1rem 2.3rem rgba(36, 95, 157, 0.28);
        }

        .btn-rooms-primary:hover,
        .btn-rooms-primary:focus {
            color: #fff;
            transform: translateY(-1px);
            box-shadow: 0 1.2rem 2.8rem rgba(36, 95, 157, 0.36);
        }

        .room-stat {
            min-height: 104px;
            padding: 1.05rem;
            border: 1px solid rgba(17, 24, 39, 0.08);
            border-radius: 1.35rem;
            background: rgba(255, 255, 255, 0.94);
            box-shadow: 0 1rem 2.4rem rgba(17, 24, 39, 0.07);
            display: flex;
            align-items: center;
            gap: 0.95rem;
        }

        .room-stat__icon {
            width: 3.05rem;
            height: 3.05rem;
            border-radius: 1rem;
            color: #fff;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 1.35rem;
            box-shadow: inset 0 1px rgba(255, 255, 255, 0.2), 0 0.8rem 1.5rem rgba(17, 24, 39, 0.13);
        }

        .room-stat small {
            display: block;
            color: #6b7280;
            font-weight: 800;
            letter-spacing: 0.03em;
            text-transform: uppercase;
        }

        .room-stat strong {
            display: block;
            color: #161827;
            font-size: 1.45rem;
            line-height: 1.1;
        }

        .bg-gradient-indigo { background: linear-gradient(135deg, #245f9d, #173d67); }
        .bg-gradient-green { background: linear-gradient(135deg, #1e8d68, #0f5132); }
        .bg-gradient-gold { background: linear-gradient(135deg, #df941b, #8a5610); }
        .bg-gradient-copper { background: linear-gradient(135deg, #c25f4a, #6f2d44); }

        .rooms-panel {
            overflow: hidden;
            border: 1px solid rgba(17, 24, 39, 0.08);
            border-radius: 1.5rem;
            background: #fff;
            box-shadow: 0 1.5rem 4rem rgba(17, 24, 39, 0.08);
        }

        .rooms-panel__header {
            padding: 1.35rem 1.5rem;
            border-bottom: 1px solid rgba(17, 24, 39, 0.08);
            background:
                linear-gradient(135deg, rgba(36, 95, 157, 0.08), rgba(30, 141, 104, 0.08)),
                #fff;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 1rem;
        }

        .rooms-panel__header h2 {
            margin: 0;
            color: #161827;
            font-size: 1.25rem;
            font-weight: 850;
        }

        .rooms-panel__header p {
            margin: 0.25rem 0 0;
            color: #6b7280;
        }

        .rooms-panel__summary {
            min-width: 150px;
            padding: 0.85rem 1rem;
            border: 1px solid rgba(36, 95, 157, 0.12);
            border-radius: 1rem;
            background: rgba(255, 255, 255, 0.72);
            text-align: right;
        }

        .rooms-panel__summary span,
        .rooms-panel__summary small {
            display: block;
            color: #6b7280;
            font-size: 0.75rem;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.08em;
        }

        .rooms-panel__summary strong {
            color: #173d67;
            font-size: 1.35rem;
        }

        #rooms-table {
            margin: 0 !important;
        }

        #rooms-table thead th {
            padding-top: 1rem;
            padding-bottom: 1rem;
            border-bottom: 1px solid rgba(17, 24, 39, 0.08);
            color: #6b7280;
            font-size: 0.74rem;
            font-weight: 850;
            letter-spacing: 0.08em;
            text-transform: uppercase;
            background: #fbfaf8;
        }

        #rooms-table tbody td {
            padding-top: 1rem;
            padding-bottom: 1rem;
            border-color: rgba(17, 24, 39, 0.06);
        }

        #rooms-table tbody tr:hover {
            background: rgba(36, 95, 157, 0.035);
        }

        .room-number-lockup {
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .room-number-icon {
            width: 3rem;
            height: 3rem;
            border-radius: 1rem;
            color: #fff;
            background: linear-gradient(135deg, #173d67, #1e8d68);
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 1.25rem;
            box-shadow: 0 0.8rem 1.5rem rgba(23, 61, 103, 0.18);
        }

        .room-number-title {
            display: block;
            color: #161827;
            font-size: 1rem;
            font-weight: 850;
        }

        .room-muted {
            color: #6b7280;
            font-size: 0.82rem;
        }

        .room-type-name {
            color: #173d67;
            font-weight: 850;
        }

        .room-price {
            color: #173d67;
            font-weight: 850;
        }

        .room-pill {
            display: inline-flex;
            align-items: center;
            gap: 0.35rem;
            padding: 0.4rem 0.72rem;
            border-radius: 999px;
            font-size: 0.78rem;
            font-weight: 850;
        }

        .room-pill--active {
            color: #0f5132;
            background: rgba(30, 141, 104, 0.13);
        }

        .room-pill--inactive {
            color: #6b7280;
            background: #f3f4f6;
        }

        .room-actions {
            display: inline-flex;
            gap: 0.45rem;
        }

        .room-action-btn {
            width: 2.25rem;
            height: 2.25rem;
            border-radius: 0.8rem;
            display: inline-flex;
            align-items: center;
            justify-content: center;
        }

        #create-room-modal .modal-dialog,
        #edit-room-modal .modal-dialog {
            height: calc(100vh - 3.5rem);
        }

        #create-room-modal .modal-content,
        #edit-room-modal .modal-content {
            max-height: 100%;
            overflow: hidden;
            border: 0;
            border-radius: 1.5rem;
            box-shadow: 0 2rem 5rem rgba(17, 24, 39, 0.22);
        }

        #create-room-modal form,
        #edit-room-modal form {
            display: flex;
            flex-direction: column;
            min-height: 0;
            height: 100%;
        }

        #create-room-modal .modal-body,
        #edit-room-modal .modal-body {
            min-height: 0;
            overflow-y: auto;
            overscroll-behavior: contain;
            background: #fbfaf8;
        }

        #create-room-modal .modal-header,
        #edit-room-modal .modal-header,
        #create-room-modal .modal-footer,
        #edit-room-modal .modal-footer {
            background: #fff;
            border-color: rgba(17, 24, 39, 0.08);
        }

        .room-form-section {
            height: 100%;
            padding: 1rem;
            border: 1px solid rgba(17, 24, 39, 0.08);
            border-radius: 1.15rem;
            background: #fff;
            box-shadow: 0 0.75rem 1.8rem rgba(17, 24, 39, 0.04);
        }

        .room-form-section__title {
            margin-bottom: 1rem;
            color: #161827;
            font-weight: 850;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .room-form-section__title i {
            color: #1e8d68;
        }

        .room-form-section .form-label {
            color: #374151;
            font-size: 0.82rem;
            font-weight: 850;
        }

        .room-form-section .form-control,
        .room-form-section .form-select {
            border-radius: 0.85rem;
        }

        .room-switch-card {
            min-height: 92px;
            padding: 0.95rem;
            border: 1px solid rgba(17, 24, 39, 0.08);
            border-radius: 1rem;
            background: linear-gradient(135deg, rgba(36, 95, 157, 0.045), rgba(30, 141, 104, 0.06));
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 0.75rem;
        }

        .room-switch-card strong,
        .room-switch-card span {
            display: block;
        }

        .room-switch-card strong {
            color: #161827;
            font-size: 0.92rem;
        }

        .room-switch-card span {
            color: #6b7280;
            font-size: 0.78rem;
        }

        .room-switch-card .form-check-input {
            width: 2.8rem;
            height: 1.45rem;
            cursor: pointer;
        }

        .room-switch-card .form-check-input:checked {
            border-color: #1e8d68;
            background-color: #1e8d68;
        }

        @media (max-width: 767.98px) {
            .rooms-hero,
            .rooms-panel__header {
                align-items: stretch;
                flex-direction: column;
            }

            .btn-rooms-primary {
                width: 100%;
            }

            .rooms-panel__summary {
                min-width: 0;
                text-align: left;
            }
        }
    </style>
@endpush

@push('js')
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const $ = window.jQuery;
            const swal = window.Swal;
            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content ?? '';
            const createForm = document.getElementById('create-room-form');
            const editForm = document.getElementById('edit-room-form');
            const createModal = window.bootstrap ? new window.bootstrap.Modal(document.getElementById('create-room-modal')) : null;
            const editModal = window.bootstrap ? new window.bootstrap.Modal(document.getElementById('edit-room-modal')) : null;
            const statuses = @json($statuses);

            if (typeof $ !== 'function') {
                console.error('jQuery no esta disponible para DataTables.');
                return;
            }

            window.roomsTable = $('#rooms-table').DataTable({
                processing: true,
                serverSide: true,
                ajax: '{{ route('adminlte.rooms.data') }}',
                language: {
                    url: 'https://cdn.datatables.net/plug-ins/1.13.8/i18n/es-ES.json',
                },
                order: [[6, 'desc']],
                columns: [
                    {
                        data: 'number',
                        name: 'number',
                        render: (data, type, row) => {
                            if (type !== 'display') {
                                return data;
                            }

                            const floor = row.floor ? `Piso ${row.floor}` : 'Sin piso registrado';
                            return `<div class="room-number-lockup">
                                <span class="room-number-icon"><i class="bi bi-door-open"></i></span>
                                <div><span class="room-number-title">Habitacion ${row.number}</span><span class="room-muted">${floor}</span></div>
                            </div>`;
                        }
                    },
                    {
                        data: 'room_type_name',
                        name: 'roomType.name',
                        render: (data, type) => {
                            if (type !== 'display') {
                                return data;
                            }

                            return `<span class="room-type-name">${data}</span>`;
                        }
                    },
                    {
                        data: 'room_type_price_formatted',
                        name: 'roomType.base_price',
                        render: (data, type) => {
                            if (type !== 'display') {
                                return data;
                            }

                            return `<span class="room-price">${data}</span>`;
                        }
                    },
                    { data: 'capacity_summary', name: 'roomType.max_guests', orderable: false, searchable: false },
                    {
                        data: 'status',
                        name: 'status',
                        className: 'text-center',
                        render: (data, type, row) => {
                            if (type !== 'display') {
                                return data;
                            }

                            return `<span class="${row.status_badge_class}"><i class="bi ${row.status_icon} me-1"></i>${row.status_label}</span>`;
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

                            const pillClass = data ? 'room-pill room-pill--active' : 'room-pill room-pill--inactive';
                            const icon = data ? 'bi-check2-circle' : 'bi-pause-circle';
                            return `<span class="${pillClass}"><i class="bi ${icon}"></i>${row.active_label}</span>`;
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

                            let actions = '<div class="room-actions" role="group">';

                            if (row.can_update) {
                                actions += `<button type="button" class="btn btn-outline-primary room-action-btn room-edit-btn" title="Editar" data-room="${encodeURIComponent(JSON.stringify(row))}">
                                    <i class="bi bi-pencil-square"></i>
                                </button>`;
                            }

                            if (row.can_change_status) {
                                actions += `<button type="button" class="btn btn-outline-warning room-action-btn room-status-btn" title="Cambiar estado" data-room="${encodeURIComponent(JSON.stringify(row))}" data-url="${row.change_status_url}">
                                    <i class="bi bi-arrow-repeat"></i>
                                </button>`;
                            }

                            if (row.can_delete) {
                                actions += `<button type="button" class="btn btn-outline-danger room-action-btn room-delete-btn" title="Eliminar" data-url="${row.delete_url}" data-number="${row.number}">
                                    <i class="bi bi-trash3"></i>
                                </button>`;
                            }

                            actions += '</div>';
                            return actions;
                        }
                    }
                ],
            });

            document.getElementById('open-create-room-modal')?.addEventListener('click', () => {
                resetForm(createForm);
                createModal?.show();
            });

            createForm.addEventListener('submit', async (event) => {
                event.preventDefault();
                await submitRoomForm(createForm, createForm.action, createModal, false);
            });

            editForm.addEventListener('submit', async (event) => {
                event.preventDefault();
                await submitRoomForm(editForm, editForm.action, editModal, true);
            });

            document.addEventListener('click', async (event) => {
                const editButton = event.target.closest('.room-edit-btn');
                if (editButton) {
                    const room = JSON.parse(decodeURIComponent(editButton.dataset.room));
                    fillEditForm(room);
                    editModal?.show();
                    return;
                }

                const statusButton = event.target.closest('.room-status-btn');
                if (statusButton) {
                    const room = JSON.parse(decodeURIComponent(statusButton.dataset.room));
                    await changeStatus(room, statusButton.dataset.url);
                    return;
                }

                const deleteButton = event.target.closest('.room-delete-btn');
                if (deleteButton) {
                    await deleteRoom(deleteButton.dataset.url, deleteButton.dataset.number);
                }
            });

            function fillEditForm(room) {
                resetForm(editForm);
                editForm.action = room.update_url;
                editForm.querySelector('[name="room_type_id"]').value = room.room_type_id ?? '';
                editForm.querySelector('[name="number"]').value = room.number ?? '';
                editForm.querySelector('[name="floor"]').value = room.floor ?? '';
                editForm.querySelector('[name="description"]').value = room.description ?? '';
                editForm.querySelector('[name="internal_notes"]').value = room.internal_notes ?? '';
                editForm.querySelector('[name="status"]').value = room.status ?? 'available';
                editForm.querySelector('[name="is_active"]').checked = !!room.is_active;
            }

            function resetForm(form) {
                form.reset();
                form.querySelector('[name="status"]').value = 'available';
                form.querySelector('[name="is_active"]').checked = true;
            }

            async function submitRoomForm(form, url, modalInstance, useMethodOverride) {
                const formData = new FormData(form);

                if (!form.querySelector('[name="is_active"]').checked) {
                    formData.set('is_active', '0');
                }

                if (useMethodOverride) {
                    formData.set('_method', 'PUT');
                }

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
                modalInstance?.hide();
                form.reset();
                window.roomsTable.ajax.reload(null, false);

                await fireAlert({
                    icon: 'success',
                    title: payload.message || 'Operacion completada correctamente.',
                    timer: 1800,
                    showConfirmButton: false,
                });
            }

            async function changeStatus(room, url) {
                let optionsHtml = '';
                Object.entries(statuses).forEach(([value, meta]) => {
                    optionsHtml += `<option value="${value}" ${room.status === value ? 'selected' : ''}>${meta.label}</option>`;
                });

                const result = await fireAlert({
                    title: `Cambiar estado de ${room.number}`,
                    html: `<select id="room-status-select" class="swal2-select">${optionsHtml}</select>`,
                    showCancelButton: true,
                    confirmButtonText: 'Actualizar',
                    cancelButtonText: 'Cancelar',
                    preConfirm: () => document.getElementById('room-status-select')?.value,
                }, true);

                if (!result.isConfirmed) {
                    return;
                }

                const formData = new FormData();
                formData.append('_method', 'PATCH');
                formData.append('status', result.value);

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
                window.roomsTable.ajax.reload(null, false);

                await fireAlert({
                    icon: 'success',
                    title: payload.message || 'Estado actualizado correctamente.',
                    timer: 1800,
                    showConfirmButton: false,
                });
            }

            async function deleteRoom(url, number) {
                const confirmation = await fireAlert({
                    icon: 'warning',
                    title: 'Eliminar habitacion',
                    text: `Se eliminara la habitacion ${number}.`,
                    showCancelButton: true,
                    confirmButtonText: 'Si, eliminar',
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
                window.roomsTable.ajax.reload(null, false);

                await fireAlert({
                    icon: 'success',
                    title: payload.message || 'Habitacion eliminada correctamente.',
                    timer: 1800,
                    showConfirmButton: false,
                });
            }

            async function handleRequestError(response) {
                let html = 'Ocurrio un error inesperado.';

                try {
                    const payload = await response.json();
                    if (response.status === 422 && payload.errors) {
                        const errors = Object.values(payload.errors).flat();
                        html = `<ul class="text-start mb-0">${errors.map((error) => `<li>${error}</li>`).join('')}</ul>`;
                    } else if (payload.message) {
                        html = payload.message;
                    }
                } catch (error) {
                    html = 'No fue posible procesar la respuesta del servidor.';
                }

                await fireAlert({
                    icon: 'error',
                    title: 'No se pudo completar la accion',
                    html,
                });
            }

            async function fireAlert(options, confirmFallback = false) {
                if (swal) {
                    return swal.fire(options);
                }

                if (confirmFallback) {
                    return { isConfirmed: window.confirm(options.text || options.title || ''), value: null };
                }

                window.alert(options.text || options.title || '');
                return { isConfirmed: true };
            }
        });
    </script>
@endpush
