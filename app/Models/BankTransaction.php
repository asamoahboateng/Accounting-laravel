<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use App\Models\Concerns\HasUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BankTransaction extends Model
{
    use HasFactory, HasUuid, BelongsToCompany;

    protected $fillable = [
        'company_id',
        'bank_account_id',
        'external_id',
        'transaction_date',
        'posted_date',
        'transaction_type',
        'amount',
        'description',
        'payee',
        'check_number',
        'reference_number',
        'category',
        'status',
        'matched_transaction_id',
        'matched_transaction_type',
        'categorized_account_id',
        'categorized_contact_id',
        'rule_id',
        'is_manual',
        'running_balance',
        'raw_data',
        'metadata',
    ];

    protected $casts = [
        'transaction_date' => 'date',
        'posted_date' => 'date',
        'amount' => 'decimal:4',
        'running_balance' => 'decimal:4',
        'is_manual' => 'boolean',
        'raw_data' => 'array',
        'metadata' => 'array',
    ];

    public function bankAccount(): BelongsTo
    {
        return $this->belongsTo(BankAccount::class);
    }

    public function categorizedAccount(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'categorized_account_id');
    }

    public function categorizedContact(): BelongsTo
    {
        return $this->belongsTo(Contact::class, 'categorized_contact_id');
    }

    public function rule(): BelongsTo
    {
        return $this->belongsTo(BankRule::class);
    }

    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    public function isMatched(): bool
    {
        return $this->status === 'matched';
    }

    public function isCategorized(): bool
    {
        return $this->status === 'categorized';
    }

    public function isReconciled(): bool
    {
        return $this->status === 'reconciled';
    }

    public function isDebit(): bool
    {
        return $this->transaction_type === 'debit';
    }

    public function isCredit(): bool
    {
        return $this->transaction_type === 'credit';
    }

    public function categorize(Account $account, ?Contact $contact = null): void
    {
        $this->update([
            'status' => 'categorized',
            'categorized_account_id' => $account->id,
            'categorized_contact_id' => $contact?->id,
        ]);
    }

    public function match(string $transactionType, string $transactionId): void
    {
        $this->update([
            'status' => 'matched',
            'matched_transaction_type' => $transactionType,
            'matched_transaction_id' => $transactionId,
        ]);
    }

    public function exclude(): void
    {
        $this->update(['status' => 'excluded']);
    }

    public function markAsReconciled(): void
    {
        $this->update(['status' => 'reconciled']);
    }

    public function reset(): void
    {
        $this->update([
            'status' => 'pending',
            'matched_transaction_id' => null,
            'matched_transaction_type' => null,
            'categorized_account_id' => null,
            'categorized_contact_id' => null,
            'rule_id' => null,
        ]);
    }
}
