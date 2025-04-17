<?php

namespace App\Imports;

use App\Models\Product;
use Maatwebsite\Excel\Concerns\SkipsEmptyRows;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;
// use Maatwebsite\Excel\Concerns\WithValidation;

class ProductImport implements ToModel, WithHeadingRow, WithMultipleSheets, SkipsEmptyRows
{
    # Mendefinisikan worksheet yang digunakan yaitu hanya bagian awal (0 dalam indeks)
    public function sheets(): array
    {
        return [
            0 => $this
        ];
    }
    /**
     * @param array $row
     *
     * @return \Illuminate\Database\Eloquent\Model|null
     */
    public function model(array $row)
    {
        // dd($row);
        return new Product([
            'category_id' => $row['category_id'],
            'name' => $row['name'],
            'slug' => Product::generateUniqueSlug($row['name']),
            'stock' => $row['stock'],
            'image' => $row['image'],
            'barcode' => $row['barcode'],
            'price' => $row['price'],
            'is_active' => $row['is_active'],
        ]);
    }
}
