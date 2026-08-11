<?php

namespace App\Filament\Resources\StokFaskes\Pages;

use App\Filament\Resources\StokFaskes\StokFaskesResource;
use App\Models\BatchStok;
use App\Models\FasilitasKesehatan;
use App\Models\Obat;
use App\Models\StokFaskes;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\Page;
use Filament\Schemas\Components\EmbeddedTable;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\HtmlString;

class DaftarBatchStokFaskes extends Page implements HasTable
{
    use InteractsWithTable;

    protected static string $resource = StokFaskesResource::class;

    public int $obatId;

    public int $fasilitasId;

    public string $namaObat = '';

    public string $namaFasilitas = '';

    public function mount(int $obat_id): void
    {
        $this->obatId = $obat_id;
        $this->fasilitasId = (int) request()->query('fasilitas_id', 0);

        $obat = Obat::find($obat_id);
        $this->namaObat = $obat?->nama_obat ?? "Obat #{$obat_id}";

        $fasilitas = FasilitasKesehatan::find($this->fasilitasId);
        $this->namaFasilitas = $fasilitas?->nama ?? "Faskes #{$this->fasilitasId}";

        $user = Auth::user();

        if ($user->hasAnyRole(['puskesmas', 'pustu']) && filled($user->fasilitas_kesehatan_id) && $user->fasilitas_kesehatan_id !== $this->fasilitasId) {
            abort(403);
        }
    }

    public function getTitle(): string|HtmlString
    {
        return new HtmlString(
            "Daftar Stok: <span class=\"font-normal\">{$this->namaObat}</span>"
            ." — <span class=\"font-normal text-gray-500\">{$this->namaFasilitas}</span>"
        );
    }

    public function getSubheading(): ?string
    {
        $totalBatch = (int) BatchStok::where('obat_id', $this->obatId)
            ->where('fasilitas_id', $this->fasilitasId)
            ->where('status', 'tersedia')
            ->sum('jumlah');

        $stokFaskes = (int) (StokFaskes::where('obat_id', $this->obatId)
            ->where('fasilitas_id', $this->fasilitasId)
            ->value('jumlah') ?? 0);

        if ($totalBatch === $stokFaskes) {
            return null;
        }

        return "Stok tidak sinkron: batch tersedia ({$totalBatch}) ≠ sistem ({$stokFaskes})";
    }

    public function getBreadcrumbs(): array
    {
        return [];
    }

    protected function getHeaderActions(): array
    {
        $totalBatch = (int) BatchStok::where('obat_id', $this->obatId)
            ->where('fasilitas_id', $this->fasilitasId)
            ->where('status', 'tersedia')
            ->sum('jumlah');

        $stokFaskes = (int) (StokFaskes::where('obat_id', $this->obatId)
            ->where('fasilitas_id', $this->fasilitasId)
            ->value('jumlah') ?? 0);

        return [
            Action::make('sinkron_stok')
                ->label('Sinkronkan Stok')
                ->icon('heroicon-o-arrow-path')
                ->color('warning')
                ->requiresConfirmation()
                ->modalHeading('Sinkronkan Stok')
                ->modalDescription("Batch tersedia: {$totalBatch}, Stok sistem: {$stokFaskes}. Stok akan disinkronkan dari data batch.")
                ->modalSubmitActionLabel('Sinkronkan')
                ->action(fn () => $this->sinkronStok())
                ->visible(fn () => $totalBatch !== $stokFaskes),
        ];
    }

    public function content(Schema $schema): Schema
    {
        return $schema
            ->components([
                EmbeddedTable::make(),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table;
    }

    protected function getTableQuery(): Builder
    {
        return BatchStok::query()
            ->where('obat_id', $this->obatId)
            ->where('fasilitas_id', $this->fasilitasId)
            ->with(['sumberDana', 'penerimaan']);
    }

    protected function getTableColumns(): array
    {
        return [
            TextColumn::make('batch_number')
                ->label('Batch Number')
                ->searchable()
                ->sortable(),
            TextColumn::make('tanggal_masuk')
                ->label('Tanggal Masuk')
                ->date('d/m/Y')
                ->sortable(),
            TextColumn::make('tanggal_expired')
                ->label('Tanggal Expired')
                ->date('d/m/Y')
                ->sortable()
                ->color(fn ($record): string => self::getExpiredColor($record)),
            TextColumn::make('jumlah')
                ->label('Jumlah')
                ->numeric()
                ->alignEnd()
                ->sortable()
                ->color(fn (BatchStok $record): string => match ($record->status) {
                    'tersedia' => 'success',
                    'karantina' => 'warning',
                    default => 'gray',
                }),
            TextColumn::make('status')
                ->label('Status')
                ->badge()
                ->formatStateUsing(fn (string $state): string => match ($state) {
                    'tersedia' => 'Tersedia',
                    'karantina' => 'Karantina',
                    default => ucfirst($state),
                })
                ->color(fn (string $state): string => match ($state) {
                    'tersedia' => 'success',
                    'karantina' => 'warning',
                    default => 'gray',
                }),
            TextColumn::make('sumberDana.nama')
                ->label('Sumber Dana')
                ->placeholder('-')
                ->sortable(),
            TextColumn::make('penerimaan.nomor_penerimaan')
                ->label('Penerimaan')
                ->placeholder('-')
                ->sortable(),
        ];
    }

    protected function getDefaultTableSortColumn(): ?string
    {
        return 'tanggal_expired';
    }

    protected function getDefaultTableSortDirection(): ?string
    {
        return 'asc';
    }

    protected function getTableRecordAction(): ?string
    {
        return null;
    }

    protected function getTableFilters(): array
    {
        return [
            SelectFilter::make('expired_filter')
                ->label('Status Expired')
                ->options([
                    'sudah_expired' => 'Sudah Expired',
                    'expired_1_bulan' => 'Expired < 1 Bulan',
                    'expired_2_bulan' => 'Expired < 2 Bulan',
                    'expired_3_bulan' => 'Expired < 3 Bulan',
                ])
                ->placeholder('Semua')
                ->query(fn (Builder $query, ?string $value): Builder => match ($value) {
                    'sudah_expired' => $query->where('tanggal_expired', '<', now()),
                    'expired_1_bulan' => $query->where('tanggal_expired', '>=', now())
                        ->where('tanggal_expired', '<', now()->addDays(30)),
                    'expired_2_bulan' => $query->where('tanggal_expired', '>=', now()->addDays(30))
                        ->where('tanggal_expired', '<', now()->addDays(60)),
                    'expired_3_bulan' => $query->where('tanggal_expired', '>=', now()->addDays(60))
                        ->where('tanggal_expired', '<', now()->addDays(90)),
                    default => $query,
                }),
        ];
    }

    protected function sinkronStok(): void
    {
        StokFaskes::recalculateForObat($this->fasilitasId, $this->obatId);

        Notification::make()
            ->title('Stok berhasil disinkronkan')
            ->success()
            ->send();

        $this->dispatch('refreshTable');
    }

    private static function getExpiredColor(BatchStok $record): string
    {
        $daysUntilExpired = now()->diffInDays($record->tanggal_expired, false);

        if ($daysUntilExpired < 0) {
            return 'danger';
        }

        if ($daysUntilExpired <= 30) {
            return 'warning';
        }

        return 'gray';
    }
}
