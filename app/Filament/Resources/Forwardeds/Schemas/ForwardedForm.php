<?php

namespace App\Filament\Resources\Forwardeds\Schemas;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class ForwardedForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->label('Nome')
                    ->required(),
                DatePicker::make('forwarding_date')
                    ->label('Data')
                    ->required(),
                Select::make('city')
                    ->label('Cidade')
                    ->options([
                        'mossoro' => 'Mossoró',
                        'natal' => 'Natal',
                        'joao-camara' => 'João Câmara',
                        'santa-cruz' => 'Santa Cruz',
                    ])
                    ->required(),
                TextInput::make('status')
                    ->required(),
                Select::make('agreement_id')
                    ->label('Convênio')
                    ->relationship('agreement', 'name')
                    ->searchable()
                    ->preload(),
            ]);
    }
}
