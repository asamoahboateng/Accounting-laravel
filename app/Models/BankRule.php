<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use App\Models\Concerns\HasUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BankRule extends Model
{
    use HasFactory, HasUuid, BelongsToCompany;

    protected $fillable = [
        'company_id',
        'name',
        'is_active',
        'priority',
        'description_contains',
        'description_equals',
        'amount_min',
        'amount_max',
        'amount_equals',
        'transaction_type',
        'action',
        'account_id',
        'contact_id',
        'tax_rate_id',
        'memo_template',
        'auto_confirm',
        'split_lines',
        'times_applied',
        'last_applied_at',
        'created_by',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'priority' => 'integer',
        'amount_min' => 'decimal:4',
        'amount_max' => 'decimal:4',
        'amount_equals' => 'decimal:4',
        'auto_confirm' => 'boolean',
        'split_lines' => 'array',
        'times_applied' => 'integer',
        'last_applied_at' => 'datetime',
    ];

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

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(BankTransaction::class, 'rule_id');
    }

    public function matches(BankTransaction $transaction): bool
    {
        if (!$this->is_active) {
            return false;
        }

        if ($this->transaction_type && $this->transaction_type !== $transaction->transaction_type) {
            return false;
        }

        if ($this->description_equals && $transaction->description !== $this->description_equals) {
            return false;
        }

        if ($this->description_contains && !str_contains(strtolower($transaction->description ?? ''), strtolower($this->description_contains))) {
            return false;
        }

        $amount = abs($transaction->amount);

        if ($this->amount_equals !== null && $amount != $this->amount_equals) {
            return false;
        }

        if ($this->amount_min !== null && $amount < $this->amount_min) {
            return false;
        }

        if ($this->amount_max !== null && $amount > $this->amount_max) {
            return false;
        }

        return true;
    }

    public function apply(BankTransaction $transaction): void
    {
        switch ($this->action) {
            case 'categorize':
                if ($this->account_id) {
                    $transaction->categorize(
                        Account::find($this->account_id),
                        $this->contact_id ? Contact::find($this->contact_id) : null
                    );
                }
                break;

            case 'exclude':
                $transaction->exclude();
                break;
        }

        $transaction->update(['rule_id' => $this->id]);

        $this->increment('times_applied');
        $this->update(['last_applied_at' => now()]);
    }

    public static function findMatchingRule(BankTransaction $transaction): ?self
    {
        return static::where('company_id', $transaction->company_id)
            ->where('is_active', true)
            ->orderBy('priority', 'desc')
            ->get()
            ->first(fn (BankRule $rule) => $rule->matches($transaction));
    }
}
