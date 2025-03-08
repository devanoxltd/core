<?php

namespace Devanox\Core\Providers;

use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Route;

class RouteServiceProvider extends ServiceProvider
{
    private function getRoutePath(): string
    {
        return __DIR__ . '/../Routes/';
    }

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
    protected function mapWebRoutes(): void
    {
        if (file_exists($this->getRoutePath() . 'web.php')) {
            Route::middleware('web')->group($this->getRoutePath() . 'web.php');
        }
    }

    /**
     * Define the "api" routes for the application.
     *
     * These routes are typically stateless.
     */
    protected function mapApiRoutes(): void
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
    protected function mapConsoleRoutes(): void
    {
        if (file_exists($this->getRoutePath() . 'console.php')) {
            Route::middleware(null)->group($this->getRoutePath() . 'console.php');
        }
    }
}
