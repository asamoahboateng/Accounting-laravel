<?php

namespace App\Filament\Widgets;

use App\Models\Bill;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;

class AccountsPayableWidget extends BaseWidget
{
    protected static ?string $heading = 'Accounts Payable Aging';

    protected static ?int $sort = 4;

    protected int|string|array $columnSpan = 1;

    public function table(Table $table): Table
    {
        return $table
            ->query(
                Bill::query()
                    ->where('company_id', filament()->getTenant()?->id)
                    ->whereIn('status', ['pending', 'partial', 'overdue'])
                    ->where('balance_due', '>', 0)
                    ->orderBy('due_date')
                    ->limit(5)
            )
            ->columns([
                Tables\Columns\TextColumn::make('bill_number')
                    ->label('Bill'),

                Tables\Columns\TextColumn::make('vendor.display_name')
                    ->label('Vendor')
                    ->limit(20),

                Tables\Columns\TextColumn::make('due_date')
                    ->label('Due')
                    ->date()
                    ->color(fn (Bill $record): string => $record->isOverdue() ? 'danger' : 'gray'),

                Tables\Columns\TextColumn::make('balance_due')
                    ->label('Amount')
                    ->money('USD')
                    ->alignEnd(),
            ])
            ->paginated(false);
    }
}
