<?php

namespace App\Filament\Resources\Users\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Support\Enums\Alignment;
use Filament\Support\Enums\IconPosition;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Stokobat\Boxicons\Boxicon;

class UsersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name'),
                TextColumn::make('email')
                    ->icon(Boxicon::Copy)
                    ->iconPosition(IconPosition::After)
                    ->alignment(Alignment::Start)
                    ->copyable()
                    ->copyMessage('Email copied!')
                    ->copyMessageDuration(1500)
                    ->tooltip('Klik untuk menyalin email'),
                TextColumn::make('fasilitasKesehatan.nama')
                    ->label('Fasilitas')
                    ->badge(),
                TextColumn::make('roles.name')
                    ->label('Roles')
                    ->badge(),
                TextColumn::make('last_active_at')
                    ->label('Last Active')
                    ->since()
                    ->placeholder('Never'),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                EditAction::make()
                    ->iconButton()
                    ->icon(Boxicon::Edit)
                    ->color('gray')
                    ->modalHeading(fn ($record) => "Edit Pengguna: {$record->name}")
                    ->modalIcon(Boxicon::Edit)
                    ->modalIconColor('primary')
                    ->modalWidth('md')
                    ->modalFooterActionsAlignment('end'),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
