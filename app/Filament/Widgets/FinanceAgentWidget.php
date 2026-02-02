<?php

namespace App\Filament\Widgets;

use Filament\Widgets\Widget;

class FinanceAgentWidget extends Widget
{
    protected static string $view = 'filament.widgets.finance-agent-widget';

    protected static ?int $sort = 5;

    protected int|string|array $columnSpan = 'full';
}
