<?php

namespace App\Filament\Resources;

use App\Filament\Resources\AccountResource\Pages;
use App\Models\Account;
use App\Models\AccountSubtype;
use App\Models\AccountType;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class AccountResource extends Resource
{
    protected static ?string $model = Account::class;

    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-list';

    protected static ?string $navigationGroup = 'Accounting';

    protected static ?int $navigationSort = 1;

    protected static ?string $navigationLabel = 'Chart of Accounts';

    protected static ?string $modelLabel = 'Account';

    protected static ?string $pluralModelLabel = 'Chart of Accounts';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Account Details')
                    ->schema([
                        Forms\Components\Select::make('account_type_id')
                            ->label('Account Type')
                            ->options(AccountType::pluck('name', 'id'))
                            ->required()
                            ->reactive()
                            ->afterStateUpdated(fn (callable $set) => $set('account_subtype_id', null)),

                        Forms\Components\Select::make('account_subtype_id')
                            ->label('Detail Type')
                            ->options(function (callable $get) {
                                $typeId = $get('account_type_id');
                                if (! $typeId) {
                                    return [];
                                }

                                return AccountSubtype::where('account_type_id', $typeId)
                                    ->pluck('name', 'id');
                            })
                            ->searchable(),

                        Forms\Components\TextInput::make('code')
                            ->label('Account Code')
                            ->required()
                            ->maxLength(20)
                            ->unique(ignoreRecord: true, modifyRuleUsing: function ($rule) {
                                return $rule->where('company_id', filament()->getTenant()->id);
                            }),

                        Forms\Components\TextInput::make('name')
                            ->required()
                            ->maxLength(255),

                        Forms\Components\Textarea::make('description')
                            ->rows(2)
                            ->maxLength(1000),

                        Forms\Components\Select::make('parent_id')
                            ->label('Parent Account')
                            ->relationship('parent', 'name')
                            ->searchable()
                            ->preload()
                            ->helperText('Make this a sub-account of another account'),

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

                Forms\Components\Section::make('Opening Balance')
                    ->schema([
                        Forms\Components\TextInput::make('opening_balance')
                            ->label('Opening Balance')
                            ->numeric()
                            ->default(0)
                            ->prefix('$'),

                        Forms\Components\DatePicker::make('opening_balance_date')
                            ->label('As of Date'),
                    ])->columns(2),

                Forms\Components\Section::make('Options')
                    ->schema([
                        Forms\Components\Toggle::make('is_active')
                            ->label('Active')
                            ->default(true),

                        Forms\Components\Toggle::make('is_bank_account')
                            ->label('Is Bank Account')
                            ->helperText('Enable bank reconciliation features'),

                        Forms\Components\Toggle::make('is_tax_account')
                            ->label('Is Tax Account')
                            ->helperText('Use for tax reporting'),
                    ])->columns(3),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('code')
                    ->label('Code')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->sortable()
                    ->description(fn (Account $record): string => $record->description ?? ''),

                Tables\Columns\TextColumn::make('accountType.name')
                    ->label('Type')
                    ->badge()
                    ->color(fn (Account $record): string => match ($record->accountType?->classification) {
                        'asset' => 'success',
                        'liability' => 'danger',
                        'equity' => 'warning',
                        'revenue' => 'info',
                        'expense' => 'gray',
                        default => 'gray',
                    })
                    ->sortable(),

                Tables\Columns\TextColumn::make('accountSubtype.name')
                    ->label('Detail Type')
                    ->toggleable(),

                Tables\Columns\TextColumn::make('current_balance')
                    ->label('Balance')
                    ->money('USD')
                    ->sortable()
                    ->alignEnd(),

                Tables\Columns\IconColumn::make('is_active')
                    ->label('Active')
                    ->boolean(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('account_type_id')
                    ->label('Account Type')
                    ->relationship('accountType', 'name'),

                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Active'),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('code');
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
            'index' => Pages\ListAccounts::route('/'),
            'create' => Pages\CreateAccount::route('/create'),
            'view' => Pages\ViewAccount::route('/{record}'),
            'edit' => Pages\EditAccount::route('/{record}/edit'),
        ];
    }
}
