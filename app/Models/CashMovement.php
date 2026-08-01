<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class CashMovement extends Model
{
    use SoftDeletes;

    public const TYPE_INCOME = 'income';
    public const TYPE_EXPENSE = 'expense';
    public const TYPE_ADJUSTMENT = 'adjustment';

    protected $fillable = [
        'cash_register_id',
        'payment_id',
        'user_id',
        'type',
        'concept',
        'amount',
        'currency',
        'exchange_rate',
        'amount_base',
        'payment_method',
        'movement_date',
        'notes',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'exchange_rate' => 'decimal:4',
            'amount_base' => 'decimal:2',
            'movement_date' => 'datetime',
        ];
    }

    public function cashRegister(): BelongsTo
    {
        return $this->belongsTo(CashRegister::class);
    }

    public function payment(): BelongsTo
    {
        return $this->belongsTo(Payment::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
