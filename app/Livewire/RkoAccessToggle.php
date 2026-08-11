<?php

namespace App\Livewire;

use App\Models\PengaturanLaporan;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class RkoAccessToggle extends Component
{
    public bool $enabled = false;

    public function mount(): void
    {
        $this->enabled = PengaturanLaporan::get('rko', 'akses_dibuka') === '1';
    }

    public function toggle(): void
    {
        $user = Auth::user();
        if (! $user || (! $user->hasRole('super_admin') && ! $user->hasRole('admin_dinas'))) {
            return;
        }

        $this->enabled = ! $this->enabled;

        PengaturanLaporan::updateOrCreate(
            ['grup' => 'rko', 'key' => 'akses_dibuka', 'fasilitas_id' => null],
            ['value' => $this->enabled ? '1' : '0']
        );

        if ($this->enabled) {
            $currentPeriode = PengaturanLaporan::get('rko', 'periode_tahun');
            if (blank($currentPeriode)) {
                PengaturanLaporan::updateOrCreate(
                    ['grup' => 'rko', 'key' => 'periode_tahun', 'fasilitas_id' => null],
                    ['value' => (string) date('Y')]
                );
            }

            PengaturanLaporan::updateOrCreate(
                ['grup' => 'rko', 'key' => 'deadline', 'fasilitas_id' => null],
                ['value' => Carbon::now()->addWeeks(2)->format('Y-m-d')]
            );
        }
    }

    public function render()
    {
        return view('livewire.rko-access-toggle');
    }
}
