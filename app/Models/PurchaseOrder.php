<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use App\Models\Concerns\HasTripleEntry;
use App\Models\Concerns\HasUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class PurchaseOrder extends Model
{
    use HasFactory, HasUuid, BelongsToCompany, HasTripleEntry, SoftDeletes;

    protected $fillable = [
        'company_id',
        'vendor_id',
        'po_number',
        'status',
        'po_date',
        'expected_date',
        'currency_code',
        'exchange_rate',
        'shipping_address',
        'ship_via',
        'subtotal',
        'discount_amount',
        'tax_amount',
        'shipping_amount',
        'total_amount',
        'inventory_location_id',
        'memo',
        'vendor_message',
        'internal_notes',
        'custom_fields',
        'attachments',
        'metadata',
        'sent_at',
        'closed_at',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'po_date' => 'date',
        'expected_date' => 'date',
        'exchange_rate' => 'decimal:10',
        'subtotal' => 'decimal:4',
        'discount_amount' => 'decimal:4',
        'tax_amount' => 'decimal:4',
        'shipping_amount' => 'decimal:4',
        'total_amount' => 'decimal:4',
        'custom_fields' => 'array',
        'attachments' => 'array',
        'metadata' => 'array',
        'sent_at' => 'datetime',
        'closed_at' => 'datetime',
    ];

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Contact::class, 'vendor_id');
    }

    public function currency(): BelongsTo
    {
        return $this->belongsTo(Currency::class, 'currency_code', 'code');
    }

    public function inventoryLocation(): BelongsTo
    {
        return $this->belongsTo(InventoryLocation::class);
    }

    public function lines(): HasMany
    {
        return $this->hasMany(PurchaseOrderLine::class)->orderBy('line_number');
    }

    public function bills(): HasMany
    {
        return $this->hasMany(Bill::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function recalculateTotals(): void
    {
        $subtotal = $this->lines()->sum('amount');
        $taxAmount = $this->lines()->sum('tax_amount');

        $this->update([
            'subtotal' => $subtotal,
            'tax_amount' => $taxAmount,
            'total_amount' => $subtotal - $this->discount_amount + $taxAmount + $this->shipping_amount,
        ]);
    }

    public function markAsSent(): void
    {
        $this->update([
            'status' => 'sent',
            'sent_at' => now(),
        ]);
    }

    public function markAsClosed(): void
    {
        $this->update([
            'status' => 'closed',
            'closed_at' => now(),
        ]);
    }

    public function isFullyReceived(): bool
    {
        foreach ($this->lines as $line) {
            if ($line->quantity_received < $line->quantity) {
                return false;
            }
        }
        return true;
    }

    public function isFullyBilled(): bool
    {
        foreach ($this->lines as $line) {
            if ($line->quantity_billed < $line->quantity) {
                return false;
            }
        }
        return true;
    }

    public function updateStatus(): void
    {
        $fullyReceived = $this->isFullyReceived();
        $fullyBilled = $this->isFullyBilled();
        $partiallyReceived = $this->lines()->where('quantity_received', '>', 0)->exists();

        if ($fullyReceived && $fullyBilled) {
            $this->update(['status' => 'closed']);
        } elseif ($fullyReceived) {
            $this->update(['status' => 'received']);
        } elseif ($partiallyReceived) {
            $this->update(['status' => 'partial']);
        }
    }

    public static function generateNumber(string $companyId): string
    {
        $lastPo = static::where('company_id', $companyId)
            ->orderBy('po_number', 'desc')
            ->first();

        if (!$lastPo) {
            return 'PO-0001';
        }

        $lastNumber = (int) substr($lastPo->po_number, 3);
        return 'PO-' . str_pad($lastNumber + 1, 4, '0', STR_PAD_LEFT);
    }
}
