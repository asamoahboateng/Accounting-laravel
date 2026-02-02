<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use App\Models\Concerns\HasUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ScheduledReport extends Model
{
    use HasFactory, HasUuid, BelongsToCompany;

    protected $fillable = [
        'company_id',
        'report_configuration_id',
        'name',
        'frequency',
        'day_of_week',
        'day_of_month',
        'time_of_day',
        'recipients',
        'format',
        'is_active',
        'last_run_at',
        'next_run_at',
        'created_by',
    ];

    protected $casts = [
        'day_of_week' => 'integer',
        'day_of_month' => 'integer',
        'recipients' => 'array',
        'is_active' => 'boolean',
        'last_run_at' => 'datetime',
        'next_run_at' => 'datetime',
    ];

    public function reportConfiguration(): BelongsTo
    {
        return $this->belongsTo(ReportConfiguration::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public static function getFrequencies(): array
    {
        return [
            'daily' => 'Daily',
            'weekly' => 'Weekly',
            'monthly' => 'Monthly',
            'quarterly' => 'Quarterly',
        ];
    }

    public static function getFormats(): array
    {
        return [
            'pdf' => 'PDF',
            'excel' => 'Excel',
            'csv' => 'CSV',
        ];
    }

    public function calculateNextRun(): ?\DateTime
    {
        if (!$this->is_active) {
            return null;
        }

        $now = now();
        $next = $now->copy();

        switch ($this->frequency) {
            case 'daily':
                $next->setTimeFromTimeString($this->time_of_day ?? '08:00');
                if ($next->lte($now)) {
                    $next->addDay();
                }
                break;

            case 'weekly':
                $next->next($this->day_of_week ?? 1);
                $next->setTimeFromTimeString($this->time_of_day ?? '08:00');
                break;

            case 'monthly':
                $day = min($this->day_of_month ?? 1, $next->daysInMonth);
                $next->day($day);
                $next->setTimeFromTimeString($this->time_of_day ?? '08:00');
                if ($next->lte($now)) {
                    $next->addMonth();
                    $day = min($this->day_of_month ?? 1, $next->daysInMonth);
                    $next->day($day);
                }
                break;

            case 'quarterly':
                $currentQuarter = ceil($now->month / 3);
                $nextQuarterStart = ($currentQuarter * 3) + 1;
                if ($nextQuarterStart > 12) {
                    $next->addYear()->month(1);
                } else {
                    $next->month($nextQuarterStart);
                }
                $next->day(min($this->day_of_month ?? 1, $next->daysInMonth));
                $next->setTimeFromTimeString($this->time_of_day ?? '08:00');
                break;
        }

        return $next;
    }

    public function recordRun(): void
    {
        $this->update([
            'last_run_at' => now(),
            'next_run_at' => $this->calculateNextRun(),
        ]);
    }
}
