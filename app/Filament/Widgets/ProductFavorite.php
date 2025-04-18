<?php

namespace App\Filament\Widgets;

use App\Models\Product;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;

class ProductFavorite extends BaseWidget
{
    protected static ?int $sort = 4;
    public function table(Table $table): Table
    {
        $productQuery = Product::query()
            ->withCount('orderProducts')
            ->orderByDesc('order_products_count')
            ->take(5);
        return $table
            ->query($productQuery)
            ->columns([
                Tables\Columns\ImageColumn::make('image'),
                Tables\Columns\TextColumn::make('name')
                    ->searchable(),
                Tables\Columns\TextColumn::make('category.name')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('order_products_count')
                    ->label('Product Sale')
                    ->sortable()

            ]);
    }
}
