<?php

namespace App\Filament\Pages\Tenancy;

use App\Models\Company;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Pages\Tenancy\RegisterTenant;
use Illuminate\Support\Str;

class RegisterCompany extends RegisterTenant
{
    public static function getLabel(): string
    {
        return 'Register Company';
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                TextInput::make('name')
                    ->label('Company Name')
                    ->required()
                    ->maxLength(255),
                TextInput::make('email')
                    ->label('Company Email')
                    ->email()
                    ->required()
                    ->maxLength(255),
                TextInput::make('phone')
                    ->label('Phone Number')
                    ->tel()
                    ->maxLength(50),
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
                    ->default('USD')
                    ->required(),
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
                    ->default('US')
                    ->required(),
            ]);
    }

    protected function handleRegistration(array $data): Company
    {
        $company = Company::create([
            ...$data,
            'id' => (string) Str::uuid(),
            'timezone' => 'America/New_York',
            'fiscal_year_start_month' => '01',
            'fiscal_year_start_day' => '01',
        ]);

        $company->users()->attach(auth()->user(), [
            'id' => (string) Str::uuid(),
            'role' => 'admin',
            'is_primary' => true,
        ]);

        // Create default chart of accounts
        $this->createDefaultChartOfAccounts($company);

        return $company;
    }

    protected function createDefaultChartOfAccounts(Company $company): void
    {
        $accountTypes = \DB::table('account_types')->get()->keyBy('code');
        $accountSubtypes = \DB::table('account_subtypes')->get()->keyBy('code');

        $defaultAccounts = [
            ['code' => '1000', 'name' => 'Checking Account', 'type' => 'BANK', 'subtype' => 'CHECKING', 'is_bank_account' => true],
            ['code' => '1100', 'name' => 'Accounts Receivable', 'type' => 'AR', 'subtype' => 'AR_TRADE', 'is_system' => true],
            ['code' => '1200', 'name' => 'Inventory Asset', 'type' => 'OCA', 'subtype' => 'INVENTORY'],
            ['code' => '1300', 'name' => 'Undeposited Funds', 'type' => 'OCA', 'subtype' => 'UNDEPOSITED', 'is_system' => true],
            ['code' => '1500', 'name' => 'Furniture & Equipment', 'type' => 'FA', 'subtype' => 'EQUIPMENT'],
            ['code' => '1510', 'name' => 'Accumulated Depreciation', 'type' => 'FA', 'subtype' => 'ACCUM_DEPR'],
            ['code' => '2000', 'name' => 'Accounts Payable', 'type' => 'AP', 'subtype' => 'AP_TRADE', 'is_system' => true],
            ['code' => '2100', 'name' => 'Credit Card', 'type' => 'CC', 'subtype' => 'CREDIT_CARD'],
            ['code' => '2200', 'name' => 'Sales Tax Payable', 'type' => 'OCL', 'subtype' => 'SALES_TAX', 'is_tax_account' => true],
            ['code' => '2300', 'name' => 'Payroll Liabilities', 'type' => 'OCL', 'subtype' => 'PAYROLL_LIAB'],
            ['code' => '3000', 'name' => 'Opening Balance Equity', 'type' => 'EQ', 'subtype' => 'OBE', 'is_system' => true],
            ['code' => '3100', 'name' => 'Retained Earnings', 'type' => 'EQ', 'subtype' => 'RETAINED_EARN', 'is_system' => true],
            ['code' => '3200', 'name' => 'Owner\'s Equity', 'type' => 'EQ', 'subtype' => 'OWNERS_EQUITY'],
            ['code' => '4000', 'name' => 'Sales', 'type' => 'INC', 'subtype' => 'SALES'],
            ['code' => '4100', 'name' => 'Service Revenue', 'type' => 'INC', 'subtype' => 'SERVICE'],
            ['code' => '4200', 'name' => 'Discounts Given', 'type' => 'INC', 'subtype' => 'DISCOUNTS_GIVEN'],
            ['code' => '4900', 'name' => 'Interest Income', 'type' => 'OI', 'subtype' => 'INTEREST_INC'],
            ['code' => '5000', 'name' => 'Cost of Goods Sold', 'type' => 'COGS', 'subtype' => 'SUPPLIES_COGS'],
            ['code' => '6000', 'name' => 'Advertising', 'type' => 'EXP', 'subtype' => 'ADVERTISING'],
            ['code' => '6100', 'name' => 'Bank Service Charges', 'type' => 'EXP', 'subtype' => 'BANK_CHARGES'],
            ['code' => '6200', 'name' => 'Insurance', 'type' => 'EXP', 'subtype' => 'INSURANCE'],
            ['code' => '6300', 'name' => 'Office Supplies', 'type' => 'EXP', 'subtype' => 'OFFICE'],
            ['code' => '6400', 'name' => 'Payroll Expenses', 'type' => 'EXP', 'subtype' => 'PAYROLL'],
            ['code' => '6500', 'name' => 'Professional Fees', 'type' => 'EXP', 'subtype' => 'PROF_FEES'],
            ['code' => '6600', 'name' => 'Rent Expense', 'type' => 'EXP', 'subtype' => 'RENT'],
            ['code' => '6700', 'name' => 'Utilities', 'type' => 'EXP', 'subtype' => 'UTILITIES'],
            ['code' => '6800', 'name' => 'Travel Expense', 'type' => 'EXP', 'subtype' => 'TRAVEL'],
            ['code' => '6900', 'name' => 'Meals & Entertainment', 'type' => 'EXP', 'subtype' => 'MEALS'],
            ['code' => '7000', 'name' => 'Depreciation Expense', 'type' => 'EXP', 'subtype' => 'DEPRECIATION'],
            ['code' => '8000', 'name' => 'Interest Expense', 'type' => 'OE', 'subtype' => 'INTEREST_EXP'],
        ];

        foreach ($defaultAccounts as $account) {
            \App\Models\Account::create([
                'id' => (string) Str::uuid(),
                'company_id' => $company->id,
                'account_type_id' => $accountTypes[$account['type']]->id,
                'account_subtype_id' => $accountSubtypes[$account['subtype']]->id ?? null,
                'code' => $account['code'],
                'name' => $account['name'],
                'currency_code' => $company->base_currency_code,
                'is_active' => true,
                'is_system' => $account['is_system'] ?? false,
                'is_bank_account' => $account['is_bank_account'] ?? false,
                'is_tax_account' => $account['is_tax_account'] ?? false,
            ]);
        }
    }
}
