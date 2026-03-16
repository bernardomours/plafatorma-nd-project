<?php

namespace App\Filament\Resources\Activities\Pages;

use App\Filament\Resources\Activities\ActivityResource;
use Filament\Actions;
use Filament\Resources\Pages\ManageRecords;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Illuminate\Database\Eloquent\Builder;

class ManageActivities extends ManageRecords
{
    protected static string $resource = ActivityResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // Botão de criar removido
        ];
    }


    public function getTabs(): array
    {
        return [
            'todos' => Tab::make('Todos os Registros')
                ->icon('heroicon-m-list-bullet'),

            'atualizacoes' => Tab::make('Atualizações')
                ->icon('heroicon-m-pencil-square')
                ->modifyQueryUsing(fn (\Illuminate\Database\Eloquent\Builder $query) => $query->where('event', 'updated')),

            'entradas_saidas' => Tab::make('Entradas e Saídas')
                ->icon('heroicon-m-arrows-right-left')
                ->modifyQueryUsing(function (\Illuminate\Database\Eloquent\Builder $query) {
                    return $query
                        ->where('subject_type', \App\Models\MovementHistory::class)
                        ->orWhere(function ($q) {
                            $q->whereIn('subject_type', [
                                \App\Models\Patient::class,
                                \App\Models\Professional::class
                            ])->whereIn('event', ['created', 'deleted', 'restored']);
                        });
                }),
        ];
    }
}