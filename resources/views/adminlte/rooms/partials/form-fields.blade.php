<div class="row g-3">
    <div class="col-lg-6">
        <div class="room-form-section">
            <div class="room-form-section__title">
                <i class="bi bi-door-open" aria-hidden="true"></i>
                Identificacion fisica
            </div>

            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label" for="{{ $prefix }}-room-number">Numero de habitacion</label>
                    <input type="text" class="form-control" id="{{ $prefix }}-room-number" name="number" maxlength="50" placeholder="Ej. 101" required>
                </div>

                <div class="col-md-6">
                    <label class="form-label" for="{{ $prefix }}-room-floor">Piso o sector</label>
                    <input type="text" class="form-control" id="{{ $prefix }}-room-floor" name="floor" maxlength="50" placeholder="Ej. Piso 1 / Ala norte">
                </div>

                <div class="col-12">
                    <label class="form-label" for="{{ $prefix }}-room-type-id">Tipo comercial</label>
                    <select class="form-select" id="{{ $prefix }}-room-type-id" name="room_type_id" required>
                        <option value="">Selecciona un tipo</option>
                        @foreach ($roomTypes as $roomType)
                            <option value="{{ $roomType->id }}">
                                {{ $roomType->name }} - Bs. {{ number_format((float) $roomType->priceBob(), 2, '.', '') }} / $us {{ number_format((float) $roomType->priceUsd(), 2, '.', '') }}
                            </option>
                        @endforeach
                    </select>
                    <div class="form-text">El tipo define precio, capacidad, galeria y condiciones comerciales.</div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-lg-6">
        <div class="room-form-section">
            <div class="room-form-section__title">
                <i class="bi bi-activity" aria-hidden="true"></i>
                Estado operativo
            </div>

            <div class="row g-3">
                <div class="col-12">
                    <label class="form-label" for="{{ $prefix }}-room-status">Estado actual</label>
                    <select class="form-select" id="{{ $prefix }}-room-status" name="status" required>
                        @foreach ($statuses as $status => $meta)
                            <option value="{{ $status }}">{{ $meta['label'] }}</option>
                        @endforeach
                    </select>
                    <div class="form-text">Recepcion usara este estado para saber si puede vender, limpiar o bloquear la habitacion.</div>
                </div>

                <div class="col-12">
                    <div class="room-switch-card">
                        <div>
                            <strong>Habitacion activa</strong>
                            <span>Disponible para operaciones y asignaciones cuando su estado lo permita.</span>
                        </div>
                        <div class="form-check form-switch m-0">
                            <input type="hidden" name="is_active" value="0">
                            <input class="form-check-input" type="checkbox" id="{{ $prefix }}-room-is-active" name="is_active" value="1" checked>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-lg-6">
        <div class="room-form-section">
            <div class="room-form-section__title">
                <i class="bi bi-card-text" aria-hidden="true"></i>
                Descripcion visible
            </div>

            <label class="form-label" for="{{ $prefix }}-room-description">Descripcion</label>
            <textarea class="form-control" id="{{ $prefix }}-room-description" name="description" rows="6" placeholder="Ej. Habitacion con vista interior, cama matrimonial, bano privado."></textarea>
            <div class="form-text">Opcional. Sirve para diferenciar esta habitacion fisica de otras del mismo tipo.</div>
        </div>
    </div>

    <div class="col-lg-6">
        <div class="room-form-section">
            <div class="room-form-section__title">
                <i class="bi bi-lock" aria-hidden="true"></i>
                Notas internas
            </div>

            <label class="form-label" for="{{ $prefix }}-room-internal-notes">Notas para el equipo</label>
            <textarea class="form-control" id="{{ $prefix }}-room-internal-notes" name="internal_notes" rows="6" placeholder="Ej. Revisar ducha, cambiar cortinas, preferir para familias."></textarea>
            <div class="form-text">Solo para administracion, gerencia o recepcion. No se muestra al cliente.</div>
        </div>
    </div>
</div>
