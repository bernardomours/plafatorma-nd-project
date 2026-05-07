<?php

namespace App\Filament\Resources\Holidays\Schemas;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class HolidayForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->schema([
                Section::make('Dados de Recesso')
                    ->schema([
                        DatePicker::make('date')
                            ->label('Data')
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->displayFormat('d/m/Y'),
                            
                        TextInput::make('name')
                            ->label('Descrição (Feriado ou Motivo do Recesso)')
                            ->required()
                            ->maxLength(255)
                            ->placeholder('Ex: Carnaval, Sexta-feira Santa, Recesso da Clínica...'),
                    ])->columns(2),
            ]);
    }
}