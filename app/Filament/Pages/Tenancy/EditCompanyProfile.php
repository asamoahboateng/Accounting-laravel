<?php

namespace App\Filament\Pages\Tenancy;

use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Pages\Tenancy\EditTenantProfile;

class EditCompanyProfile extends EditTenantProfile
{
    public static function getLabel(): string
    {
        return 'Company Settings';
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Company Information')
                    ->schema([
                        TextInput::make('name')
                            ->label('Company Name')
                            ->required()
                            ->maxLength(255),
                        TextInput::make('legal_name')
                            ->label('Legal Name')
                            ->maxLength(255),
                        TextInput::make('tax_id')
                            ->label('Tax ID / EIN')
                            ->maxLength(50),
                        TextInput::make('registration_number')
                            ->label('Registration Number')
                            ->maxLength(50),
                        FileUpload::make('logo_path')
                            ->label('Company Logo')
                            ->image()
                            ->directory('company-logos')
                            ->maxSize(2048),
                    ])->columns(2),

                Section::make('Contact Information')
                    ->schema([
                        TextInput::make('email')
                            ->email()
                            ->required()
                            ->maxLength(255),
                        TextInput::make('phone')
                            ->tel()
                            ->maxLength(50),
                        TextInput::make('website')
                            ->url()
                            ->maxLength(255),
                    ])->columns(3),

                Section::make('Address')
                    ->schema([
                        Textarea::make('address_line_1')
                            ->label('Address Line 1')
                            ->rows(2),
                        Textarea::make('address_line_2')
                            ->label('Address Line 2')
                            ->rows(2),
                        TextInput::make('city')
                            ->maxLength(100),
                        TextInput::make('state')
                            ->label('State / Province')
                            ->maxLength(100),
                        TextInput::make('postal_code')
                            ->maxLength(20),
                        Select::make('country_code')
                            ->label('Country')
                            ->options([
                                'US' => 'United States',
                                'CA' => 'Canada',
                                'GB' => 'United Kingdom',
                                'AU' => 'Australia',
                                'DE' => 'Germany',
                                'FR' => 'France',
                                'JP' => 'Japan',
                            ])
                            ->searchable(),
                    ])->columns(3),

                Section::make('Financial Settings')
                    ->schema([
                        Select::make('base_currency_code')
                            ->label('Base Currency')
                            ->options([
                                'USD' => 'USD - US Dollar',
                                'EUR' => 'EUR - Euro',
                                'GBP' => 'GBP - British Pound',
                                'CAD' => 'CAD - Canadian Dollar',
                                'AUD' => 'AUD - Australian Dollar',
                                'JPY' => 'JPY - Japanese Yen',
                            ])
                            ->required()
                            ->disabled()
                            ->helperText('Base currency cannot be changed after company creation.'),
                        Select::make('fiscal_year_start_month')
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
                            ])
                            ->default('01'),
                        Select::make('timezone')
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
                            ->searchable()
                            ->default('America/New_York'),
                    ])->columns(3),

                Section::make('Industry')
                    ->schema([
                        Select::make('industry')
                            ->label('Industry')
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
                                'transportation' => 'Transportation',
                                'agriculture' => 'Agriculture',
                                'other' => 'Other',
                            ])
                            ->searchable(),
                        Select::make('company_type')
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
            ]);
    }
}
