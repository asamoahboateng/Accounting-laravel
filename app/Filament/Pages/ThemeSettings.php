<?php

namespace App\Filament\Pages;

use App\Models\ThemeSetting;
use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Forms\Components\ColorPicker;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;

class ThemeSettings extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-paint-brush';
    protected static ?string $navigationLabel = 'Theme Settings';
    protected static ?string $navigationGroup = 'Settings';
    protected static ?int $navigationSort = 100;
    protected static string $view = 'filament.pages.theme-settings';

    public ?array $data = [];

    public function mount(): void
    {
        $company = Filament::getTenant();
        $setting = $company?->themeSetting;

        if ($setting) {
            $this->form->fill($setting->toArray());
        } else {
            $this->form->fill(ThemeSetting::getDefaults());
        }
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Brand Settings')
                    ->description('Customize your brand name displayed in the sidebar.')
                    ->schema([
                        TextInput::make('brand_name')
                            ->label('Brand Name')
                            ->required()
                            ->maxLength(100)
                            ->placeholder('Your Company Name')
                            ->helperText('This name will appear in the sidebar header.'),
                    ]),

                Section::make('Theme Preset')
                    ->description('Choose a preset theme or customize individual colors below.')
                    ->schema([
                        Select::make('preset')
                            ->label('Quick Preset')
                            ->options(collect(ThemeSetting::getPresets())->mapWithKeys(fn ($preset, $key) => [$key => $preset['name']]))
                            ->placeholder('Select a preset...')
                            ->reactive()
                            ->afterStateUpdated(function ($state, callable $set) {
                                if ($state && isset(ThemeSetting::getPresets()[$state])) {
                                    $preset = ThemeSetting::getPresets()[$state];
                                    $set('sidebar_bg', $preset['sidebar_bg']);
                                    $set('sidebar_text', $preset['sidebar_text']);
                                    $set('sidebar_text_muted', $preset['sidebar_text_muted']);
                                    $set('sidebar_hover_bg', $preset['sidebar_hover_bg']);
                                    $set('sidebar_active_bg', $preset['sidebar_active_bg']);
                                    $set('sidebar_border', $preset['sidebar_border']);
                                    $set('sidebar_brand_bg', $preset['sidebar_brand_bg']);
                                    $set('sidebar_accent_color', $preset['sidebar_accent_color']);
                                }
                            }),
                    ]),

                Section::make('Sidebar Colors')
                    ->description('Customize the sidebar appearance with your own colors.')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                ColorPicker::make('sidebar_bg')
                                    ->label('Background Color')
                                    ->required()
                                    ->helperText('Main sidebar background'),

                                ColorPicker::make('sidebar_brand_bg')
                                    ->label('Brand Area Background')
                                    ->required()
                                    ->helperText('Header/brand section background'),

                                ColorPicker::make('sidebar_text')
                                    ->label('Text Color')
                                    ->required()
                                    ->helperText('Main navigation text'),

                                ColorPicker::make('sidebar_text_muted')
                                    ->label('Muted Text Color')
                                    ->required()
                                    ->helperText('Secondary text and icons'),

                                ColorPicker::make('sidebar_hover_bg')
                                    ->label('Hover Background')
                                    ->required()
                                    ->helperText('Background when hovering items'),

                                ColorPicker::make('sidebar_active_bg')
                                    ->label('Active Item Background')
                                    ->required()
                                    ->helperText('Background for selected item'),

                                ColorPicker::make('sidebar_border')
                                    ->label('Border Color')
                                    ->required()
                                    ->helperText('Divider and border color'),

                                ColorPicker::make('sidebar_accent_color')
                                    ->label('Accent Color')
                                    ->required()
                                    ->helperText('Active indicator and badges'),
                            ]),
                    ]),
            ])
            ->statePath('data');
    }

    public function save(): void
    {
        $company = Filament::getTenant();

        if (!$company) {
            Notification::make()
                ->title('Error')
                ->body('No company selected.')
                ->danger()
                ->send();
            return;
        }

        $data = $this->form->getState();
        unset($data['preset']);

        ThemeSetting::updateOrCreate(
            ['company_id' => $company->id],
            $data
        );

        Notification::make()
            ->title('Theme settings saved')
            ->body('Your theme changes have been saved. Refresh the page to see the changes.')
            ->success()
            ->send();
    }

    public function resetToDefaults(): void
    {
        $this->form->fill(ThemeSetting::getDefaults());

        Notification::make()
            ->title('Defaults restored')
            ->body('Theme settings have been reset to defaults. Click Save to apply.')
            ->info()
            ->send();
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('reset')
                ->label('Reset to Defaults')
                ->color('gray')
                ->icon('heroicon-o-arrow-path')
                ->requiresConfirmation()
                ->action('resetToDefaults'),
        ];
    }
}
