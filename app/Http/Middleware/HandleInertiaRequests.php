<?php

namespace App\Http\Middleware;

use App\Config\Cache\LocalizationCache;
use App\Config\ps_constant;
use App\Http\Contracts\Authorization\PermissionServiceInterface;
use App\Http\Contracts\Image\ImageServiceInterface;
use App\Http\Contracts\Localization\LanguageServiceInterface;
use App\Http\Contracts\Menu\MenuGroupServiceInterface;
use App\Http\Contracts\Menu\SubMenuGroupServiceInterface;
use App\Http\Contracts\Menu\VendorMenuGroupServiceInterface;
use App\Http\Contracts\Menu\VendorMenuServiceInterface;
use App\Http\Contracts\Menu\VendorModuleServiceInterface;
use App\Http\Contracts\Menu\VendorSubMenuGroupServiceInterface;
use App\Http\Contracts\User\UserServiceInterface;
use App\Http\Contracts\Vendor\VendorServiceInterface;
use App\Http\Services\PsService;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Inertia\Middleware;
use Modules\Core\Constants\Constants;
use Modules\Core\Entities\Configuration\Setting;
use Modules\Core\Entities\Localization\Language;
use Modules\Core\Entities\Menu\VendorModule;
use Modules\Core\Entities\Menu\VendorSubMenuGroup;
use Modules\Core\Entities\Vendor\VendorRole;
use Modules\Core\Entities\Vendor\VendorUserPermission;
use Modules\Core\Http\Facades\BackendSettingFacade;
use Modules\Core\Http\Facades\FrontendSettingFacade;
use Modules\Core\Http\Facades\MobileSettingFacade;
use Modules\Core\Http\Facades\PsCache;
use Modules\Core\Http\Facades\PSXBuilderServiceFacade;
use Modules\Core\Http\Services\ApiTokenService;
use Modules\Core\Http\Services\ProjectService;
use Modules\Installer\Helpers\InstalledFileManager;
use Modules\Template\PSXFETemplate\Http\Controllers\FeDashboardController;

class HandleInertiaRequests extends Middleware
{
    private $storage_upload_path = 'storage/'.Constants::folderPath.'/uploads';

    private $storage_thumb1x_path = 'storage/'.Constants::folderPath.'/thumbnail';

    private $storage_thumb2x_path = 'storage/'.Constants::folderPath.'/thumbnail2x';

    private $storage_thumb3x_path = 'storage/'.Constants::folderPath.'/thumbnail3x';

    private $system_img_folder_path = 'images/assets';

    protected $rootView = 'app';

    public function __construct(
        protected ApiTokenService $apiTokenService,
        protected ImageServiceInterface $imageService,
        protected VendorServiceInterface $vendorService,
        protected MenuGroupServiceInterface $menuGroupService,
        protected SubMenuGroupServiceInterface $subMenuGroupService,
        protected ProjectService $projectService,
        protected UserServiceInterface $userService,
        protected VendorSubMenuGroupServiceInterface $vendorSubMenuGroupService,
        protected VendorModuleServiceInterface $vendorModuleService,
        protected VendorMenuServiceInterface $vendorMenuService,
        protected VendorMenuGroupServiceInterface $vendorMenuGroupService,
        protected FeDashboardController $feDashboardController,
        protected LanguageServiceInterface $languageService
    ) {}

    public function share(Request $request): array
    {

        $vendorIds = getNormalAccessVendorIds(); /** @todo refactor after LMP vendorUserPermission and vendorRole */
        $psService = new PsService;

        $backendSetting = BackendSettingFacade::get();
        $mobileSetting = MobileSettingFacade::get();
        $frontendSetting = FrontendSettingFacade::get();

        [$forBE, $forFE, $forVendor] = $this->getBEAndFEAndVendorData($mobileSetting, $psService, $vendorIds, $request);
        $forAll = $this->forAll($frontendSetting, $backendSetting, $mobileSetting, $vendorIds);

        return array_merge(
            parent::share($request),
            $this->firstLoadOnlyProps($request),
            $forBE, $forFE, $forAll, $forVendor
        );

    }

