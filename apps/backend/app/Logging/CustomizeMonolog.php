<?php

namespace App\Logging;

use Illuminate\Support\Facades\Auth;
use Monolog\Logger;
use Monolog\Processor\UidProcessor;
use Monolog\Processor\WebProcessor;

class CustomizeMonolog
{
    public function __invoke(Logger $logger): void
    {
        $logger->pushProcessor(new UidProcessor());

        $logger->pushProcessor(new WebProcessor());

        $logger->pushProcessor(function (array $record) {
            $request = request();

            $record['extra']['request_id'] = $request->headers->get('X-Request-Id')
                ?? $request->attributes->get('request_id');

            if (Auth::check()) {
                $user = Auth::user();
                $record['extra']['user_id'] = $user->id;
                $record['extra']['user_role'] = method_exists($user, 'role') ? $user->role : null;
            } else {
                $record['extra']['user_id'] = null;
                $record['extra']['user_role'] = null;
            }

            $record['extra']['route'] = optional($request->route())->getName();
            $record['extra']['path']  = $request->path();
            $record['extra']['method'] = $request->method();

            return $record;
        });
    }
}
