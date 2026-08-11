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
}
