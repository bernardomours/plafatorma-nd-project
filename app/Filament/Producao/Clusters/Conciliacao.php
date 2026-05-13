<?php

namespace App\Filament\Producao\Clusters;
use BackedEnum;
use Filament\Pages\Enums\SubNavigationPosition;
use Filament\Clusters\Cluster;

class Conciliacao extends Cluster
{
    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-clipboard-document-check';
    protected static ?string $navigationLabel = 'Conciliação de Guias';
    protected static ?SubNavigationPosition $subNavigationPosition = SubNavigationPosition::Top;
}