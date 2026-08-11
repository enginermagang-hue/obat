<?php

namespace App\Filament\Resources\NeracaTahunans\Schemas;

use App\Models\NeracaTahunan;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Components\EmbeddedTable;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;

class NeracaTahunanForm
{
    public static function configure(Schema $schema): Schema
    {
        $user = auth()->user();
        $userFaskesId = $user?->fasilitas_kesehatan_id;

        return $schema
            ->columns(4)
            ->components([
                Hidden::make('nomor_neraca')
                    ->default(fn () => 'NR-'.date('Ymd').'-'.str_pad(mt_rand(1, 999), 3, '0', STR_PAD_LEFT)),
                Hidden::make('fasilitas_id')
                    ->default($userFaskesId),
                Select::make('tahun')
                    ->label('Tahun')
                    ->required()
                    ->options(array_combine(range(now()->year - 5, now()->year), range(now()->year - 5, now()->year)))
                    ->default((int) date('Y'))
                    ->rules([
                        fn (Get $get, Schema $schema): \Closure => function (string $attribute, $value, \Closure $fail) use ($get, $schema): void {
                            $fasilitasId = $get('fasilitas_id');

                            if (blank($fasilitasId) || blank($value)) {
                                return;
                            }

                            $query = NeracaTahunan::where('fasilitas_id', $fasilitasId)
                                ->where('tahun', (int) $value);

                            $record = $schema->getRecord();
                            if ($record) {
                                $query->where('id', '!=', $record->id);
                            }

                            if ($query->exists()) {
                                $fail("Neraca untuk tahun {$value} sudah ada. Silakan edit neraca yang sudah ada atau pilih tahun lain.");
                            }
                        },
                    ]),
                Hidden::make('status')
                    ->default(fn (?NeracaTahunan $record) => $record ? $record->status : 'draft')
                    ->required(),
                Hidden::make('dibuat_oleh')
                    ->default(auth()->id()),
                Textarea::make('catatan')
                    ->columnStart(1)
                    ->columnSpan(2)
                    ->nullable(),
                Section::make('Detail Item')
                    ->heading('Daftar Item Neraca')
                    ->contained(false)
                    ->description('Tambahkan item obat untuk neraca ini, atau gunakan "Generate dari Stok" di bagian atas.')
                    ->columnSpanFull()
                    ->schema([
                        EmbeddedTable::make(),
                    ]),
            ]);
    }
}
