<?php

namespace Modules\Core\Http\Controllers\Backend\Controllers\Configuration;

use App\Config\Cache\LocalizationCache;
use App\Http\Contracts\Configuration\BackendSettingServiceInterface;
use App\Http\Contracts\Configuration\ColorServiceInterface;
use App\Http\Contracts\Configuration\FrontendSettingServiceInterface;
use App\Http\Contracts\Localization\FeLanguageStringServiceInterface;
use App\Http\Contracts\Utilities\CoreFieldServiceInterface;
use App\Http\Controllers\PsController;
use Illuminate\Http\Request;
use Modules\Core\Constants\Constants;
use Modules\Core\Entities\Configuration\FrontendSetting;
use Modules\Core\Http\Facades\LanguageFacade;
use Modules\Core\Http\Facades\PsCache;
use Modules\Core\Http\Requests\Configuration\StoreFrontendSettingRequest;
use Modules\Core\Http\Requests\Configuration\UpdateFrontendSettingRequest;

class FrontendSettingController extends PsController
{
    private const parentPath = 'frontend_setting';

    private const indexPath = self::parentPath.'/Index';

    private const createPath = self::parentPath.'/Create';

    private const editPath = self::parentPath.'/Edit';

    private const indexRoute = self::parentPath.'.index';

    private const frontendLogoKey = 'frontend_logo';

    private const frontendIconKey = 'frontend_icon';

    private const frontendBannerKey = 'frontend_banner';

    private const appBrandingImageKey = 'app_branding_image';

    private const frontendMetaImageKey = 'frontend_meta_image';

    private const becomeVendorImageKey = 'become_vendor_image';

    private const frontendRegisterImage = 'frontend_register_image';

    private const frontendLoginImage = 'frontend_login_image';

    public function __construct(protected FrontendSettingServiceInterface $frontendSettingService,
        protected CoreFieldServiceInterface $coreFieldService,
        protected ColorServiceInterface $colorService,
        protected FeLanguageStringServiceInterface $frontendLanguageStringService,
        protected BackendSettingServiceInterface $backendSettingService)
    {
        parent::__construct();
    }

    public function index(Request $request)
    {
        // check permission start
        $this->handlePermissionWithModel(FrontendSetting::class, Constants::viewAnyAbility);

        $dataArr = $this->prepareIndexData();

        $dataArr['SettingPage'] = $request->query('page') ?? 0;

        return renderView(self::editPath, $dataArr);
    }

    // not use
    public function store(StoreFrontendSettingRequest $request)
    {
        try {
            $validatedData = $request->validated();

            $frontendLogo = $request->file(self::frontendLogoKey);
            $frontendIcon = $request->file(self::frontendIconKey);
            $frontendBanner = $request->file(self::frontendBannerKey);
            $appBrandingImage = $request->file(self::appBrandingImageKey);
            $frontendMeatImage = $request->file(self::frontendMetaImageKey);

            $this->frontendSettingService->save(frontendSettingData: $validatedData,
                frontendColors: json_decode($request->input('frontendColors')),
                frontendLogo: $frontendLogo,
                frontendIcon: $frontendIcon,
                frontendBanner: $frontendBanner,
                appBrandingImage: $appBrandingImage,
                frontendMetaImage: $frontendMeatImage
            );

            return redirectView(self::indexRoute);
        } catch (\Exception $e) {
            return redirectViewWithError(self::indexRoute, $e->getMessage());
        }
    }

    public function update(UpdateFrontendSettingRequest $request, $id)
    {
        try {
            $validatedData = $request->validated();

            $frontendLogo = $request->file(self::frontendLogoKey);
            $frontendIcon = $request->file(self::frontendIconKey);
            $frontendBanner = $request->file(self::frontendBannerKey);
            $appBrandingImage = $request->file(self::appBrandingImageKey);
            $frontendMeatImage = $request->file(self::frontendMetaImageKey);
            $becomeVendorImage = $request->file(self::becomeVendorImageKey);
            $frontendRegisterImage = $request->file(self::frontendRegisterImage);
            $frontendLoginImage = $request->file(self::frontendLoginImage);

            $this->frontendSettingService->update(id: $id, frontendSettingData: $validatedData,
                frontendColors: json_decode($request->input('frontendColors')),
                frontendLogoId: $request->input('frontend_logo_id'),
                frontendLogo: $frontendLogo,
                frontendIconId: $request->input('frontend_icon_id'),
                frontendIcon: $frontendIcon,
                frontendBannerId: $request->input('frontend_banner_id'),
                frontendBanner: $frontendBanner,
                appBrandingImageId: $request->input('app_branding_image_id'),
                appBrandingImage: $appBrandingImage,
                frontendMetaImageId: $request->input('frontend_meta_image_id'),
                frontendMetaImage: $frontendMeatImage,
                becomeVendorImageId: $request->input('become_vendor_image_id'),
                becomeVendorImage: $becomeVendorImage,
                frontendRegisterImageId: $request->input('frontend_register_image_id'),
                frontendRegisterImage: $frontendRegisterImage,
                frontendLoginImageId: $request->input('frontend_login_image_id'),
                frontendLoginImage: $frontendLoginImage
            );

            return redirectView(self::indexRoute);
        } catch (\Exception $e) {
            return redirectViewWithError(self::indexRoute, $e->getMessage(), $id);
        }
    }

