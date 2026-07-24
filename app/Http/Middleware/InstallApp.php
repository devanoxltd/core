<?php

declare(strict_types=1);

namespace Devanox\Core\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Symfony\Component\HttpFoundation\Response;

final class InstallApp
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (app()->runningUnitTests() || isAppInstalled() || $this->inAllowedRoutes($request)) {
            return $next($request);
        }

        if ($request->expectsJson() || $request->is('api/*')) {
            return response()->json(
                ['message' => __('core::install.not_installed')],
                Response::HTTP_FORBIDDEN,
            );
        }

        return to_route('devanox.install');
    }

    /**
     * Determine if the request is for an allowed install-time route.
     */
    private function inAllowedRoutes(Request $request): bool
    {
        $installRoutes = config('core.route_allow');

        if (! is_array($installRoutes) || $installRoutes === []) {
            $installRoutes = [
                'install',
                'livewire-*/*',
            ];
        }

        // Automatically allow the target install route path to prevent redirect loops.
        if (Route::has('devanox.install')) {
            $installPath = route('devanox.install', absolute: false);
            $installRoutes[] = mb_trim($installPath, '/') ?: '/';
        }

        return $request->is(...$installRoutes);
    }
}
