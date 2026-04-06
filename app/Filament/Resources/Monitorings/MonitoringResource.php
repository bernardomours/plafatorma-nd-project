<?php

namespace App\Filament\Resources\Monitorings;

use App\Filament\Resources\Monitorings\Pages\CreateMonitoring;
use App\Filament\Resources\Monitorings\Pages\EditMonitoring;
use App\Filament\Resources\Monitorings\Pages\ListMonitorings;
use App\Filament\Resources\Monitorings\Schemas\MonitoringForm;
use App\Filament\Resources\Monitorings\Tables\MonitoringsTable;
use App\Models\Monitoring;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;

class MonitoringResource extends Resource
{
    protected static ?string $model = Monitoring::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-chart-bar';
    protected static ?string $modelLabel = 'Tarefa';
    protected static ?string $pluralModelLabel = 'Monitoramento de Tarefas';
    protected static ?string $navigationLabel = 'Monitoramento de Tarefas';

    public static function form(Schema $schema): Schema
    {
        return MonitoringForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return MonitoringsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function canViewAny(): bool
    {
        $user = auth()->user();
        return $user->isAdmin() || $user->isManager() || $user->isAdministrative();
    }

    public static function getPages(): array
    {
        return [
            'index' => ListMonitorings::route('/'),
            'create' => CreateMonitoring::route('/create'),
            'edit' => EditMonitoring::route('/{record}/edit'),
        ];
    }
}
