<?php

namespace App\Filament\Resources\Users\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Actions\RestoreAction; 
use Filament\Actions\EditAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\RestoreBulkAction; 
use Filament\Actions\ForceDeleteBulkAction; 
use Filament\Tables\Filters\TrashedFilter; 

class UsersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('Nome')
                    ->searchable(),
                
                TextColumn::make('email')
                    ->label('E-mail')
                    ->searchable(),
                
                TextColumn::make('units.city')
                    ->badge()
                    ->label('Unidade(s)')
                    ->searchable(), 
                TextColumn::make('role')
                    ->label('Função')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'admin'          => 'Administrador',
                        'manager'        => 'Gerência Geral',
                        'administrative' => 'Administrativo',
                        'coordinator'    => 'Coordenador',
                        'supervisor'     => 'Supervisor',
                        default          => 'Desconhecido',
                    })
                    ->color(fn (string $state): string => match ($state) {
                        'admin'          => 'danger', 
                        'manager'        => 'warning',
                        'coordinator'    => 'info',
                        'supervisor'     => 'success',
                        'administrative' => 'gray',
                        default          => 'gray',
                    }),

                TextColumn::make('email_verified_at')
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->dateTime()
                    ->sortable(),
                
                TextColumn::make('created_at')
                    ->label('Data de Registro')
                    ->timezone('America/Fortaleza')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                
                TextColumn::make('updated_at')
                    ->label('Última atualização')
                    ->timezone('America/Fortaleza')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                TrashedFilter::make(),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(), 
                RestoreAction::make(), 
            ])
            ->toolbarActions([
                    BulkActionGroup::make([
                    DeleteBulkAction::make(),
                    RestoreBulkAction::make(),
                    ForceDeleteBulkAction::make(),
                ]),
            ]);
    }
}