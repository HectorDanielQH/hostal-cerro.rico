@extends('adminlte::page')

@section('title', 'Clientes / Huespedes')

@section('content_header')
    <div class="customers-hero">
        <div class="customers-hero__content">
            <span class="customers-kicker">Gestion de huespedes</span>
            <h1>Clientes / Huespedes</h1>
            <p>Centraliza datos personales, documentos, contacto, empresas y vinculaciones de usuario para reservas presentes y futuras.</p>
        </div>

        @can('clientes.crear')
            <button type="button" class="btn btn-customers-primary" id="open-create-customer-modal">
                <i class="bi bi-person-plus me-2" aria-hidden="true"></i> Nuevo cliente
            </button>
        @endcan
    </div>
@stop

@section('content')
    <div class="row g-3 mb-4">
        <div class="col-6 col-xl-3">
            <div class="customer-stat">
                <span class="customer-stat__icon bg-gradient-indigo"><i class="bi bi-people"></i></span>
                <div><small>Total</small><strong>{{ number_format($stats['total'] ?? 0) }}</strong></div>
            </div>
        </div>
        <div class="col-6 col-xl-3">
            <div class="customer-stat">
                <span class="customer-stat__icon bg-gradient-green"><i class="bi bi-check2-circle"></i></span>
                <div><small>Activos</small><strong>{{ number_format($stats['active'] ?? 0) }}</strong></div>
            </div>
        </div>
        <div class="col-6 col-xl-3">
            <div class="customer-stat">
                <span class="customer-stat__icon bg-gradient-gold"><i class="bi bi-passport"></i></span>
                <div><small>Extranjeros</small><strong>{{ number_format($stats['foreign'] ?? 0) }}</strong></div>
            </div>
        </div>
        <div class="col-6 col-xl-3">
            <div class="customer-stat">
                <span class="customer-stat__icon bg-gradient-copper"><i class="bi bi-building"></i></span>
                <div><small>Empresas</small><strong>{{ number_format($stats['companies'] ?? 0) }}</strong></div>
            </div>
        </div>
    </div>

    <div class="customers-panel">
        <div class="customers-panel__header">
            <div>
                <span class="customers-kicker text-primary">Base de clientes</span>
                <h2>Historial y contacto de huespedes</h2>
                <p>Identifica rapidamente clientes, documentos, contacto y actividad relacionada con reservas o pagos.</p>
            </div>
            <div class="customers-panel__summary">
                <span>Clientes activos</span>
                <strong>{{ number_format($stats['active'] ?? 0) }}</strong>
                <small>sin acceso por login</small>
            </div>
        </div>

        <div class="table-responsive">
            <table class="table table-hover align-middle w-100" id="customers-table">
                <thead>
                    <tr>
                        <th>Cliente</th>
                        <th>Documento</th>
                        <th>Contacto</th>
                        <th>Nacionalidad</th>
                        <th>Tipo</th>
                        <th>Actividad</th>
                        <th>Estado</th>
                        <th>Registro</th>
                        <th class="text-end">Acciones</th>
                    </tr>
                </thead>
            </table>
        </div>
    </div>

    <div class="modal fade" id="create-customer-modal" tabindex="-1" aria-labelledby="create-customer-modal-label" aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-scrollable">
            <div class="modal-content">
                <form id="create-customer-form" action="{{ route('adminlte.customers.store') }}">
                    @csrf
                    <div class="modal-header">
                        <div>
                            <span class="customers-kicker text-primary">Nuevo perfil</span>
                            <h5 class="modal-title" id="create-customer-modal-label">Nuevo cliente / huesped</h5>
                            <small class="text-muted">Registra clientes nacionales, extranjeros o empresas sin salir del panel.</small>
                        </div>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                    </div>
                    <div class="modal-body">
                        @include('adminlte.customers.partials.form-fields', ['prefix' => 'create', 'documentTypes' => $documentTypes])
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-customers-primary">Guardar cliente</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="modal fade" id="edit-customer-modal" tabindex="-1" aria-labelledby="edit-customer-modal-label" aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-scrollable">
            <div class="modal-content">
                <form id="edit-customer-form" method="POST">
                    @csrf
                    <input type="hidden" name="_method" value="PUT">
                    <div class="modal-header">
                        <div>
                            <span class="customers-kicker text-primary">Edicion de perfil</span>
                            <h5 class="modal-title" id="edit-customer-modal-label">Editar cliente / huesped</h5>
                            <small class="text-muted">Actualiza documentos, contacto, empresa y observaciones.</small>
                        </div>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                    </div>
                    <div class="modal-body">
                        @include('adminlte.customers.partials.form-fields', ['prefix' => 'edit', 'documentTypes' => $documentTypes])
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-customers-primary">Actualizar cliente</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@stop

