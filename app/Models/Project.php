<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use App\Models\Concerns\HasUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Project extends Model
{
    use HasFactory, HasUuid, BelongsToCompany, SoftDeletes;

    protected $fillable = [
        'company_id',
        'customer_id',
        'name',
        'code',
        'description',
        'status',
        'start_date',
        'end_date',
        'due_date',
        'budget_amount',
        'estimated_hours',
        'actual_hours',
        'actual_cost',
        'invoiced_amount',
        'metadata',
        'created_by',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'due_date' => 'date',
        'budget_amount' => 'decimal:4',
        'estimated_hours' => 'decimal:2',
        'actual_hours' => 'decimal:2',
        'actual_cost' => 'decimal:4',
        'invoiced_amount' => 'decimal:4',
        'metadata' => 'array',
    ];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Contact::class, 'customer_id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class);
    }

    public function journalEntryLines(): HasMany
    {
        return $this->hasMany(JournalEntryLine::class);
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    public function isCompleted(): bool
    {
        return $this->status === 'completed';
    }

    public function isOnHold(): bool
    {
        return $this->status === 'on_hold';
    }

    public function isCancelled(): bool
    {
        return $this->status === 'cancelled';
    }

    public function getBudgetUsedPercentAttribute(): float
    {
        if (!$this->budget_amount || $this->budget_amount == 0) {
            return 0;
        }

        return ($this->actual_cost / $this->budget_amount) * 100;
    }

    public function getHoursUsedPercentAttribute(): float
    {
        if (!$this->estimated_hours || $this->estimated_hours == 0) {
            return 0;
        }

        return ($this->actual_hours / $this->estimated_hours) * 100;
    }

    public function getProfitMarginAttribute(): float
    {
        if ($this->invoiced_amount == 0) {
            return 0;
        }

        return (($this->invoiced_amount - $this->actual_cost) / $this->invoiced_amount) * 100;
    }

    public function isOverBudget(): bool
    {
        return $this->budget_amount && $this->actual_cost > $this->budget_amount;
    }

    public function isOverTime(): bool
    {
        return $this->estimated_hours && $this->actual_hours > $this->estimated_hours;
    }

    public function markAsCompleted(): void
    {
        $this->update([
            'status' => 'completed',
            'end_date' => now(),
        ]);
    }

    public function updateTotals(): void
    {
        $invoicedAmount = $this->invoices()
            ->whereIn('status', ['sent', 'paid', 'partial'])
            ->sum('total_amount');

        $this->update(['invoiced_amount' => $invoicedAmount]);
    }

    public static function getStatuses(): array
    {
        return [
            'active' => 'Active',
            'completed' => 'Completed',
            'on_hold' => 'On Hold',
            'cancelled' => 'Cancelled',
        ];
    }
}
