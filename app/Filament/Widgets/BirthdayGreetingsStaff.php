<?php

namespace App\Filament\Widgets;

use App\Models\Professional;
use App\Models\User;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use Illuminate\Database\Eloquent\Collection;

class BirthdayGreetingsStaff extends TableWidget
{
    protected static ?string $pollingInterval = null;

    public function getTableRecords(): Collection
    {
        // 1. Corrigido: Profissionais agora puxam 'units' (plural)
        $professionals = Professional::with('units')
            ->whereMonth('birth_date', now()->month)
            ->whereDay('birth_date', now()->day)
            ->get();

        // 2. Mantido: Users continuam puxando 'unit' (singular)
        $users = User::with('unit')
            ->whereMonth('birth_date', now()->month)
            ->whereDay('birth_date', now()->day)
            ->get();

        return $professionals->concat($users);
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(Professional::query()->whereRaw('1 = 0')) 
            ->heading('Equipe')
            ->description('🎂 Aniversariantes do Dia')
            ->columns([
                TextColumn::make('name')
                    ->label('Nome')
                    ->weight('bold')
                    ->icon('heroicon-m-cake')
                    ->iconColor('primary'),

                // 3. A Coluna Inteligente: Trata as diferenças de relações
                TextColumn::make('unit_display')
                    ->label('Unidade')
                    ->badge()
                    ->color('info')
                    ->getStateUsing(function ($record) {
                        // Se for um Profissional, pega a lista de unidades e transforma em Array
                        if ($record instanceof Professional) {
                            return $record->units->pluck('city')->toArray();
                        }
                        
                        // Se for um User e tiver unidade vinculada, devolve num Array
                        if ($record instanceof User && $record->unit) {
                            return [$record->unit->city];
                        }
                        
                        return null;
                    })
                    ->placeholder('Sem unidade'),
            ])
            ->emptyStateHeading('Ninguém soprando velinhas hoje')
            ->emptyStateIcon('heroicon-o-calendar-days')
            ->paginated(false);
    }
}