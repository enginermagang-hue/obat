<?php

namespace App\Filament\Resources\PermintaanObats\Pages;

use App\Filament\Resources\DistribusiObats\DistribusiObatResource;
use App\Filament\Resources\PermintaanObats\PermintaanObatResource;
use App\Models\FasilitasKesehatan;
use App\Models\Obat;
use App\Models\PermintaanObat;
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
use Filament\Resources\Pages\EditRecord;
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

/**
 * Kelas halaman untuk mengedit permintaan obat.
 * Menyediakan fungsionalitas untuk mengelola status permintaan obat dan tindakan terkait seperti kirim, setujui, tolak, dan batal.
 */
class EditPermintaanObat extends EditRecord implements HasSchemas, HasTable
{
    use InteractsWithActions;
    use InteractsWithSchemas;
    use InteractsWithTable;

    protected static string $resource = PermintaanObatResource::class;

    protected ?string $customSavedNotificationTitle = null;

    public ?string $targetStatus = null;

    public array $details = [];

    /**
     * Inisialisasi halaman edit dengan mengatur status target dan pesan notifikasi kustom.
     */
    public function mount(int|string $record): void
    {
        parent::mount($record);

        $this->targetStatus = $this->record->status;

        $this->customSavedNotificationTitle = 'Permintaan berhasil disimpan';

        $this->details = $this->record->details->map(fn ($detail, int $index) => [
            '_key' => $index,
            'id' => $detail->id,
            'obat_id' => $detail->obat_id,
            'obat_name' => $detail->obat?->nama_obat ?? '',
            'info_satuan' => $detail->info_satuan,
            'info_kategori' => $detail->info_kategori,
            'jumlah_diminta' => $detail->jumlah_diminta,
            'jumlah_disetujui' => $detail->jumlah_disetujui,
            'jumlah_diterima' => $detail->jumlah_diterima,
            'catatan' => $detail->catatan,
        ])->toArray();
    }

    public function table(Table $table): Table
    {
        $isAdmin = Auth::user()->hasRole(['admin_dinas', 'admin_gudang', 'super_admin']);
        $isApprover = $this->isApprover();

        $columns = [
            TextColumn::make('obat_name')
                ->label('Obat'),
            TextColumn::make('info_satuan')
                ->label('Satuan'),
            TextColumn::make('info_kategori')
                ->label('Kategori'),
            TextColumn::make('jumlah_diminta')
                ->label('Jumlah Diminta')
                ->numeric()
                ->sortable()
                ->alignEnd()
                ->summarize(
                    Sum::make('total_jumlah_diminta')
                        ->using(fn () => collect($this->details)->sum('jumlah_diminta'))
                ),
            TextColumn::make('catatan')
                ->label('Catatan')
                ->limit(30),
        ];

        // Tambah kolom Jumlah Disetujui untuk approver (admin_dinas, puskesmas yang menyetujui)
        if ($isAdmin || $isApprover) {
            array_splice($columns, 4, 0, [
                TextColumn::make('jumlah_disetujui')
                    ->label('Jml Disetujui')
                    ->numeric()
                    ->sortable()
                    ->alignEnd()
                    ->summarize(
                        Sum::make('total_jumlah_diminta')
                            ->using(fn () => collect($this->details)->sum('jumlah_disetujui'))
                    ),
            ]);
        }

        return $table
            ->records(fn (): Collection => collect($this->details))
            ->paginated(false)
            ->columns($columns)
            ->headerActions([
                Action::make('addItem')
                    ->label('Tambah Item')
                    ->icon('heroicon-m-plus')
                    ->modalHeading('Tambah Item Permintaan')
                    ->modalWidth(Width::Medium)
                    ->form(fn (): array => $this->getItemFormSchema())
                    ->action(fn (array $data) => $this->addItem($data))
                    ->visible(fn (): bool => ! $isApprover),
            ])
            ->actions([
                Action::make('editItem')
                    ->label('Edit')
                    ->icon(Boxicon::EditAlt)
                    ->iconButton()
                    ->modalHeading('Edit Item Permintaan')
                    ->modalWidth(Width::Medium)
                    ->form(fn (): array => $this->getItemFormSchema())
                    ->fillForm(fn (array $record): array => $this->getItemFormData($record))
                    ->action(fn (array $data, array $record) => $this->editItem($record, $data)),
                Action::make('deleteItem')
                    ->label('Hapus')
                    ->icon('heroicon-m-trash')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->modalHeading('Hapus Item Permintaan')
                    ->modalDescription('Apakah Anda yakin ingin menghapus item ini?')
                    ->action(fn (array $record) => $this->deleteItem($record))
                    ->visible(fn (): bool => ! $isApprover),
            ]);
    }

