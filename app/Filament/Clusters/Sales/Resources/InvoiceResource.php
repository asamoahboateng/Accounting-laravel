<?php

namespace App\Filament\Clusters\Sales\Resources;

use App\Filament\Clusters\Sales;
use App\Filament\Clusters\Sales\Resources\InvoiceResource\Pages;
use App\Models\Account;
use App\Models\Contact;
use App\Models\Invoice;
use App\Models\Product;
use App\Models\TaxRate;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class InvoiceResource extends Resource
{
    protected static ?string $model = Invoice::class;

    protected static ?string $navigationIcon = 'heroicon-o-document-text';

    protected static ?string $cluster = Sales::class;

    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Invoice Details')
                    ->schema([
                        Forms\Components\Select::make('customer_id')
                            ->label('Customer')
                            ->relationship('customer', 'display_name', fn (Builder $query) => $query->whereIn('type', ['customer', 'both']))
                            ->searchable()
                            ->preload()
                            ->required()
                            ->createOptionForm([
                                Forms\Components\TextInput::make('display_name')
                                    ->required()
                                    ->maxLength(255),
                                Forms\Components\TextInput::make('email')
                                    ->email(),
                                Forms\Components\Hidden::make('type')
                                    ->default('customer'),
                            ]),

                        Forms\Components\TextInput::make('invoice_number')
                            ->label('Invoice #')
                            ->default(fn () => 'INV-' . str_pad(Invoice::count() + 1, 5, '0', STR_PAD_LEFT))
                            ->required()
                            ->unique(ignoreRecord: true, modifyRuleUsing: function ($rule) {
                                return $rule->where('company_id', filament()->getTenant()->id);
                            }),

                        Forms\Components\DatePicker::make('invoice_date')
                            ->label('Invoice Date')
                            ->default(now())
                            ->required(),

                        Forms\Components\DatePicker::make('due_date')
                            ->label('Due Date')
                            ->default(now()->addDays(30))
                            ->required(),

                        Forms\Components\Select::make('payment_terms')
                            ->options([
                                'due_on_receipt' => 'Due on Receipt',
                                'net_15' => 'Net 15',
                                'net_30' => 'Net 30',
                                'net_60' => 'Net 60',
                            ])
                            ->default('net_30'),

                        Forms\Components\Select::make('status')
                            ->options([
                                'draft' => 'Draft',
                                'sent' => 'Sent',
                                'viewed' => 'Viewed',
                                'partial' => 'Partially Paid',
                                'paid' => 'Paid',
                                'overdue' => 'Overdue',
                            ])
                            ->default('draft')
                            ->required(),
                    ])->columns(3),

                Forms\Components\Section::make('Billing & Shipping')
                    ->schema([
                        Forms\Components\Textarea::make('billing_address')
                            ->label('Billing Address')
                            ->rows(3),

                        Forms\Components\Textarea::make('shipping_address')
                            ->label('Shipping Address')
                            ->rows(3),
                    ])->columns(2)->collapsed(),

                Forms\Components\Section::make('Line Items')
                    ->schema([
                        Forms\Components\Repeater::make('lines')
                            ->relationship()
                            ->schema([
                                Forms\Components\Select::make('product_id')
                                    ->label('Product/Service')
                                    ->options(Product::where('is_active', true)->pluck('name', 'id'))
                                    ->searchable()
                                    ->reactive()
                                    ->afterStateUpdated(function ($state, callable $set) {
                                        if ($state) {
                                            $product = Product::find($state);
                                            if ($product) {
                                                $set('description', $product->sales_description ?? $product->description);
                                                $set('unit_price', $product->sales_price);
                                                $set('account_id', $product->income_account_id);
                                            }
                                        }
                                    }),

                                Forms\Components\Textarea::make('description')
                                    ->label('Description')
                                    ->rows(1)
                                    ->columnSpan(2),

                                Forms\Components\TextInput::make('quantity')
                                    ->numeric()
                                    ->default(1)
                                    ->required()
                                    ->reactive()
                                    ->afterStateUpdated(fn ($state, callable $set, callable $get) => $set('amount', ($state ?? 0) * ($get('unit_price') ?? 0))),

                                Forms\Components\TextInput::make('unit_price')
                                    ->label('Rate')
                                    ->numeric()
                                    ->prefix('$')
                                    ->required()
                                    ->reactive()
                                    ->afterStateUpdated(fn ($state, callable $set, callable $get) => $set('amount', ($state ?? 0) * ($get('quantity') ?? 0))),

                                Forms\Components\TextInput::make('amount')
                                    ->numeric()
                                    ->prefix('$')
                                    ->disabled()
                                    ->dehydrated(),

                                Forms\Components\Select::make('tax_rate_id')
                                    ->label('Tax')
                                    ->options(TaxRate::where('is_active', true)->pluck('name', 'id'))
                                    ->searchable(),

                                Forms\Components\Select::make('account_id')
                                    ->label('Account')
                                    ->options(Account::whereHas('accountType', fn ($q) => $q->where('classification', 'revenue'))->pluck('name', 'id'))
                                    ->searchable()
                                    ->required(),
                            ])
                            ->columns(8)
                            ->defaultItems(1)
                            ->reorderable()
                            ->cloneable()
                            ->itemLabel(fn (array $state): ?string => $state['description'] ?? 'New Line'),
                    ]),

                Forms\Components\Section::make('Totals')
                    ->schema([
                        Forms\Components\Select::make('discount_type')
                            ->options([
                                'percentage' => 'Percentage',
                                'fixed' => 'Fixed Amount',
                            ])
                            ->default(null)
                            ->reactive(),

                        Forms\Components\TextInput::make('discount_type_value')
                            ->label('Discount')
                            ->numeric()
                            ->prefix(fn (callable $get) => $get('discount_type') === 'percentage' ? '' : '$')
                            ->suffix(fn (callable $get) => $get('discount_type') === 'percentage' ? '%' : '')
                            ->default(0),

                        Forms\Components\TextInput::make('shipping_amount')
                            ->label('Shipping')
                            ->numeric()
                            ->prefix('$')
                            ->default(0),
                    ])->columns(3),

                Forms\Components\Section::make('Notes')
                    ->schema([
                        Forms\Components\Textarea::make('message')
                            ->label('Message to Customer')
                            ->rows(2),

                        Forms\Components\Textarea::make('internal_notes')
                            ->label('Internal Notes')
                            ->rows(2),
                    ])->columns(2)->collapsed(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('invoice_date')
                    ->date()
                    ->sortable(),

                Tables\Columns\TextColumn::make('invoice_number')
                    ->label('Invoice #')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('customer.display_name')
                    ->label('Customer')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('due_date')
                    ->date()
                    ->sortable()
                    ->color(fn (Invoice $record): string => $record->isOverdue() ? 'danger' : 'gray'),

                Tables\Columns\TextColumn::make('total_amount')
                    ->money('USD')
                    ->sortable()
                    ->alignEnd(),

                Tables\Columns\TextColumn::make('balance_due')
                    ->money('USD')
                    ->sortable()
                    ->alignEnd()
                    ->color(fn (Invoice $record): string => $record->balance_due > 0 ? 'warning' : 'success'),

                Tables\Columns\BadgeColumn::make('status')
                    ->colors([
                        'gray' => 'draft',
                        'info' => 'sent',
                        'primary' => 'viewed',
                        'warning' => 'partial',
                        'success' => 'paid',
                        'danger' => 'overdue',
                    ]),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'draft' => 'Draft',
                        'sent' => 'Sent',
                        'viewed' => 'Viewed',
                        'partial' => 'Partially Paid',
                        'paid' => 'Paid',
                        'overdue' => 'Overdue',
                    ]),
                Tables\Filters\SelectFilter::make('customer')
                    ->relationship('customer', 'display_name'),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\Action::make('send')
                    ->icon('heroicon-o-paper-airplane')
                    ->action(fn (Invoice $record) => $record->update(['status' => 'sent', 'sent_at' => now()]))
                    ->requiresConfirmation()
                    ->visible(fn (Invoice $record) => $record->status === 'draft'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('invoice_date', 'desc');
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
            'index' => Pages\ListInvoices::route('/'),
            'create' => Pages\CreateInvoice::route('/create'),
            'view' => Pages\ViewInvoice::route('/{record}'),
            'edit' => Pages\EditInvoice::route('/{record}/edit'),
        ];
    }
}
