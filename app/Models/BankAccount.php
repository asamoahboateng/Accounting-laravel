<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use App\Models\Concerns\HasUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BankAccount extends Model
{
    use BelongsToCompany, HasFactory, HasUuid;

    protected $fillable = [
        'company_id',
        'account_id',
        'bank_connection_id',
        'name',
        'account_type',
        'account_number_last4',
        'routing_number',
        'currency_code',
        'current_balance',
        'available_balance',
        'statement_balance',
        'statement_date',
        'is_active',
        'is_default',
        'last_sync_at',
        'metadata',
    ];

    protected $casts = [
        'current_balance' => 'decimal:4',
        'available_balance' => 'decimal:4',
        'statement_balance' => 'decimal:4',
        'statement_date' => 'date',
        'is_active' => 'boolean',
        'is_default' => 'boolean',
        'last_sync_at' => 'datetime',
        'metadata' => 'array',
    ];

    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    public function bankConnection(): BelongsTo
    {
        return $this->belongsTo(BankConnection::class);
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(BankTransaction::class);
    }

    public function reconciliations(): HasMany
    {
        return $this->hasMany(Reconciliation::class);
    }

    public function getLastReconciliation()
    {
        return $this->reconciliations()
            ->where('status', 'completed')
            ->orderBy('statement_date', 'desc')
            ->first();
    }

    public function getUnreconciledTransactions()
    {
        return $this->transactions()
            ->where('status', '!=', 'reconciled')
            ->orderBy('transaction_date')
            ->get();
    }
}
