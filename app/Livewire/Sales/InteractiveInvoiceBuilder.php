<?php

namespace App\Livewire\Sales;

use App\Models\Account;
use App\Models\Contact;
use App\Models\Invoice;
use App\Models\InvoiceLine;
use App\Models\Product;
use App\Models\TaxRate;
use Illuminate\Support\Str;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Livewire\Attributes\Validate;
use Livewire\Component;

class InteractiveInvoiceBuilder extends Component
{
    public ?string $invoiceId = null;

    #[Validate('required|uuid')]
    public ?string $customerId = null;

    #[Validate('required|string|max:50')]
    public string $invoiceNumber = '';

    #[Validate('required|date')]
    public string $invoiceDate = '';

    #[Validate('required|date')]
    public string $dueDate = '';

    public string $paymentTerms = 'net_30';
    public string $billingAddress = '';
    public string $shippingAddress = '';
    public string $message = '';
    public string $internalNotes = '';
    public string $discountType = '';
    public float $discountValue = 0;
    public float $shippingAmount = 0;

    public array $lines = [];
    public bool $showProductSearch = false;
    public int $activeLineIndex = -1;
    public string $productSearchTerm = '';

    protected $listeners = ['refreshInvoice' => '$refresh'];

    public function mount(?string $invoiceId = null): void
    {
        $this->invoiceId = $invoiceId;
        $this->invoiceDate = now()->format('Y-m-d');
        $this->dueDate = now()->addDays(30)->format('Y-m-d');

        if ($invoiceId) {
            $this->loadInvoice();
        } else {
            $this->generateInvoiceNumber();
            $this->addLine();
        }
    }

    protected function loadInvoice(): void
    {
        $invoice = Invoice::with('lines', 'customer')->find($this->invoiceId);

        if (! $invoice) {
            return;
        }

        $this->customerId = $invoice->customer_id;
        $this->invoiceNumber = $invoice->invoice_number;
        $this->invoiceDate = $invoice->invoice_date->format('Y-m-d');
        $this->dueDate = $invoice->due_date->format('Y-m-d');
        $this->paymentTerms = $invoice->payment_terms ?? 'net_30';
        $this->billingAddress = $invoice->billing_address ?? '';
        $this->shippingAddress = $invoice->shipping_address ?? '';
        $this->message = $invoice->message ?? '';
        $this->internalNotes = $invoice->internal_notes ?? '';
        $this->discountType = $invoice->discount_type ?? '';
        $this->discountValue = (float) $invoice->discount_type_value;
        $this->shippingAmount = (float) $invoice->shipping_amount;

        $this->lines = $invoice->lines->map(fn ($line) => [
            'id' => $line->id,
            'product_id' => $line->product_id,
            'account_id' => $line->account_id,
            'description' => $line->description ?? '',
            'quantity' => (float) $line->quantity,
            'unit_price' => (float) $line->unit_price,
            'discount_percent' => (float) $line->discount_percent,
            'tax_rate_id' => $line->tax_rate_id,
            'tax_amount' => (float) $line->tax_amount,
        ])->toArray();
    }

    protected function generateInvoiceNumber(): void
    {
        $lastInvoice = Invoice::where('company_id', filament()->getTenant()->id)
            ->orderBy('invoice_number', 'desc')
            ->first();

        $nextNumber = $lastInvoice
            ? (int) preg_replace('/\D/', '', $lastInvoice->invoice_number) + 1
            : 1;

        $this->invoiceNumber = 'INV-' . str_pad($nextNumber, 5, '0', STR_PAD_LEFT);
    }

    #[Computed]
    public function customers(): array
    {
        return Contact::where('company_id', filament()->getTenant()?->id)
            ->whereIn('type', ['customer', 'both'])
            ->where('is_active', true)
            ->orderBy('display_name')
            ->get()
            ->map(fn ($c) => ['id' => $c->id, 'name' => $c->display_name, 'email' => $c->email])
            ->toArray();
    }

