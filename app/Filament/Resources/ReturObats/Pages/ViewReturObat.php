<?php

namespace App\Filament\Resources\ReturObats\Pages;

use App\Filament\Resources\ReturObats\ReturObatResource;
use App\Models\DetailReturObat;
use App\Models\User;
use App\Services\StokService;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use Filament\Tables\Columns\Layout\Split;
use Filament\Tables\Columns\Layout\Stack;
use Filament\Tables\Columns\Summarizers\Sum;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ViewColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;
use Stokobat\Boxicons\Boxicon;

class ViewReturObat extends ViewRecord implements HasTable
{
    use InteractsWithTable;

    protected static string $resource = ReturObatResource::class;

    protected string $view = 'filament.pages.detail-retur-obat';

    public function mount(int|string $record): void
    {
        parent::mount($record);

        $this->record->loadMissing([
            'details.obat',
            'details.batch',
            'fasilitasPengirim',
            'fasilitasPenerima',
            'supplier',
            'distribusi',
            'penerimaan',
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
        return view('filament.pages.detail-retur-obat-heading', [
            'record' => $this->record,
            'statusLabel' => static::formatStatus($this->record->status),
            'statusBg' => static::statusBg($this->record->status),
            'tipeLabel' => static::formatTipe($this->record->tipe_retur),
            'tipeBg' => static::tipeBg($this->record->tipe_retur),
            'actions' => $this->getCachedHeaderActions(),
            'actionsAlignment' => $this->getHeaderActionsAlignment(),
        ]);
    }

    protected function getHeaderActions(): array
    {
        $user = auth()->user();
        $record = $this->record;

        return [
            EditAction::make()
                ->label('Edit Retur')
                ->visible(fn (): bool => $record?->status === 'draft'
                    && ($record->fasilitas_pengirim_id === $user->fasilitas_kesehatan_id || $user->hasRole('super_admin'))),

            Action::make('cetak-faktur')
                ->label('Cetak Faktur')
                ->color('gray')
                ->icon(Boxicon::Printer)
                ->url(fn (): string => route('admin.retur.cetak-faktur', ['retur' => $this->record->id]))
                ->openUrlInNewTab(),

            // Submit: draft → menunggu_approval (hanya untuk faskes user)
            Action::make('ajukan')
                ->label('Ajukan Retur')
                ->icon('heroicon-o-paper-airplane')
                ->color('primary')
                ->requiresConfirmation()
                ->modalHeading('Ajukan Retur Obat')
                ->modalDescription('Retur akan dikirim untuk persetujuan admin dinas.')
                ->visible(fn (): bool => $record?->status === 'draft'
                    && filled($user->fasilitasKesehatan)
                    && $record->fasilitas_pengirim_id === $user->fasilitas_kesehatan_id)
                ->action(function (): void {
                    $this->record->update([
                        'status' => 'menunggu_approval',
                    ]);

                    $this->sendNotificationToAdminDinas(
                        'Retur Obat Menunggu Persetujuan',
                        "Retur {$this->record->nomor_retur} dari ".($this->record->fasilitasPengirim?->nama ?? 'Gudang').' menunggu persetujuan Anda.'
                    );

                    Notification::make()
                        ->title('Retur Diajukan')
                        ->body('Retur berhasil diajukan untuk persetujuan admin dinas.')
                        ->success()
                        ->send();

                    $this->refreshFormData(['status']);
                }),

            // Approve: menunggu_approval → disetujui
            Action::make('setujui')
                ->label('Setujui')
                ->icon('heroicon-o-check-circle')
                ->color('success')
                ->requiresConfirmation()
                ->modalHeading('Setujui Retur Obat')
                ->modalDescription('Apakah Anda yakin ingin menyetujui retur ini?')
                ->visible(fn (): bool => $record?->status === 'menunggu_approval' && $user->hasRole('admin_dinas'))
                ->action(function () use ($user): void {
                    $this->record->update([
                        'status' => 'disetujui',
                        'tanggal_disetujui' => now(),
                        'disetujui_oleh' => $user->id,
                    ]);

                    $this->sendNotificationToPengirim(
                        'Retur Obat Disetujui',
                        "Retur {$this->record->nomor_retur} telah disetujui oleh admin dinas."
                    );

                    Notification::make()
                        ->title('Retur Disetujui')
                        ->success()
                        ->send();

                    $this->refreshFormData(['status']);
                }),

            // Reject: menunggu_approval → ditolak
            Action::make('tolak')
                ->label('Tolak')
                ->icon('heroicon-o-x-circle')
                ->color('danger')
                ->form([
                    Textarea::make('alasan_penolakan')
                        ->label('Alasan Penolakan')
                        ->required()
                        ->rows(3)
                        ->placeholder('Masukkan alasan penolakan...'),
                ])
                ->requiresConfirmation()
                ->modalHeading('Tolak Retur Obat')
                ->modalDescription('Apakah Anda yakin ingin menolak retur ini?')
                ->visible(fn (): bool => $record?->status === 'menunggu_approval' && $user->hasRole('admin_dinas'))
                ->action(function (array $data): void {
                    $this->record->update([
                        'status' => 'ditolak',
                        'tanggal_ditolak' => now(),
                        'catatan' => ($this->record->catatan ? $this->record->catatan."\n\n" : '').'Alasan penolakan: '.$data['alasan_penolakan'],
                    ]);

                    $this->sendNotificationToPengirim(
                        'Retur Obat Ditolak',
                        "Retur {$this->record->nomor_retur} ditolak. Alasan: {$data['alasan_penolakan']}"
                    );

                    Notification::make()
                        ->title('Retur Ditolak')
                        ->danger()
                        ->send();

                    $this->refreshFormData(['status']);
                }),

            // Kirim: disetujui → dalam_pengiriman
            Action::make('kirim')
                ->label('Kirim Retur')
                ->icon('heroicon-o-truck')
                ->color('primary')
                ->requiresConfirmation()
                ->modalHeading('Kirim Retur Obat')
                ->modalDescription('Konfirmasi bahwa obat returan sedang dikirim.')
                ->visible(fn (): bool => $record?->status === 'disetujui'
                    && ($user->hasRole('admin_gudang') || $user->hasRole('super_admin')))
                ->action(function (): void {
                    $this->record->update([
                        'status' => 'dalam_pengiriman',
                        'tanggal_dikirim' => now(),
                    ]);

                    $this->sendNotificationToPenerima(
                        'Retur Obat Dalam Pengiriman',
                        "Retur {$this->record->nomor_retur} sedang dalam pengiriman."
                    );

                    Notification::make()
                        ->title('Retur Dikirim')
                        ->success()
                        ->send();

                    $this->refreshFormData(['status']);
                }),

            // Terima: dalam_pengiriman → diterima + proses stok
            Action::make('terima')
                ->label('Terima Retur')
                ->icon('heroicon-o-check-circle')
                ->color('success')
                ->requiresConfirmation()
                ->modalHeading('Terima Retur Obat')
                ->modalDescription('Konfirmasi penerimaan obat returan. Stok akan disesuaikan otomatis.')
                ->visible(fn (): bool => $record?->status === 'dalam_pengiriman'
                    && $this->isPenerima($user))
                ->action(function (): void {
                    DB::transaction(function (): void {
                        $this->record->update([
                            'status' => 'diterima',
                            'tanggal_diterima' => now(),
                        ]);

                        $stokService = app(StokService::class);
                        $stokService->prosesReturDiterima($this->record);
                    });

                    $this->sendNotificationToPengirim(
                        'Retur Obat Diterima',
                        "Retur {$this->record->nomor_retur} telah diterima dan stok telah disesuaikan."
                    );

                    $this->sendNotificationToAdminGudang(
                        'Retur Obat Selesai Diproses',
                        "Retur {$this->record->nomor_retur} telah diterima dan stok telah disesuaikan."
                    );

                    Notification::make()
                        ->title('Retur Diterima')
                        ->body('Stok telah disesuaikan otomatis.')
                        ->success()
                        ->send();

                    $this->refreshFormData(['status']);
                }),

            // Tandai Selesai: diterima → selesai
            Action::make('tandai_selesai')
                ->label('Tandai Selesai')
                ->icon('heroicon-o-flag')
                ->color('success')
                ->requiresConfirmation()
                ->modalHeading('Tandai Retur Selesai')
                ->modalDescription('Tandai retur ini sebagai selesai.')
                ->visible(fn (): bool => $record?->status === 'diterima'
                    && ($record->fasilitas_pengirim_id === $user->fasilitas_kesehatan_id || $user->hasRole('super_admin')))
                ->action(function (): void {
                    $this->record->update([
                        'status' => 'selesai',
                    ]);

                    Notification::make()
                        ->title('Retur Selesai')
                        ->success()
                        ->send();

                    $this->refreshFormData(['status']);
                }),
        ];
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(
                DetailReturObat::query()
                    ->where('retur_id', $this->record->id)
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
                    TextColumn::make('jumlah_retur')
                        ->label('Jumlah')
                        ->tooltip('Jumlah Retur')
                        ->numeric()
                        ->alignEnd()
                        ->html()
                        ->grow(false)
                        ->formatStateUsing(fn ($state) => '<span class="text-gray-500">Qty:</span> '.$state)
                        ->summarize([
                            Sum::make()->label('Total'),
                        ]),
                    ViewColumn::make('bukti_foto')
                        ->label('Bukti')
                        ->view('filament.partials.retur-bukti-foto'),
                ]),
            ])
            ->stackedOnMobile()
            ->paginated(false);
    }

    /**
     * Cek apakah user adalah penerima retur.
     */
    private function isPenerima(User $user): bool
    {
        $record = $this->record;

        if ($user->hasRole('super_admin')) {
            return true;
        }

        if ($user->hasRole('admin_gudang') && blank($user->fasilitas_kesehatan_id)) {
            return $record->tipe_retur === 'puskesmas_ke_gudang';
        }

        return $record->fasilitas_penerima_id === $user->fasilitas_kesehatan_id;
    }

    private function sendNotificationToAdminDinas(string $title, string $body): void
    {
        $adminDinasUsers = Role::findByName('admin_dinas')->users;

        foreach ($adminDinasUsers as $admin) {
            Notification::make()
                ->title($title)
                ->body($body)
                ->sendToDatabase($admin);
        }
    }

    private function sendNotificationToAdminGudang(string $title, string $body): void
    {
        $adminGudangUsers = Role::findByName('admin_gudang')->users;

        foreach ($adminGudangUsers as $admin) {
            Notification::make()
                ->title($title)
                ->body($body)
                ->sendToDatabase($admin);
        }
    }

    private function sendNotificationToPengirim(string $title, string $body): void
    {
        if ($this->record->fasilitas_pengirim_id) {
            $pengirimUsers = User::where('fasilitas_kesehatan_id', $this->record->fasilitas_pengirim_id)->get();

            foreach ($pengirimUsers as $pengirim) {
                Notification::make()
                    ->title($title)
                    ->body($body)
                    ->sendToDatabase($pengirim);
            }
        }
    }

    private function sendNotificationToPenerima(string $title, string $body): void
    {
        if ($this->record->fasilitas_penerima_id) {
            $penerimaUsers = User::where('fasilitas_kesehatan_id', $this->record->fasilitas_penerima_id)->get();

            foreach ($penerimaUsers as $penerima) {
                Notification::make()
                    ->title($title)
                    ->body($body)
                    ->sendToDatabase($penerima);
            }
        }
    }

    public static function formatTipe(string $state): string
    {
        return match ($state) {
            'puskesmas_ke_gudang' => 'Puskesmas → Gudang',
            'pustu_ke_puskesmas' => 'Pustu → Puskesmas',
            'gudang_ke_supplier' => 'Gudang → Supplier',
            default => $state,
        };
    }

    public static function tipeBg(string $state): string
    {
        return match ($state) {
            'puskesmas_ke_gudang' => 'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400',
            'pustu_ke_puskesmas' => 'bg-sky-100 text-sky-700 dark:bg-sky-900/30 dark:text-sky-400',
            'gudang_ke_supplier' => 'bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400',
            default => 'bg-gray-100 text-gray-700 dark:bg-gray-800 dark:text-gray-300',
        };
    }

    public static function formatStatus(string $state): string
    {
        return match ($state) {
            'draft' => 'Draft',
            'menunggu_approval' => 'Menunggu Approval',
            'disetujui' => 'Disetujui',
            'ditolak' => 'Ditolak',
            'dalam_pengiriman' => 'Dalam Pengiriman',
            'diterima' => 'Diterima',
            'selesai' => 'Selesai',
            default => $state,
        };
    }

    public static function statusBg(string $state): string
    {
        return match ($state) {
            'draft' => 'bg-gray-100 text-gray-700 dark:bg-gray-800 dark:text-gray-300',
            'menunggu_approval' => 'bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400',
            'disetujui' => 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400',
            'ditolak' => 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400',
            'dalam_pengiriman' => 'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400',
            'diterima' => 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400',
            'selesai' => 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400',
            default => 'bg-gray-100 text-gray-700 dark:bg-gray-800 dark:text-gray-300',
        };
    }

    public static function formatAlasan(string $state): string
    {
        return match ($state) {
            'expired' => 'Kedaluwarsa',
            'rusak' => 'Rusak',
            'kelebihan_stok' => 'Kelebihan Stok',
            'salah_kirim' => 'Salah Kirim',
            'recall' => 'Recall',
            'near_expiry' => 'Mendekati Kedaluwarsa',
            'lainnya' => 'Lainnya',
            default => $state,
        };
    }

    public static function alasanBg(string $state): string
    {
        return match ($state) {
            'expired', 'rusak', 'recall' => 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400',
            'near_expiry' => 'bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400',
            'kelebihan_stok' => 'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400',
            'salah_kirim' => 'bg-violet-100 text-violet-700 dark:bg-violet-900/30 dark:text-violet-400',
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
            'tipeLabel' => static::formatTipe($record->tipe_retur),
            'tipeBg' => static::tipeBg($record->tipe_retur),
            'alasanLabel' => static::formatAlasan($record->alasan),
            'alasanBg' => static::alasanBg($record->alasan),
            'showDistribusi' => filled($record->distribusi_id),
            'showPenerimaan' => filled($record->penerimaan_id),
        ];
    }
}
