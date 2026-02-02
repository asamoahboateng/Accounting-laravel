<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use App\Models\Concerns\HasUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PurchaseOrderLine extends Model
{
    use HasFactory, HasUuid, BelongsToCompany;

    protected $fillable = [
        'company_id',
        'purchase_order_id',
        'line_number',
        'product_id',
        'account_id',
        'description',
        'quantity',
        'quantity_received',
        'quantity_billed',
        'unit_of_measure_id',
        'unit_cost',
        'amount',
        'tax_rate_id',
        'tax_amount',
        'customer_id',
        'is_billable',
        'expected_date',
        'metadata',
    ];

    protected $casts = [
        'quantity' => 'decimal:4',
        'quantity_received' => 'decimal:4',
        'quantity_billed' => 'decimal:4',
        'unit_cost' => 'decimal:4',
        'amount' => 'decimal:4',
        'tax_amount' => 'decimal:4',
        'is_billable' => 'boolean',
        'expected_date' => 'date',
        'metadata' => 'array',
    ];

    public function purchaseOrder(): BelongsTo
    {
        return $this->belongsTo(PurchaseOrder::class);
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

    public function getQuantityOpenAttribute(): float
    {
        return $this->quantity - $this->quantity_received;
    }

    public function getQuantityToBillAttribute(): float
    {
        return $this->quantity_received - $this->quantity_billed;
    }

    protected static function booted(): void
    {
        static::saving(function (PurchaseOrderLine $line) {
            $line->amount = $line->quantity * $line->unit_cost;

            if ($line->tax_rate_id) {
                $taxRate = TaxRate::find($line->tax_rate_id);
                if ($taxRate) {
                    $line->tax_amount = $line->amount * ($taxRate->rate / 100);
                }
            }
        });

        static::saved(function (PurchaseOrderLine $line) {
            $line->purchaseOrder->recalculateTotals();
        });

        static::deleted(function (PurchaseOrderLine $line) {
            $line->purchaseOrder->recalculateTotals();
        });
    }
}
