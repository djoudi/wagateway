<?php

namespace App\Providers\Filament;

use App\Filament\Resources\InvoiceResource;
use App\Filament\Resources\MessageResource;
use App\Filament\Resources\PlanResource;
use App\Filament\Resources\SecurityEventResource;
use App\Filament\Resources\UserResource;
use App\Filament\Widgets\PlatformStatsWidget;
use App\Models\User;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Pages;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\AuthenticateSession;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;

class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->id('admin')
            ->path('admin')
            ->login()
            ->colors(['primary' => Color::Green])
            ->brandName('WaGateway Admin')
            ->resources([
                UserResource::class,
                PlanResource::class,
                InvoiceResource::class,
                MessageResource::class,
                SecurityEventResource::class,
            ])
            ->widgets([
                PlatformStatsWidget::class,
            ])
            ->pages([
                Pages\Dashboard::class,
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
            ->authGuard('web')
            ->auth(
                // Only users listed in ADMIN_EMAILS config can access
                fn (User $user): bool => $user->isAdmin()
            );
    }
}