    #[Computed]
    public function selectedCustomer(): ?array
    {
        if (! $this->customerId) {
            return null;
        }

        $customer = Contact::find($this->customerId);

        return $customer ? [
            'id' => $customer->id,
            'name' => $customer->display_name,
            'email' => $customer->email,
            'billing_address' => $customer->billing_address,
            'shipping_address' => $customer->shipping_address,
        ] : null;
    }

    #[Computed]
    public function products(): array
    {
        $query = Product::where('company_id', filament()->getTenant()?->id)
            ->where('is_active', true)
            ->where('is_sold', true);

        if ($this->productSearchTerm) {
            $query->where(function ($q) {
                $q->where('name', 'like', '%' . $this->productSearchTerm . '%')
                    ->orWhere('sku', 'like', '%' . $this->productSearchTerm . '%');
            });
        }

        return $query->limit(20)
            ->get()
            ->map(fn ($p) => [
                'id' => $p->id,
                'name' => $p->name,
                'sku' => $p->sku,
                'description' => $p->sales_description ?? $p->description,
                'price' => (float) $p->sales_price,
                'income_account_id' => $p->income_account_id,
            ])
            ->toArray();
    }

    #[Computed]
    public function taxRates(): array
    {
        return TaxRate::where('company_id', filament()->getTenant()?->id)
            ->where('is_active', true)
            ->whereIn('tax_type', ['sales', 'both'])
            ->get()
            ->map(fn ($t) => ['id' => $t->id, 'name' => $t->name, 'rate' => (float) $t->rate])
            ->toArray();
    }

    #[Computed]
    public function incomeAccounts(): array
    {
        return Account::where('company_id', filament()->getTenant()?->id)
            ->whereHas('accountType', fn ($q) => $q->where('classification', 'revenue'))
            ->where('is_active', true)
            ->get()
            ->map(fn ($a) => ['id' => $a->id, 'name' => $a->name, 'code' => $a->code])
            ->toArray();
    }

    #[Computed]
    public function subtotal(): float
    {
        return collect($this->lines)->sum(fn ($line) => $this->getLineAmount($line));
    }

    #[Computed]
    public function discountAmount(): float
    {
        if (! $this->discountType || $this->discountValue <= 0) {
            return 0;
        }

        return $this->discountType === 'percentage'
            ? $this->subtotal * ($this->discountValue / 100)
            : $this->discountValue;
    }

    #[Computed]
    public function taxTotal(): float
    {
        return collect($this->lines)->sum(fn ($line) => $this->calculateLineTax($line));
    }

    #[Computed]
    public function total(): float
    {
        return $this->subtotal - $this->discountAmount + $this->taxTotal + $this->shippingAmount;
    }

    protected function getLineAmount(array $line): float
    {
        $amount = ($line['quantity'] ?? 0) * ($line['unit_price'] ?? 0);

        if (($line['discount_percent'] ?? 0) > 0) {
            $amount -= $amount * ($line['discount_percent'] / 100);
        }

        return $amount;
    }

    protected function calculateLineTax(array $line): float
    {
        if (empty($line['tax_rate_id'])) {
            return 0;
        }

        $taxRate = collect($this->taxRates)->firstWhere('id', $line['tax_rate_id']);

        if (! $taxRate) {
            return 0;
        }

        return $this->getLineAmount($line) * ($taxRate['rate'] / 100);
    }

    public function addLine(): void
    {
        $this->lines[] = [
            'id' => null,
            'product_id' => null,
            'account_id' => null,
            'description' => '',
            'quantity' => 1,
            'unit_price' => 0,
            'discount_percent' => 0,
            'tax_rate_id' => null,
            'tax_amount' => 0,
        ];
    }

    public function removeLine(int $index): void
    {
        unset($this->lines[$index]);
        $this->lines = array_values($this->lines);
    }

