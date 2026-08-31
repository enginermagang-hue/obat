<?php

namespace App\Filament\Resources\PrediksiKebutuhans\Pages;

use App\Filament\Resources\PrediksiKebutuhans\PrediksiKebutuhanResource;
use Filament\Resources\Pages\ListRecords;

class ListPrediksiKebutuhans extends ListRecords
{
    protected static string $resource = PrediksiKebutuhanResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }

    public function getSubheading(): ?string
    {
        return 'Prediksi dihasilkan untuk 3 bulan ke depan dari tanggal training (now + 1..3 bulan). '
            .'Jika data pemakaian < 6 bulan, digunakan Moving Average sebagai fallback.';
    }
}
