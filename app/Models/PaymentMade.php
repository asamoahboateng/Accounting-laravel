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

class PaymentMade extends Model
{
    use HasFactory, HasUuid, BelongsToCompany, HasTripleEntry, SoftDeletes;

    protected $table = 'payments_made';

    protected $fillable = [
        'company_id',
        'transaction_id',
        'vendor_id',
        'payment_number',
        'payment_date',
        'payment_method_id',
        'check_number',
        'reference_number',
        'currency_code',
        'exchange_rate',
        'amount',
        'amount_applied',
        'amount_unapplied',
        'base_currency_amount',
        'bank_account_id',
        'payable_account_id',
        'status',
        'memo',
        'address',
        'is_print_later',
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
        'is_print_later' => 'boolean',
        'metadata' => 'array',
    ];

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Contact::class, 'vendor_id');
    }

    public function paymentMethod(): BelongsTo
    {
        return $this->belongsTo(PaymentMethod::class);
    }

    public function currency(): BelongsTo
    {
        return $this->belongsTo(Currency::class, 'currency_code', 'code');
    }

    public function bankAccount(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'bank_account_id');
    }

    public function payableAccount(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'payable_account_id');
    }

    public function transaction(): BelongsTo
    {
        return $this->belongsTo(Transaction::class);
    }

    public function applications(): HasMany
    {
        return $this->hasMany(BillPaymentApplication::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function applyToBill(Bill $bill, float $amount, float $discountAmount = 0): BillPaymentApplication
    {
        $application = $this->applications()->create([
            'company_id' => $this->company_id,
            'payable_type' => 'bill',
            'payable_id' => $bill->id,
            'amount_applied' => $amount,
            'discount_amount' => $discountAmount,
        ]);

        $this->updateAmounts();
        $bill->updatePaymentStatus();

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

    public function markAsCleared(): void
    {
        $this->update(['status' => 'cleared']);
    }

    public function markAsVoid(): void
    {
        $this->update(['status' => 'void']);

        foreach ($this->applications as $application) {
            if ($application->payable_type === 'bill') {
                $bill = Bill::find($application->payable_id);
                $application->delete();
                $bill?->updatePaymentStatus();
            }
        }
    }

    public static function generateNumber(string $companyId): string
    {
        $lastPayment = static::where('company_id', $companyId)
            ->orderBy('payment_number', 'desc')
            ->first();

        if (!$lastPayment) {
            return 'VPMT-0001';
        }

        $lastNumber = (int) substr($lastPayment->payment_number, 5);
        return 'VPMT-' . str_pad($lastNumber + 1, 4, '0', STR_PAD_LEFT);
    }

    protected static function booted(): void
    {
        static::creating(function (PaymentMade $payment) {
            $payment->base_currency_amount = $payment->amount * $payment->exchange_rate;
            $payment->amount_unapplied = $payment->amount;
        });
    }
}
