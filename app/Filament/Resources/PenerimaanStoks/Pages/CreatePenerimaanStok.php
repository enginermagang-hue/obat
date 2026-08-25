<?php

namespace App\Filament\Resources\PenerimaanStoks\Pages;

use App\Filament\Resources\PenerimaanStoks\PenerimaanStokResource;
use App\Filament\Resources\PenerimaanStoks\Schemas\PenerimaanStokForm;
use App\Models\DistribusiObat;
use App\Models\PenerimaanStok;
use App\Services\NotificationService;
use App\Services\StokService;
use Filament\Actions\Action;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Url;

class CreatePenerimaanStok extends CreateRecord
{
    use InteractsWithActions;

    protected static string $resource = PenerimaanStokResource::class;

    private ?string $actionStatus = null;

    private array $detailsToCreate = [];

    #[Url]
    public ?int $distribusi_id = null;

    #[Url]
    public ?string $tipe = null;

    public function form(Schema $schema): Schema
    {
        return $schema->columns(1)->components([
            PenerimaanStokForm::configureCreate(),
        ]);
    }

    public string $step = 'informasi';

    public function beforeNavigate(string $step): void
    {
        $rules = match ($step) {
            'item-obat' => [
                'data.nomor_penerimaan' => ['required'],
                'data.tipe' => ['required'],
                'data.tanggal_penerimaan' => ['required'],
            ],
            'konfirmasi' => [
                'data.details' => ['required', 'array', 'min:1'],
                'data.details.*.obat_id' => ['required'],
                'data.details.*.jumlah' => ['required', 'numeric', 'min:0.01'],
                'data.details.*.tanggal_expired' => ['required'],
                'data.details.*.batch_number' => ['required'],
            ],
            default => [],
        };

        $this->validate($rules);
    }

    protected function getFormActions(): array
    {
        $batal = $this->getCancelFormAction()->label('Batal');

        $sebelumnya = Action::make('sebelumnya')
            ->label('Sebelumnya')
            ->color('gray')
            ->extraAttributes(['type' => 'button'])
            ->visible(fn () => $this->step !== 'informasi')
            ->action(function () {
                $target = match ($this->step) {
                    'konfirmasi' => 'item-obat',
                    default => 'informasi',
                };
                $this->step = $target;
                $this->dispatch('wizard-navigate', step: 'form.'.$target.'::data::wizard-step');
            });

        $selanjutnya = Action::make('selanjutnya')
            ->label('Selanjutnya')
            ->color('gray')
            ->extraAttributes([
                'type' => 'button',
                'class' => 'ml-auto',
            ])
            ->visible(fn () => $this->step !== 'konfirmasi')
            ->action(function () {
                $target = match ($this->step) {
                    'informasi' => 'item-obat',
                    default => 'konfirmasi',
                };
                $this->beforeNavigate($target);
                $this->step = $target;
                $this->dispatch('wizard-navigate', step: 'form.'.$target.'::data::wizard-step');
            });

        $simpan = Action::make('simpan')
            ->label('Simpan')
            ->icon('heroicon-m-document-check')
            ->color('gray')
            ->visible(fn () => $this->step === 'konfirmasi')
            ->action(fn () => $this->prosesSimpan('draft'))
            ->extraAttributes([
                'class' => 'ml-auto',
            ]);

        $buat = Action::make('buat')
            ->label('Buat')
            ->icon('heroicon-m-check-circle')
            ->color('success')
            ->visible(fn () => $this->step === 'konfirmasi')
            ->action(fn () => $this->prosesSimpan('dikonfirmasi'));

        return [$batal, $sebelumnya, $selanjutnya, $simpan, $buat];
    }

    public function prosesSimpan(string $status): void
    {
        $this->actionStatus = $status;
        $this->create();

        Notification::make()
            ->success()
            ->title($status === 'draft'
                ? 'Penerimaan stok disimpan sebagai draft'
                : 'Penerimaan stok berhasil dibuat')
            ->send();
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['user_id'] = auth()->id();
        $data['status'] = $this->actionStatus ?? 'draft';

        // Fasilitas penerima wajib dari user login - jangan bergantung pada
        // hidden field yang bisa terkirim kosong (bug penerimaan distribusi).
        $data['fasilitas_id'] ??= auth()->user()?->fasilitas_kesehatan_id;

        if (blank($data['nomor_penerimaan'] ?? null)) {
            $data['nomor_penerimaan'] = PenerimaanStok::generateNomorPenerimaan();
        }

        if (($data['tipe'] ?? null) === 'manual') {
            $data['distribusi_id'] = null;
        }

        $this->detailsToCreate = $data['details'] ?? [];
        unset($data['details']);

        if (($data['tipe'] ?? null) === 'pembelian') {
            $totalBiaya = 0.0;
            foreach ($this->detailsToCreate as $d) {
                $hargaRaw = $d['harga_satuan'] ?? null;
                $hargaNum = filled($hargaRaw)
                    ? (float) (str_contains((string) $hargaRaw, ',')
                        ? str_replace(['.', ','], ['', '.'], (string) $hargaRaw)
                        : (string) $hargaRaw)
                    : 0.0;
                $totalBiaya += (int) ($d['jumlah'] ?? 0) * $hargaNum;
            }
            $data['total_biaya'] = $totalBiaya > 0 ? round($totalBiaya, 2) : null;
        }

        return $data;
    }

