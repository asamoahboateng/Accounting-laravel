<?php

namespace App\Filament\Clusters\Expenses\Resources\BillResource\Pages;

use App\Filament\Clusters\Expenses\Resources\BillResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListBills extends ListRecords
{
    protected static string $resource = BillResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
