<?php

namespace App\Filament\Widgets;

use App\Models\Invoice;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;

class AccountsReceivableWidget extends BaseWidget
{
    protected static ?string $heading = 'Accounts Receivable Aging';

    protected static ?int $sort = 3;

    protected int|string|array $columnSpan = 1;

    public function table(Table $table): Table
    {
        return $table
            ->query(
                Invoice::query()
                    ->where('company_id', filament()->getTenant()?->id)
                    ->whereIn('status', ['sent', 'viewed', 'partial', 'overdue'])
                    ->where('balance_due', '>', 0)
                    ->orderBy('due_date')
                    ->limit(5)
            )
            ->columns([
                Tables\Columns\TextColumn::make('invoice_number')
                    ->label('Invoice')
                    ->url(fn (Invoice $record) => route('filament.admin.sales.resources.invoices.view', [
                        'tenant' => filament()->getTenant(),
                        'record' => $record,
                    ])),

                Tables\Columns\TextColumn::make('customer.display_name')
                    ->label('Customer')
                    ->limit(20),

                Tables\Columns\TextColumn::make('due_date')
                    ->label('Due')
                    ->date()
                    ->color(fn (Invoice $record): string => $record->isOverdue() ? 'danger' : 'gray'),

                Tables\Columns\TextColumn::make('balance_due')
                    ->label('Amount')
                    ->money('USD')
                    ->alignEnd(),
            ])
            ->paginated(false);
    }
}
