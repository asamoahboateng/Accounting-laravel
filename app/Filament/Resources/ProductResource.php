<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ProductResource\Pages;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\UnitOfMeasure;
use App\Models\Account;
use App\Models\TaxRate;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class ProductResource extends Resource
{
    protected static ?string $model = Product::class;

    protected static ?string $navigationIcon = 'heroicon-o-cube';

    protected static ?string $navigationGroup = 'Sales';

    protected static ?int $navigationSort = 30;

    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Tabs::make('Product')
                    ->tabs([
                        Forms\Components\Tabs\Tab::make('Basic Information')
                            ->icon('heroicon-o-information-circle')
                            ->schema([
                                Forms\Components\Section::make()
                                    ->schema([
                                        Forms\Components\Select::make('type')
                                            ->options([
                                                'inventory' => 'Inventory Item',
                                                'non_inventory' => 'Non-Inventory Item',
                                                'service' => 'Service',
                                                'bundle' => 'Bundle/Assembly',
                                            ])
                                            ->required()
                                            ->reactive()
                                            ->columnSpan(1),

                                        Forms\Components\TextInput::make('sku')
                                            ->label('SKU')
                                            ->maxLength(50),

                                        Forms\Components\TextInput::make('name')
                                            ->required()
                                            ->maxLength(255)
                                            ->columnSpan(2),

                                        Forms\Components\Select::make('category_id')
                                            ->label('Category')
                                            ->options(fn () => ProductCategory::query()
                                                ->where('company_id', filament()->getTenant()?->id)
                                                ->pluck('name', 'id'))
                                            ->searchable(),

                                        Forms\Components\Select::make('unit_of_measure_id')
                                            ->label('Unit of Measure')
                                            ->options(fn () => UnitOfMeasure::query()
                                                ->where('company_id', filament()->getTenant()?->id)
                                                ->pluck('name', 'id'))
                                            ->searchable(),
                                    ])->columns(2),

                                Forms\Components\Section::make('Description')
                                    ->schema([
                                        Forms\Components\Textarea::make('description')
                                            ->label('General Description')
                                            ->rows(2),

                                        Forms\Components\Textarea::make('sales_description')
                                            ->label('Sales Description')
                                            ->helperText('Appears on invoices and estimates')
                                            ->rows(2),

                                        Forms\Components\Textarea::make('purchase_description')
                                            ->label('Purchase Description')
                                            ->helperText('Appears on purchase orders and bills')
                                            ->rows(2),
                                    ]),
                            ]),

                        Forms\Components\Tabs\Tab::make('Pricing')
                            ->icon('heroicon-o-currency-dollar')
                            ->schema([
                                Forms\Components\Section::make('Sales')
                                    ->schema([
                                        Forms\Components\TextInput::make('sales_price')
                                            ->label('Sales Price')
                                            ->numeric()
                                            ->prefix('$')
                                            ->default(0),

                                        Forms\Components\Select::make('income_account_id')
                                            ->label('Income Account')
                                            ->options(fn () => Account::query()
                                                ->where('company_id', filament()->getTenant()?->id)
                                                ->whereHas('accountType', fn ($q) => $q->where('category', 'income'))
                                                ->pluck('name', 'id'))
                                            ->searchable(),

                                        Forms\Components\Select::make('sales_tax_rate_id')
                                            ->label('Sales Tax')
                                            ->options(fn () => TaxRate::query()
                                                ->where('company_id', filament()->getTenant()?->id)
                                                ->pluck('name', 'id'))
                                            ->searchable(),

                                        Forms\Components\Toggle::make('is_taxable')
                                            ->label('Taxable')
                                            ->default(true),
                                    ])->columns(2),

                                Forms\Components\Section::make('Purchasing')
                                    ->schema([
                                        Forms\Components\TextInput::make('purchase_cost')
                                            ->label('Purchase Cost')
                                            ->numeric()
                                            ->prefix('$')
                                            ->default(0),

                                        Forms\Components\Select::make('expense_account_id')
                                            ->label('Expense Account')
                                            ->options(fn () => Account::query()
                                                ->where('company_id', filament()->getTenant()?->id)
                                                ->whereHas('accountType', fn ($q) => $q->whereIn('category', ['expense', 'cost_of_goods_sold']))
                                                ->pluck('name', 'id'))
                                            ->searchable(),

                                        Forms\Components\Select::make('purchase_tax_rate_id')
                                            ->label('Purchase Tax')
                                            ->options(fn () => TaxRate::query()
                                                ->where('company_id', filament()->getTenant()?->id)
                                                ->pluck('name', 'id'))
                                            ->searchable(),
                                    ])->columns(2),
                            ]),

                        Forms\Components\Tabs\Tab::make('Inventory')
                            ->icon('heroicon-o-archive-box')
                            ->visible(fn (Forms\Get $get) => $get('type') === 'inventory')
                            ->schema([
                                Forms\Components\Section::make('Inventory Settings')
                                    ->schema([
                                        Forms\Components\Toggle::make('track_inventory')
                                            ->label('Track Inventory')
                                            ->default(true),

                                        Forms\Components\Select::make('costing_method')
                                            ->label('Costing Method')
                                            ->options([
                                                'moving_average' => 'Moving Average Cost (MAC)',
                                                'fifo' => 'First In, First Out (FIFO)',
                                                'lifo' => 'Last In, First Out (LIFO)',
                                                'specific' => 'Specific Identification',
                                            ])
                                            ->default('moving_average'),
                                    ])->columns(2),

                                Forms\Components\Section::make('Quantities')
                                    ->schema([
                                        Forms\Components\TextInput::make('quantity_on_hand')
                                            ->label('Quantity on Hand')
                                            ->numeric()
                                            ->disabled(),

                                        Forms\Components\TextInput::make('quantity_on_order')
                                            ->label('Quantity on Order')
                                            ->numeric()
                                            ->disabled(),

                                        Forms\Components\TextInput::make('reorder_point')
                                            ->label('Reorder Point')
                                            ->numeric()
                                            ->helperText('Alert when quantity falls below this'),

                                        Forms\Components\TextInput::make('reorder_quantity')
                                            ->label('Reorder Quantity')
                                            ->numeric(),
                                    ])->columns(2),

                                Forms\Components\Section::make('Costing')
                                    ->schema([
                                        Forms\Components\TextInput::make('average_cost')
                                            ->label('Average Cost')
                                            ->numeric()
                                            ->prefix('$')
                                            ->disabled(),

                                        Forms\Components\TextInput::make('last_cost')
                                            ->label('Last Cost')
                                            ->numeric()
                                            ->prefix('$')
                                            ->disabled(),

                                        Forms\Components\Select::make('asset_account_id')
                                            ->label('Inventory Asset Account')
                                            ->options(fn () => Account::query()
                                                ->where('company_id', filament()->getTenant()?->id)
                                                ->whereHas('accountType', fn ($q) => $q->where('category', 'asset'))
                                                ->pluck('name', 'id'))
                                            ->searchable(),

                                        Forms\Components\Select::make('cogs_account_id')
                                            ->label('COGS Account')
                                            ->options(fn () => Account::query()
                                                ->where('company_id', filament()->getTenant()?->id)
                                                ->whereHas('accountType', fn ($q) => $q->where('category', 'cost_of_goods_sold'))
                                                ->pluck('name', 'id'))
                                            ->searchable(),
                                    ])->columns(2),
                            ]),

                        Forms\Components\Tabs\Tab::make('Settings')
                            ->icon('heroicon-o-cog-6-tooth')
                            ->schema([
                                Forms\Components\Section::make()
                                    ->schema([
                                        Forms\Components\Toggle::make('is_active')
                                            ->label('Active')
                                            ->default(true),

                                        Forms\Components\Toggle::make('is_sold')
                                            ->label('I sell this product/service')
                                            ->default(true),

                                        Forms\Components\Toggle::make('is_purchased')
                                            ->label('I purchase this product/service')
                                            ->default(true),
                                    ]),

                                Forms\Components\Section::make('Physical Attributes')
                                    ->schema([
                                        Forms\Components\TextInput::make('barcode')
                                            ->maxLength(50),

                                        Forms\Components\TextInput::make('weight')
                                            ->numeric(),

                                        Forms\Components\Select::make('weight_unit')
                                            ->options([
                                                'oz' => 'Ounces',
                                                'lb' => 'Pounds',
                                                'g' => 'Grams',
                                                'kg' => 'Kilograms',
                                            ]),
                                    ])->columns(3),

                                Forms\Components\FileUpload::make('image_path')
                                    ->label('Product Image')
                                    ->image()
                                    ->directory('product-images'),
                            ]),
                    ])
                    ->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\ImageColumn::make('image_path')
                    ->label('')
                    ->circular()
                    ->defaultImageUrl(fn () => asset('images/placeholder.png')),

                Tables\Columns\TextColumn::make('sku')
                    ->label('SKU')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('type')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'inventory' => 'success',
                        'non_inventory' => 'gray',
                        'service' => 'info',
                        'bundle' => 'warning',
                        default => 'gray',
                    }),

                Tables\Columns\TextColumn::make('sales_price')
                    ->label('Price')
                    ->money('USD')
                    ->sortable(),

                Tables\Columns\TextColumn::make('quantity_on_hand')
                    ->label('Qty on Hand')
                    ->numeric()
                    ->sortable()
                    ->visible(fn () => true),

                Tables\Columns\IconColumn::make('is_active')
                    ->label('Active')
                    ->boolean(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('type')
                    ->options([
                        'inventory' => 'Inventory',
                        'non_inventory' => 'Non-Inventory',
                        'service' => 'Service',
                        'bundle' => 'Bundle',
                    ]),

                Tables\Filters\SelectFilter::make('category_id')
                    ->label('Category')
                    ->options(fn () => ProductCategory::query()
                        ->where('company_id', filament()->getTenant()?->id)
                        ->pluck('name', 'id')),

                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Active'),

                Tables\Filters\TrashedFilter::make(),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\Action::make('adjust_quantity')
                    ->label('Adjust Qty')
                    ->icon('heroicon-o-adjustments-horizontal')
                    ->visible(fn (Product $record) => $record->type === 'inventory')
                    ->form([
                        Forms\Components\TextInput::make('adjustment')
                            ->label('Quantity Adjustment')
                            ->numeric()
                            ->required()
                            ->helperText('Enter positive to increase, negative to decrease'),

                        Forms\Components\Textarea::make('reason')
                            ->label('Reason')
                            ->required(),
                    ])
                    ->action(function (Product $record, array $data) {
                        $record->adjustQuantity($data['adjustment'], $data['reason']);
                    }),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    Tables\Actions\ForceDeleteBulkAction::make(),
                    Tables\Actions\RestoreBulkAction::make(),
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
            'index' => Pages\ListProducts::route('/'),
            'create' => Pages\CreateProduct::route('/create'),
            'edit' => Pages\EditProduct::route('/{record}/edit'),
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
