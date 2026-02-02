<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use App\Models\Concerns\HasUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AnomalyDetection extends Model
{
    use BelongsToCompany, HasFactory, HasUuid;

    protected $table = 'anomaly_detections';

    protected $fillable = [
        'company_id',
        'fiscal_period_id',
        'detection_type',
        'severity',
        'status',
        'entity_type',
        'entity_id',
        'anomaly_code',
        'title',
        'description',
        'confidence_score',
        'detection_data',
        'suggested_actions',
        'resolution_notes',
        'reviewed_by',
        'reviewed_at',
        'resolved_by',
        'resolved_at',
    ];

    protected $casts = [
        'confidence_score' => 'decimal:4',
        'detection_data' => 'array',
        'suggested_actions' => 'array',
        'reviewed_at' => 'datetime',
        'resolved_at' => 'datetime',
    ];

    public function fiscalPeriod(): BelongsTo
    {
        return $this->belongsTo(FiscalPeriod::class);
    }

    public function reviewedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function resolvedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'resolved_by');
    }

    public function markAsReviewed(): void
    {
        $this->update([
            'status' => 'reviewed',
            'reviewed_by' => auth()->id(),
            'reviewed_at' => now(),
        ]);
    }

    public function resolve(string $notes): void
    {
        $this->update([
            'status' => 'resolved',
            'resolution_notes' => $notes,
            'resolved_by' => auth()->id(),
            'resolved_at' => now(),
        ]);
    }

    public function dismiss(): void
    {
        $this->update([
            'status' => 'dismissed',
            'resolved_by' => auth()->id(),
            'resolved_at' => now(),
        ]);
    }

    public function getSeverityColorAttribute(): string
    {
        return match ($this->severity) {
            'critical' => 'danger',
            'warning' => 'warning',
            'info' => 'info',
            default => 'gray',
        };
    }
}
