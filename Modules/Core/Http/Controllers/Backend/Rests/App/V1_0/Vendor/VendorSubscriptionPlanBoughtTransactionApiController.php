<?php

namespace Modules\Core\Http\Controllers\Backend\Rests\App\V1_0\Vendor;

use App\Http\Contracts\Financial\PaymentInfoServiceInterface;
use App\Http\Contracts\Vendor\VendorSubscriptionPlanBoughtTransactionServiceInterface;
use App\Http\Controllers\PsApiController;
use Illuminate\Contracts\Translation\Translator;
use Modules\Core\Constants\Constants;
use Modules\Core\Http\Requests\Vendor\StoreVendorSubscriptionPlanBoughtRequest;

class VendorSubscriptionPlanBoughtTransactionApiController extends PsApiController
{
    public function __construct(protected Translator $translator,
        protected VendorSubscriptionPlanBoughtTransactionServiceInterface $vendorSubscriptionPlanBoughtTransactionService,
        protected PaymentInfoServiceInterface $paymentInfoService)
    {
        parent::__construct();
    }

    public function store(StoreVendorSubscriptionPlanBoughtRequest $request)
    {
        $validateData = $request->validated();
        $package = $this->paymentInfoService->get($validateData['subscription_plan_id']);
        if ($package['payment_id'] != Constants::vendorSubscriptionPlanPaymentId) {
            return responseMsgApi('package__pkg_invalid', Constants::badRequestStatusCode);
        }
        $packages = $this->vendorSubscriptionPlanBoughtTransactionService->storeFromApi($validateData);

        return $packages;
    }
}
