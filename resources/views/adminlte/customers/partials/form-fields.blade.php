<div class="row g-3">
    <div class="col-lg-7">
        <div class="customer-form-section">
            <div class="customer-form-section__title">
                <i class="bi bi-person-vcard" aria-hidden="true"></i>
                Datos principales
            </div>

            <div class="row g-3">
                <div class="col-12">
                    <label class="form-label" for="{{ $prefix }}-customer-full-name">Nombre completo</label>
                    <input type="text" class="form-control" id="{{ $prefix }}-customer-full-name" name="full_name" placeholder="Nombre y apellidos" required>
                </div>

                <div class="col-md-4">
                    <label class="form-label" for="{{ $prefix }}-customer-birth-date">Fecha de nacimiento</label>
                    <input type="date" class="form-control" id="{{ $prefix }}-customer-birth-date" name="birth_date">
                </div>

                <div class="col-md-4" data-foreign-section>
                    <label class="form-label" for="{{ $prefix }}-customer-nationality">Nacionalidad</label>
                    <input type="text" class="form-control" id="{{ $prefix }}-customer-nationality" name="nationality" maxlength="100" placeholder="Ej. Bolivia">
                </div>

                <div class="col-md-4">
                    <div class="customer-switch-card h-100">
                        <div>
                            <strong>Extranjero</strong>
                            <span>Marca si no es nacional.</span>
                        </div>
                        <div class="form-check form-switch m-0">
                            <input type="hidden" name="is_foreign" value="0">
                            <input class="form-check-input" type="checkbox" id="{{ $prefix }}-customer-is-foreign" name="is_foreign" value="1">
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-lg-5">
        <div class="customer-form-section">
            <div class="customer-form-section__title">
                <i class="bi bi-card-text" aria-hidden="true"></i>
                Documento y datos fiscales
            </div>

            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label" for="{{ $prefix }}-customer-document-type">Tipo de documento</label>
                    <select class="form-select" id="{{ $prefix }}-customer-document-type" name="document_type">
                        <option value="">Sin especificar</option>
                        @foreach ($documentTypes as $value => $label)
                            <option value="{{ $value }}">{{ $label }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="col-md-6">
                    <label class="form-label" for="{{ $prefix }}-customer-document-number">Numero</label>
                    <input type="text" class="form-control" id="{{ $prefix }}-customer-document-number" name="document_number" maxlength="100">
                </div>

                <div class="col-12">
                    <label class="form-label" for="{{ $prefix }}-customer-tax-number">NIT / Numero fiscal</label>
                    <input type="text" class="form-control" id="{{ $prefix }}-customer-tax-number" name="tax_number" maxlength="100" placeholder="Opcional">
                </div>
            </div>
        </div>
    </div>

    <div class="col-lg-6">
        <div class="customer-form-section">
            <div class="customer-form-section__title">
                <i class="bi bi-chat-dots" aria-hidden="true"></i>
                Contacto
            </div>

            <div class="row g-3">
                <div class="col-md-4">
                    <label class="form-label" for="{{ $prefix }}-customer-phone">Telefono</label>
                    <input type="text" class="form-control" id="{{ $prefix }}-customer-phone" name="phone" maxlength="50">
                </div>

                <div class="col-md-4">
                    <label class="form-label" for="{{ $prefix }}-customer-whatsapp">WhatsApp</label>
                    <input type="text" class="form-control" id="{{ $prefix }}-customer-whatsapp" name="whatsapp" maxlength="50">
                </div>

                <div class="col-md-4">
                    <label class="form-label" for="{{ $prefix }}-customer-email">Correo electronico</label>
                    <input type="email" class="form-control" id="{{ $prefix }}-customer-email" name="email" maxlength="255">
                </div>
            </div>
        </div>
    </div>

    <div class="col-lg-6">
        <div class="customer-form-section">
            <div class="customer-form-section__title">
                <i class="bi bi-geo-alt" aria-hidden="true"></i>
                Direccion
            </div>

            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label" for="{{ $prefix }}-customer-address">Direccion</label>
                    <input type="text" class="form-control" id="{{ $prefix }}-customer-address" name="address" maxlength="255">
                </div>

                <div class="col-md-3">
                    <label class="form-label" for="{{ $prefix }}-customer-city">Ciudad</label>
                    <input type="text" class="form-control" id="{{ $prefix }}-customer-city" name="city" maxlength="100">
                </div>

                <div class="col-md-3">
                    <label class="form-label" for="{{ $prefix }}-customer-country">Pais</label>
                    <input type="text" class="form-control" id="{{ $prefix }}-customer-country" name="country" maxlength="100">
                </div>
            </div>
        </div>
    </div>

    <div class="col-lg-6">
        <div class="customer-form-section" data-company-section>
            <div class="customer-form-section__title">
                <i class="bi bi-building" aria-hidden="true"></i>
                Empresa
            </div>

            <div class="row g-3">
                <div class="col-md-5">
                    <div class="customer-switch-card h-100">
                        <div>
                            <strong>Es empresa</strong>
                            <span>Marca si factura o reserva como empresa.</span>
                        </div>
                        <div class="form-check form-switch m-0">
                            <input type="hidden" name="is_company" value="0">
                            <input class="form-check-input" type="checkbox" id="{{ $prefix }}-customer-is-company" name="is_company" value="1">
                        </div>
                    </div>
                </div>

                <div class="col-md-7">
                    <label class="form-label" for="{{ $prefix }}-customer-company-name">Nombre de empresa</label>
                    <input type="text" class="form-control" id="{{ $prefix }}-customer-company-name" name="company_name" maxlength="255">
                    <div class="form-text">El NIT o numero fiscal se registra en la seccion de documento.</div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-lg-6">
        <div class="customer-form-section">
            <div class="customer-form-section__title">
                <i class="bi bi-clipboard2-heart" aria-hidden="true"></i>
                Observaciones y estado
            </div>

            <div class="row g-3">
                <div class="col-12">
                    <label class="form-label" for="{{ $prefix }}-customer-notes">Observaciones</label>
                    <textarea class="form-control" id="{{ $prefix }}-customer-notes" name="notes" rows="4" placeholder="Preferencias, referencias, necesidades especiales o datos utiles para recepcion."></textarea>
                </div>

                <div class="col-12">
                    <div class="customer-switch-card">
                        <div>
                            <strong>Cliente activo</strong>
                            <span>Puede usarse en reservas, pagos y busquedas internas.</span>
                        </div>
                        <div class="form-check form-switch m-0">
                            <input type="hidden" name="is_active" value="0">
                            <input class="form-check-input" type="checkbox" id="{{ $prefix }}-customer-is-active" name="is_active" value="1" checked>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
