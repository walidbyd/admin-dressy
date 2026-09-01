<?php

namespace Modules\Core\Http\Controllers\Backend\Rests\App\V1_0\Information;

use App\Http\Contracts\Information\AboutServiceInterface;
use App\Http\Controllers\PsApiController;
use Illuminate\Http\Request;
use Modules\Core\Transformers\Api\App\V1_0\Information\AboutApiResource;

class AboutApiController extends PsApiController
{
    public function __construct(protected AboutServiceInterface $aboutService)
    {
        parent::__construct();
    }

    public function index(Request $request)
    {
        $aboutApiRelation = ['defaultPhoto'];

        $about = new AboutApiResource($this->aboutService->get(null, $aboutApiRelation));

        // Prepare and Check No Data Return
        return responseDataApi($about);
    }
}
