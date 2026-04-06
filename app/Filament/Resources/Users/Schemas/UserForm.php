<?php

namespace App\Filament\Resources\Users\Schemas;

use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\DatePicker;

class UserForm
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

                TextInput::make('email')
                    ->label('E-mail')
                    ->email()
                    ->required(),

                Select::make('units')
                    ->label('Unidade(s)')
                    ->relationship('units', 'city') 
                    ->multiple()
                    ->placeholder('Acesso Global')
                    ->searchable()
                    ->preload(),
                
                TextInput::make('password')
                    ->label('Senha')
                    ->password()
                    ->required(fn (string $context): bool => $context === 'create') 
                    ->dehydrated(fn ($state) => filled($state)),

                Select::make('role')
                    ->label('Nível de Acesso (Cargo)')
                    ->options([
                        'admin'          => 'Administrador',
                        'manager'        => 'Gerência',
                        'administrative' => 'Administrativo',
                        'coordinator'    => 'Coordenador',
                        'supervisor'     => 'Supervisor',
                    ])
                    ->default('administrative')
                    ->required()
                    ->native(false),
            ]);
    }
}