<?php

namespace App\Http\Middleware;

use App\Config\Cache\VendorSessionCache;
use Closure;
use Illuminate\Support\Facades\Cache;

class PageLoadHandler
{
    public function handle($request, Closure $next)
    {
        // Handling Vendor Session Delete
        // It will delete every 3 hrs,
        // this function will help without setting cronjob to remove some session data
        Cache::remember(VendorSessionCache::SESSION_KEY, VendorSessionCache::SESSION_EXPIRY, function () {
            deleteOldSessions();

            return true;
        });

        return $next($request);
    }
}
