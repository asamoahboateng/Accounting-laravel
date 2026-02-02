<?php

namespace App\Filament\Clusters\Reporting\Pages;

use App\Filament\Clusters\Reporting;
use App\Models\Account;
use App\Models\FiscalPeriod;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Form;
use Filament\Pages\Page;
use Illuminate\Support\Collection;

class FinancialReports extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-chart-bar';

    protected static string $view = 'filament.clusters.reporting.pages.financial-reports';

    protected static ?string $cluster = Reporting::class;

    protected static ?int $navigationSort = 10;

    protected static ?string $title = 'Financial Reports';

    public ?string $reportType = 'profit_loss';
    public ?string $startDate = null;
    public ?string $endDate = null;
    public ?string $accountingBasis = 'accrual';
    public ?string $comparisonPeriod = null;

    public ?array $reportData = null;

    public function mount(): void
    {
        $this->startDate = now()->startOfMonth()->format('Y-m-d');
        $this->endDate = now()->format('Y-m-d');
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Report Settings')
                    ->schema([
                        Select::make('reportType')
                            ->label('Report Type')
                            ->options([
                                'profit_loss' => 'Profit & Loss',
                                'balance_sheet' => 'Balance Sheet',
                                'cash_flow' => 'Statement of Cash Flows',
                                'trial_balance' => 'Trial Balance',
                                'general_ledger' => 'General Ledger',
                                'ar_aging' => 'Accounts Receivable Aging',
                                'ap_aging' => 'Accounts Payable Aging',
                            ])
                            ->required()
                            ->reactive(),

                        DatePicker::make('startDate')
                            ->label('From Date')
                            ->required(),

                        DatePicker::make('endDate')
                            ->label('To Date')
                            ->required(),

                        Select::make('accountingBasis')
                            ->label('Accounting Basis')
                            ->options([
                                'accrual' => 'Accrual',
                                'cash' => 'Cash',
                            ])
                            ->default('accrual'),

                        Select::make('comparisonPeriod')
                            ->label('Compare To')
                            ->options([
                                'previous_period' => 'Previous Period',
                                'previous_year' => 'Previous Year',
                                'budget' => 'Budget',
                            ])
                            ->placeholder('No comparison'),
                    ])
                    ->columns(5),
            ]);
    }

    public function generateReport(): void
    {
        $this->reportData = match ($this->reportType) {
            'profit_loss' => $this->generateProfitLoss(),
            'balance_sheet' => $this->generateBalanceSheet(),
            'cash_flow' => $this->generateCashFlow(),
            'trial_balance' => $this->generateTrialBalance(),
            default => [],
        };
    }

    protected function generateProfitLoss(): array
    {
        $company = filament()->getTenant();

        $incomeAccounts = Account::query()
            ->where('company_id', $company->id)
            ->whereHas('accountType', fn ($q) => $q->where('category', 'income'))
            ->with(['journalEntryLines' => function ($query) {
                $query->whereHas('journalEntry', function ($q) {
                    $q->whereBetween('entry_date', [$this->startDate, $this->endDate])
                        ->where('status', 'posted');
                });
            }])
            ->get();

        $expenseAccounts = Account::query()
            ->where('company_id', $company->id)
            ->whereHas('accountType', fn ($q) => $q->whereIn('category', ['expense', 'cost_of_goods_sold']))
            ->with(['journalEntryLines' => function ($query) {
                $query->whereHas('journalEntry', function ($q) {
                    $q->whereBetween('entry_date', [$this->startDate, $this->endDate])
                        ->where('status', 'posted');
                });
            }])
            ->get();

        $totalIncome = $incomeAccounts->sum(function ($account) {
            return $account->journalEntryLines->sum('credit_amount') - $account->journalEntryLines->sum('debit_amount');
        });

        $totalExpenses = $expenseAccounts->sum(function ($account) {
            return $account->journalEntryLines->sum('debit_amount') - $account->journalEntryLines->sum('credit_amount');
        });

        return [
            'income' => [
                'accounts' => $incomeAccounts->map(fn ($a) => [
                    'name' => $a->name,
                    'amount' => $a->journalEntryLines->sum('credit_amount') - $a->journalEntryLines->sum('debit_amount'),
                ]),
                'total' => $totalIncome,
            ],
            'expenses' => [
                'accounts' => $expenseAccounts->map(fn ($a) => [
                    'name' => $a->name,
                    'amount' => $a->journalEntryLines->sum('debit_amount') - $a->journalEntryLines->sum('credit_amount'),
                ]),
                'total' => $totalExpenses,
            ],
            'net_income' => $totalIncome - $totalExpenses,
        ];
    }

    protected function generateBalanceSheet(): array
    {
        $company = filament()->getTenant();

        $assetAccounts = Account::query()
            ->where('company_id', $company->id)
            ->whereHas('accountType', fn ($q) => $q->where('category', 'asset'))
            ->get();

        $liabilityAccounts = Account::query()
            ->where('company_id', $company->id)
            ->whereHas('accountType', fn ($q) => $q->where('category', 'liability'))
            ->get();

        $equityAccounts = Account::query()
            ->where('company_id', $company->id)
            ->whereHas('accountType', fn ($q) => $q->where('category', 'equity'))
            ->get();

        $totalAssets = $assetAccounts->sum(fn ($a) => $a->calculateBalance($this->endDate));
        $totalLiabilities = $liabilityAccounts->sum(fn ($a) => $a->calculateBalance($this->endDate));
        $totalEquity = $equityAccounts->sum(fn ($a) => $a->calculateBalance($this->endDate));

        return [
            'assets' => [
                'accounts' => $assetAccounts->map(fn ($a) => [
                    'name' => $a->name,
                    'balance' => $a->calculateBalance($this->endDate),
                ]),
                'total' => $totalAssets,
            ],
            'liabilities' => [
                'accounts' => $liabilityAccounts->map(fn ($a) => [
                    'name' => $a->name,
                    'balance' => $a->calculateBalance($this->endDate),
                ]),
                'total' => $totalLiabilities,
            ],
            'equity' => [
                'accounts' => $equityAccounts->map(fn ($a) => [
                    'name' => $a->name,
                    'balance' => $a->calculateBalance($this->endDate),
                ]),
                'total' => $totalEquity,
            ],
            'total_liabilities_equity' => $totalLiabilities + $totalEquity,
        ];
    }

    protected function generateCashFlow(): array
    {
        return [
            'operating' => [],
            'investing' => [],
            'financing' => [],
            'net_change' => 0,
        ];
    }

    protected function generateTrialBalance(): array
    {
        $company = filament()->getTenant();

        $accounts = Account::query()
            ->where('company_id', $company->id)
            ->where('is_active', true)
            ->orderBy('account_number')
            ->get();

        $data = [];
        $totalDebits = 0;
        $totalCredits = 0;

        foreach ($accounts as $account) {
            $balance = $account->calculateBalance($this->endDate);
            $debit = $balance > 0 ? $balance : 0;
            $credit = $balance < 0 ? abs($balance) : 0;

            $totalDebits += $debit;
            $totalCredits += $credit;

            $data[] = [
                'account_number' => $account->account_number,
                'name' => $account->name,
                'debit' => $debit,
                'credit' => $credit,
            ];
        }

        return [
            'accounts' => $data,
            'total_debits' => $totalDebits,
            'total_credits' => $totalCredits,
            'is_balanced' => abs($totalDebits - $totalCredits) < 0.01,
        ];
    }

    public function exportPdf(): void
    {
        // PDF export logic
    }

    public function exportExcel(): void
    {
        // Excel export logic
    }
}
