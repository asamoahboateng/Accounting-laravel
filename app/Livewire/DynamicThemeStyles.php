<?php

namespace App\Livewire;

use App\Models\ThemeSetting;
use Filament\Facades\Filament;
use Livewire\Component;

class DynamicThemeStyles extends Component
{
    public array $theme = [];

    public function mount(): void
    {
        $company = Filament::getTenant();

        if ($company) {
            $this->theme = $company->getThemeSettings();
        } else {
            $this->theme = ThemeSetting::getDefaults();
        }
    }

    public function render()
    {
        return view('livewire.dynamic-theme-styles');
    }
}
