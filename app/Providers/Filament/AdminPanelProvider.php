<?php

namespace App\Providers\Filament;

use App\Filament\Backup\BackupsPage;
use App\Filament\Pages\CustomDashboard;
use App\Filament\Pages\ImportTabulasiPage;
use App\Filament\Pages\PrediksiAiPage;
use App\Filament\Resources\AlokasiDana\AlokasiDanaPage;
use App\Http\Middleware\UpdateUserLastActive;
use DutchCodingCompany\FilamentSocialite\FilamentSocialitePlugin;
use DutchCodingCompany\FilamentSocialite\Provider;
use Filament\Actions\Action;
use Filament\Enums\ThemeMode;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Navigation\NavigationGroup;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\View\PanelsRenderHook;
use Filament\Widgets\AccountWidget;
use Illuminate\Auth\Middleware\Authenticate;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;
use Laravel\Socialite\Contracts\User;
use ShuvroRoy\FilamentSpatieLaravelBackup\FilamentSpatieLaravelBackupPlugin;

class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('admin')
            ->path('admin')
            ->viteTheme('resources/css/filament/admin/theme.css')
            ->databaseNotifications()
            ->databaseNotificationsPolling('30s')
            ->topNavigation(false)
            ->globalSearch(false)
            ->colors([
                'primary' => [
                    50 => '#e6f5f8',
                    100 => '#b3e0ea',
                    200 => '#80cbdc',
                    300 => '#4db6ce',
                    400 => '#26a6c3',
                    500 => '#067D9B',
                    600 => '#05718c',
                    700 => '#046077',
                    800 => '#035063',
                    900 => '#023540',
                    950 => '#011d24',
                ],
                'success' => '#059669',
                'warning' => '#F59E0B',
                'danger' => '#DC2626',
                'info' => '#0284C7',
            ])
            ->defaultThemeMode(ThemeMode::Dark)
            ->brandLogo(fn () => view('filament.components.brand-logo'))
            ->brandLogoHeight('2.2rem')
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\Filament\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\Filament\Pages')
            ->discoverClusters(in: app_path('Filament/Clusters'), for: 'App\Filament\Clusters')
            ->pages([
                CustomDashboard::class,
                PrediksiAiPage::class,
                AlokasiDanaPage::class,
                ImportTabulasiPage::class,
            ])
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\Filament\Widgets')
            ->widgets([
                AccountWidget::class,
                FilementInfoWidget::class,
            ])
            // ->maxContentWidth(Width::Full)
            ->sidebarWidth('16rem')
            ->navigationGroups([
                NavigationGroup::make()->label('Master Data'),
                NavigationGroup::make()->label('Distribusi & Permintaan'),
                NavigationGroup::make()->label('Inventory'),
                NavigationGroup::make()->label('Ai Service'),
                NavigationGroup::make()->label('Laporan'),
                NavigationGroup::make()->label('Manajemen Akses'),
                NavigationGroup::make()->label('Sistem'),
            ])
            ->renderHook(
                PanelsRenderHook::CONTENT_BEFORE,
                fn () => view('filament.hooks.rko-banner'),
            )
            ->renderHook(
                PanelsRenderHook::HEAD_END,
                fn () => view('filament.hooks.chart-scripts'),
            )
            ->renderHook(
                PanelsRenderHook::USER_MENU_PROFILE_AFTER,
                fn () => view('filament.components.user-menu-profile-card'),
            )
            ->renderHook(
                PanelsRenderHook::FOOTER,
                fn () => view('filament.components.footer'),
            )
            ->userMenuItems([

                'profile' => Action::make('profile')
                    ->label(fn (): string => filament()->getUserName(filament()->auth()->user()))
                    ->icon('heroicon-o-user')
                    ->color('primary')
                    ->sort(-10),

                'panduan' => Action::make('panduan')
                    ->label('Panduan Aplikasi')
                    ->url('/panduan')
                    ->icon('heroicon-o-book-open')
                    ->openUrlInNewTab()
                    ->sort(90),
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
            ->authMiddleware([
                Authenticate::class,
                UpdateUserLastActive::class,
            ])
            ->plugin(
                FilamentSocialitePlugin::make()
                    ->providers([
                        Provider::make('google')
                            ->label('Google')
                            ->icon('icon-google')
                            ->color('#4285F4')
                            ->outlined(false),
                    ])
                    ->loginRouteName('login')
                    ->registration(false)
                    ->resolveUserUsing(function (string $provider, User $oauthUser, FilamentSocialitePlugin $plugin) {
                        $userModel = $plugin->getUserModelClass();

                        return $userModel::where('email', $oauthUser->getEmail())
                            ->where('google_login_enabled', true)
                            ->first();
                    })
                    ->loginRouteName('login')
            )
            ->plugin(
                FilamentSpatieLaravelBackupPlugin::make()
                    ->usingPage(BackupsPage::class)
                    ->navigationGroup('Sistem')
                    ->navigationLabel('Backup Database')
                    ->navigationIcon('heroicon-o-circle-stack')
                    ->navigationSort(82)
                    ->authorize(fn (): bool => auth()->user()?->hasPermissionTo('manage_backup') ?? false)
            )
            ->spa();
    }
}