    // //////////////////////////////////////////////////////////////////
    // / Private Functions
    // //////////////////////////////////////////////////////////////////

    // -------------------------------------------------------------------
    // Data Preparations
    // -------------------------------------------------------------------

    private function prepareCondsBackendLogoData()
    {
        return ['img_type' => Constants::backendLogo];
    }

    private function prepareCondsFavIconData()
    {
        return ['img_type' => Constants::backendFavIcon];
    }

    // -------------------------------------------------------------------
    // Database
    // -------------------------------------------------------------------

    private function getBackendLogo()
    {
        return $this->imageService->get($this->prepareCondsBackendLogoData());
    }

    private function getFavIcon()
    {
        return $this->imageService->get($this->prepareCondsFavIconData());
    }

    private function isUserDeleted()
    {
        $isUserDeleted = false;

        $loginUserId = session('loginUserId');
        if ($loginUserId != null) {
            $user = $this->userService->get($loginUserId);
            $isUserDeleted = $user == null ? true : false;
        }

        return $isUserDeleted;
    }

    private function getVendorListAndVendorCurrency($vendorIds, $vendorId)
    {
        $relation = ['logo'];
        if (empty($vendorIds)) {
            $vendorList = [];
            $currentVendor = null;

            return [$vendorList, $currentVendor];
        }

        $vendorList = $this->vendorService->getAll(
            ids: $vendorIds,
            status: Constants::vendorAcceptStatus,
            relation: $relation
        );

        $currentVendor = $this->vendorService->get($vendorId, $relation);

        return [$vendorList, $currentVendor];
    }

    private function getCoreMenuGroup()
    {
        return $this->menuGroupService->getAll(
            isShowOnMenu: Constants::yes,
            relation: 'sub_menu_group',
            isHas: 'sub_menu_group.module',
            ordering: Constants::ascending
        );
    }

    private function getCoreSubMenuGroup()
    {
        return $this->subMenuGroupService->getAll(
            relation: 'module',
            whereNullData: 'core_menu_group_id',
            ordering: Constants::ascending,
        );
    }

    private function getCheckVersionUpdate()
    {
        $checkVersionUpdate = null;

        if (Schema::hasTable('psx_check_version_updates')) {
            $checkVersionUpdate = PSXBuilderServiceFacade::getCheckVersionUpdate();
        }

        return $checkVersionUpdate;
    }

