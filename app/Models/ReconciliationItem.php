<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use App\Models\Concerns\HasUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReconciliationItem extends Model
{
    use BelongsToCompany, HasFactory, HasUuid;

    protected $fillable = [
        'company_id',
        'reconciliation_id',
        'journal_entry_line_id',
        'amount',
        'is_cleared',
        'cleared_at',
    ];

    protected $casts = [
        'amount' => 'decimal:4',
        'is_cleared' => 'boolean',
        'cleared_at' => 'datetime',
    ];

    public function reconciliation(): BelongsTo
    {
        return $this->belongsTo(Reconciliation::class);
    }

    public function journalEntryLine(): BelongsTo
    {
        return $this->belongsTo(JournalEntryLine::class);
    }
}
