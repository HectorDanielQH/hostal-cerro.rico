<div class="row g-3">
    <div class="col-lg-7">
        <div class="promotion-form-section">
            <div class="promotion-form-section__title">
                <i class="bi bi-megaphone" aria-hidden="true"></i>
                Campana comercial
            </div>

            <div class="row g-3">
                <div class="col-12">
                    <label class="form-label" for="{{ $prefix }}-promotion-name">Nombre de la promocion</label>
                    <input type="text" class="form-control" id="{{ $prefix }}-promotion-name" name="name" placeholder="Ej. Promo Invierno 20%" required>
                    <div class="form-text">Usa un nombre simple y entendible para recepcion y para el sitio web.</div>
                </div>

                <div class="col-12">
                    <label class="form-label" for="{{ $prefix }}-promotion-description">Descripcion</label>
                    <textarea class="form-control" id="{{ $prefix }}-promotion-description" name="description" rows="5" placeholder="Describe el beneficio, condiciones y mensaje comercial."></textarea>
                </div>
            </div>
        </div>
    </div>

    <div class="col-lg-5">
        <div class="promotion-form-section">
            <div class="promotion-form-section__title">
                <i class="bi bi-percent" aria-hidden="true"></i>
                Regla de descuento
            </div>

            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label" for="{{ $prefix }}-promotion-discount-type">Tipo</label>
                    <select class="form-select" id="{{ $prefix }}-promotion-discount-type" name="discount_type" required>
                        @foreach ($discountTypes as $value => $label)
                            <option value="{{ $value }}">{{ $label }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="col-md-6">
                    <label class="form-label" for="{{ $prefix }}-promotion-discount-value">Valor</label>
                    <input type="number" class="form-control" id="{{ $prefix }}-promotion-discount-value" name="discount_value" min="0.01" step="0.01" placeholder="20" required>
                </div>

                <div class="col-12">
                    <div class="promotion-preview-card rounded-3 p-3">
                        <div class="fw-semibold mb-1"><i class="bi bi-calculator me-1"></i> Vista previa del descuento</div>
                        <div id="{{ $prefix }}-promotion-preview-label" class="text-muted">
                            Selecciona un tipo de habitacion, tipo de descuento y valor para ver el calculo.
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-lg-6">
        <div class="promotion-form-section">
            <div class="promotion-form-section__title">
                <i class="bi bi-calendar2-week" aria-hidden="true"></i>
                Vigencia y limites
            </div>

            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label" for="{{ $prefix }}-promotion-starts-at">Inicio</label>
                    <input type="date" class="form-control" id="{{ $prefix }}-promotion-starts-at" name="starts_at">
                </div>

                <div class="col-md-6">
                    <label class="form-label" for="{{ $prefix }}-promotion-ends-at">Fin</label>
                    <input type="date" class="form-control" id="{{ $prefix }}-promotion-ends-at" name="ends_at">
                </div>

                <div class="col-md-6">
                    <label class="form-label" for="{{ $prefix }}-promotion-minimum-nights">Noches minimas</label>
                    <input type="number" class="form-control" id="{{ $prefix }}-promotion-minimum-nights" name="minimum_nights" min="1" max="365" placeholder="Sin minimo">
                </div>

                <div class="col-md-6">
                    <label class="form-label" for="{{ $prefix }}-promotion-maximum-uses">Maximo de usos</label>
                    <input type="number" class="form-control" id="{{ $prefix }}-promotion-maximum-uses" name="maximum_uses" min="1" max="999999" placeholder="Sin limite">
                </div>
            </div>
        </div>
    </div>

    <div class="col-lg-6">
        <div class="promotion-form-section">
            <div class="promotion-form-section__title">
                <i class="bi bi-broadcast" aria-hidden="true"></i>
                Publicacion
            </div>

            <div class="row g-3">
                <div class="col-md-6">
                    <div class="promotion-switch-card">
                        <div>
                            <strong>Mostrar en sitio web</strong>
                            <span>Los clientes podran verla en la pagina publica.</span>
                        </div>
                        <div class="form-check form-switch m-0">
                            <input type="hidden" name="show_on_website" value="0">
                            <input class="form-check-input" type="checkbox" id="{{ $prefix }}-promotion-show-on-website" name="show_on_website" value="1" checked>
                        </div>
                    </div>
                </div>

                <div class="col-md-6">
                    <div class="promotion-switch-card">
                        <div>
                            <strong>Promocion activa</strong>
                            <span>Puede aplicarse si cumple fecha, noches y usos.</span>
                        </div>
                        <div class="form-check form-switch m-0">
                            <input type="hidden" name="is_active" value="0">
                            <input class="form-check-input" type="checkbox" id="{{ $prefix }}-promotion-is-active" name="is_active" value="1" checked>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-12">
        <div class="promotion-form-section">
            <div class="promotion-form-section__title">
                <i class="bi bi-door-open" aria-hidden="true"></i>
                Tipos de habitacion aplicables
            </div>

            <div class="row g-2">
                @foreach ($roomTypes as $roomType)
                    <div class="col-md-6 col-xl-4">
                        <label class="form-check promotion-room-option px-3 py-3 h-100">
                            <input
                                class="form-check-input me-2"
                                type="checkbox"
                                name="room_type_ids[]"
                                value="{{ $roomType->id }}"
                                data-base-price="{{ number_format((float) $roomType->priceBob(), 2, '.', '') }}"
                            >
                            <span class="form-check-label">
                                <strong class="d-block">{{ $roomType->name }}</strong>
                                <small class="text-muted">
                                    Bs. {{ number_format((float) $roomType->priceBob(), 2, '.', '') }} / $us {{ number_format((float) $roomType->priceUsd(), 2, '.', '') }}
                                </small>
                            </span>
                        </label>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
</div>
