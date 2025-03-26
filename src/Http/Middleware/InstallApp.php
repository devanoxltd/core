<?php

namespace Devanox\Core\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class InstallApp
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (isAppInstalled()) {
            return $next($request);
        }

        if (config('core.installRoutes') && is_array(config('core.installRoutes'))) {
            $installRoutes = config('core.installRoutes');
        } else {
            $installRoutes = [
                'install',
                'livewire/*',
            ];
        }

        foreach ($installRoutes as $route) {
            if ($request->is($route)) {
                return $next($request);
            }
        }

        if ($request->is('api/*')) {
            return response()->json(['message' => 'Application is not installed yet.'], 403);
        }

        return redirect()->route('devanox.install');
    }
}
