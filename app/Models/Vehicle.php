<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use App\Models\Concerns\HasUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Vehicle extends Model
{
    use HasFactory, HasUuid, BelongsToCompany;

    protected $fillable = [
        'company_id',
        'name',
        'description',
        'make',
        'model',
        'year',
        'license_plate',
        'vin',
        'odometer_reading',
        'is_active',
    ];

    protected $casts = [
        'odometer_reading' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    public function mileageEntries(): HasMany
    {
        return $this->hasMany(MileageEntry::class);
    }

    public function getDisplayNameAttribute(): string
    {
        $parts = array_filter([
            $this->year,
            $this->make,
            $this->model,
        ]);

        return count($parts) > 0
            ? implode(' ', $parts) . " ({$this->name})"
            : $this->name;
    }

    public function getTotalMilesAttribute(): float
    {
        return $this->mileageEntries()->sum('distance');
    }

    public function updateOdometer(float $miles): void
    {
        $this->increment('odometer_reading', $miles);
    }
}
