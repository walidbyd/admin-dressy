<?php

namespace Modules\Core\Http\Services\Item;

use App\Http\Contracts\Financial\ItemCurrencyServiceInterface;
use App\Http\Services\PsService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Modules\Core\Constants\Constants;
use Modules\Core\Entities\Item\Package;
use Modules\Core\Exports\PackageReportExport;
use Modules\Core\Http\Services\User\UserInfoService;

class PackageService extends PsService
{
    protected $successStatus;

    protected $createdStatusCode;

    protected $okStatusCode;

    protected $internalServerErrorStatusCode;

    protected $noContentStatusCode;

    protected $notFoundStatusCode;

    protected $packageIdCol;

    protected $packageTitleCol;

    protected $packagePriceCol;

    protected $packageCurrencyIdCol;

    protected $packageIapPrdIdCol;

    protected $packageTypeCol;

    protected $publish;

    protected $unPublish;

    protected $warningFlag;

    protected $successFlag;

    protected $dangerFlag;

    protected $packageStatusCol;

    public function __construct(
        protected ItemCurrencyServiceInterface $itemCurrencyService,
        protected UserInfoService $userInfoService
    ) {
        $this->successStatus = Constants::successStatus;
        $this->createdStatusCode = Constants::createdStatusCode;
        $this->okStatusCode = Constants::okStatusCode;
        $this->internalServerErrorStatusCode = Constants::internalServerErrorStatusCode;
        $this->noContentStatusCode = Constants::noContentStatusCode;
        $this->notFoundStatusCode = Constants::notFoundStatusCode;
        $this->packageIdCol = Package::id;
        $this->packageTitleCol = Package::title;
        $this->packagePriceCol = Package::price;
        $this->packageCurrencyIdCol = Package::currency_id;

        $this->publish = Constants::publish;
        $this->unPublish = Constants::unPublish;
        $this->warningFlag = Constants::warning;
        $this->successFlag = Constants::success;
        $this->dangerFlag = Constants::danger;
        $this->packageStatusCol = Package::status;
    }

    public function getPackages($relation = null, $status = null, $limit = null, $offset = null, $conds = null)
    {
        $packages = Package::when($relation, function ($q, $relation) {
            $q->with($relation);
        })
            ->when($status, function ($q, $status) {
                $q->where($this->packageStatusCol, $status);
            })
            ->when($limit, function ($query, $limit) {
                $query->limit($limit);
            })
            ->when($offset, function ($query, $offset) {
                $query->offset($offset);
            })
            ->when($conds, function ($query, $conds) {
                // search term
                if (isset($conds['keyword']) && $conds['keyword']) {
                    $query->where($this->packageTitleCol, 'LIKE', '%'.$conds['keyword'].'%');
                }

                if (isset($conds['currency_id']) && $conds['currency_id']) {
                    $query->where($this->packageCurrencyIdCol, $conds['currency_id']);
                }

                // order by
                if (isset($conds['order_by']) && isset($conds['order_type']) && $conds['order_by'] && $conds['order_type']) {
                    $query->orderBy($conds['order_by'], $conds['order_type']);
                } elseif (isset($conds['order_by']) && $conds['order_by']) {
                    $query->orderBy($conds['order_by']);
                }
            })
            ->latest()->get();

        return $packages;
    }

    public function getPackage($id = null)
    {
        $package = Package::when($id, function ($q, $id) {
            $q->where($this->packageIdCol, $id);
        })
            ->first();

        return $package;
    }

    public function index()
    {
        $relation = ['owner', 'editor', 'currency'];
        $packages = $this->getPackages($relation);

        $dataArr = [
            'packages' => $packages,
        ];

        return $dataArr;
    }

    public function create()
    {
        $currencies = $this->itemCurrencyService->getAll(status: Constants::publish, noPagination: Constants::yes);
        $dataArr = [
            'currencies' => $currencies,
        ];

        return $dataArr;
    }

