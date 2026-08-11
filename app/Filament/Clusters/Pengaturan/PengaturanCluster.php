<?php

namespace App\Filament\Clusters\Pengaturan;

use BackedEnum;
use Filament\Clusters\Cluster;
use Filament\Pages\Enums\SubNavigationPosition;
use Stokobat\Boxicons\Boxicon;
use UnitEnum;

class PengaturanCluster extends Cluster
{
    protected static ?string $navigationLabel = 'Pengaturan';

    protected static string|UnitEnum|null $navigationGroup = 'Sistem';

    protected static string|BackedEnum|null $navigationIcon = Boxicon::Cog;

    protected static ?int $navigationSort = 83;

    protected static ?string $clusterBreadcrumb = 'Pengaturan';

    protected static ?SubNavigationPosition $subNavigationPosition = SubNavigationPosition::Top;
}
