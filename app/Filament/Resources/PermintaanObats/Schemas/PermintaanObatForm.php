<?php

namespace App\Filament\Resources\PermintaanObats\Schemas;

use App\Models\PermintaanObat;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\EmbeddedTable;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Auth;

class PermintaanObatForm
{
    public static function configure(Schema $schema): Schema
    {
        $user = Auth::user();
        $userFaskes = $user?->fasilitasKesehatan;

        $isDisabled = fn (?PermintaanObat $record) => $record?->status === 'menunggu_persetujuan';

        return $schema
            ->columns(1)
            ->components([
                Section::make()
                    ->components([
                        Hidden::make('tipe_permintaan')
                            ->default(function () use ($userFaskes) {
                                if ($userFaskes->tipe === 'puskesmas') {
                                    return 'puskesmas_ke_dinas';
                                } else {
                                    return 'pustu_ke_puskesmas';
                                }
                            }),

                        Hidden::make('fasilitas_pengirim_id')
                            ->default(fn () => $userFaskes?->id),

                        Hidden::make('fasilitas_tujuan_id')
                            ->default(function () use ($userFaskes) {
                                if ($userFaskes->tipe === 'puskesmas') {
                                    return null;
                                } else {
                                    return $userFaskes->puskesmas_induk_id;
                                }
                            }),

                        Grid::make(3)
                            ->components([
                                TextInput::make('nomor_permintaan')
                                    ->label('Nomor Permintaan')
                                    ->required()
                                    ->maxLength(100)
                                    ->default(fn () => PermintaanObat::generateNomorPermintaan())
                                    ->disabled($isDisabled),

                                DatePicker::make('tanggal_permintaan')
                                    ->label('Tanggal Permintaan')
                                    ->required()
                                    ->default(now())
                                    ->native(false)
                                    ->disabled($isDisabled),

                                TextInput::make('fasilitas_tujuan_display')
                                    ->label('Tujuan')
                                    ->dehydrated(false)
                                    ->disabled()
                                    ->formatStateUsing(function (mixed $state, ?PermintaanObat $record) use ($userFaskes) {
                                        if ($record) {
                                            return $record->fasilitasTujuan?->nama ?? 'Dinas Kesehatan';
                                        }
                                        if ($userFaskes->tipe === 'puskesmas') {
                                            return 'Dinas Kesehatan';
                                        }

                                        return $userFaskes->puskesmasInduk->nama;
                                    }),
                            ]),

                        // ═══════════════════════════════════════════
                        // Detail Obat — EmbeddedTable (didefinisikan di halaman)
                        // ═══════════════════════════════════════════
                        Section::make('Detail Obat')
                            ->heading('Daftar Item')
                            ->contained(false)
                            ->schema([
                                EmbeddedTable::make(),
                            ]),

                        Textarea::make('catatan')
                            ->label('Catatan')
                            ->autosize()
                            ->rows(5)
                            ->disabled($isDisabled)
                            ->helperText('Tambahkan catatan jika diperlukan.'),

                        FileUpload::make('surat_permintaan')
                            ->label('Surat Permintaan')
                            ->acceptedFileTypes(['application/pdf', 'image/jpeg', 'image/png', 'image/jpg'])
                            ->maxSize(5120)
                            ->disk('public')
                            ->directory('surat-permintaan')
                            ->columnSpanFull()
                            ->visible(function (?PermintaanObat $record) use ($isDisabled) {
                                if ($record) {
                                    return ! $isDisabled($record);
                                }

                                return true;
                            })
                            ->helperText(fn (?PermintaanObat $record): string => match (true) {
                                filled($record?->surat_permintaan) => 'Surat permintaan sudah diupload. Upload ulang untuk mengganti.',
                                default => 'Upload surat permintaan obat yang telah ditandatangani. Wajib untuk mengirim permintaan.',
                            }),

                        Textarea::make('alasan_penolakan')
                            ->label('Alasan Penolakan')
                            ->visible(fn (?PermintaanObat $record) => $record?->status === 'ditolak'),
                    ]),
            ]);
    }
}
