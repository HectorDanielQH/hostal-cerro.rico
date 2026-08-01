<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class CashRegister extends Model
{
    use SoftDeletes;

    public const STATUS_OPEN = 'open';
    public const STATUS_CLOSED = 'closed';
    public const STATUS_CANCELLED = 'cancelled';

    protected $fillable = [
        'code',
        'user_id',
        'opened_at',
        'closed_at',
        'opening_amount',
        'expected_amount',
        'counted_amount',
        'difference_amount',
        'total_income',
        'total_expense',
        'total_adjustment',
        'status',
        'shift_name',
        'opening_notes',
        'closing_notes',
        'created_by',
        'closed_by',
    ];

    protected function casts(): array
    {
        return [
            'opened_at' => 'datetime',
            'closed_at' => 'datetime',
            'opening_amount' => 'decimal:2',
            'expected_amount' => 'decimal:2',
            'counted_amount' => 'decimal:2',
            'difference_amount' => 'decimal:2',
            'total_income' => 'decimal:2',
            'total_expense' => 'decimal:2',
            'total_adjustment' => 'decimal:2',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function movements(): HasMany
    {
        return $this->hasMany(CashMovement::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function closedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'closed_by');
    }

    public function isOpen(): bool
    {
        return $this->status === self::STATUS_OPEN;
    }

    public function isClosed(): bool
    {
        return $this->status === self::STATUS_CLOSED;
    }

    public function canBeClosed(): bool
    {
        return $this->status === self::STATUS_OPEN;
    }

    public function recalculateTotals(): void
    {
        $totalIncome = (float) $this->movements()->where('type', CashMovement::TYPE_INCOME)->sum('amount_base');
        $totalExpense = (float) $this->movements()->where('type', CashMovement::TYPE_EXPENSE)->sum('amount_base');
        $totalAdjustment = (float) $this->movements()->where('type', CashMovement::TYPE_ADJUSTMENT)->sum('amount_base');
        $expectedAmount = (float) $this->opening_amount + $totalIncome - $totalExpense + $totalAdjustment;
        $countedAmount = $this->counted_amount !== null ? (float) $this->counted_amount : null;
        $differenceAmount = $countedAmount !== null ? $countedAmount - $expectedAmount : 0.0;

        $this->total_income = round($totalIncome, 2);
        $this->total_expense = round($totalExpense, 2);
        $this->total_adjustment = round($totalAdjustment, 2);
        $this->expected_amount = round($expectedAmount, 2);
        $this->difference_amount = round($differenceAmount, 2);
    }
}
