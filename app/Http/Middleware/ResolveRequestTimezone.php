<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class ResolveRequestTimezone
{
    public function handle(Request $request, Closure $next)
    {
        $timezone = $request->cookie('app_timezone');

        if (! is_string($timezone) || ! in_array($timezone, timezone_identifiers_list(), true)) {
            $timezone = config('app.timezone', date_default_timezone_get());
        }

        config(['app.timezone' => $timezone]);
        date_default_timezone_set($timezone);

        return $next($request);
    }
}
