<?php

namespace Modules\StoreFront\VendorPanel\Http\Controllers\Backend\Controllers\Currency;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\StoreFront\VendorPanel\Http\Services\VendorCurrencyService;

class VendorCurrencyController extends Controller
{
    protected $vendorCurrencyService;

    public function __construct(VendorCurrencyService $vendorCurrencyService)
    {
        $this->vendorCurrencyService = $vendorCurrencyService;
    }

    public function index(Request $request)
    {
        return $this->vendorCurrencyService->index($request);
    }

    public function defaultChange($id)
    {
        return $this->vendorCurrencyService->defaultChange($id);
    }
}
