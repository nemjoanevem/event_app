<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Exceptions\UserDisabledException;

class EnsureUserEnabled
{
    protected array $allowList = [
        'api/logout',
        'api/user',
    ];

    public function handle(Request $request, Closure $next)
    {
        $user = $request->user();

        if ($user && (int)($user->enabled ?? 1) !== 1) {
            $path = $request->path();
            foreach ($this->allowList as $allowed) {
                if (\Illuminate\Support\Str::is($allowed, $path)) {
                    return $next($request);
                }
            }

            throw new UserDisabledException();
        }

        return $next($request);
    }
}
