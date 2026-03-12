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
        $user = auth()->user();
        
        // 1. Define a "Região" de quem está logado
        $unidadesPermitidas = [];
        if ($user->unit_id == 1) {
            $unidadesPermitidas = [1]; // Mossoró vê apenas Mossoró
        } else {
            $unidadesPermitidas = [2, 3, 4]; // Região Natal vê Natal, JC e Santa Cruz
        }

        // 2. Busca Profissionais IGNORANDO o bloqueio padrão e aplicando a regra da região
        $professionalsQuery = Professional::withoutGlobalScopes()
            ->with('units')
            ->whereMonth('birth_date', now()->month)
            ->whereDay('birth_date', now()->day);

        // Se NÃO for Admin, aplica o filtro da região
        if (!$user->is_admin) {
            $professionalsQuery->whereHas('units', function ($q) use ($unidadesPermitidas) {
                $q->whereIn('units.id', $unidadesPermitidas);
            });
        }
        $professionals = $professionalsQuery->get();

        // 3. Busca Usuários da Equipe
        $usersQuery = User::with('unit')
            ->whereMonth('birth_date', now()->month)
            ->whereDay('birth_date', now()->day);

        // Se NÃO for Admin, aplica o filtro da região
        if (!$user->is_admin) {
            $usersQuery->whereIn('unit_id', $unidadesPermitidas);
        }
        $users = $usersQuery->get();

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