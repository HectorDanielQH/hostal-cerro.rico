<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Room extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'room_type_id',
        'number',
        'floor',
        'description',
        'internal_notes',
        'gallery_images',
        'status',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'gallery_images' => 'array',
            'is_active' => 'boolean',
        ];
    }

    public function roomType(): BelongsTo
    {
        return $this->belongsTo(RoomType::class);
    }

    public function reservations(): HasMany
    {
        return $this->hasMany(Reservation::class);
    }

    public function publicGalleryImages(): array
    {
        $images = collect($this->gallery_images ?? [])
            ->filter(fn ($image): bool => is_string($image) && trim($image) !== '')
            ->values();

        if ($images->isNotEmpty()) {
            return $images->unique()->take(8)->values()->all();
        }

        return $this->roomType?->publicGalleryImages() ?? [];
    }
}
