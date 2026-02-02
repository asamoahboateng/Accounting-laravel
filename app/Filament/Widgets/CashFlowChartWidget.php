<?php

namespace App\Filament\Widgets;

use App\Models\Bill;
use App\Models\Invoice;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Carbon;

class CashFlowChartWidget extends ChartWidget
{
    protected static ?string $heading = 'Cash Flow (Last 6 Months)';

    protected static ?int $sort = 2;

    protected int|string|array $columnSpan = 'full';

    protected function getData(): array
    {
        $company = filament()->getTenant();

        if (! $company) {
            return [
                'datasets' => [],
                'labels' => [],
            ];
        }

        $months = collect();
        $incomeData = collect();
        $expenseData = collect();

        for ($i = 5; $i >= 0; $i--) {
            $date = now()->subMonths($i);
            $months->push($date->format('M Y'));

            $income = Invoice::where('company_id', $company->id)
                ->whereMonth('invoice_date', $date->month)
                ->whereYear('invoice_date', $date->year)
                ->where('status', '!=', 'void')
                ->sum('total_amount');

            $expenses = Bill::where('company_id', $company->id)
                ->whereMonth('bill_date', $date->month)
                ->whereYear('bill_date', $date->year)
                ->where('status', '!=', 'void')
                ->sum('total_amount');

            $incomeData->push($income);
            $expenseData->push($expenses);
        }

        return [
            'datasets' => [
                [
                    'label' => 'Income',
                    'data' => $incomeData->toArray(),
                    'backgroundColor' => 'rgba(16, 185, 129, 0.2)',
                    'borderColor' => 'rgb(16, 185, 129)',
                    'fill' => true,
                ],
                [
                    'label' => 'Expenses',
                    'data' => $expenseData->toArray(),
                    'backgroundColor' => 'rgba(239, 68, 68, 0.2)',
                    'borderColor' => 'rgb(239, 68, 68)',
                    'fill' => true,
                ],
            ],
            'labels' => $months->toArray(),
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }

    protected function getOptions(): array
    {
        return [
            'scales' => [
                'y' => [
                    'beginAtZero' => true,
                    'ticks' => [
                        'callback' => "function(value) { return '$' + value.toLocaleString(); }",
                    ],
                ],
            ],
            'plugins' => [
                'tooltip' => [
                    'callbacks' => [
                        'label' => "function(context) { return context.dataset.label + ': $' + context.parsed.y.toLocaleString(); }",
                    ],
                ],
            ],
        ];
    }
}
