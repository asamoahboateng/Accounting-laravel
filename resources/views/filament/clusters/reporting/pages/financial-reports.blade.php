<x-filament-panels::page>
    <form wire:submit="generateReport">
        {{ $this->form }}

        <div class="mt-4 flex gap-4">
            <x-filament::button type="submit">
                Generate Report
            </x-filament::button>

            @if($reportData)
                <x-filament::button color="gray" wire:click="exportPdf">
                    <x-heroicon-o-document-arrow-down class="w-4 h-4 mr-2" />
                    Export PDF
                </x-filament::button>

                <x-filament::button color="gray" wire:click="exportExcel">
                    <x-heroicon-o-table-cells class="w-4 h-4 mr-2" />
                    Export Excel
                </x-filament::button>
            @endif
        </div>
    </form>

    @if($reportData)
        <div class="mt-8">
            @if($reportType === 'profit_loss')
                <x-filament::section>
                    <x-slot name="heading">
                        <h2 class="text-xl font-semibold">Profit & Loss Statement</h2>
                        <p class="text-sm text-gray-500">{{ \Carbon\Carbon::parse($startDate)->format('M d, Y') }} - {{ \Carbon\Carbon::parse($endDate)->format('M d, Y') }}</p>
                    </x-slot>

                    <div class="space-y-6">
                        {{-- Income Section --}}
                        <div>
                            <h3 class="font-semibold text-lg border-b pb-2 mb-3">Income</h3>
                            @foreach($reportData['income']['accounts'] as $account)
                                <div class="flex justify-between py-1 px-2 hover:bg-gray-50 dark:hover:bg-gray-800">
                                    <span>{{ $account['name'] }}</span>
                                    <span>${{ number_format($account['amount'], 2) }}</span>
                                </div>
                            @endforeach
                            <div class="flex justify-between py-2 px-2 font-semibold border-t mt-2">
                                <span>Total Income</span>
                                <span>${{ number_format($reportData['income']['total'], 2) }}</span>
                            </div>
                        </div>

                        {{-- Expenses Section --}}
                        <div>
                            <h3 class="font-semibold text-lg border-b pb-2 mb-3">Expenses</h3>
                            @foreach($reportData['expenses']['accounts'] as $account)
                                <div class="flex justify-between py-1 px-2 hover:bg-gray-50 dark:hover:bg-gray-800">
                                    <span>{{ $account['name'] }}</span>
                                    <span>${{ number_format($account['amount'], 2) }}</span>
                                </div>
                            @endforeach
                            <div class="flex justify-between py-2 px-2 font-semibold border-t mt-2">
                                <span>Total Expenses</span>
                                <span>${{ number_format($reportData['expenses']['total'], 2) }}</span>
                            </div>
                        </div>

                        {{-- Net Income --}}
                        <div class="border-t-2 border-b-2 py-3 mt-4">
                            <div class="flex justify-between text-xl font-bold">
                                <span>Net Income</span>
                                <span class="{{ $reportData['net_income'] >= 0 ? 'text-green-600' : 'text-red-600' }}">
                                    ${{ number_format($reportData['net_income'], 2) }}
                                </span>
                            </div>
                        </div>
                    </div>
                </x-filament::section>
            @endif

            @if($reportType === 'balance_sheet')
                <x-filament::section>
                    <x-slot name="heading">
                        <h2 class="text-xl font-semibold">Balance Sheet</h2>
                        <p class="text-sm text-gray-500">As of {{ \Carbon\Carbon::parse($endDate)->format('M d, Y') }}</p>
                    </x-slot>

                    <div class="grid grid-cols-2 gap-8">
                        {{-- Assets --}}
                        <div>
                            <h3 class="font-semibold text-lg border-b pb-2 mb-3">Assets</h3>
                            @foreach($reportData['assets']['accounts'] as $account)
                                <div class="flex justify-between py-1 px-2 hover:bg-gray-50 dark:hover:bg-gray-800">
                                    <span>{{ $account['name'] }}</span>
                                    <span>${{ number_format($account['balance'], 2) }}</span>
                                </div>
                            @endforeach
                            <div class="flex justify-between py-2 px-2 font-semibold border-t mt-2">
                                <span>Total Assets</span>
                                <span>${{ number_format($reportData['assets']['total'], 2) }}</span>
                            </div>
                        </div>

                        {{-- Liabilities & Equity --}}
                        <div>
                            <h3 class="font-semibold text-lg border-b pb-2 mb-3">Liabilities</h3>
                            @foreach($reportData['liabilities']['accounts'] as $account)
                                <div class="flex justify-between py-1 px-2 hover:bg-gray-50 dark:hover:bg-gray-800">
                                    <span>{{ $account['name'] }}</span>
                                    <span>${{ number_format($account['balance'], 2) }}</span>
                                </div>
                            @endforeach
                            <div class="flex justify-between py-2 px-2 font-semibold border-t mt-2">
                                <span>Total Liabilities</span>
                                <span>${{ number_format($reportData['liabilities']['total'], 2) }}</span>
                            </div>

                            <h3 class="font-semibold text-lg border-b pb-2 mb-3 mt-6">Equity</h3>
                            @foreach($reportData['equity']['accounts'] as $account)
                                <div class="flex justify-between py-1 px-2 hover:bg-gray-50 dark:hover:bg-gray-800">
                                    <span>{{ $account['name'] }}</span>
                                    <span>${{ number_format($account['balance'], 2) }}</span>
                                </div>
                            @endforeach
                            <div class="flex justify-between py-2 px-2 font-semibold border-t mt-2">
                                <span>Total Equity</span>
                                <span>${{ number_format($reportData['equity']['total'], 2) }}</span>
                            </div>

                            <div class="flex justify-between py-3 px-2 font-bold border-t-2 mt-4 text-lg">
                                <span>Total Liabilities & Equity</span>
                                <span>${{ number_format($reportData['total_liabilities_equity'], 2) }}</span>
                            </div>
                        </div>
                    </div>
                </x-filament::section>
            @endif

            @if($reportType === 'trial_balance')
                <x-filament::section>
                    <x-slot name="heading">
                        <h2 class="text-xl font-semibold">Trial Balance</h2>
                        <p class="text-sm text-gray-500">As of {{ \Carbon\Carbon::parse($endDate)->format('M d, Y') }}</p>
                    </x-slot>

                    <table class="w-full">
                        <thead>
                            <tr class="border-b">
                                <th class="text-left py-2">Account #</th>
                                <th class="text-left py-2">Account Name</th>
                                <th class="text-right py-2">Debit</th>
                                <th class="text-right py-2">Credit</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($reportData['accounts'] as $account)
                                <tr class="border-b hover:bg-gray-50 dark:hover:bg-gray-800">
                                    <td class="py-2">{{ $account['account_number'] }}</td>
                                    <td class="py-2">{{ $account['name'] }}</td>
                                    <td class="text-right py-2">{{ $account['debit'] > 0 ? '$' . number_format($account['debit'], 2) : '' }}</td>
                                    <td class="text-right py-2">{{ $account['credit'] > 0 ? '$' . number_format($account['credit'], 2) : '' }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                        <tfoot>
                            <tr class="border-t-2 font-bold">
                                <td class="py-3" colspan="2">Total</td>
                                <td class="text-right py-3">${{ number_format($reportData['total_debits'], 2) }}</td>
                                <td class="text-right py-3">${{ number_format($reportData['total_credits'], 2) }}</td>
                            </tr>
                        </tfoot>
                    </table>

                    @if(!$reportData['is_balanced'])
                        <div class="mt-4 p-4 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-lg">
                            <p class="text-red-700 dark:text-red-400 font-semibold">
                                Warning: Trial balance is out of balance by ${{ number_format(abs($reportData['total_debits'] - $reportData['total_credits']), 2) }}
                            </p>
                        </div>
                    @endif
                </x-filament::section>
            @endif
        </div>
    @endif
</x-filament-panels::page>
