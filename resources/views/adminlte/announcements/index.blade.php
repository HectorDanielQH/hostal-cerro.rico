@extends('adminlte::page')

@section('title', 'Anuncios Web')

@section('content_header')
    <div class="announcements-hero">
        <div class="announcements-hero-copy">
            <span class="announcements-eyebrow">Marketing web</span>
            <h1 class="m-0">Anuncios Web</h1>
            <p class="mb-0">Gestiona anuncios visuales para la portada publica: promociones, avisos importantes y mensajes temporales para visitantes.</p>
        </div>

        @can('configuracion.editar')
            <button type="button" class="btn announcements-hero-action" id="open-create-announcement-modal">
                <i class="bi bi-megaphone" aria-hidden="true"></i>
                Nuevo anuncio
            </button>
        @endcan
    </div>
@stop

@section('content')
    <div class="announcements-shell">
        <div class="row g-3 mb-4">
            <div class="col-md-6 col-xl-3">
                <div class="announcements-stat-card">
                    <i class="bi bi-megaphone"></i>
                    <span>Total anuncios</span>
                    <strong>{{ $announcementStats['total'] }}</strong>
                    <small>Registros creados</small>
                </div>
            </div>
            <div class="col-md-6 col-xl-3">
                <div class="announcements-stat-card is-active">
                    <i class="bi bi-check-circle"></i>
                    <span>Activos</span>
                    <strong>{{ $announcementStats['active'] }}</strong>
                    <small>Disponibles para publicacion</small>
                </div>
            </div>
            <div class="col-md-6 col-xl-3">
                <div class="announcements-stat-card is-web">
                    <i class="bi bi-globe2"></i>
                    <span>Visibles web</span>
                    <strong>{{ $announcementStats['visible'] }}</strong>
                    <small>Marcados para la portada</small>
                </div>
            </div>
            <div class="col-md-6 col-xl-3">
                <div class="announcements-stat-card is-modal">
                    <i class="bi bi-window"></i>
                    <span>Modal inicio</span>
                    <strong>{{ $announcementStats['modal'] }}</strong>
                    <small>Aparecen como aviso emergente</small>
                </div>
            </div>
        </div>

        <div class="announcements-table-card">
            <div class="announcements-section-head">
                <div>
                    <span class="announcements-eyebrow">Campanas y avisos</span>
                    <h3>Listado de anuncios</h3>
                    <p>Controla imagen, vigencia, orden y visibilidad de cada anuncio del sitio publico.</p>
                </div>
            </div>
            <div class="table-responsive">
                <table class="table table-hover align-middle w-100 announcements-table" id="announcements-table">
                    <thead>
                        <tr>
                            <th>Anuncio</th>
                            <th>Imagen</th>
                            <th>Fechas</th>
                            <th>Web</th>
                            <th>Modal</th>
                            <th>Estado</th>
                            <th>Orden</th>
                            <th class="text-end">Acciones</th>
                        </tr>
                    </thead>
                </table>
            </div>
        </div>
    </div>

    <div class="modal fade" id="create-announcement-modal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-scrollable">
            <div class="modal-content">
                <form id="create-announcement-form" action="{{ route('adminlte.announcements.store') }}" enctype="multipart/form-data">
                    @csrf
                    <div class="modal-header">
                        <div>
                            <h5 class="modal-title">Nuevo anuncio</h5>
                            <small class="text-muted">Este anuncio podra aparecer al inicio de la pagina publica.</small>
                        </div>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                    </div>
                    <div class="modal-body">
                        <div class="announcement-form-banner mb-3">
                            <i class="bi bi-stars"></i>
                            <div>
                                <strong>Anuncio para la portada</strong>
                                <span>Usa imagen horizontal de buena calidad. Si esta activo, visible en web y como modal, aparecera al inicio.</span>
                            </div>
                        </div>
                        @include('adminlte.announcements.partials.form-fields', ['prefix' => 'create'])
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-primary">Guardar anuncio</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="modal fade" id="edit-announcement-modal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-scrollable">
            <div class="modal-content">
                <form id="edit-announcement-form" enctype="multipart/form-data">
                    @csrf
                    <div class="modal-header">
                        <div>
                            <h5 class="modal-title">Editar anuncio</h5>
                            <small class="text-muted">Actualiza el contenido visual que aparecera en la portada.</small>
                        </div>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                    </div>
                    <div class="modal-body">
                        <div class="announcement-form-banner mb-3">
                            <i class="bi bi-pencil-square"></i>
                            <div>
                                <strong>Editar anuncio publicado</strong>
                                <span>Los cambios se reflejan en la pagina publica segun las fechas, estado y orden configurado.</span>
                            </div>
                        </div>
                        @include('adminlte.announcements.partials.form-fields', ['prefix' => 'edit'])
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-primary">Actualizar anuncio</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@stop

