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
        $unidadesPermitidas = [];
        
        // 2. Tenta pegar a unidade direto da tabela de usuários (Singular)
        if ($user->unit_id) {
            $unidadesPermitidas[] = $user->unit_id;
        }

        // 3. Busca o profissional para pegar as Múltiplas Unidades (Plural)
        $profissional = \App\Models\Professional::withoutGlobalScopes()
                            ->with('units') // Traz a nova relação de unidades
                            ->where('email', $user->email)
                            ->first();
                            
        if ($profissional && $profissional->units->isNotEmpty()) {
            // Adiciona todas as unidades que este profissional atende na lista
            $idsDoProfissional = $profissional->units->pluck('id')->toArray();
            $unidadesPermitidas = array_merge($unidadesPermitidas, $idsDoProfissional);
        }
        
        // Remove possíveis IDs repetidos
        $unidadesPermitidas = array_unique($unidadesPermitidas);
        
        // 4. Se não achou unidade nenhuma, esconde tudo por segurança
        if (empty($unidadesPermitidas)) {
            return $query->whereRaw('1 = 0'); 
        }

        // --- AS REGRAS OFICIAIS DE IDs (MOSSORÓ VS NATAL) ---
        $regionaisPermitidas = [];
        
        // Se ele pertence a Mossoró (ID 1), libera Mossoró
        if (in_array(1, $unidadesPermitidas)) { 
            $regionaisPermitidas[] = 1;
        } 
        
        // Se ele tem ALGUMA unidade da região de Natal (2, 3 ou 4), libera a região toda de Natal
        if (array_intersect([2, 3, 4], $unidadesPermitidas)) {
            array_push($regionaisPermitidas, 2, 3, 4);
        }

        // 5. A trava "Raiz" direto no banco (Imune a linhas fantasmas)
        return $query->whereIn('patient_id', function ($subquery) use ($regionaisPermitidas) {
            $subquery->select('id')
                     ->from('patients')
                     ->whereIn('unit_id', $regionaisPermitidas);
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
