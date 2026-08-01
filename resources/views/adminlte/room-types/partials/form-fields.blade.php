<div class="row g-3">
    <div class="col-lg-7">
        <div class="room-type-form-section">
            <div class="room-type-form-section__title">
                <i class="bi bi-stars" aria-hidden="true"></i>
                Identidad comercial
            </div>

            <div class="row g-3">
                <div class="col-12">
                    <label class="form-label" for="{{ $prefix }}-room-type-name">Nombre del tipo</label>
                    <input type="text" class="form-control" id="{{ $prefix }}-room-type-name" name="name" placeholder="Ej. Suite Imperial" required>
                    <div class="form-text">Este nombre sera visible en reservas, habitaciones y pagina publica.</div>
                </div>

                <div class="col-12">
                    <label class="form-label" for="{{ $prefix }}-room-type-description">Descripcion para ventas</label>
                    <textarea class="form-control" id="{{ $prefix }}-room-type-description" name="description" rows="5" placeholder="Describe la experiencia, comodidad y valor diferencial de esta habitacion."></textarea>
                </div>

                <div class="col-12">
                    <label class="form-label" for="{{ $prefix }}-room-type-amenities">Amenidades</label>
                    <textarea class="form-control" id="{{ $prefix }}-room-type-amenities" name="amenities" rows="5" placeholder="WiFi&#10;Bano privado&#10;TV&#10;Desayuno"></textarea>
                    <div class="form-text">Ingresa una amenidad por linea para mostrarla de forma ordenada.</div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-lg-5">
        <div class="room-type-form-section">
            <div class="room-type-form-section__title">
                <i class="bi bi-image" aria-hidden="true"></i>
                Imagen principal
            </div>

            <label class="form-label" for="{{ $prefix }}-room-type-gallery-images">Galeria comercial</label>
            <input type="file" class="form-control" id="{{ $prefix }}-room-type-gallery-images" name="gallery_images[]" accept=".jpg,.jpeg,.png,.webp" multiple {{ $prefix === 'create' ? 'required' : '' }}>
            <div class="border rounded-3 p-3 mt-3 room-type-preview text-center">
                <div id="{{ $prefix }}-room-type-preview-placeholder">
                    <i class="bi bi-image text-secondary fs-1" aria-hidden="true"></i>
                </div>
                <div id="{{ $prefix }}-room-type-preview-gallery" class="room-type-preview-gallery d-none"></div>
                <div id="{{ $prefix }}-room-type-preview-text" class="small text-muted mt-2">Sin imagen cargada</div>
            </div>
            <div class="form-text mt-2">Sube de 1 a 4 imagenes. La primera sera la portada y todas rotaran en el frontend.</div>
        </div>
    </div>

    <div class="col-lg-6">
        <div class="room-type-form-section">
            <div class="room-type-form-section__title">
                <i class="bi bi-cash-coin" aria-hidden="true"></i>
                Precios y anticipo
            </div>

            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label" for="{{ $prefix }}-room-type-price-bob">Precio en bolivianos</label>
                    <div class="input-group">
                        <span class="input-group-text">Bs.</span>
                        <input type="number" class="form-control" id="{{ $prefix }}-room-type-price-bob" name="price_bob" min="0" step="0.01" required>
                    </div>
                </div>

                <div class="col-md-6">
                    <label class="form-label" for="{{ $prefix }}-room-type-price-usd">Precio en dolares</label>
                    <div class="input-group">
                        <span class="input-group-text">$us</span>
                        <input type="number" class="form-control" id="{{ $prefix }}-room-type-price-usd" name="price_usd" min="0" step="0.01" required>
                    </div>
                </div>

                <div class="col-12">
                    <label class="form-label" for="{{ $prefix }}-room-type-deposit-percentage">Anticipo para confirmar</label>
                    <select class="form-select" id="{{ $prefix }}-room-type-deposit-percentage" name="reservation_deposit_percentage" required>
                        @for ($percentage = 10; $percentage <= 100; $percentage += 10)
                            <option value="{{ $percentage }}" @selected($percentage === 20)>{{ $percentage }}% del total</option>
                        @endfor
                    </select>
                    <div class="form-text">Elige entre 10% y 100%. La reserva solo podra confirmarse cuando tenga este anticipo pagado.</div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-lg-6">
        <div class="room-type-form-section">
            <div class="room-type-form-section__title">
                <i class="bi bi-people" aria-hidden="true"></i>
                Capacidad y publicacion
            </div>

            <div class="row g-3">
                <div class="col-md-4">
                    <label class="form-label" for="{{ $prefix }}-room-type-capacity-adults">Adultos</label>
                    <input type="number" class="form-control" id="{{ $prefix }}-room-type-capacity-adults" name="capacity_adults" min="1" max="20" required>
                </div>

                <div class="col-md-4">
                    <label class="form-label" for="{{ $prefix }}-room-type-capacity-children">Ninos</label>
                    <input type="number" class="form-control" id="{{ $prefix }}-room-type-capacity-children" name="capacity_children" min="0" max="20">
                </div>

                <div class="col-md-4">
                    <label class="form-label" for="{{ $prefix }}-room-type-max-guests">Max. huespedes</label>
                    <input type="number" class="form-control" id="{{ $prefix }}-room-type-max-guests" name="max_guests" min="1" max="40" required>
                </div>

                <div class="col-md-6">
                    <div class="room-type-switch-card">
                        <div>
                            <strong>Mostrar en sitio web</strong>
                            <span>Disponible para clientes online.</span>
                        </div>
                        <div class="form-check form-switch m-0">
                            <input type="hidden" name="show_on_website" value="0">
                            <input class="form-check-input" type="checkbox" id="{{ $prefix }}-room-type-show-on-website" name="show_on_website" value="1" checked>
                        </div>
                    </div>
                </div>

                <div class="col-md-6">
                    <div class="room-type-switch-card">
                        <div>
                            <strong>Activo</strong>
                            <span>Puede usarse en operaciones.</span>
                        </div>
                        <div class="form-check form-switch m-0">
                            <input type="hidden" name="is_active" value="0">
                            <input class="form-check-input" type="checkbox" id="{{ $prefix }}-room-type-is-active" name="is_active" value="1" checked>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
