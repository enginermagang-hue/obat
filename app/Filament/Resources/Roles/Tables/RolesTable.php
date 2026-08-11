<?php

namespace App\Filament\Resources\Roles\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\Layout\Split;
use Filament\Tables\Columns\Layout\Stack;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Stokobat\Boxicons\Boxicon;

class RolesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                Split::make([
                    ImageColumn::make('avatar')
                        ->default(asset('assets/images/icons').'/user.png')
                        ->grow(false),
                    Stack::make([
                        TextColumn::make('name')
                            ->formatStateUsing(fn (string $state): string => ucwords(str_replace(['_', '-'], ' ', $state)))
                            ->sortable(),
                        TextColumn::make('users_count')
                            ->label('Jumlah User')
                            ->color('gray')
                            ->icon(Boxicon::SolidUserCheck)
                            ->formatStateUsing(fn (string $state): string => 'Jumlah User: '.$state),
                    ]),
                    TextColumn::make('guard_name')
                        ->icon(Boxicon::SolidShield)
                        ->formatStateUsing(fn (string $state): string => ucwords($state)),
                    TextColumn::make('permissions_count')
                        ->label('Jumlah Permission')
                        ->color('gray')
                        ->icon(Boxicon::Checklist)
                        ->formatStateUsing(fn (string $state): string => 'Jumlah Permission: '.$state)
                        ->counts('permissions')
                        ->alignEnd(),
                ]),
            ])
            ->stackedOnMobile()
            ->filters([
                //
            ])
            ->modifyQueryUsing(fn ($query) => $query->withCount('users'))
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
