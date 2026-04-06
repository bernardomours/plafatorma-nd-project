<?php

namespace App\Filament\Resources\Patients;

use App\Filament\Resources\Patients\Pages\CreatePatient;
use App\Filament\Resources\Patients\Pages\EditPatient;
use App\Filament\Resources\Patients\Pages\ListPatients;
use App\Filament\Resources\Patients\Pages\PatientSchedule;
use App\Filament\Resources\Patients\Schemas\PatientForm;
use App\Filament\Resources\Patients\Tables\PatientsTable;
use App\Filament\Resources\Patients\Pages\PatientServices;
use App\Models\Patient;
use BackedEnum;
use UnitEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Filament\Actions\Action;     
use Filament\Actions\EditAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\ActionGroup;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class PatientResource extends Resource
{
    protected static ?string $model = Patient::class;
    protected static ?string $modelLabel = 'Paciente';
    protected static ?string $pluralModelLabel = 'Pacientes';
    protected static ?string $navigationLabel = 'Pacientes';
    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-user-group';
    protected static string|UnitEnum|null $navigationGroup = 'Ocupação';


    public static function form(Schema $schema): Schema
    {
        return PatientForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        $table = PatientsTable::configure($table);

        return $table
            ->actions([
                ActionGroup::make([
                    EditAction::make(), 
                    Action::make('agenda')
                        ->label('Agenda')
                        ->icon('heroicon-o-calendar')
                        ->color('info')
                        ->url(fn ($record): string => PatientSchedule::getUrl(['record' => $record]))
                        ->disabled(fn ($record) => !$record->is_active),
                    Action::make('services')
                        ->label('Cargas Horárias')
                        ->icon('heroicon-o-clipboard-document-check')
                        ->color('success')
                        ->url(fn (Patient $record): string => static::getUrl('services', ['record' => $record]))
                        ->disabled(fn ($record) => !$record->is_active),
                    //DeleteAction::make(),
                ])
                
                ->icon('heroicon-m-ellipsis-vertical') 
                ->tooltip('Opções')
            ]);
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();
        $user = auth()->user();

        if ($user->isAdmin() || $user->isManager()) {
            return $query;
        }

        $userUnits = $user->units->pluck('id')->toArray(); 
        $regioesPermitidas = [];
        
        if (in_array(1, $userUnits)) {
            $regioesPermitidas[] = 1; 
        } 
        
        if (array_intersect([2, 3, 4], $userUnits)) {
            array_push($regioesPermitidas, 2, 3, 4); 
        }

        if (empty($regioesPermitidas)) {
            return $query->whereRaw('1 = 0'); 
        }
        
        return $query->whereIn('unit_id', $regioesPermitidas);
    }

    public static function canViewAny(): bool
    {
        return true;
    }

    public static function getPages(): array
    {
        return [
            'index' => ListPatients::route('/'),
            'create' => CreatePatient::route('/create'),
            'edit' => EditPatient::route('/{record}/edit'),
            'schedule' => PatientSchedule::route('/{record}/schedule'), 
            'services' => PatientServices::route('/{record}/services'),
        ];
    }

}