    private function getVendorMenuGroups($vendorId)
    {
        $vendorMenuGroups = [];
        $vendorSubMenuGroups = [];

        if ($vendorId != null) {
            $vendorRole = VendorUserPermission::where('user_id', Auth::id())->first(); /** @todo refactor if LMP finish */
            if ($vendorRole) {
                $vendorRoleObj = json_decode($vendorRole->vendor_and_role);

                $getRoleIds = explode(',', $vendorRoleObj->$vendorId);
                $vendorRoles = VendorRole::whereIn('id', $getRoleIds)->with('vendorRolePermissions')->where('status', 1)->get(); /** @todo refactor if LMP finish */
                $moduleIds = $vendorRoles->flatMap(function ($vendorRole) {
                    return $vendorRole->vendorRolePermissions->flatMap(function ($permission) {
                        // Decode the JSON in 'module_and_permission'
                        $decoded = json_decode($permission->module_and_permission, true);

                        // Return the keys (e.g., ps-0000000001, ps-0000000002, etc.)
                        return array_keys($decoded);
                    });
                })->unique()->values();

                $dropDownSubMenuIds = $this->vendorSubMenuGroupService->getAll(isDropdown: Constants::yes)->pluck(VendorSubMenuGroup::id);

                $linkSubMenuIds = $this->vendorModuleService->getAll(
                    ids: $moduleIds,
                    isNotEmptySubMenuId: Constants::yes
                )->pluck(VendorModule::subMenuId);

                $menuIds = $this->vendorModuleService->getAll(
                    ids: $moduleIds,
                    isNotEmptyMenuId: Constants::yes
                )->pluck(VendorModule::menuId);

                $vendorMenus = $this->vendorMenuService->getAll(
                    relation: ['routeName'],
                    isShowOnMenu: Constants::yes,
                    ids: $menuIds,
                    ordering: Constants::ascending
                );

                $subMenuIds = $dropDownSubMenuIds->merge($linkSubMenuIds);
                $allSubMenuIds = $subMenuIds->merge($vendorMenus->pluck('core_sub_menu_group_id'));

                $vendorSubMenuGroupArr = $this->vendorSubMenuGroupService->getAll(
                    ids: $allSubMenuIds,
                    isShowOnMenu: Constants::yes,
                    relation: ['module' => function ($q) use ($menuIds) {
                        $q->whereIn('id', $menuIds);
                    }, 'icon', 'routeName'],
                    ordering: Constants::ascending
                );
                $vendorSubMenuGroups = json_decode(json_encode($vendorSubMenuGroupArr));

                $vendorMenuGroupsArr = $this->vendorMenuGroupService->getAll(
                    ids: $vendorSubMenuGroupArr->pluck('core_menu_group_id'),
                    isShowOnMenu: Constants::yes,
                    ordering: Constants::ascending
                );
                $vendorMenuGroups = json_decode(json_encode($vendorMenuGroupsArr));

                foreach ($vendorMenuGroups as $vendorMenuGroup) {
                    $vendorMenuGroup->sub_menu_group = [];
                    $hasData = false;
                    foreach ($vendorSubMenuGroups as $vendorSubMenuGroup) {
                        if (! ($vendorSubMenuGroup->is_dropdown == '1' && count($vendorSubMenuGroup->module) == 0) && $vendorSubMenuGroup->core_menu_group_id == $vendorMenuGroup->id) {
                            array_push($vendorMenuGroup->sub_menu_group, $vendorSubMenuGroup);
                        }
                    }
                }
            }

        }

        return $vendorMenuGroups;
    }

    // -------------------------------------------------------------------
    // Other
    // -------------------------------------------------------------------

    private function firbaseConfig($frontendSetting)
    {

        $firebaseConfig = new \stdClass;
        $firebaseConfig->apiKey = '000000000000000000000000000000000000000';
        $firebaseConfig->authDomain = 'flutter-buy-and-sell.firebaseapp.com';
        $firebaseConfig->databaseURL = 'https://flutter-buy-and-sell.firebaseio.com';
        $firebaseConfig->projectId = 'flutter-buy-and-sell';
        $firebaseConfig->storageBucket = 'flutter-buy-and-sell.appspot.com';
        $firebaseConfig->messagingSenderId = '000000000000';
        $firebaseConfig->appId = '1:000000000000:web:0000000000000000000000';
        $firebaseConfig->measurementId = 'G-0000000000';

        $firebaseConfig = json_encode($firebaseConfig);
        $webPushKey = $frontendSetting->firebase_web_push_key_pair;

        $firebaseConfigStr = $frontendSetting->firebase_config;
        if ($frontendSetting->firebase_config == null || $frontendSetting->firebase_config == '') {
            $firebaseConfigStr = $firebaseConfig;
        } else {

            $firebaseConfigObj = json_decode($firebaseConfigStr);
            if (! is_object($firebaseConfigObj) || ! isset($firebaseConfigObj->apiKey)) {
                $firebaseConfigStr = $firebaseConfig;
            }
        }

        return $firebaseConfigStr;
    }

    private function getDomain()
    {
        $dir = config('app.dir');
        if (! empty($dir)) {
            $domain = config('app.url').'/';
        } else {
            $domain = config('app.url');
        }

        return $domain;
    }

    private function isBE()
    {
        $currentUrl = url()->current();
        $domain = $this->getDomain();

        return str_starts_with(substr($currentUrl, strlen($domain)), 'admin');
    }

    private function isVendor()
    {
        $currentUrl = url()->current();
        $domain = $this->getDomain();

        return str_starts_with(substr($currentUrl, strlen($domain)), 'vendor-panel');
    }

    private function isFE()
    {
        return ! $this->isBE() && ! $this->isVendor();
    }

