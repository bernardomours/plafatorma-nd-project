<?php

namespace App\Filament\Resources\Visits;

use App\Filament\Resources\Visits\Pages\CreateVisit;
use App\Filament\Resources\Visits\Pages\EditVisit;
use App\Filament\Resources\Visits\Pages\ListVisits;
use App\Filament\Resources\Visits\Schemas\VisitForm;
use App\Filament\Resources\Visits\Tables\VisitsTable;
use App\Models\Visit;
use BackedEnum;
use UnitEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class VisitResource extends Resource
{
    protected static ?string $model = Visit::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-eye';

    protected static ?string $modelLabel = 'Acompanhamento';
    protected static ?string $pluralModelLabel = 'Acompanhamentos';
    protected static ?string $navigationLabel = 'Acompanhamentos';
    protected static ?int $navigationSort = 1;

    protected static string|UnitEnum|null $navigationGroup = 'Coordenação/Supervisão';

    public static function form(Schema $schema): Schema
    {
        return VisitForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return VisitsTable::configure($table);
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();
        $user = auth()->user();

        if ($user->isAdmin() || $user->isManager()) {
            return $query;
        }

        $regionaisPermitidas = [];
        if ($user->unit_id == 1) {
            $regionaisPermitidas = [1];
        } elseif ($user->unit_id) {
            $regionaisPermitidas = [2, 3, 4];
        } else {
            return $query->whereRaw('1 = 0'); 
        }

        return $query->whereHas('patient', function ($subquery) use ($regionaisPermitidas) {
            $subquery->whereIn('unit_id', $regionaisPermitidas);
        });
    }

    public static function canViewAny(): bool
    {
        return true;
    }

    /**
     * @return array<int, class-string|string>|
     */
    public static function getPages(): array
    {
        return [
            'index' => ListVisits::route('/'),
            'create' => CreateVisit::route('/create'),
            'edit' => EditVisit::route('/{record}/edit'),
        ];
    }
}
