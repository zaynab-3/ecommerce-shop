<?php

namespace App\Http\Middleware;

use App\Services\CatalogRevision;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class PublishCatalogRevision
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);
        // Publish after the controller's transaction and cache invalidation finish.
        if (! $request->isMethodSafe() && $response->getStatusCode() < 400
            && $request->routeIs('admin.products.*', 'admin.categories.*', 'admin.sort')
            && ! $request->session()->has('errors')) {
            CatalogRevision::publish();
        }

        return $response;
    }
}
