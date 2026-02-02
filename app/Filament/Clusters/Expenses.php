<?php

namespace App\Filament\Clusters;

use Filament\Clusters\Cluster;

class Expenses extends Cluster
{
    protected static ?string $navigationIcon = 'heroicon-o-credit-card';

    protected static ?string $navigationGroup = 'Expenses';

    protected static ?int $navigationSort = 2;

    protected static ?string $navigationLabel = 'Expenses';

    protected static ?string $clusterBreadcrumb = 'Expenses';
}
