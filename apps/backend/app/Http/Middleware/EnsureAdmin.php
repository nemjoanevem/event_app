<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class EnsureAdmin
{
    /**
     * Allow only admin users to access the route.
     */
    public function handle(Request $request, Closure $next)
    {
        $user = $request->user();

        if (! $user || ! method_exists($user, 'isAdmin') || ! $user->isAdmin()) {
            abort(403);
        }

        return $next($request);
    }
}
