@extends('adminlte::page')

@section('title', 'Tipos de Habitacion')

@section('content_header')
    <div class="room-types-hero">
        <div class="room-types-hero__content">
            <span class="room-types-kicker">Catalogo comercial</span>
            <h1>Tipos de Habitacion</h1>
            <p>
                Configura tarifas en bolivianos y dolares, capacidad, visibilidad web y el anticipo requerido para confirmar reservas.
            </p>
        </div>

        @can('tipos_habitacion.crear')
            <button type="button" class="btn btn-room-type-primary" id="open-create-room-type-modal">
                <i class="bi bi-plus-lg me-2" aria-hidden="true"></i> Nuevo tipo de habitacion
            </button>
        @endcan
    </div>
@stop

@section('content')
    <div class="row g-3 mb-4">
        <div class="col-6 col-xl-3">
            <div class="room-type-stat">
                <span class="room-type-stat__icon bg-gradient-indigo"><i class="bi bi-door-open"></i></span>
                <div>
                    <small>Total tipos</small>
                    <strong>{{ number_format($stats['total'] ?? 0) }}</strong>
                </div>
            </div>
        </div>
        <div class="col-6 col-xl-3">
            <div class="room-type-stat">
                <span class="room-type-stat__icon bg-gradient-green"><i class="bi bi-check2-circle"></i></span>
                <div>
                    <small>Activos</small>
                    <strong>{{ number_format($stats['active'] ?? 0) }}</strong>
                </div>
            </div>
        </div>
        <div class="col-6 col-xl-3">
            <div class="room-type-stat">
                <span class="room-type-stat__icon bg-gradient-gold"><i class="bi bi-globe-americas"></i></span>
                <div>
                    <small>Visibles en web</small>
                    <strong>{{ number_format($stats['visible'] ?? 0) }}</strong>
                </div>
            </div>
        </div>
        <div class="col-6 col-xl-3">
            <div class="room-type-stat">
                <span class="room-type-stat__icon bg-gradient-copper"><i class="bi bi-shield-check"></i></span>
                <div>
                    <small>Anticipo promedio</small>
                    <strong>{{ (int) ($stats['average_deposit'] ?? 0) }}%</strong>
                </div>
            </div>
        </div>
    </div>

    <div class="room-type-panel">
        <div class="room-type-panel__header">
            <div>
                <span class="room-types-kicker text-primary">Inventario comercial</span>
                <h2>Habitaciones listas para vender</h2>
                <p>Controla que ve el cliente, cuanto paga y que anticipo necesita cada categoria antes de confirmar.</p>
            </div>
            <div class="room-type-panel__amount">
                <span>Tarifa promedio</span>
                <strong>Bs. {{ number_format((float) ($stats['average_price_bob'] ?? 0), 2, '.', '') }}</strong>
            </div>
        </div>

        <div class="table-responsive">
            <table class="table table-hover align-middle w-100" id="room-types-table">
                <thead>
                    <tr>
                        <th>Imagen</th>
                        <th>Tipo</th>
                        <th>Precios</th>
                        <th>Anticipo</th>
                        <th>Habitaciones</th>
                        <th>Capacidad</th>
                        <th>Web</th>
                        <th>Estado</th>
                        <th>Registro</th>
                        <th class="text-end">Acciones</th>
                    </tr>
                </thead>
            </table>
        </div>
    </div>

    <div class="modal fade" id="create-room-type-modal" tabindex="-1" aria-labelledby="create-room-type-modal-label" aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-scrollable">
            <div class="modal-content">
                <form id="create-room-type-form" action="{{ route('adminlte.room-types.store') }}">
                    @csrf
                    <div class="modal-header">
                        <div>
                            <span class="room-types-kicker text-primary">Nueva categoria</span>
                            <h5 class="modal-title" id="create-room-type-modal-label">Crear tipo de habitacion</h5>
                            <small class="text-muted">Define precios, capacidad, anticipo e imagen comercial.</small>
                        </div>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                    </div>
                    <div class="modal-body">
                        @include('adminlte.room-types.partials.form-fields', ['prefix' => 'create'])
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-room-type-primary">Guardar tipo</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="modal fade" id="edit-room-type-modal" tabindex="-1" aria-labelledby="edit-room-type-modal-label" aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-scrollable">
            <div class="modal-content">
                <form id="edit-room-type-form" method="POST">
                    @csrf
                    <input type="hidden" name="_method" value="PUT">
                    <div class="modal-header">
                        <div>
                            <span class="room-types-kicker text-primary">Edicion comercial</span>
                            <h5 class="modal-title" id="edit-room-type-modal-label">Editar tipo de habitacion</h5>
                            <small class="text-muted">Actualiza informacion comercial sin salir del listado.</small>
                        </div>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                    </div>
                    <div class="modal-body">
                        @include('adminlte.room-types.partials.form-fields', ['prefix' => 'edit'])
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-room-type-primary">Actualizar tipo</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@stop

