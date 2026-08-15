@extends('adminlte::page')

@section('title', 'Turnos de recepcion')

@section('content_header')
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3">
        <div>
            <h1 class="m-0">Turnos de recepcion</h1>
            <p class="text-muted mb-0">Define horarios operativos para asignarlos a usuarios con rol recepcionista.</p>
        </div>

        @can('usuarios.crear')
            <button type="button" class="btn btn-primary" id="open-create-shift-modal">
                <i class="bi bi-clock-history me-1" aria-hidden="true"></i> Nuevo turno
            </button>
        @endcan
    </div>
@stop

@section('content')
    <div class="row g-3 mb-4">
        <div class="col-md-4">
            <div class="shift-help-card">
                <i class="bi bi-calendar2-week"></i>
                <div>
                    <strong>1. Registra turnos</strong>
                    <span>Ejemplo: Mañana 07:00 - 15:00, Tarde 15:00 - 23:00 o Noche 23:00 - 07:00.</span>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="shift-help-card">
                <i class="bi bi-person-badge"></i>
                <div>
                    <strong>2. Asigna recepcionistas</strong>
                    <span>En Usuarios, al elegir rol Recepcionista, aparecerá el selector de turno.</span>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="shift-help-card">
                <i class="bi bi-shield-check"></i>
                <div>
                    <strong>3. Mantén historial</strong>
                    <span>Si un turno ya tiene usuarios, se desactiva en lugar de eliminarse.</span>
                </div>
            </div>
        </div>
    </div>

    <x-adminlte-card icon="bi bi-clock" title="Listado de turnos" bodyClass="p-0">
        <div class="table-responsive p-3">
            <table class="table table-hover align-middle w-100" id="work-shifts-table">
                <thead>
                    <tr>
                        <th>Turno</th>
                        <th>Horario</th>
                        <th>Usuarios</th>
                        <th>Estado</th>
                        <th>Registro</th>
                        <th class="text-end">Acciones</th>
                    </tr>
                </thead>
            </table>
        </div>
    </x-adminlte-card>

    <div class="modal fade" id="work-shift-modal" tabindex="-1" aria-labelledby="work-shift-modal-label" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-scrollable">
            <div class="modal-content">
                <form id="work-shift-form" action="{{ route('adminlte.work-shifts.store') }}">
                    @csrf
                    <input type="hidden" name="_method" id="work-shift-method" value="POST">
                    <div class="modal-header">
                        <div>
                            <h5 class="modal-title" id="work-shift-modal-label">Nuevo turno</h5>
                            <small class="text-muted">Configura el rango horario que luego se asignará a recepcionistas.</small>
                        </div>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                    </div>
                    <div class="modal-body">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label" for="shift-name">Nombre del turno</label>
                                <input type="text" class="form-control" id="shift-name" name="name" maxlength="100" placeholder="Ej. Turno mañana" required>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label" for="shift-starts-at">Desde</label>
                                <input type="time" class="form-control" id="shift-starts-at" name="starts_at" required>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label" for="shift-ends-at">Hasta</label>
                                <input type="time" class="form-control" id="shift-ends-at" name="ends_at" required>
                            </div>
                            <div class="col-12">
                                <label class="form-label" for="shift-description">Descripcion / notas</label>
                                <textarea class="form-control" id="shift-description" name="description" rows="3" placeholder="Ej. Responsable de entradas, llamadas y caja de recepcion."></textarea>
                            </div>
                            <div class="col-12">
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" id="shift-is-active" name="is_active" value="1" checked>
                                    <label class="form-check-label" for="shift-is-active">Turno activo para nuevas asignaciones</label>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-primary">Guardar turno</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@stop

@push('css')
    <link rel="stylesheet" href="{{ asset('vendor/datatables/dataTables.bootstrap5.min.css') }}">
    <style>
        .shift-help-card {
            min-height: 118px;
            padding: 1rem;
            border: 1px solid rgba(17, 24, 39, 0.08);
            border-radius: 1.2rem;
            background: #fff;
            box-shadow: 0 0.8rem 1.8rem rgba(17, 24, 39, 0.055);
            display: flex;
            gap: 0.85rem;
        }

        .shift-help-card i {
            width: 2.75rem;
            height: 2.75rem;
            border-radius: 0.9rem;
            color: #fff;
            background: linear-gradient(135deg, #245f9d, #14b8a6);
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 1.3rem;
            flex: 0 0 auto;
        }

        .shift-help-card strong,
        .shift-help-card span {
            display: block;
        }

        .shift-help-card span {
            margin-top: 0.25rem;
            color: #6b7280;
            line-height: 1.35;
        }

        #work-shift-modal .modal-dialog {
            height: calc(100vh - 3.5rem);
        }

        #work-shift-modal .modal-content {
            max-height: 100%;
            border-radius: 1.25rem;
        }

        #work-shift-modal form {
            display: flex;
            flex-direction: column;
            min-height: 0;
            height: 100%;
        }

        #work-shift-modal .modal-body {
            min-height: 0;
            overflow-y: auto;
            overscroll-behavior: contain;
        }
    </style>
@endpush

