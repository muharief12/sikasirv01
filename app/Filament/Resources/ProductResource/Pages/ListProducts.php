<?php

namespace App\Filament\Resources\ProductResource\Pages;

use App\Filament\Resources\ProductResource;
use App\Imports\ProductImport;
use App\Imports\ProductsImport;
use Filament\Actions;
use Filament\Actions\Action;
use Filament\Forms\Components\FileUpload;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Support\Facades\Session;
use Maatwebsite\Excel\Facades\Excel;

class ListProducts extends ListRecords
{
    protected static string $resource = ProductResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('importProduct')
                ->label('Import Products')
                ->color('danger')
                ->icon('heroicon-o-arrow-down-tray')
                ->form([
                    FileUpload::make('attachment')
                        ->label('Upload Product Template'),
                ])->action(function (array $data) {
                    $file = public_path('storage/' . $data['attachment']);

                    try {
                        Excel::import(new ProductImport, $file);
                        Notification::make()
                            ->title('Product data success to imported!')
                            ->success()
                            ->send();
                    } catch (\Exception $e) {
                        // dd($e);
                        Notification::make()
                            ->title('product Data failed to import because !')
                            ->danger()
                            ->send();
                    }
                }),
            Action::make('Download Template')
                ->url(route('download_template'))
                ->color('info'),
            Actions\CreateAction::make(),
        ];
    }

    protected function setFlashMessage()
    {
        $error = Session::get('error');

        if ($error) {
            $this->notify($error, 'danger');
            Session::forget('error');
        }
    }
}
