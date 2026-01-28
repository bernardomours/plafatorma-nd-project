<?php

namespace App\Filament\Resources\Users\Schemas;

use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;
use Filament\Forms\Components\Select;

class UserForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->label('Nome')
                    ->required(),

                TextInput::make('email')
                    ->label('E-mail')
                    ->email()
                    ->required(),

                Select::make('unit_id')
                    ->label('Unidade')
                    ->relationship('unit', 'name') // Agora o Eloquent encontrará este método 'unit()'
                    ->placeholder('Acesso Global (Admin)')
                    ->searchable()
                    ->preload(),
                
                TextInput::make('password')
                    ->label('Senha')
                    ->password()
                    // Obrigatório apenas ao criar; ao editar, se ficar vazio, mantém a senha atual
                    ->required(fn (string $context): bool => $context === 'create') 
                    ->dehydrated(fn ($state) => filled($state)),

                Toggle::make('is_admin')
                    ->label('Administrador')
                    ->default(false),
            ]);
    }
}