@push('js')
    <script src="{{ asset('vendor/jquery/jquery.min.js') }}"></script>
    <script src="{{ asset('vendor/datatables/dataTables.min.js') }}"></script>
    <script src="{{ asset('vendor/datatables/dataTables.bootstrap5.min.js') }}"></script>
    <script>
        window.addEventListener('load', () => {
            const $ = window.jQuery || window.$;
            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content ?? '';
            const modalElement = document.getElementById('work-shift-modal');
            const modal = window.bootstrap ? new window.bootstrap.Modal(modalElement) : null;
            const form = document.getElementById('work-shift-form');
            const swal = window.Swal ?? null;

            if (typeof $ !== 'function') {
                console.error('jQuery no esta disponible para inicializar turnos.');
                return;
            }

            const shiftsTable = $('#work-shifts-table').DataTable({
                processing: true,
                serverSide: true,
                ajax: '{{ route('adminlte.work-shifts.data') }}',
                language: {
                    url: 'https://cdn.datatables.net/plug-ins/1.13.8/i18n/es-ES.json',
                },
                order: [[4, 'desc']],
                columns: [
                    {
                        data: 'name',
                        name: 'name',
                        render: (data, type, row) => type === 'display'
                            ? `<div class="fw-semibold">${row.name}</div><small class="text-muted">${row.description || 'Sin descripcion'}</small>`
                            : data
                    },
                    { data: 'schedule_label', name: 'starts_at', className: 'fw-semibold text-nowrap' },
                    { data: 'users_count', name: 'users_count', className: 'text-center' },
                    {
                        data: 'is_active',
                        name: 'is_active',
                        className: 'text-center',
                        render: (data, type, row) => type === 'display'
                            ? `<span class="badge ${data ? 'text-bg-success' : 'text-bg-secondary'}">${row.status_label}</span>`
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

                            let actions = '<div class="btn-group btn-group-sm" role="group">';

                            if (row.can_update) {
                                actions += `<button type="button" class="btn btn-outline-primary shift-edit-btn" data-shift="${encodeURIComponent(JSON.stringify(row))}">
                                    <i class="bi bi-pencil-square"></i>
                                </button>`;
                            }

                            if (row.can_delete) {
                                actions += `<button type="button" class="btn btn-outline-danger shift-delete-btn" data-url="${row.delete_url}" data-name="${row.name}">
                                    <i class="bi bi-trash"></i>
                                </button>`;
                            }

                            actions += '</div>';
                            return actions;
                        }
                    },
                ],
            });

            document.getElementById('open-create-shift-modal')?.addEventListener('click', () => {
                resetForm();
                modal?.show();
            });

            document.addEventListener('click', async (event) => {
                const editButton = event.target.closest('.shift-edit-btn');
                if (editButton) {
                    fillForm(JSON.parse(decodeURIComponent(editButton.dataset.shift)));
                    modal?.show();
                    return;
                }

                const deleteButton = event.target.closest('.shift-delete-btn');
                if (deleteButton) {
                    await deleteShift(deleteButton.dataset.url, deleteButton.dataset.name);
                }
            });

            form.addEventListener('submit', async (event) => {
                event.preventDefault();
                await submitShift();
            });

            function resetForm() {
                form.reset();
                form.action = '{{ route('adminlte.work-shifts.store') }}';
                document.getElementById('work-shift-method').value = 'POST';
                document.getElementById('work-shift-modal-label').textContent = 'Nuevo turno';
                form.querySelector('[name="is_active"]').checked = true;
            }

            function fillForm(shift) {
                resetForm();
                form.action = shift.update_url;
                document.getElementById('work-shift-method').value = 'PUT';
                document.getElementById('work-shift-modal-label').textContent = `Editar turno ${shift.name}`;
                form.querySelector('[name="name"]').value = shift.name ?? '';
                form.querySelector('[name="starts_at"]').value = (shift.starts_at ?? '').substring(0, 5);
                form.querySelector('[name="ends_at"]').value = (shift.ends_at ?? '').substring(0, 5);
                form.querySelector('[name="description"]').value = shift.description ?? '';
                form.querySelector('[name="is_active"]').checked = !!shift.is_active;
            }

            async function submitShift() {
                const formData = new FormData(form);
                if (!form.querySelector('[name="is_active"]').checked) {
                    formData.set('is_active', '0');
                }

                const response = await fetch(form.action, {
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
                modal?.hide();
                shiftsTable.ajax.reload(null, false);
                await fireAlert({ icon: 'success', title: payload.message || 'Turno guardado.', timer: 1800, showConfirmButton: false });
            }

            async function deleteShift(url, name) {
                const confirmation = await fireAlert({
                    icon: 'warning',
                    title: 'Eliminar turno',
                    text: `Eliminar o desactivar el turno ${name}?`,
                    showCancelButton: true,
                    confirmButtonText: 'Si, continuar',
                    cancelButtonText: 'Cancelar',
                    confirmButtonColor: '#dc3545',
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
                shiftsTable.ajax.reload(null, false);
                await fireAlert({ icon: 'success', title: payload.message || 'Turno actualizado.', timer: 1800, showConfirmButton: false });
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

                await fireAlert({ icon: 'error', title: 'No se pudo completar la accion', html: message });
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
