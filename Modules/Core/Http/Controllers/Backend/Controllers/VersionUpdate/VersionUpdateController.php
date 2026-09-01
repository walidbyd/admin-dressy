<?php

namespace Modules\Core\Http\Controllers\Backend\Controllers\VersionUpdate;

use Illuminate\Routing\Controller;
use Modules\Core\Http\Services\VersionUpdateService;

class VersionUpdateController extends Controller
{
    const parentPath = 'version_update/';

    const indexPath = self::parentPath.'Index';

    const createPath = self::parentPath.'Create';

    const editPath = self::parentPath.'Edit';

    const indexRoute = 'versionUpdate.index';

    const createRoute = 'versionUpdate.create';

    const editRoute = 'versionUpdate.edit';

    protected $versionUpdateService;

    public function __construct(VersionUpdateService $versionUpdateService)
    {
        $this->versionUpdateService = $versionUpdateService;
    }

    public function index()
    {
        return renderView(self::indexPath);
    }
}
