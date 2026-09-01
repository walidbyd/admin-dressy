<?php

namespace Modules\Core\Http\Controllers\Backend\Rests\App\V1_0\Financial;

use App\Http\Contracts\Configuration\MobileSettingServiceInterface;
use App\Http\Contracts\Financial\ItemCurrencyServiceInterface;
use App\Http\Controllers\PsApiController;
use Illuminate\Http\Request;
use Modules\Core\Constants\Constants;
use Modules\Core\Transformers\Api\App\V1_0\Financial\ItemCurrencyApiResource;

class ItemCurrencyApiController extends PsApiController
{
    public function __construct(
        protected ItemCurrencyServiceInterface $currencyService,
        protected MobileSettingServiceInterface $mobileSettingService
    ) {
        parent::__construct();
    }

    public function index(Request $request)
    {
        // Get Limit and Offset
        [$limit, $offset] = $this->getLimitOffsetFromSetting($request);

        $data = ItemCurrencyApiResource::collection(
            $this->currencyService->getAll(Constants::publish, Constants::default, $limit, $offset)
        );

        // Prepare and Check No Data Return
        return $this->handleNoDataResponse($request->offset, $data);

    }

    // //////////////////////////////////////////////////////////////////
    // / Private Functions
    // //////////////////////////////////////////////////////////////////

    private function getLimitOffsetFromSetting($request)
    {
        $offset = $request->offset;
        $limit = $request->limit ?: $this->getDefaultLimit();

        return [$limit, $offset];
    }

    private function getDefaultLimit()
    {
        $defaultLimit = $this->mobileSettingService->get()->default_loading_limit;

        return $defaultLimit ?: 9;
    }
}
