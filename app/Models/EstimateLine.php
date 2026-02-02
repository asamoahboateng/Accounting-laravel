<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use App\Models\Concerns\HasUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EstimateLine extends Model
{
    use HasFactory, HasUuid, BelongsToCompany;

    protected $fillable = [
        'company_id',
        'estimate_id',
        'line_number',
        'line_type',
        'product_id',
        'account_id',
        'description',
        'quantity',
        'unit_of_measure_id',
        'unit_price',
        'discount_percent',
        'discount_amount',
        'amount',
        'tax_rate_id',
        'tax_amount',
        'metadata',
    ];

    protected $casts = [
        'quantity' => 'decimal:4',
        'unit_price' => 'decimal:4',
        'discount_percent' => 'decimal:4',
        'discount_amount' => 'decimal:4',
        'amount' => 'decimal:4',
        'tax_amount' => 'decimal:4',
        'metadata' => 'array',
    ];

    public function estimate(): BelongsTo
    {
        return $this->belongsTo(Estimate::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    public function unitOfMeasure(): BelongsTo
    {
        return $this->belongsTo(UnitOfMeasure::class);
    }

    public function taxRate(): BelongsTo
    {
        return $this->belongsTo(TaxRate::class);
    }

    protected static function booted(): void
    {
        static::saving(function (EstimateLine $line) {
            $baseAmount = $line->quantity * $line->unit_price;
            $discountAmount = $line->discount_percent > 0
                ? $baseAmount * ($line->discount_percent / 100)
                : $line->discount_amount;

            $line->amount = $baseAmount - $discountAmount;

            if ($line->tax_rate_id) {
                $taxRate = TaxRate::find($line->tax_rate_id);
                if ($taxRate) {
                    $line->tax_amount = $line->amount * ($taxRate->rate / 100);
                }
            }
        });

        static::saved(function (EstimateLine $line) {
            $line->estimate->recalculateTotals();
        });

        static::deleted(function (EstimateLine $line) {
            $line->estimate->recalculateTotals();
        });
    }
}