    public function store($request)
    {
        DB::beginTransaction();
        try {
            $package = new Package;
            $package->title = $request->title;
            $package->price = $request->price;
            $package->currency_id = $request->currency_id;
            $package->post_count = $request->post_count;
            $package->added_user_id = Auth::user()->id;
            $package->save();

            DB::commit();

        } catch (\Throwable $e) {
            DB::rollBack();

            return ['error' => $e->getMessage()];
        }

        //        return $package;
    }

    public function update($id, $request)
    {

        DB::beginTransaction();
        try {
            $package = Package::findOrFail($id);
            $package->title = $request->title;
            $package->price = $request->price;
            $package->currency_id = $request->currency_id;
            $package->post_count = $request->post_count;
            $package->package_in_app_purchase_prd_id = $request->package_in_app_purchase_prd_id;
            $package->type = $request->type;
            $package->updated_user_id = Auth::user()->id;
            $package->update();

            DB::commit();

        } catch (\Throwable $e) {
            DB::rollBack();

            return ['error' => $e->getMessage()];
        }

        //        return $package;
    }

    public function destroy($id)
    {
        $package = $this->getPackage($id);
        $title = $package->title;
        $package->delete();

        $status = [
            // 'msg' => 'The '.$title.' row has been deleted successfully.',
            'msg' => __('core__be_delete_success', ['attribute' => $title]),
            'flag' => $this->dangerFlag,
        ];

        return $status;
    }

    // for package report
    public function packageReportIndex()
    {
        $packages = Package::with(['cover', 'icon'])
            ->withCount(['category_touch'])
            ->orderBy('category_touch_count', 'desc')
            ->latest()
            ->get();
        $dataArr = [
            'packages' => $packages,
        ];

        return $dataArr;
    }

    public function packageReportShow($id)
    {
        $package = Package::where($this->catIdCol, $id)->with(['cover', 'icon'])->first();
        $dataArr = [
            'package' => $package,
        ];

        return $dataArr;
    }

    public function packageReportCsvExport()
    {
        $filename = newFileNameForExport($this->csvFileName);

        return (new PackageReportExport)->download($filename, \Maatwebsite\Excel\Excel::CSV, [
            'Content-Type' => 'text/csv',
        ]);
    }

    /**
     * @coveredBy testIsPaidItemUploadSettingEnabled_*
     */
    public function isPaidItemUploadSettingEnabled($systemConfig): bool
    {

        if (isset($systemConfig->is_paid_app) && $systemConfig->is_paid_app == 1) {
            return true;
        }

        return false;
    }

    /**
     * @coveredBy testHasSufficientBalance_*
     */
    public function hasSufficientBalance($userRemainingPostInfo, $user): bool
    {

        if (isset($user->role_id) && $user->role_id == Constants::superAdminRoleId) {
            return true;
        }

        if (isset($userRemainingPostInfo->value)
            && is_numeric($userRemainingPostInfo->value)
            && $userRemainingPostInfo->value > 0

        ) {
            return true;
        }

        return false;
    }

    /**
     * @coveredBy testConsumeBalance_*
     */
    public function consumeBalance($systemConfig, $user, $userRemainingPostInfo): ?int
    {
        // Check setting
        if (! $this->isPaidItemUploadSettingEnabled($systemConfig)) {
            return null;
        }

        // Check user role
        if (isset($user->role_id) && $user->role_id == Constants::superAdminRoleId) {
            return null;
        }

        // Validate
        if (! isset($userRemainingPostInfo->value) || ! is_numeric($userRemainingPostInfo->value)) {
            throw new \RuntimeException('Invalid user remaining post data.');
        }
        if ($userRemainingPostInfo->value <= 0) {
            throw new \RuntimeException('Insufficient Balance.');
        }
        if (! isset($userRemainingPostInfo->user_id)) {
            throw new \RuntimeException('User Id is missing.');
        }

        // Consume Blanance
        $balance = $userRemainingPostInfo->value - 1;
        $userInfoData = [Constants::usrRemainingPost => (int) $balance];
        $this->userInfoService->update($userRemainingPostInfo->user_id, $userInfoData);

        return $balance;
    }
}
