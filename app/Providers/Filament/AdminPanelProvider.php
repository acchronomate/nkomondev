<?php

namespace App\Providers\Filament;

use App\Filament\Admin\Resources\AdminResource;
use App\Filament\Admin\Resources\CityResource;
use App\Filament\Admin\Resources\ClientResource;
use App\Filament\Admin\Resources\CountryResource;
use App\Filament\Admin\Resources\CurrencyResource;
use App\Filament\Admin\Resources\DistrictResource;
use App\Filament\Admin\Resources\HostResource;
use App\Filament\Admin\Resources\PartnerResource;
use App\Filament\Admin\Resources\SettingResource;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Navigation\NavigationGroup;
use Filament\Pages;
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
use App\Http\Middleware\AdminMiddleware;
use Filament\Navigation\NavigationBuilder;
use Filament\Navigation\NavigationItem;
use Filament\Pages\Dashboard;

class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('admin')
            ->path('admin')
            ->login()
            ->colors([
                'primary' => Color::Amber,
                'danger' => Color::Rose,
                'gray' => Color::Gray,
                'info' => Color::Blue,
                'success' => Color::Emerald,
                'warning' => Color::Orange,
            ])
            ->font('Inter')
            ->brandName('NKOMON Admin')
            ->brandLogo(asset('images/logos/email_logo.png'))
            ->brandLogoHeight('3rem')
            ->favicon(asset('vendor/telescope/favicon.ico'))
            ->discoverResources(in: app_path('Filament/Admin/Resources'), for: 'App\\Filament\\Admin\\Resources')
            ->discoverPages(in: app_path('Filament/Admin/Pages'), for: 'App\\Filament\\Admin\\Pages')
            ->pages([
                Pages\Dashboard::class,
            ])
            ->discoverWidgets(in: app_path('Filament/Admin/Widgets'), for: 'App\\Filament\\Admin\\Widgets')
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
                AdminMiddleware::class,
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
                        NavigationGroup::make('Gestion des utilisateurs')
                            ->icon('heroicon-o-users')
                            ->items([
                                ...AdminResource::getNavigationItems(),
                                ...HostResource::getNavigationItems(),
                                ...ClientResource::getNavigationItems()
                            ]),
                        NavigationGroup::make('Gestion des localisations')
                            ->icon('heroicon-o-globe-alt')
                            ->items([
                                ...CountryResource::getNavigationItems(),
                                ...CityResource::getNavigationItems(),
                                ...DistrictResource::getNavigationItems(),
                            ]),
                        NavigationGroup::make('Gestion des hébergements')
                            ->icon('heroicon-o-building-office'),
                        NavigationGroup::make('Gestion des réservations')
                            ->icon('heroicon-o-calendar-days'),
                        NavigationGroup::make('Gestion financière')
                            ->icon('heroicon-o-banknotes'),
                        NavigationGroup::make('Configuration')
                            ->icon('heroicon-o-cog-6-tooth')
                        ->items([
                                ...CurrencyResource::getNavigationItems(),
                                ...SettingResource::getNavigationItems(),
                            ]),
                    ]);
            })
            ->sidebarCollapsibleOnDesktop()
            ->maxContentWidth('full');
    }
}
