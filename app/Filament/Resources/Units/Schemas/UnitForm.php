<?php

namespace App\Filament\Resources\Units\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class UnitForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->required(),
                TextInput::make('cnpj')
                    ->required(),
                TextInput::make('street')
                    ->required(),
                TextInput::make('neighborhood')
                    ->required(),
                TextInput::make('number')
                    ->required(),
                TextInput::make('city')
                    ->required(),
            ]);
    }
}