    /**
     * @return array<int, Component>
     */
    protected function getItemFormSchema(): array
    {
        $isApprover = $this->isApprover();

        return [
            Select::make('obat_id')
                ->label('Obat')
                ->required()
                ->searchable()
                ->live(onBlur: true)
                ->options(fn (): array => $this->getAvailableObatOptions())
                ->disabled($isApprover),
            TextInput::make('jumlah_diminta')
                ->label('Jumlah Diminta')
                ->required()
                ->numeric()
                ->minValue(1)
                ->default(1)
                ->disabled($isApprover),
            TextInput::make('jumlah_disetujui')
                ->label('Jumlah Disetujui')
                ->numeric()
                ->minValue(0)
                ->default(0)
                ->visible($isApprover),
            TextInput::make('jumlah_diterima')
                ->label('Jumlah Diterima')
                ->numeric()
                ->minValue(0)
                ->maxValue(function (Get $get): int {
                    $disetujui = (int) ($get('jumlah_disetujui') ?? 0);

                    return max($disetujui, 0);
                })
                ->default(fn (Get $get): int => (int) ($get('jumlah_disetujui') ?? 0))
                ->visible(fn (): bool => $this->isProcessable() && $this->record?->status === 'sedang_didistribusi'),
            Textarea::make('catatan')
                ->nullable()
                ->disabled($isApprover),
        ];
    }

    protected function getAvailableObatOptions(): array
    {
        $user = Auth::user();
        $userFaskes = $user?->fasilitasKesehatan;

        if ($user?->hasRole('super_admin')) {
            return Obat::where('status', 'aktif')
                ->orderBy('nama_obat')
                ->pluck('nama_obat', 'id')
                ->toArray();
        }

        if ($userFaskes?->tipe === 'puskesmas') {
            return Obat::where('status', 'aktif')
                ->whereIn('id', StokGudang::where('jumlah', '>', 0)->select('obat_id'))
                ->orderBy('nama_obat')
                ->pluck('nama_obat', 'id')
                ->toArray();
        }

        if ($userFaskes?->tipe === 'pustu') {
            return Obat::where('status', 'aktif')
                ->whereIn('id', StokFaskes::where('fasilitas_id', $userFaskes->puskesmas_induk_id)
                    ->where('jumlah', '>', 0)
                    ->select('obat_id'))
                ->orderBy('nama_obat')
                ->pluck('nama_obat', 'id')
                ->toArray();
        }

        return Obat::where('status', 'aktif')
            ->orderBy('nama_obat')
            ->pluck('nama_obat', 'id')
            ->toArray();
    }

    private function isApprover(): bool
    {
        $user = Auth::user();

        if ($user->hasRole('admin_dinas') || $user->hasRole('super_admin')) {
            return true;
        }

        if ($user->hasRole('puskesmas') && $this->record?->tipe_permintaan === 'pustu_ke_puskesmas') {
            return $this->record->fasilitas_tujuan_id === $user->fasilitas_kesehatan_id;
        }

        return false;
    }

