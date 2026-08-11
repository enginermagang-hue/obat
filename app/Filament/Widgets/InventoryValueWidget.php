<?php

namespace App\Filament\Widgets;

use App\Models\BatchStok;
use Filament\Widgets\Widget;

class InventoryValueWidget extends Widget
{
    protected string $view = 'filament.widgets.inventory-value';

    protected int|string|array $columnSpan = 'full';

    public string $totalValue = 'Rp 0';

    public int $totalBatch = 0;

    public int $totalObat = 0;

    public function mount(): void
    {
        $result = BatchStok::query()
            ->where('status', 'tersedia')
            ->selectRaw('SUM(jumlah * harga_beli) as total_value, COUNT(*) as total_batch, COUNT(DISTINCT obat_id) as total_obat')
            ->first();

        $this->totalValue = 'Rp '.number_format((float) ($result->total_value ?? 0), 0, ',', '.');
        $this->totalBatch = (int) ($result->total_batch ?? 0);
        $this->totalObat = (int) ($result->total_obat ?? 0);
    }
}
