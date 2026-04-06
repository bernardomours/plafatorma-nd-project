<?php

namespace App\Filament\Resources\Professionals;

use App\Filament\Resources\Professionals\Pages\CreateProfessional;
use App\Filament\Resources\Professionals\Pages\EditProfessional;
use App\Filament\Resources\Professionals\Pages\ListProfessionals;
use App\Filament\Resources\Professionals\Schemas\ProfessionalForm;
use App\Filament\Resources\Professionals\Tables\ProfessionalsTable;
use App\Models\Professional;
use BackedEnum;
use UnitEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class ProfessionalResource extends Resource
{
    protected static ?string $model = Professional::class;

    protected static string|UnitEnum|null $navigationGroup = 'Ocupação';

    protected static ?string $modelLabel = 'Profissional';
    protected static ?string $pluralModelLabel = 'Profissionais';
    protected static ?string $navigationLabel = 'Profissionais';
    
    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-user-circle';

    public static function form(Schema $schema): Schema
    {
        return ProfessionalForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ProfessionalsTable::configure($table);
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();
        $user = auth()->user();

        if ($user->isAdmin() || $user->isManager()) {
            return $query;
        }

        $regioesPermitidas = [];
        
        if ($user->unit_id == 1) {
            $regioesPermitidas = [1]; 
        } elseif (in_array($user->unit_id, [2, 3, 4])) {
            $regioesPermitidas = [2, 3, 4]; 
        } else {
            return $query->whereRaw('1 = 0'); 
        }

        return $query->whereHas('units', function ($q) use ($regioesPermitidas) {
            $q->whereIn('unit_id', $regioesPermitidas);
        });
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function canViewAny(): bool
    {
        $user = auth()->user();
        return $user->isAdmin() || $user->isManager() || $user->isAdministrative();
    }

    public static function getPages(): array
    {
        return [
            'index' => ListProfessionals::route('/'),
            'create' => CreateProfessional::route('/create'),
            'edit' => EditProfessional::route('/{record}/edit'),
        ];
    }
}