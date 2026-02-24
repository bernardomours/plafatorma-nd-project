<?php

namespace App\Filament\Resources\Monitorings\Schemas;

use App\Enums\MonitoringStatus;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;
use Filament\Forms\Components\DatePicker;

class MonitoringForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(2)
            ->components([
                TextInput::make('setor_responsavel')
                    ->label('Setor Responsável')
                    ->maxLength(255),
                Select::make('professional_id')
                    ->label('Profissional Responsável')
                    ->relationship('professional', 'name')
                    ->preload()
                    ->searchable(),
                Select::make('status')
                    ->label('Status')
                    ->options(MonitoringStatus::class)
                    ->default(MonitoringStatus::Pending)
                    ->required(),
                Select::make('unit_id')
                    ->label('Unidade')
                    ->relationship('unit', 'city')
                    ->required(),
                Textarea::make('task')
                    ->label('Tarefa')
                    ->required()
                    ->columnSpanFull(),
                DatePicker::make('prazo')
                    ->label('Prazo')
                    ->required(),
            ]);
    }
}
