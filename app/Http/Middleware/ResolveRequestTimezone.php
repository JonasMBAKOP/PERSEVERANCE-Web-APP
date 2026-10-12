<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class ResolveRequestTimezone
{
    public function handle(Request $request, Closure $next)
    {
        // Keep the application timezone authoritative. Browser timezone cookies
        // must not make stored dates drift away from Cameroon time.
        $timezone = config('app.timezone', 'Africa/Douala');

        config(['app.timezone' => $timezone]);
        date_default_timezone_set($timezone);

        return $next($request);
    }
}
