<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Payment extends Model
{
    use SoftDeletes;

    public const STATUS_PENDING = 'pending';
    public const STATUS_CONFIRMED = 'confirmed';
    public const STATUS_REJECTED = 'rejected';
    public const STATUS_CANCELLED = 'cancelled';
    public const STATUS_REFUNDED = 'refunded';
    public const SUPPORTED_CURRENCIES = HotelSetting::SUPPORTED_CURRENCIES;

    protected $fillable = [
        'code',
        'reservation_id',
        'customer_id',
        'amount',
        'currency',
        'exchange_rate',
        'amount_base',
        'payment_method',
        'status',
        'payment_date',
        'reference_number',
        'receipt_image',
        'notes',
        'rejection_reason',
        'cancellation_reason',
        'refund_reason',
        'confirmed_at',
        'rejected_at',
        'cancelled_at',
        'refunded_at',
        'created_by',
        'confirmed_by',
        'rejected_by',
        'cancelled_by',
        'refunded_by',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'exchange_rate' => 'decimal:4',
            'amount_base' => 'decimal:2',
            'payment_date' => 'date',
            'confirmed_at' => 'datetime',
            'rejected_at' => 'datetime',
            'cancelled_at' => 'datetime',
            'refunded_at' => 'datetime',
        ];
    }

    public function reservation(): BelongsTo
    {
        return $this->belongsTo(Reservation::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function confirmedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'confirmed_by');
    }

    public function rejectedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'rejected_by');
    }

    public function cancelledBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'cancelled_by');
    }

    public function refundedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'refunded_by');
    }

    public function cashMovements(): HasMany
    {
        return $this->hasMany(CashMovement::class);
    }

    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    public function isConfirmed(): bool
    {
        return $this->status === self::STATUS_CONFIRMED;
    }

    public function canBeConfirmed(): bool
    {
        return in_array($this->status, [self::STATUS_PENDING, self::STATUS_REJECTED], true);
    }

    public function canBeRejected(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    public function canBeUpdated(): bool
    {
        return in_array($this->status, [self::STATUS_PENDING, self::STATUS_REJECTED, self::STATUS_CONFIRMED], true);
    }

    public function canBeCancelled(): bool
    {
        return in_array($this->status, [self::STATUS_PENDING, self::STATUS_CONFIRMED], true);
    }

    public function canBeRefunded(): bool
    {
        return $this->status === self::STATUS_CONFIRMED;
    }
}
