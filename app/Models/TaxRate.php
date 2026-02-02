<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use App\Models\Concerns\HasUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class TaxRate extends Model
{
    use BelongsToCompany, HasFactory, HasUuid, SoftDeletes;

    protected $fillable = [
        'company_id',
        'tax_agency_id',
        'name',
        'code',
        'description',
        'tax_type',
        'rate',
        'is_compound',
        'is_inclusive',
        'account_id',
        'country_code',
        'state_code',
        'effective_date',
        'end_date',
        'is_default',
        'is_active',
    ];

    protected $casts = [
        'rate' => 'decimal:4',
        'is_compound' => 'boolean',
        'is_inclusive' => 'boolean',
        'is_default' => 'boolean',
        'is_active' => 'boolean',
        'effective_date' => 'date',
        'end_date' => 'date',
    ];

    public function taxAgency(): BelongsTo
    {
        return $this->belongsTo(TaxAgency::class);
    }

    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    public function components(): HasMany
    {
        return $this->hasMany(TaxRateComponent::class);
    }

    public function calculateTax(float $amount): float
    {
        if ($this->is_inclusive) {
            return $amount - ($amount / (1 + ($this->rate / 100)));
        }

        return $amount * ($this->rate / 100);
    }

    public function getDisplayNameAttribute(): string
    {
        return "{$this->name} ({$this->rate}%)";
    }
}
