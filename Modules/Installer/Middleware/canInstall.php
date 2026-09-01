<?php

namespace Modules\Installer\Middleware;

use Closure;
use Illuminate\Support\Facades\Schema;
use Modules\Core\Entities\Installer;
use Modules\Installer\Helpers\InstalledFileManager;

class canInstall
{
    /**
     * @todo : No longer needed.
     *
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse|mixed
     */
    public function handle($request, Closure $next)
    {
        // $domainTable = Schema::hasTable('psx_domain_changes');
        // if (! $domainTable) {
        //     abort(404);
        // }
        // if ($this->alreadyInstalled()) {

        //     $installedRedirect = config('installer.installedAlreadyAction');
        //     switch ($installedRedirect) {

        //         case 'route':
        //             $routeName = config('installer.installed.redirectOptions.route.name');
        //             $data = config('installer.installed.redirectOptions.route.message');

        //             return redirect()->route($routeName)->with(['data' => $data]);
        //             break;

        //         case 'abort':
        //             abort(config('installer.installed.redirectOptions.abort.type'));
        //             break;

        //         case 'dump':
        //             $dump = config('installer.installed.redirectOptions.dump.data');
        //             break;

        //         case '404':
        //         case 'default':
        //         default:
        //             abort(404);
        //             break;
        //     }
        // }
        $installManager = new InstalledFileManager;

        if ($installManager->isInstalled()) {
            return redirect()->route('dashboard');
        }

        return $next($request);

    }

    /**
     * If application is already installed.
     *
     * @return bool
     */
    public function alreadyInstalled()
    {
        $installedFile = file_exists(storage_path('installed'));

        $isInstalled = '';
        $domainTable = Schema::hasTable('psx_domain_changes');
        if (! $domainTable) {
            return true;
        }
        $installerTable = Schema::hasTable('psx_installer');

        if ($installerTable) {
            $isInstalled = Installer::first() ? Installer::first()->is_installed : '';
        }

        if ($installedFile || ! empty($isInstalled)) {
            return true;
        } else {
            return false;
        }
        //        return file_exists(storage_path('installed'));
    }
}
