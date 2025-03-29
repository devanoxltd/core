<?php

namespace Devanox\Core\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class Licence
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (isAppInstalled()) {
            if (isLicenceValid()) {
                return $next($request);
            }

            if ($request->is('api/*')) {
                return response()->json(['message' => 'Licence is not valid.'], 403);
            }

            return redirect()->route('devanox.licence');
        }

        return $next($request);
    }
}
