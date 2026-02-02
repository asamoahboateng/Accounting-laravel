<?php

namespace App\Filament\Clusters\Expenses\Resources;

use App\Filament\Clusters\Expenses;
use App\Filament\Clusters\Expenses\Resources\BillResource\Pages;
use App\Models\Account;
use App\Models\Bill;
use App\Models\Contact;
use App\Models\Product;
use App\Models\TaxRate;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class BillResource extends Resource
{
    protected static ?string $model = Bill::class;

    protected static ?string $navigationIcon = 'heroicon-o-document-minus';

    protected static ?string $cluster = Expenses::class;

    protected static ?int $navigationSort = 10;

    protected static ?string $recordTitleAttribute = 'bill_number';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Group::make()
                    ->schema([
                        Forms\Components\Section::make('Bill Information')
                            ->schema([
                                Forms\Components\Select::make('vendor_id')
                                    ->label('Vendor')
                                    ->options(fn () => Contact::query()
                                        ->where('company_id', filament()->getTenant()?->id)
                                        ->whereIn('type', ['vendor', 'both'])
                                        ->pluck('display_name', 'id'))
                                    ->searchable()
                                    ->required()
                                    ->reactive()
                                    ->afterStateUpdated(function ($state, Forms\Set $set) {
                                        if ($state) {
                                            $vendor = Contact::find($state);
                                            $set('mailing_address', $vendor?->billing_address);
                                            $set('payment_terms', $vendor?->payment_terms);
                                        }
                                    }),

                                Forms\Components\TextInput::make('bill_number')
                                    ->label('Bill #')
                                    ->required()
                                    ->default(fn () => Bill::generateNumber(filament()->getTenant()?->id ?? '')),

                                Forms\Components\TextInput::make('vendor_invoice_number')
                                    ->label('Vendor Invoice #')
                                    ->maxLength(100),

                                Forms\Components\DatePicker::make('bill_date')
                                    ->label('Bill Date')
                                    ->required()
                                    ->default(now()),

                                Forms\Components\DatePicker::make('due_date')
                                    ->label('Due Date')
                                    ->required()
                                    ->default(now()->addDays(30)),

                                Forms\Components\Select::make('payment_terms')
                                    ->options([
                                        'net_15' => 'Net 15',
                                        'net_30' => 'Net 30',
                                        'net_45' => 'Net 45',
                                        'net_60' => 'Net 60',
                                        'due_on_receipt' => 'Due on Receipt',
                                    ]),
                            ])->columns(2),

                        Forms\Components\Section::make('Line Items')
                            ->schema([
                                Forms\Components\Repeater::make('lines')
                                    ->relationship()
                                    ->schema([
                                        Forms\Components\Select::make('product_id')
                                            ->label('Product/Service')
                                            ->options(fn () => Product::query()
                                                ->where('company_id', filament()->getTenant()?->id)
                                                ->where('is_purchased', true)
                                                ->pluck('name', 'id'))
                                            ->searchable()
                                            ->reactive()
                                            ->afterStateUpdated(function ($state, Forms\Set $set) {
                                                if ($state) {
                                                    $product = Product::find($state);
                                                    $set('description', $product?->purchase_description ?? $product?->description);
                                                    $set('unit_cost', $product?->purchase_cost ?? 0);
                                                    $set('account_id', $product?->expense_account_id);
                                                }
                                            })
                                            ->columnSpan(2),

                                        Forms\Components\Textarea::make('description')
                                            ->rows(1)
                                            ->columnSpan(2),

                                        Forms\Components\Select::make('account_id')
                                            ->label('Account')
                                            ->options(fn () => Account::query()
                                                ->where('company_id', filament()->getTenant()?->id)
                                                ->whereHas('accountType', fn ($q) => $q->whereIn('category', ['expense', 'cost_of_goods_sold', 'asset']))
                                                ->pluck('name', 'id'))
                                            ->searchable()
                                            ->required()
                                            ->columnSpan(2),

                                        Forms\Components\TextInput::make('quantity')
                                            ->numeric()
                                            ->default(1)
                                            ->minValue(0.0001)
                                            ->required(),

                                        Forms\Components\TextInput::make('unit_cost')
                                            ->label('Rate')
                                            ->numeric()
                                            ->prefix('$')
                                            ->default(0)
                                            ->required(),

                                        Forms\Components\TextInput::make('amount')
                                            ->numeric()
                                            ->prefix('$')
                                            ->disabled()
                                            ->dehydrated(false),

                                        Forms\Components\Select::make('tax_rate_id')
                                            ->label('Tax')
                                            ->options(fn () => TaxRate::query()
                                                ->where('company_id', filament()->getTenant()?->id)
                                                ->pluck('name', 'id'))
                                            ->searchable(),

                                        Forms\Components\Hidden::make('company_id')
                                            ->default(fn () => filament()->getTenant()?->id),
                                    ])
                                    ->columns(10)
                                    ->defaultItems(1)
                                    ->reorderable()
                                    ->orderColumn('line_number')
                                    ->addActionLabel('Add Line'),
                            ]),
                    ])
                    ->columnSpan(['lg' => 2]),

                Forms\Components\Group::make()
                    ->schema([
                        Forms\Components\Section::make('Totals')
                            ->schema([
                                Forms\Components\Placeholder::make('subtotal_display')
                                    ->label('Subtotal')
                                    ->content(fn (?Bill $record) => '$' . number_format($record?->subtotal ?? 0, 2)),

                                Forms\Components\TextInput::make('discount_amount')
                                    ->label('Discount')
                                    ->numeric()
                                    ->prefix('$')
                                    ->default(0),

                                Forms\Components\Placeholder::make('tax_display')
                                    ->label('Tax')
                                    ->content(fn (?Bill $record) => '$' . number_format($record?->tax_amount ?? 0, 2)),

                                Forms\Components\Placeholder::make('total_display')
                                    ->label('Total')
                                    ->content(fn (?Bill $record) => '$' . number_format($record?->total_amount ?? 0, 2)),
                            ]),

                        Forms\Components\Section::make('Notes')
                            ->schema([
                                Forms\Components\Textarea::make('memo')
                                    ->label('Memo')
                                    ->rows(2),

                                Forms\Components\Textarea::make('internal_notes')
                                    ->label('Internal Notes')
                                    ->rows(2)
                                    ->helperText('Not visible to vendor'),
                            ]),

                        Forms\Components\Section::make('Attachments')
                            ->schema([
                                Forms\Components\FileUpload::make('attachments')
                                    ->multiple()
                                    ->directory('bill-attachments'),
                            ]),
                    ])
                    ->columnSpan(['lg' => 1]),
            ])
            ->columns(3);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('bill_date')
                    ->date()
                    ->sortable(),

                Tables\Columns\TextColumn::make('bill_number')
                    ->label('Bill #')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('vendor.display_name')
                    ->label('Vendor')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('due_date')
                    ->date()
                    ->sortable()
                    ->color(fn (Bill $record) => $record->isOverdue() ? 'danger' : null),

                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'draft' => 'gray',
                        'open' => 'info',
                        'partial' => 'warning',
                        'paid' => 'success',
                        'overdue' => 'danger',
                        'void' => 'gray',
                        default => 'gray',
                    }),

                Tables\Columns\TextColumn::make('total_amount')
                    ->label('Total')
                    ->money('USD')
                    ->sortable(),

                Tables\Columns\TextColumn::make('balance_due')
                    ->label('Balance')
                    ->money('USD')
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'draft' => 'Draft',
                        'open' => 'Open',
                        'partial' => 'Partially Paid',
                        'paid' => 'Paid',
                        'overdue' => 'Overdue',
                        'void' => 'Void',
                    ]),

                Tables\Filters\SelectFilter::make('vendor_id')
                    ->label('Vendor')
                    ->options(fn () => Contact::query()
                        ->where('company_id', filament()->getTenant()?->id)
                        ->whereIn('type', ['vendor', 'both'])
                        ->pluck('display_name', 'id'))
                    ->searchable(),

                Tables\Filters\Filter::make('overdue')
                    ->query(fn (Builder $query): Builder => $query
                        ->where('due_date', '<', now())
                        ->where('balance_due', '>', 0)),

                Tables\Filters\TrashedFilter::make(),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\Action::make('pay')
                    ->label('Record Payment')
                    ->icon('heroicon-o-banknotes')
                    ->color('success')
                    ->visible(fn (Bill $record) => $record->balance_due > 0)
                    ->url(fn (Bill $record) => route('filament.admin.resources.payments-made.create', [
                        'tenant' => filament()->getTenant(),
                        'vendor_id' => $record->vendor_id,
                        'bill_id' => $record->id,
                    ])),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('bill_date', 'desc');
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
            'index' => Pages\ListBills::route('/'),
            'create' => Pages\CreateBill::route('/create'),
            'edit' => Pages\EditBill::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }
}
