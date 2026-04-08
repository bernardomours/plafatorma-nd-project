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
        $unidadesPermitidas = [];
        
        if ($user->unit_id == 1) {
            $unidadesPermitidas = [1];
        } elseif ($user->unit_id) {
            $unidadesPermitidas = [2, 3, 4];
        }

        $professionalsQuery = Professional::query()
            ->with('units')
            ->whereMonth('birth_date', now()->month)
            ->whereDay('birth_date', now()->day);

        if (!$user->isAdmin() && !$user->isManager()) {
            if (!empty($unidadesPermitidas)) {
                $professionalsQuery->whereHas('units', function ($q) use ($unidadesPermitidas) {
                    $q->whereIn('units.id', $unidadesPermitidas);
                });
            } else {
                $professionalsQuery->whereRaw('1 = 0'); 
            }
        }
        $professionals = $professionalsQuery->get();

        $usersQuery = User::with('units')
            ->whereMonth('birth_date', now()->month)
            ->whereDay('birth_date', now()->day);

        if (!$user->isAdmin() && !$user->isManager()) {
            if (!empty($unidadesPermitidas)) {
                $usersQuery->whereHas('units', function ($q) use ($unidadesPermitidas) {
                    $q->whereIn('units.id', $unidadesPermitidas);
                });
            } else {
                $usersQuery->whereRaw('1 = 0');
            }
        }
        $users = $usersQuery->get();

        $all_professionals = $professionals->concat($users);
        return $all_professionals->unique(function ($pessoa) {
            return $pessoa->email ?: $pessoa->name;
        })->values();
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

                TextColumn::make('unit_display')
                    ->label('Unidade')
                    ->badge()
                    ->color('info')
                    ->getStateUsing(function ($record) {
                        if ($record instanceof Professional) {
                            return $record->units->pluck('city')->toArray();
                        }
                        
                        if ($record instanceof User) {
                            return $record->units->pluck('city')->toArray();
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