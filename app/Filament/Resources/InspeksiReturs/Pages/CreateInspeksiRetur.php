<?php

namespace App\Filament\Resources\InspeksiReturs\Pages;

use App\Filament\Resources\InspeksiReturs\InspeksiReturResource;
use App\Models\ReturObat;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Auth;

class CreateInspeksiRetur extends CreateRecord
{
    protected static string $resource = InspeksiReturResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['inspected_by'] = Auth::id();

        return $data;
    }

    protected function afterCreate(): void
    {
        $retur = ReturObat::withCount('details')->find($this->record->retur_id);

        if (! $retur) {
            return;
        }

        $inspectedCount = $retur->details()->whereHas('inspeksi')->count();

        if ($inspectedCount >= $retur->details_count) {
            $retur->update(['status' => 'selesai']);
        }
    }
}
