<?php

namespace App\Filament\Resources\PermintaanObats\Pages;

use App\Filament\Resources\PermintaanObats\PermintaanObatResource;
use App\Models\FasilitasKesehatan;
use App\Models\Obat;
use App\Models\StokFaskes;
use App\Models\StokGudang;
use App\Services\NotificationService;
use App\Services\PdfSettingsService;
use Carbon\Carbon;
use Filament\Actions\Action;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Concerns\InteractsWithSchemas;
use Filament\Schemas\Contracts\HasSchemas;
use Filament\Support\Enums\Width;
use Filament\Tables\Columns\Summarizers\Sum;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Spatie\LaravelPdf\Facades\Pdf;
use Stokobat\Boxicons\Boxicon;

class CreatePermintaanObat extends CreateRecord implements HasSchemas, HasTable
{
    use InteractsWithActions;
    use InteractsWithSchemas;
    use InteractsWithTable;

    protected static string $resource = PermintaanObatResource::class;

    protected static ?string $title = 'Buat Permintaan Obat';

    public array $details = [];

    public ?string $targetStatus = 'draft';

    public function table(Table $table): Table
    {
        return $table
            ->records(fn (): Collection => collect($this->details))
            ->paginated(false)
            ->columns([
                TextColumn::make('obat_name')
                    ->limit(30)
                    ->tooltip(function (TextColumn $column): ?string {
                        $state = $column->getState();

                        if (strlen($state) <= $column->getCharacterLimit()) {
                            return null;
                        }

                        // Only render the tooltip if the column contents exceeds the length limit.
                        return $state;
                    })
                    ->label('Obat'),
                TextColumn::make('info_satuan')
                    ->label('Satuan'),
                TextColumn::make('info_kategori')
                    ->label('Kategori'),
                TextColumn::make('jumlah_diminta')
                    ->label('Jumlah Diminta')
                    ->numeric()
                    ->alignEnd()
                    ->summarize(
                        Sum::make('jumlah_diminta')
                            ->label('Total')
                            ->using(fn () => collect($this->details)->sum('jumlah_diminta')),
                    ),
                TextColumn::make('catatan')
                    ->label('Catatan')
                    ->limit(30),
            ])
            ->headerActions([
                Action::make('addItem')
                    ->label('Tambah Item')
                    ->icon('heroicon-m-plus')
                    ->modalHeading('Tambah Item Permintaan')
                    ->modalWidth(Width::Medium)
                    ->form(fn (): array => $this->getItemFormSchema())
                    ->action(fn (array $data) => $this->addItem($data)),
            ])
            ->actions([
                Action::make('editItem')
                    ->label('Edit')
                    ->icon(Boxicon::EditAlt)
                    ->iconButton()
                    ->modalHeading('Edit Item Permintaan')
                    ->form(fn (): array => $this->getItemFormSchema())
                    ->fillForm(fn (array $record): array => $this->getItemFormData($record))
                    ->action(fn (array $data, array $record) => $this->editItem($record, $data)),
                Action::make('deleteItem')
                    ->label('Hapus')
                    ->icon(Boxicon::Trash)
                    ->iconButton()
                    ->color('danger')
                    ->requiresConfirmation()
                    ->modalHeading('Hapus Item Permintaan')
                    ->modalDescription('Apakah Anda yakin ingin menghapus item ini?')
                    ->action(fn (array $record) => $this->deleteItem($record)),
            ]);
    }

    /**
     * @return array<int, Component>
     */
    protected function getItemFormSchema(): array
    {
        return [
            Select::make('obat_id')
                ->label('Obat')
                ->required()
                // ->searchable()
                ->live(onBlur: true)
                ->options(fn (): array => $this->getAvailableObatOptions())
                ->helperText(function (Get $get): string {
                    $obatId = $get('obat_id');

                    if (blank($obatId)) {
                        return 'Pilih obat untuk melihat stok tersedia';
                    }

                    $stock = $this->getStockForSelectedObat((int) $obatId);

                    return 'Stok tersedia: '.number_format($stock, 0, ',', '.').' unit';
                }),
            TextInput::make('jumlah_diminta')
                ->label('Jumlah Diminta')
                ->required()
                ->numeric()
                ->minValue(1)
                ->maxValue(function (Get $get): int {
                    $obatId = $get('obat_id');

                    if (blank($obatId)) {
                        return 999999;
                    }

                    return $this->getStockForSelectedObat((int) $obatId);
                })
                ->default(1),
            Textarea::make('catatan')
                ->nullable(),
        ];
    }

