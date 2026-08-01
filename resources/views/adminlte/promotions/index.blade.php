@extends('adminlte::page')

@section('title', 'Promociones')

@section('content_header')
    <div class="promotions-hero">
        <div class="promotions-hero__content">
            <span class="promotions-kicker">Estrategia comercial</span>
            <h1>Promociones</h1>
            <p>Administra campanas de descuento para habitaciones, controla vigencias, usos y visibilidad web sin modificar las tarifas base.</p>
        </div>

        @can('promociones.crear')
            <button type="button" class="btn btn-promotions-primary" id="open-create-promotion-modal">
                <i class="bi bi-plus-lg me-2" aria-hidden="true"></i> Nueva promocion
            </button>
        @endcan
    </div>
@stop

@section('content')
    <div class="row g-3 mb-4">
        <div class="col-6 col-xl-3">
            <div class="promotion-stat">
                <span class="promotion-stat__icon bg-gradient-indigo"><i class="bi bi-tags"></i></span>
                <div><small>Total</small><strong>{{ number_format($stats['total'] ?? 0) }}</strong></div>
            </div>
        </div>
        <div class="col-6 col-xl-3">
            <div class="promotion-stat">
                <span class="promotion-stat__icon bg-gradient-green"><i class="bi bi-lightning-charge"></i></span>
                <div><small>Activas hoy</small><strong>{{ number_format($stats['active'] ?? 0) }}</strong></div>
            </div>
        </div>
        <div class="col-6 col-xl-3">
            <div class="promotion-stat">
                <span class="promotion-stat__icon bg-gradient-gold"><i class="bi bi-globe-americas"></i></span>
                <div><small>Visibles web</small><strong>{{ number_format($stats['visible'] ?? 0) }}</strong></div>
            </div>
        </div>
        <div class="col-6 col-xl-3">
            <div class="promotion-stat">
                <span class="promotion-stat__icon bg-gradient-copper"><i class="bi bi-graph-up-arrow"></i></span>
                <div><small>Usos registrados</small><strong>{{ number_format($stats['used'] ?? 0) }}</strong></div>
            </div>
        </div>
    </div>

    <div class="promotions-panel">
        <div class="promotions-panel__header">
            <div>
                <span class="promotions-kicker text-primary">Campanas configuradas</span>
                <h2>Descuentos aplicables a reservas</h2>
                <p>Revisa que promociones estan vigentes, donde se muestran y a que tipos de habitacion afectan.</p>
            </div>
            <div class="promotions-panel__summary">
                <span>Vencidas</span>
                <strong>{{ number_format($stats['expired'] ?? 0) }}</strong>
                <small>activas fuera de rango</small>
            </div>
        </div>

        <div class="table-responsive">
            <table class="table table-hover align-middle w-100" id="promotions-table">
                <thead>
                    <tr>
                        <th>Promocion</th>
                        <th>Descuento</th>
                        <th>Tipos de Habitacion</th>
                        <th>Vigencia</th>
                        <th>Uso</th>
                        <th>Web</th>
                        <th>Estado</th>
                        <th class="text-end">Acciones</th>
                    </tr>
                </thead>
            </table>
        </div>
    </div>

    <div class="modal fade" id="create-promotion-modal" tabindex="-1" aria-labelledby="create-promotion-modal-label" aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-scrollable">
            <div class="modal-content">
                <form id="create-promotion-form" action="{{ route('adminlte.promotions.store') }}">
                    @csrf
                    <div class="modal-header">
                        <div>
                            <span class="promotions-kicker text-primary">Nueva campana</span>
                            <h5 class="modal-title" id="create-promotion-modal-label">Nueva promocion</h5>
                            <small class="text-muted">Configura descuentos por porcentaje o monto fijo para tipos de habitacion.</small>
                        </div>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                    </div>
                    <div class="modal-body">
                        @include('adminlte.promotions.partials.form-fields', ['prefix' => 'create', 'roomTypes' => $roomTypes, 'discountTypes' => $discountTypes])
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-promotions-primary">Guardar promocion</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="modal fade" id="edit-promotion-modal" tabindex="-1" aria-labelledby="edit-promotion-modal-label" aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-scrollable">
            <div class="modal-content">
                <form id="edit-promotion-form" method="POST">
                    @csrf
                    <input type="hidden" name="_method" value="PUT">
                    <div class="modal-header">
                        <div>
                            <span class="promotions-kicker text-primary">Edicion de campana</span>
                            <h5 class="modal-title" id="edit-promotion-modal-label">Editar promocion</h5>
                            <small class="text-muted">Actualiza reglas de descuento y habitaciones aplicables sin salir del listado.</small>
                        </div>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                    </div>
                    <div class="modal-body">
                        @include('adminlte.promotions.partials.form-fields', ['prefix' => 'edit', 'roomTypes' => $roomTypes, 'discountTypes' => $discountTypes])
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-promotions-primary">Actualizar promocion</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@stop

