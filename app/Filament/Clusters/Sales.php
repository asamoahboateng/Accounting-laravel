<?php

namespace App\Filament\Clusters;

use Filament\Clusters\Cluster;

class Sales extends Cluster
{
    protected static ?string $navigationIcon = 'heroicon-o-currency-dollar';

    protected static ?string $navigationGroup = 'Sales';

    protected static ?int $navigationSort = 1;

    protected static ?string $navigationLabel = 'Sales';

    protected static ?string $clusterBreadcrumb = 'Sales';

    public static function getNavigationBadge(): ?string
    {
        return null;
    }
}
