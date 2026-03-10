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

        // 1. Catraca VIP para o Admin
        if (auth()->user()?->is_admin) {
            return $query;
        }

        $user = auth()->user();
        
        // 2. Tenta pegar a unidade direto da tabela de usuários
        $userUnitId = $user->unit_id;

        // 3. Se estiver vazio, caçamos o profissional ignorando os Scopes de segurança temporariamente!
        if (!$userUnitId) {
            $profissional = \App\Models\Professional::withoutGlobalScopes()
                                ->where('email', $user->email)
                                ->first();
                                
            $userUnitId = $profissional?->unit_id;
        }
        
        // 4. Se não achou unidade nenhuma, esconde tudo por segurança
        if (!$userUnitId) {
            return $query->whereRaw('1 = 0'); 
        }

        // --- AS REGRAS OFICIAIS DE IDs ---
        
        // ID 1 = Mossoró
        if ($userUnitId == 1) { 
            $unidadesPermitidas = [1];
        } else {
            // IDs 2, 3 e 4 = Região de Natal e outras
            $unidadesPermitidas = [2, 3, 4]; 
        }

        // 5. A trava "Raiz" direto no banco (Imune a linhas fantasmas e bloqueios do UnitScope)
        return $query->whereIn('patient_id', function ($subquery) use ($unidadesPermitidas) {
            $subquery->select('id')
                     ->from('patients')
                     ->whereIn('unit_id', $unidadesPermitidas);
        });
    }

    public static function canViewAny(): bool
    {
        return true; // Todo mundo vê!
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
