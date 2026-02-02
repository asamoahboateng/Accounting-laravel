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

class Invoice extends Model
{
    use BelongsToCompany, HasFactory, HasTripleEntry, HasUuid, SoftDeletes;

    protected $fillable = [
        'company_id',
        'transaction_id',
        'customer_id',
        'invoice_number',
        'estimate_id',
        'status',
        'invoice_date',
        'due_date',
        'payment_terms',
        'currency_code',
        'exchange_rate',
        'billing_address',
        'shipping_address',
        'ship_via',
        'ship_date',
        'tracking_number',
        'subtotal',
        'discount_type_value',
        'discount_type',
        'discount_amount',
        'tax_amount',
        'shipping_amount',
        'total_amount',
        'amount_paid',
        'balance_due',
        'base_currency_total',
        'receivable_account_id',
        'deposit_account_id',
        'deposit_amount',
        'is_progress_billing',
        'progress_percentage',
        'project_id',
        'is_recurring',
        'recurring_schedule_id',
        'message',
        'statement_message',
        'internal_notes',
        'custom_field_1',
        'custom_field_2',
        'custom_field_3',
        'metadata',
        'attachments',
        'sent_at',
        'viewed_at',
        'last_reminder_at',
        'reminder_count',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'invoice_date' => 'date',
        'due_date' => 'date',
        'ship_date' => 'date',
        'exchange_rate' => 'decimal:10',
        'subtotal' => 'decimal:4',
        'discount_type_value' => 'decimal:4',
        'discount_amount' => 'decimal:4',
        'tax_amount' => 'decimal:4',
        'shipping_amount' => 'decimal:4',
        'total_amount' => 'decimal:4',
        'amount_paid' => 'decimal:4',
        'balance_due' => 'decimal:4',
        'base_currency_total' => 'decimal:4',
        'deposit_amount' => 'decimal:4',
        'progress_percentage' => 'decimal:4',
        'is_progress_billing' => 'boolean',
        'is_recurring' => 'boolean',
        'metadata' => 'array',
        'attachments' => 'array',
        'sent_at' => 'datetime',
        'viewed_at' => 'datetime',
        'last_reminder_at' => 'datetime',
    ];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Contact::class, 'customer_id');
    }

    public function transaction(): BelongsTo
    {
        return $this->belongsTo(Transaction::class);
    }

    public function estimate(): BelongsTo
    {
        return $this->belongsTo(Estimate::class);
    }

    public function currency(): BelongsTo
    {
        return $this->belongsTo(Currency::class, 'currency_code', 'code');
    }

    public function receivableAccount(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'receivable_account_id');
    }

    public function depositAccount(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'deposit_account_id');
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function recurringSchedule(): BelongsTo
    {
        return $this->belongsTo(RecurringSchedule::class);
    }

    public function lines(): HasMany
    {
        return $this->hasMany(InvoiceLine::class)->orderBy('line_number');
    }

    public function paymentApplications(): HasMany
    {
        return $this->hasMany(PaymentApplication::class);
    }

    public function createdByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
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
        $discountAmount = 0;

        if ($this->discount_type === 'percentage') {
            $discountAmount = $subtotal * ($this->discount_type_value / 100);
        } elseif ($this->discount_type === 'fixed') {
            $discountAmount = $this->discount_type_value;
        }

        $totalAmount = $subtotal - $discountAmount + $taxAmount + $this->shipping_amount;
        $balanceDue = $totalAmount - $this->amount_paid;

        $this->update([
            'subtotal' => $subtotal,
            'discount_amount' => $discountAmount,
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
            $this->sent_at !== null => 'sent',
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
        $this->customer->updateBalance();
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
        $lastInvoice = static::where('company_id', $companyId)
            ->orderBy('invoice_number', 'desc')
            ->first();

        if (!$lastInvoice) {
            return 'INV-0001';
        }

        $lastNumber = (int) substr($lastInvoice->invoice_number, 4);
        return 'INV-' . str_pad($lastNumber + 1, 4, '0', STR_PAD_LEFT);
    }
}
