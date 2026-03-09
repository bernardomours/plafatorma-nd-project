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

        $userUnitId = auth()->user()?->unit_id;
        
        if (in_array($userUnitId, [2, 3, 4])) {
            $unidadesPermitidas = [2, 3, 4];
        } else {
            $unidadesPermitidas = [$userUnitId]; 
        }

        // 3. A Trava "Raiz"
        // Em vez de usar as relações do Model, vamos direto na tabela do banco.
        // Isso impede que qualquer SoftDelete ou UnitScope crie linhas fantasmas.
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
