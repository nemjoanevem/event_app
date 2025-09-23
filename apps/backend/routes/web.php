<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
| All SPA/JSON endpoints are grouped with force.json + request.id so that:
| - responses are always JSON (including errors)
| - no stack trace leaks to clients (handled by Handler + APP_DEBUG=false)
*/