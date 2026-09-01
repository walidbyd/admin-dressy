<?php

namespace Modules\Core\Http\Controllers\Backend\Rests\App\V1_0\Image;

use Illuminate\Routing\Controller;
use Modules\Core\Constants\Constants;

class ImageApiController extends Controller
{
    protected $badRequestStatusCode;

    protected $internalServerErrorStatusCode;

    public function __construct()
    {
        $this->badRequestStatusCode = Constants::badRequestStatusCode;
        $this->internalServerErrorStatusCode = Constants::internalServerErrorStatusCode;

    }
}
