<?php

namespace App\Filament\Resources\LaporanRkos\Pages;

use App\Filament\Resources\LaporanRkos\LaporanRkoResource;
use App\Models\DetailRko;
use App\Models\FasilitasKesehatan;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Pages\ViewRecord;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\Summarizers\Sum;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Stokobat\Boxicons\Boxicon;

class ViewLaporanRko extends ViewRecord implements HasTable
{
    use InteractsWithTable;

    protected static string $resource = LaporanRkoResource::class;

    protected string $view = 'filament.pages.detail-rko';

    public function mount($record = null): void
    {
        parent::mount($record);

        $this->record->loadMissing([
            'fasilitas',
            'dibuatOleh',
            'disetujuiOleh',
            'details.obat',
        ]);
    }

    public function schema(Schema $schema): Schema
    {
        return $schema
            ->record($this->record)
            ->columns(2)
            ->components([
                Section::make('info_rko')
                    ->heading('Info RKO (Rencana Kebutuhan Obat)')
                    ->columnSpanFull()
                    ->columns(3)
                    ->schema([
                        TextEntry::make('nomor_rko')
                            ->label('Nomor RKO')
                            ->placeholder('-')
                            ->copyable()
                            ->weight('medium'),
                        TextEntry::make('fasilitas.nama')
                            ->label('Fasilitas Kesehatan')
                            ->placeholder('-'),
                        TextEntry::make('periode_tahun')
                            ->label('Periode Tahun')
                            ->placeholder('-'),
                        TextEntry::make('status')
                            ->label('Status')
                            ->placeholder('-')
                            ->badge()
                            ->color(fn ($state): string => match ($state) {
                                'draft' => 'gray',
                                'diajukan' => 'warning',
                                'disetujui' => 'success',
                                'ditolak' => 'danger',
                                default => 'gray',
                            })
                            ->icon(fn ($state) => match ($state) {
                                'draft' => Boxicon::PencilDraw,
                                'diajukan' => Boxicon::ArrowDown,
                                'disetujui' => Boxicon::CheckCircle,
                                'ditolak' => Boxicon::XCircle,
                                default => null,
                            })
                            ->formatStateUsing(fn ($state): string => match ($state) {
                                'draft' => 'Draft',
                                'diajukan' => 'Diajukan',
                                'disetujui' => 'Disetujui',
                                'ditolak' => 'Ditolak',
                                default => '-',
                            }),
                        TextEntry::make('total_anggaran')
                            ->label('Total Anggaran')
                            ->placeholder('-')
                            ->money('IDR'),
                        TextEntry::make('tanggal_pembuatan')
                            ->label('Tanggal Pembuatan')
                            ->placeholder('-')
                            ->date('d/m/Y'),
                        TextEntry::make('tanggal_pengajuan')
                            ->label('Tanggal Pengajuan')
                            ->placeholder('-')
                            ->date('d/m/Y'),
                        TextEntry::make('tanggal_disetujui')
                            ->label('Tanggal Disetujui')
                            ->placeholder('-')
                            ->date('d/m/Y'),
                        TextEntry::make('dibuatOleh.name')
                            ->label('Dibuat Oleh')
                            ->placeholder('-'),
                        TextEntry::make('disetujuiOleh.name')
                            ->label('Disetujui Oleh')
                            ->placeholder('-'),
                    ]),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->heading('Detail Item ('.$this->record->details->count().' item)')
            ->query(
                DetailRko::query()
                    ->where('rko_id', $this->record->id)
                    ->join('obat', 'detail_rko.obat_id', '=', 'obat.id')
                    ->select('detail_rko.*')
                    ->with('obat')
            )
            ->columns([
                TextColumn::make('obat.kode_obat')
                    ->label('Kode')
                    ->placeholder('-')
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('obat.nama_obat')
                    ->label('Nama Obat')
                    ->searchable()
                    ->limit(30)
                    ->tooltip(function (TextColumn $column): ?string {
                        $state = $column->getState();

                        if (strlen($state) <= $column->getCharacterLimit()) {
                            return null;
                        }

                        return $state;
                    }),
                TextColumn::make('obat.satuan')
                    ->label('Satuan')
                    ->placeholder('-'),
                TextColumn::make('abc_kategori')
                    ->label('ABC')
                    ->badge()
                    ->color(fn ($state): string => match ($state) {
                        'A' => 'danger',
                        'B' => 'warning',
                        'C' => 'info',
                        default => 'gray',
                    })
                    ->placeholder('-'),
                TextColumn::make('ven_kategori')
                    ->label('VEN')
                    ->badge()
                    ->color(fn ($state): string => match ($state) {
                        'V' => 'danger',
                        'E' => 'warning',
                        'N' => 'info',
                        default => 'gray',
                    })
                    ->placeholder('-'),
                TextColumn::make('pemakaian_tahun_sebelumnya')
                    ->label('Pemakaian Th Lalu')
                    ->numeric()
                    ->alignEnd()
                    ->sortable()
                    ->summarize([
                        Sum::make()->label('Total'),
                    ]),
                TextColumn::make('rata_rata_pemakaian_bulanan')
                    ->label('Rata-rata/Bln')
                    ->numeric()
                    ->alignEnd()
                    ->sortable()
                    ->summarize([
                        Sum::make()->label('Total'),
                    ]),
                TextColumn::make('stok_akhir')
                    ->label('Sisa Stok')
                    ->numeric()
                    ->alignEnd()
                    ->sortable()
                    ->summarize([
                        Sum::make()->label('Total'),
                    ]),
                TextColumn::make('kebutuhan_tahunan')
                    ->label('Kebutuhan 18 Bln')
                    ->numeric()
                    ->alignEnd()
                    ->sortable()
                    ->summarize([
                        Sum::make()->label('Total'),
                    ]),
                TextColumn::make('rencana_kebutuhan')
                    ->label('Rencana Kebutuhan')
                    ->numeric()
                    ->alignEnd()
                    ->sortable()
                    ->summarize([
                        Sum::make()->label('Total'),
                    ]),
                TextColumn::make('usulan')
                    ->label('Usulan')
                    ->numeric()
                    ->alignEnd()
                    ->sortable()
                    ->summarize([
                        Sum::make()->label('Total'),
                    ]),
                TextColumn::make('buffer_stock_persen')
                    ->label('Buffer (%)')
                    ->suffix('%')
                    ->numeric()
                    ->alignEnd(),
                TextColumn::make('buffer_stok_qty')
                    ->label('Buffer Qty')
                    ->numeric()
                    ->alignEnd()
                    ->sortable()
                    ->summarize([
                        Sum::make()->label('Total'),
                    ]),
                TextColumn::make('total_kebutuhan')
                    ->label('Total Kebutuhan')
                    ->numeric()
                    ->alignEnd()
                    ->sortable()
                    ->summarize([
                        Sum::make()->label('Total'),
                    ]),
                TextColumn::make('harga_perkiraan')
                    ->label('Harga Perkiraan')
                    ->money('IDR')
                    ->alignEnd()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('total_harga')
                    ->label('Total Harga')
                    ->money('IDR')
                    ->alignEnd()
                    ->sortable()
                    ->summarize([
                        Sum::make()->label('Total')->money('IDR'),
                    ]),
                TextColumn::make('keterangan')
                    ->label('Keterangan')
                    ->placeholder('-')
                    ->limit(30)
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('obat.nama_obat');
    }

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make()
                ->label('Edit')
                ->visible(fn (): bool => $this->record?->status === 'draft' && $this->isOwnRko()),
            Action::make('ajukan')
                ->label('Ajukan')
                ->icon('heroicon-o-paper-airplane')
                ->color('warning')
                ->requiresConfirmation()
                ->modalHeading('Ajukan RKO')
                ->modalDescription('Yakin ingin mengajukan RKO ini? Setelah diajukan tidak dapat diedit lagi.')
                ->modalSubmitActionLabel('Ya, Ajukan')
                ->visible(fn (): bool => $this->record?->status === 'draft' && $this->isOwnRko())
                ->action(function (): void {
                    $this->record->update([
                        'status' => 'diajukan',
                        'tanggal_pengajuan' => now(),
                    ]);
                    $this->redirect(static::getResource()::getUrl('view', ['record' => $this->record]));
                }),
            Action::make('setujui')
                ->label('Setujui')
                ->icon('heroicon-o-check-circle')
                ->color('success')
                ->requiresConfirmation()
                ->modalHeading('Setujui RKO')
                ->modalDescription('Yakin ingin menyetujui RKO ini?')
                ->modalSubmitActionLabel('Ya, Setujui')
                ->visible(fn (): bool => $this->record?->status === 'diajukan'
                    && $this->canApproveRko())
                ->action(function (): void {
                    $this->record->update([
                        'status' => 'disetujui',
                        'tanggal_disetujui' => now(),
                        'disetujui_oleh' => auth()->id(),
                    ]);
                    $this->redirect(static::getResource()::getUrl('view', ['record' => $this->record]));
                }),
            Action::make('tolak')
                ->label('Tolak')
                ->icon('heroicon-o-x-circle')
                ->color('danger')
                ->requiresConfirmation()
                ->modalHeading('Tolak RKO')
                ->modalDescription('Yakin ingin menolak RKO ini?')
                ->modalSubmitActionLabel('Ya, Tolak')
                ->visible(fn (): bool => $this->record?->status === 'diajukan'
                    && $this->canApproveRko())
                ->action(function (): void {
                    $this->record->update([
                        'status' => 'ditolak',
                        'tanggal_disetujui' => now(),
                        'disetujui_oleh' => auth()->id(),
                    ]);
                    $this->redirect(static::getResource()::getUrl('view', ['record' => $this->record]));
                }),
            Action::make('cetak_pdf')
                ->label('Cetak PDF')
                ->icon('heroicon-o-printer')
                ->color('primary')
                ->visible(fn (): bool => $this->record?->status === 'disetujui')
                ->url(fn (): string => route('admin.rko.cetak-pdf', ['rko' => $this->record->id]), shouldOpenInNewTab: true),
            Action::make('cetak_xls')
                ->label('Export XLS')
                ->icon('heroicon-o-arrow-down-tray')
                ->color('success')
                ->visible(fn (): bool => $this->record?->status === 'disetujui')
                ->url(fn (): string => route('admin.rko.cetak-xls', ['rko' => $this->record]), shouldOpenInNewTab: true),
            DeleteAction::make()
                ->label('Hapus')
                ->visible(fn (): bool => in_array($this->record?->status, ['draft', 'diajukan']) && $this->isOwnRko()),
        ];
    }

    protected function isOwnRko(): bool
    {
        return $this->record?->fasilitas_id === auth()->user()?->fasilitas_kesehatan_id;
    }

    protected function canApproveRko(): bool
    {
        $user = auth()->user();

        if ($user?->hasRole('admin_dinas') || $user?->hasRole('super_admin')) {
            return true;
        }

        $userFaskesId = $user?->fasilitas_kesehatan_id;

        if (blank($userFaskesId)) {
            return false;
        }

        $faskes = FasilitasKesehatan::find($userFaskesId);

        return $faskes?->tipe === 'puskesmas'
            && $faskes->pustu()->where('id', $this->record?->fasilitas_id)->exists();
    }

    protected function getViewData(): array
    {
        $record = $this->record;

        return [
            'record' => $record,
            'statusLabel' => match ($record->status) {
                'draft' => 'Draft',
                'diajukan' => 'Diajukan',
                'disetujui' => 'Disetujui',
                'ditolak' => 'Ditolak',
                default => $record->status,
            },
            'statusColor' => match ($record->status) {
                'draft' => 'bg-gray-100 text-gray-700 dark:bg-gray-800 dark:text-gray-300',
                'diajukan' => 'bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400',
                'disetujui' => 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400',
                'ditolak' => 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400',
                default => 'bg-gray-100 text-gray-700 dark:bg-gray-800 dark:text-gray-300',
            },
            'count' => $record->details()->count(),
        ];
    }
}