    public function selectProduct(int $lineIndex, string $productId): void
    {
        $product = collect($this->products)->firstWhere('id', $productId);

        if ($product) {
            $this->lines[$lineIndex]['product_id'] = $product['id'];
            $this->lines[$lineIndex]['description'] = $product['description'] ?? '';
            $this->lines[$lineIndex]['unit_price'] = $product['price'];
            $this->lines[$lineIndex]['account_id'] = $product['income_account_id'];
        }

        $this->showProductSearch = false;
        $this->activeLineIndex = -1;
        $this->productSearchTerm = '';
    }

    public function openProductSearch(int $index): void
    {
        $this->activeLineIndex = $index;
        $this->showProductSearch = true;
        $this->productSearchTerm = '';
    }

    public function updatedCustomerId(): void
    {
        if ($this->selectedCustomer) {
            $this->billingAddress = $this->selectedCustomer['billing_address'] ?? '';
            $this->shippingAddress = $this->selectedCustomer['shipping_address'] ?? '';
        }
    }

    public function updatedPaymentTerms(): void
    {
        $days = match ($this->paymentTerms) {
            'due_on_receipt' => 0,
            'net_15' => 15,
            'net_30' => 30,
            'net_60' => 60,
            default => 30,
        };

        $this->dueDate = now()->parse($this->invoiceDate)->addDays($days)->format('Y-m-d');
    }

    public function save(): void
    {
        $this->validate();

        $data = [
            'company_id' => filament()->getTenant()->id,
            'customer_id' => $this->customerId,
            'invoice_number' => $this->invoiceNumber,
            'invoice_date' => $this->invoiceDate,
            'due_date' => $this->dueDate,
            'payment_terms' => $this->paymentTerms,
            'billing_address' => $this->billingAddress,
            'shipping_address' => $this->shippingAddress,
            'message' => $this->message,
            'internal_notes' => $this->internalNotes,
            'discount_type' => $this->discountType ?: null,
            'discount_type_value' => $this->discountValue,
            'discount_amount' => $this->discountAmount,
            'shipping_amount' => $this->shippingAmount,
            'subtotal' => $this->subtotal,
            'tax_amount' => $this->taxTotal,
            'total_amount' => $this->total,
            'balance_due' => $this->total,
            'status' => 'draft',
            'currency_code' => filament()->getTenant()->base_currency_code ?? 'USD',
            'exchange_rate' => 1,
        ];

        if ($this->invoiceId) {
            $invoice = Invoice::find($this->invoiceId);
            $invoice->update($data);
            $invoice->lines()->delete();
        } else {
            $data['id'] = (string) Str::uuid();
            $data['created_by'] = auth()->id();
            $invoice = Invoice::create($data);
            $this->invoiceId = $invoice->id;
        }

        foreach ($this->lines as $index => $line) {
            if (empty($line['description']) && empty($line['product_id'])) {
                continue;
            }

            InvoiceLine::create([
                'id' => (string) Str::uuid(),
                'company_id' => filament()->getTenant()->id,
                'invoice_id' => $invoice->id,
                'line_number' => $index + 1,
                'product_id' => $line['product_id'],
                'account_id' => $line['account_id'],
                'description' => $line['description'],
                'quantity' => $line['quantity'],
                'unit_price' => $line['unit_price'],
                'discount_percent' => $line['discount_percent'],
                'amount' => $this->getLineAmount($line),
                'tax_rate_id' => $line['tax_rate_id'],
                'tax_amount' => $this->calculateLineTax($line),
            ]);
        }

        $this->dispatch('notify', [
            'type' => 'success',
            'message' => $this->invoiceId ? 'Invoice updated successfully!' : 'Invoice created successfully!',
        ]);
    }

    public function saveAndSend(): void
    {
        $this->save();

        $invoice = Invoice::find($this->invoiceId);
        $invoice->update([
            'status' => 'sent',
            'sent_at' => now(),
        ]);

        $this->dispatch('notify', [
            'type' => 'success',
            'message' => 'Invoice saved and marked as sent!',
        ]);
    }

    public function render()
    {
        return view('livewire.sales.interactive-invoice-builder');
    }
}