    private function isProcessable(): bool
    {
        $user = Auth::user();

        if ($user->hasRole(['super_admin', 'admin_dinas', 'admin_gudang'])) {
            return $this->record?->tipe_permintaan === 'puskesmas_ke_dinas';
        }

        if ($user->hasRole('puskesmas') && $this->record?->tipe_permintaan === 'pustu_ke_puskesmas') {
            return $this->record->fasilitas_tujuan_id === $user->fasilitas_kesehatan_id;
        }

        return false;
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
            'jumlah_disetujui' => $record['jumlah_disetujui'] ?? 0,
            'jumlah_diterima' => $record['jumlah_diterima'] ?? null,
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
            'jumlah_disetujui' => (int) ($data['jumlah_disetujui'] ?? 0),
            'jumlah_diterima' => $data['jumlah_diterima'] ?? null,
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

        // Fallback ke nilai existing untuk field yang di-disable (admin)
        $obatId = (int) ($data['obat_id'] ?? $this->details[$key]['obat_id']);
        $obat = Obat::find($obatId);

        $this->details[$key] = [
            '_key' => $searchKey,
            'id' => $this->details[$key]['id'] ?? null,
            'obat_id' => $obatId,
            'obat_name' => $obat?->nama_obat ?? '',
            'info_satuan' => $obat?->satuan ?? '',
            'info_kategori' => $obat?->kategori ?? '',
            'jumlah_diminta' => (int) ($data['jumlah_diminta'] ?? $this->details[$key]['jumlah_diminta']),
            'jumlah_disetujui' => (int) ($data['jumlah_disetujui'] ?? 0),
            'jumlah_diterima' => $data['jumlah_diterima'] ?? ($this->details[$key]['jumlah_diterima'] ?? null),
            'catatan' => $data['catatan'] ?? ($this->details[$key]['catatan'] ?? null),
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

    /**
     * Mengembalikan daftar aksi formulir yang tersedia berdasarkan peran pengguna dan status permintaan.
     *
     * @return array Daftar aksi formulir yang ditampilkan
     */
    public function getFormActions(): array
    {
        $isPpk = Auth::user()->hasRole(['puskesmas', 'pustu']);

        return [
            parent::getCancelFormAction()
                ->label('Batal'),

            parent::getSaveFormAction()
                ->label('Simpan Draft')
                ->icon(Boxicon::Save)
                ->iconPosition('after')
                ->color('gray')
                ->visible(fn () => in_array($this->record?->status, ['draft', 'ditolak']) && $isPpk)
                ->extraAttributes([
                    'class' => 'ml-auto',
                ]),

            Action::make('kirim')
                ->label('Kirim Permintaan')
                ->icon(Boxicon::PaperPlane)
                ->iconPosition('after')
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

                    $this->targetStatus = 'menunggu_persetujuan';
                    $this->customSavedNotificationTitle = 'Permintaan berhasil dikirim';
                    $this->save();
                    $this->notifyPermintaanApprovers(
                        'Permintaan Obat Dikirim',
                        "Permintaan {$this->record->nomor_permintaan} dikirim untuk persetujuan.",
                        'warning',
                    );
                    $this->fillForm();
                })
                ->visible(fn () => in_array($this->record?->status, ['draft', 'ditolak']) && $isPpk),

            Action::make('tolak')
                ->label('Tolak')
                ->color('danger')
                ->icon(Boxicon::XCircle)
                ->requiresConfirmation()
                ->action(function () {
                    $this->targetStatus = 'ditolak';
                    $this->customSavedNotificationTitle = 'Permintaan berhasil ditolak';
                    $this->save();
                    $this->notifyPengirim(
                        'Permintaan Obat Ditolak',
                        "Permintaan {$this->record->nomor_permintaan} ditolak.",
                        'danger',
                    );
                    $this->fillForm();
                })
                ->visible(fn () => $this->record?->status === 'menunggu_persetujuan' && $this->isApprover())
                ->extraAttributes([
                    'class' => 'ml-auto',
                ]),

            Action::make('approve')
                ->label('Setujui')
                ->color('primary')
                ->icon(Boxicon::CheckCircle)
                ->action(function () {
                    foreach (array_keys($this->details) as $key) {
                        if (blank($this->details[$key]['jumlah_disetujui'] ?? null) || (int) ($this->details[$key]['jumlah_disetujui'] ?? 0) <= 0) {
                            $this->details[$key]['jumlah_disetujui'] = (int) ($this->details[$key]['jumlah_diminta'] ?? 0);
                        }
                    }

                    $invalidItems = collect($this->details)->filter(
                        fn ($item) => blank($item['jumlah_disetujui'] ?? null) || (int) ($item['jumlah_disetujui'] ?? 0) <= 0
                    );

                    if ($invalidItems->isNotEmpty()) {
                        Notification::make()
                            ->title('Gagal menyetujui permintaan')
                            ->body('Semua item harus memiliki jumlah disetujui minimal 1.')
                            ->danger()
                            ->send();

                        return;
                    }

                    $this->targetStatus = 'disetujui';
                    $this->customSavedNotificationTitle = 'Permintaan berhasil disetujui';
                    $this->save();
                    $this->notifyPengirim(
                        'Permintaan Obat Disetujui',
                        "Permintaan {$this->record->nomor_permintaan} disetujui.",
                        'success',
                    );
                    $this->fillForm();
                })
                ->visible(fn () => $this->record?->status === 'menunggu_persetujuan' && $this->isApprover()),

            Action::make('batal')
                ->color('gray')
                ->label('Batalkan Permintaan')
                ->action(function () {
                    $this->targetStatus = 'draft';
                    $this->customSavedNotificationTitle = 'Permintaan berhasil dibatalkan';
                    $this->save();
                    $this->notifyPermintaanApprovers(
                        'Permintaan Obat Dibatalkan',
                        "Permintaan {$this->record->nomor_permintaan} dibatalkan oleh pengirim.",
                        'gray',
                    );
                    $this->fillForm();
                })
                ->visible(fn () => $this->record?->status === 'menunggu_persetujuan' && $isPpk)
                ->extraAttributes([
                    'class' => 'ml-auto',
                ]),

            Action::make('process')
                ->label('Konfirmasi Diterima')
                ->color('success')
                ->icon(Boxicon::CheckCircle)
                ->visible(fn () => $this->isProcessable() && $this->record?->status === 'sedang_didistribusi')
                ->action(function () {
                    foreach (array_keys($this->details) as $key) {
                        $diterima = $this->details[$key]['jumlah_diterima'] ?? null;
                        if (blank($diterima) || (int) $diterima < 0) {
                            $this->details[$key]['jumlah_diterima'] = (int) ($this->details[$key]['jumlah_disetujui'] ?? 0);
                        }
                    }

                    $this->targetStatus = 'diterima';
                    $this->customSavedNotificationTitle = 'Permintaan berhasil dikonfirmasi diterima';
                    $this->save();
                    $this->notifyPengirim(
                        'Permintaan Obat Diterima',
                        "Permintaan {$this->record->nomor_permintaan} telah dikonfirmasi diterima.",
                        'success',
                    );
                    $this->fillForm();
                }),
        ];
    }

