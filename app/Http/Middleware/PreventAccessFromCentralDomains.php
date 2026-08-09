<?php

declare(strict_types=1);

namespace Devanox\Core\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

use function Devanox\Core\Helpers\tenant;

final class PreventAccessFromCentralDomains
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        abort_unless((bool) tenant(), 404);

        return $next($request);
    }
}
