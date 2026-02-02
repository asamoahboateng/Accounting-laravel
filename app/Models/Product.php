<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use App\Models\Concerns\HasTripleEntry;
use App\Models\Concerns\HasUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Product extends Model
{
    use BelongsToCompany, HasFactory, HasTripleEntry, HasUuid, SoftDeletes;

    protected $fillable = [
        'company_id',
        'category_id',
        'type',
        'sku',
        'name',
        'description',
        'sales_description',
        'purchase_description',
        'sales_price',
        'purchase_cost',
        'currency_code',
        'track_inventory',
        'unit_of_measure_id',
        'quantity_on_hand',
        'quantity_on_order',
        'quantity_committed',
        'reorder_point',
        'reorder_quantity',
        'costing_method',
        'average_cost',
        'last_cost',
        'standard_cost',
        'income_account_id',
        'expense_account_id',
        'asset_account_id',
        'cogs_account_id',
        'sales_tax_rate_id',
        'purchase_tax_rate_id',
        'is_taxable',
        'weight',
        'weight_unit',
        'length',
        'width',
        'height',
        'dimension_unit',
        'barcode',
        'image_path',
        'images',
        'custom_fields',
        'metadata',
        'is_active',
        'is_sold',
        'is_purchased',
    ];

    protected $casts = [
        'sales_price' => 'decimal:4',
        'purchase_cost' => 'decimal:4',
        'track_inventory' => 'boolean',
        'quantity_on_hand' => 'decimal:4',
        'quantity_on_order' => 'decimal:4',
        'quantity_committed' => 'decimal:4',
        'reorder_point' => 'decimal:4',
        'reorder_quantity' => 'decimal:4',
        'average_cost' => 'decimal:4',
        'last_cost' => 'decimal:4',
        'standard_cost' => 'decimal:4',
        'is_taxable' => 'boolean',
        'weight' => 'decimal:4',
        'length' => 'decimal:4',
        'width' => 'decimal:4',
        'height' => 'decimal:4',
        'images' => 'array',
        'custom_fields' => 'array',
        'metadata' => 'array',
        'is_active' => 'boolean',
        'is_sold' => 'boolean',
        'is_purchased' => 'boolean',
    ];

    public function category(): BelongsTo
    {
        return $this->belongsTo(ProductCategory::class, 'category_id');
    }

    public function unitOfMeasure(): BelongsTo
    {
        return $this->belongsTo(UnitOfMeasure::class);
    }

    public function incomeAccount(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'income_account_id');
    }

    public function expenseAccount(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'expense_account_id');
    }

    public function assetAccount(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'asset_account_id');
    }

    public function cogsAccount(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'cogs_account_id');
    }

    public function salesTaxRate(): BelongsTo
    {
        return $this->belongsTo(TaxRate::class, 'sales_tax_rate_id');
    }

    public function purchaseTaxRate(): BelongsTo
    {
        return $this->belongsTo(TaxRate::class, 'purchase_tax_rate_id');
    }

    public function inventory(): HasMany
    {
        return $this->hasMany(ProductInventory::class);
    }

    public function movements(): HasMany
    {
        return $this->hasMany(InventoryMovement::class);
    }

    public function bundleComponents(): HasMany
    {
        return $this->hasMany(ProductBundle::class, 'bundle_product_id');
    }

    public function bundledIn(): HasMany
    {
        return $this->hasMany(ProductBundle::class, 'component_product_id');
    }

    public function isInventory(): bool
    {
        return $this->type === 'inventory';
    }

    public function isService(): bool
    {
        return $this->type === 'service';
    }

    public function isBundle(): bool
    {
        return $this->type === 'bundle';
    }

    public function getAvailableQuantity(): float
    {
        return $this->quantity_on_hand - $this->quantity_committed;
    }

    /**
     * Update average cost using Moving Average Cost method
     */
    public function updateAverageCost(float $quantity, float $unitCost): void
    {
        if ($quantity <= 0) {
            return;
        }

        $currentValue = $this->quantity_on_hand * $this->average_cost;
        $newValue = $quantity * $unitCost;
        $newQuantity = $this->quantity_on_hand + $quantity;

        if ($newQuantity > 0) {
            $this->average_cost = ($currentValue + $newValue) / $newQuantity;
        }

        $this->quantity_on_hand = $newQuantity;
        $this->last_cost = $unitCost;
        $this->save();
    }
}
