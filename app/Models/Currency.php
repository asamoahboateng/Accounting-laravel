<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Currency extends Model
{
    use HasFactory;

    protected $primaryKey = 'code';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'code',
        'name',
        'symbol',
        'decimal_places',
        'decimal_separator',
        'thousands_separator',
        'symbol_first',
        'is_active',
    ];

    protected $casts = [
        'symbol_first' => 'boolean',
        'is_active' => 'boolean',
    ];

    public function format(float $amount): string
    {
        $formatted = number_format(
            abs($amount),
            $this->decimal_places,
            $this->decimal_separator,
            $this->thousands_separator
        );

        $sign = $amount < 0 ? '-' : '';

        if ($this->symbol_first) {
            return $sign . $this->symbol . $formatted;
        }

        return $sign . $formatted . ' ' . $this->symbol;
    }
}
