<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use App\Models\Concerns\HasUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class JournalEntryLine extends Model
{
    use BelongsToCompany, HasFactory, HasUuid;

    protected $fillable = [
        'company_id',
        'journal_entry_id',
        'account_id',
        'line_number',
        'type',
        'amount',
        'base_currency_amount',
        'currency_code',
        'exchange_rate',
        'description',
        'contact_id',
        'contact_type',
        'department_id',
        'project_id',
        'location_id',
        'class_id',
        'tax_rate_id',
        'tax_amount',
        'reconciliation_id',
        'is_reconciled',
        'metadata',
    ];

    protected $casts = [
        'amount' => 'decimal:4',
        'base_currency_amount' => 'decimal:4',
        'exchange_rate' => 'decimal:10',
        'tax_amount' => 'decimal:4',
        'is_reconciled' => 'boolean',
        'metadata' => 'array',
    ];

    protected static function boot()
    {
        parent::boot();

        static::saved(function ($line) {
            $line->journalEntry->recalculateTotals();
            $line->account->updateBalance();
        });

        static::deleted(function ($line) {
            $line->journalEntry->recalculateTotals();
            $line->account->updateBalance();
        });
    }

    public function journalEntry(): BelongsTo
    {
        return $this->belongsTo(JournalEntry::class);
    }

    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    public function contact(): BelongsTo
    {
        return $this->belongsTo(Contact::class);
    }

    public function taxRate(): BelongsTo
    {
        return $this->belongsTo(TaxRate::class);
    }

    public function reconciliation(): BelongsTo
    {
        return $this->belongsTo(Reconciliation::class);
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(TrackingCategory::class, 'department_id');
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function location(): BelongsTo
    {
        return $this->belongsTo(TrackingCategory::class, 'location_id');
    }

    public function trackingClass(): BelongsTo
    {
        return $this->belongsTo(TrackingCategory::class, 'class_id');
    }

    public function isDebit(): bool
    {
        return $this->type === 'debit';
    }

    public function isCredit(): bool
    {
        return $this->type === 'credit';
    }
}
