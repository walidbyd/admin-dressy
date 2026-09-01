<?php

namespace Modules\Core\Http\Controllers\Backend\Rests\App\V1_0\Configuration;

use App\Http\Contracts\Configuration\MobileSettingServiceInterface;
use App\Http\Contracts\Configuration\PhoneCountryCodeServiceInterface;
use App\Http\Controllers\PsApiController;
use Illuminate\Http\Request;
use Modules\Core\Constants\Constants;
use Modules\Core\Transformers\Api\App\V1_0\Configuration\PhoneCountryCodeApiResource;

class PhoneCountryCodeApiController extends PsApiController
{
    public function __construct(protected PhoneCountryCodeServiceInterface $phoneCountryCodeService,
        protected MobileSettingServiceInterface $mobileSettingService)
    {
        parent::__construct();
    }

    public function search(Request $request)
    {
        // Get Limit and Offset
        [$limit, $offset] = $this->getLimitOffsetFromSetting($request);

        // Prepare Filter Conditions
        $conds = $this->getFilterConditions($request);

        // Get Phone Country Codes
        $data = PhoneCountryCodeApiResource::collection(
            $this->phoneCountryCodeService->getAll(Constants::publish, Constants::default, $limit, $offset, false, null, $conds)
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

    private function getFilterConditions($request)
    {
        return [
            'searchterm' => $request->keyword,
            'order_by' => $request->order_by,
            'order_type' => $request->order_type,
        ];
    }
}
