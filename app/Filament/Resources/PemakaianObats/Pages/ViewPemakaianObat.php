<?php

namespace App\Filament\Resources\PemakaianObats\Pages;

use App\Filament\Resources\PemakaianObats\PemakaianObatResource;
use App\Filament\Resources\PemakaianObats\Tables\PemakaianObatsTable;
use App\Models\DetailPemakaianObat;
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
use Illuminate\Support\Facades\Auth;
use Stokobat\Boxicons\Boxicon;

class ViewPemakaianObat extends ViewRecord implements HasTable
{
    use InteractsWithTable;

    protected static string $resource = PemakaianObatResource::class;

    protected string $view = 'filament.pages.detail-pemakaian-obat';

    public function mount(int|string $record): void
    {
        parent::mount($record);

        $this->record->loadMissing([
            'fasilitas',
            'user',
            'details.obat',
            'details.batch',
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
        return view('filament.pages.detail-pemakaian-obat-heading', [
            'record' => $this->record,
            'jenisLabel' => PemakaianObatsTable::getJenisLabel($this->record->jenis_pelayanan),
            'jenisBg' => PemakaianObatsTable::getJenisColor($this->record->jenis_pelayanan),
            'actions' => $this->getCachedHeaderActions(),
            'actionsAlignment' => $this->getHeaderActionsAlignment(),
        ]);
    }

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make()
                ->label('Edit')
                ->visible(fn (): bool => $this->canEdit()),
        ];
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(
                DetailPemakaianObat::query()
                    ->where('pemakaian_id', $this->record->id)
                    ->join('obat', 'detail_pemakaian_obat.obat_id', '=', 'obat.id')
                    ->select('detail_pemakaian_obat.*')
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

    private function canEdit(): bool
    {
        $user = Auth::user();

        if (! $user) {
            return false;
        }

        if ($user->hasRole('super_admin')) {
            return true;
        }

        $userFaskesId = $user->fasilitas_kesehatan_id;

        if (blank($userFaskesId)) {
            return false;
        }

        return $this->record->fasilitas_id === $userFaskesId
            && $this->record->tanggal_pemakaian?->isToday();
    }

    protected function getViewData(): array
    {
        $record = $this->record;

        return [
            'record' => $record,
            'jenisLabel' => PemakaianObatsTable::getJenisLabel($record->jenis_pelayanan),
            'jenisBg' => PemakaianObatsTable::getJenisColor($record->jenis_pelayanan),
            'showDataPasien' => filled($record->nama_pasien),
        ];
    }
}
