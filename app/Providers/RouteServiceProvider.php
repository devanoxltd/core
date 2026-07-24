<?php

declare(strict_types=1);

namespace Devanox\Core\Providers;

use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Route;

final class RouteServiceProvider extends ServiceProvider
{
    /**
     * Define the routes for the application.
     */
    public function map(): void
    {
        $this->mapApiRoutes();
        $this->mapWebRoutes();
        $this->mapConsoleRoutes();
    }

    /**
     * Define the "web" routes for the application.
     *
     * These routes all receive session state, CSRF protection, etc.
     */
    private function mapWebRoutes(): void
    {
        if (file_exists($this->getRoutePath() . 'web.php')) {
            Route::middleware('web')->name('devanox.')->group($this->getRoutePath() . 'web.php');
        }
    }

    /**
     * Define the "api" routes for the application.
     *
     * These routes are typically stateless.
     */
    private function mapApiRoutes(): void
    {
        if (file_exists($this->getRoutePath() . 'api.php')) {
            Route::middleware('api')->prefix('api')->name('api.')->group($this->getRoutePath() . 'api.php');
        }
    }

    /**
     * Define the "console" routes for the application.
     *
     * These routes are typically stateless.
     */
    private function mapConsoleRoutes(): void
    {
        if (file_exists($this->getRoutePath() . 'console.php')) {
            Route::middleware(null)->group($this->getRoutePath() . 'console.php');
        }
    }

    private function getRoutePath(): string
    {
        return __DIR__ . '/../../routes/';
    }
}
