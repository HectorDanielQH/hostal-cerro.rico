<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

class Reservation extends Model
{
    use SoftDeletes;

    public const PREFERRED_PAYMENT_METHODS = [
        'cash' => 'Efectivo',
        'qr' => 'QR',
        'bank' => 'Transferencia / Deposito bancario',
        'bank_transfer' => 'Transferencia bancaria',
        'bank_deposit' => 'Deposito bancario',
        'card' => 'Tarjeta',
        'other' => 'A coordinar con el hotel',
    ];

    public const STATUS_PENDING = 'pending';
    public const STATUS_CONFIRMED = 'confirmed';
    public const STATUS_CHECKED_IN = 'checked_in';
    public const STATUS_CHECKED_OUT = 'checked_out';
    public const STATUS_CANCELLED = 'cancelled';
    public const STATUS_EXPIRED = 'expired';
    public const STATUS_NO_SHOW = 'no_show';

    public const ACTIVE_STATUSES = [
        self::STATUS_PENDING,
        self::STATUS_CONFIRMED,
        self::STATUS_CHECKED_IN,
    ];

    protected $fillable = [
        'code',
        'customer_id',
        'room_id',
        'room_type_id',
        'promotion_id',
        'check_in',
        'check_out',
        'nights',
        'adults',
        'children',
        'base_price',
        'discount_type',
        'discount_value',
        'discount_amount',
        'price_per_night',
        'total_amount',
        'deposit_percentage',
        'deposit_amount_required',
        'paid_amount',
        'balance_amount',
        'status',
        'source',
        'preferred_payment_method',
        'special_requests',
        'internal_notes',
        'confirmed_at',
        'checked_in_at',
        'checked_out_at',
        'cancelled_at',
        'expired_at',
        'cancellation_reason',
        'cancellation_reviewed_at',
        'cancellation_reviewed_by',
        'created_by',
        'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'check_in' => 'date',
            'check_out' => 'date',
            'nights' => 'integer',
            'adults' => 'integer',
            'children' => 'integer',
            'base_price' => 'decimal:2',
            'discount_value' => 'decimal:2',
            'discount_amount' => 'decimal:2',
            'price_per_night' => 'decimal:2',
            'total_amount' => 'decimal:2',
            'deposit_percentage' => 'integer',
            'deposit_amount_required' => 'decimal:2',
            'paid_amount' => 'decimal:2',
            'balance_amount' => 'decimal:2',
            'confirmed_at' => 'datetime',
            'checked_in_at' => 'datetime',
            'checked_out_at' => 'datetime',
            'cancelled_at' => 'datetime',
            'expired_at' => 'datetime',
            'cancellation_reviewed_at' => 'datetime',
        ];
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function room(): BelongsTo
    {
        return $this->belongsTo(Room::class);
    }

    public function roomType(): BelongsTo
    {
        return $this->belongsTo(RoomType::class);
    }

    public function promotion(): BelongsTo
    {
        return $this->belongsTo(Promotion::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function cancellationReviewedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'cancellation_reviewed_by');
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function guests(): HasMany
    {
        return $this->hasMany(ReservationGuest::class);
    }

    public function isActive(): bool
    {
        return in_array($this->status, self::ACTIVE_STATUSES, true);
    }

    public function canBeConfirmed(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    public function canBeCancelled(): bool
    {
        return in_array($this->status, [self::STATUS_PENDING, self::STATUS_CONFIRMED], true);
    }

    public function canExpire(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    public function canCheckIn(): bool
    {
        return $this->status === self::STATUS_CONFIRMED;
    }

    public function canCheckOut(): bool
    {
        return $this->status === self::STATUS_CHECKED_IN;
    }

    public function recalculateTotals(): void
    {
        $checkIn = $this->check_in instanceof Carbon ? $this->check_in : Carbon::parse($this->check_in);
        $checkOut = $this->check_out instanceof Carbon ? $this->check_out : Carbon::parse($this->check_out);
        $nights = max($checkIn->diffInDays($checkOut), 1);
        $basePrice = (float) $this->base_price;
        $discountValue = (float) ($this->discount_value ?? 0);

        if ($this->discount_type === 'percentage') {
            $discountAmount = ($basePrice * $discountValue) / 100;
        } elseif ($this->discount_type === 'fixed') {
            $discountAmount = $discountValue;
        } else {
            $discountAmount = 0;
        }

        $discountAmount = min($discountAmount, $basePrice);
        $pricePerNight = max($basePrice - $discountAmount, 0);
        $totalAmount = $pricePerNight * $nights;
        $depositPercentage = $this->normalizedDepositPercentage();
        $depositAmountRequired = round(($totalAmount * $depositPercentage) / 100, 2);
        $paidAmount = max((float) ($this->paid_amount ?? 0), 0);
        $balanceAmount = max($totalAmount - $paidAmount, 0);

        $this->nights = $nights;
        $this->discount_amount = round($discountAmount, 2);
        $this->price_per_night = round($pricePerNight, 2);
        $this->total_amount = round($totalAmount, 2);
        $this->deposit_percentage = $depositPercentage;
        $this->deposit_amount_required = $depositAmountRequired;
        $this->balance_amount = round($balanceAmount, 2);
    }

    public function normalizedDepositPercentage(): int
    {
        $percentage = (int) ($this->deposit_percentage ?? 20);

        if ($percentage < 10 || $percentage > 100 || $percentage % 10 !== 0) {
            return 20;
        }

        return $percentage;
    }

    public function depositAmountPending(): float
    {
        return max(round((float) $this->deposit_amount_required - (float) $this->paid_amount, 2), 0);
    }

    public function hasRequiredDeposit(): bool
    {
        return round((float) $this->paid_amount, 2) >= round((float) $this->deposit_amount_required, 2);
    }
}
