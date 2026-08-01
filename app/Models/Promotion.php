<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

class Promotion extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'name',
        'slug',
        'description',
        'discount_type',
        'discount_value',
        'starts_at',
        'ends_at',
        'minimum_nights',
        'maximum_uses',
        'used_count',
        'show_on_website',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'discount_value' => 'decimal:2',
            'starts_at' => 'date',
            'ends_at' => 'date',
            'minimum_nights' => 'integer',
            'maximum_uses' => 'integer',
            'used_count' => 'integer',
            'show_on_website' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    public function roomTypes(): BelongsToMany
    {
        return $this->belongsToMany(RoomType::class, 'promotion_room_type')->withTimestamps();
    }

    public function reservations(): HasMany
    {
        return $this->hasMany(Reservation::class);
    }

    public function isCurrentlyActive(): bool
    {
        $today = Carbon::today();

        return $this->is_active
            && ($this->starts_at === null || $this->starts_at->lessThanOrEqualTo($today))
            && ($this->ends_at === null || $this->ends_at->greaterThanOrEqualTo($today))
            && ($this->maximum_uses === null || $this->used_count < $this->maximum_uses);
    }

    public function calculateDiscount(float $basePrice): float
    {
        $discount = $this->discount_type === 'percentage'
            ? ($basePrice * (float) $this->discount_value) / 100
            : (float) $this->discount_value;

        return min($discount, $basePrice);
    }

    public function calculateFinalPrice(float $basePrice): float
    {
        return max($basePrice - $this->calculateDiscount($basePrice), 0);
    }
}
