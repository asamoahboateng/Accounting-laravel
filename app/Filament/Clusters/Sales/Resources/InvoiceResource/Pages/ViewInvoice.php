<?php

namespace App\Filament\Clusters\Sales\Resources\InvoiceResource\Pages;

use App\Filament\Clusters\Sales\Resources\InvoiceResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewInvoice extends ViewRecord
{
    protected static string $resource = InvoiceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
            Actions\Action::make('send')
                ->icon('heroicon-o-paper-airplane')
                ->action(fn () => $this->record->update(['status' => 'sent', 'sent_at' => now()]))
                ->requiresConfirmation()
                ->visible(fn () => $this->record->status === 'draft'),
            Actions\Action::make('print')
                ->icon('heroicon-o-printer')
                ->url(fn () => '#')
                ->openUrlInNewTab(),
        ];
    }
}