    /**
     * Mengembalikan judul notifikasi kustom yang ditetapkan selama proses penyimpanan.
     *
     * @return string|null Judul notifikasi yang akan ditampilkan
     */
    protected function getSavedNotificationTitle(): ?string
    {
        return $this->customSavedNotificationTitle ?? parent::getSavedNotificationTitle();
    }

    /**
     * Memodifikasi data formulir sebelum disimpan dengan menetapkan status target jika ada.
     */
    protected function mutateFormDataBeforeSave(array $data): array
    {
        if ($this->targetStatus) {
            $data['status'] = $this->targetStatus;

            if ($this->targetStatus === 'diterima') {
                $data['tanggal_diterima'] = now();
            }
        }

        unset($data['details']);

        return $data;
    }

    protected function afterSave(): void
    {
        $record = $this->getRecord();

        // Hapus detail yang sudah dihapus dari UI
        $existingIds = collect($this->details)->pluck('id')->filter()->toArray();
        $record->details()->whereNotIn('id', $existingIds)->delete();

        // Simpan/update detail
        foreach ($this->details as $detail) {
            $detailData = [
                'obat_id' => $detail['obat_id'],
                'jumlah_diminta' => $detail['jumlah_diminta'],
                'jumlah_disetujui' => $detail['jumlah_disetujui'] ?? 0,
                'jumlah_diterima' => $detail['jumlah_diterima'] ?? null,
                'catatan' => $detail['catatan'] ?? null,
            ];

            if (isset($detail['id'])) {
                $record->details()->where('id', $detail['id'])->update($detailData);
            } else {
                $record->details()->create($detailData);
            }
        }
    }

