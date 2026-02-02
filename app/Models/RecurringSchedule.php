<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use App\Models\Concerns\HasUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RecurringSchedule extends Model
{
    use HasFactory, HasUuid, BelongsToCompany;

    protected $fillable = [
        'company_id',
        'name',
        'type',
        'template_type',
        'template_id',
        'frequency',
        'interval',
        'day_of_week',
        'day_of_month',
        'month_of_year',
        'start_date',
        'end_date',
        'next_occurrence',
        'last_occurrence',
        'occurrences_count',
        'max_occurrences',
        'is_active',
        'auto_send',
        'days_before_due',
        'metadata',
        'created_by',
    ];

    protected $casts = [
        'interval' => 'integer',
        'day_of_week' => 'integer',
        'day_of_month' => 'integer',
        'month_of_year' => 'integer',
        'start_date' => 'date',
        'end_date' => 'date',
        'next_occurrence' => 'date',
        'last_occurrence' => 'date',
        'occurrences_count' => 'integer',
        'max_occurrences' => 'integer',
        'is_active' => 'boolean',
        'auto_send' => 'boolean',
        'days_before_due' => 'integer',
        'metadata' => 'array',
    ];

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
            'yearly' => 'Yearly',
        ];
    }

    public static function getTemplateTypes(): array
    {
        return [
            'invoice' => 'Invoice',
            'bill' => 'Bill',
            'journal_entry' => 'Journal Entry',
        ];
    }

    public function calculateNextOccurrence(): ?\DateTime
    {
        if (!$this->is_active) {
            return null;
        }

        if ($this->end_date && now()->gt($this->end_date)) {
            return null;
        }

        if ($this->max_occurrences && $this->occurrences_count >= $this->max_occurrences) {
            return null;
        }

        $baseDate = $this->last_occurrence ?? $this->start_date;
        $next = clone $baseDate;

        switch ($this->frequency) {
            case 'daily':
                $next->addDays($this->interval);
                break;

            case 'weekly':
                $next->addWeeks($this->interval);
                if ($this->day_of_week !== null) {
                    $next->next($this->day_of_week);
                }
                break;

            case 'monthly':
                $next->addMonths($this->interval);
                if ($this->day_of_month) {
                    $day = min($this->day_of_month, $next->daysInMonth);
                    $next->day($day);
                }
                break;

            case 'yearly':
                $next->addYears($this->interval);
                if ($this->month_of_year) {
                    $next->month($this->month_of_year);
                }
                if ($this->day_of_month) {
                    $day = min($this->day_of_month, $next->daysInMonth);
                    $next->day($day);
                }
                break;
        }

        if ($this->end_date && $next->gt($this->end_date)) {
            return null;
        }

        return $next;
    }

    public function recordOccurrence(): void
    {
        $this->increment('occurrences_count');
        $this->update([
            'last_occurrence' => now(),
            'next_occurrence' => $this->calculateNextOccurrence(),
        ]);

        if ($this->max_occurrences && $this->occurrences_count >= $this->max_occurrences) {
            $this->update(['is_active' => false]);
        }
    }

    public function isDue(): bool
    {
        if (!$this->is_active || !$this->next_occurrence) {
            return false;
        }

        return now()->gte($this->next_occurrence);
    }
}
