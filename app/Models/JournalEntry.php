<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use App\Models\Concerns\HasTripleEntry;
use App\Models\Concerns\HasUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class JournalEntry extends Model
{
    use BelongsToCompany, HasFactory, HasTripleEntry, HasUuid, SoftDeletes;

    protected $fillable = [
        'company_id',
        'transaction_id',
        'entry_number',
        'entry_date',
        'posting_date',
        'fiscal_period_id',
        'entry_type',
        'source_type',
        'source_id',
        'description',
        'total_debit',
        'total_credit',
        'currency_code',
        'exchange_rate',
        'status',
        'is_balanced',
        'is_auto_generated',
        'is_reversing',
        'auto_reverse_date',
        'reversed_by_entry_id',
        'reversal_of_entry_id',
        'posted_at',
        'posted_by',
        'created_by',
    ];

    protected $casts = [
        'entry_date' => 'date',
        'posting_date' => 'date',
        'auto_reverse_date' => 'date',
        'total_debit' => 'decimal:4',
        'total_credit' => 'decimal:4',
        'exchange_rate' => 'decimal:10',
        'is_balanced' => 'boolean',
        'is_auto_generated' => 'boolean',
        'is_reversing' => 'boolean',
        'posted_at' => 'datetime',
    ];

    protected static function boot()
    {
        parent::boot();

        static::saving(function ($model) {
            $model->is_balanced = bccomp($model->total_debit, $model->total_credit, 4) === 0;
        });
    }

    public function transaction(): BelongsTo
    {
        return $this->belongsTo(Transaction::class);
    }

    public function fiscalPeriod(): BelongsTo
    {
        return $this->belongsTo(FiscalPeriod::class);
    }

    public function lines(): HasMany
    {
        return $this->hasMany(JournalEntryLine::class)->orderBy('line_number');
    }

    public function debitLines(): HasMany
    {
        return $this->lines()->where('type', 'debit');
    }

    public function creditLines(): HasMany
    {
        return $this->lines()->where('type', 'credit');
    }

    public function reversedBy(): BelongsTo
    {
        return $this->belongsTo(JournalEntry::class, 'reversed_by_entry_id');
    }

    public function reversalOf(): BelongsTo
    {
        return $this->belongsTo(JournalEntry::class, 'reversal_of_entry_id');
    }

    public function postedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'posted_by');
    }

    public function createdByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function isPosted(): bool
    {
        return $this->status === 'posted';
    }

    public function isBalanced(): bool
    {
        return $this->is_balanced;
    }

    public function recalculateTotals(): void
    {
        $this->total_debit = $this->lines()->where('type', 'debit')->sum('amount');
        $this->total_credit = $this->lines()->where('type', 'credit')->sum('amount');
        $this->save();
    }

    public function addLine(array $data): JournalEntryLine
    {
        $lineNumber = $this->lines()->max('line_number') + 1;

        return $this->lines()->create(array_merge($data, [
            'company_id' => $this->company_id,
            'line_number' => $lineNumber,
            'exchange_rate' => $this->exchange_rate,
            'currency_code' => $this->currency_code,
            'base_currency_amount' => $data['amount'] * $this->exchange_rate,
        ]));
    }

    public function createReversingEntry(): JournalEntry
    {
        $reversingEntry = $this->replicate();
        $reversingEntry->entry_number = null;
        $reversingEntry->entry_date = $this->auto_reverse_date ?? now()->addMonth()->startOfMonth();
        $reversingEntry->posting_date = $reversingEntry->entry_date;
        $reversingEntry->entry_type = 'reversing';
        $reversingEntry->is_reversing = false;
        $reversingEntry->reversal_of_entry_id = $this->id;
        $reversingEntry->description = "Reversal of {$this->entry_number}: {$this->description}";
        $reversingEntry->status = 'draft';
        $reversingEntry->posted_at = null;
        $reversingEntry->posted_by = null;
        $reversingEntry->save();

        // Reverse all lines (swap debits and credits)
        foreach ($this->lines as $line) {
            $reversingEntry->addLine([
                'account_id' => $line->account_id,
                'type' => $line->type === 'debit' ? 'credit' : 'debit',
                'amount' => $line->amount,
                'description' => $line->description,
                'contact_id' => $line->contact_id,
                'contact_type' => $line->contact_type,
            ]);
        }

        $this->update(['reversed_by_entry_id' => $reversingEntry->id]);

        return $reversingEntry;
    }
}