    protected function getAvailableObatOptions(): array
    {
        $user = Auth::user();
        $userFaskes = $user?->fasilitasKesehatan;

        // Super admin melihat semua obat aktif
        if ($user?->hasRole('super_admin')) {
            return Obat::where('status', 'aktif')
                ->orderBy('nama_obat')
                ->pluck('nama_obat', 'id')
                ->toArray();
        }

        // Puskesmas meminta ke Dinas (gudang): hanya obat yang ada stok gudang
        if ($userFaskes?->tipe === 'puskesmas') {
            return Obat::where('status', 'aktif')
                ->whereIn('id', StokGudang::where('jumlah', '>', 0)->select('obat_id'))
                ->orderBy('nama_obat')
                ->pluck('nama_obat', 'id')
                ->toArray();
        }

        // Pustu meminta ke Puskesmas induk: hanya obat yang ada stok di puskesmas induk
        if ($userFaskes?->tipe === 'pustu') {
            return Obat::where('status', 'aktif')
                ->whereIn('id', StokFaskes::where('fasilitas_id', $userFaskes->puskesmas_induk_id)
                    ->where('jumlah', '>', 0)
                    ->select('obat_id'))
                ->orderBy('nama_obat')
                ->pluck('nama_obat', 'id')
                ->toArray();
        }

        // Fallback: admin_dinas, admin_gudang, dll.
        return Obat::where('status', 'aktif')
            ->orderBy('nama_obat')
            ->pluck('nama_obat', 'id')
            ->toArray();
    }

    private function getStockForSelectedObat(int $obatId): int
    {
        $user = Auth::user();
        $userFaskes = $user?->fasilitasKesehatan;

        if ($user?->hasRole('super_admin')) {
            return StokGudang::where('obat_id', $obatId)->value('jumlah') ?? 0;
        }

        if ($userFaskes?->tipe === 'puskesmas') {
            return StokGudang::where('obat_id', $obatId)->value('jumlah') ?? 0;
        }

        if ($userFaskes?->tipe === 'pustu') {
            return StokFaskes::where('fasilitas_id', $userFaskes->puskesmas_induk_id)
                ->where('obat_id', $obatId)
                ->value('jumlah') ?? 0;
        }

        return 0;
    }

