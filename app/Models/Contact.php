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

class Contact extends Model
{
    use BelongsToCompany, HasFactory, HasTripleEntry, HasUuid, SoftDeletes;

    protected $fillable = [
        'company_id',
        'type',
        'display_name',
        'company_name',
        'first_name',
        'last_name',
        'email',
        'phone',
        'mobile',
        'fax',
        'website',
        'tax_id',
        'account_number',
        'billing_address_line_1',
        'billing_address_line_2',
        'billing_city',
        'billing_state',
        'billing_postal_code',
        'billing_country',
        'shipping_address_line_1',
        'shipping_address_line_2',
        'shipping_city',
        'shipping_state',
        'shipping_postal_code',
        'shipping_country',
        'currency_code',
        'payment_terms',
        'payment_terms_days',
        'credit_limit',
        'opening_balance',
        'opening_balance_date',
        'current_balance',
        'default_receivable_account_id',
        'default_payable_account_id',
        'default_expense_account_id',
        'default_income_account_id',
        'tax_rate_id',
        'is_tax_exempt',
        'tax_exemption_reason',
        'notes',
        'custom_fields',
        'metadata',
        'is_active',
        'is_1099_eligible',
    ];

    protected $casts = [
        'credit_limit' => 'decimal:4',
        'opening_balance' => 'decimal:4',
        'current_balance' => 'decimal:4',
        'opening_balance_date' => 'date',
        'custom_fields' => 'array',
        'metadata' => 'array',
        'is_active' => 'boolean',
        'is_tax_exempt' => 'boolean',
        'is_1099_eligible' => 'boolean',
    ];

    public function currency(): BelongsTo
    {
        return $this->belongsTo(Currency::class, 'currency_code', 'code');
    }

    public function taxRate(): BelongsTo
    {
        return $this->belongsTo(TaxRate::class);
    }

    public function defaultReceivableAccount(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'default_receivable_account_id');
    }

    public function defaultPayableAccount(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'default_payable_account_id');
    }

    public function defaultExpenseAccount(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'default_expense_account_id');
    }

    public function defaultIncomeAccount(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'default_income_account_id');
    }

    public function contactPersons(): HasMany
    {
        return $this->hasMany(ContactPerson::class);
    }

    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class, 'customer_id');
    }

    public function bills(): HasMany
    {
        return $this->hasMany(Bill::class, 'vendor_id');
    }

    public function estimates(): HasMany
    {
        return $this->hasMany(Estimate::class, 'customer_id');
    }

    public function purchaseOrders(): HasMany
    {
        return $this->hasMany(PurchaseOrder::class, 'vendor_id');
    }

    public function paymentsReceived(): HasMany
    {
        return $this->hasMany(PaymentReceived::class, 'customer_id');
    }

    public function paymentsMade(): HasMany
    {
        return $this->hasMany(PaymentMade::class, 'vendor_id');
    }

    public function isCustomer(): bool
    {
        return in_array($this->type, ['customer', 'both']);
    }

    public function isVendor(): bool
    {
        return in_array($this->type, ['vendor', 'both']);
    }

    public function getBillingAddressAttribute(): string
    {
        return collect([
            $this->billing_address_line_1,
            $this->billing_address_line_2,
            implode(', ', array_filter([
                $this->billing_city,
                $this->billing_state,
                $this->billing_postal_code,
            ])),
            $this->billing_country,
        ])->filter()->implode("\n");
    }

    public function getShippingAddressAttribute(): string
    {
        return collect([
            $this->shipping_address_line_1,
            $this->shipping_address_line_2,
            implode(', ', array_filter([
                $this->shipping_city,
                $this->shipping_state,
                $this->shipping_postal_code,
            ])),
            $this->shipping_country,
        ])->filter()->implode("\n");
    }

    public function updateBalance(): void
    {
        if ($this->isCustomer()) {
            $invoiced = $this->invoices()->where('status', '!=', 'void')->sum('total_amount');
            $paid = $this->paymentsReceived()->where('status', '!=', 'void')->sum('amount');
            $balance = $invoiced - $paid + $this->opening_balance;
        } elseif ($this->isVendor()) {
            $billed = $this->bills()->where('status', '!=', 'void')->sum('total_amount');
            $paid = $this->paymentsMade()->where('status', '!=', 'void')->sum('amount');
            $balance = $billed - $paid + $this->opening_balance;
        } else {
            $balance = $this->opening_balance;
        }

        $this->update(['current_balance' => $balance]);
    }
}