@push('css')
    <link rel="stylesheet" href="https://cdn.datatables.net/2.3.8/css/dataTables.bootstrap5.css">
    <style>
        .room-types-hero {
            position: relative;
            overflow: hidden;
            border-radius: 1.75rem;
            padding: 1.5rem;
            color: #fff;
            background:
                radial-gradient(circle at top right, rgba(205, 140, 42, 0.55), transparent 34%),
                linear-gradient(135deg, #24104d 0%, #3b1579 48%, #141827 100%);
            box-shadow: 0 1.5rem 4rem rgba(35, 16, 78, 0.22);
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 1rem;
        }

        .room-types-hero::after {
            content: "";
            position: absolute;
            inset: auto -6rem -7rem auto;
            width: 18rem;
            height: 18rem;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.08);
            pointer-events: none;
        }

        .room-types-hero__content {
            position: relative;
            z-index: 1;
            max-width: 780px;
        }

        .room-types-hero h1 {
            margin: 0;
            font-size: clamp(1.8rem, 3vw, 2.8rem);
            font-weight: 800;
            letter-spacing: -0.04em;
        }

        .room-types-hero p {
            max-width: 680px;
            margin: 0.55rem 0 0;
            color: rgba(255, 255, 255, 0.78);
            font-size: 1rem;
        }

        .room-types-kicker {
            display: inline-flex;
            align-items: center;
            gap: 0.45rem;
            margin-bottom: 0.4rem;
            font-size: 0.72rem;
            font-weight: 800;
            letter-spacing: 0.14em;
            text-transform: uppercase;
            color: rgba(255, 255, 255, 0.72);
        }

        .room-types-kicker::before {
            content: "";
            width: 1.85rem;
            height: 1px;
            background: currentColor;
            opacity: 0.75;
        }

        .btn-room-type-primary {
            position: relative;
            z-index: 1;
            border: 0;
            border-radius: 999px;
            padding: 0.78rem 1.15rem;
            color: #fff;
            font-weight: 800;
            letter-spacing: 0.02em;
            background: linear-gradient(135deg, #df941b, #5b26b7);
            box-shadow: 0 1rem 2.3rem rgba(91, 38, 183, 0.28);
        }

        .btn-room-type-primary:hover,
        .btn-room-type-primary:focus {
            color: #fff;
            transform: translateY(-1px);
            box-shadow: 0 1.2rem 2.8rem rgba(91, 38, 183, 0.36);
        }

        .room-type-stat {
            min-height: 104px;
            padding: 1.05rem;
            border: 1px solid rgba(17, 24, 39, 0.08);
            border-radius: 1.35rem;
            background: rgba(255, 255, 255, 0.92);
            box-shadow: 0 1rem 2.4rem rgba(17, 24, 39, 0.07);
            display: flex;
            align-items: center;
            gap: 0.95rem;
        }

        .room-type-stat__icon {
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

        .room-type-stat small {
            display: block;
            color: #6b7280;
            font-weight: 700;
            letter-spacing: 0.03em;
            text-transform: uppercase;
        }

        .room-type-stat strong {
            display: block;
            color: #161827;
            font-size: 1.45rem;
            line-height: 1.1;
        }

        .bg-gradient-indigo { background: linear-gradient(135deg, #5b26b7, #2a0f66); }
        .bg-gradient-green { background: linear-gradient(135deg, #16865a, #0f5132); }
        .bg-gradient-gold { background: linear-gradient(135deg, #df941b, #8a5610); }
        .bg-gradient-copper { background: linear-gradient(135deg, #c25f4a, #6f2d44); }

        .room-type-panel {
            overflow: hidden;
            border: 1px solid rgba(17, 24, 39, 0.08);
            border-radius: 1.5rem;
            background: #fff;
            box-shadow: 0 1.5rem 4rem rgba(17, 24, 39, 0.08);
        }

        .room-type-panel__header {
            padding: 1.35rem 1.5rem;
            border-bottom: 1px solid rgba(17, 24, 39, 0.08);
            background:
                linear-gradient(135deg, rgba(91, 38, 183, 0.08), rgba(223, 148, 27, 0.08)),
                #fff;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 1rem;
        }

        .room-type-panel__header h2 {
            margin: 0;
            color: #161827;
            font-size: 1.25rem;
            font-weight: 800;
        }

        .room-type-panel__header p {
            margin: 0.25rem 0 0;
            color: #6b7280;
        }

        .room-type-panel__amount {
            min-width: 185px;
            padding: 0.85rem 1rem;
            border: 1px solid rgba(91, 38, 183, 0.12);
            border-radius: 1rem;
            background: rgba(255, 255, 255, 0.72);
            text-align: right;
        }

        .room-type-panel__amount span {
            display: block;
            color: #6b7280;
            font-size: 0.75rem;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.08em;
        }

        .room-type-panel__amount strong {
            color: #3b1579;
            font-size: 1.2rem;
        }

        #room-types-table {
            margin: 0 !important;
        }

        #room-types-table thead th {
            padding-top: 1rem;
            padding-bottom: 1rem;
            border-bottom: 1px solid rgba(17, 24, 39, 0.08);
            color: #6b7280;
            font-size: 0.74rem;
            font-weight: 800;
            letter-spacing: 0.08em;
            text-transform: uppercase;
            background: #fbfaf8;
        }

        #room-types-table tbody td {
            padding-top: 1rem;
            padding-bottom: 1rem;
            border-color: rgba(17, 24, 39, 0.06);
        }

        #room-types-table tbody tr {
            transition: background-color 0.2s ease, transform 0.2s ease;
        }

        #room-types-table tbody tr:hover {
            background: rgba(91, 38, 183, 0.035);
        }

        #create-room-type-modal .modal-dialog,
        #edit-room-type-modal .modal-dialog {
            height: calc(100vh - 3.5rem);
        }

        #create-room-type-modal .modal-content,
        #edit-room-type-modal .modal-content {
            max-height: 100%;
            overflow: hidden;
            border: 0;
            border-radius: 1.5rem;
            box-shadow: 0 2rem 5rem rgba(17, 24, 39, 0.22);
        }

        #create-room-type-modal form,
        #edit-room-type-modal form {
            display: flex;
            flex-direction: column;
            min-height: 0;
            height: 100%;
        }

        #create-room-type-modal .modal-body,
        #edit-room-type-modal .modal-body {
            min-height: 0;
            overflow-y: auto;
            overscroll-behavior: contain;
            background: #fbfaf8;
        }

        #create-room-type-modal .modal-header,
        #edit-room-type-modal .modal-header {
            border-bottom: 1px solid rgba(17, 24, 39, 0.08);
            background: #fff;
        }

        #create-room-type-modal .modal-footer,
        #edit-room-type-modal .modal-footer {
            border-top: 1px solid rgba(17, 24, 39, 0.08);
            background: #fff;
        }

        .room-type-thumb {
            width: 72px;
            height: 72px;
            object-fit: cover;
            border-radius: 1rem;
            border: 1px solid rgba(0, 0, 0, 0.08);
            box-shadow: 0 0.8rem 1.6rem rgba(17, 24, 39, 0.12);
        }

        .room-type-thumb-fallback {
            width: 72px;
            height: 72px;
            border-radius: 1rem;
            background: linear-gradient(135deg, rgba(91, 38, 183, 0.1), rgba(223, 148, 27, 0.14));
            color: #5b26b7;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border: 1px dashed rgba(91, 38, 183, 0.2);
            font-size: 1.55rem;
        }

        .room-type-name {
            color: #161827;
            font-size: 1rem;
            font-weight: 800;
        }

        .room-type-slug {
            display: inline-flex;
            margin-top: 0.25rem;
            padding: 0.18rem 0.5rem;
            border-radius: 999px;
            color: #6b7280;
            background: #f3f4f6;
            font-size: 0.72rem;
            font-weight: 700;
        }

        .room-price-main {
            color: #3b1579;
            font-weight: 850;
        }

        .room-price-secondary,
        .room-type-muted {
            color: #6b7280;
            font-size: 0.82rem;
        }

        .room-type-pill {
            display: inline-flex;
            align-items: center;
            gap: 0.35rem;
            padding: 0.38rem 0.7rem;
            border-radius: 999px;
            font-size: 0.78rem;
            font-weight: 800;
        }

        .room-type-pill--deposit {
            color: #5b3410;
            background: rgba(223, 148, 27, 0.18);
        }

        .room-type-pill--rooms {
            color: #2a0f66;
            background: rgba(91, 38, 183, 0.1);
        }

        .room-type-actions {
            display: inline-flex;
            gap: 0.45rem;
        }

        .room-type-action-btn {
            width: 2.25rem;
            height: 2.25rem;
            border-radius: 0.8rem;
            display: inline-flex;
            align-items: center;
            justify-content: center;
        }

        .room-type-action-btn.btn-outline-primary {
            color: #5b26b7;
            border-color: rgba(91, 38, 183, 0.25);
        }

        .room-type-action-btn.btn-outline-primary:hover {
            color: #fff;
            background: #5b26b7;
            border-color: #5b26b7;
        }

        .room-type-preview {
            min-height: 160px;
            background:
                linear-gradient(135deg, rgba(91, 38, 183, 0.08), rgba(223, 148, 27, 0.11)),
                #fff;
            border-style: dashed !important;
        }

        .room-type-preview img {
            width: 100%;
            height: 110px;
            object-fit: cover;
        }

        .room-type-preview-gallery {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 0.65rem;
        }

        .room-type-preview-gallery img {
            border-radius: 0.85rem;
            box-shadow: 0 0.75rem 1.6rem rgba(17, 24, 39, 0.1);
        }

        .room-type-form-section {
            height: 100%;
            padding: 1rem;
            border: 1px solid rgba(17, 24, 39, 0.08);
            border-radius: 1.15rem;
            background: #fff;
            box-shadow: 0 0.75rem 1.8rem rgba(17, 24, 39, 0.04);
        }

        .room-type-form-section__title {
            margin-bottom: 1rem;
            color: #161827;
            font-weight: 800;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .room-type-form-section__title i {
            color: #5b26b7;
        }

        .room-type-form-section .form-label {
            color: #374151;
            font-size: 0.82rem;
            font-weight: 800;
        }

        .room-type-form-section .form-control,
        .room-type-form-section .form-select {
            border-radius: 0.85rem;
        }

        .room-type-form-section .input-group-text {
            border-radius: 0.85rem 0 0 0.85rem;
            color: #5b26b7;
            font-weight: 800;
            background: rgba(91, 38, 183, 0.08);
        }

        .room-type-form-section .input-group .form-control {
            border-radius: 0 0.85rem 0.85rem 0;
        }

        .room-type-switch-card {
            min-height: 92px;
            padding: 0.95rem;
            border: 1px solid rgba(17, 24, 39, 0.08);
            border-radius: 1rem;
            background: linear-gradient(135deg, rgba(91, 38, 183, 0.045), rgba(223, 148, 27, 0.06));
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 0.75rem;
        }

        .room-type-switch-card strong,
        .room-type-switch-card span {
            display: block;
        }

        .room-type-switch-card strong {
            color: #161827;
            font-size: 0.92rem;
        }

        .room-type-switch-card span {
            color: #6b7280;
            font-size: 0.78rem;
        }

        .room-type-switch-card .form-check-input {
            width: 2.8rem;
            height: 1.45rem;
            cursor: pointer;
        }

        .room-type-switch-card .form-check-input:checked {
            border-color: #5b26b7;
            background-color: #5b26b7;
        }

        @media (max-width: 767.98px) {
            .room-types-hero,
            .room-type-panel__header {
                align-items: stretch;
                flex-direction: column;
            }

            .btn-room-type-primary {
                width: 100%;
            }

            .room-type-panel__amount {
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
            const createModalElement = document.getElementById('create-room-type-modal');
            const editModalElement = document.getElementById('edit-room-type-modal');
            const createModal = window.bootstrap ? new window.bootstrap.Modal(createModalElement) : null;
            const editModal = window.bootstrap ? new window.bootstrap.Modal(editModalElement) : null;
            const createForm = document.getElementById('create-room-type-form');
            const editForm = document.getElementById('edit-room-type-form');

            if (typeof $ !== 'function') {
                console.error('jQuery no esta disponible para DataTables.');
                return;
            }

            window.roomTypesTable = $('#room-types-table').DataTable({
                processing: true,
                serverSide: true,
                ajax: '{{ route('adminlte.room-types.data') }}',
                language: {
                    url: 'https://cdn.datatables.net/plug-ins/1.13.8/i18n/es-ES.json',
                },
                order: [[8, 'desc']],
                columns: [
                    {
                        data: 'main_image_url',
                        name: 'main_image',
                        orderable: false,
                        searchable: false,
                        render: (data, type) => {
                            if (type !== 'display') {
                                return data || '';
                            }

                            if (data) {
                                return `<img src="${data}" alt="Imagen del tipo" class="room-type-thumb">`;
                            }

                            return '<span class="room-type-thumb-fallback"><i class="bi bi-house-heart" aria-hidden="true"></i></span>';
                        }
                    },
                    {
                        data: 'name',
                        name: 'name',
                        render: (data, type, row) => {
                            if (type !== 'display') {
                                return data;
                            }

                            return `<div class="room-type-name">${row.name}</div><span class="room-type-slug">${row.slug}</span>`;
                        }
                    },
                    {
                        data: 'price_summary_formatted',
                        name: 'price_bob',
                        render: (data, type, row) => {
                            if (type !== 'display') {
                                return row.price_bob ?? 0;
                            }

                            return `<div class="room-price-main">${row.price_bob_formatted}</div><div class="room-price-secondary">${row.price_usd_formatted}</div>`;
                        }
                    },
                    {
                        data: 'deposit_percentage_label',
                        name: 'reservation_deposit_percentage',
                        className: 'text-center',
                        render: (data, type, row) => {
                            if (type !== 'display') {
                                return row.reservation_deposit_percentage ?? 20;
                            }

                            return `<span class="room-type-pill room-type-pill--deposit"><i class="bi bi-shield-check"></i>${data}</span><div class="room-type-muted mt-1">para confirmar</div>`;
                        }
                    },
                    {
                        data: 'rooms_count',
                        name: 'rooms_count',
                        className: 'text-center',
                        searchable: false,
                        orderable: false,
                        render: (data, type) => {
                            if (type !== 'display') {
                                return data ?? 0;
                            }

                            return `<span class="room-type-pill room-type-pill--rooms"><i class="bi bi-door-open"></i>${data ?? 0}</span>`;
                        }
                    },
                    { data: 'capacity_summary', name: 'max_guests', orderable: false, searchable: false },
                    {
                        data: 'show_on_website',
                        name: 'show_on_website',
                        className: 'text-center',
                        render: (data, type, row) => {
                            if (type !== 'display') {
                                return data ? 1 : 0;
                            }

                            const badgeClass = data ? 'text-bg-info' : 'text-bg-secondary';
                            return `<span class="badge ${badgeClass}">${row.show_on_website_label}</span>`;
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
                            return `<span class="badge ${badgeClass}">${row.status_label}</span>`;
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

                            let actions = '<div class="room-type-actions" role="group">';

                            if (row.can_update) {
                                actions += `<button type="button" class="btn btn-outline-primary room-type-action-btn room-type-edit-btn" title="Editar" data-room-type="${encodeURIComponent(JSON.stringify(row))}">
                                    <i class="bi bi-pencil-square"></i>
                                </button>`;
                            }

                            if (row.can_delete) {
                                actions += `<button type="button" class="btn btn-outline-danger room-type-action-btn room-type-delete-btn" title="Eliminar" data-url="${row.delete_url}" data-name="${row.name}">
                                    <i class="bi bi-trash3"></i>
                                </button>`;
                            }

                            actions += '</div>';
                            return actions;
                        }
                    }
                ],
            });

            document.getElementById('open-create-room-type-modal')?.addEventListener('click', () => {
                resetForm(createForm, 'create');
                createModal?.show();
            });

            createForm.addEventListener('submit', async (event) => {
                event.preventDefault();
                await submitRoomTypeForm(createForm, createForm.action, createModal, false);
            });

            editForm.addEventListener('submit', async (event) => {
                event.preventDefault();
                await submitRoomTypeForm(editForm, editForm.action, editModal, true);
            });

            document.addEventListener('click', async (event) => {
                const editButton = event.target.closest('.room-type-edit-btn');
                if (editButton) {
                    const roomType = JSON.parse(decodeURIComponent(editButton.dataset.roomType));
                    fillEditForm(roomType);
                    editModal?.show();
                    return;
                }

                const deleteButton = event.target.closest('.room-type-delete-btn');
                if (deleteButton) {
                    await deleteRoomType(deleteButton.dataset.url, deleteButton.dataset.name);
                }
            });

            createForm.querySelector('[name="gallery_images[]"]')?.addEventListener('change', (event) => updatePreview(event.target, 'create'));
            editForm.querySelector('[name="gallery_images[]"]')?.addEventListener('change', (event) => updatePreview(event.target, 'edit'));

            function fillEditForm(roomType) {
                resetForm(editForm, 'edit');
                editForm.action = roomType.update_url;
                editForm.querySelector('[name="name"]').value = roomType.name ?? '';
                editForm.querySelector('[name="description"]').value = roomType.description ?? '';
                editForm.querySelector('[name="price_bob"]').value = roomType.price_bob ?? roomType.base_price ?? 0;
                editForm.querySelector('[name="price_usd"]').value = roomType.price_usd ?? 0;
                editForm.querySelector('[name="reservation_deposit_percentage"]').value = roomType.reservation_deposit_percentage ?? 20;
                editForm.querySelector('[name="capacity_adults"]').value = roomType.capacity_adults ?? 1;
                editForm.querySelector('[name="capacity_children"]').value = roomType.capacity_children ?? 0;
                editForm.querySelector('[name="max_guests"]').value = roomType.max_guests ?? 1;
                editForm.querySelector('[name="amenities"]').value = Array.isArray(roomType.amenities) ? roomType.amenities.join('\n') : '';
                editForm.querySelector('[name="show_on_website"]').checked = !!roomType.show_on_website;
                editForm.querySelector('[name="is_active"]').checked = !!roomType.is_active;
                setExistingPreview('edit', roomType.gallery_image_urls ?? []);
            }

            function resetForm(form, prefix) {
                form.reset();
                form.querySelector('[name="capacity_adults"]').value = 1;
                form.querySelector('[name="capacity_children"]').value = 0;
                form.querySelector('[name="max_guests"]').value = 1;
                form.querySelector('[name="price_bob"]').value = '0.00';
                form.querySelector('[name="price_usd"]').value = '0.00';
                form.querySelector('[name="reservation_deposit_percentage"]').value = '20';
                form.querySelector('[name="show_on_website"]').checked = true;
                form.querySelector('[name="is_active"]').checked = true;
                form.querySelector('[name="gallery_images[]"]').value = '';
                const previewGallery = document.getElementById(`${prefix}-room-type-preview-gallery`);
                const previewPlaceholder = document.getElementById(`${prefix}-room-type-preview-placeholder`);
                const previewText = document.getElementById(`${prefix}-room-type-preview-text`);

                if (previewGallery) {
                    previewGallery.classList.add('d-none');
                    previewGallery.innerHTML = '';
                }

                if (previewPlaceholder) {
                    previewPlaceholder.classList.remove('d-none');
                }

                if (previewText) {
                    previewText.textContent = 'Sin imagen cargada';
                }
            }

            function setExistingPreview(prefix, imageUrls) {
                const previewGallery = document.getElementById(`${prefix}-room-type-preview-gallery`);
                const previewPlaceholder = document.getElementById(`${prefix}-room-type-preview-placeholder`);
                const previewText = document.getElementById(`${prefix}-room-type-preview-text`);

                if (!previewGallery || !previewPlaceholder || !previewText) {
                    return;
                }

                if (Array.isArray(imageUrls) && imageUrls.length) {
                    previewGallery.innerHTML = imageUrls.slice(0, 4).map((imageUrl, index) => `<img src="${imageUrl}" alt="Imagen ${index + 1}">`).join('');
                    previewGallery.classList.remove('d-none');
                    previewPlaceholder.classList.add('d-none');
                    previewText.textContent = `${Math.min(imageUrls.length, 4)} imagen(es) actuales`;
                    return;
                }

                previewGallery.classList.add('d-none');
                previewGallery.innerHTML = '';
                previewPlaceholder.classList.remove('d-none');
                previewText.textContent = 'Sin imagen cargada';
            }

            function updatePreview(input, prefix) {
                const files = Array.from(input.files ?? []);
                const previewGallery = document.getElementById(`${prefix}-room-type-preview-gallery`);
                const previewPlaceholder = document.getElementById(`${prefix}-room-type-preview-placeholder`);
                const previewText = document.getElementById(`${prefix}-room-type-preview-text`);

                if (!previewGallery || !previewPlaceholder || !previewText) {
                    return;
                }

                if (files.length > 4) {
                    input.value = '';
                    setExistingPreview(prefix, []);
                    fireAlert({
                        icon: 'warning',
                        title: 'Maximo 4 imagenes',
                        text: 'Selecciona entre 1 y 4 imagenes para este tipo de habitacion.',
                    });
                    return;
                }

                if (!files.length) {
                    if (prefix === 'edit') {
                        previewText.textContent = 'Sin cambios en la imagen';
                    } else {
                        previewText.textContent = 'Sin imagen cargada';
                    }
                    return;
                }

                previewGallery.innerHTML = files.map((file, index) => `<img src="${URL.createObjectURL(file)}" alt="Vista previa ${index + 1}">`).join('');
                previewGallery.classList.remove('d-none');
                previewPlaceholder.classList.add('d-none');
                previewText.textContent = `${files.length} imagen(es) seleccionadas`;
            }

            async function submitRoomTypeForm(form, url, modalInstance, useMethodOverride) {
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
                window.roomTypesTable.ajax.reload(null, false);

                await fireAlert({
                    icon: 'success',
                    title: payload.message || 'Operacion completada correctamente.',
                    timer: 1800,
                    showConfirmButton: false,
                });
            }

            async function deleteRoomType(url, name) {
                const confirmation = await fireAlert({
                    icon: 'warning',
                    title: 'Eliminar tipo de habitacion',
                    text: `Se eliminara el tipo ${name}.`,
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
                window.roomTypesTable.ajax.reload(null, false);

                await fireAlert({
                    icon: 'success',
                    title: payload.message || 'Tipo de habitacion eliminado correctamente.',
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
