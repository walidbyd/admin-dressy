<?php

namespace Modules\StoreFront\VendorPanel\Http\Controllers\Backend\Controllers\Item;

use App\Config\ps_constant;
use App\Http\Controllers\PsController;
use Illuminate\Http\Request;
use Modules\Core\Constants\Constants;
use Modules\Core\Http\Services\VendorItemService;

class VendorItemController extends PsController
{
    const parentPath = 'Pages/vendor/views/item/';

    const indexPath = self::parentPath.'ItemList';

    const createPath = self::parentPath.'Create';

    const editPath = self::parentPath.'ItemDetail';

    const indexRoute = 'vendor_item.index';

    protected $itemService;

    public function __construct(VendorItemService $itemService)
    {
        parent::__construct();

        $this->itemService = $itemService;

    }

    public function index(Request $request)
    {

        // check permission start
        $vendorId = getVendorIdFromSession();
        $this->handleVendorPermission(Constants::vendorItemModule, ps_constant::readPermission, $vendorId);

        $dataArr = $this->itemService->index($request);

        return renderView(self::indexPath, $dataArr);
    }

    public function destroy($id)
    {

        $dataArr = $this->itemService->destroy($id, self::indexRoute);
        // check permission
        $checkPermission = $dataArr['checkPermission'];
        if ($checkPermission == false) {
            return redirect()->back();
        }

        return redirectView(self::indexRoute, $dataArr['msg'], $dataArr['flag']);
    }

    public function edit($id)
    {
        $relation = ['category', 'subcategory', 'city', 'township', 'currency', 'owner', 'itemRelation', 'cover', 'video', 'icon'];
        $dataArr = $this->itemService->itemReportShow($id, $relation);

        return renderView(self::editPath, $dataArr);
    }
}
