<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use App\Models\Concerns\HasTripleEntry;
use App\Models\Concerns\HasUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class Estimate extends Model
{
    use HasFactory, HasUuid, BelongsToCompany, HasTripleEntry, SoftDeletes;

    protected $fillable = [
        'company_id',
        'customer_id',
        'estimate_number',
        'status',
        'estimate_date',
        'expiration_date',
        'currency_code',
        'exchange_rate',
        'billing_address',
        'shipping_address',
        'subtotal',
        'discount_amount',
        'discount_type',
        'tax_amount',
        'total_amount',
        'message',
        'internal_notes',
        'custom_fields',
        'attachments',
        'metadata',
        'sent_at',
        'viewed_at',
        'accepted_at',
        'rejected_at',
        'rejection_reason',
        'acceptance_signature',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'estimate_date' => 'date',
        'expiration_date' => 'date',
        'exchange_rate' => 'decimal:10',
        'subtotal' => 'decimal:4',
        'discount_amount' => 'decimal:4',
        'tax_amount' => 'decimal:4',
        'total_amount' => 'decimal:4',
        'custom_fields' => 'array',
        'attachments' => 'array',
        'metadata' => 'array',
        'sent_at' => 'datetime',
        'viewed_at' => 'datetime',
        'accepted_at' => 'datetime',
        'rejected_at' => 'datetime',
    ];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Contact::class, 'customer_id');
    }

    public function currency(): BelongsTo
    {
        return $this->belongsTo(Currency::class, 'currency_code', 'code');
    }

    public function lines(): HasMany
    {
        return $this->hasMany(EstimateLine::class)->orderBy('line_number');
    }

    public function invoice(): HasOne
    {
        return $this->hasOne(Invoice::class);
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
            'total_amount' => $subtotal - $this->discount_amount + $taxAmount,
        ]);
    }

    public function markAsSent(): void
    {
        $this->update([
            'status' => 'sent',
            'sent_at' => now(),
        ]);
    }

    public function markAsAccepted(?string $signature = null): void
    {
        $this->update([
            'status' => 'accepted',
            'accepted_at' => now(),
            'acceptance_signature' => $signature,
        ]);
    }

    public function markAsRejected(?string $reason = null): void
    {
        $this->update([
            'status' => 'rejected',
            'rejected_at' => now(),
            'rejection_reason' => $reason,
        ]);
    }

    public function convertToInvoice(): Invoice
    {
        $invoice = Invoice::create([
            'company_id' => $this->company_id,
            'customer_id' => $this->customer_id,
            'estimate_id' => $this->id,
            'invoice_number' => Invoice::generateNumber($this->company_id),
            'status' => 'draft',
            'invoice_date' => now(),
            'due_date' => now()->addDays(30),
            'currency_code' => $this->currency_code,
            'exchange_rate' => $this->exchange_rate,
            'billing_address' => $this->billing_address,
            'shipping_address' => $this->shipping_address,
            'subtotal' => $this->subtotal,
            'discount_amount' => $this->discount_amount,
            'discount_type' => $this->discount_type,
            'tax_amount' => $this->tax_amount,
            'total_amount' => $this->total_amount,
            'message' => $this->message,
            'custom_fields' => $this->custom_fields,
        ]);

        foreach ($this->lines as $line) {
            $invoice->lines()->create([
                'company_id' => $this->company_id,
                'line_number' => $line->line_number,
                'line_type' => $line->line_type,
                'product_id' => $line->product_id,
                'account_id' => $line->account_id,
                'description' => $line->description,
                'quantity' => $line->quantity,
                'unit_of_measure_id' => $line->unit_of_measure_id,
                'unit_price' => $line->unit_price,
                'discount_percent' => $line->discount_percent,
                'discount_amount' => $line->discount_amount,
                'amount' => $line->amount,
                'tax_rate_id' => $line->tax_rate_id,
                'tax_amount' => $line->tax_amount,
            ]);
        }

        $this->update(['status' => 'converted']);

        return $invoice;
    }
}
