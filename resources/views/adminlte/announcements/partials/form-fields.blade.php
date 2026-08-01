<div class="row g-3 announcement-form-grid">
    <div class="col-md-8">
        <label class="form-label" for="{{ $prefix }}-announcement-title">Titulo</label>
        <input type="text" id="{{ $prefix }}-announcement-title" name="title" class="form-control" required>
    </div>
    <div class="col-md-4">
        <label class="form-label" for="{{ $prefix }}-announcement-sort-order">Orden</label>
        <input type="number" id="{{ $prefix }}-announcement-sort-order" name="sort_order" class="form-control" min="0" max="9999" value="0">
    </div>
    <div class="col-12">
        <label class="form-label" for="{{ $prefix }}-announcement-subtitle">Subtitulo</label>
        <input type="text" id="{{ $prefix }}-announcement-subtitle" name="subtitle" class="form-control">
    </div>
    <div class="col-12">
        <label class="form-label" for="{{ $prefix }}-announcement-content">Contenido</label>
        <textarea id="{{ $prefix }}-announcement-content" name="content" rows="5" class="form-control"></textarea>
    </div>
    <div class="col-md-6">
        <label class="form-label" for="{{ $prefix }}-announcement-image">Imagen</label>
        <input type="file" id="{{ $prefix }}-announcement-image" name="image" class="form-control" accept=".jpg,.jpeg,.png,.webp">
        <div class="announcement-image-preview" id="{{ $prefix }}-announcement-image-preview">
            <span class="text-muted">Sin imagen seleccionada</span>
        </div>
    </div>
    <div class="col-md-6">
        <label class="form-label" for="{{ $prefix }}-announcement-button-label">Texto del boton</label>
        <input type="text" id="{{ $prefix }}-announcement-button-label" name="button_label" class="form-control" maxlength="100">
        <label class="form-label mt-3" for="{{ $prefix }}-announcement-button-url">Enlace del boton</label>
        <input type="url" id="{{ $prefix }}-announcement-button-url" name="button_url" class="form-control" placeholder="https://...">
    </div>
    <div class="col-md-6">
        <label class="form-label" for="{{ $prefix }}-announcement-starts-at">Inicio</label>
        <input type="datetime-local" id="{{ $prefix }}-announcement-starts-at" name="starts_at" class="form-control">
    </div>
    <div class="col-md-6">
        <label class="form-label" for="{{ $prefix }}-announcement-ends-at">Fin</label>
        <input type="datetime-local" id="{{ $prefix }}-announcement-ends-at" name="ends_at" class="form-control">
    </div>
    <div class="col-md-4">
        <div class="form-check form-switch announcement-switch-card">
            <input class="form-check-input" type="checkbox" role="switch" id="{{ $prefix }}-announcement-show-on-website" name="show_on_website" value="1" checked>
            <label class="form-check-label" for="{{ $prefix }}-announcement-show-on-website">
                <strong>Visible en la web</strong>
                <span>Permite que el anuncio se muestre en el sitio publico.</span>
            </label>
        </div>
    </div>
    <div class="col-md-4">
        <div class="form-check form-switch announcement-switch-card">
            <input class="form-check-input" type="checkbox" role="switch" id="{{ $prefix }}-announcement-show-as-modal" name="show_as_modal" value="1" checked>
            <label class="form-check-label" for="{{ $prefix }}-announcement-show-as-modal">
                <strong>Mostrar en modal</strong>
                <span>Abre el anuncio como aviso moderno al inicio.</span>
            </label>
        </div>
    </div>
    <div class="col-md-4">
        <div class="form-check form-switch announcement-switch-card">
            <input class="form-check-input" type="checkbox" role="switch" id="{{ $prefix }}-announcement-is-active" name="is_active" value="1" checked>
            <label class="form-check-label" for="{{ $prefix }}-announcement-is-active">
                <strong>Activo</strong>
                <span>Habilita el anuncio para evaluarlo por fecha y orden.</span>
            </label>
        </div>
    </div>
</div>
