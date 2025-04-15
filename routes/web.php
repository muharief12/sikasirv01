<?php

use App\Exports\CategoriesExport;
use Illuminate\Support\Facades\Route;
use Maatwebsite\Excel\Facades\Excel;

Route::get('/', function () {
    return redirect('dashboard');
});

Route::get('/download_template', function () {
    return Excel::download(new CategoriesExport, 'template.xlsx');
})->name('download_template');
