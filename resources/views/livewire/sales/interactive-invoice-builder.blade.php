<div class="max-w-6xl mx-auto space-y-6">
    {{-- Header --}}
    <div class="flex items-center justify-between">
        <div>
            <h2 class="text-2xl font-bold text-gray-900 dark:text-white">
                {{ $invoiceId ? 'Edit Invoice' : 'Create Invoice' }}
            </h2>
            <p class="text-sm text-gray-600 dark:text-gray-400">{{ $invoiceNumber }}</p>
        </div>
        <div class="flex gap-2">
            <button wire:click="save" class="px-4 py-2 bg-gray-600 text-white rounded-lg hover:bg-gray-700 transition">
                <span wire:loading.remove wire:target="save">Save Draft</span>
                <span wire:loading wire:target="save">Saving...</span>
            </button>
            <button wire:click="saveAndSend" class="px-4 py-2 bg-emerald-600 text-white rounded-lg hover:bg-emerald-700 transition">
                <span wire:loading.remove wire:target="saveAndSend">Save & Send</span>
                <span wire:loading wire:target="saveAndSend">Sending...</span>
            </button>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        {{-- Main Content --}}
        <div class="lg:col-span-2 space-y-6">
            {{-- Customer Selection --}}
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-6">
                <h3 class="text-lg font-semibold mb-4">Customer Information</h3>
                <div class="grid grid-cols-2 gap-4">
                    <div class="col-span-2">
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Customer</label>
                        <select wire:model.live="customerId" class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700">
                            <option value="">Select a customer</option>
                            @foreach($this->customers as $customer)
                                <option value="{{ $customer['id'] }}">{{ $customer['name'] }}</option>
                            @endforeach
                        </select>
                        @error('customerId') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                    </div>
                    @if($this->selectedCustomer)
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Billing Address</label>
                            <textarea wire:model="billingAddress" rows="3" class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 text-sm"></textarea>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Shipping Address</label>
                            <textarea wire:model="shippingAddress" rows="3" class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 text-sm"></textarea>
                        </div>
                    @endif
                </div>
            </div>

            {{-- Invoice Details --}}
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-6">
                <h3 class="text-lg font-semibold mb-4">Invoice Details</h3>
                <div class="grid grid-cols-3 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Invoice Date</label>
                        <input type="date" wire:model="invoiceDate" class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Payment Terms</label>
                        <select wire:model.live="paymentTerms" class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700">
                            <option value="due_on_receipt">Due on Receipt</option>
                            <option value="net_15">Net 15</option>
                            <option value="net_30">Net 30</option>
                            <option value="net_60">Net 60</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Due Date</label>
                        <input type="date" wire:model="dueDate" class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700">
                    </div>
                </div>
            </div>

            {{-- Line Items --}}
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700">
                <div class="p-4 border-b border-gray-200 dark:border-gray-700">
                    <h3 class="text-lg font-semibold">Line Items</h3>
                </div>

                {{-- Header --}}
                <div class="grid grid-cols-12 gap-2 px-4 py-2 bg-gray-50 dark:bg-gray-700 text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">
                    <div class="col-span-4">Product / Description</div>
                    <div class="col-span-2">Account</div>
                    <div class="col-span-1 text-center">Qty</div>
                    <div class="col-span-2 text-right">Rate</div>
                    <div class="col-span-2 text-right">Amount</div>
                    <div class="col-span-1"></div>
                </div>

                {{-- Lines --}}
                <div class="divide-y divide-gray-200 dark:divide-gray-700">
                    @foreach($lines as $index => $line)
                        <div wire:key="line-{{ $index }}" class="grid grid-cols-12 gap-2 p-4 items-start">
                            <div class="col-span-4 space-y-2">
                                <button
                                    type="button"
                                    wire:click="openProductSearch({{ $index }})"
                                    class="w-full text-left px-3 py-2 rounded-lg border border-gray-300 dark:border-gray-600 dark:bg-gray-700 text-sm hover:border-emerald-500 transition"
                                >
                                    @if($line['product_id'])
                                        {{ collect($this->products)->firstWhere('id', $line['product_id'])['name'] ?? 'Select product' }}
                                    @else
                                        <span class="text-gray-400">Select product...</span>
                                    @endif
                                </button>
                                <textarea
                                    wire:model="lines.{{ $index }}.description"
                                    rows="2"
                                    placeholder="Description"
                                    class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 text-sm"
                                ></textarea>
                            </div>
                            <div class="col-span-2">
                                <select wire:model="lines.{{ $index }}.account_id" class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 text-sm">
                                    <option value="">Account</option>
                                    @foreach($this->incomeAccounts as $account)
                                        <option value="{{ $account['id'] }}">{{ $account['code'] }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-span-1">
                                <input
                                    type="number"
                                    wire:model.live="lines.{{ $index }}.quantity"
                                    step="0.01"
                                    min="0"
                                    class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 text-sm text-center"
                                >
                            </div>
                            <div class="col-span-2">
                                <div class="relative">
                                    <span class="absolute left-2 top-1/2 -translate-y-1/2 text-gray-500 text-sm">$</span>
                                    <input
                                        type="number"
                                        wire:model.live="lines.{{ $index }}.unit_price"
                                        step="0.01"
                                        min="0"
                                        class="w-full pl-6 rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 text-sm text-right"
                                    >
                                </div>
                            </div>
                            <div class="col-span-2 text-right">
                                <p class="py-2 font-medium text-gray-900 dark:text-white">
                                    ${{ number_format($this->getLineAmount($line), 2) }}
                                </p>
                            </div>
                            <div class="col-span-1 text-right">
                                <button
                                    wire:click="removeLine({{ $index }})"
                                    class="p-2 text-gray-400 hover:text-red-500 transition"
                                    @disabled(count($lines) === 1)
                                >
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                    </svg>
                                </button>
                            </div>
                        </div>
                    @endforeach
                </div>

                <div class="p-4 border-t border-gray-200 dark:border-gray-700">
                    <button
                        wire:click="addLine"
                        class="flex items-center gap-2 text-emerald-600 hover:text-emerald-700 font-medium"
                    >
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                        </svg>
                        Add Line
                    </button>
                </div>
            </div>

            {{-- Notes --}}
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-6">
                <h3 class="text-lg font-semibold mb-4">Notes</h3>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Message to Customer</label>
                        <textarea wire:model="message" rows="3" class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 text-sm"></textarea>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Internal Notes</label>
                        <textarea wire:model="internalNotes" rows="3" class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 text-sm"></textarea>
                    </div>
                </div>
            </div>
        </div>

        {{-- Sidebar Summary --}}
        <div class="space-y-6">
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-6 sticky top-6">
                <h3 class="text-lg font-semibold mb-4">Invoice Summary</h3>

                <div class="space-y-3">
                    <div class="flex justify-between text-sm">
                        <span class="text-gray-600 dark:text-gray-400">Subtotal</span>
                        <span class="font-medium">${{ number_format($this->subtotal, 2) }}</span>
                    </div>

                    {{-- Discount --}}
                    <div class="pt-3 border-t border-gray-200 dark:border-gray-700">
                        <div class="flex gap-2 mb-2">
                            <select wire:model.live="discountType" class="flex-1 rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 text-sm">
                                <option value="">No Discount</option>
                                <option value="percentage">Percentage (%)</option>
                                <option value="fixed">Fixed Amount ($)</option>
                            </select>
                            @if($discountType)
                                <input
                                    type="number"
                                    wire:model.live="discountValue"
                                    step="0.01"
                                    min="0"
                                    class="w-24 rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 text-sm"
                                >
                            @endif
                        </div>
                        @if($this->discountAmount > 0)
                            <div class="flex justify-between text-sm text-red-600">
                                <span>Discount</span>
                                <span>-${{ number_format($this->discountAmount, 2) }}</span>
                            </div>
                        @endif
                    </div>

                    {{-- Tax --}}
                    <div class="flex justify-between text-sm">
                        <span class="text-gray-600 dark:text-gray-400">Tax</span>
                        <span>${{ number_format($this->taxTotal, 2) }}</span>
                    </div>

                    {{-- Shipping --}}
                    <div class="pt-3 border-t border-gray-200 dark:border-gray-700">
                        <label class="block text-sm text-gray-600 dark:text-gray-400 mb-1">Shipping</label>
                        <div class="relative">
                            <span class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-500">$</span>
                            <input
                                type="number"
                                wire:model.live="shippingAmount"
                                step="0.01"
                                min="0"
                                class="w-full pl-7 rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 text-sm"
                            >
                        </div>
                    </div>

                    {{-- Total --}}
                    <div class="pt-3 border-t-2 border-gray-900 dark:border-white">
                        <div class="flex justify-between">
                            <span class="text-lg font-bold text-gray-900 dark:text-white">Total</span>
                            <span class="text-2xl font-bold text-emerald-600">${{ number_format($this->total, 2) }}</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Product Search Modal --}}
    @if($showProductSearch)
        <div class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
            <div class="flex items-center justify-center min-h-screen px-4">
                <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" wire:click="$set('showProductSearch', false)"></div>

                <div class="relative bg-white dark:bg-gray-800 rounded-xl shadow-xl max-w-lg w-full p-6 z-10">
                    <h3 class="text-lg font-semibold mb-4">Select Product</h3>

                    <input
                        type="text"
                        wire:model.live.debounce.300ms="productSearchTerm"
                        placeholder="Search products..."
                        class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 mb-4"
                        autofocus
                    >

                    <div class="max-h-64 overflow-y-auto space-y-2">
                        @forelse($this->products as $product)
                            <button
                                wire:click="selectProduct({{ $activeLineIndex }}, '{{ $product['id'] }}')"
                                class="w-full text-left p-3 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700 transition"
                            >
                                <p class="font-medium text-gray-900 dark:text-white">{{ $product['name'] }}</p>
                                <p class="text-sm text-gray-600 dark:text-gray-400">
                                    {{ $product['sku'] ? $product['sku'] . ' - ' : '' }}${{ number_format($product['price'], 2) }}
                                </p>
                            </button>
                        @empty
                            <p class="text-center text-gray-500 py-4">No products found</p>
                        @endforelse
                    </div>

                    <button
                        wire:click="$set('showProductSearch', false)"
                        class="mt-4 w-full px-4 py-2 bg-gray-200 dark:bg-gray-700 rounded-lg hover:bg-gray-300 dark:hover:bg-gray-600 transition"
                    >
                        Cancel
                    </button>
                </div>
            </div>
        </div>
    @endif
</div>
