<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\URL;
use Symfony\Component\HttpFoundation\Response;

/**
 * APP_URL ба хөтчийн хаяг өөр үед redirect/session алдагдана.
 * Ирсэн request-ийн scheme+host-оор root URL-ийг тааруулна.
 */
class ForceAppUrlFromRequest
{
    public function handle(Request $request, Closure $next): Response
    {
        $host = $request->getHttpHost();
        if ($host !== '') {
            URL::forceRootUrl($request->getScheme().'://'.$host);
        }

        return $next($request);
    }
}
