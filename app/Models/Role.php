<?php

namespace App\Models;

use Database\Factories\RoleFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Spatie\Permission\Models\Role as SpatieRole;

class Role extends SpatieRole
{
    /** @use HasFactory<RoleFactory> */
    use HasFactory;

    protected $table = 'adminlte_roles';

    protected $fillable = [
        'name',
        'guard_name',
        'label',
    ];

    public function users(): MorphToMany
    {
        return $this->morphedByMany(
            User::class,
            'model',
            config('permission.table_names.model_has_roles'),
            'role_id',
            config('permission.column_names.model_morph_key')
        );
    }
}
