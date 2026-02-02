<?php

namespace App\Filament\Resources\CompanySettingsResource\Pages;

use App\Filament\Resources\CompanySettingsResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditCompanySettings extends EditRecord
{
    protected static string $resource = CompanySettingsResource::class;

    protected static ?string $title = 'Company Settings';

    public function mount(int|string $record = null): void
    {
        $this->record = filament()->getTenant();

        $this->fillForm();
    }

    protected function getHeaderActions(): array
    {
        return [];
    }

    protected function getRedirectUrl(): ?string
    {
        return null;
    }
}
