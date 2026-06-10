<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ApiTokenFromQuery
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (!$request->hasHeader('Authorization') && $request->has('token')) {
            $request->headers->set('Authorization', 'Bearer ' . $request->query('token'));
        }

        // Force Accept: application/json to avoid redirects to the 'login' route
        if ($request->is('api/*')) {
            $request->headers->set('Accept', 'application/json');
        }

        return $next($request);
    }
}
