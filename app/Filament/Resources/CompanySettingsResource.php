<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CompanySettingsResource\Pages;
use App\Models\Company;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class CompanySettingsResource extends Resource
{
    protected static ?string $model = Company::class;

    protected static ?string $navigationIcon = 'heroicon-o-cog-6-tooth';

    protected static ?string $navigationGroup = 'Settings';

    protected static ?int $navigationSort = 100;

    protected static ?string $navigationLabel = 'Company Settings';

    protected static ?string $modelLabel = 'Company Settings';

    protected static ?string $pluralModelLabel = 'Company Settings';

    protected static ?string $slug = 'settings/company';

    public static function canCreate(): bool
    {
        return false;
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Tabs::make('Settings')
                    ->tabs([
                        Forms\Components\Tabs\Tab::make('Company Information')
                            ->icon('heroicon-o-building-office')
                            ->schema([
                                Forms\Components\Section::make('Basic Information')
                                    ->schema([
                                        Forms\Components\TextInput::make('name')
                                            ->label('Company Name')
                                            ->required()
                                            ->maxLength(255),

                                        Forms\Components\TextInput::make('legal_name')
                                            ->label('Legal Name')
                                            ->maxLength(255),

                                        Forms\Components\TextInput::make('tax_id')
                                            ->label('Tax ID / EIN')
                                            ->maxLength(50),

                                        Forms\Components\TextInput::make('registration_number')
                                            ->label('Registration Number')
                                            ->maxLength(50),

                                        Forms\Components\FileUpload::make('logo_path')
                                            ->label('Company Logo')
                                            ->image()
                                            ->directory('company-logos')
                                            ->maxSize(2048),
                                    ])->columns(2),

                                Forms\Components\Section::make('Contact Information')
                                    ->schema([
                                        Forms\Components\TextInput::make('email')
                                            ->email()
                                            ->maxLength(255),

                                        Forms\Components\TextInput::make('phone')
                                            ->tel()
                                            ->maxLength(50),

                                        Forms\Components\TextInput::make('website')
                                            ->url()
                                            ->maxLength(255),
                                    ])->columns(3),

                                Forms\Components\Section::make('Address')
                                    ->schema([
                                        Forms\Components\Textarea::make('address_line_1')
                                            ->label('Address Line 1')
                                            ->rows(2),

                                        Forms\Components\Textarea::make('address_line_2')
                                            ->label('Address Line 2')
                                            ->rows(2),

                                        Forms\Components\TextInput::make('city')
                                            ->maxLength(100),

                                        Forms\Components\TextInput::make('state')
                                            ->label('State / Province')
                                            ->maxLength(100),

                                        Forms\Components\TextInput::make('postal_code')
                                            ->maxLength(20),

                                        Forms\Components\Select::make('country_code')
                                            ->label('Country')
                                            ->options([
                                                'US' => 'United States',
                                                'CA' => 'Canada',
                                                'GB' => 'United Kingdom',
                                                'AU' => 'Australia',
                                                'DE' => 'Germany',
                                                'FR' => 'France',
                                            ])
                                            ->searchable(),
                                    ])->columns(3),
                            ]),

                        Forms\Components\Tabs\Tab::make('Financial Settings')
                            ->icon('heroicon-o-currency-dollar')
                            ->schema([
                                Forms\Components\Section::make('Currency & Fiscal Year')
                                    ->schema([
                                        Forms\Components\Select::make('base_currency_code')
                                            ->label('Base Currency')
                                            ->options([
                                                'USD' => 'USD - US Dollar',
                                                'EUR' => 'EUR - Euro',
                                                'GBP' => 'GBP - British Pound',
                                                'CAD' => 'CAD - Canadian Dollar',
                                                'AUD' => 'AUD - Australian Dollar',
                                            ])
                                            ->disabled()
                                            ->helperText('Contact support to change base currency'),

                                        Forms\Components\Select::make('fiscal_year_start_month')
                                            ->label('Fiscal Year Start Month')
                                            ->options([
                                                '01' => 'January',
                                                '02' => 'February',
                                                '03' => 'March',
                                                '04' => 'April',
                                                '05' => 'May',
                                                '06' => 'June',
                                                '07' => 'July',
                                                '08' => 'August',
                                                '09' => 'September',
                                                '10' => 'October',
                                                '11' => 'November',
                                                '12' => 'December',
                                            ]),

                                        Forms\Components\DatePicker::make('books_closed_through')
                                            ->label('Books Closed Through')
                                            ->helperText('Prevents changes to transactions before this date'),
                                    ])->columns(3),

                                Forms\Components\Section::make('Accounting Preferences')
                                    ->schema([
                                        Forms\Components\Toggle::make('settings.enable_multi_currency')
                                            ->label('Enable Multi-Currency')
                                            ->helperText('Allow transactions in multiple currencies'),

                                        Forms\Components\Toggle::make('settings.enable_inventory')
                                            ->label('Enable Inventory Tracking')
                                            ->helperText('Track inventory quantities and costs'),

                                        Forms\Components\Toggle::make('settings.enable_projects')
                                            ->label('Enable Projects')
                                            ->helperText('Track income and expenses by project'),

                                        Forms\Components\Toggle::make('settings.enable_classes')
                                            ->label('Enable Class Tracking')
                                            ->helperText('Categorize transactions by class'),
                                    ])->columns(2),
                            ]),

                        Forms\Components\Tabs\Tab::make('Localization')
                            ->icon('heroicon-o-globe-alt')
                            ->schema([
                                Forms\Components\Section::make('Regional Settings')
                                    ->schema([
                                        Forms\Components\Select::make('timezone')
                                            ->label('Timezone')
                                            ->options([
                                                'America/New_York' => 'Eastern Time (US & Canada)',
                                                'America/Chicago' => 'Central Time (US & Canada)',
                                                'America/Denver' => 'Mountain Time (US & Canada)',
                                                'America/Los_Angeles' => 'Pacific Time (US & Canada)',
                                                'Europe/London' => 'London',
                                                'Europe/Paris' => 'Paris',
                                                'Asia/Tokyo' => 'Tokyo',
                                                'Australia/Sydney' => 'Sydney',
                                            ])
                                            ->searchable(),

                                        Forms\Components\Select::make('date_format')
                                            ->label('Date Format')
                                            ->options([
                                                'Y-m-d' => '2026-02-02',
                                                'm/d/Y' => '02/02/2026',
                                                'd/m/Y' => '02/02/2026',
                                                'M j, Y' => 'Feb 2, 2026',
                                            ]),

                                        Forms\Components\Select::make('number_format')
                                            ->label('Number Format')
                                            ->options([
                                                '1,234.56' => '1,234.56',
                                                '1.234,56' => '1.234,56',
                                                '1 234,56' => '1 234,56',
                                            ]),
                                    ])->columns(3),
                            ]),

                        Forms\Components\Tabs\Tab::make('Business Info')
                            ->icon('heroicon-o-briefcase')
                            ->schema([
                                Forms\Components\Section::make('Industry & Type')
                                    ->schema([
                                        Forms\Components\Select::make('industry')
                                            ->options([
                                                'technology' => 'Technology',
                                                'retail' => 'Retail',
                                                'manufacturing' => 'Manufacturing',
                                                'healthcare' => 'Healthcare',
                                                'finance' => 'Finance',
                                                'real_estate' => 'Real Estate',
                                                'construction' => 'Construction',
                                                'consulting' => 'Consulting',
                                                'education' => 'Education',
                                                'hospitality' => 'Hospitality',
                                                'other' => 'Other',
                                            ])
                                            ->searchable(),

                                        Forms\Components\Select::make('company_type')
                                            ->label('Company Type')
                                            ->options([
                                                'sole_proprietorship' => 'Sole Proprietorship',
                                                'partnership' => 'Partnership',
                                                'llc' => 'LLC',
                                                's_corp' => 'S Corporation',
                                                'c_corp' => 'C Corporation',
                                                'nonprofit' => 'Nonprofit',
                                            ]),
                                    ])->columns(2),
                            ]),
                    ])
                    ->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name'),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\EditCompanySettings::route('/'),
        ];
    }
}
