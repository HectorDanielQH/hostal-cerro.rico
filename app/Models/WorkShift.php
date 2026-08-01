<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class WorkShift extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'name',
        'starts_at',
        'ends_at',
        'description',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function scheduleLabel(): string
    {
        return substr((string) $this->starts_at, 0, 5).' - '.substr((string) $this->ends_at, 0, 5);
    }
}
