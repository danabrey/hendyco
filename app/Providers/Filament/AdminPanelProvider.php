<?php

namespace App\Providers\Filament;

use App\Http\Controllers\ExpenseReceiptController;
use App\Http\Controllers\GiftCardPrintController;
use App\Http\Controllers\ReportPrintController;
use App\NavigationGroup;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Pages\Dashboard;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\View\PanelsRenderHook;
use Filament\Widgets\AccountWidget;
use Filament\Widgets\FilamentInfoWidget;
use Guava\Calendar\CalendarPlugin;
use Illuminate\Contracts\View\View;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\Support\Facades\Route;
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
            ->brandName('Hendy & Co.')
            ->brandLogo(fn (): View => view('filament.brand'))
            ->brandLogoHeight('2.5rem')
            ->favicon(asset('images/logo-mark.png'))
            ->font('Instrument Sans')
            ->colors([
                'primary' => Color::hex('#c17b72'),
                'gray' => Color::Stone,
            ])
            ->renderHook(PanelsRenderHook::HEAD_END, fn (): View => view('filament.brand-head'))
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\Filament\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\Filament\Pages')
            ->pages([
                Dashboard::class,
            ])
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\Filament\Widgets')
            ->widgets([
                AccountWidget::class,
                FilamentInfoWidget::class,
            ])
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                PreventRequestForgery::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ])
            ->navigationGroups(NavigationGroup::class)
            ->plugin(CalendarPlugin::make())
            ->authenticatedRoutes(function (): void {
                Route::get('/expenses/{expense}/receipts/{index}', ExpenseReceiptController::class)
                    ->whereNumber('index')
                    ->withTrashed()
                    ->name('expenses.receipt');
                Route::get('/reports/uc/print', [ReportPrintController::class, 'uc'])->name('reports.uc.print');
                Route::get('/reports/business/print', [ReportPrintController::class, 'business'])->name('reports.business.print');
                Route::get('/gift-cards/{giftCard}/print', GiftCardPrintController::class)
                    ->withTrashed()
                    ->name('gift-cards.print');
            })
            ->authMiddleware([
                Authenticate::class,
            ]);
    }
}
