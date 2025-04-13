<?php

namespace App\Filament\Resources;

use App\Filament\Resources\OrderResource\Pages;
use App\Filament\Resources\OrderResource\RelationManagers;
use App\Models\Order;
use App\Models\PaymentMethod;
use App\Models\Product;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Components\Group;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Section;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Facades\Auth;
use TomatoPHP\FilamentInvoices\Facades\FilamentInvoices;

class OrderResource extends Resource
{
    protected static ?string $model = Order::class;

    protected static ?string $navigationIcon = 'heroicon-o-shopping-cart';
    protected static ?int $navigationSort = 2;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Group::make()
                    ->schema([
                        Section::make('General Information')
                            ->schema([
                                Forms\Components\TextInput::make('name')
                                    ->required()
                                    ->maxLength(255),
                                Forms\Components\Select::make('gender')
                                    ->options([
                                        'Male' => 'Laki-laki',
                                        'Female' => 'Perempuan',
                                        'Other' => 'Lainnya',
                                    ])
                                    ->required(),
                                Forms\Components\TextInput::make('address')
                                    ->required()
                                    ->maxLength(255),
                            ])
                    ]),
                Group::make()
                    ->schema([
                        Section::make('Additional Information')
                            ->schema([
                                Forms\Components\TextInput::make('email')
                                    ->email()
                                    ->required()
                                    ->maxLength(255),
                                Forms\Components\TextInput::make('phone')
                                    ->tel()
                                    ->required()
                                    ->maxLength(255),
                            ])
                    ]),
                Section::make('Product Items')
                    ->schema([
                        self::getProductItemsRepeater()
                    ]),
                Forms\Components\RichEditor::make('note')
                    ->required()
                    ->columnSpanFull(),
                Forms\Components\Select::make('payment_method_id')
                    ->relationship('paymentMethod', 'name')
                    ->afterStateUpdated(function ($state, Get $get, Set $set) {
                        $paymentMethod = PaymentMethod::find($state);
                        $set('is_cash', $paymentMethod?->is_cash ?? false);

                        if (!$paymentMethod->is_cash) {
                            $set('paid_amount', $get('total_price'));
                            $set('change_amount', 0);
                        }
                    })
                    ->afterStateHydrated(function ($state, Get $get, Set $set) {
                        $paymentMethod = PaymentMethod::find($state);
                        if (!$paymentMethod?->is_cash) {
                            $set('paid_amount', $get('total_price'));
                            $set('change_amount', 0);
                        }

                        $set('is_cash', $paymentMethod?->is_cash ?? false);
                    })
                    ->required(),
                Hidden::make('is_cash')
                    ->dehydrated(),
                Forms\Components\TextInput::make('total_price')
                    ->required()
                    ->readOnly()
                    ->prefix('Rp ')
                    ->numeric(),
                Forms\Components\TextInput::make('paid_amount')
                    ->required()
                    ->reactive()
                    ->readOnly(fn(Get $get) => $get('is_cash') === false)
                    ->prefix('Rp ')
                    ->afterStateUpdated(function ($state, Get $get, Set $set) {
                        $changeAmount = (int) $state - (int) $get('total_price');
                        $set('change_amount', $changeAmount);
                    })
                    ->numeric(),
                Forms\Components\TextInput::make('change_amount')
                    ->required()
                    ->reactive()
                    ->readOnly()
                    ->default(0)
                    ->prefix('Rp ')
                    ->numeric(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->searchable(),
                Tables\Columns\TextColumn::make('email')
                    ->searchable(),
                Tables\Columns\TextColumn::make('gender')
                    ->searchable(),
                Tables\Columns\TextColumn::make('phone')
                    ->searchable(),
                Tables\Columns\TextColumn::make('address')
                    ->searchable(),
                Tables\Columns\TextColumn::make('total_price')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('paid_amount')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('change_amount')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('paymentMethod.name')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('deleted_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
                Tables\Actions\Action::make('Create Invoice')
                    ->label('Create Invoice')
                    ->action(function ($record) {
                        // Ambil order berdasarkan ID record atau parameter lain.
                        $order = Order::find($record->id);  // Ganti dengan cara pengambilan ID yang sesuai

                        if ($order) {
                            // Ambil detail pesanan (order details)
                            $items = $order->orderProducts()->get()->map(function ($detail) {
                                return \TomatoPHP\FilamentInvoices\Services\Contracts\InvoiceItem::make($detail->product->name ?? null)
                                    ->qty($detail->qty ?? 0)
                                    ->price($detail->unit_price ?? 0);
                            });

                            FilamentInvoices::create()
                                ->for(User::find(1))  // Akun penerima
                                ->from(User::find(1))  // Akun pengirim
                                ->dueDate(now()->addDays(7))
                                ->date(now())
                                ->items($items)  // Gunakan items hasil mapping di atas.
                                ->save();

                            // Tambahkan logika tambahan jika diperlukan, seperti redirect atau notifikasi.
                        } else {
                            // Tangani kasus ketika order tidak ditemukan.
                        }
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

    public static function getProductItemsRepeater(): Forms\Components\Repeater
    {
        return Repeater::make('orderProducts')
            ->relationship()
            ->live()
            ->afterStateUpdated(function (Get $get, Set $set) {
                Self::updateTotalPrice($get, $set);
            })
            ->schema([
                Forms\Components\Select::make('product_id')
                    ->relationship('product', 'name')
                    ->options(Product::query()->where('stock', '>', 1)->pluck('name', 'id'))
                    ->afterStateHydrated(function (Get $get, Set $set, $state) {
                        $product = Product::find($state);
                        $set('stock', $product->stock ?? null);
                    })
                    ->afterStateUpdated(function (Get $get, Set $set, $state) {
                        $product = Product::find($state);
                        $set('name', $product->name ?? null);
                        $set('unit_price', $product->price ?? null);
                        $set('stock', $product->stock ?? null);
                        Self::updateTotalPrice($get, $set);
                    })
                    ->live()
                    ->disableOptionsWhenSelectedInSiblingRepeaterItems()
                    ->required(),
                Forms\Components\TextInput::make('unit_price')
                    ->required()
                    ->readOnly()
                    ->prefix('Rp ')
                    ->numeric(),
                Forms\Components\TextInput::make('qty')
                    ->integer()
                    ->minValue(1)
                    ->default(1)
                    // ->maxValue(function (Get $get) {
                    //     $product = Product::find($get('product_id')) ?: 1;
                    //     return $product->stock ?? 1;
                    // })
                    ->afterStateUpdated(function ($state, Get $get, Set $set) {
                        $stock = $get('stock');
                        if ($state > $stock) {
                            $set('qty', $stock);
                            Notification::make()
                                ->title('The ' . Product::find($get('product_id'))->name . ' product stock is limited to ' . $stock . ' Items')
                                ->warning()
                                ->send();
                        }

                        Self::updateTotalPrice($get, $set);
                    })
                    ->required(),
                Forms\Components\TextInput::make('stock')
                    ->required()
                    ->readOnly()
                    ->numeric(),
            ])->columns(4);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListOrders::route('/'),
            'create' => Pages\CreateOrder::route('/create'),
            'edit' => Pages\EditOrder::route('/{record}/edit'),
        ];
    }

    protected static function updateTotalPrice(Get $get, Set $set): void
    {
        $selectedProducts = collect($get('orderProducts'))->filter(fn($item) => !empty($item['product_id']) && !empty($item['qty']));
        $price = Product::find($selectedProducts->pluck('product_id'))->pluck('price', 'id');

        $total = $selectedProducts->reduce(function ($total, $product) use ($price) {
            return $total + ($price[$product['product_id']] * $product['qty']);
        }, 0);

        $set('total_price', $total);
    }
}
