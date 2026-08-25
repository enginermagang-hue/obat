<?php

namespace App\Filament\Resources\DistribusiObats\Pages;

use App\Filament\Resources\DistribusiObats\DistribusiObatResource;
use App\Filament\Resources\PenerimaanStoks\PenerimaanStokResource;
use App\Filament\Resources\PermintaanObats\PermintaanObatResource;
use App\Models\DetailDistribusiObat;
use App\Models\DetailPermintaanObat;
use App\Services\NotificationService;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use Filament\Tables\Columns\Layout\Split;
use Filament\Tables\Columns\Layout\Stack;
use Filament\Tables\Columns\Summarizers\Sum;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Contracts\View\View;
use Stokobat\Boxicons\Boxicon;

class DetailDistribusi extends ViewRecord implements HasTable
{
    use InteractsWithTable;

    protected static string $resource = DistribusiObatResource::class;

    protected string $view = 'filament.pages.detail-distribusi';

    public function mount($record = null): void
    {
        parent::mount($record);

        $this->record->loadMissing([
            'details.obat',
            'details.batch',
            'fasilitasPengirim',
            'fasilitasPenerima',
            'pengirim',
            'penerima',
            'permintaan',
            'penerimaanStok',
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
        return view('filament.pages.detail-distribusi-heading', [
            'record' => $this->record,
            'statusLabel' => static::formatStatus($this->record->status),
            'statusBg' => static::statusBg($this->record->status),
            'tipeLabel' => static::formatTipe($this->record->tipe_distribusi),
            'tipeBg' => static::tipeBg($this->record->tipe_distribusi),
            'actions' => $this->getCachedHeaderActions(),
            'actionsAlignment' => $this->getHeaderActionsAlignment(),
        ]);
    }

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make()
                ->label('Edit Distribusi')
                ->visible(fn (): bool => $this->record?->status === 'draft'),
            Action::make('kirim')
                ->label('Kirim')
                ->icon('heroicon-o-paper-airplane')
                ->color('primary')
                ->requiresConfirmation()
                ->modalHeading('Kirim Distribusi Obat')
                ->modalDescription('Apakah Anda yakin ingin mengirim distribusi ini? Status akan berubah menjadi Dalam Pengiriman.')
                ->visible(fn (): bool => $this->record?->status === 'draft')
                ->action(function (): void {
                    $this->record->update([
                        'status' => 'dalam_pengiriman',
                        'tanggal_kirim' => now(),
                    ]);

                    if ($this->record->permintaan) {
                        $this->record->permintaan->update(['status' => 'sedang_didistribusi']);
                        $this->updateJumlahDikirim($this->record);
                    }

                    Notification::make()
                        ->title('Distribusi berhasil dikirim')
                        ->success()
                        ->send();

                    $this->notifyPenerima(
                        'Distribusi Obat Dikirim',
                        "Distribusi {$this->record->nomor_surat_jalan} sedang dalam pengiriman ke ".($this->record->fasilitasPenerima?->nama ?? 'faskes tujuan').'.',
                    );

                    $this->refreshFormData(['status']);
                }),
            Action::make('buatPenerimaan')
                ->label('Buat Penerimaan')
                ->icon('heroicon-o-inbox-arrow-down')
                ->color('success')
                ->url(fn (): string => PenerimaanStokResource::getUrl('create', [
                    'distribusi_id' => $this->record->id,
                    'tipe' => 'distribusi',
                ]))
                ->visible(fn (): bool => $this->record?->status === 'dalam_pengiriman'
                    && auth()->user()?->hasAnyRole(['puskesmas', 'pustu'])
                    && $this->record->fasilitas_penerima_id === auth()->user()->fasilitas_kesehatan_id),
            Action::make('tolak')
                ->label('Tolak Distribusi')
                ->icon('heroicon-o-x-circle')
                ->color('danger')
                ->requiresConfirmation()
                ->modalHeading('Tolak Distribusi Obat')
                ->modalDescription('Apakah Anda yakin ingin menolak distribusi ini? Stok tidak akan diterima.')
                ->form([
                    Textarea::make('catatan_penolakan')
                        ->label('Alasan Penolakan')
                        ->required()
                        ->maxLength(500),
                ])
                ->visible(fn (): bool => $this->record?->status === 'dalam_pengiriman'
                    && auth()->user()?->hasAnyRole(['puskesmas', 'pustu', 'super_admin'])
                    && ($this->record->fasilitas_penerima_id === auth()->user()->fasilitas_kesehatan_id
                        || auth()->user()?->hasRole('super_admin')))
                ->action(function (array $data): void {
                    $this->record->update([
                        'status' => 'ditolak',
                        'tanggal_ditolak' => now(),
                        'catatan' => trim(($this->record->catatan ?? '')."\n[Ditolak]: ".$data['catatan_penolakan']),
                    ]);

                    if ($this->record->permintaan) {
                        $this->record->permintaan->update(['status' => 'disetujui']);
                    }

                    DetailPermintaanObat::where('permintaan_id', $this->record->permintaan_id)
                        ->update(['jumlah_dikirim' => 0]);

                    Notification::make()
                        ->title('Distribusi ditolak')
                        ->warning()
                        ->send();

                    $this->notifyPengirim(
                        'Distribusi Obat Ditolak',
                        "Distribusi {$this->record->nomor_surat_jalan} ditolak oleh penerima.",
                    );

                    $this->refreshFormData(['status']);
                }),
            Action::make('lihatPenerimaan')
                ->label('Lihat Penerimaan')
                ->icon('heroicon-o-document-check')
                ->color('gray')
                ->url(fn (): string => $this->record->penerimaanStok
                    ? PenerimaanStokResource::getUrl('view', ['record' => $this->record->penerimaanStok])
                    : '#')
                ->visible(fn (): bool => $this->record?->penerimaan_stok_id !== null),
            Action::make('cetak_faktur')
                ->label('Cetak Faktur')
                ->icon('heroicon-o-printer')
                ->color('primary')
                ->url(fn (): string => route('admin.distribusi.cetak-faktur', ['distribusi' => $this->record->id]))
                ->openUrlInNewTab()
                ->visible(fn (): bool => $this->record?->status !== 'draft'),
        ];
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(
                DetailDistribusiObat::query()
                    ->where('distribusi_id', $this->record->id)
                    ->with(['obat', 'batch'])
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
                    ]),
                    TextColumn::make('batch.batch_number')
                        ->label('Batch')
                        ->tooltip('No. Batch')
                        ->icon(Boxicon::Qr)
                        ->placeholder('-')
                        ->extraAttributes([
                            'class' => 'no-wrap',
                        ]),
                    TextColumn::make('batch.tanggal_expired')
                        ->icon(Boxicon::CalendarAlt)
                        ->label('Expired')
                        ->tooltip('Tanggal Kadaluarsa')
                        ->date('d/m/Y')
                        ->placeholder('-'),
                    TextColumn::make('jumlah')
                        ->label('Jumlah')
                        ->tooltip('Jumlah')
                        ->numeric()
                        ->alignEnd()
                        ->html()
                        ->grow(false)
                        ->formatStateUsing(fn ($state) => '<span class="text-gray-500">Qty:</span> '.$state)
                        ->summarize([
                            Sum::make()->label('Total'),
                        ]),
                ]),
            ])
            ->stackedOnMobile()
            ->paginated(false);
    }

    private function updateJumlahDikirim($record): void
    {
        $totals = DetailDistribusiObat::whereHas('distribusi', fn ($q) => $q->where('permintaan_id', $record->permintaan_id))
            ->selectRaw('obat_id, SUM(jumlah) as total_jumlah')
            ->groupBy('obat_id')
            ->pluck('total_jumlah', 'obat_id');

        foreach ($totals as $obatId => $totalJumlah) {
            DetailPermintaanObat::where('permintaan_id', $record->permintaan_id)
                ->where('obat_id', $obatId)
                ->update(['jumlah_dikirim' => $totalJumlah]);
        }
    }

    private function notifyPenerima(string $title, string $body): void
    {
        app(NotificationService::class)->notifyFaskesUsers(
            $this->record->fasilitas_penerima_id,
            $title,
            $body,
            DistribusiObatResource::getUrl('view', ['record' => $this->record->id]),
            icon: 'heroicon-o-truck',
            color: 'info',
        );
    }

    private function notifyPengirim(string $title, string $body): void
    {
        app(NotificationService::class)->notifyRole(
            'admin_gudang',
            $title,
            $body,
            DistribusiObatResource::getUrl('view', ['record' => $this->record->id]),
            icon: 'heroicon-o-truck',
            color: 'danger',
        );

        app(NotificationService::class)->notifyFaskesUsers(
            $this->record->fasilitas_pengirim_id,
            $title,
            $body,
            DistribusiObatResource::getUrl('view', ['record' => $this->record->id]),
            icon: 'heroicon-o-truck',
            color: 'danger',
        );
    }

    public static function formatStatus(string $state): string
    {
        return match ($state) {
            'draft' => 'Draft',
            'dalam_pengiriman' => 'Dalam Pengiriman',
            'diterima' => 'Diterima',
            'ditolak' => 'Ditolak',
            default => $state,
        };
    }

    public static function statusBg(string $state): string
    {
        return match ($state) {
            'draft' => 'bg-gray-100 text-gray-700 dark:bg-gray-800 dark:text-gray-300',
            'dalam_pengiriman' => 'bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400',
            'diterima' => 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400',
            'ditolak' => 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400',
            default => 'bg-gray-100 text-gray-700 dark:bg-gray-800 dark:text-gray-300',
        };
    }

    public static function formatTipe(string $state): string
    {
        return match ($state) {
            'puskesmas_ke_pustu' => 'Puskesmas → Pustu',
            'dinas_ke_puskesmas' => 'Dinas → Puskesmas',
            default => $state,
        };
    }

    public static function tipeBg(string $state): string
    {
        return match ($state) {
            'puskesmas_ke_pustu' => 'bg-sky-100 text-sky-700 dark:bg-sky-900/30 dark:text-sky-400',
            'dinas_ke_puskesmas' => 'bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400',
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
            'tipeLabel' => static::formatTipe($record->tipe_distribusi),
            'tipeBg' => static::tipeBg($record->tipe_distribusi),
            'details' => $record->details,
            'permintaanUrl' => filled($record->permintaan_id)
                ? PermintaanObatResource::getUrl('view', ['record' => $record->permintaan_id])
                : null,
        ];
    }
}
