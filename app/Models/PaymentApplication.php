<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use App\Models\Concerns\HasUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PaymentApplication extends Model
{
    use HasFactory, HasUuid, BelongsToCompany;

    protected $fillable = [
        'company_id',
        'payment_received_id',
        'invoice_id',
        'amount_applied',
        'discount_amount',
    ];

    protected $casts = [
        'amount_applied' => 'decimal:4',
        'discount_amount' => 'decimal:4',
    ];

    public function paymentReceived(): BelongsTo
    {
        return $this->belongsTo(PaymentReceived::class);
    }

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    protected static function booted(): void
    {
        static::deleted(function (PaymentApplication $application) {
            $application->paymentReceived->updateAmounts();
            $application->invoice->updatePaymentStatus();
        });
    }
}
