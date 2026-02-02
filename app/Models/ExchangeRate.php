<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ExchangeRate extends Model
{
    use HasFactory;

    protected $fillable = [
        'from_currency_code',
        'to_currency_code',
        'rate',
        'effective_date',
        'source',
    ];

    protected $casts = [
        'rate' => 'decimal:10',
        'effective_date' => 'date',
    ];

    public function fromCurrency(): BelongsTo
    {
        return $this->belongsTo(Currency::class, 'from_currency_code', 'code');
    }

    public function toCurrency(): BelongsTo
    {
        return $this->belongsTo(Currency::class, 'to_currency_code', 'code');
    }

    public static function getRate(string $fromCurrency, string $toCurrency, ?\DateTimeInterface $date = null): ?float
    {
        if ($fromCurrency === $toCurrency) {
            return 1.0;
        }

        $date = $date ?? now();

        $rate = static::where('from_currency_code', $fromCurrency)
            ->where('to_currency_code', $toCurrency)
            ->where('effective_date', '<=', $date)
            ->orderBy('effective_date', 'desc')
            ->first();

        return $rate?->rate;
    }

    public static function convert(float $amount, string $fromCurrency, string $toCurrency, ?\DateTimeInterface $date = null): ?float
    {
        $rate = static::getRate($fromCurrency, $toCurrency, $date);

        if ($rate === null) {
            return null;
        }

        return $amount * $rate;
    }

    public function getInverseRateAttribute(): float
    {
        return $this->rate > 0 ? 1 / $this->rate : 0;
    }
}