@push('css')
    <link rel="stylesheet" href="https://cdn.datatables.net/2.3.8/css/dataTables.bootstrap5.css">
    <style>
        .promotions-hero {
            position: relative;
            overflow: hidden;
            border-radius: 1.75rem;
            padding: 1.5rem;
            color: #fff;
            background:
                radial-gradient(circle at top right, rgba(245, 158, 11, 0.55), transparent 34%),
                linear-gradient(135deg, #2b124c 0%, #5b217a 48%, #7a2e38 100%);
            box-shadow: 0 1.5rem 4rem rgba(43, 18, 76, 0.2);
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 1rem;
        }

        .promotions-hero::after {
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

        .promotions-hero__content {
            position: relative;
            z-index: 1;
            max-width: 790px;
        }

        .promotions-hero h1 {
            margin: 0;
            font-size: clamp(1.8rem, 3vw, 2.75rem);
            font-weight: 850;
            letter-spacing: -0.04em;
        }

        .promotions-hero p {
            max-width: 720px;
            margin: 0.55rem 0 0;
            color: rgba(255, 255, 255, 0.78);
        }

        .promotions-kicker {
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

        .promotions-kicker::before {
            content: "";
            width: 1.85rem;
            height: 1px;
            background: currentColor;
            opacity: 0.75;
        }

        .btn-promotions-primary {
            position: relative;
            z-index: 1;
            border: 0;
            border-radius: 999px;
            padding: 0.78rem 1.15rem;
            color: #fff;
            font-weight: 850;
            letter-spacing: 0.02em;
            background: linear-gradient(135deg, #f59e0b, #7c3aed);
            box-shadow: 0 1rem 2.3rem rgba(124, 58, 237, 0.26);
        }

        .btn-promotions-primary:hover,
        .btn-promotions-primary:focus {
            color: #fff;
            transform: translateY(-1px);
            box-shadow: 0 1.2rem 2.8rem rgba(124, 58, 237, 0.34);
        }

        .promotion-stat {
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

        .promotion-stat__icon {
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

        .promotion-stat small {
            display: block;
            color: #6b7280;
            font-weight: 800;
            letter-spacing: 0.03em;
            text-transform: uppercase;
        }

        .promotion-stat strong {
            display: block;
            color: #161827;
            font-size: 1.45rem;
            line-height: 1.1;
        }

        .bg-gradient-indigo { background: linear-gradient(135deg, #7c3aed, #4c1d95); }
        .bg-gradient-green { background: linear-gradient(135deg, #16a34a, #166534); }
        .bg-gradient-gold { background: linear-gradient(135deg, #f59e0b, #92400e); }
        .bg-gradient-copper { background: linear-gradient(135deg, #c25f4a, #7f1d1d); }

        .promotions-panel {
            overflow: hidden;
            border: 1px solid rgba(17, 24, 39, 0.08);
            border-radius: 1.5rem;
            background: #fff;
            box-shadow: 0 1.5rem 4rem rgba(17, 24, 39, 0.08);
        }

        .promotions-panel__header {
            padding: 1.35rem 1.5rem;
            border-bottom: 1px solid rgba(17, 24, 39, 0.08);
            background:
                linear-gradient(135deg, rgba(124, 58, 237, 0.08), rgba(245, 158, 11, 0.08)),
                #fff;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 1rem;
        }

        .promotions-panel__header h2 {
            margin: 0;
            color: #161827;
            font-size: 1.25rem;
            font-weight: 850;
        }

        .promotions-panel__header p {
            margin: 0.25rem 0 0;
            color: #6b7280;
        }

        .promotions-panel__summary {
            min-width: 170px;
            padding: 0.85rem 1rem;
            border: 1px solid rgba(124, 58, 237, 0.12);
            border-radius: 1rem;
            background: rgba(255, 255, 255, 0.72);
            text-align: right;
        }

        .promotions-panel__summary span,
        .promotions-panel__summary small {
            display: block;
            color: #6b7280;
            font-size: 0.75rem;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.08em;
        }

        .promotions-panel__summary strong {
            color: #5b217a;
            font-size: 1.35rem;
        }

        #promotions-table {
            margin: 0 !important;
        }

        #promotions-table thead th {
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

        #promotions-table tbody td {
            padding-top: 1rem;
            padding-bottom: 1rem;
            border-color: rgba(17, 24, 39, 0.06);
        }

        #promotions-table tbody tr:hover {
            background: rgba(124, 58, 237, 0.035);
        }

        .promotion-name-lockup {
            display: grid;
            gap: 0.25rem;
        }

        .promotion-name {
            color: #161827;
            font-size: 1rem;
            font-weight: 850;
        }

        .promotion-slug {
            width: fit-content;
            padding: 0.18rem 0.5rem;
            border-radius: 999px;
            color: #6b7280;
            background: #f3f4f6;
            font-size: 0.72rem;
            font-weight: 700;
        }

        .promotion-muted {
            color: #6b7280;
            font-size: 0.82rem;
        }

        .promotion-discount {
            color: #5b217a;
            font-size: 1.05rem;
            font-weight: 900;
        }

        .promotion-pill {
            display: inline-flex;
            align-items: center;
            gap: 0.35rem;
            padding: 0.38rem 0.7rem;
            border-radius: 999px;
            font-size: 0.78rem;
            font-weight: 850;
        }

        .promotion-pill--type {
            color: #4c1d95;
            background: rgba(124, 58, 237, 0.1);
        }

        .promotion-pill--room {
            color: #5b3410;
            background: rgba(245, 158, 11, 0.14);
        }

        .promotion-actions {
            display: inline-flex;
            gap: 0.45rem;
        }

        .promotion-action-btn {
            width: 2.25rem;
            height: 2.25rem;
            border-radius: 0.8rem;
            display: inline-flex;
            align-items: center;
            justify-content: center;
        }

        .promotion-usage-bar {
            height: 0.45rem;
            overflow: hidden;
            border-radius: 999px;
            background: #eef2f7;
        }

        .promotion-usage-bar span {
            display: block;
            height: 100%;
            border-radius: inherit;
            background: linear-gradient(135deg, #f59e0b, #7c3aed);
        }

        #create-promotion-modal .modal-dialog,
        #edit-promotion-modal .modal-dialog {
            height: calc(100vh - 3.5rem);
        }

        #create-promotion-modal .modal-content,
        #edit-promotion-modal .modal-content {
            max-height: 100%;
            overflow: hidden;
            border: 0;
            border-radius: 1.5rem;
            box-shadow: 0 2rem 5rem rgba(17, 24, 39, 0.22);
        }

        #create-promotion-modal form,
        #edit-promotion-modal form {
            display: flex;
            flex-direction: column;
            min-height: 0;
            height: 100%;
        }

        #create-promotion-modal .modal-body,
        #edit-promotion-modal .modal-body {
            min-height: 0;
            overflow-y: auto;
            overscroll-behavior: contain;
            background: #fbfaf8;
        }

        #create-promotion-modal .modal-header,
        #edit-promotion-modal .modal-header,
        #create-promotion-modal .modal-footer,
        #edit-promotion-modal .modal-footer {
            background: #fff;
            border-color: rgba(17, 24, 39, 0.08);
        }

        .promotion-preview-card {
            border: 1px dashed rgba(124, 58, 237, 0.2);
            background:
                linear-gradient(135deg, rgba(124, 58, 237, 0.07), rgba(245, 158, 11, 0.08)),
                #fff;
            box-shadow: 0 0.75rem 1.8rem rgba(17, 24, 39, 0.04);
        }

        .promotion-form-section {
            height: 100%;
            padding: 1rem;
            border: 1px solid rgba(17, 24, 39, 0.08);
            border-radius: 1.15rem;
            background: #fff;
            box-shadow: 0 0.75rem 1.8rem rgba(17, 24, 39, 0.04);
        }

        .promotion-form-section__title {
            margin-bottom: 1rem;
            color: #161827;
            font-weight: 850;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .promotion-form-section__title i {
            color: #7c3aed;
        }

        .promotion-form-section .form-label {
            color: #374151;
            font-size: 0.82rem;
            font-weight: 850;
        }

        .promotion-form-section .form-control,
        .promotion-form-section .form-select {
            border-radius: 0.85rem;
        }

        .promotion-room-option {
            border: 1px solid rgba(17, 24, 39, 0.08);
            border-radius: 1rem;
            background: linear-gradient(135deg, rgba(255, 255, 255, 0.95), rgba(245, 158, 11, 0.05));
            transition: border-color 0.2s ease, box-shadow 0.2s ease, transform 0.2s ease;
        }

        .promotion-room-option:hover {
            border-color: rgba(124, 58, 237, 0.22);
            box-shadow: 0 0.75rem 1.6rem rgba(17, 24, 39, 0.06);
            transform: translateY(-1px);
        }

        .promotion-switch-card {
            min-height: 88px;
            padding: 0.95rem;
            border: 1px solid rgba(17, 24, 39, 0.08);
            border-radius: 1rem;
            background: linear-gradient(135deg, rgba(124, 58, 237, 0.045), rgba(245, 158, 11, 0.06));
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 0.75rem;
        }

        .promotion-switch-card strong,
        .promotion-switch-card span {
            display: block;
        }

        .promotion-switch-card strong {
            color: #161827;
            font-size: 0.92rem;
        }

        .promotion-switch-card span {
            color: #6b7280;
            font-size: 0.78rem;
        }

        .promotion-switch-card .form-check-input {
            width: 2.8rem;
            height: 1.45rem;
            cursor: pointer;
        }

        .promotion-switch-card .form-check-input:checked {
            border-color: #7c3aed;
            background-color: #7c3aed;
        }

        @media (max-width: 767.98px) {
            .promotions-hero,
            .promotions-panel__header {
                align-items: stretch;
                flex-direction: column;
            }

            .btn-promotions-primary {
                width: 100%;
            }

            .promotions-panel__summary {
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
            const createForm = document.getElementById('create-promotion-form');
            const editForm = document.getElementById('edit-promotion-form');
            const createModal = window.bootstrap ? new window.bootstrap.Modal(document.getElementById('create-promotion-modal')) : null;
            const editModal = window.bootstrap ? new window.bootstrap.Modal(document.getElementById('edit-promotion-modal')) : null;

            if (typeof $ !== 'function') {
                console.error('jQuery no esta disponible para DataTables.');
                return;
            }

            window.promotionsTable = $('#promotions-table').DataTable({
                processing: true,
                serverSide: true,
                ajax: '{{ route('adminlte.promotions.data') }}',
                language: {
                    url: 'https://cdn.datatables.net/plug-ins/1.13.8/i18n/es-ES.json',
                },
                order: [[0, 'asc']],
                columns: [
                    {
                        data: 'name',
                        name: 'name',
                        render: (data, type, row) => {
                            if (type !== 'display') {
                                return data;
                            }

                            const description = row.description ? `<div class="promotion-muted mt-1">${row.description}</div>` : '';
                            return `<div class="promotion-name-lockup">
                                <span class="promotion-name">${row.name}</span>
                                <span class="promotion-slug">${row.slug}</span>
                                ${description}
                            </div>`;
                        }
                    },
                    {
                        data: 'discount_label',
                        name: 'discount_value',
                        render: (data, type, row) => {
                            if (type !== 'display') {
                                return data;
                            }

                            const icon = row.discount_type === 'percentage' ? 'bi-percent' : 'bi-cash-coin';
                            return `<div class="promotion-discount">${row.discount_label}</div><span class="promotion-pill promotion-pill--type"><i class="bi ${icon}"></i>${row.discount_type_label}</span>`;
                        }
                    },
                    {
                        data: 'room_type_badges',
                        name: 'roomTypes.name',
                        orderable: false,
                        searchable: false,
                        render: (data, type) => {
                            if (type !== 'display') {
                                return Array.isArray(data) ? data.map((item) => item.name).join(', ') : '';
                            }

                            if (!Array.isArray(data) || data.length === 0) {
                                return '<span class="text-muted">Sin tipos asociados</span>';
                            }

                            return data.map((item) => `<span class="promotion-pill promotion-pill--room me-1 mb-1"><i class="bi bi-door-open"></i>${item.name}</span>`).join('');
                        }
                    },
                    {
                        data: 'date_range_label',
                        name: 'starts_at',
                        render: (data, type, row) => {
                            if (type !== 'display') {
                                return data;
                            }

                            return `<div class="fw-semibold">${data}</div><div class="promotion-muted">${row.date_range_detail}</div>`;
                        }
                    },
                    {
                        data: 'usage_label',
                        name: 'maximum_uses',
                        render: (data, type, row) => {
                            if (type !== 'display') {
                                return data;
                            }

                            const width = Number(row.usage_percentage || 0);
                            const bar = row.maximum_uses ? `<div class="promotion-usage-bar mt-2"><span style="width: ${width}%"></span></div>` : '';
                            return `<div class="fw-semibold">${data}</div>${bar}`;
                        }
                    },
                    {
                        data: 'show_on_website',
                        name: 'show_on_website',
                        className: 'text-center',
                        render: (data, type, row) => {
                            if (type !== 'display') {
                                return data ? 1 : 0;
                            }

                            const badgeClass = data ? 'badge text-bg-info' : 'badge text-bg-secondary';
                            const icon = data ? 'bi-globe-americas' : 'bi-eye-slash';
                            return `<span class="${badgeClass}"><i class="bi ${icon} me-1"></i>${row.show_on_website_label}</span>`;
                        }
                    },
                    {
                        data: 'status_label',
                        name: 'is_active',
                        className: 'text-center',
                        render: (data, type, row) => {
                            if (type !== 'display') {
                                return data;
                            }

                            const icon = row.status_label === 'Activa' ? 'bi-lightning-charge' : (row.status_label === 'Vencida' ? 'bi-hourglass-split' : 'bi-pause-circle');
                            return `<span class="${row.status_badge_class}"><i class="bi ${icon} me-1"></i>${row.status_label}</span>`;
                        }
                    },
                    {
                        data: null,
                        orderable: false,
                        searchable: false,
                        className: 'text-end',
                        render: (data, type, row) => {
                            if (type !== 'display') {
                                return '';
                            }

                            let actions = '<div class="promotion-actions" role="group">';

                            if (row.can_update) {
                                actions += `<button type="button" class="btn btn-outline-primary promotion-action-btn promotion-edit-btn" title="Editar" data-promotion="${encodeURIComponent(JSON.stringify(row))}">
                                    <i class="bi bi-pencil-square"></i>
                                </button>`;
                            }

                            if (row.can_delete) {
                                actions += `<button type="button" class="btn btn-outline-danger promotion-action-btn promotion-delete-btn" title="Eliminar" data-url="${row.delete_url}" data-name="${row.name}">
                                    <i class="bi bi-trash3"></i>
                                </button>`;
                            }

                            actions += '</div>';
                            return actions;
                        }
                    }
                ],
            });

            document.getElementById('open-create-promotion-modal')?.addEventListener('click', () => {
                resetPromotionForm(createForm, 'create');
                createModal?.show();
            });

            createForm.addEventListener('submit', async (event) => {
                event.preventDefault();
                await submitPromotionForm(createForm, createForm.action, createModal, false);
            });

            editForm.addEventListener('submit', async (event) => {
                event.preventDefault();
                await submitPromotionForm(editForm, editForm.action, editModal, true);
            });

            document.addEventListener('click', async (event) => {
                const editButton = event.target.closest('.promotion-edit-btn');
                if (editButton) {
                    const promotion = JSON.parse(decodeURIComponent(editButton.dataset.promotion));
                    fillEditForm(promotion);
                    editModal?.show();
                    return;
                }

                const deleteButton = event.target.closest('.promotion-delete-btn');
                if (deleteButton) {
                    await deletePromotion(deleteButton.dataset.url, deleteButton.dataset.name);
                }
            });

            createForm.querySelectorAll('[name="discount_type"], [name="discount_value"], [name="room_type_ids[]"]').forEach((field) => {
                field.addEventListener('change', () => updatePreview('create'));
                field.addEventListener('input', () => updatePreview('create'));
            });

            editForm.querySelectorAll('[name="discount_type"], [name="discount_value"], [name="room_type_ids[]"]').forEach((field) => {
                field.addEventListener('change', () => updatePreview('edit'));
                field.addEventListener('input', () => updatePreview('edit'));
            });

            function fillEditForm(promotion) {
                resetPromotionForm(editForm, 'edit');
                editForm.action = promotion.update_url;
                editForm.querySelector('[name="name"]').value = promotion.name ?? '';
                editForm.querySelector('[name="description"]').value = promotion.description ?? '';
                editForm.querySelector('[name="discount_type"]').value = promotion.discount_type ?? 'percentage';
                editForm.querySelector('[name="discount_value"]').value = promotion.discount_value ?? '';
                editForm.querySelector('[name="starts_at"]').value = promotion.starts_at ?? '';
                editForm.querySelector('[name="ends_at"]').value = promotion.ends_at ?? '';
                editForm.querySelector('[name="minimum_nights"]').value = promotion.minimum_nights ?? '';
                editForm.querySelector('[name="maximum_uses"]').value = promotion.maximum_uses ?? '';
                editForm.querySelector('[name="show_on_website"]').checked = !!promotion.show_on_website;
                editForm.querySelector('[name="is_active"]').checked = !!promotion.is_active;

                (promotion.room_type_ids ?? []).forEach((id) => {
                    const checkbox = editForm.querySelector(`input[name="room_type_ids[]"][value="${id}"]`);
                    if (checkbox) {
                        checkbox.checked = true;
                    }
                });

                updatePreview('edit');
            }

            function resetPromotionForm(form, prefix) {
                form.reset();
                form.querySelector('[name="discount_type"]').value = 'percentage';
                form.querySelector('[name="show_on_website"]').checked = true;
                form.querySelector('[name="is_active"]').checked = true;
                form.querySelectorAll('input[name="room_type_ids[]"]').forEach((checkbox) => {
                    checkbox.checked = false;
                });
                const previewLabel = document.getElementById(`${prefix}-promotion-preview-label`);
                if (previewLabel) {
                    previewLabel.textContent = 'Selecciona un tipo de habitacion, tipo de descuento y valor para ver el calculo.';
                }
            }

            function updatePreview(prefix) {
                const form = prefix === 'create' ? createForm : editForm;
                const checkedRoomType = form.querySelector('input[name="room_type_ids[]"]:checked');
                const discountType = form.querySelector('[name="discount_type"]').value;
                const discountValue = parseFloat(form.querySelector('[name="discount_value"]').value || '0');
                const previewLabel = document.getElementById(`${prefix}-promotion-preview-label`);

                if (!previewLabel) {
                    return;
                }

                if (!checkedRoomType || !discountValue) {
                    previewLabel.textContent = 'Selecciona un tipo de habitacion, tipo de descuento y valor para ver el calculo.';
                    return;
                }

                const basePrice = parseFloat(checkedRoomType.dataset.basePrice || '0');
                const discountAmount = discountType === 'percentage'
                    ? Math.min((basePrice * discountValue) / 100, basePrice)
                    : Math.min(discountValue, basePrice);
                const finalPrice = Math.max(basePrice - discountAmount, 0);

                previewLabel.textContent = `Bs. ${basePrice.toFixed(2)} - Bs. ${discountAmount.toFixed(2)} = Bs. ${finalPrice.toFixed(2)}`;
            }

            async function submitPromotionForm(form, url, modalInstance, useMethodOverride) {
                const formData = new FormData(form);

                if (!form.querySelector('[name="show_on_website"]').checked) {
                    formData.set('show_on_website', '0');
                }

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
                window.promotionsTable.ajax.reload(null, false);

                await fireAlert({
                    icon: 'success',
                    title: payload.message || 'Operacion completada correctamente.',
                    timer: 1800,
                    showConfirmButton: false,
                });
            }

            async function deletePromotion(url, name) {
                const confirmation = await fireAlert({
                    icon: 'warning',
                    title: 'Eliminar promocion',
                    text: `Se eliminara la promocion ${name}.`,
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
                window.promotionsTable.ajax.reload(null, false);

                await fireAlert({
                    icon: 'success',
                    title: payload.message || 'Promocion eliminada correctamente.',
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
                    return { isConfirmed: window.confirm(options.text || options.title || '') };
                }

                window.alert(options.text || options.title || '');
                return { isConfirmed: true };
            }
        });
    </script>
@endpush
