<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AccountType extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'code',
        'classification',
        'normal_balance',
        'display_order',
        'is_system',
    ];

    protected $casts = [
        'is_system' => 'boolean',
    ];

    public function subtypes(): HasMany
    {
        return $this->hasMany(AccountSubtype::class);
    }

    public function accounts(): HasMany
    {
        return $this->hasMany(Account::class);
    }

    public function isAsset(): bool
    {
        return $this->classification === 'asset';
    }

    public function isLiability(): bool
    {
        return $this->classification === 'liability';
    }

    public function isEquity(): bool
    {
        return $this->classification === 'equity';
    }

    public function isRevenue(): bool
    {
        return $this->classification === 'revenue';
    }

    public function isExpense(): bool
    {
        return $this->classification === 'expense';
    }
}
