<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use App\Models\Concerns\HasUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReportSnapshot extends Model
{
    use HasFactory, HasUuid, BelongsToCompany;

    protected $fillable = [
        'company_id',
        'report_configuration_id',
        'name',
        'report_type',
        'period_start',
        'period_end',
        'generated_at',
        'parameters',
        'data',
        'summary',
        'file_path',
        'created_by',
    ];

    protected $casts = [
        'period_start' => 'date',
        'period_end' => 'date',
        'generated_at' => 'datetime',
        'parameters' => 'array',
        'data' => 'array',
        'summary' => 'array',
    ];

    public function reportConfiguration(): BelongsTo
    {
        return $this->belongsTo(ReportConfiguration::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function getPeriodLabelAttribute(): string
    {
        if ($this->period_start->eq($this->period_end)) {
            return $this->period_start->format('M j, Y');
        }

        return $this->period_start->format('M j, Y') . ' - ' . $this->period_end->format('M j, Y');
    }

    public function hasFile(): bool
    {
        return !empty($this->file_path);
    }
}
