<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use App\Models\Concerns\HasUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BillLine extends Model
{
    use HasFactory, HasUuid, BelongsToCompany;

    protected $fillable = [
        'company_id',
        'bill_id',
        'line_number',
        'line_type',
        'product_id',
        'account_id',
        'description',
        'quantity',
        'unit_of_measure_id',
        'unit_cost',
        'discount_percent',
        'discount_amount',
        'amount',
        'tax_rate_id',
        'tax_amount',
        'customer_id',
        'is_billable',
        'billed_status',
        'class_id',
        'purchase_order_line_id',
        'metadata',
    ];

    protected $casts = [
        'quantity' => 'decimal:4',
        'unit_cost' => 'decimal:4',
        'discount_percent' => 'decimal:4',
        'discount_amount' => 'decimal:4',
        'amount' => 'decimal:4',
        'tax_amount' => 'decimal:4',
        'is_billable' => 'boolean',
        'metadata' => 'array',
    ];

    public function bill(): BelongsTo
    {
        return $this->belongsTo(Bill::class);
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

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Contact::class, 'customer_id');
    }

    public function trackingClass(): BelongsTo
    {
        return $this->belongsTo(TrackingCategory::class, 'class_id');
    }

    public function purchaseOrderLine(): BelongsTo
    {
        return $this->belongsTo(PurchaseOrderLine::class);
    }

    public function isBillable(): bool
    {
        return $this->is_billable && $this->billed_status !== 'billed';
    }

    public function markAsBilled(): void
    {
        $this->update(['billed_status' => 'billed']);
    }

    protected static function booted(): void
    {
        static::saving(function (BillLine $line) {
            $baseAmount = $line->quantity * $line->unit_cost;
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

        static::saved(function (BillLine $line) {
            $line->bill->recalculateTotals();
        });

        static::deleted(function (BillLine $line) {
            $line->bill->recalculateTotals();
        });
    }
}
