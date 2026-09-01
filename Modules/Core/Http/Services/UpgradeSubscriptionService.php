<?php

namespace Modules\Core\Http\Services;

use App\Http\Contracts\Vendor\VendorSubscriptionPlanBoughtTransactionServiceInterface;
use App\Http\Services\PsService;
use Modules\Core\Constants\Constants;
use Modules\Core\Transformers\Backend\Model\Vendor\VendorWithKeyResource;
use Modules\Core\Transformers\Backend\NoModel\Vendor\VendorSubscriptionPlanWithKeyResource;
use Modules\Payment\Http\Services\PaymentSettingService;

class UpgradeSubscriptionService extends PsService
{
    protected $vendorService;

    protected $paymentSettingService;

    protected $appInfoService;

    protected $vendorSubscriptionPlanBoughtTransactionService;

    public function __construct(VendorService $vendorService, PaymentSettingService $paymentSettingService, AppInfoService $appInfoService, VendorSubscriptionPlanBoughtTransactionServiceInterface $vendorSubscriptionPlanBoughtTransactionService)
    {
        $this->vendorService = $vendorService;
        $this->paymentSettingService = $paymentSettingService;
        $this->appInfoService = $appInfoService;
        $this->vendorSubscriptionPlanBoughtTransactionService = $vendorSubscriptionPlanBoughtTransactionService;
    }

    // for Backend
    public function index()
    {
        $vendorId = getVendorIdFromSession();
        $vendor = new VendorWithKeyResource($this->vendorService->getVendor($vendorId));

        $conds['payment_id'] = Constants::vendorSubscriptionPlanPaymentId;
        $relations = ['core_key'];
        $attributes = [
            Constants::pmtAttrVendorSpDurationCol,
            Constants::pmtAttrVendorSpSalePriceCol,
            Constants::pmtAttrVendorSpDiscountPriceCol,
            Constants::pmtAttrVendorSpCurrencyCol,
            Constants::pmtAttrVendorSpIsMostPopularPlanCol,
            Constants::pmtAttrVendorSpStatusCol,
        ];
        $vendorSubscriptionPlans = VendorSubscriptionPlanWithKeyResource::collection($this->paymentSettingService->getPaymentInfos($relations, null, null, $conds, true, null, $attributes));
        $appInfo = $this->appInfoService->forVendor();

        $dataArr = [
            'vendor' => $vendor,
            'appInfo' => $appInfo,
            'vendorSubscriptionPlans' => $vendorSubscriptionPlans,
        ];

        return $dataArr;
    }

    public function upgradeSubscription($request)
    {
        $transaction = $this->vendorSubscriptionPlanBoughtTransactionService->upgradeSubscription($request);

        if (isset($transaction['error'])) {
            return [
                'msg' => __($transaction['error']),
                'flag' => Constants::danger,
            ];
        }
        $dataArr = [
            'msg' => __('vendor_subscription__transaction_success'),
            'flag' => Constants::successStatus,
        ];

        return $dataArr;
    }
}
