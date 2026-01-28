<?php

namespace App\Livewire;

use Livewire\Component;
use Filament\Forms\Contracts\HasForms;
use Filament\Actions\Contracts\HasActions;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Action;

class SidebarFooter extends Component implements HasForms, HasActions
{
    use InteractsWithActions;
    use InteractsWithForms;

    public function helpAction(): Action
    {
        return Action::make('help')
            ->label('Encontrou um erro? Contate o Suporte')
            ->icon('heroicon-o-question-mark-circle')
            ->url('https://wa.me/5584991470939', shouldOpenInNewTab: true)
            ->color('gray');
    }

    public function render()
    {
        return view('livewire.sidebar-footer');
    }
}
