<?php

namespace App\Filament\Resources\Visits\Schemas;

use App\Enums\ProfessionalRole;
use App\Enums\VisitStatus;
use App\Enums\VisitType;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Builder;

class VisitForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->schema([
            Select::make('patient_id')
                ->relationship('patient', 'name')
                ->label('Paciente')
                ->searchable()
                ->preload()
                ->required(),

            Select::make('professional_id')
                ->relationship(
                    'professional',
                    'name',
                    fn (Builder $query) => $query->whereIn('role', [
                        ProfessionalRole::Coordinator,
                        ProfessionalRole::Supervisor,
                    ])
                )
                ->label('Profissional (Coord/Superv)')
                ->searchable()
                ->preload(),

            DatePicker::make('happened_at')
                ->label('Data da Visita')
                ->nullable(),

            Select::make('type')
                ->label('Tipo')
                ->options(VisitType::class)
                ->required(),

            Select::make('status')
                ->label('Status')
                ->options(VisitStatus::class)
                ->required()
                ->default(VisitStatus::Pending),
            
            Select::make('service_type_id')
                ->label('Ambiente / Tipo de Serviço')
                ->relationship('serviceType', 'name')
                ->required(),

            Textarea::make('notes')
                ->label('Observações')
                ->columnSpanFull(),
        ]);
    }
}
