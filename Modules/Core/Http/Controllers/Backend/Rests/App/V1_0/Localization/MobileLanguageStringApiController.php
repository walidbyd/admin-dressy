<?php

namespace Modules\Core\Http\Controllers\Backend\Rests\App\V1_0\Localization;

use App\Http\Contracts\Localization\MobileLanguageServiceInterface;
use App\Http\Contracts\Localization\MobileLanguageStringServiceInterface;
use App\Http\Controllers\PsController;
use Illuminate\Http\Request;

class MobileLanguageStringApiController extends PsController
{
    public function __construct(
        protected MobileLanguageStringServiceInterface $mobileLanguageStringService,
        protected MobileLanguageServiceInterface $mobileLanguageService
    ) {
        parent::__construct();
    }

    public function index(Request $request)
    {
        $mobileLanguageStrings = $this->mobileLanguageStringService->getAll($request->mobile_language)->map(function ($lang, $key) {
            return [
                $lang['key'] => $lang['value'],
            ];
        })->collapse();

        return responseDataApi($mobileLanguageStrings);
    }
}
