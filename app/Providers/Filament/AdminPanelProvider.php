<?php

namespace App\Providers\Filament;

use App\Filament\Pages\Dashboard;
use App\Models\Company;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Navigation\NavigationGroup;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\View\PanelsRenderHook;
use Illuminate\Support\Facades\Blade;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;

class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('admin')
            ->path('admin')
            ->login()
            ->registration()
            ->passwordReset()
            ->emailVerification()
            ->profile()
            ->tenant(Company::class)
            ->tenantRegistration(\App\Filament\Pages\Tenancy\RegisterCompany::class)
            ->tenantProfile(\App\Filament\Pages\Tenancy\EditCompanyProfile::class)
            ->colors([
                'primary' => Color::Emerald,
                'danger' => Color::Rose,
                'warning' => Color::Amber,
                'success' => Color::Green,
                'info' => Color::Sky,
            ])
            ->font('Inter')
            ->brandName('QuickBooks Clone')
            ->theme(asset('css/filament/admin/theme.css'))
            ->navigationGroups([
                NavigationGroup::make()
                    ->label('Dashboard'),
                NavigationGroup::make()
                    ->label('Sales')
                    ->icon('heroicon-o-currency-dollar')
                    ->collapsed(),
                NavigationGroup::make()
                    ->label('Expenses')
                    ->icon('heroicon-o-credit-card')
                    ->collapsed(),
                NavigationGroup::make()
                    ->label('Accounting')
                    ->icon('heroicon-o-calculator')
                    ->collapsed(),
                NavigationGroup::make()
                    ->label('Banking')
                    ->icon('heroicon-o-building-library')
                    ->collapsed(),
                NavigationGroup::make()
                    ->label('Inventory')
                    ->icon('heroicon-o-cube')
                    ->collapsed(),
                NavigationGroup::make()
                    ->label('Reports')
                    ->icon('heroicon-o-chart-bar')
                    ->collapsed(),
                NavigationGroup::make()
                    ->label('Settings')
                    ->icon('heroicon-o-cog-6-tooth')
                    ->collapsed(),
                NavigationGroup::make()
                    ->label('Administration')
                    ->icon('heroicon-o-shield-check')
                    ->collapsed(),
            ])
            ->renderHook(
                PanelsRenderHook::BODY_START,
                fn () => Blade::render('@livewire(\'impersonation-banner\')')
            )
            ->renderHook(
                PanelsRenderHook::HEAD_END,
                fn () => Blade::render('@livewire(\'dynamic-theme-styles\')')
            )
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\\Filament\\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\\Filament\\Pages')
            ->discoverClusters(in: app_path('Filament/Clusters'), for: 'App\\Filament\\Clusters')
            ->pages([
                Dashboard::class,
            ])
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\\Filament\\Widgets')
            ->widgets([
                \App\Filament\Widgets\FinancialOverviewWidget::class,
                \App\Filament\Widgets\CashFlowChartWidget::class,
                \App\Filament\Widgets\AccountsReceivableWidget::class,
                \App\Filament\Widgets\AccountsPayableWidget::class,
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
            ])
            ->databaseNotifications()
            ->sidebarCollapsibleOnDesktop()
            ->maxContentWidth('full');
    }
}
