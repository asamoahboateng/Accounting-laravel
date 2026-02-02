<?php

namespace App\Models;

use App\Models\Concerns\HasUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class Company extends Model
{
    use HasFactory, HasUuid, SoftDeletes;

    protected $fillable = [
        'id',
        'name',
        'slug',
        'legal_name',
        'tax_id',
        'registration_number',
        'email',
        'phone',
        'website',
        'address_line_1',
        'address_line_2',
        'city',
        'state',
        'postal_code',
        'country_code',
        'logo_path',
        'base_currency_code',
        'fiscal_year_start_month',
        'fiscal_year_start_day',
        'timezone',
        'date_format',
        'number_format',
        'industry',
        'company_type',
        'settings',
        'features',
        'subscription_plan',
        'subscription_ends_at',
        'is_active',
        'books_closed_through',
    ];

    protected $casts = [
        'settings' => 'array',
        'features' => 'array',
        'subscription_ends_at' => 'datetime',
        'books_closed_through' => 'datetime',
        'is_active' => 'boolean',
    ];

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class)
            ->using(CompanyUser::class)
            ->withPivot(['id', 'role', 'permissions', 'is_primary'])
            ->withTimestamps();
    }

    public function accounts(): HasMany
    {
        return $this->hasMany(Account::class);
    }

    public function contacts(): HasMany
    {
        return $this->hasMany(Contact::class);
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class);
    }

    public function journalEntries(): HasMany
    {
        return $this->hasMany(JournalEntry::class);
    }

    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class);
    }

    public function bills(): HasMany
    {
        return $this->hasMany(Bill::class);
    }

    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }

    public function fiscalYears(): HasMany
    {
        return $this->hasMany(FiscalYear::class);
    }

    public function fiscalPeriods(): HasMany
    {
        return $this->hasMany(FiscalPeriod::class);
    }

    public function bankAccounts(): HasMany
    {
        return $this->hasMany(BankAccount::class);
    }

    public function taxRates(): HasMany
    {
        return $this->hasMany(TaxRate::class);
    }

    public function themeSetting(): HasOne
    {
        return $this->hasOne(ThemeSetting::class);
    }

    public function getThemeSettings(): array
    {
        $setting = $this->themeSetting;

        if (!$setting) {
            return ThemeSetting::getDefaults();
        }

        return [
            'sidebar_bg' => $setting->sidebar_bg,
            'sidebar_text' => $setting->sidebar_text,
            'sidebar_text_muted' => $setting->sidebar_text_muted,
            'sidebar_hover_bg' => $setting->sidebar_hover_bg,
            'sidebar_active_bg' => $setting->sidebar_active_bg,
            'sidebar_border' => $setting->sidebar_border,
            'sidebar_brand_bg' => $setting->sidebar_brand_bg,
            'sidebar_accent_color' => $setting->sidebar_accent_color,
            'brand_name' => $setting->brand_name,
        ];
    }
}
