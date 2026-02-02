<?php

namespace App\Filament\Resources\BankAccountResource\Pages;

use App\Filament\Resources\BankAccountResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListBankAccounts extends ListRecords
{
    protected static string $resource = BankAccountResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
            Actions\Action::make('connect_bank')
                ->label('Connect Bank')
                ->icon('heroicon-o-link')
                ->color('info')
                ->action(function () {
                    // Plaid/bank connection flow would be triggered here
                }),
        ];
    }
}