    /**
     * @param  array<string, mixed>  $record
     * @return array<string, mixed>
     */
    protected function getItemFormData(array $record): array
    {
        return [
            '_key' => $record['_key'] ?? null,
            'obat_id' => $record['obat_id'] ?? null,
            'jumlah_diminta' => $record['jumlah_diminta'] ?? 1,
            'catatan' => $record['catatan'] ?? null,
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     */
    protected function addItem(array $data): void
    {
        $obat = Obat::find($data['obat_id']);

        $this->details[] = [
            '_key' => count($this->details),
            'obat_id' => (int) $data['obat_id'],
            'obat_name' => $obat?->nama_obat ?? '',
            'info_satuan' => $obat?->satuan ?? '',
            'info_kategori' => $obat?->kategori ?? '',
            'jumlah_diminta' => (int) ($data['jumlah_diminta'] ?? 1),
            'catatan' => $data['catatan'] ?? null,
        ];

        $this->flushCachedTableRecords();
    }

    /**
     * @param  array<string, mixed>  $record
     * @param  array<string, mixed>  $data
     */
    protected function editItem(array $record, array $data): void
    {
        $searchKey = $record['_key'] ?? null;
        $key = $searchKey !== null
            ? array_search($searchKey, array_column($this->details, '_key'))
            : false;

        if ($key === false) {
            return;
        }

        $obat = Obat::find($data['obat_id']);

        $this->details[$key] = [
            '_key' => $searchKey,
            'obat_id' => (int) $data['obat_id'],
            'obat_name' => $obat?->nama_obat ?? '',
            'info_satuan' => $obat?->satuan ?? '',
            'info_kategori' => $obat?->kategori ?? '',
            'jumlah_diminta' => (int) ($data['jumlah_diminta'] ?? 1),
            'catatan' => $data['catatan'] ?? null,
        ];

        $this->flushCachedTableRecords();
    }

    /**
     * @param  array<string, mixed>  $record
     */
    protected function deleteItem(array $record): void
    {
        $searchKey = $record['_key'] ?? null;
        $key = $searchKey !== null
            ? array_search($searchKey, array_column($this->details, '_key'))
            : false;

        if ($key === false) {
            return;
        }

        unset($this->details[$key]);
        $this->details = array_values($this->details);
        $this->flushCachedTableRecords();
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('download_surat')
                ->label('Download Surat Permintaan')
                ->icon('heroicon-o-document-arrow-down')
                ->color('gray')
                ->action(fn () => $this->downloadSurat())
                ->visible(fn () => count($this->details) > 0),
        ];
    }

    protected function getFormActions(): array
    {
        $actions = [
            $this->getCancelFormAction()
                ->label('Batal')
                ->icon(Boxicon::XCircle),
            Action::make('simpan')
                ->label('Simpan Draft')
                ->icon(Boxicon::Save)
                ->color('gray')
                ->action(fn () => $this->prosesSimpan('draft'))
                ->extraAttributes([
                    'class' => 'ml-auto',
                ])
                ->tooltip('Simpan sebagai draft'),
            Action::make('kirim')
                ->label('Kirim Permintaan')
                ->tooltip(function () {
                    $user = Auth::user();

                    if ($user?->hasRole('puskesmas')) {
                        return 'Kirim permintaan ke Dinas';
                    } else {
                        return 'Kirim permintaan ke Puskesmas';
                    }
                })
                ->icon(Boxicon::PaperPlane)
                ->color('primary')
                ->action(function () {
                    if (blank($this->data['surat_permintaan'] ?? null)) {
                        Notification::make()
                            ->title('Surat permintaan wajib diupload')
                            ->body('Silakan upload surat permintaan yang telah ditandatangani sebelum mengirim.')
                            ->warning()
                            ->send();

                        return;
                    }

                    $this->prosesSimpan('menunggu_persetujuan');
                }),
        ];

        return $actions;
    }

    protected function prosesSimpan(string $status): void
    {
        $this->targetStatus = $status;
        $this->create();
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $user = Auth::user();
        $userFaskes = $user?->fasilitasKesehatan;
        $isSuperAdmin = $user?->hasRole('super_admin');

        if (! $isSuperAdmin && filled($userFaskes)) {
            $data['fasilitas_pengirim_id'] = $userFaskes->id;
            $data['status'] = $this->targetStatus;

            if ($userFaskes->tipe === 'pustu') {
                $data['tipe_permintaan'] = 'pustu_ke_puskesmas';
                $data['fasilitas_tujuan_id'] = $userFaskes->puskesmas_induk_id;
            } else {
                $data['tipe_permintaan'] = 'puskesmas_ke_dinas';
                $data['fasilitas_tujuan_id'] = null;
            }
        }

        unset($data['details']);

        return $data;
    }

    protected function afterCreate(): void
    {
        $record = $this->getRecord();

        foreach ($this->details as $detail) {
            $record->details()->create([
                'obat_id' => $detail['obat_id'],
                'jumlah_diminta' => $detail['jumlah_diminta'],
                'catatan' => $detail['catatan'] ?? null,
            ]);
        }

        if ($this->targetStatus === 'menunggu_persetujuan') {
            app(NotificationService::class)->notifyPermintaanApprovers(
                $record,
                'Permintaan Obat Baru',
                "Permintaan {$record->nomor_permintaan} dari {$record->fasilitasPengirim?->nama} menunggu persetujuan.",
                PermintaanObatResource::getUrl('view', ['record' => $record->id]),
                icon: 'heroicon-o-document-arrow-up',
                color: 'warning',
            );
        }
    }

    protected function getRedirectUrl(): string
    {
        return static::getResource()::getUrl('edit', ['record' => $this->getRecord()]);
    }

    protected function getCreatedNotification(): ?Notification
    {
        return null;
    }

    protected function downloadSurat()
    {
        $data = $this->data;

        $permintaan = new \stdClass;
        $permintaan->nomor_permintaan = $data['nomor_permintaan'] ?? '';
        $permintaan->tanggal_permintaan = isset($data['tanggal_permintaan'])
            ? Carbon::parse($data['tanggal_permintaan'])
            : null;
        $permintaan->tipe_permintaan = $data['tipe_permintaan'] ?? '';
        $permintaan->status = 'draft';
        $permintaan->catatan = $data['catatan'] ?? null;
        $permintaan->alasan_penolakan = null;
        $permintaan->fasilitasPengirim = FasilitasKesehatan::find($data['fasilitas_pengirim_id'] ?? null);
        $permintaan->fasilitasTujuan = FasilitasKesehatan::find($data['fasilitas_tujuan_id'] ?? null);
        $permintaan->disetujuiOleh = null;

        $permintaan->details = collect($this->details)->map(fn ($d) => (object) [
            'obat' => Obat::find($d['obat_id']),
            'jumlah_diminta' => $d['jumlah_diminta'],
            'catatan' => $d['catatan'] ?? null,
        ]);

        $faskes = $permintaan->fasilitasPengirim;
        $kop = PdfSettingsService::getKopSurat($faskes?->id);
        $layout = PdfSettingsService::DEFAULT_LAYOUT;

        $filename = 'surat-permintaan-'.str_replace('/', '_', $permintaan->nomor_permintaan).'.pdf';

        $pdfContent = base64_decode(
            Pdf::view('pdf.faktur-permintaan', [
                'permintaan' => $permintaan,
                'kop' => $kop,
                'layout' => $layout,
            ])
                ->format('A4')
                ->base64()
        );

        return response()->streamDownload(function () use ($pdfContent) {
            echo $pdfContent;
        }, $filename, [
            'Content-Type' => 'application/pdf',
        ]);
    }
}
