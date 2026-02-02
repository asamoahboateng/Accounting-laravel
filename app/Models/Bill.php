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

class Bill extends Model
{
    use BelongsToCompany, HasFactory, HasTripleEntry, HasUuid, SoftDeletes;

    protected $fillable = [
        'company_id',
        'transaction_id',
        'vendor_id',
        'bill_number',
        'vendor_invoice_number',
        'purchase_order_id',
        'status',
        'bill_date',
        'due_date',
        'payment_terms',
        'currency_code',
        'exchange_rate',
        'mailing_address',
        'subtotal',
        'discount_amount',
        'tax_amount',
        'total_amount',
        'amount_paid',
        'balance_due',
        'base_currency_total',
        'payable_account_id',
        'is_recurring',
        'recurring_schedule_id',
        'memo',
        'internal_notes',
        'custom_fields',
        'attachments',
        'metadata',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'bill_date' => 'date',
        'due_date' => 'date',
        'exchange_rate' => 'decimal:10',
        'subtotal' => 'decimal:4',
        'discount_amount' => 'decimal:4',
        'tax_amount' => 'decimal:4',
        'total_amount' => 'decimal:4',
        'amount_paid' => 'decimal:4',
        'balance_due' => 'decimal:4',
        'base_currency_total' => 'decimal:4',
        'is_recurring' => 'boolean',
        'custom_fields' => 'array',
        'attachments' => 'array',
        'metadata' => 'array',
    ];

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Contact::class, 'vendor_id');
    }

    public function transaction(): BelongsTo
    {
        return $this->belongsTo(Transaction::class);
    }

    public function purchaseOrder(): BelongsTo
    {
        return $this->belongsTo(PurchaseOrder::class);
    }

    public function currency(): BelongsTo
    {
        return $this->belongsTo(Currency::class, 'currency_code', 'code');
    }

    public function payableAccount(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'payable_account_id');
    }

    public function recurringSchedule(): BelongsTo
    {
        return $this->belongsTo(RecurringSchedule::class);
    }

    public function lines(): HasMany
    {
        return $this->hasMany(BillLine::class)->orderBy('line_number');
    }

    public function paymentApplications(): HasMany
    {
        return $this->hasMany(BillPaymentApplication::class, 'payable_id')
            ->where('payable_type', 'bill');
    }

    public function isPaid(): bool
    {
        return $this->status === 'paid' || $this->balance_due <= 0;
    }

    public function isOverdue(): bool
    {
        return $this->due_date < now() && $this->balance_due > 0;
    }

    public function recalculateTotals(): void
    {
        $subtotal = $this->lines()->sum('amount');
        $taxAmount = $this->lines()->sum('tax_amount');
        $totalAmount = $subtotal - $this->discount_amount + $taxAmount;
        $balanceDue = $totalAmount - $this->amount_paid;

        $this->update([
            'subtotal' => $subtotal,
            'tax_amount' => $taxAmount,
            'total_amount' => $totalAmount,
            'balance_due' => $balanceDue,
            'base_currency_total' => $totalAmount * $this->exchange_rate,
        ]);

        $this->updateStatus();
    }

    public function updateStatus(): void
    {
        if ($this->status === 'void') {
            return;
        }

        $status = match (true) {
            $this->balance_due <= 0 => 'paid',
            $this->amount_paid > 0 => 'partial',
            $this->due_date < now() => 'overdue',
            default => $this->status,
        };

        if ($status !== $this->status) {
            $this->update(['status' => $status]);
        }
    }

    public function applyPayment(float $amount): void
    {
        $this->increment('amount_paid', $amount);
        $this->decrement('balance_due', $amount);
        $this->updateStatus();
        $this->vendor->updateBalance();
    }

    public function updatePaymentStatus(): void
    {
        $totalApplied = $this->paymentApplications()->sum('amount_applied');
        $totalDiscount = $this->paymentApplications()->sum('discount_amount');

        $this->update([
            'amount_paid' => $totalApplied + $totalDiscount,
            'balance_due' => $this->total_amount - $totalApplied - $totalDiscount,
        ]);

        $this->updateStatus();
    }

    public static function generateNumber(string $companyId): string
    {
        $lastBill = static::where('company_id', $companyId)
            ->orderBy('bill_number', 'desc')
            ->first();

        if (!$lastBill) {
            return 'BILL-0001';
        }

        $lastNumber = (int) substr($lastBill->bill_number, 5);
        return 'BILL-' . str_pad($lastNumber + 1, 4, '0', STR_PAD_LEFT);
    }
}
