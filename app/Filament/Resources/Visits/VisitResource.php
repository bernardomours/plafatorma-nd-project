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

        // 1. Libera tudo se for o Administrador Geral
        if (auth()->user()?->is_admin) {
            return $query;
        }

        // 2. Define as unidades do usuário logado
        $userUnitId = auth()->user()?->unit_id;
        
        // ATENÇÃO: Troque os números 4, 1, 2 e 3 pelos IDs reais das suas unidades!
        if ($userUnitId == 1) { 
            $unidadesPermitidas = [1];
        } else {
            $unidadesPermitidas = [2, 3, 4];
        }

        // 3. A super trava:
        return $query->whereHas('patient', function (Builder $q) use ($unidadesPermitidas) {
            $q->withoutGlobalScopes() // Ignora o UnitScope do Patient (impede que o Laravel se confunda)
              ->withTrashed() // Ignora a Lixeira (garante que visitas de pacientes de alta continuem visíveis)
              ->whereIn('unit_id', $unidadesPermitidas); // Trava a exibição pela unidade exata
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