    private function notifyPermintaanApprovers(string $title, string $body, ?string $color = null): void
    {
        app(NotificationService::class)->notifyPermintaanApprovers(
            $this->record,
            $title,
            $body,
            PermintaanObatResource::getUrl('view', ['record' => $this->record->id]),
            icon: 'heroicon-o-document-arrow-up',
            color: $color,
        );
    }

    private function notifyPengirim(string $title, string $body, ?string $color = null): void
    {
        app(NotificationService::class)->notifyFaskesUsers(
            $this->record->fasilitas_pengirim_id,
            $title,
            $body,
            PermintaanObatResource::getUrl('view', ['record' => $this->record->id]),
            icon: 'heroicon-o-document-arrow-up',
            color: $color,
        );
    }

    protected function downloadSurat()
    {
        $data = $this->data;
        $record = $this->record;

        $permintaan = new \stdClass;
        $permintaan->nomor_permintaan = $data['nomor_permintaan'] ?? $record->nomor_permintaan;
        $permintaan->tanggal_permintaan = isset($data['tanggal_permintaan'])
            ? Carbon::parse($data['tanggal_permintaan'])
            : $record->tanggal_permintaan;
        $permintaan->tipe_permintaan = $data['tipe_permintaan'] ?? $record->tipe_permintaan;
        $permintaan->status = $record->status;
        $permintaan->catatan = $data['catatan'] ?? $record->catatan;
        $permintaan->alasan_penolakan = $record->alasan_penolakan;
        $permintaan->fasilitasPengirim = FasilitasKesehatan::find($data['fasilitas_pengirim_id'] ?? $record->fasilitas_pengirim_id);
        $permintaan->fasilitasTujuan = FasilitasKesehatan::find($data['fasilitas_tujuan_id'] ?? $record->fasilitas_tujuan_id);
        $permintaan->disetujuiOleh = $record->disetujuiOleh;

        $permintaan->details = collect($this->details)->map(fn ($d) => (object) [
            'obat' => Obat::find($d['obat_id']),
            'jumlah_diminta' => $d['jumlah_diminta'],
            'catatan' => $d['catatan'] ?? null,
        ]);

        $faskes = $permintaan->fasilitasPengirim;
        $kop = PdfSettingsService::getKopSurat($faskes?->id);
        $layout = PdfSettingsService::getLayout();
        $googleFontUrl = PdfSettingsService::isGoogleFont($layout['font_family'])
            ? PdfSettingsService::getGoogleFontImportUrl($layout['font_family'])
            : null;

        $filename = 'surat-permintaan-'.str_replace('/', '_', $permintaan->nomor_permintaan).'.pdf';

        $pdfContent = base64_decode(
            Pdf::view('pdf.faktur-permintaan', [
                'permintaan' => $permintaan,
                'kop' => $kop,
                'layout' => $layout,
                'googleFontUrl' => $googleFontUrl,
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

    /**
     * Mengembalikan daftar aksi header (aksi-aksi yang muncul di bagian atas halaman).
     *
     * @return array Daftar aksi header
     */
    protected function getHeaderActions(): array
    {
        return [
            Action::make('download_surat')
                ->label('Download Surat Permintaan')
                ->icon('heroicon-o-document-arrow-down')
                ->color('gray')
                ->action(fn () => $this->downloadSurat())
                ->visible(fn () => count($this->details) > 0),

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

            Action::make('delete')
                ->label('Hapus')
                ->color('danger')
                ->icon('heroicon-o-trash')
                ->requiresConfirmation()
                ->modalHeading('Hapus Permintaan Obat')
                ->modalDescription('Apakah Anda yakin ingin menghapus permintaan ini? Tindakan ini tidak dapat dibatalkan.')
                ->modalSubmitActionLabel('Hapus')
                ->keyBindings(['mod+d'])
                ->action(function (): void {
                    $this->record->delete();
                    $this->redirect(PermintaanObatResource::getUrl('index'));
                })
                ->visible(fn (?PermintaanObat $record): bool => $record !== null),
        ];
    }
}
