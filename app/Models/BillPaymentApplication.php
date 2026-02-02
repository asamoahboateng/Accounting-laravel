<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use App\Models\Concerns\HasUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class BillPaymentApplication extends Model
{
    use HasFactory, HasUuid, BelongsToCompany;

    protected $fillable = [
        'company_id',
        'payment_made_id',
        'payable_type',
        'payable_id',
        'amount_applied',
        'discount_amount',
    ];

    protected $casts = [
        'amount_applied' => 'decimal:4',
        'discount_amount' => 'decimal:4',
    ];

    public function paymentMade(): BelongsTo
    {
        return $this->belongsTo(PaymentMade::class);
    }

    public function payable(): MorphTo
    {
        return $this->morphTo();
    }

    public function bill(): BelongsTo
    {
        return $this->belongsTo(Bill::class, 'payable_id')
            ->where('payable_type', 'bill');
    }

    protected static function booted(): void
    {
        static::deleted(function (BillPaymentApplication $application) {
            $application->paymentMade->updateAmounts();

            if ($application->payable_type === 'bill') {
                $bill = Bill::find($application->payable_id);
                $bill?->updatePaymentStatus();
            }
        });
    }
}
