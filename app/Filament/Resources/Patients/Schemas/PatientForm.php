<?php

namespace App\Filament\Resources\Patients\Schemas;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Builder;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Get;
use App\Rules\CpfValidate;

class PatientForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->label('Nome')
                    ->required(),
                DatePicker::make('birth_date')
                    ->label('Data de Nascimento')
                    ->required(),
                TextInput::make('cpf')
                    ->label('CPF')
                    ->required()
                    ->mask('999.999.999-99')
                    ->rule(new CpfValidate())
                    ->maxLength(14)
                    ->unique(ignoreRecord: true)
                    ->validationMessages([
                        'unique' => 'Esse CPF já está cadastrado.'
                    ])
                    ->helperText('Digite apenas os números, o sistema formata automaticamente.'),
                TextInput::make('agreement_number')
                    ->label('Carteira')
                    ->required(),
                TextInput::make('guardian_name')
                    ->label('Nome do Responsável'),
                TextInput::make('guardian_phone')
                    ->label('Contato do Responsável')
                    ->tel(),
                Select::make('unit_id')
                    ->label('Unidade')
                    ->relationship('unit', 'city')
                    ->live()
                    ->required(),
                    
                Select::make('agreement_id')
                    ->label('Convênio')
                    ->relationship(
                        name: 'agreement',
                        titleAttribute: 'name',
                    )
                    ->preload()
                    ->live()
                    ->required(),
                
                Repeater::make('patientServices')
                    ->relationship()
                    ->label('Equipe de Acompanhamento (Supervisão e Coordenação)')
                    ->schema([
                        Select::make('service_type_id')
                            ->label('Ambiente / Tipo de Serviço')
                            ->relationship('serviceType', 'name') 
                            ->required()
                            ->disableOptionsWhenSelectedInSiblingRepeaterItems(),
                
                        Select::make('coordinator_id')
                            ->label('Coordenador')
                            ->relationship(
                                name: 'coordinator',
                                titleAttribute: 'name',
                                modifyQueryUsing: function (Builder $query, $get) {
                                    $pacienteUnitId = $get('../../unit_id');                                   
                                    $query->where('role', 'coordinator'); 
                                    
                                    if ($pacienteUnitId) {
                                        $query->whereHas('units', fn ($subQuery) => $subQuery->where('units.id', $pacienteUnitId));
                                    }
                                    
                                    return $query;
                                }
                            )
                            ->searchable()
                            ->preload(),
                
                        Select::make('supervisor_id')
                            ->label('Supervisor')
                            ->relationship(
                                name: 'supervisor',
                                titleAttribute: 'name',
                                modifyQueryUsing: function (Builder $query, $get) {
                                    $pacienteUnitId = $get('../../unit_id');
                                    
                                    $query->where('role', 'supervisor');
                                    
                                    if ($pacienteUnitId) {
                                        $query->whereHas('units', fn ($subQuery) => $subQuery->where('units.id', $pacienteUnitId));
                                    }
                                    
                                    return $query;
                                }
                            )
                            ->searchable()
                            ->preload(),
                    ])
                    ->columns(3)
                    ->addActionLabel('Adicionar Nova Supervisão/Coordenação')
                    ->defaultItems(1)
                    ->columnSpanFull(),
            ]);
    }
}