    public function languageRefresh(Request $request)
    {
        $languageId = $request->input('languageId');
        $this->frontendLanguageStringService->generateJsonFiles($languageId);
        PsCache::clear(LocalizationCache::BASE);

        return redirectView(self::indexRoute, null, 'langSuccess', ['page=4']);
    }

    public function colorGenerate(Request $request)
    {
        try {
            $frontendColors = $request->input('frontendColors');

            $dataArr = $this->frontendSettingService->colorGenerate($frontendColors);
            $msg = 'Color Generated Successfully';

            return redirectView(self::indexRoute, $dataArr['msg'], 'colorSuccess', ['page=6']);
        } catch (\Exception $e) {
            return redirectViewWithError(self::indexRoute, $e->getMessage(), null, ['page=6']);
        }
    }

    // //////////////////////////////////////////////////////////////////
    // / Private Functions
    // //////////////////////////////////////////////////////////////////

    // -------------------------------------------------------------------
    // Data Preparation
    // -------------------------------------------------------------------
    private function prepareIndexData()
    {
        $relation = ['frontend_logo', 'frontend_banner', 'frontend_icon', 'frontend_meta_image', 'app_branding_image', 'become_vendor_image', 'frontend_register_image', 'frontend_login_image'];
        $frontendSetting = $this->frontendSettingService->get(id: null, relation: $relation);
        $backendSetting = $this->backendSettingService->get();

        $coreFieldFilterSettings = $this->coreFieldService->getAll(code: Constants::frontendSetting,
            relation: null, limit: null, offset: null, isDel: 0, withNoPag: 1
        );

        $frontendColorsConds['fe_color'] = 1;
        $frontendColors = $this->colorService->getAll(null, null, $frontendColorsConds);

        $languages = LanguageFacade::getAll();

        $keyValueArr = [
            'updateFrontendSetting' => 'update-frontendSetting',
        ];

        return [
            'frontend_setting' => $frontendSetting,
            'backend_setting' => $backendSetting,
            'coreFieldFilterSettings' => $coreFieldFilterSettings,
            'available_languages' => $this->getAvailableLanguages(),
            'frontendColors' => $frontendColors,
            'languages' => $languages,
            'can' => $this->permissionService->checkingForCreateAbilityWithModel($keyValueArr),
        ];
    }

    // -------------------------------------------------------------------
    // Others
    // -------------------------------------------------------------------
    private function getAvailableLanguages()
    {
        return [
            ['language_code' => 'en', 'country_code' => 'US', 'name' => 'English'],
            ['language_code' => 'ar', 'country_code' => 'DZ', 'name' => 'Arabic'],
            ['language_code' => 'hi', 'country_code' => 'IN', 'name' => 'Hindi'],
            ['language_code' => 'de', 'country_code' => 'DE', 'name' => 'German'],
            ['language_code' => 'es', 'country_code' => 'ES', 'name' => 'Spainish'],
            ['language_code' => 'fr', 'country_code' => 'FR', 'name' => 'French'],
            ['language_code' => 'id', 'country_code' => 'ID', 'name' => 'Indonesian'],
            ['language_code' => 'it', 'country_code' => 'IT', 'name' => 'Italian'],
            ['language_code' => 'ja', 'country_code' => 'JP', 'name' => 'Japanese'],
            ['language_code' => 'ko', 'country_code' => 'KR', 'name' => 'Korean'],
            ['language_code' => 'ms', 'country_code' => 'MY', 'name' => 'Malay'],
            ['language_code' => 'pt', 'country_code' => 'PT', 'name' => 'Portuguese'],
            ['language_code' => 'ru', 'country_code' => 'RU', 'name' => 'Russian'],
            ['language_code' => 'th', 'country_code' => 'TH', 'name' => 'Thai'],
            ['language_code' => 'tr', 'country_code' => 'TR', 'name' => 'Turkish'],
            ['language_code' => 'zh', 'country_code' => 'CN', 'name' => 'Chinese'],
        ];
    }
}
