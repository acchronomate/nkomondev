<?php

namespace App\Providers\Filament;

use App\Http\Middleware\HostMiddleware;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Navigation\NavigationBuilder;
use Filament\Navigation\NavigationGroup;
use Filament\Navigation\NavigationItem;
use Filament\Pages;
use Filament\Pages\Dashboard;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\Widgets;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;

class HostPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->id('host')
            ->path('host')
            ->login()
            ->registration()
            ->profile()
            ->colors([
                'primary' => Color::Blue,
                'danger' => Color::Rose,
                'gray' => Color::Gray,
                'info' => Color::Sky,
                'success' => Color::Emerald,
                'warning' => Color::Orange,
            ])
            ->font('Inter')
            ->brandName('NKOMON Hébergeur')
            ->brandLogo(asset('images/logos/email_logo.png'))
            ->brandLogoHeight('3rem')
            ->favicon(asset('images/logos/favicon.jpg'))
            ->discoverResources(in: app_path('Filament/Host/Resources'), for: 'App\\Filament\\Host\\Resources')
            ->discoverPages(in: app_path('Filament/Host/Pages'), for: 'App\\Filament\\Host\\Pages')
            ->pages([
                Pages\Dashboard::class,
            ])
            ->discoverWidgets(in: app_path('Filament/Host/Widgets'), for: 'App\\Filament\\Host\\Widgets')
            ->widgets([
                // Widgets\AccountWidget::class,
                // Widgets\FilamentInfoWidget::class,
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
                HostMiddleware::class,
            ])
            ->navigation(function (NavigationBuilder $builder): NavigationBuilder {
                return $builder
                    ->items([
                        NavigationItem::make('Tableau de bord')
                            ->sort(0)
                            ->icon('heroicon-o-home')
                            ->isActiveWhen(fn (): bool => request()->routeIs('filament.admin.pages.dashboard'))
                            ->url(fn (): string => Dashboard::getUrl()),
                    ])
                    ->groups([
                        NavigationGroup::make('Mes hébergements')
                            ->icon('heroicon-o-building-office')
                            ->items([

                            ]),
                        NavigationGroup::make('Réservations')
                            ->icon('heroicon-o-calendar-days'),
                        NavigationGroup::make('Finances')
                            ->icon('heroicon-o-banknotes'),
                        NavigationGroup::make('Avis clients')
                            ->icon('heroicon-o-star'),
                    ]);
            })
            ->sidebarCollapsibleOnDesktop()
            ->maxContentWidth('full');
    }
}
