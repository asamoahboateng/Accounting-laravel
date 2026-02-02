<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use App\Models\Concerns\HasUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductBundle extends Model
{
    use HasFactory, HasUuid, BelongsToCompany;

    protected $fillable = [
        'company_id',
        'bundle_product_id',
        'component_product_id',
        'quantity',
        'sort_order',
    ];

    protected $casts = [
        'quantity' => 'decimal:4',
        'sort_order' => 'integer',
    ];

    public function bundleProduct(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'bundle_product_id');
    }

    public function componentProduct(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'component_product_id');
    }

    public function getComponentCostAttribute(): float
    {
        return $this->quantity * $this->componentProduct->average_cost;
    }

    public function hasAvailableInventory(): bool
    {
        if (!$this->componentProduct->track_inventory) {
            return true;
        }

        return $this->componentProduct->quantity_on_hand >= $this->quantity;
    }

    public function getMaxBuildableQuantityAttribute(): float
    {
        if (!$this->componentProduct->track_inventory) {
            return PHP_FLOAT_MAX;
        }

        if ($this->quantity <= 0) {
            return 0;
        }

        return floor($this->componentProduct->quantity_on_hand / $this->quantity);
    }
}
