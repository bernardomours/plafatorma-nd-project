<?php

namespace App\Providers\Filament;

use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Pages\Dashboard;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\Widgets\AccountWidget;
use Filament\Widgets\FilamentInfoWidget;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;

class ProducaoPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->id('producao')
            ->path('producao')
            ->login()
            ->topNavigation()
            ->viteTheme('resources/css/filament/admin/theme.css')
            ->brandLogo(url('/images/icon-nd.png'))
            ->brandLogoHeight('7rem')
            ->brandName('Plataforma ND - PRODUÇÃO')
            ->favicon(url('/images/favicon.png'))
            ->colors([
                'primary' => Color::hex('#014bde'),
            ])
            ->discoverResources(in: app_path('Filament/Producao/Resources'), for: 'App\Filament\Producao\Resources')
            ->discoverPages(in: app_path('Filament/Producao/Pages'), for: 'App\Filament\Producao\Pages')
            ->discoverClusters(in: app_path('Filament/Producao/Clusters'), for: 'App\\Filament\\Producao\\Clusters')
            ->pages([
                Dashboard::class,
            ])
            ->discoverWidgets(in: app_path('Filament/Producao/Widgets'), for: 'App\Filament\Producao\Widgets')
            ->widgets([
                // AccountWidget::class,
                // FilamentInfoWidget::class,
            ])
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                VerifyCsrfToken::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ])
            ->authMiddleware([
                Authenticate::class,
            ]);
    }
}
