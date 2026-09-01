<?php

namespace Modules\Core\Http\Controllers\Backend\Rests\App\V1_0\Vendor;

use App\Config\Cache\VendorCache;
use App\Http\Contracts\Vendor\VendorApplicationServiceInterface;
use App\Http\Contracts\Vendor\VendorServiceInterface;
use App\Http\Controllers\PsApiController;
use Illuminate\Contracts\Translation\Translator;
use Modules\Core\Constants\Constants;
use Modules\Core\Http\Facades\PsCache;
use Modules\Core\Http\Requests\Vendor\StoreVendorApplicationRequest;
use Modules\Core\Transformers\Api\App\V1_0\Vendor\VendorApiResource;

class VendorApplicationApiController extends PsApiController
{
    public function __construct(protected VendorApplicationServiceInterface $vendorApplicationService, protected Translator $translator, protected VendorServiceInterface $vendorService)
    {
        parent::__construct();
    }

    public function submitApplication(StoreVendorApplicationRequest $request)
    {

        $validateData = $request->validated();
        $vendorId = '';
        if (isset($validateData['id'])) {
            $vendorApplication = $this->vendorApplicationService->get($validateData['id']);
            $vendorId = $vendorApplication->vendor_id;
            $validateData['vendor_id'] = $vendorId;
        }

        if (isset($validateData['language_symbol'])) {
            $this->translator->setLocale($validateData['language_symbol']);
        }
        $validateData['owner_user_id'] = $validateData['login_user_id'];
        try {
            $dataArr = ! isset($validateData['id']) ? $this->vendorService->save($validateData) : $this->vendorService->update($vendorApplication->vendor_id, $validateData);

            ! isset($validateData['id']) ? $this->vendorApplicationService->save($validateData, $dataArr['id']) : $this->vendorApplicationService->update($validateData['id'], $validateData, $vendorId);

            PsCache::clear(VendorCache::BASE);

            $vendor = new VendorApiResource($dataArr);

            return responseDataApi($vendor);
        } catch (\Exception $e) {
            return responseMsgApi($e->getMessage(), Constants::internalServerErrorStatusCode, Constants::errorStatus);
        }
    }
}
