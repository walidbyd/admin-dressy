<?php

namespace App\Http\Middleware;

use App\Config\ps_constant;
use Closure;
use Illuminate\Http\Request;
use Modules\Core\Entities\Configuration\MobileSetting;

class CheckSupportedApiVersion
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function handle(Request $request, Closure $next, $versionNumber)
    {
        $supportedApiVersionCount = ps_constant::supportedApiVersionCount;
        $supportedVersion = '0.'.$supportedApiVersionCount;
        // $mobileSettingObj = MobileSetting::select('version_no')->first() ?? $supportedVersion;
        $mobileSettingObj = MobileSetting::select('version_no')->first();

        $latestAppVersion = $mobileSettingObj ? $mobileSettingObj->version_no : $supportedVersion;

        // $latestAppVersion = $mobileSettingObj->version_no;

        $notSupportedVersionNumbers = floatval($latestAppVersion) - floatval($supportedVersion);
        if ($versionNumber > $notSupportedVersionNumbers) {
            return $next($request);
        } else {
            return responseMsgApi("Api Version Number $versionNumber is not supported", 400);
        }
    }
}
