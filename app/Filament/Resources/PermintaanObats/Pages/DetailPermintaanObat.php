<?php

namespace App\Filament\Resources\PermintaanObats\Pages;

use App\Filament\Pages\CetakPdfPage;
use App\Filament\Resources\DistribusiObats\DistribusiObatResource;
use App\Filament\Resources\PermintaanObats\PermintaanObatResource;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;
use Filament\Tables\Columns\Layout\Split;
use Filament\Tables\Columns\Layout\Stack;
use Filament\Tables\Columns\Summarizers\Sum;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Contracts\View\View;

class DetailPermintaanObat extends ViewRecord implements HasTable
{
    use InteractsWithTable;

    protected static string $resource = PermintaanObatResource::class;

    protected string $view = 'filament.pages.detail-permintaan-obat';

    public function mount($record = null): void
    {
        parent::mount($record);

        $this->record->loadMissing([
            'details.obat',
            'distribusi',
            'fasilitasPengirim',
            'fasilitasTujuan',
            'disetujuiOleh',
        ]);
    }

    public function getHeading(): string
    {
        return '';
    }

    public function getSubheading(): ?string
    {
        return 'Created At: '.$this->record->created_at->format('d M Y H:i:s');
    }

    public function getHeader(): ?View
    {
        return view('filament.pages.detail-permintaan-obat-heading', [
            'record' => $this->record,
            'statusLabel' => static::formatStatus($this->record->status),
            'statusBg' => static::statusBg($this->record->status),
            'tipeLabel' => static::formatTipe($this->record->tipe_permintaan),
            'tipeBg' => static::tipeBg($this->record->tipe_permintaan),
            'actions' => $this->getCachedHeaderActions(),
            'actionsAlignment' => $this->getHeaderActionsAlignment(),
        ]);
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('lihat_surat')
                ->label('Lihat Surat')
                ->icon('heroicon-o-document-text')
                ->color('gray')
                ->url(fn () => route('admin.permintaan.download-surat', ['permintaan' => $this->record]))
                ->openUrlInNewTab()
                ->visible(function (): bool {
                    if (blank($this->record?->surat_permintaan)) {
                        return false;
                    }

                    if ($this->record?->status !== 'menunggu_persetujuan') {
                        return false;
                    }

                    $user = auth()->user();

                    return $user->hasRole(['super_admin', 'admin_dinas', 'puskesmas']);
                }),

            Action::make('tinjau')
                ->label('Tinjau Permintaan')
                ->icon('heroicon-o-eye')
                ->color('gray')
                ->url(fn () => PermintaanObatResource::getUrl('edit', ['record' => $this->record->id]))
                ->visible(function (): bool {
                    if ($this->record?->status !== 'menunggu_persetujuan') {
                        return false;
                    }

                    $user = auth()->user();

                    if ($user->hasRole(['super_admin', 'admin_dinas', 'admin_gudang'])) {
                        return $this->record->tipe_permintaan === 'puskesmas_ke_dinas';
                    }

                    if ($user->hasRole('puskesmas') && $this->record->tipe_permintaan === 'pustu_ke_puskesmas') {
                        return $this->record->fasilitas_tujuan_id === $user->fasilitas_kesehatan_id;
                    }

                    return false;
                }),

            Action::make('process')
                ->label('Process Permintaan')
                ->icon('heroicon-o-pencil-square')
                ->color('primary')
                ->url(fn () => PermintaanObatResource::getUrl('edit', ['record' => $this->record->id]))
                ->visible(function (): bool {
                    if ($this->record?->status !== 'sedang_didistribusi') {
                        return false;
                    }

                    $user = auth()->user();

                    if ($user->hasRole(['super_admin', 'admin_dinas', 'admin_gudang'])) {
                        return $this->record->tipe_permintaan === 'puskesmas_ke_dinas';
                    }

                    if ($user->hasRole('puskesmas') && $this->record->tipe_permintaan === 'pustu_ke_puskesmas') {
                        return $this->record->fasilitas_tujuan_id === $user->fasilitas_kesehatan_id;
                    }

                    return false;
                }),

            /**
            Action::make('cetak_faktur')
                ->label('Cetak Faktur')
                ->icon('heroicon-m-printer')
                ->color('gray')
                ->url(fn () => CetakPdfPage::getUrl(['type' => 'faktur-permintaan', 'id' => $this->record->id]))
                ->openUrlInNewTab()
                ->visible(fn () => $this->record?->status !== 'draft'),
             **/
            EditAction::make()
                ->label('Edit Permintaan')
                ->visible(fn () => $this->record?->status === 'draft'),

            Action::make('buat_distribusi')
                ->label('Buat Distribusi')
                ->icon('heroicon-o-truck')
                ->color('success')
                ->url(fn () => DistribusiObatResource::getUrl('create', [
                    'permintaan_id' => $this->record->id,
                ]))
                ->visible(function (): bool {
                    if ($this->record?->status !== 'disetujui') {
                        return false;
                    }

                    if ($this->record->distribusi()->exists()) {
                        return false;
                    }

                    $user = auth()->user();

                    if ($user->hasRole('admin_gudang')) {
                        return true;
                    }

                    if ($user->hasRole('puskesmas') && $this->record->tipe_permintaan === 'pustu_ke_puskesmas') {
                        return $this->record->fasilitas_tujuan_id === $user->fasilitas_kesehatan_id;
                    }

                    return false;
                }),

            Action::make('lihatDistribusi')
                ->label('Lihat Distribusi')
                ->icon('heroicon-o-document-check')
                ->color('gray')
                ->url(fn () => $this->record->distribusi->first()
                    ? DistribusiObatResource::getUrl('view', ['record' => $this->record->distribusi->first()])
                    : '#')
                ->visible(fn (): bool => $this->record?->distribusi()->exists()),
        ];
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(
                \App\Models\DetailPermintaanObat::query()
                    ->where('permintaan_id', $this->record->id)
                    ->with(['obat'])
            )
            ->columns([
                Split::make([
                    Stack::make([
                        TextColumn::make('obat.kode_obat')
                            ->color('gray'),
                        TextColumn::make('obat.nama_obat')
                            ->label('Nama Obat')
                            ->weight('medium'),
                        TextColumn::make('obat.satuan')
                            ->label('Satuan')
                            ->placeholder('-')
                            ->color('gray'),
                        TextColumn::make('obat.kategori')
                            ->label('Kategori')
                            ->placeholder('-')
                            ->color('gray'),
                    ]),
                    TextColumn::make('jumlah_diminta')
                        ->label('Diminta')
                        ->tooltip('Jumlah Diminta')
                        ->numeric()
                        ->alignEnd()
                        ->html()
                        ->grow(false)
                        ->formatStateUsing(fn ($state) => '<span class="text-gray-500">Qty:</span> '.$state)
                        ->summarize([
                            Sum::make()->label('Total'),
                        ]),
                    TextColumn::make('jumlah_disetujui')
                        ->label('Disetujui')
                        ->tooltip('Jumlah Disetujui')
                        ->numeric()
                        ->alignEnd()
                        ->html()
                        ->grow(false)
                        ->placeholder('-')
                        ->formatStateUsing(fn ($state) => '<span class="text-gray-500">Qty:</span> '.$state),
                    TextColumn::make('jumlah_dikirim')
                        ->label('Dikirim')
                        ->tooltip('Jumlah Dikirim')
                        ->numeric()
                        ->alignEnd()
                        ->html()
                        ->grow(false)
                        ->placeholder('-')
                        ->formatStateUsing(fn ($state) => '<span class="text-gray-500">Qty:</span> '.$state),
                    TextColumn::make('jumlah_diterima')
                        ->label('Diterima')
                        ->tooltip('Jumlah Diterima')
                        ->numeric()
                        ->alignEnd()
                        ->html()
                        ->grow(false)
                        ->placeholder('-')
                        ->formatStateUsing(fn ($state) => '<span class="text-gray-500">Qty:</span> '.$state),
                ]),
            ])
            ->stackedOnMobile()
            ->paginated(false);
    }

    public static function formatTipe(string $state): string
    {
        return match ($state) {
            'pustu_ke_puskesmas' => 'Pustu → Puskesmas',
            'puskesmas_ke_dinas' => 'Puskesmas → Dinas',
            default => $state,
        };
    }

    public static function tipeBg(string $state): string
    {
        return match ($state) {
            'pustu_ke_puskesmas' => 'bg-sky-100 text-sky-700 dark:bg-sky-900/30 dark:text-sky-400',
            'puskesmas_ke_dinas' => 'bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400',
            default => 'bg-gray-100 text-gray-700 dark:bg-gray-800 dark:text-gray-300',
        };
    }

    public static function formatStatus(string $state): string
    {
        return match ($state) {
            'draft' => 'Draft',
            'menunggu_persetujuan' => 'Menunggu Persetujuan',
            'disetujui' => 'Disetujui',
            'ditolak' => 'Ditolak',
            'sedang_didistribusi' => 'Sedang Didistribusi',
            'diterima' => 'Diterima',
            'dibatalkan' => 'Dibatalkan',
            default => $state,
        };
    }

    public static function statusBg(string $state): string
    {
        return match ($state) {
            'draft' => 'bg-gray-100 text-gray-700 dark:bg-gray-800 dark:text-gray-300',
            'menunggu_persetujuan' => 'bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400',
            'disetujui' => 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400',
            'ditolak' => 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400',
            'sedang_didistribusi' => 'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400',
            'diterima' => 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400',
            'dibatalkan' => 'bg-gray-100 text-gray-700 dark:bg-gray-800 dark:text-gray-300',
            default => 'bg-gray-100 text-gray-700 dark:bg-gray-800 dark:text-gray-300',
        };
    }

    protected function getViewData(): array
    {
        $record = $this->record;

        return [
            'record' => $record,
            'statusLabel' => static::formatStatus($record->status),
            'statusBg' => static::statusBg($record->status),
            'tipeLabel' => static::formatTipe($record->tipe_permintaan),
            'tipeBg' => static::tipeBg($record->tipe_permintaan),
            'details' => $record->details,
            'distribusi' => $record->distribusi,
            'hasDistribusi' => $record->distribusi()->exists(),
            'showAlasanPenolakan' => filled($record->alasan_penolakan),
            'showCatatan' => filled($record->catatan),
        ];
    }
}
