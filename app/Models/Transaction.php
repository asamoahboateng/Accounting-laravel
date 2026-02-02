<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use App\Models\Concerns\HasTripleEntry;
use App\Models\Concerns\HasUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Transaction extends Model
{
    use BelongsToCompany, HasFactory, HasTripleEntry, HasUuid, SoftDeletes;

    protected $fillable = [
        'company_id',
        'transaction_type',
        'transaction_number',
        'reference_id',
        'reference_type',
        'transaction_date',
        'due_date',
        'posting_date',
        'fiscal_period_id',
        'contact_id',
        'contact_type',
        'currency_code',
        'exchange_rate',
        'subtotal',
        'tax_total',
        'discount_total',
        'total_amount',
        'base_currency_total',
        'amount_paid',
        'balance_due',
        'status',
        'approval_status',
        'memo',
        'internal_notes',
        'external_reference',
        'metadata',
        'line_items_snapshot',
        'is_recurring',
        'recurring_schedule_id',
        'is_manual',
        'is_adjusting',
        'is_closing',
        'is_reversed',
        'reversal_of_id',
        'reversed_by_id',
        'posted_at',
        'posted_by',
        'voided_at',
        'voided_by',
        'void_reason',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'transaction_date' => 'date',
        'due_date' => 'date',
        'posting_date' => 'date',
        'exchange_rate' => 'decimal:10',
        'subtotal' => 'decimal:4',
        'tax_total' => 'decimal:4',
        'discount_total' => 'decimal:4',
        'total_amount' => 'decimal:4',
        'base_currency_total' => 'decimal:4',
        'amount_paid' => 'decimal:4',
        'balance_due' => 'decimal:4',
        'metadata' => 'array',
        'line_items_snapshot' => 'array',
        'is_recurring' => 'boolean',
        'is_manual' => 'boolean',
        'is_adjusting' => 'boolean',
        'is_closing' => 'boolean',
        'is_reversed' => 'boolean',
        'posted_at' => 'datetime',
        'voided_at' => 'datetime',
    ];

    public function fiscalPeriod(): BelongsTo
    {
        return $this->belongsTo(FiscalPeriod::class);
    }

    public function contact(): BelongsTo
    {
        return $this->belongsTo(Contact::class);
    }

    public function currency(): BelongsTo
    {
        return $this->belongsTo(Currency::class, 'currency_code', 'code');
    }

    public function journalEntries(): HasMany
    {
        return $this->hasMany(JournalEntry::class);
    }

    public function reference(): MorphTo
    {
        return $this->morphTo('reference');
    }

    public function reversalOf(): BelongsTo
    {
        return $this->belongsTo(Transaction::class, 'reversal_of_id');
    }

    public function reversedBy(): BelongsTo
    {
        return $this->belongsTo(Transaction::class, 'reversed_by_id');
    }

    public function recurringSchedule(): BelongsTo
    {
        return $this->belongsTo(RecurringSchedule::class);
    }

    public function postedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'posted_by');
    }

    public function voidedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'voided_by');
    }

    public function createdByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function isPosted(): bool
    {
        return $this->status === 'posted';
    }

    public function isVoid(): bool
    {
        return $this->status === 'void';
    }

    public function canBeEdited(): bool
    {
        return in_array($this->status, ['draft', 'pending']);
    }

    public function post(): void
    {
        if ($this->isPosted()) {
            return;
        }

        $this->update([
            'status' => 'posted',
            'posted_at' => now(),
            'posted_by' => auth()->id(),
        ]);

        // Post associated journal entries
        $this->journalEntries()->update([
            'status' => 'posted',
            'posted_at' => now(),
            'posted_by' => auth()->id(),
        ]);
    }

    public function void(string $reason): void
    {
        $this->update([
            'status' => 'void',
            'voided_at' => now(),
            'voided_by' => auth()->id(),
            'void_reason' => $reason,
        ]);

        $this->journalEntries()->update(['status' => 'void']);
    }
}