    private function getBEAndFEAndVendorData($mobileSetting, $psService, $vendorIds, $request)
    {
        $forBE = $forFE = $forVendor = [];

        switch (true) {
            case $this->isBE():
                $forBE = $this->forBE($mobileSetting, $psService, $request);
                break;

            case $this->isFE():
                $forFE = $this->forFE();
                break;

            case $this->isVendor():
                $forVendor = $this->forVendor($vendorIds);
                break;
        }

        return [$forBE, $forFE, $forVendor];
    }

    private function getCurrentRouteName()
    {
        return Route::currentRouteName();
    }

    private function hasPermission(User $authUser, $agility) // <-- Correct Type Hint
    {
        return $authUser->can($agility);
    }

    private function forBE($mobileSetting, $psService, $request)
    {
        $installManager = new InstalledFileManager;
        $isDev = $installManager->isDevMode($request);
        $project = $this->projectService->getProject();
        $setting = Setting::where('setting_env', Constants::SYSTEM_CONFIG)->first(); /** @todo change with cache after HA finish */
        $selcted_array = json_decode($setting->setting, true);
        $authUser = Auth::user();

        $forBE = [
            'defaultProfileImg' => asset('/images/assets/default_profile.png'),
            'checkVersionUpdate' => $this->getCheckVersionUpdate(),
            'builderAppInfo' => $isDev ? [] : PSXBuilderServiceFacade::syncBuilderInfo($project),
            'isSubCategoryOn' => $mobileSetting->is_show_subcategory,
            'videoDuration' => $mobileSetting->video_duration,
            'selected_price_type' => (string) $selcted_array['selected_price_type']['id'],
            'menuGroups' => $this->getCoreMenuGroup(),
            'subMenuGroups' => $this->getCoreSubMenuGroup(),
            'project' => $project,
            'purchased_code' => session('purchased_code') ? session('purchased_code') : '',
            'product_relation_errors' => session('product_relation_errors') ? session('product_relation_errors') : '',
            'user_relation_errors' => session('user_relation_errors') ? session('user_relation_errors') : '',
            'city_relation_errors' => session('city_relation_errors') ? session('city_relation_errors') : '',
            'hasError' => session('hasError'),
            'can' => [
                'createProduct' => $this->hasPermission($authUser, 'create-product'),
                'createRole' => $this->hasPermission($authUser, 'create-role'),

                'createPayment' => $this->hasPermission($authUser, 'create-payment'),

                'createAvailableCurrency' => $this->hasPermission($authUser, 'create-availableCurrency'),
                'createLanguage' => $this->hasPermission($authUser, 'create-language'),
                'createLanguageString' => $this->hasPermission($authUser, 'create-languageString'),
                'createPhoneCountryCode' => $this->hasPermission($authUser, 'create-phoneCountryCode'),
                'createApiToken' => $this->hasPermission($authUser, 'create-apiToken'),
                'createCurrency' => $this->hasPermission($authUser, 'create-currency'),
                'createSystemConfig' => $this->hasPermission($authUser, 'create-systemConfig'),
                'createLocationTownship' => $this->hasPermission($authUser, 'create-locationTownship'),
                'createLocationCity' => $this->hasPermission($authUser, 'create-locationCity'),
                'createContactUsMessage' => $this->hasPermission($authUser, 'create-contactUsMessage'),
                'createMobileLanguage' => $this->hasPermission($authUser, 'create-mobileLanguage'),
                'createMobileLanguageString' => $this->hasPermission($authUser, 'create-mobileLanguageString'),
                'createPackageReport' => app(PermissionServiceInterface::class)->permissionControl(Constants::packageReportModule, ps_constant::createPermission) ? true : false,
                'deleteDataReset' => app(PermissionServiceInterface::class)->permissionControl(Constants::dataReset, ps_constant::deletePermission) ? true : false,
                'updateContact' => app(PermissionServiceInterface::class)->permissionControl(Constants::contactModule, ps_constant::updatePermission) ? true : false,
                'deleteContact' => app(PermissionServiceInterface::class)->permissionControl(Constants::contactModule, ps_constant::deletePermission) ? true : false,
                'updateGenerateDeepLink' => app(PermissionServiceInterface::class)->permissionControl(Constants::DeepLinkGenerateModule, ps_constant::updatePermission) ? true : false,
                'updatePaymentSetting' => app(PermissionServiceInterface::class)->permissionControl(Constants::paymentSettingModule, ps_constant::updatePermission) ? true : false,
                'createTableField' => app(PermissionServiceInterface::class)->permissionControl(Constants::tableFieldModule, ps_constant::createPermission) ? true : false,
                'createTable' => $this->hasPermission($authUser, 'create-table'),
                'createPrivacyModule' => $this->hasPermission($authUser, 'create-privacyModule'),
                'createDataDeletionModule' => $this->hasPermission($authUser, 'create-dataDeletionModule'),

                // for frontend language string
                'createFeLanguageString' => $this->hasPermission($authUser, 'create-feLanguageString'),
                'createVendorLanguageString' => $this->hasPermission($authUser, 'create-vendorLanguageString'),

                // for vendor
                'createVendor' => $this->hasPermission($authUser, 'create-vendor'),
            ],
        ];

        return $forBE;
    }

