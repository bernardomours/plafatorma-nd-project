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
        $unidadesPermitidas = [];
        
        if ($user->unit_id) {
            $unidadesPermitidas[] = $user->unit_id;
        }

        $profissional = \App\Models\Professional::withoutGlobalScopes()
                            ->with('units')
                            ->where('email', $user->email)
                            ->first();
                            
        if ($profissional && $profissional->units->isNotEmpty()) {
            $idsDoProfissional = $profissional->units->pluck('id')->toArray();
            $unidadesPermitidas = array_merge($unidadesPermitidas, $idsDoProfissional);
        }
        
        $unidadesPermitidas = array_unique($unidadesPermitidas);
        
        if (empty($unidadesPermitidas)) {
            return $query->whereRaw('1 = 0'); 
        }

        $regionaisPermitidas = [];
        
        if (in_array(1, $unidadesPermitidas)) { 
            $regionaisPermitidas[] = 1;
        } 
        
        if (array_intersect([2, 3, 4], $unidadesPermitidas)) {
            array_push($regionaisPermitidas, 2, 3, 4);
        }

        return $query->whereIn('patient_id', function ($subquery) use ($regionaisPermitidas) {
            $subquery->select('id')
                     ->from('patients')
                     ->whereIn('unit_id', $regionaisPermitidas);
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
