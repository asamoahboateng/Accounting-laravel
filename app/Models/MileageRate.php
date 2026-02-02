<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use App\Models\Concerns\HasUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MileageRate extends Model
{
    use HasFactory, HasUuid, BelongsToCompany;

    protected $fillable = [
        'company_id',
        'year',
        'rate_type',
        'rate_per_mile',
        'effective_date',
        'end_date',
        'is_active',
    ];

    protected $casts = [
        'year' => 'integer',
        'rate_per_mile' => 'decimal:4',
        'effective_date' => 'date',
        'end_date' => 'date',
        'is_active' => 'boolean',
    ];

    public function mileageEntries(): HasMany
    {
        return $this->hasMany(MileageEntry::class);
    }

    public static function getRateTypes(): array
    {
        return [
            'standard' => 'Standard Business',
            'charity' => 'Charity',
            'medical' => 'Medical/Moving',
        ];
    }

    public function isEffective(\DateTimeInterface $date = null): bool
    {
        $date = $date ?? now();

        if (!$this->is_active) {
            return false;
        }

        if ($this->effective_date > $date) {
            return false;
        }

        if ($this->end_date && $this->end_date < $date) {
            return false;
        }

        return true;
    }

    public static function getEffectiveRate(string $companyId, string $rateType = 'standard', \DateTimeInterface $date = null): ?self
    {
        $date = $date ?? now();

        return static::where('company_id', $companyId)
            ->where('rate_type', $rateType)
            ->where('is_active', true)
            ->where('effective_date', '<=', $date)
            ->where(function ($query) use ($date) {
                $query->whereNull('end_date')
                    ->orWhere('end_date', '>=', $date);
            })
            ->orderBy('effective_date', 'desc')
            ->first();
    }
}