    protected function afterCreate(): void
    {
        $record = $this->getRecord();

        foreach ($this->detailsToCreate as $d) {
            $hargaRaw = $d['harga_satuan'] ?? null;
            $hargaNum = filled($hargaRaw)
                ? (float) (str_contains((string) $hargaRaw, ',')
                    ? str_replace(['.', ','], ['', '.'], (string) $hargaRaw)
                    : (string) $hargaRaw)
                : null;
            $jumlah = (int) ($d['jumlah'] ?? 0);
            $subTotal = $hargaNum !== null ? $jumlah * $hargaNum : null;

            $record->details()->create([
                'obat_id' => $d['obat_id'],
                'batch_number' => $d['batch_number'],
                'tanggal_expired' => $d['tanggal_expired'],
                'jumlah' => $jumlah,
                'harga_satuan' => $hargaNum,
                'sub_total' => $subTotal,
                'keterangan' => $d['keterangan'] ?? null,
            ]);
        }

        if ($record->status !== 'dikonfirmasi') {
            return;
        }

        $stokService = app(StokService::class);

        if ($record->tipe === 'distribusi') {
            $stokService->prosesPenerimaanDistribusi($record);
        } else {
            $stokService->prosesPenerimaan($record);
        }

        $this->notifyDikonfirmasi($record);
    }

    private function notifyDikonfirmasi(PenerimaanStok $record): void
    {
        $url = PenerimaanStokResource::getUrl('view', ['record' => $record->id]);

        app(NotificationService::class)->notifyRole(
            'admin_gudang',
            'Penerimaan Stok Dikonfirmasi',
            "Penerimaan {$record->nomor_penerimaan} telah dikonfirmasi dan stok diperbarui.",
            $url,
            icon: 'heroicon-o-inbox-arrow-down',
            color: 'success',
        );

        if ($record->tipe === 'distribusi' && $record->distribusi?->fasilitas_pengirim_id) {
            app(NotificationService::class)->notifyFaskesUsers(
                $record->distribusi->fasilitas_pengirim_id,
                'Distribusi Obat Diterima',
                "Distribusi Anda telah diterima melalui penerimaan {$record->nomor_penerimaan}.",
                $url,
                icon: 'heroicon-o-inbox-arrow-down',
                color: 'success',
            );
        }
    }

    public function updatedDataDistribusiId($value): void
    {
        if (blank($value)) {
            $this->form->rawState(['details' => []]);

            return;
        }

        $user = Auth::user();
        $userFaskesId = $user?->fasilitas_kesehatan_id;

        $dist = DistribusiObat::with('details.batch.obat')->find((int) $value);
        if (! $dist || $dist->status !== 'dalam_pengiriman' || (filled($userFaskesId) && $dist->fasilitas_penerima_id !== $userFaskesId)) {
            Notification::make()
                ->title('Distribusi tidak valid')
                ->body('Distribusi harus berstatus "Dalam Pengiriman" dan ditujukan ke faskes Anda.')
                ->danger()
                ->send();
            $this->form->rawState(['details' => []]);

            return;
        }

        $items = [];
        foreach ($dist->details as $detail) {
            $batch = $detail->batch;
            $obat = $detail->obat;

            $items[] = [
                'obat_id' => $detail->obat_id,
                'batch_number' => $batch?->batch_number,
                'tanggal_expired' => $batch?->tanggal_expired?->format('Y-m-d'),
                'jumlah' => $detail->jumlah,
                'harga_satuan' => null,
                'sub_total' => null,
                'keterangan' => null,
            ];
        }

        $this->form->rawState(['details' => $items]);
    }

    public function fillForm(): void
    {
        parent::fillForm();

        if (blank($this->distribusi_id) || $this->tipe !== 'distribusi') {
            return;
        }

        $user = Auth::user();
        $userFaskesId = $user?->fasilitas_kesehatan_id;

        $dist = DistribusiObat::with('details.batch.obat')->find((int) $this->distribusi_id);
        if (! $dist || $dist->status !== 'dalam_pengiriman' || (filled($userFaskesId) && $dist->fasilitas_penerima_id !== $userFaskesId)) {
            return;
        }

        $items = [];
        foreach ($dist->details as $detail) {
            $items[] = [
                'obat_id' => $detail->obat_id,
                'batch_number' => $detail->batch?->batch_number,
                'tanggal_expired' => $detail->batch?->tanggal_expired?->format('Y-m-d'),
                'jumlah' => $detail->jumlah,
                'harga_satuan' => null,
                'sub_total' => null,
                'keterangan' => null,
            ];
        }

        $this->form->rawState([
            ...$this->form->getRawState(),
            'tipe' => 'distribusi',
            'distribusi_id' => $dist->id,
            'tanggal_penerimaan' => now()->format('Y-m-d'),
            'details' => $items,
        ]);
        $this->data['tipe'] = 'distribusi';
        $this->data['distribusi_id'] = $dist->id;
    }

    protected function getRedirectUrl(): string
    {
        return static::getResource()::getUrl('view', ['record' => $this->getRecord()]);
    }

    protected function getCreatedNotification(): ?Notification
    {
        return null;
    }
}