    private function forFE()
    {
        $project = $this->projectService->getProject();

        $forFE = [
            'isUserDeleted' => $this->isUserDeleted(),
            'project' => $project, // only used in Dashboard.vue at feTemplate (but mobile)
            'dashboardScreenInfos' => getScreenInfoByScreenId(ps_constant::dashboardScreenIds),
            'searchAndPopularCategoryComponentIds' => ps_constant::searchAndPopularCategoryComponentIds,
            'categoryHorizontalListComponentIds' => ps_constant::categoryHorizontalListComponentIds,
            'howItsWorkComponentIds' => ps_constant::howItsWorkComponentIds,
            'vendorHorizontalListComponentIds' => ps_constant::vendorHorizontalListComponentIds,
            'featureItemHorizontalListComponentIds' => ps_constant::featureItemHorizontalListComponentIds,
            'recentItemHorizontalListComponentIds' => ps_constant::recentItemHorizontalListComponentIds,
            'popularItemHorizontalListComponentIds' => ps_constant::popularItemHorizontalListComponentIds,
            'vendorCardComponentIds' => ps_constant::vendorCardComponentIds,
            'discountItemHorizontalListComponentIds' => ps_constant::discountItemHorizontalListComponentIds,
            'packageHorizontalListComponentIds' => ps_constant::packageHorizontalListComponentIds,
            'topSellerHorizontalListComponentIds' => ps_constant::topSellerHorizontalListComponentIds,
            'blogHorizontalListComponentIds' => ps_constant::blogHorizontalListComponentIds,
            'mobileShowCaseComponentIds' => ps_constant::mobileShowCaseComponentIds,
            'itemVerticalListWithFilterComponentIds' => ps_constant::itemVerticalListWithFilterComponents,
        ];

        return $forFE;
    }

    private function forVendor($vendorIds)
    {
        $vendorId = getVendorIdFromSession();

        [$vendorList, $currentVendor] = $this->getVendorListAndVendorCurrency($vendorIds, $vendorId);
        $forVendor = [
            'vendorList' => $vendorList,
            'currentVendor' => $currentVendor,
            'currencyId' => $currentVendor?->currency_id,
            'currentVendorId' => $currentVendor?->id,
            'vendorMenuGroups' => $this->getVendorMenuGroups($vendorId),
            'storeCan' => [
                'updateMyVendor' => app(PermissionServiceInterface::class)->vendorPermissionControl(Constants::vendorStoreModule, ps_constant::updatePermission, getVendorIdFromSession()) ? true : false,
                'createPaymentStatus' => app(PermissionServiceInterface::class)->vendorPermissionControl(Constants::vendorPaymentStatusModule, ps_constant::createPermission, getVendorIdFromSession()) ? true : false,
                'createOrderStatus' => app(PermissionServiceInterface::class)->vendorPermissionControl(Constants::vendorOrderStatusModule, ps_constant::createPermission, getVendorIdFromSession()) ? true : false,
            ],

        ];

        return $forVendor;
    }

