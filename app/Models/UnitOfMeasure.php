<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use App\Models\Concerns\HasUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class UnitOfMeasure extends Model
{
    use HasFactory, HasUuid, BelongsToCompany;

    protected $table = 'units_of_measure';

    protected $fillable = [
        'company_id',
        'name',
        'abbreviation',
        'is_base_unit',
        'base_unit_id',
        'conversion_factor',
        'is_active',
    ];

    protected $casts = [
        'is_base_unit' => 'boolean',
        'conversion_factor' => 'decimal:10',
        'is_active' => 'boolean',
    ];

    public function baseUnit(): BelongsTo
    {
        return $this->belongsTo(UnitOfMeasure::class, 'base_unit_id');
    }

    public function derivedUnits(): HasMany
    {
        return $this->hasMany(UnitOfMeasure::class, 'base_unit_id');
    }

    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }

    public function convertToBase(float $quantity): float
    {
        if ($this->is_base_unit) {
            return $quantity;
        }

        return $quantity * $this->conversion_factor;
    }

    public function convertFromBase(float $quantity): float
    {
        if ($this->is_base_unit) {
            return $quantity;
        }

        return $quantity / $this->conversion_factor;
    }

    public function convertTo(float $quantity, UnitOfMeasure $targetUnit): float
    {
        $baseQuantity = $this->convertToBase($quantity);
        return $targetUnit->convertFromBase($baseQuantity);
    }
}
