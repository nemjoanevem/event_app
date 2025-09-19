<?php
namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class EnsureUserHasRole
{
    public function handle(Request $request, Closure $next, ...$roles)
    {
        // Allow only if authenticated and role matches any required
        $user = $request->user();
        if (!$user) {
            abort(401);
        }
        if (!in_array((string)$user->role, $roles, true)) {
            abort(403);
        }
        return $next($request);
    }
}
