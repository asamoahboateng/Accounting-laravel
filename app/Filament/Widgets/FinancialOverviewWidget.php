<?php

namespace App\Filament\Widgets;

use App\Models\Bill;
use App\Models\Invoice;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class FinancialOverviewWidget extends BaseWidget
{
    protected static ?int $sort = 1;

    protected int|string|array $columnSpan = 'full';

    protected function getStats(): array
    {
        $company = filament()->getTenant();

        if (! $company) {
            return [];
        }

        // Revenue this month
        $revenueThisMonth = Invoice::where('company_id', $company->id)
            ->whereMonth('invoice_date', now()->month)
            ->whereYear('invoice_date', now()->year)
            ->where('status', '!=', 'void')
            ->sum('total_amount');

        $revenueLastMonth = Invoice::where('company_id', $company->id)
            ->whereMonth('invoice_date', now()->subMonth()->month)
            ->whereYear('invoice_date', now()->subMonth()->year)
            ->where('status', '!=', 'void')
            ->sum('total_amount');

        $revenueChange = $revenueLastMonth > 0
            ? (($revenueThisMonth - $revenueLastMonth) / $revenueLastMonth) * 100
            : 0;

        // Expenses this month
        $expensesThisMonth = Bill::where('company_id', $company->id)
            ->whereMonth('bill_date', now()->month)
            ->whereYear('bill_date', now()->year)
            ->where('status', '!=', 'void')
            ->sum('total_amount');

        $expensesLastMonth = Bill::where('company_id', $company->id)
            ->whereMonth('bill_date', now()->subMonth()->month)
            ->whereYear('bill_date', now()->subMonth()->year)
            ->where('status', '!=', 'void')
            ->sum('total_amount');

        $expensesChange = $expensesLastMonth > 0
            ? (($expensesThisMonth - $expensesLastMonth) / $expensesLastMonth) * 100
            : 0;

        // Accounts Receivable
        $accountsReceivable = Invoice::where('company_id', $company->id)
            ->whereIn('status', ['sent', 'viewed', 'partial', 'overdue'])
            ->sum('balance_due');

        // Accounts Payable
        $accountsPayable = Bill::where('company_id', $company->id)
            ->whereIn('status', ['pending', 'partial', 'overdue'])
            ->sum('balance_due');

        // Net Profit
        $netProfit = $revenueThisMonth - $expensesThisMonth;

        return [
            Stat::make('Revenue (This Month)', '$' . number_format($revenueThisMonth, 2))
                ->description($revenueChange >= 0 ? number_format(abs($revenueChange), 1) . '% increase' : number_format(abs($revenueChange), 1) . '% decrease')
                ->descriptionIcon($revenueChange >= 0 ? 'heroicon-m-arrow-trending-up' : 'heroicon-m-arrow-trending-down')
                ->color($revenueChange >= 0 ? 'success' : 'danger')
                ->chart([7, 3, 4, 5, 6, 3, 5, 8]),

            Stat::make('Expenses (This Month)', '$' . number_format($expensesThisMonth, 2))
                ->description($expensesChange >= 0 ? number_format(abs($expensesChange), 1) . '% increase' : number_format(abs($expensesChange), 1) . '% decrease')
                ->descriptionIcon($expensesChange >= 0 ? 'heroicon-m-arrow-trending-up' : 'heroicon-m-arrow-trending-down')
                ->color($expensesChange <= 0 ? 'success' : 'warning')
                ->chart([3, 5, 4, 3, 6, 4, 5, 4]),

            Stat::make('Net Profit', '$' . number_format($netProfit, 2))
                ->description('Revenue minus Expenses')
                ->color($netProfit >= 0 ? 'success' : 'danger'),

            Stat::make('Accounts Receivable', '$' . number_format($accountsReceivable, 2))
                ->description('Outstanding invoices')
                ->descriptionIcon('heroicon-m-arrow-right')
                ->color('info'),

            Stat::make('Accounts Payable', '$' . number_format($accountsPayable, 2))
                ->description('Outstanding bills')
                ->descriptionIcon('heroicon-m-arrow-left')
                ->color('warning'),
        ];
    }
}
