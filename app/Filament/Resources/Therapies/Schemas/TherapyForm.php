<?php

namespace App\Filament\Resources\Therapies\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class TherapyForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->required(),
            ]);
    }
}
