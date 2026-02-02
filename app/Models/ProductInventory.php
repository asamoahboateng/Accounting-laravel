<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use App\Models\Concerns\HasUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductInventory extends Model
{
    use HasFactory, HasUuid, BelongsToCompany;

    protected $table = 'product_inventory';

    protected $fillable = [
        'company_id',
        'product_id',
        'location_id',
        'quantity_on_hand',
        'quantity_committed',
        'quantity_on_order',
        'average_cost',
        'bin_location',
    ];

    protected $casts = [
        'quantity_on_hand' => 'decimal:4',
        'quantity_committed' => 'decimal:4',
        'quantity_on_order' => 'decimal:4',
        'average_cost' => 'decimal:4',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function location(): BelongsTo
    {
        return $this->belongsTo(InventoryLocation::class, 'location_id');
    }

    public function getQuantityAvailableAttribute(): float
    {
        return $this->quantity_on_hand - $this->quantity_committed;
    }

    public function adjustQuantity(float $adjustment, float $unitCost = null): void
    {
        $oldQuantity = $this->quantity_on_hand;
        $newQuantity = $oldQuantity + $adjustment;

        if ($adjustment > 0 && $unitCost !== null) {
            $totalOldValue = $oldQuantity * $this->average_cost;
            $totalNewValue = $adjustment * $unitCost;
            $this->average_cost = $newQuantity > 0
                ? ($totalOldValue + $totalNewValue) / $newQuantity
                : 0;
        }

        $this->quantity_on_hand = $newQuantity;
        $this->save();
    }

    public function commitQuantity(float $quantity): bool
    {
        if ($quantity > $this->quantity_available) {
            return false;
        }

        $this->increment('quantity_committed', $quantity);
        return true;
    }

    public function releaseCommitment(float $quantity): void
    {
        $this->decrement('quantity_committed', min($quantity, $this->quantity_committed));
    }

    public function fulfillCommitment(float $quantity): void
    {
        $this->decrement('quantity_committed', min($quantity, $this->quantity_committed));
        $this->decrement('quantity_on_hand', $quantity);
    }
}
