<?php

declare(strict_types=1);

namespace Devanox\Core\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

use function Devanox\Core\Helpers\isAppInstalled;
use function Devanox\Core\Helpers\isLicenseValid;

final class License
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (app()->runningUnitTests() || ! isAppInstalled() || isLicenseValid()) {
            return $next($request);
        }

        if ($request->expectsJson() || $request->is('api/*')) {
            return response()->json([
                'message' => __('core::install.not_activated'),
            ], Response::HTTP_FORBIDDEN);
        }

        if ($request->is('license') || $request->is('livewire-*/*')) {
            return $next($request);
        }

        return to_route('devanox.license');
    }
}
