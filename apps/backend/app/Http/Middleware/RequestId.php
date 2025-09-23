<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Support\Str;

class RequestId
{
    public function handle($request, Closure $next)
    {
        $id = (string) Str::uuid();
        $request->attributes->set('request_id', $id);

        $response = $next($request);
        $response->headers->set('X-Request-Id', $id);
        return $response;
    }
}
