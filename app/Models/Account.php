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

class Account extends Model
{
    use BelongsToCompany, HasFactory, HasTripleEntry, HasUuid, SoftDeletes;

    protected $fillable = [
        'company_id',
        'parent_id',
        'account_type_id',
        'account_subtype_id',
        'code',
        'name',
        'description',
        'currency_code',
        'is_active',
        'is_system',
        'is_bank_account',
        'is_tax_account',
        'is_header_account',
        'is_sub_account',
        'depth',
        'full_path',
        'opening_balance',
        'opening_balance_date',
        'current_balance',
        'metadata',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'is_system' => 'boolean',
        'is_bank_account' => 'boolean',
        'is_tax_account' => 'boolean',
        'is_header_account' => 'boolean',
        'is_sub_account' => 'boolean',
        'opening_balance' => 'decimal:4',
        'current_balance' => 'decimal:4',
        'opening_balance_date' => 'date',
        'metadata' => 'array',
    ];

    public function accountType(): BelongsTo
    {
        return $this->belongsTo(AccountType::class);
    }

    public function accountSubtype(): BelongsTo
    {
        return $this->belongsTo(AccountSubtype::class);
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(Account::class, 'parent_id');
    }

    public function journalEntryLines(): HasMany
    {
        return $this->hasMany(JournalEntryLine::class);
    }

    public function balances(): HasMany
    {
        return $this->hasMany(AccountBalance::class);
    }

    public function bankAccount(): HasMany
    {
        return $this->hasMany(BankAccount::class);
    }

    public function getFullNameAttribute(): string
    {
        if ($this->parent) {
            return $this->parent->full_name . ' : ' . $this->name;
        }

        return $this->name;
    }

    public function getDisplayNameAttribute(): string
    {
        return $this->code . ' - ' . $this->name;
    }

    public function isDebitNormal(): bool
    {
        return $this->accountType?->normal_balance === 'debit';
    }

    public function updateBalance(): void
    {
        $debits = $this->journalEntryLines()
            ->whereHas('journalEntry', fn ($q) => $q->where('status', 'posted'))
            ->where('type', 'debit')
            ->sum('amount');

        $credits = $this->journalEntryLines()
            ->whereHas('journalEntry', fn ($q) => $q->where('status', 'posted'))
            ->where('type', 'credit')
            ->sum('amount');

        $balance = $this->isDebitNormal()
            ? ($debits - $credits + $this->opening_balance)
            : ($credits - $debits + $this->opening_balance);

        $this->update(['current_balance' => $balance]);
    }
}
