<?php

namespace App\Filament\Resources\Forwardeds;

use App\Filament\Resources\Forwardeds\Pages\CreateForwarded;
use App\Filament\Resources\Forwardeds\Pages\EditForwarded;
use App\Filament\Resources\Forwardeds\Pages\ListForwardeds;
use App\Filament\Resources\Forwardeds\Schemas\ForwardedForm;
use App\Filament\Resources\Forwardeds\Tables\ForwardedsTable;
use App\Models\Forwarded;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class ForwardedResource extends Resource
{
    protected static ?string $model = Forwarded::class;

    protected static string|UnitEnum|null $navigationGroup = 'Frequência';
    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-arrow-right-circle';
    protected static ?string $modelLabel = 'Encaminhamento';
    protected static ?string $pluralModelLabel = 'Encaminhamentos';
    protected static ?string $navigationLabel = 'Encaminhamentos';

    public static function form(Schema $schema): Schema
    {
        return ForwardedForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ForwardedsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListForwardeds::route('/'),
            'create' => CreateForwarded::route('/create'),
            'edit' => EditForwarded::route('/{record}/edit'),
        ];
    }
}
