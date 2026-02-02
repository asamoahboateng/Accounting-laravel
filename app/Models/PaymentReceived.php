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

class PaymentReceived extends Model
{
    use HasFactory, HasUuid, BelongsToCompany, HasTripleEntry, SoftDeletes;

    protected $table = 'payments_received';

    protected $fillable = [
        'company_id',
        'transaction_id',
        'customer_id',
        'payment_number',
        'payment_date',
        'payment_method_id',
        'reference_number',
        'currency_code',
        'exchange_rate',
        'amount',
        'amount_applied',
        'amount_unapplied',
        'base_currency_amount',
        'deposit_account_id',
        'receivable_account_id',
        'status',
        'memo',
        'metadata',
        'created_by',
    ];

    protected $casts = [
        'payment_date' => 'date',
        'exchange_rate' => 'decimal:10',
        'amount' => 'decimal:4',
        'amount_applied' => 'decimal:4',
        'amount_unapplied' => 'decimal:4',
        'base_currency_amount' => 'decimal:4',
        'metadata' => 'array',
    ];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Contact::class, 'customer_id');
    }

    public function paymentMethod(): BelongsTo
    {
        return $this->belongsTo(PaymentMethod::class);
    }

    public function currency(): BelongsTo
    {
        return $this->belongsTo(Currency::class, 'currency_code', 'code');
    }

    public function depositAccount(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'deposit_account_id');
    }

    public function receivableAccount(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'receivable_account_id');
    }

    public function transaction(): BelongsTo
    {
        return $this->belongsTo(Transaction::class);
    }

    public function applications(): HasMany
    {
        return $this->hasMany(PaymentApplication::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function applyToInvoice(Invoice $invoice, float $amount, float $discountAmount = 0): PaymentApplication
    {
        $application = $this->applications()->create([
            'company_id' => $this->company_id,
            'invoice_id' => $invoice->id,
            'amount_applied' => $amount,
            'discount_amount' => $discountAmount,
        ]);

        $this->updateAmounts();
        $invoice->updatePaymentStatus();

        return $application;
    }

    public function updateAmounts(): void
    {
        $totalApplied = $this->applications()->sum('amount_applied');
        $this->update([
            'amount_applied' => $totalApplied,
            'amount_unapplied' => $this->amount - $totalApplied,
        ]);
    }

    public function markAsDeposited(): void
    {
        $this->update(['status' => 'deposited']);
    }

    public function markAsVoid(): void
    {
        $this->update(['status' => 'void']);

        foreach ($this->applications as $application) {
            $invoice = $application->invoice;
            $application->delete();
            $invoice->updatePaymentStatus();
        }
    }

    public static function generateNumber(string $companyId): string
    {
        $lastPayment = static::where('company_id', $companyId)
            ->orderBy('payment_number', 'desc')
            ->first();

        if (!$lastPayment) {
            return 'PMT-0001';
        }

        $lastNumber = (int) substr($lastPayment->payment_number, 4);
        return 'PMT-' . str_pad($lastNumber + 1, 4, '0', STR_PAD_LEFT);
    }

    protected static function booted(): void
    {
        static::creating(function (PaymentReceived $payment) {
            $payment->base_currency_amount = $payment->amount * $payment->exchange_rate;
            $payment->amount_unapplied = $payment->amount;
        });
    }
}
