<?php

namespace App\Exports;

use App\Models\Category;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;
use Maatwebsite\Excel\Concerns\WithTitle;

class TemplateExport implements WithMultipleSheets
{
    public function sheets(): array
    {
        return [
            new ProductsExport(),
            new CategoriesExport(),
        ];
    }
}

class ProductsExport implements FromCollection, WithHeadings, WithTitle
{
    public function collection()
    {
        return collect([]);
    }

    public function headings(): array
    {
        return [
            'category_id',
            'name',
            // 'slug',
            'stock',
            'image',
            'barcode',
            'price',
            'is_active',
        ];
    }

    public function title(): string
    {
        return 'Products';
    }
}

class CategoriesExport implements FromCollection, WithHeadings, WithTitle
{
    /**
     * @return \Illuminate\Support\Collection
     */
    public function collection()
    {
        return Category::select('id', 'name')->get();
    }

    public function headings(): array
    {
        return [
            'id',
            'name',
        ];
    }

    public function title(): string
    {
        return 'Categories';
    }
}
