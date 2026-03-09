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

    protected static ?string $modelLabel = 'Coordenação/Supervisão';
    protected static ?string $pluralModelLabel = 'Coordenação/Supervisão';
    protected static ?string $navigationLabel = 'Coordenação/Supervisão';

    protected static string|UnitEnum|null $navigationGroup = 'Frequência';

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

        if (auth()->user()?->is_admin) {
            return $query;
        }

        $user = auth()->user();
        $userUnitId = $user->unit_id ?? $user->professional?->unit_id;
        
        if (!$userUnitId) {
            return $query->whereRaw('1 = 0');
        }

        if ($userUnitId == 1) {
            $unidadesPermitidas = [1];
        } else {
            $unidadesPermitidas = [2, 3, 4]; //
        }

        return $query->whereIn('patient_id', function ($subquery) use ($unidadesPermitidas) {
            $subquery->select('id')
                     ->from('patients')
                     ->whereIn('unit_id', $unidadesPermitidas);
        });
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
