<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use App\Models\Concerns\HasUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Reconciliation extends Model
{
    use BelongsToCompany, HasFactory, HasUuid;

    protected $fillable = [
        'company_id',
        'bank_account_id',
        'account_id',
        'statement_date',
        'statement_start_date',
        'statement_end_date',
        'statement_balance',
        'opening_balance',
        'cleared_balance',
        'difference',
        'status',
        'completed_at',
        'completed_by',
        'notes',
        'metadata',
        'created_by',
    ];

    protected $casts = [
        'statement_date' => 'date',
        'statement_start_date' => 'date',
        'statement_end_date' => 'date',
        'statement_balance' => 'decimal:4',
        'opening_balance' => 'decimal:4',
        'cleared_balance' => 'decimal:4',
        'difference' => 'decimal:4',
        'completed_at' => 'datetime',
        'metadata' => 'array',
    ];

    public function bankAccount(): BelongsTo
    {
        return $this->belongsTo(BankAccount::class);
    }

    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(ReconciliationItem::class);
    }

    public function clearedItems(): HasMany
    {
        return $this->items()->where('is_cleared', true);
    }

    public function completedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'completed_by');
    }

    public function createdByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function recalculateDifference(): void
    {
        $clearedBalance = $this->clearedItems()->sum('amount');

        $this->update([
            'cleared_balance' => $this->opening_balance + $clearedBalance,
            'difference' => $this->statement_balance - ($this->opening_balance + $clearedBalance),
        ]);
    }

    public function isBalanced(): bool
    {
        return abs($this->difference) < 0.01;
    }

    public function complete(): void
    {
        if (! $this->isBalanced()) {
            throw new \Exception('Reconciliation is not balanced.');
        }

        $this->update([
            'status' => 'completed',
            'completed_at' => now(),
            'completed_by' => auth()->id(),
        ]);

        // Mark all cleared items' journal entry lines as reconciled
        $this->clearedItems()->each(function ($item) {
            $item->journalEntryLine->update([
                'is_reconciled' => true,
                'reconciliation_id' => $this->id,
            ]);
        });
    }
}
