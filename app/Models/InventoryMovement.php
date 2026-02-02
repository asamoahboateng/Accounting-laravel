<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use App\Models\Concerns\HasUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class InventoryMovement extends Model
{
    use HasFactory, HasUuid, BelongsToCompany;

    protected $fillable = [
        'company_id',
        'product_id',
        'location_id',
        'transaction_id',
        'movement_type',
        'reference_type',
        'reference_id',
        'movement_date',
        'quantity',
        'unit_cost',
        'total_cost',
        'quantity_before',
        'quantity_after',
        'average_cost_before',
        'average_cost_after',
        'notes',
        'created_by',
    ];

    protected $casts = [
        'movement_date' => 'date',
        'quantity' => 'decimal:4',
        'unit_cost' => 'decimal:4',
        'total_cost' => 'decimal:4',
        'quantity_before' => 'decimal:4',
        'quantity_after' => 'decimal:4',
        'average_cost_before' => 'decimal:4',
        'average_cost_after' => 'decimal:4',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function location(): BelongsTo
    {
        return $this->belongsTo(InventoryLocation::class, 'location_id');
    }

    public function transaction(): BelongsTo
    {
        return $this->belongsTo(Transaction::class);
    }

    public function reference(): MorphTo
    {
        return $this->morphTo('reference', 'reference_type', 'reference_id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function isIncrease(): bool
    {
        return $this->quantity > 0;
    }

    public function isDecrease(): bool
    {
        return $this->quantity < 0;
    }

    public static function getMovementTypes(): array
    {
        return [
            'purchase' => 'Purchase',
            'sale' => 'Sale',
            'adjustment' => 'Adjustment',
            'transfer' => 'Transfer',
            'assembly' => 'Assembly',
            'return' => 'Return',
            'write_off' => 'Write Off',
        ];
    }

    public static function recordMovement(
        Product $product,
        InventoryLocation $location,
        string $movementType,
        float $quantity,
        float $unitCost,
        ?string $referenceType = null,
        ?string $referenceId = null,
        ?string $notes = null,
        ?int $userId = null
    ): self {
        $inventory = ProductInventory::firstOrCreate(
            [
                'company_id' => $product->company_id,
                'product_id' => $product->id,
                'location_id' => $location->id,
            ],
            [
                'quantity_on_hand' => 0,
                'average_cost' => 0,
            ]
        );

        $quantityBefore = $inventory->quantity_on_hand;
        $averageCostBefore = $inventory->average_cost;

        $inventory->adjustQuantity($quantity, $quantity > 0 ? $unitCost : null);

        return static::create([
            'company_id' => $product->company_id,
            'product_id' => $product->id,
            'location_id' => $location->id,
            'movement_type' => $movementType,
            'reference_type' => $referenceType,
            'reference_id' => $referenceId,
            'movement_date' => now(),
            'quantity' => $quantity,
            'unit_cost' => $unitCost,
            'total_cost' => abs($quantity * $unitCost),
            'quantity_before' => $quantityBefore,
            'quantity_after' => $inventory->quantity_on_hand,
            'average_cost_before' => $averageCostBefore,
            'average_cost_after' => $inventory->average_cost,
            'notes' => $notes,
            'created_by' => $userId,
        ]);
    }
}