@push('css')
    <link rel="stylesheet" href="https://cdn.datatables.net/2.3.8/css/dataTables.bootstrap5.css">
    <style>
        .customers-hero {
            position: relative;
            overflow: hidden;
            border-radius: 1.75rem;
            padding: 1.5rem;
            color: #fff;
            background:
                radial-gradient(circle at top right, rgba(20, 184, 166, 0.48), transparent 34%),
                linear-gradient(135deg, #102947 0%, #173d67 48%, #2f145b 100%);
            box-shadow: 0 1.5rem 4rem rgba(16, 41, 71, 0.2);
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 1rem;
        }

        .customers-hero::after {
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

        .customers-hero__content {
            position: relative;
            z-index: 1;
            max-width: 790px;
        }

        .customers-hero h1 {
            margin: 0;
            font-size: clamp(1.8rem, 3vw, 2.75rem);
            font-weight: 850;
            letter-spacing: -0.04em;
        }

        .customers-hero p {
            max-width: 720px;
            margin: 0.55rem 0 0;
            color: rgba(255, 255, 255, 0.78);
        }

        .customers-kicker {
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

        .customers-kicker::before {
            content: "";
            width: 1.85rem;
            height: 1px;
            background: currentColor;
            opacity: 0.75;
        }

        .btn-customers-primary {
            position: relative;
            z-index: 1;
            border: 0;
            border-radius: 999px;
            padding: 0.78rem 1.15rem;
            color: #fff;
            font-weight: 850;
            letter-spacing: 0.02em;
            background: linear-gradient(135deg, #14b8a6, #245f9d);
            box-shadow: 0 1rem 2.3rem rgba(36, 95, 157, 0.28);
        }

        .btn-customers-primary:hover,
        .btn-customers-primary:focus {
            color: #fff;
            transform: translateY(-1px);
            box-shadow: 0 1.2rem 2.8rem rgba(36, 95, 157, 0.36);
        }

        .customer-stat {
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

        .customer-stat__icon {
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

        .customer-stat small {
            display: block;
            color: #6b7280;
            font-weight: 800;
            letter-spacing: 0.03em;
            text-transform: uppercase;
        }

        .customer-stat strong {
            display: block;
            color: #161827;
            font-size: 1.45rem;
            line-height: 1.1;
        }

        .bg-gradient-indigo { background: linear-gradient(135deg, #245f9d, #173d67); }
        .bg-gradient-green { background: linear-gradient(135deg, #14b8a6, #0f766e); }
        .bg-gradient-gold { background: linear-gradient(135deg, #df941b, #8a5610); }
        .bg-gradient-copper { background: linear-gradient(135deg, #7c3aed, #4c1d95); }

        .customers-panel {
            overflow: hidden;
            border: 1px solid rgba(17, 24, 39, 0.08);
            border-radius: 1.5rem;
            background: #fff;
            box-shadow: 0 1.5rem 4rem rgba(17, 24, 39, 0.08);
        }

        .customers-panel__header {
            padding: 1.35rem 1.5rem;
            border-bottom: 1px solid rgba(17, 24, 39, 0.08);
            background:
                linear-gradient(135deg, rgba(36, 95, 157, 0.08), rgba(20, 184, 166, 0.08)),
                #fff;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 1rem;
        }

        .customers-panel__header h2 {
            margin: 0;
            color: #161827;
            font-size: 1.25rem;
            font-weight: 850;
        }

        .customers-panel__header p {
            margin: 0.25rem 0 0;
            color: #6b7280;
        }

        .customers-panel__summary {
            min-width: 185px;
            padding: 0.85rem 1rem;
            border: 1px solid rgba(36, 95, 157, 0.12);
            border-radius: 1rem;
            background: rgba(255, 255, 255, 0.72);
            text-align: right;
        }

        .customers-panel__summary span,
        .customers-panel__summary small {
            display: block;
            color: #6b7280;
            font-size: 0.75rem;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.08em;
        }

        .customers-panel__summary strong {
            color: #173d67;
            font-size: 1.35rem;
        }

        #customers-table {
            margin: 0 !important;
        }

        #customers-table thead th {
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

        #customers-table tbody td {
            padding-top: 1rem;
            padding-bottom: 1rem;
            border-color: rgba(17, 24, 39, 0.06);
        }

        #customers-table tbody tr:hover {
            background: rgba(36, 95, 157, 0.035);
        }

        .customer-lockup {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            min-width: 230px;
        }

        .customer-avatar {
            width: 3rem;
            height: 3rem;
            border-radius: 1rem;
            color: #fff;
            background: linear-gradient(135deg, #173d67, #14b8a6);
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-weight: 900;
            box-shadow: 0 0.8rem 1.5rem rgba(23, 61, 103, 0.18);
        }

        .customer-name {
            display: block;
            color: #161827;
            font-size: 1rem;
            font-weight: 850;
        }

        .customer-muted {
            color: #6b7280;
            font-size: 0.82rem;
        }

        .customer-pill {
            display: inline-flex;
            align-items: center;
            gap: 0.35rem;
            padding: 0.38rem 0.7rem;
            border-radius: 999px;
            font-size: 0.78rem;
            font-weight: 850;
        }

        .customer-pill--person {
            color: #173d67;
            background: rgba(36, 95, 157, 0.1);
        }

        .customer-pill--company {
            color: #4c1d95;
            background: rgba(124, 58, 237, 0.1);
        }

        .customer-pill--activity {
            color: #0f766e;
            background: rgba(20, 184, 166, 0.12);
        }

        .customer-actions {
            display: inline-flex;
            gap: 0.45rem;
        }

        .customer-action-btn {
            width: 2.25rem;
            height: 2.25rem;
            border-radius: 0.8rem;
            display: inline-flex;
            align-items: center;
            justify-content: center;
        }

        #create-customer-modal .modal-dialog,
        #edit-customer-modal .modal-dialog {
            height: calc(100vh - 3.5rem);
        }

        #create-customer-modal .modal-content,
        #edit-customer-modal .modal-content {
            max-height: 100%;
            overflow: hidden;
            border: 0;
            border-radius: 1.5rem;
            box-shadow: 0 2rem 5rem rgba(17, 24, 39, 0.22);
        }

        #create-customer-modal form,
        #edit-customer-modal form {
            display: flex;
            flex-direction: column;
            min-height: 0;
            height: 100%;
        }

        #create-customer-modal .modal-body,
        #edit-customer-modal .modal-body {
            min-height: 0;
            overflow-y: auto;
            overscroll-behavior: contain;
            background: #fbfaf8;
        }

        #create-customer-modal .modal-header,
        #edit-customer-modal .modal-header,
        #create-customer-modal .modal-footer,
        #edit-customer-modal .modal-footer {
            background: #fff;
            border-color: rgba(17, 24, 39, 0.08);
        }

        .customer-section-highlight {
            background:
                linear-gradient(135deg, rgba(36, 95, 157, 0.06), rgba(20, 184, 166, 0.08)),
                #fff;
            border-color: rgba(20, 184, 166, 0.28) !important;
        }

        .customer-form-section {
            height: 100%;
            padding: 1rem;
            border: 1px solid rgba(17, 24, 39, 0.08);
            border-radius: 1.15rem;
            background: #fff;
            box-shadow: 0 0.75rem 1.8rem rgba(17, 24, 39, 0.04);
        }

        .customer-form-section__title {
            margin-bottom: 1rem;
            color: #161827;
            font-weight: 850;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .customer-form-section__title i {
            color: #14b8a6;
        }

        .customer-form-section .form-label {
            color: #374151;
            font-size: 0.82rem;
            font-weight: 850;
        }

        .customer-form-section .form-control,
        .customer-form-section .form-select {
            border-radius: 0.85rem;
        }

        .customer-switch-card {
            min-height: 88px;
            padding: 0.95rem;
            border: 1px solid rgba(17, 24, 39, 0.08);
            border-radius: 1rem;
            background: linear-gradient(135deg, rgba(36, 95, 157, 0.045), rgba(20, 184, 166, 0.06));
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 0.75rem;
        }

        .customer-switch-card strong,
        .customer-switch-card span {
            display: block;
        }

        .customer-switch-card strong {
            color: #161827;
            font-size: 0.92rem;
        }

        .customer-switch-card span {
            color: #6b7280;
            font-size: 0.78rem;
        }

        .customer-switch-card .form-check-input {
            width: 2.8rem;
            height: 1.45rem;
            cursor: pointer;
        }

        .customer-switch-card .form-check-input:checked {
            border-color: #14b8a6;
            background-color: #14b8a6;
        }

        @media (max-width: 767.98px) {
            .customers-hero,
            .customers-panel__header {
                align-items: stretch;
                flex-direction: column;
            }

            .btn-customers-primary {
                width: 100%;
            }

            .customers-panel__summary {
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
            const createForm = document.getElementById('create-customer-form');
            const editForm = document.getElementById('edit-customer-form');
            const createModal = window.bootstrap ? new window.bootstrap.Modal(document.getElementById('create-customer-modal')) : null;
            const editModal = window.bootstrap ? new window.bootstrap.Modal(document.getElementById('edit-customer-modal')) : null;

            if (typeof $ !== 'function') {
                console.error('jQuery no esta disponible para DataTables.');
                return;
            }

            window.customersTable = $('#customers-table').DataTable({
                processing: true,
                serverSide: true,
                ajax: '{{ route('adminlte.customers.data') }}',
                language: {
                    url: 'https://cdn.datatables.net/plug-ins/1.13.8/i18n/es-ES.json',
                },
                order: [[7, 'desc']],
                columns: [
                    {
                        data: 'full_name',
                        name: 'full_name',
                        render: (data, type, row) => {
                            if (type !== 'display') {
                                return data;
                            }

                            const initial = (row.full_name || '?').trim().charAt(0).toUpperCase();
                            const company = row.is_company && row.company_name ? `<div class="customer-muted">${row.company_name}</div>` : '';
                            return `<div class="customer-lockup">
                                <span class="customer-avatar">${initial}</span>
                                <div><span class="customer-name">${row.full_name}</span>${company}</div>
                            </div>`;
                        }
                    },
                    {
                        data: 'document_type_label',
                        name: 'document_type',
                        render: (data, type, row) => {
                            if (type !== 'display') {
                                return row.document_number || '';
                            }

                            const number = row.document_number ? `<div class="customer-muted">${row.document_number}</div>` : '<div class="customer-muted">Sin numero</div>';
                            const tax = row.tax_number ? `<div class="customer-muted">NIT: ${row.tax_number}</div>` : '';
                            return `<div class="fw-semibold"><i class="bi bi-card-text me-1"></i>${row.document_type_label}</div>${number}${tax}`;
                        }
                    },
                    {
                        data: 'email',
                        name: 'email',
                        render: (data, type, row) => {
                            if (type !== 'display') {
                                return [row.email, row.phone, row.whatsapp].filter(Boolean).join(' ');
                            }

                            const email = row.email ? `<div><i class="bi bi-envelope me-1"></i>${row.email}</div>` : '<div class="customer-muted">Sin email</div>';
                            const phone = row.phone ? `<div class="customer-muted"><i class="bi bi-telephone me-1"></i>${row.phone}</div>` : '';
                            const whatsapp = row.whatsapp ? `<div class="customer-muted"><i class="bi bi-whatsapp me-1"></i>${row.whatsapp}</div>` : '';
                            return `${email}${phone}${whatsapp}`;
                        }
                    },
                    {
                        data: 'nationality',
                        name: 'nationality',
                        render: (data, type, row) => {
                            if (type !== 'display') {
                                return data || '';
                            }

                            const nationality = row.nationality || '<span class="customer-muted">Sin nacionalidad</span>';
                            const country = row.country ? `<div class="customer-muted">${row.city ? `${row.city}, ` : ''}${row.country}</div>` : '';
                            const foreignBadge = row.is_foreign ? '<div class="mt-1"><span class="badge text-bg-warning"><i class="bi bi-passport me-1"></i>Extranjero</span></div>' : '<div class="mt-1"><span class="badge text-bg-light border">Nacional</span></div>';
                            return `<div class="fw-semibold">${nationality}</div>${country}${foreignBadge}`;
                        }
                    },
                    {
                        data: 'is_company',
                        name: 'is_company',
                        className: 'text-center',
                        render: (data, type, row) => {
                            if (type !== 'display') {
                                return data ? 1 : 0;
                            }

                            const pillClass = data ? 'customer-pill customer-pill--company' : 'customer-pill customer-pill--person';
                            const icon = data ? 'bi-building' : 'bi-person';
                            return `<span class="${pillClass}"><i class="bi ${icon}"></i>${row.is_company_label}</span>`;
                        }
                    },
                    {
                        data: 'reservations_count',
                        name: 'reservations_count',
                        className: 'text-center',
                        orderable: false,
                        searchable: false,
                        render: (data, type, row) => {
                            if (type !== 'display') {
                                return data ?? 0;
                            }

                            return `<span class="customer-pill customer-pill--activity"><i class="bi bi-calendar2-check"></i>${data ?? 0} reservas</span>
                                <div class="customer-muted mt-1">${row.payments_count ?? 0} pagos</div>`;
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

                            const icon = data ? 'bi-check2-circle' : 'bi-pause-circle';
                            return `<span class="${row.status_badge_class}"><i class="bi ${icon} me-1"></i>${row.status_label}</span>`;
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

                            let actions = '<div class="customer-actions" role="group">';

                            if (row.can_update) {
                                actions += `<button type="button" class="btn btn-outline-primary customer-action-btn customer-edit-btn" title="Editar" data-customer="${encodeURIComponent(JSON.stringify(row))}">
                                    <i class="bi bi-pencil-square"></i>
                                </button>`;
                            }

                            if (row.can_delete) {
                                actions += `<button type="button" class="btn btn-outline-danger customer-action-btn customer-delete-btn" title="Eliminar" data-url="${row.delete_url}" data-name="${row.full_name}">
                                    <i class="bi bi-trash3"></i>
                                </button>`;
                            }

                            actions += '</div>';
                            return actions;
                        }
                    }
                ],
            });

            document.getElementById('open-create-customer-modal')?.addEventListener('click', () => {
                resetCustomerForm(createForm);
                toggleHighlights(createForm);
                createModal?.show();
            });

            createForm.addEventListener('submit', async (event) => {
                event.preventDefault();
                await submitCustomerForm(createForm, createForm.action, createModal, false);
            });

            editForm.addEventListener('submit', async (event) => {
                event.preventDefault();
                await submitCustomerForm(editForm, editForm.action, editModal, true);
            });

            document.addEventListener('click', async (event) => {
                const editButton = event.target.closest('.customer-edit-btn');
                if (editButton) {
                    const customer = JSON.parse(decodeURIComponent(editButton.dataset.customer));
                    fillEditForm(customer);
                    editModal?.show();
                    return;
                }

                const deleteButton = event.target.closest('.customer-delete-btn');
                if (deleteButton) {
                    await deleteCustomer(deleteButton.dataset.url, deleteButton.dataset.name);
                }
            });

            [createForm, editForm].forEach((form) => {
                form.querySelector('[name="is_company"]')?.addEventListener('change', () => toggleHighlights(form));
                form.querySelector('[name="is_foreign"]')?.addEventListener('change', () => toggleHighlights(form));
            });

            function fillEditForm(customer) {
                resetCustomerForm(editForm);
                editForm.action = customer.update_url;
                editForm.querySelector('[name="full_name"]').value = customer.full_name ?? '';
                editForm.querySelector('[name="document_type"]').value = customer.document_type ?? '';
                editForm.querySelector('[name="document_number"]').value = customer.document_number ?? '';
                editForm.querySelector('[name="nationality"]').value = customer.nationality ?? '';
                editForm.querySelector('[name="birth_date"]').value = customer.birth_date ?? '';
                editForm.querySelector('[name="phone"]').value = customer.phone ?? '';
                editForm.querySelector('[name="whatsapp"]').value = customer.whatsapp ?? '';
                editForm.querySelector('[name="email"]').value = customer.email ?? '';
                editForm.querySelector('[name="address"]').value = customer.address ?? '';
                editForm.querySelector('[name="city"]').value = customer.city ?? '';
                editForm.querySelector('[name="country"]').value = customer.country ?? '';
                editForm.querySelector('[name="notes"]').value = customer.notes ?? '';
                editForm.querySelector('[name="company_name"]').value = customer.company_name ?? '';
                editForm.querySelectorAll('[name="tax_number"]').forEach((field) => {
                    field.value = customer.tax_number ?? '';
                });
                editForm.querySelector('[name="is_foreign"]').checked = !!customer.is_foreign;
                editForm.querySelector('[name="is_company"]').checked = !!customer.is_company;
                editForm.querySelector('[name="is_active"]').checked = !!customer.is_active;
                toggleHighlights(editForm);
            }

            function resetCustomerForm(form) {
                form.reset();
                form.querySelector('[name="is_active"]').checked = true;
                form.querySelector('[name="is_foreign"]').checked = false;
                form.querySelector('[name="is_company"]').checked = false;
            }

            function toggleHighlights(form) {
                const isCompany = form.querySelector('[name="is_company"]').checked;
                const isForeign = form.querySelector('[name="is_foreign"]').checked;
                const companySection = form.querySelector('[data-company-section]');
                const foreignSection = form.querySelector('[data-foreign-section]');

                companySection?.classList.toggle('customer-section-highlight', isCompany);
                foreignSection?.classList.toggle('customer-section-highlight', isForeign);
            }

            async function submitCustomerForm(form, url, modalInstance, useMethodOverride) {
                const formData = new FormData(form);

                if (!form.querySelector('[name="is_foreign"]').checked) {
                    formData.set('is_foreign', '0');
                }

                if (!form.querySelector('[name="is_company"]').checked) {
                    formData.set('is_company', '0');
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
                window.customersTable.ajax.reload(null, false);

                await fireAlert({
                    icon: 'success',
                    title: payload.message || 'Operacion completada correctamente.',
                    timer: 1800,
                    showConfirmButton: false,
                });
            }

            async function deleteCustomer(url, name) {
                const confirmation = await fireAlert({
                    icon: 'warning',
                    title: 'Eliminar cliente',
                    text: `Se eliminara el registro de ${name}.`,
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
                window.customersTable.ajax.reload(null, false);

                await fireAlert({
                    icon: 'success',
                    title: payload.message || 'Cliente eliminado correctamente.',
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
