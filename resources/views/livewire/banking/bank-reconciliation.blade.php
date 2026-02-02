<div class="space-y-6">
    {{-- Header --}}
    <div class="flex items-center justify-between">
        <div>
            <h2 class="text-2xl font-bold text-gray-900 dark:text-white">Bank Reconciliation</h2>
            <p class="text-sm text-gray-600 dark:text-gray-400">Match your bank statement with your records</p>
        </div>
        @if($reconciliationId)
            <div class="flex gap-2">
                <button wire:click="saveProgress" class="px-4 py-2 bg-gray-600 text-white rounded-lg hover:bg-gray-700 transition">
                    Save Progress
                </button>
                <button
                    wire:click="finishReconciliation"
                    @class([
                        'px-4 py-2 rounded-lg transition',
                        'bg-emerald-600 text-white hover:bg-emerald-700' => $this->isBalanced,
                        'bg-gray-300 text-gray-500 cursor-not-allowed' => !$this->isBalanced,
                    ])
                    @disabled(!$this->isBalanced)
                >
                    Finish Reconciliation
                </button>
            </div>
        @endif
    </div>

    {{-- Setup Form --}}
    @if(!$reconciliationId)
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-6">
            <h3 class="text-lg font-semibold mb-4">Start Reconciliation</h3>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Bank Account</label>
                    <select wire:model.live="bankAccountId" class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700">
                        <option value="">Select Account</option>
                        @foreach($this->bankAccounts as $account)
                            <option value="{{ $account['id'] }}">{{ $account['name'] }} (${{ number_format($account['balance'], 2) }})</option>
                        @endforeach
                    </select>
                    @error('bankAccountId') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Statement Date</label>
                    <input type="date" wire:model="statementDate" class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700">
                    @error('statementDate') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Statement Ending Balance</label>
                    <div class="relative">
                        <span class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-500">$</span>
                        <input type="number" step="0.01" wire:model="statementBalance" class="w-full pl-7 rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700">
                    </div>
                    @error('statementBalance') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                </div>
            </div>
            <div class="mt-4">
                <button wire:click="startReconciliation" class="px-6 py-2 bg-emerald-600 text-white rounded-lg hover:bg-emerald-700 transition">
                    Start Reconciling
                </button>
            </div>
        </div>
    @else
        {{-- Reconciliation Summary --}}
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-4">
                <p class="text-sm text-gray-600 dark:text-gray-400">Statement Balance</p>
                <p class="text-2xl font-bold text-gray-900 dark:text-white">${{ number_format($statementBalance, 2) }}</p>
            </div>
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-4">
                <p class="text-sm text-gray-600 dark:text-gray-400">Opening Balance</p>
                <p class="text-2xl font-bold text-gray-900 dark:text-white">${{ number_format($openingBalance, 2) }}</p>
            </div>
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-4">
                <p class="text-sm text-gray-600 dark:text-gray-400">Cleared Balance</p>
                <p class="text-2xl font-bold text-emerald-600">${{ number_format($this->clearedBalance, 2) }}</p>
            </div>
            <div @class([
                'rounded-xl shadow-sm border p-4',
                'bg-emerald-50 border-emerald-200 dark:bg-emerald-900/20 dark:border-emerald-800' => $this->isBalanced,
                'bg-red-50 border-red-200 dark:bg-red-900/20 dark:border-red-800' => !$this->isBalanced,
            ])>
                <p class="text-sm text-gray-600 dark:text-gray-400">Difference</p>
                <p @class([
                    'text-2xl font-bold',
                    'text-emerald-600' => $this->isBalanced,
                    'text-red-600' => !$this->isBalanced,
                ])>
                    ${{ number_format($this->difference, 2) }}
                </p>
                @if($this->isBalanced)
                    <p class="text-sm text-emerald-600 mt-1">Balanced!</p>
                @endif
            </div>
        </div>

        {{-- Transactions List --}}
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700">
            {{-- Filters --}}
            <div class="p-4 border-b border-gray-200 dark:border-gray-700">
                <div class="flex flex-wrap items-center gap-4">
                    <div class="flex-1 min-w-64">
                        <input
                            type="text"
                            wire:model.live.debounce.300ms="searchTerm"
                            placeholder="Search transactions..."
                            class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700"
                        >
                    </div>
                    <select wire:model.live="filterType" class="rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700">
                        <option value="all">All Transactions</option>
                        <option value="deposits">Deposits Only</option>
                        <option value="withdrawals">Withdrawals Only</option>
                    </select>
                    <div class="flex gap-2">
                        <button wire:click="selectAll" class="px-3 py-1.5 text-sm bg-gray-100 dark:bg-gray-700 rounded-lg hover:bg-gray-200 dark:hover:bg-gray-600 transition">
                            Select All
                        </button>
                        <button wire:click="deselectAll" class="px-3 py-1.5 text-sm bg-gray-100 dark:bg-gray-700 rounded-lg hover:bg-gray-200 dark:hover:bg-gray-600 transition">
                            Deselect All
                        </button>
                    </div>
                </div>
            </div>

            {{-- Transactions Table --}}
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-gray-50 dark:bg-gray-700">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Cleared</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Date</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Reference</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Description</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Contact</th>
                            <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Amount</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                        @forelse($this->transactions as $transaction)
                            <tr
                                wire:key="{{ $transaction['id'] }}"
                                wire:click="toggleTransaction('{{ $transaction['id'] }}')"
                                @class([
                                    'cursor-pointer transition',
                                    'bg-emerald-50 dark:bg-emerald-900/20' => $transaction['is_selected'],
                                    'hover:bg-gray-50 dark:hover:bg-gray-700/50' => !$transaction['is_selected'],
                                ])
                            >
                                <td class="px-4 py-3">
                                    <input
                                        type="checkbox"
                                        @checked($transaction['is_selected'])
                                        class="rounded border-gray-300 text-emerald-600 focus:ring-emerald-500"
                                    >
                                </td>
                                <td class="px-4 py-3 text-sm text-gray-900 dark:text-white">{{ $transaction['date'] }}</td>
                                <td class="px-4 py-3 text-sm text-gray-600 dark:text-gray-400">{{ $transaction['reference'] }}</td>
                                <td class="px-4 py-3 text-sm text-gray-900 dark:text-white">{{ $transaction['description'] }}</td>
                                <td class="px-4 py-3 text-sm text-gray-600 dark:text-gray-400">{{ $transaction['contact'] ?? '-' }}</td>
                                <td @class([
                                    'px-4 py-3 text-sm text-right font-medium',
                                    'text-emerald-600' => $transaction['amount'] > 0,
                                    'text-red-600' => $transaction['amount'] < 0,
                                ])>
                                    ${{ number_format(abs($transaction['amount']), 2) }}
                                    <span class="text-gray-400 text-xs">{{ $transaction['amount'] > 0 ? 'DR' : 'CR' }}</span>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-4 py-8 text-center text-gray-500">
                                    No transactions found to reconcile.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    @endif
</div>
