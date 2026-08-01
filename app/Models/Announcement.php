<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

class Announcement extends Model
{
    protected $fillable = [
        'title',
        'subtitle',
        'content',
        'image',
        'button_label',
        'button_url',
        'sort_order',
        'starts_at',
        'ends_at',
        'show_on_website',
        'show_as_modal',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
            'show_on_website' => 'boolean',
            'show_as_modal' => 'boolean',
            'is_active' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    public function scopeVisibleOnWebsite(Builder $query): Builder
    {
        $now = Carbon::now();

        return $query
            ->where('is_active', true)
            ->where('show_on_website', true)
            ->where('show_as_modal', true)
            ->where(function (Builder $builder) use ($now): void {
                $builder->whereNull('starts_at')->orWhere('starts_at', '<=', $now);
            })
            ->where(function (Builder $builder) use ($now): void {
                $builder->whereNull('ends_at')->orWhere('ends_at', '>=', $now);
            })
            ->orderBy('sort_order')
            ->orderByDesc('created_at');
    }
}
