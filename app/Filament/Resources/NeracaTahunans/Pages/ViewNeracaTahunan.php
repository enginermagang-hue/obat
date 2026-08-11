<?php

namespace App\Filament\Resources\NeracaTahunans\Pages;

use App\Filament\Pages\CetakPdfPage;
use App\Filament\Resources\NeracaTahunans\NeracaTahunanResource;
use App\Models\DetailNeracaTahunan;
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

class ViewNeracaTahunan extends ViewRecord implements HasTable
{
    use InteractsWithTable;

    protected static string $resource = NeracaTahunanResource::class;

    protected string $view = 'filament.pages.detail-neraca';

    public function mount($record = null): void
    {
        parent::mount($record);

        $this->record->loadMissing([
            'fasilitas',
            'dibuatOleh',
            'details.obat',
        ]);
    }

    public function schema(Schema $schema): Schema
    {
        return $schema
            ->record($this->record)
            ->columns(2)
            ->components([
                Section::make('info_neraca')
                    ->heading('Info Neraca Tahunan')
                    ->columnSpanFull()
                    ->columns(3)
                    ->schema([
                        TextEntry::make('nomor_neraca')
                            ->label('Nomor Neraca')
                            ->placeholder('-')
                            ->copyable()
                            ->weight('medium'),
                        TextEntry::make('fasilitas.nama')
                            ->label('Fasilitas Kesehatan')
                            ->placeholder('Gudang'),
                        TextEntry::make('tahun')
                            ->label('Tahun')
                            ->placeholder('-'),
                        TextEntry::make('status')
                            ->label('Status')
                            ->placeholder('-')
                            ->badge()
                            ->color(fn ($state): string => match ($state) {
                                'draft' => 'gray',
                                'selesai' => 'success',
                                default => 'gray',
                            })
                            ->icon(fn ($state) => match ($state) {
                                'draft' => Boxicon::PencilDraw,
                                'selesai' => Boxicon::CheckCircle,
                                default => null,
                            })
                            ->formatStateUsing(fn ($state): string => match ($state) {
                                'draft' => 'Draft',
                                'selesai' => 'Selesai',
                                default => '-',
                            }),
                        TextEntry::make('total_nilai_stok')
                            ->label('Total Nilai Stok')
                            ->placeholder('-')
                            ->money('IDR')
                            ->state(fn () => $this->record->details->sum('nilai_stok')),
                        TextEntry::make('created_at')
                            ->label('Tanggal Dibuat')
                            ->placeholder('-')
                            ->date('d/m/Y'),
                        TextEntry::make('dibuatOleh.name')
                            ->label('Dibuat Oleh')
                            ->placeholder('-'),
                        TextEntry::make('catatan')
                            ->label('Catatan')
                            ->placeholder('-')
                            ->columnSpan(2),
                    ]),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->heading('Detail Item ('.$this->record->details->count().' item)')
            ->query(
                DetailNeracaTahunan::query()
                    ->where('neraca_id', $this->record->id)
                    ->join('obat', 'detail_neraca_tahunan.obat_id', '=', 'obat.id')
                    ->select('detail_neraca_tahunan.*')
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
                TextColumn::make('stok_awal')
                    ->label('Stok Awal')
                    ->numeric()
                    ->alignEnd()
                    ->sortable()
                    ->summarize([
                        Sum::make()->label('Total'),
                    ]),
                TextColumn::make('total_masuk')
                    ->label('Total Masuk')
                    ->numeric()
                    ->alignEnd()
                    ->sortable()
                    ->summarize([
                        Sum::make()->label('Total'),
                    ]),
                TextColumn::make('total_keluar')
                    ->label('Total Keluar')
                    ->numeric()
                    ->alignEnd()
                    ->sortable()
                    ->summarize([
                        Sum::make()->label('Total'),
                    ]),
                TextColumn::make('stok_akhir')
                    ->label('Stok Akhir')
                    ->numeric()
                    ->alignEnd()
                    ->sortable()
                    ->summarize([
                        Sum::make()->label('Total'),
                    ]),
                TextColumn::make('stok_optimum')
                    ->label('Stok Optimum')
                    ->numeric()
                    ->alignEnd()
                    ->sortable()
                    ->summarize([
                        Sum::make()->label('Total'),
                    ]),
                TextColumn::make('permintaan')
                    ->label('Permintaan')
                    ->numeric()
                    ->alignEnd()
                    ->sortable()
                    ->summarize([
                        Sum::make()->label('Total'),
                    ]),
                TextColumn::make('harga_satuan')
                    ->label('Harga Satuan')
                    ->money('IDR')
                    ->alignEnd()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('nilai_stok')
                    ->label('Nilai Stok')
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
        $user = auth()->user();
        $isFaskesUser = filled($user?->fasilitas_kesehatan_id);
        $isOwner = $isFaskesUser && $this->record?->fasilitas_id === $user->fasilitas_kesehatan_id;

        return [
            EditAction::make()
                ->label('Edit')
                ->visible(fn (): bool => $isOwner && $this->record?->status === 'draft'),
            Action::make('tandai_selesai')
                ->label('Tandai Selesai')
                ->icon('heroicon-o-check-circle')
                ->color('success')
                ->requiresConfirmation()
                ->modalHeading('Tandai Selesai')
                ->modalDescription('Yakin ingin menandai neraca ini sebagai selesai?')
                ->modalSubmitActionLabel('Ya, Selesaikan')
                ->visible(fn (): bool => $isOwner && $this->record?->status === 'draft')
                ->action(function (): void {
                    $this->record->update(['status' => 'selesai']);
                    $this->redirect(static::getResource()::getUrl('view', ['record' => $this->record]));
                }),
            Action::make('kembalikan_ke_draft')
                ->label('Kembalikan ke Draft')
                ->icon('heroicon-o-arrow-uturn-left')
                ->color('gray')
                ->requiresConfirmation()
                ->visible(fn (): bool => $isOwner && $this->record?->status === 'selesai')
                ->action(function (): void {
                    $this->record->update(['status' => 'draft']);
                    $this->redirect(static::getResource()::getUrl('view', ['record' => $this->record]));
                }),
            Action::make('cetak_pdf')
                ->label('Cetak PDF')
                ->icon('heroicon-o-printer')
                ->color('primary')
                ->visible(fn (): bool => $this->record?->status === 'selesai')
                ->url(fn (): string => CetakPdfPage::getUrl(['type' => 'neraca', 'id' => $this->record->id]), shouldOpenInNewTab: true),
            Action::make('export_excel')
                ->label('Export Excel')
                ->icon('heroicon-o-arrow-down-tray')
                ->color('success')
                ->visible(fn (): bool => $this->record?->status === 'selesai')
                ->url(fn (): string => route('admin.neraca.cetak-xls', ['neraca' => $this->record]), shouldOpenInNewTab: true),
            DeleteAction::make()
                ->label('Hapus')
                ->visible(fn (): bool => $this->record?->status === 'draft'),
        ];
    }
}
