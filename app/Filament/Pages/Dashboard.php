<?php

namespace App\Filament\Pages;

use App\Filament\Widgets\AccountsPayableWidget;
use App\Filament\Widgets\AccountsReceivableWidget;
use App\Filament\Widgets\CashFlowChartWidget;
use App\Filament\Widgets\FinancialOverviewWidget;
use Filament\Pages\Dashboard as BaseDashboard;

class Dashboard extends BaseDashboard
{
    protected static ?string $navigationIcon = 'heroicon-o-home';

    protected static ?int $navigationSort = -2;

    public function getWidgets(): array
    {
        return [
            FinancialOverviewWidget::class,
            CashFlowChartWidget::class,
            AccountsReceivableWidget::class,
            AccountsPayableWidget::class,
        ];
    }

    public function getColumns(): int|string|array
    {
        return 2;
    }
}
