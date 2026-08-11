<?php

namespace App\Filament\Resources\LaporanLplpos\Pages;

use App\Filament\Pages\CetakPdfPage;
use App\Filament\Resources\LaporanLplpos\LaporanLplpoResource;
use App\Models\DetailLplpo;
use App\Models\LaporanLplpo;
use App\Services\LaporanLplpoService;
use Filament\Actions\Action;
use Filament\Infolists\Components\TextEntry;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use Filament\Schemas\Components\Callout;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\Summarizers\Sum;
use Filament\Tables\Columns\Summarizers\Summarizer;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Database\Query\Builder;
use Stokobat\Boxicons\Boxicon;

class ShowLaporanLplpo extends ViewRecord implements HasTable
{
    use InteractsWithTable;

    protected static string $resource = LaporanLplpoResource::class;

    protected string $view = 'filament.pages.detail-lplpo';

    public function mount($record = null): void
    {
        parent::mount($record);

        $this->record->loadMissing([
            'fasilitas',
            'dibuatOleh',
            'parentLplpo',
            'revisiLplpo',
        ]);
    }

    public function schema(Schema $schema): Schema
    {
        return $schema
            ->record($this->record)
            ->columns(2)
            ->components([
                Section::make('info_fakses')
                    ->heading('Info Fasilitas Kesehatan')
                    ->components([
                        TextEntry::make('fasilitas.nama')
                            ->label('Nama Fasilitas')
                            ->placeholder('-'),
                        TextEntry::make('fasilitas.pic')
                            ->label('PIC')
                            ->placeholder('-'),
                        TextEntry::make('fasilitas.kontak_pic')
                            ->label('Kontak PIC')
                            ->placeholder('-'),
                    ]),

                Section::make('info_lplpo')
                    ->heading('Info LPLPO')
                    ->components([
                        TextEntry::make('nomor_laporan')
                            ->label('Nomor Laporan')
                            ->placeholder('-'),
                        Grid::make(2)
                            ->components([
                                TextEntry::make('status')
                                    ->label('Status')
                                    ->placeholder('-')
                                    ->badge()
                                    ->color(fn ($state) => match ($state) {
                                        'draft' => 'gray',
                                        'selesai' => 'success',
                                        default => 'gray',
                                    })
                                    ->icon(fn ($state) => match ($state) {
                                        'draft' => Boxicon::PencilDraw,
                                        'selesai' => Boxicon::CheckCircle,
                                        default => Boxicon::PencilDraw,
                                    })
                                    ->formatStateUsing(function ($state) {
                                        return match ($state) {
                                            'draft' => 'Draft',
                                            'selesai' => 'Selesai',
                                            default => '-',
                                        };
                                    }),

                                TextEntry::make('periode')
                                    ->label('Periode')
                                    ->formatStateUsing(fn ($record): string => static::getNamaBulan($record->periode_bulan).' '.$record->periode_tahun),

                                TextEntry::make('parentLplpo.nomor_laporan')
                                    ->label('Revisi Dari')
                                    ->placeholder('-'),

                                TextEntry::make('revisiLplpo_count')
                                    ->label('Jumlah Revisi')
                                    ->counts('revisiLplpo')
                                    ->placeholder('0'),

                                TextEntry::make('dibuatOleh.name')
                                    ->label('Dibuat Oleh')
                                    ->placeholder('-'),
                            ]),
                    ]),

                Section::make('validasi')
                    ->heading('Validasi LPLPO')
                    ->icon('heroicon-o-shield-check')
                    ->iconColor('warning')
                    ->columnSpanFull()
                    ->visible(fn () => $this->record?->status === 'selesai')
                    ->components([
                        Callout::make()
                            ->heading(fn (): string => $this->getValidationResult('errors_count').' error ditemukan')
                            ->description(fn (): string => implode("\n", $this->getValidationResult('errors')))
                            ->danger()
                            ->icon('heroicon-o-x-circle')
                            ->visible(fn () => $this->getValidationResult('errors_count') > 0),

                        Callout::make()
                            ->heading(fn (): string => $this->getValidationResult('warnings_count').' warning ditemukan')
                            ->description(fn (): string => implode("\n", $this->getValidationResult('warnings')))
                            ->warning()
                            ->icon('heroicon-o-exclamation-triangle')
                            ->visible(fn () => $this->getValidationResult('warnings_count') > 0),

                        Callout::make()
                            ->heading('Data LPLPO valid')
                            ->description('Semua rumus perhitungan konsisten.')
                            ->success()
                            ->icon('heroicon-o-check-circle')
                            ->visible(fn () => $this->getValidationResult('errors_count') === 0 && $this->getValidationResult('warnings_count') === 0),
                    ]),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->heading('Detail Item ( '.$this->record->details->count().' item)')
            ->query(
                DetailLplpo::query()
                    ->where('lplpo_id', $this->record->id)
                    ->join('obat', 'detail_lplpo.obat_id', '=', 'obat.id')
                    ->select('detail_lplpo.*')
                    ->with('obat')
            )
            ->columns([
                TextColumn::make('obat.kode_obat')
                    ->label('Kode')
                    ->placeholder('-'),
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
                    ->label('Satuan'),
                TextColumn::make('stok_awal')
                    ->label('Stok Awal')
                    ->numeric()
                    ->alignEnd()
                    ->sortable()
                    ->summarize([
                        Sum::make()->label('Total'),
                    ]),
                TextColumn::make('jumlah_masuk')
                    ->label('Penerimaan')
                    ->numeric()
                    ->alignEnd()
                    ->sortable()
                    ->summarize([
                        Sum::make()->label('Total'),
                    ]),
                TextColumn::make('persediaan')
                    ->label('Persediaan')
                    ->getStateUsing(fn (DetailLplpo $detail): int => ($detail->stok_awal ?? 0) + ($detail->jumlah_masuk ?? 0))
                    ->numeric()
                    ->alignEnd()
                    ->summarize([
                        Summarizer::make()
                            ->label('Total')
                            ->using(fn (Builder $query): int => (int) $query->sum(\DB::raw('stok_awal + jumlah_masuk'))),
                    ]),
                TextColumn::make('jumlah_keluar')
                    ->label('Pemakaian')
                    ->numeric()
                    ->alignEnd()
                    ->sortable()
                    ->summarize([
                        Sum::make()->label('Total'),
                    ]),
                TextColumn::make('sisa_stok')
                    ->label('Sisa Stok')
                    ->numeric()
                    ->alignEnd()
                    ->sortable()
                    ->color(fn ($state): string => $state > 0 ? 'primary' : 'danger')
                    ->summarize([
                        Sum::make()->label('Total'),
                    ]),
                TextColumn::make('stok_optimum')
                    ->label('Stok Opt.')
                    ->numeric()
                    ->alignEnd()
                    ->sortable()
                    ->summarize([
                        Sum::make()->label('Total'),
                    ]),
                TextColumn::make('permintaan_selanjutnya')
                    ->label('Permintaan')
                    ->numeric()
                    ->alignEnd()
                    ->sortable()
                    ->summarize([
                        Sum::make()->label('Total'),
                    ]),
                TextColumn::make('keterangan')
                    ->label('Ket.')
                    ->placeholder('-')
                    ->limit(30),
            ])
            ->defaultSort('obat.nama_obat');
    }

    protected function getHeaderActions(): array
    {
        return [
            $this->getCreateRevisiAction(),
            $this->getPrintAction(),
        ];
    }

    protected function getCreateRevisiAction(): Action
    {
        return Action::make('createRevisi')
            ->label('Buat Revisi')
            ->icon('heroicon-o-pencil')
            ->color('warning')
            ->visible(fn () => $this->record?->status === 'selesai')
            ->requiresConfirmation()
            ->modalHeading('Buat Revisi LPLPO')
            ->modalDescription('Ini akan membuat LPLPO baru sebagai revisi dari LPLPO ini. LPLPO baru akan berstatus draft dengan data yang sama.')
            ->action(function (): void {
                $original = $this->record;
                $user = auth()->user();

                $newLplpo = LaporanLplpo::create([
                    'nomor_laporan' => 'LPLPO-'.date('Ymd').'-'.str_pad(mt_rand(1, 999), 3, '0', STR_PAD_LEFT),
                    'fasilitas_id' => $original->fasilitas_id,
                    'periode_bulan' => $original->periode_bulan,
                    'periode_tahun' => $original->periode_tahun,
                    'status' => 'draft',
                    'tanggal_pembuatan' => now(),
                    'dibuat_oleh' => $user->id,
                    'parent_lplpo_id' => $original->id,
                ]);

                // Copy details from original
                foreach ($original->details as $detail) {
                    $newLplpo->details()->create([
                        'obat_id' => $detail->obat_id,
                        'stok_awal' => $detail->stok_awal,
                        'jumlah_masuk' => $detail->jumlah_masuk,
                        'jumlah_keluar' => $detail->jumlah_keluar,
                        'sisa_stok' => $detail->sisa_stok,
                        'stok_optimum' => $detail->stok_optimum,
                        'permintaan_selanjutnya' => $detail->permintaan_selanjutnya,
                        'keterangan' => $detail->keterangan,
                    ]);
                }

                Notification::make()
                    ->title('Revisi LPLPO berhasil dibuat')
                    ->body('LPLPO baru: '.$newLplpo->nomor_laporan)
                    ->success()
                    ->send();

                $this->redirect(LaporanLplpoResource::getUrl('edit', ['record' => $newLplpo]));
            });
    }

    protected function getPrintAction(): Action
    {
        return Action::make('print')
            ->label('Cetak PDF')
            ->icon('heroicon-o-printer')
            ->color('primary')
            ->url(fn () => CetakPdfPage::getUrl(['type' => 'lplpo', 'id' => $this->record->id]), shouldOpenInNewTab: true);
    }

    public static function getNamaBulan(int $bulan): string
    {
        $bulanList = [
            1 => 'Januari',
            2 => 'Februari',
            3 => 'Maret',
            4 => 'April',
            5 => 'Mei',
            6 => 'Juni',
            7 => 'Juli',
            8 => 'Agustus',
            9 => 'September',
            10 => 'Oktober',
            11 => 'November',
            12 => 'Desember',
        ];

        return $bulanList[$bulan] ?? $bulan;
    }

    protected function getViewData(): array
    {
        $record = $this->record;
        $bulanList = [
            1 => 'Januari',
            2 => 'Februari',
            3 => 'Maret',
            4 => 'April',
            5 => 'Mei',
            6 => 'Juni',
            7 => 'Juli',
            8 => 'Agustus',
            9 => 'September',
            10 => 'Oktober',
            11 => 'November',
            12 => 'Desember',
        ];

        return [
            'record' => $record,
            'namaBulan' => $bulanList[$record->periode_bulan] ?? $record->periode_bulan,
            'statusLabel' => match ($record->status) {
                'draft' => 'Draft',
                'selesai' => 'Selesai',
                default => $record->status,
            },
            'statusColor' => match ($record->status) {
                'draft' => 'bg-gray-100 text-gray-700 dark:bg-gray-800 dark:text-gray-300',
                'selesai' => 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400',
                default => 'bg-gray-100 text-gray-700 dark:bg-gray-800 dark:text-gray-300',
            },
            'count' => $record->details()->count(),
        ];
    }

    protected function getValidationResult(string $key): mixed
    {
        static $cache = null;

        if ($cache === null && $this->record) {
            $cache = app(LaporanLplpoService::class)->validate($this->record);
        }

        return $cache[$key] ?? null;
    }
}
