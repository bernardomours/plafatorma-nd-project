<?php

namespace App\Filament\Resources\Units;

use App\Filament\Resources\Units\Pages\CreateUnit;
use App\Filament\Resources\Units\Pages\EditUnit;
use App\Filament\Resources\Units\Pages\ListUnits;
use App\Filament\Resources\Units\Schemas\UnitForm;
use App\Filament\Resources\Units\Tables\UnitsTable;
use App\Models\Unit;
use BackedEnum;
use UnitEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class UnitResource extends Resource
{
    protected static ?string $model = Unit::class;
    protected static ?string $modelLabel = 'Unidade';
    protected static ?string $pluralModelLabel = 'Unidades';
    protected static ?string $navigationLabel = 'Unidades';
    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-building-office';
    protected static string|UnitEnum|null $navigationGroup = 'Administração';


    public static function form(Schema $schema): Schema
    {
        return UnitForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return UnitsTable::configure($table);
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
            'index' => ListUnits::route('/'),
            'create' => CreateUnit::route('/create'),
            'edit' => EditUnit::route('/{record}/edit'),
        ];
    }
}
