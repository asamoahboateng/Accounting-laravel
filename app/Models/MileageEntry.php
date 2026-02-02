<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use App\Models\Concerns\HasTripleEntry;
use App\Models\Concerns\HasUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MileageEntry extends Model
{
    use HasFactory, HasUuid, BelongsToCompany, HasTripleEntry;

    protected $fillable = [
        'company_id',
        'transaction_id',
        'vehicle_id',
        'mileage_rate_id',
        'trip_date',
        'purpose',
        'start_location',
        'end_location',
        'distance',
        'distance_unit',
        'rate_per_unit',
        'total_amount',
        'expense_account_id',
        'customer_id',
        'is_billable',
        'is_round_trip',
        'status',
        'notes',
        'metadata',
        'created_by',
        'approved_by',
        'approved_at',
    ];

    protected $casts = [
        'trip_date' => 'date',
        'distance' => 'decimal:2',
        'rate_per_unit' => 'decimal:4',
        'total_amount' => 'decimal:4',
        'is_billable' => 'boolean',
        'is_round_trip' => 'boolean',
        'metadata' => 'array',
        'approved_at' => 'datetime',
    ];

    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(Vehicle::class);
    }

    public function mileageRate(): BelongsTo
    {
        return $this->belongsTo(MileageRate::class);
    }

    public function transaction(): BelongsTo
    {
        return $this->belongsTo(Transaction::class);
    }

    public function expenseAccount(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'expense_account_id');
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Contact::class, 'customer_id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    public function isApproved(): bool
    {
        return $this->status === 'approved';
    }

    public function isReimbursed(): bool
    {
        return $this->status === 'reimbursed';
    }

    public function approve(int $userId): void
    {
        $this->update([
            'status' => 'approved',
            'approved_by' => $userId,
            'approved_at' => now(),
        ]);
    }

    public function markAsReimbursed(): void
    {
        $this->update(['status' => 'reimbursed']);
    }

    public function getActualDistanceAttribute(): float
    {
        return $this->is_round_trip ? $this->distance * 2 : $this->distance;
    }

    protected static function booted(): void
    {
        static::creating(function (MileageEntry $entry) {
            if (!$entry->rate_per_unit && $entry->mileage_rate_id) {
                $rate = MileageRate::find($entry->mileage_rate_id);
                $entry->rate_per_unit = $rate?->rate_per_mile ?? 0;
            }

            $distance = $entry->is_round_trip ? $entry->distance * 2 : $entry->distance;
            $entry->total_amount = $distance * $entry->rate_per_unit;
        });

        static::saved(function (MileageEntry $entry) {
            if ($entry->vehicle_id) {
                $distance = $entry->is_round_trip ? $entry->distance * 2 : $entry->distance;
                $entry->vehicle->updateOdometer($distance);
            }
        });
    }
}
