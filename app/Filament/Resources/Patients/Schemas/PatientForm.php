<?php

namespace App\Filament\Resources\Patients\Schemas;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Builder;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Get;

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
                    ->unique(ignoreRecord: true)
                    ->validationMessages([
                        'unique' => 'Esse CPF já está cadastrado.'
                    ])
                    ->helperText('Coloque apenas os dígitos'),
                TextInput::make('agreement_number')
                    ->label('Carteira')
                    ->required(),
                TextInput::make('guardian_name')
                    ->label('Nome do Responsável')
                    ->helperText('Caso não possua esse dado, pode deixar em branco'),
                TextInput::make('guardian_phone')
                    ->label('Contato do Responsável')
                    ->helperText('Caso não possua esse dado, pode deixar em branco')
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
                                    
                                    // 1. Filtra rigorosamente para mostrar SÓ Coordenadores
                                    $query->where('role', 'coordinator'); 
                                    
                                    // 2. Filtro de unidade
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
                                    
                                    // 1. Filtra rigorosamente para mostrar SÓ Supervisores
                                    $query->where('role', 'supervisor');
                                    
                                    // 2. Filtro de unidade
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