    private function forAll($frontendSetting, $backendSetting, $mobileSetting, $vendorIds)
    {

        $canAccessVendor = false;
        if (count($vendorIds) > 0 && $backendSetting->vendor_setting == '1') {
            $canAccessVendor = true;
        }

        $setting = Setting::where('setting_env', Constants::SYSTEM_CONFIG)->first(); /** @todo change with cache after HA finish */
        $selcted_array = json_decode($setting->setting, true);

        $forAll = [
            'logMessages' => session('logMessages'),
            'currentRouteName' => $this->getCurrentRouteName(),
            'canAccessAdminPanel' => checkForDashboardPermission(),
            'adsDisplayId' => ! empty($selcted_array['display_ads_id']) ? $selcted_array['display_ads_id'] : '',
            'adsClient' => ! empty($selcted_array['ads_client']) ? $selcted_array['ads_client'] : '',
            'isDisplayGoogleAdsense' => ! empty($selcted_array['is_display_google_adsense']) ? (int) $selcted_array['is_display_google_adsense'] : '',
            'canAccessVendor' => $canAccessVendor,
            'firebaseConfig' => $this->firbaseConfig($frontendSetting),
            'webPushKey' => $frontendSetting->firebase_web_push_key_pair,
            'dateFormat' => $backendSetting->date_format,
            'uploadSetting' => $backendSetting->upload_setting,
            'mapKey' => $backendSetting->map_key,
            'backendSetting' => $backendSetting,
            'mobileSetting' => $mobileSetting,
            'currentRoute' => $this->getCurrentRouteName(),
            'authUser' => Auth::user(),
            'languages' => Language::where('is_publish', 1)->get(), /** @todo to refactor if finish language refactor */
            'backendLogo' => $this->getBackendLogo(),
            'favIcon' => $this->getFavIcon(),
            'uploadUrl' => asset($this->storage_upload_path),
            'thumb1xUrl' => asset($this->storage_thumb1x_path),
            'thumb2xUrl' => asset($this->storage_thumb2x_path),
            'thumb3xUrl' => asset($this->storage_thumb3x_path),
            'sysImageUrl' => asset($this->system_img_folder_path),
            'csrf' => csrf_token(),
            'domain' => config('app.base_domain'),
            'dir' => config('app.dir'),
            'appUrl' => config('app.url'),
            'status' => session('status') ? session('status') : '',
            'message' => session('message'),
        ];

        return $forAll;
    }

    private function getAllLangString()
    {
        $activeLang = ! empty($_COOKIE['activeLanguage']) ? $_COOKIE['activeLanguage'] : $this->getLangSymbol();

        $param = [$activeLang];

        return PsCache::remember([LocalizationCache::BASE], LocalizationCache::GET_ALL_EXPIRY, $param,
            function () use ($activeLang) {
                $langDir = base_path('lang');
                $defaultLangFile = "{$langDir}/en.json";
                $activeLangFile = "{$langDir}/{$activeLang}.json";

                if (File::exists($activeLangFile)) {
                    return File::get($activeLangFile);
                }

                if (File::exists($defaultLangFile)) {
                    return File::get($defaultLangFile);
                }

                if (! File::exists($langDir)) {
                    File::makeDirectory($langDir);
                }

                if (! File::exists($defaultLangFile)) {
                    File::put($defaultLangFile, '[]');
                }

                return File::get($defaultLangFile);
            });

    }

    private function getLangSymbol()
    {
        if (! empty($_COOKIE['activeLanguage'])) {
            return $_COOKIE['activeLanguage'];
        } else {
            $lang = $this->languageService->get(conds: ['status' => 1]);

            return $lang ? $lang->symbol : 'en';
        }
    }

    private function firstLoadOnlyProps($request)
    {
        $firstLoadOnlyProps = $request->hasHeader('X-Inertia') ? [] : [
            'langStrings' => $this->getAllLangString(),
            'langSymbol' => $this->getLangSymbol(),
            'getAppInfo' => $this->feDashboardController->getAppInfo(),
            'api_token' => (string) config('app.api_token'),
        ];

        return $firstLoadOnlyProps;
    }
}
