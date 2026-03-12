<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Filament\Support\Facades\FilamentView;
use Filament\View\PanelsRenderHook;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\URL;
use App\Models\Appointment;
use App\Observers\AppointmentObserver;
use Illuminate\Contracts\View\View;
use App\Models\Visit;
use App\Observers\VisitObserver;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        Appointment::observe(AppointmentObserver::class);
        Visit::observe(VisitObserver::class);

        FilamentView::registerRenderHook(
            PanelsRenderHook::HEAD_END,
            fn (): View => view('filament.dark-mode-table-fix'),
        );
        
        FilamentView::registerRenderHook(
            PanelsRenderHook::SIDEBAR_FOOTER,
            fn (): string => Blade::render('@livewire("App\Livewire\SidebarFooter")'),
        );

        if($this->app->environment('production')) {
            URL::forceScheme('https');
        }
    }
}
