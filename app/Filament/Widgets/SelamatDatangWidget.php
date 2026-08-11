<?php

namespace App\Filament\Widgets;

use App\Models\FasilitasKesehatan;
use App\Models\Obat;
use Filament\Widgets\Widget;
use Illuminate\Support\Facades\Auth;

class SelamatDatangWidget extends Widget
{
    protected string $view = 'filament.widgets.selamat-datang';

    protected int|string|array $columnSpan = 'full';

    public string $salutation = '';

    public string $userName = '';

    public ?string $faskesNama = null;

    public ?string $faskesTipe = null;

    public int $totalObat = 0;

    public int $totalFaskes = 0;

    public function mount(): void
    {
        $user = Auth::user();
        $this->userName = $user?->name ?? 'Pengguna';
        $this->salutation = $this->getSalutation();

        $this->totalFaskes = FasilitasKesehatan::query()->count();
        $this->totalObat = Obat::count();

        if ($user?->fasilitasKesehatan) {
            $this->faskesNama = $user->fasilitasKesehatan->nama;
            $this->faskesTipe = $user->fasilitasKesehatan->tipe;
        }
    }

    public function getSalutation(): string
    {
        $hour = now()->hour;

        return match (true) {
            $hour >= 5 && $hour < 12 => 'Selamat Pagi',
            $hour >= 12 && $hour < 15 => 'Selamat Siang',
            $hour >= 15 && $hour < 18 => 'Selamat Sore',
            default => 'Selamat Malam',
        };
    }
}
