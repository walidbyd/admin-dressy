<?php

namespace Modules\Core\Http\Services;

use App\Http\Services\PsService;
use Modules\Core\Entities\Icon;

class IconService extends PsService
{
    public function __construct() {}

    public function getIcons()
    {
        $icons = Icon::all();

        return $icons;
    }
}
