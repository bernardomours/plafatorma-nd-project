<?php

namespace App\Filament\Resources\RequestedServices;

use App\Filament\Resources\RequestedServices\Pages\CreateRequestedService;
use App\Filament\Resources\RequestedServices\Pages\EditRequestedService;
use App\Filament\Resources\RequestedServices\Pages\ListRequestedServices;
use App\Filament\Resources\RequestedServices\Schemas\RequestedServiceForm;
use App\Filament\Resources\RequestedServices\Tables\RequestedServicesTable;
use App\Models\RequestedService;
use BackedEnum;
use UnitEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema; // Corrected namespace for Filament v2
use Filament\Tables\Table;
use Filament\Support\Icons\Heroicon;

class RequestedServiceResource extends Resource
{
    protected static ?string $model = RequestedService::class;

    protected static ?string $modelLabel = 'CH - Solicitada';
    protected static ?string $pluralModelLabel = 'CH - Solicitada';
    protected static ?string $navigationLabel = 'CH - Solicitada';
    protected static string|UnitEnum|null $navigationGroup = 'Administração';
    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-calendar-days';

    public static function form(Schema $schema): Schema
    {
        // Correctly use the `components` method with the array from getFormSchema
        return $schema->components(RequestedServiceForm::getFormSchema());
    }

    public static function table(Table $table): Table
    {
        return RequestedServicesTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function canViewAny(): bool
    {
        // Verifica a coluna 'is_admin' que você criou na migration
        return auth()->user()?->is_admin ?? false;
    }

    public static function getPages(): array
    {
        return [
            'index' => ListRequestedServices::route('/'),
            'create' => CreateRequestedService::route('/create'),
            'edit' => EditRequestedService::route('/{record}/edit'),
        ];
    }
}
