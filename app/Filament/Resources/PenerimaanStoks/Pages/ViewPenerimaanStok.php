<?php

namespace App\Filament\Resources\PenerimaanStoks\Pages;

use App\Filament\Resources\DistribusiObats\DistribusiObatResource;
use App\Filament\Resources\PenerimaanStoks\PenerimaanStokResource;
use App\Models\DetailPenerimaanStok;
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
use Stokobat\Boxicons\Boxicon;

class ViewPenerimaanStok extends ViewRecord implements HasTable
{
    use InteractsWithTable;

    protected static string $resource = PenerimaanStokResource::class;

    protected string $view = 'filament.pages.detail-penerimaan-stok';

    public function mount(int|string $record): void
    {
        parent::mount($record);

        $this->record->loadMissing([
            'details.obat',
            'fasilitas',
            'supplier',
            'user',
            'sumberDana',
            'distribusi.fasilitasPengirim',
            'distribusi.fasilitasPenerima',
            'distribusi.permintaan',
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
        return view('filament.pages.detail-penerimaan-stok-heading', [
            'record' => $this->record,
            'statusLabel' => static::formatStatus($this->record->status),
            'statusBg' => static::statusBg($this->record->status),
            'tipeLabel' => static::formatTipe($this->record->tipe),
            'tipeBg' => static::tipeBg($this->record->tipe),
            'actions' => $this->getCachedHeaderActions(),
            'actionsAlignment' => $this->getHeaderActionsAlignment(),
        ]);
    }

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make()
                ->label('Edit Penerimaan')
                ->visible(fn (): bool => $this->record?->status === 'draft'
                    && auth()->user()->can('update', $this->record)),
            Action::make('cetak-faktur')
                ->label('Cetak Faktur')
                ->color('gray')
                ->icon(Boxicon::Printer)
                ->url(fn (): string => route('admin.penerimaan.cetak-faktur', ['penerimaan' => $this->record->id]))
                ->openUrlInNewTab(),
        ];
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(
                DetailPenerimaanStok::query()
                    ->where('penerimaan_id', $this->record->id)
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
                    ]),
                    TextColumn::make('batch_number')
                        ->label('Batch')
                        ->tooltip('No. Batch')
                        ->icon(Boxicon::Qr)
                        ->placeholder('-')
                        ->extraAttributes([
                            'class' => 'no-wrap',
                        ]),
                    TextColumn::make('tanggal_expired')
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

    public static function formatStatus(string $state): string
    {
        return match ($state) {
            'draft' => 'Draft',
            'dikonfirmasi' => 'Dikonfirmasi',
            'dibatalkan' => 'Dibatalkan',
            default => $state,
        };
    }

    public static function statusBg(string $state): string
    {
        return match ($state) {
            'draft' => 'bg-gray-100 text-gray-700 dark:bg-gray-800 dark:text-gray-300',
            'dikonfirmasi' => 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400',
            'dibatalkan' => 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400',
            default => 'bg-gray-100 text-gray-700 dark:bg-gray-800 dark:text-gray-300',
        };
    }

    public static function formatTipe(string $state): string
    {
        return match ($state) {
            'pembelian' => 'Pembelian',
            'hibah' => 'Hibah',
            'stok_awal' => 'Stok Awal',
            'penyesuaian' => 'Penyesuaian',
            'distribusi' => 'Distribusi',
            'manual' => 'Manual',
            default => $state,
        };
    }

    public static function tipeBg(string $state): string
    {
        return match ($state) {
            'pembelian' => 'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400',
            'hibah' => 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400',
            'stok_awal' => 'bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400',
            'distribusi' => 'bg-sky-100 text-sky-700 dark:bg-sky-900/30 dark:text-sky-400',
            'manual' => 'bg-gray-100 text-gray-700 dark:bg-gray-800 dark:text-gray-300',
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
            'tipeLabel' => static::formatTipe($record->tipe),
            'tipeBg' => static::tipeBg($record->tipe),
            'sumberDanaLabel' => $record->sumberDana?->nama ?? '-',
            'showDokumenPendukung' => in_array($record->tipe, ['pembelian'], true),
            'showReferensiDistribusi' => $record->tipe === 'distribusi' && $record->distribusi !== null,
            'distribusiUrl' => $record->distribusi
                ? DistribusiObatResource::getUrl('view', ['record' => $record->distribusi_id])
                : null,
        ];
    }
}
