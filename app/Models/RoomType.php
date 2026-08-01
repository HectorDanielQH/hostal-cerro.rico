<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class RoomType extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'name',
        'slug',
        'description',
        'base_price',
        'price_bob',
        'price_usd',
        'reservation_deposit_percentage',
        'capacity_adults',
        'capacity_children',
        'max_guests',
        'main_image',
        'gallery_images',
        'amenities',
        'show_on_website',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'base_price' => 'decimal:2',
            'price_bob' => 'decimal:2',
            'price_usd' => 'decimal:2',
            'reservation_deposit_percentage' => 'integer',
            'gallery_images' => 'array',
            'amenities' => 'array',
            'show_on_website' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (self $roomType): void {
            $priceBob = $roomType->price_bob ?? $roomType->base_price ?? 0;
            $priceUsd = $roomType->price_usd ?? 0;

            $roomType->price_bob = round((float) $priceBob, 2);
            $roomType->price_usd = round((float) $priceUsd, 2);
            $roomType->base_price = round((float) $roomType->price_bob, 2);
        });
    }

    public function rooms(): HasMany
    {
        return $this->hasMany(Room::class);
    }

    public function promotions(): BelongsToMany
    {
        return $this->belongsToMany(Promotion::class, 'promotion_room_type')->withTimestamps();
    }

    public function reservations(): HasMany
    {
        return $this->hasMany(Reservation::class);
    }

    public function priceBob(): float
    {
        return round((float) ($this->price_bob ?? $this->base_price ?? 0), 2);
    }

    public function priceUsd(): float
    {
        return round((float) ($this->price_usd ?? 0), 2);
    }

    public function dualPriceLabel(): string
    {
        return sprintf(
            'Bs. %s / $us %s',
            number_format($this->priceBob(), 2, '.', ''),
            number_format($this->priceUsd(), 2, '.', '')
        );
    }

    public function reservationDepositPercentage(): int
    {
        $percentage = (int) ($this->reservation_deposit_percentage ?? 20);

        if ($percentage < 10 || $percentage > 100 || $percentage % 10 !== 0) {
            return 20;
        }

        return $percentage;
    }

    public function publicGalleryImages(): array
    {
        $images = collect($this->gallery_images ?? [])
            ->filter(fn ($image): bool => is_string($image) && trim($image) !== '')
            ->values();

        if ($this->main_image && ! $images->contains($this->main_image)) {
            $images->prepend($this->main_image);
        }

        return $images->unique()->take(4)->values()->all();
    }
}