@push('css')
    <link rel="stylesheet" href="{{ asset('vendor/datatables/dataTables.bootstrap5.min.css') }}">
    <style>
        :root {
            --ann-ink: #172033;
            --ann-muted: #667085;
            --ann-line: rgba(15, 23, 42, .08);
            --ann-blue: #2563eb;
            --ann-green: #16a34a;
            --ann-gold: #d6a23d;
            --ann-red: #dc2626;
            --ann-shadow: 0 24px 60px rgba(15, 23, 42, .12);
        }

        .announcements-hero {
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
            box-shadow: var(--ann-shadow);
        }

        .announcements-hero::after {
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

        .announcements-hero-copy,
        .announcements-hero-action {
            position: relative;
            z-index: 1;
        }

        .announcements-hero h1 {
            font-size: clamp(2.3rem, 5vw, 4rem);
            font-weight: 850;
            letter-spacing: -.05em;
        }

        .announcements-hero p {
            max-width: 780px;
            color: rgba(255, 255, 255, .74);
        }

        .announcements-eyebrow {
            display: inline-flex;
            margin-bottom: .45rem;
            color: #f6d48e;
            font-size: .72rem;
            font-weight: 850;
            letter-spacing: .16em;
            text-transform: uppercase;
        }

        .announcements-hero-action {
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

        .announcements-hero-action:hover {
            color: #172033;
            transform: translateY(-1px);
            box-shadow: 0 20px 38px rgba(214, 162, 61, .32);
        }

        .announcements-shell {
            margin-top: 1.5rem;
        }

        .announcements-stat-card {
            position: relative;
            overflow: hidden;
            min-height: 155px;
            padding: 1.2rem;
            border: 1px solid var(--ann-line);
            border-radius: 26px;
            background: #fff;
            box-shadow: 0 18px 45px rgba(15, 23, 42, .08);
        }

        .announcements-stat-card::before {
            content: "";
            position: absolute;
            inset: 0;
            background: linear-gradient(135deg, rgba(37, 99, 235, .09), transparent 58%);
            pointer-events: none;
        }

        .announcements-stat-card span,
        .announcements-section-head p,
        .announcements-stat-card small {
            color: var(--ann-muted);
        }

        .announcements-stat-card span,
        .announcements-table thead th,
        .announcements-section-head .announcements-eyebrow,
        .announcement-form-grid .form-label {
            font-size: .74rem;
            font-weight: 850;
            letter-spacing: .08em;
            text-transform: uppercase;
        }

        .announcements-stat-card strong {
            position: relative;
            display: block;
            margin-top: .7rem;
            color: var(--ann-ink);
            font-size: 2.45rem;
            font-weight: 850;
            letter-spacing: -.06em;
            line-height: 1;
        }

        .announcements-stat-card small {
            position: relative;
            display: block;
            margin-top: .45rem;
        }

        .announcements-stat-card i {
            position: absolute;
            right: 1rem;
            bottom: .8rem;
            color: rgba(37, 99, 235, .14);
            font-size: 3.3rem;
        }

        .announcements-stat-card.is-active::before {
            background: linear-gradient(135deg, rgba(22, 163, 74, .11), transparent 58%);
        }

        .announcements-stat-card.is-active i {
            color: rgba(22, 163, 74, .18);
        }

        .announcements-stat-card.is-web::before,
        .announcements-stat-card.is-modal::before {
            background: linear-gradient(135deg, rgba(214, 162, 61, .14), transparent 58%);
        }

        .announcements-stat-card.is-web i,
        .announcements-stat-card.is-modal i {
            color: rgba(214, 162, 61, .22);
        }

        .announcements-table-card {
            padding: 1.15rem;
            border: 1px solid var(--ann-line);
            border-radius: 30px;
            background: #fff;
            box-shadow: 0 18px 45px rgba(15, 23, 42, .08);
        }

        .announcements-section-head {
            display: flex;
            align-items: flex-end;
            justify-content: space-between;
            gap: 1rem;
            margin-bottom: 1rem;
            padding: .25rem .25rem 0;
        }

        .announcements-section-head .announcements-eyebrow {
            color: #8b5e13;
        }

        .announcements-section-head h3 {
            margin: 0;
            color: var(--ann-ink);
            font-weight: 850;
            letter-spacing: -.04em;
        }

        .announcements-section-head p {
            margin: .25rem 0 0;
        }

        .announcements-table thead th {
            border-bottom: 0;
            color: #64748b;
            white-space: nowrap;
            background: #f8fafc;
        }

        .announcements-table tbody td {
            border-color: rgba(15, 23, 42, .06);
            padding-top: 1rem;
            padding-bottom: 1rem;
        }

        .announcement-thumb {
            width: 104px;
            height: 74px;
            object-fit: cover;
            border-radius: 18px;
            box-shadow: 0 12px 26px rgba(15, 23, 42, .14);
        }

        .announcement-empty-thumb {
            display: grid;
            place-items: center;
            width: 104px;
            height: 74px;
            border-radius: 18px;
            color: #94a3b8;
            background: #f8fafc;
            border: 1px dashed rgba(15, 23, 42, .16);
        }

        .announcement-order-badge {
            display: inline-grid;
            place-items: center;
            min-width: 34px;
            height: 34px;
            border-radius: 12px;
            color: var(--ann-ink);
            background: rgba(214, 162, 61, .14);
            font-weight: 850;
        }

        .announcement-action-group {
            display: inline-flex;
            justify-content: flex-end;
            gap: .35rem;
        }

        .announcement-action-group .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 36px;
            height: 36px;
            border-radius: 12px;
        }

        .announcement-form-banner {
            display: flex;
            gap: .85rem;
            padding: 1rem;
            border: 1px solid rgba(37, 99, 235, .12);
            border-radius: 20px;
            color: #1e293b;
            background: linear-gradient(135deg, rgba(37, 99, 235, .08), rgba(214, 162, 61, .08));
        }

        .announcement-form-banner i {
            display: grid;
            place-items: center;
            flex: 0 0 42px;
            width: 42px;
            height: 42px;
            border-radius: 15px;
            color: var(--ann-blue);
            background: #fff;
            box-shadow: 0 10px 24px rgba(15, 23, 42, .08);
        }

        .announcement-form-banner strong,
        .announcement-form-banner span {
            display: block;
        }

        .announcement-form-banner span {
            color: var(--ann-muted);
        }

        .announcement-form-grid .form-control,
        .announcement-form-grid .form-select {
            border-radius: 14px;
            min-height: 42px;
            border-color: rgba(15, 23, 42, .12);
        }

        .announcement-image-preview {
            display: grid;
            place-items: center;
            min-height: 190px;
            margin-top: .75rem;
            padding: 1rem;
            border: 1px dashed rgba(15, 23, 42, .16);
            border-radius: 22px;
            background: linear-gradient(135deg, #f8fafc, #fff);
        }

        #create-announcement-modal .modal-dialog,
        #edit-announcement-modal .modal-dialog {
            height: calc(100vh - 3.5rem);
        }

        #create-announcement-modal .modal-content,
        #edit-announcement-modal .modal-content {
            max-height: 100%;
        }

        #create-announcement-modal form,
        #edit-announcement-modal form {
            display: flex;
            flex-direction: column;
            min-height: 0;
            height: 100%;
        }

        #create-announcement-modal .modal-body,
        #edit-announcement-modal .modal-body {
            min-height: 0;
            overflow-y: auto;
            overscroll-behavior: contain;
        }

        .announcement-image-preview img {
            max-height: 180px;
            border-radius: 1rem;
            box-shadow: 0 14px 30px rgba(15, 23, 42, .16);
        }

        .announcement-switch-card {
            min-height: 100%;
            margin: 0;
            padding: 1rem 1rem 1rem 3.1rem;
            border: 1px solid var(--ann-line);
            border-radius: 18px;
            background: #fff;
            box-shadow: 0 10px 24px rgba(15, 23, 42, .05);
        }

        .announcement-switch-card .form-check-input {
            margin-left: -2.1rem;
        }

        .announcement-switch-card strong,
        .announcement-switch-card span {
            display: block;
        }

        .announcement-switch-card span {
            color: var(--ann-muted);
            font-size: .85rem;
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

        @media (max-width: 991.98px) {
            .announcements-hero {
                align-items: stretch;
                flex-direction: column;
            }

            .announcements-hero-action {
                width: 100%;
            }
        }

        @media (max-width: 575.98px) {
            .announcements-hero,
            .announcements-table-card,
            .announcements-stat-card {
                border-radius: 22px;
            }

            .announcements-hero {
                padding: 1.1rem;
            }
        }
    </style>
@endpush

@push('js')
    <script src="{{ asset('vendor/jquery/jquery.min.js') }}"></script>
    <script src="{{ asset('vendor/datatables/dataTables.min.js') }}"></script>
    <script src="{{ asset('vendor/datatables/dataTables.bootstrap5.min.js') }}"></script>
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const $ = window.jQuery;
            const swal = window.Swal;
            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content ?? '';
            const createForm = document.getElementById('create-announcement-form');
            const editForm = document.getElementById('edit-announcement-form');
            const createModal = window.bootstrap ? new window.bootstrap.Modal(document.getElementById('create-announcement-modal')) : null;
            const editModal = window.bootstrap ? new window.bootstrap.Modal(document.getElementById('edit-announcement-modal')) : null;

            if (typeof $ !== 'function') {
                console.error('jQuery no esta disponible para DataTables.');
                return;
            }

            const formatDateTimeLocal = (value) => {
                if (!value) return '';
                const normalized = String(value).replace(' ', 'T');
                return normalized.length >= 16 ? normalized.slice(0, 16) : normalized;
            };

            const renderPreview = (prefix, imageUrl = null) => {
                const preview = document.getElementById(`${prefix}-announcement-image-preview`);
                if (!preview) return;
                preview.innerHTML = imageUrl
                    ? `<img src="${imageUrl}" alt="Preview anuncio" class="img-fluid">`
                    : '<span class="text-muted">Sin imagen seleccionada</span>';
            };

            ['create', 'edit'].forEach((prefix) => {
                document.getElementById(`${prefix}-announcement-image`)?.addEventListener('change', (event) => {
                    const [file] = event.target.files ?? [];
                    if (!file) {
                        renderPreview(prefix, null);
                        return;
                    }

                    const reader = new FileReader();
                    reader.onload = () => renderPreview(prefix, reader.result);
                    reader.readAsDataURL(file);
                });
            });

            const resetAnnouncementForm = (form, prefix) => {
                form.reset();
                form.querySelector('[name="sort_order"]').value = 0;
                form.querySelector('[name="show_on_website"]').checked = true;
                form.querySelector('[name="show_as_modal"]').checked = true;
                form.querySelector('[name="is_active"]').checked = true;
                renderPreview(prefix, null);
            };

            window.announcementsTable = $('#announcements-table').DataTable({
                processing: true,
                serverSide: true,
                ajax: '{{ route('adminlte.announcements.data') }}',
                language: {
                    url: 'https://cdn.datatables.net/plug-ins/1.13.8/i18n/es-ES.json',
                },
                order: [[6, 'asc'], [0, 'asc']],
                columns: [
                    {
                        data: 'title',
                        name: 'title',
                        render: (data, type, row) => {
                            if (type !== 'display') return data;
                            const subtitle = row.subtitle ? `<div class="small text-muted mt-1">${row.subtitle}</div>` : '';
                            const content = row.content ? `<div class="small mt-2">${row.content.substring(0, 140)}${row.content.length > 140 ? '...' : ''}</div>` : '';
                            return `<div class="fw-semibold text-dark">${row.title}</div>${subtitle}${content}`;
                        }
                    },
                    {
                        data: 'image_url',
                        orderable: false,
                        searchable: false,
                        render: (data, type) => {
                            if (type !== 'display') return data ?? '';
                            return data
                                ? `<img src="${data}" alt="Anuncio" class="announcement-thumb">`
                                : '<span class="announcement-empty-thumb"><i class="bi bi-image"></i></span>';
                        }
                    },
                    { data: 'date_range_label', name: 'starts_at' },
                    {
                        data: 'website_label',
                        name: 'show_on_website',
                        className: 'text-center',
                        render: (data, type, row) => type === 'display'
                            ? `<span class="badge ${row.show_on_website ? 'text-bg-info' : 'text-bg-secondary'}">${data}</span>`
                            : data
                    },
                    {
                        data: 'modal_label',
                        name: 'show_as_modal',
                        className: 'text-center',
                        render: (data, type, row) => type === 'display'
                            ? `<span class="badge ${row.show_as_modal ? 'text-bg-primary' : 'text-bg-secondary'}">${data}</span>`
                            : data
                    },
                    {
                        data: 'status_label',
                        name: 'is_active',
                        className: 'text-center',
                        render: (data, type, row) => type === 'display'
                            ? `<span class="badge ${row.is_active ? 'text-bg-success' : 'text-bg-secondary'}">${data}</span>`
                            : data
                    },
                    {
                        data: 'sort_order',
                        name: 'sort_order',
                        className: 'text-center',
                        render: (data, type) => type === 'display'
                            ? `<span class="announcement-order-badge">${data}</span>`
                            : data
                    },
                    {
                        data: null,
                        orderable: false,
                        searchable: false,
                        className: 'text-end',
                        render: (data, type, row) => {
                            if (type !== 'display') return '';
                            return `<div class="announcement-action-group">
                                <button type="button" class="btn btn-outline-primary announcement-edit-btn" title="Editar anuncio" data-announcement="${encodeURIComponent(JSON.stringify(row))}">
                                    <i class="bi bi-pencil-square"></i>
                                </button>
                                <button type="button" class="btn btn-outline-danger announcement-delete-btn" title="Eliminar anuncio" data-url="${row.delete_url}" data-title="${row.title}">
                                    <i class="bi bi-trash"></i>
                                </button>
                            </div>`;
                        }
                    }
                ],
            });

            document.getElementById('open-create-announcement-modal')?.addEventListener('click', () => {
                resetAnnouncementForm(createForm, 'create');
                createModal?.show();
            });

            const submitForm = async (form, url, modalInstance) => {
                const formData = new FormData(form);

                ['show_on_website', 'show_as_modal', 'is_active'].forEach((field) => {
                    if (!form.querySelector(`[name="${field}"]`).checked) {
                        formData.set(field, '0');
                    }
                });

                const response = await fetch(url, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': csrfToken,
                        'Accept': 'application/json',
                    },
                    body: formData,
                });

                if (!response.ok) {
                    await handleError(response);
                    return;
                }

                const payload = await response.json();
                modalInstance?.hide();
                window.announcementsTable.ajax.reload(null, false);
                await fireAlert({ icon: 'success', title: payload.message, timer: 1800, showConfirmButton: false });
            };

            createForm.addEventListener('submit', async (event) => {
                event.preventDefault();
                await submitForm(createForm, createForm.action, createModal);
            });

            editForm.addEventListener('submit', async (event) => {
                event.preventDefault();
                await submitForm(editForm, editForm.action, editModal);
            });

            document.addEventListener('click', async (event) => {
                const editButton = event.target.closest('.announcement-edit-btn');
                if (editButton) {
                    const row = JSON.parse(decodeURIComponent(editButton.dataset.announcement));
                    resetAnnouncementForm(editForm, 'edit');
                    editForm.action = row.update_url;
                    editForm.querySelector('[name="title"]').value = row.title ?? '';
                    editForm.querySelector('[name="subtitle"]').value = row.subtitle ?? '';
                    editForm.querySelector('[name="content"]').value = row.content ?? '';
                    editForm.querySelector('[name="button_label"]').value = row.button_label ?? '';
                    editForm.querySelector('[name="button_url"]').value = row.button_url ?? '';
                    editForm.querySelector('[name="sort_order"]').value = row.sort_order ?? 0;
                    editForm.querySelector('[name="starts_at"]').value = formatDateTimeLocal(row.starts_at ?? '');
                    editForm.querySelector('[name="ends_at"]').value = formatDateTimeLocal(row.ends_at ?? '');
                    editForm.querySelector('[name="show_on_website"]').checked = !!row.show_on_website;
                    editForm.querySelector('[name="show_as_modal"]').checked = !!row.show_as_modal;
                    editForm.querySelector('[name="is_active"]').checked = !!row.is_active;
                    renderPreview('edit', row.image_url ?? null);
                    editModal?.show();
                    return;
                }

                const deleteButton = event.target.closest('.announcement-delete-btn');
                if (deleteButton) {
                    const confirmation = await fireAlert({
                        icon: 'warning',
                        title: 'Eliminar anuncio',
                        text: `Se eliminara el anuncio ${deleteButton.dataset.title}.`,
                        showCancelButton: true,
                        confirmButtonText: 'Si, eliminar',
                        cancelButtonText: 'Cancelar',
                        confirmButtonColor: '#dc3545',
                    }, true);

                    if (!confirmation.isConfirmed) {
                        return;
                    }

                    const response = await fetch(deleteButton.dataset.url, {
                        method: 'DELETE',
                        headers: {
                            'X-CSRF-TOKEN': csrfToken,
                            'Accept': 'application/json',
                        },
                    });

                    if (!response.ok) {
                        await handleError(response);
                        return;
                    }

                    const payload = await response.json();
                    window.announcementsTable.ajax.reload(null, false);
                    await fireAlert({ icon: 'success', title: payload.message, timer: 1800, showConfirmButton: false });
                }
            });

            async function handleError(response) {
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

                await fireAlert({ icon: 'error', title: 'No se pudo completar la accion', html });
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
