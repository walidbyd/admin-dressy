<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Modules\Core\Entities\Installer;
use Modules\Installer\Helpers\InstalledFileManager;

class isInstalled
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function handle(Request $request, Closure $next)
    {
        $installManager = new InstalledFileManager;

        if ($installManager->isDevMode($request)) {
            return $next($request);
        }

        if (! $installManager->isInstalled()) {
            return redirect()->route('LaravelInstaller::welcome');
        }

        $installerTable = Schema::hasTable('psx_installer');

        if ($installerTable && empty(Installer::first() ? Installer::first()->is_installed : '')) {
            return redirect()->route('LaravelInstaller::welcome');
        }

        return $next($request);

    }
}
