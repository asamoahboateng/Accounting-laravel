<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use App\Models\Concerns\HasUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BankConnection extends Model
{
    use HasFactory, HasUuid, BelongsToCompany;

    protected $fillable = [
        'company_id',
        'provider',
        'institution_id',
        'institution_name',
        'institution_logo',
        'access_token',
        'item_id',
        'status',
        'error_code',
        'error_message',
        'last_sync_at',
        'consent_expires_at',
        'metadata',
    ];

    protected $casts = [
        'last_sync_at' => 'datetime',
        'consent_expires_at' => 'datetime',
        'metadata' => 'array',
    ];

    protected $hidden = [
        'access_token',
    ];

    public function bankAccounts(): HasMany
    {
        return $this->hasMany(BankAccount::class);
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    public function hasError(): bool
    {
        return $this->status === 'error';
    }

    public function markAsError(string $code, string $message): void
    {
        $this->update([
            'status' => 'error',
            'error_code' => $code,
            'error_message' => $message,
        ]);
    }

    public function markAsActive(): void
    {
        $this->update([
            'status' => 'active',
            'error_code' => null,
            'error_message' => null,
        ]);
    }

    public function disconnect(): void
    {
        $this->update([
            'status' => 'disconnected',
            'access_token' => null,
        ]);
    }

    public function updateLastSync(): void
    {
        $this->update(['last_sync_at' => now()]);
    }

    public function isConsentExpired(): bool
    {
        return $this->consent_expires_at && $this->consent_expires_at->isPast();
    }
}
