<?php

namespace App\Filament\Resources\Forwardeds\Schemas;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;
use Filament\Forms\Components\RichEditor;

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
                Select::make('unit_id')
                    ->label('Unidade')
                    ->relationship('unit', 'city')
                    ->searchable()
                    ->preload()
                    ->required(),
                RichEditor::make('status')
                    ->required(),
                RichEditor::make('status_return')
                ->label('Retorno sobre encaminhamento')
                ->helperText('Este campo será preenchido após resultado do encaminhamento'),
                Select::make('agreement_id')
                    ->label('Convênio')
                    ->relationship('agreement', 'name')
                    ->searchable()
                    ->preload(),
            ]);
    }
}
