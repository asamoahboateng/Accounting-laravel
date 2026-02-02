<?php

namespace App\Models;

use App\Models\Concerns\HasUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class AuditLog extends Model
{
    use HasUuid;

    public $timestamps = false;

    protected $fillable = [
        'company_id',
        'transaction_id',
        'journal_entry_id',
        'auditable_type',
        'auditable_id',
        'event',
        'old_values',
        'new_values',
        'changed_fields',
        'ip_address',
        'user_agent',
        'user_id',
        'user_email',
        'user_name',
        'batch_id',
        'previous_hash',
        'hash',
        'amount_affected',
        'currency_code',
        'metadata',
        'created_at',
    ];

    protected $casts = [
        'old_values' => 'array',
        'new_values' => 'array',
        'changed_fields' => 'array',
        'metadata' => 'array',
        'amount_affected' => 'decimal:4',
        'created_at' => 'datetime',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function transaction(): BelongsTo
    {
        return $this->belongsTo(Transaction::class);
    }

    public function journalEntry(): BelongsTo
    {
        return $this->belongsTo(JournalEntry::class);
    }

    public function auditable(): MorphTo
    {
        return $this->morphTo();
    }

    public function verifyIntegrity(): bool
    {
        $hashData = [
            'company_id' => $this->company_id,
            'auditable_type' => $this->auditable_type,
            'auditable_id' => $this->auditable_id,
            'event' => $this->event,
            'old_values' => $this->old_values,
            'new_values' => $this->new_values,
            'previous_hash' => $this->previous_hash,
            'timestamp' => $this->created_at->toIso8601String(),
        ];

        $computedHash = hash('sha256', json_encode($hashData));

        return $computedHash === $this->hash;
    }

    public function getChangeSummary(): string
    {
        if (empty($this->changed_fields)) {
            return $this->event;
        }

        return sprintf(
            '%s: %s',
            $this->event,
            implode(', ', $this->changed_fields)
        );
    }
}
