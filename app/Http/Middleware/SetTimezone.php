<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use JetBrains\PhpStorm\NoReturn;
use JsonException;
use Symfony\Component\HttpFoundation\Response;
use Carbon\Carbon;
use Worksome\IpGeolocation\Facades\IpGeolocation;

class SetTimezone
{
    /**
     * Handle an incoming request.
     *
     * @param Closure(Request): (Response) $next
     * @throws JsonException
     */
    #[NoReturn] public function handle(Request $request, Closure $next): Response
    {
        $timezone = session('timezone');

        // Set default timezone to 'Africa/Porto-Novo' if application environment is 'local'
        if (app()->environment('local')) {
            $timezone = 'Africa/Porto-Novo';
        }

        // If timezone is not set in session, determine it based on the user's IP address
        if (!$timezone) {
            $ip = $request->ip();
            $geoData = geoip($ip);
            $timezone = $geoData->time_zone['name'] ?? null;
            session(['timezone' => $timezone]);
        }

        // Set the timezone for the application
        config(['app.timezone' => $timezone]);
        (new Carbon())->setTimezone($timezone);

        return $next($request);
    }
}
