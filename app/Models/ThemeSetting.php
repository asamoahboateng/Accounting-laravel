<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ThemeSetting extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'company_id',
        'sidebar_bg',
        'sidebar_text',
        'sidebar_text_muted',
        'sidebar_hover_bg',
        'sidebar_active_bg',
        'sidebar_border',
        'sidebar_brand_bg',
        'sidebar_accent_color',
        'brand_name',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public static function getDefaults(): array
    {
        return [
            'sidebar_bg' => '#1e293b',
            'sidebar_text' => '#e2e8f0',
            'sidebar_text_muted' => '#94a3b8',
            'sidebar_hover_bg' => '#334155',
            'sidebar_active_bg' => '#0f172a',
            'sidebar_border' => '#334155',
            'sidebar_brand_bg' => '#0f172a',
            'sidebar_accent_color' => '#10b981',
            'brand_name' => 'QuickBooks Clone',
        ];
    }

    public static function getPresets(): array
    {
        return [
            'slate' => [
                'name' => 'Slate (Default)',
                'sidebar_bg' => '#1e293b',
                'sidebar_text' => '#e2e8f0',
                'sidebar_text_muted' => '#94a3b8',
                'sidebar_hover_bg' => '#334155',
                'sidebar_active_bg' => '#0f172a',
                'sidebar_border' => '#334155',
                'sidebar_brand_bg' => '#0f172a',
                'sidebar_accent_color' => '#10b981',
            ],
            'dark' => [
                'name' => 'Dark',
                'sidebar_bg' => '#111827',
                'sidebar_text' => '#f9fafb',
                'sidebar_text_muted' => '#9ca3af',
                'sidebar_hover_bg' => '#1f2937',
                'sidebar_active_bg' => '#000000',
                'sidebar_border' => '#374151',
                'sidebar_brand_bg' => '#000000',
                'sidebar_accent_color' => '#3b82f6',
            ],
            'indigo' => [
                'name' => 'Indigo',
                'sidebar_bg' => '#312e81',
                'sidebar_text' => '#e0e7ff',
                'sidebar_text_muted' => '#a5b4fc',
                'sidebar_hover_bg' => '#3730a3',
                'sidebar_active_bg' => '#1e1b4b',
                'sidebar_border' => '#4338ca',
                'sidebar_brand_bg' => '#1e1b4b',
                'sidebar_accent_color' => '#818cf8',
            ],
            'emerald' => [
                'name' => 'Emerald',
                'sidebar_bg' => '#064e3b',
                'sidebar_text' => '#d1fae5',
                'sidebar_text_muted' => '#6ee7b7',
                'sidebar_hover_bg' => '#065f46',
                'sidebar_active_bg' => '#022c22',
                'sidebar_border' => '#047857',
                'sidebar_brand_bg' => '#022c22',
                'sidebar_accent_color' => '#34d399',
            ],
            'rose' => [
                'name' => 'Rose',
                'sidebar_bg' => '#881337',
                'sidebar_text' => '#ffe4e6',
                'sidebar_text_muted' => '#fda4af',
                'sidebar_hover_bg' => '#9f1239',
                'sidebar_active_bg' => '#4c0519',
                'sidebar_border' => '#be123c',
                'sidebar_brand_bg' => '#4c0519',
                'sidebar_accent_color' => '#fb7185',
            ],
            'amber' => [
                'name' => 'Amber',
                'sidebar_bg' => '#78350f',
                'sidebar_text' => '#fef3c7',
                'sidebar_text_muted' => '#fcd34d',
                'sidebar_hover_bg' => '#92400e',
                'sidebar_active_bg' => '#451a03',
                'sidebar_border' => '#b45309',
                'sidebar_brand_bg' => '#451a03',
                'sidebar_accent_color' => '#fbbf24',
            ],
            'cyan' => [
                'name' => 'Cyan',
                'sidebar_bg' => '#164e63',
                'sidebar_text' => '#cffafe',
                'sidebar_text_muted' => '#67e8f9',
                'sidebar_hover_bg' => '#155e75',
                'sidebar_active_bg' => '#083344',
                'sidebar_border' => '#0891b2',
                'sidebar_brand_bg' => '#083344',
                'sidebar_accent_color' => '#22d3ee',
            ],
            'light' => [
                'name' => 'Light',
                'sidebar_bg' => '#f8fafc',
                'sidebar_text' => '#1e293b',
                'sidebar_text_muted' => '#64748b',
                'sidebar_hover_bg' => '#e2e8f0',
                'sidebar_active_bg' => '#cbd5e1',
                'sidebar_border' => '#e2e8f0',
                'sidebar_brand_bg' => '#ffffff',
                'sidebar_accent_color' => '#10b981',
            ],
        ];
    }
}
