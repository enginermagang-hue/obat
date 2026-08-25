<?php

namespace App\Filament\Resources\ReturObats\Schemas;

use App\Models\DistribusiObat;
use App\Models\PenerimaanStok;
use App\Models\ReturObat;
use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\EmbeddedTable;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Auth;
use Stokobat\Boxicons\Boxicon;

class ReturObatForm
{
    public static function configure(Schema $schema): Schema
    {
        $user = Auth::user();
        $userFaskes = $user?->fasilitasKesehatan;
        $isFaskesUser = filled($userFaskes);

        // Auto-determine tipe_retur based on auth user
        $tipeRetur = match ($userFaskes?->tipe) {
            'puskesmas' => 'puskesmas_ke_gudang',
            'pustu' => 'pustu_ke_puskesmas',
            default => 'gudang_ke_supplier',
        };

        $components = [];

        // ═══════════════════════════════════════════
        // Section 1: Informasi Retur
        // ═══════════════════════════════════════════
        $infoComponents = [];

        $infoComponents[] = TextInput::make('nomor_retur')
            ->label('Nomor Retur')
            ->dehydrated()
            ->required(fn ($component) => ! $component->isDisabled())
            ->maxLength(100)
            ->disabled(! $user?->hasRole('super_admin'))
            ->helperText('Akan terisi otomatis jika dikosongkan')
            ->suffixAction(
                Action::make('generate_nomor_retur')
                    ->icon(Boxicon::RefreshCcwDot)
                    ->action(
                        function (Set $set) {
                            $set('nomor_retur', ReturObat::generateNomorRetur());
                        }
                    )
            );

        // Hidden fields untuk auto-populate
        $infoComponents[] = Hidden::make('tipe_retur')
            ->default($tipeRetur);

        $infoComponents[] = Hidden::make('fasilitas_pengirim_id')
            ->default($userFaskes?->id);

        $infoComponents[] = Hidden::make('fasilitas_penerima_id');

        $infoComponents[] = Hidden::make('supplier_id');

        // Status (dikontrol oleh action simpan/ajukan, bukan form)
        $infoComponents[] = Hidden::make('status')
            ->default('draft');

        // Distribusi Terkait (untuk faskes user: puskesmas_ke_gudang atau pustu_ke_puskesmas)
        if ($isFaskesUser) {
            $infoComponents[] = Select::make('distribusi_id')
                ->label('Distribusi Terkait')
                ->relationship('distribusi', 'nomor_surat_jalan', fn ($query) => $query->where('status', 'diterima'))
                ->searchable()
                ->preload()
                ->nullable()
                ->live()
                ->afterStateUpdated(fn (callable $set, $state): self => self::onDistribusiChanged($set, $state))
                ->helperText('Pilih distribusi yang ingin diretur');
        }

        // Penerimaan Terkait (untuk admin: gudang_ke_supplier)
        if (! $isFaskesUser) {
            $infoComponents[] = Select::make('penerimaan_id')
                ->label('Penerimaan Terkait')
                ->relationship('penerimaan', 'nomor_penerimaan', fn ($query) => $query->where('status', 'dikonfirmasi'))
                ->searchable()
                ->preload()
                ->nullable()
                ->live()
                ->required()
                ->afterStateUpdated(fn (callable $set, $state): self => self::onPenerimaanChanged($set, $state))
                ->helperText('Pilih penerimaan dari supplier yang ingin diretur');
        }

        $infoComponents[] = Select::make('alasan')
            ->label('Alasan Retur')
            ->required()
            ->native(false)
            ->live()
            ->options([
                'expired' => 'Kedaluwarsa',
                'rusak' => 'Rusak',
                'kelebihan_stok' => 'Kelebihan Stok',
                'salah_kirim' => 'Salah Kirim',
                'recall' => 'Recall',
                'near_expiry' => 'Mendekati Kedaluwarsa',
                'lainnya' => 'Lainnya',
            ]);

        $infoComponents[] = DatePicker::make('tanggal_retur')
            ->label('Tanggal Retur')
            ->required()
            ->default(now());

        $infoComponents[] = Textarea::make('alasan_lainnya')
            ->label('Jelaskan Alasan')
            ->nullable()
            ->rows(2)
            ->hidden(fn (Get $get): bool => $get('alasan') !== 'lainnya');

        $components[] = Section::make('Informasi Retur')
            ->heading('')
            ->contained(false)
            ->schema($infoComponents)
            ->columns(4);

        // ═══════════════════════════════════════════
        // Section 2: Catatan
        // ═══════════════════════════════════════════
        $components[] = Section::make('Catatan')
            ->heading('')
            ->schema([
                Textarea::make('catatan')
                    ->label('Catatan')
                    ->nullable()
                    ->rows(3)
                    ->placeholder('Tambahkan catatan jika diperlukan...'),
            ])
            ->contained(false);

        // ═══════════════════════════════════════════
        // Section 3: Detail Obat (Embedded Table)
        // ═══════════════════════════════════════════
        $components[] = Section::make('Detail Obat')
            ->heading('Daftar Item')
            ->contained(false)
            ->schema([
                EmbeddedTable::make(),
            ]);

        return $schema->columns(1)->components($components);
    }

    /**
     * Handle distribusi changed: update fasilitas_penerima_id + reset obat/batch.
     */
    private static function onDistribusiChanged(callable $set, $distribusiId): self
    {
        // Reset obat dan batch saat ganti distribusi
        $set('obat_id', null);
        $set('batch_id', null);

        if (blank($distribusiId)) {
            $set('fasilitas_penerima_id', null);

            return new self;
        }

        $distribusi = DistribusiObat::find($distribusiId);
        $set('fasilitas_penerima_id', $distribusi?->fasilitas_penerima_id);

        return new self;
    }

    /**
     * Handle penerimaan changed: update supplier_id + reset obat/batch.
     */
    private static function onPenerimaanChanged(callable $set, $penerimaanId): self
    {
        // Reset obat dan batch saat ganti penerimaan
        $set('obat_id', null);
        $set('batch_id', null);

        if (blank($penerimaanId)) {
            $set('supplier_id', null);

            return new self;
        }

        $penerimaan = PenerimaanStok::find($penerimaanId);
        $set('supplier_id', $penerimaan?->supplier_id);

        return new self;
    }
}
