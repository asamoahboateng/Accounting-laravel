<?php

namespace App\Models\Concerns;

use App\Models\AuditLog;
use App\Models\JournalEntry;
use App\Models\Transaction;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

trait HasTripleEntry
{
    protected static string $auditBatchId = '';

    public static function bootHasTripleEntry(): void
    {
        static::created(function ($model) {
            if (method_exists($model, 'shouldCreateTripleEntry') && ! $model->shouldCreateTripleEntry()) {
                return;
            }

            static::createAuditLog($model, 'created');
        });

        static::updated(function ($model) {
            static::createAuditLog($model, 'updated');
        });

        static::deleted(function ($model) {
            static::createAuditLog($model, 'deleted');
        });
    }

    public static function startAuditBatch(): string
    {
        static::$auditBatchId = (string) Str::uuid();
        return static::$auditBatchId;
    }

    public static function endAuditBatch(): void
    {
        static::$auditBatchId = '';
    }

    protected static function createAuditLog($model, string $event): void
    {
        $companyId = $model->company_id ?? (filament()->getTenant()?->id);

        if (! $companyId) {
            return;
        }

        $oldValues = $event === 'updated' ? $model->getOriginal() : null;
        $newValues = $event === 'deleted' ? null : $model->getAttributes();

        $changedFields = $event === 'updated'
            ? array_keys($model->getDirty())
            : null;

        // Get the previous hash for chain integrity
        $previousLog = AuditLog::where('company_id', $companyId)
            ->orderBy('created_at', 'desc')
            ->first();

        $previousHash = $previousLog?->hash;

        // Calculate hash for this entry
        $hashData = [
            'company_id' => $companyId,
            'auditable_type' => get_class($model),
            'auditable_id' => $model->getKey(),
            'event' => $event,
            'old_values' => $oldValues,
            'new_values' => $newValues,
            'previous_hash' => $previousHash,
            'timestamp' => now()->toIso8601String(),
        ];

        $hash = hash('sha256', json_encode($hashData));

        AuditLog::create([
            'id' => (string) Str::uuid(),
            'company_id' => $companyId,
            'transaction_id' => $model->transaction_id ?? ($model instanceof Transaction ? $model->id : null),
            'journal_entry_id' => $model->journal_entry_id ?? ($model instanceof JournalEntry ? $model->id : null),
            'auditable_type' => get_class($model),
            'auditable_id' => $model->getKey(),
            'event' => $event,
            'old_values' => $oldValues,
            'new_values' => $newValues,
            'changed_fields' => $changedFields,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'user_id' => auth()->id(),
            'user_email' => auth()->user()?->email,
            'user_name' => auth()->user()?->name,
            'batch_id' => static::$auditBatchId ?: null,
            'previous_hash' => $previousHash,
            'hash' => $hash,
            'amount_affected' => $model->total_amount ?? $model->amount ?? null,
            'currency_code' => $model->currency_code ?? null,
            'created_at' => now(),
        ]);
    }

    public function auditLogs(): MorphMany
    {
        return $this->morphMany(AuditLog::class, 'auditable');
    }
}
