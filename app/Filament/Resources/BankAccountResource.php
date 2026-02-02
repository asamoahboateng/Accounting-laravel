<?php

namespace App\Filament\Resources;

use App\Filament\Resources\BankAccountResource\Pages;
use App\Models\Account;
use App\Models\BankAccount;
use App\Models\BankConnection;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class BankAccountResource extends Resource
{
    protected static ?string $model = BankAccount::class;

    protected static ?string $navigationIcon = 'heroicon-o-building-library';

    protected static ?string $navigationGroup = 'Banking';

    protected static ?int $navigationSort = 10;

    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Bank Account Details')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->required()
                            ->maxLength(255),

                        Forms\Components\Select::make('account_type')
                            ->options([
                                'checking' => 'Checking',
                                'savings' => 'Savings',
                                'credit_card' => 'Credit Card',
                                'money_market' => 'Money Market',
                            ])
                            ->required(),

                        Forms\Components\Select::make('account_id')
                            ->label('GL Account')
                            ->options(fn () => Account::query()
                                ->where('company_id', filament()->getTenant()?->id)
                                ->whereHas('accountType', fn ($q) => $q->where('category', 'asset'))
                                ->pluck('name', 'id'))
                            ->searchable()
                            ->required()
                            ->helperText('Link to a General Ledger account'),

                        Forms\Components\Select::make('bank_connection_id')
                            ->label('Bank Connection')
                            ->options(fn () => BankConnection::query()
                                ->where('company_id', filament()->getTenant()?->id)
                                ->pluck('institution_name', 'id'))
                            ->searchable()
                            ->helperText('Optional: Connect to bank feed'),

                        Forms\Components\TextInput::make('account_number_last4')
                            ->label('Last 4 Digits')
                            ->maxLength(4)
                            ->mask('9999'),

                        Forms\Components\TextInput::make('routing_number')
                            ->label('Routing Number')
                            ->maxLength(9),

                        Forms\Components\Select::make('currency_code')
                            ->label('Currency')
                            ->options([
                                'USD' => 'USD - US Dollar',
                                'EUR' => 'EUR - Euro',
                                'GBP' => 'GBP - British Pound',
                                'CAD' => 'CAD - Canadian Dollar',
                            ])
                            ->default('USD')
                            ->required(),
                    ])->columns(2),

                Forms\Components\Section::make('Balances')
                    ->schema([
                        Forms\Components\TextInput::make('current_balance')
                            ->label('Current Balance')
                            ->numeric()
                            ->prefix('$')
                            ->disabled()
                            ->dehydrated(false),

                        Forms\Components\TextInput::make('available_balance')
                            ->label('Available Balance')
                            ->numeric()
                            ->prefix('$')
                            ->disabled()
                            ->dehydrated(false),

                        Forms\Components\TextInput::make('statement_balance')
                            ->label('Statement Balance')
                            ->numeric()
                            ->prefix('$'),

                        Forms\Components\DatePicker::make('statement_date')
                            ->label('Statement Date'),
                    ])->columns(2),

                Forms\Components\Section::make('Settings')
                    ->schema([
                        Forms\Components\Toggle::make('is_active')
                            ->label('Active')
                            ->default(true),

                        Forms\Components\Toggle::make('is_default')
                            ->label('Default Account')
                            ->helperText('Use as default for payments'),
                    ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('account_type')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'checking' => 'success',
                        'savings' => 'info',
                        'credit_card' => 'warning',
                        'money_market' => 'gray',
                        default => 'gray',
                    }),

                Tables\Columns\TextColumn::make('account_number_last4')
                    ->label('Account')
                    ->formatStateUsing(fn (?string $state) => $state ? '****' . $state : '-'),

                Tables\Columns\TextColumn::make('current_balance')
                    ->label('Balance')
                    ->money('USD')
                    ->sortable(),

                Tables\Columns\TextColumn::make('bankConnection.institution_name')
                    ->label('Bank')
                    ->placeholder('Manual'),

                Tables\Columns\TextColumn::make('last_sync_at')
                    ->label('Last Synced')
                    ->dateTime()
                    ->sortable(),

                Tables\Columns\IconColumn::make('is_active')
                    ->label('Active')
                    ->boolean(),

                Tables\Columns\IconColumn::make('is_default')
                    ->label('Default')
                    ->boolean(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('account_type')
                    ->options([
                        'checking' => 'Checking',
                        'savings' => 'Savings',
                        'credit_card' => 'Credit Card',
                        'money_market' => 'Money Market',
                    ]),

                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Active'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\Action::make('reconcile')
                    ->label('Reconcile')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->url(fn (BankAccount $record) => route('banking.reconcile', [
                        'bankAccountId' => $record->id,
                    ])),
                Tables\Actions\Action::make('import')
                    ->label('Import Transactions')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->form([
                        Forms\Components\FileUpload::make('file')
                            ->label('CSV/OFX File')
                            ->required()
                            ->acceptedFileTypes(['text/csv', 'application/x-ofx']),
                    ])
                    ->action(function (BankAccount $record, array $data) {
                        // Import logic would go here
                    }),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
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
            'index' => Pages\ListBankAccounts::route('/'),
            'create' => Pages\CreateBankAccount::route('/create'),
            'edit' => Pages\EditBankAccount::route('/{record}/edit'),
        ];
    }
}
