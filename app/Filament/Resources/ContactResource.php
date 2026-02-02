<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ContactResource\Pages;
use App\Models\Contact;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class ContactResource extends Resource
{
    protected static ?string $model = Contact::class;

    protected static ?string $navigationIcon = 'heroicon-o-users';

    protected static ?string $navigationGroup = 'Sales';

    protected static ?int $navigationSort = 10;

    protected static ?string $navigationLabel = 'Customers & Vendors';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Contact Information')
                    ->schema([
                        Forms\Components\Select::make('type')
                            ->options([
                                'customer' => 'Customer',
                                'vendor' => 'Vendor',
                                'both' => 'Customer & Vendor',
                            ])
                            ->required()
                            ->default('customer'),

                        Forms\Components\TextInput::make('display_name')
                            ->label('Display Name')
                            ->required()
                            ->maxLength(255),

                        Forms\Components\TextInput::make('company_name')
                            ->label('Company Name')
                            ->maxLength(255),

                        Forms\Components\TextInput::make('first_name')
                            ->maxLength(100),

                        Forms\Components\TextInput::make('last_name')
                            ->maxLength(100),

                        Forms\Components\TextInput::make('email')
                            ->email()
                            ->maxLength(255),

                        Forms\Components\TextInput::make('phone')
                            ->tel()
                            ->maxLength(50),

                        Forms\Components\TextInput::make('mobile')
                            ->tel()
                            ->maxLength(50),

                        Forms\Components\TextInput::make('website')
                            ->url()
                            ->maxLength(255),
                    ])->columns(3),

                Forms\Components\Section::make('Billing Address')
                    ->schema([
                        Forms\Components\Textarea::make('billing_address_line_1')
                            ->label('Address Line 1')
                            ->rows(2),

                        Forms\Components\Textarea::make('billing_address_line_2')
                            ->label('Address Line 2')
                            ->rows(2),

                        Forms\Components\TextInput::make('billing_city')
                            ->label('City')
                            ->maxLength(100),

                        Forms\Components\TextInput::make('billing_state')
                            ->label('State/Province')
                            ->maxLength(100),

                        Forms\Components\TextInput::make('billing_postal_code')
                            ->label('Postal Code')
                            ->maxLength(20),

                        Forms\Components\Select::make('billing_country')
                            ->label('Country')
                            ->options([
                                'US' => 'United States',
                                'CA' => 'Canada',
                                'GB' => 'United Kingdom',
                                'AU' => 'Australia',
                            ])
                            ->searchable(),
                    ])->columns(3),

                Forms\Components\Section::make('Payment Settings')
                    ->schema([
                        Forms\Components\Select::make('payment_terms')
                            ->options([
                                'due_on_receipt' => 'Due on Receipt',
                                'net_15' => 'Net 15',
                                'net_30' => 'Net 30',
                                'net_60' => 'Net 60',
                            ])
                            ->default('net_30'),

                        Forms\Components\TextInput::make('payment_terms_days')
                            ->label('Payment Terms (Days)')
                            ->numeric()
                            ->default(30),

                        Forms\Components\TextInput::make('credit_limit')
                            ->numeric()
                            ->prefix('$'),

                        Forms\Components\Select::make('currency_code')
                            ->label('Currency')
                            ->options([
                                'USD' => 'USD',
                                'EUR' => 'EUR',
                                'GBP' => 'GBP',
                                'CAD' => 'CAD',
                            ])
                            ->default('USD'),

                        Forms\Components\TextInput::make('tax_id')
                            ->label('Tax ID')
                            ->maxLength(50),

                        Forms\Components\Toggle::make('is_tax_exempt')
                            ->label('Tax Exempt'),

                        Forms\Components\Toggle::make('is_1099_eligible')
                            ->label('1099 Eligible')
                            ->visible(fn (callable $get) => in_array($get('type'), ['vendor', 'both'])),
                    ])->columns(3),

                Forms\Components\Section::make('Notes')
                    ->schema([
                        Forms\Components\Textarea::make('notes')
                            ->rows(3),
                    ]),

                Forms\Components\Toggle::make('is_active')
                    ->label('Active')
                    ->default(true),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('display_name')
                    ->label('Name')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\BadgeColumn::make('type')
                    ->colors([
                        'success' => 'customer',
                        'warning' => 'vendor',
                        'info' => 'both',
                    ]),

                Tables\Columns\TextColumn::make('company_name')
                    ->searchable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('email')
                    ->searchable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('phone')
                    ->toggleable(),

                Tables\Columns\TextColumn::make('current_balance')
                    ->money('USD')
                    ->sortable()
                    ->alignEnd(),

                Tables\Columns\IconColumn::make('is_active')
                    ->boolean(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('type')
                    ->options([
                        'customer' => 'Customer',
                        'vendor' => 'Vendor',
                        'both' => 'Both',
                    ]),
                Tables\Filters\TernaryFilter::make('is_active'),
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
            ->defaultSort('display_name');
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
            'index' => Pages\ListContacts::route('/'),
            'create' => Pages\CreateContact::route('/create'),
            'view' => Pages\ViewContact::route('/{record}'),
            'edit' => Pages\EditContact::route('/{record}/edit'),
        ];
    }
}
