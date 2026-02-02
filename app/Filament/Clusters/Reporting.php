<?php

namespace App\Filament\Clusters;

use Filament\Clusters\Cluster;

class Reporting extends Cluster
{
    protected static ?string $navigationIcon = 'heroicon-o-chart-bar';

    protected static ?string $navigationGroup = 'Reports';

    protected static ?int $navigationSort = 6;

    protected static ?string $navigationLabel = 'Reports';

    protected static ?string $clusterBreadcrumb = 'Reports';
}
