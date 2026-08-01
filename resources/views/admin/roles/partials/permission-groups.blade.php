<div class="row g-3">
    @foreach ($permissionGroups as $group => $permissions)
        @php($groupKey = \Illuminate\Support\Str::slug($group))
        <div class="col-lg-6" data-permission-group="{{ $formPrefix }}-{{ $groupKey }}">
            <div class="permission-group-card h-100">
                <div class="permission-group-head">
                    <div>
                        <h6 class="mb-1">{{ $group }}</h6>
                        <small class="text-muted">{{ $permissions->count() }} permisos disponibles</small>
                    </div>
                    <div class="permission-group-actions" role="group">
                        <button type="button" class="btn btn-outline-primary btn-sm" data-group-action="check" data-form="{{ $formPrefix }}" data-group="{{ $groupKey }}">
                            Marcar grupo
                        </button>
                        <button type="button" class="btn btn-outline-secondary btn-sm" data-group-action="clear" data-form="{{ $formPrefix }}" data-group="{{ $groupKey }}">
                            Limpiar grupo
                        </button>
                    </div>
                </div>

                <div class="row g-2">
                    @foreach ($permissions as $permission)
                        <div class="col-md-6">
                            <label class="form-check permission-check-card h-100">
                                <input
                                    class="form-check-input me-2"
                                    type="checkbox"
                                    name="permissions[]"
                                    value="{{ $permission->name }}"
                                    data-counter-form="{{ $formPrefix }}"
                                >
                                <span class="form-check-label">
                                    <strong class="d-block">{{ $permission->name }}</strong>
                                    @if (! empty($permission->label))
                                        <small class="text-muted">{{ $permission->label }}</small>
                                    @endif
                                </span>
                            </label>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    @endforeach
</div>
