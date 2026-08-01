<?php

namespace App\Models;

use Database\Factories\PermissionFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Spatie\Permission\Models\Permission as SpatiePermission;

class Permission extends SpatiePermission
{
    /** @use HasFactory<PermissionFactory> */
    use HasFactory;

    protected $table = 'adminlte_permissions';

    protected $fillable = [
        'name',
        'guard_name',
        'label',
    ];
}
