<?php

namespace Modules\Core\Actions\Item;

use App\Config\Cache\CategoryCache;
use App\Config\Cache\ItemCache;
use App\Config\Cache\VendorCache;
use App\Config\ps_constant;
use App\Exceptions\PsApiException;
use App\Http\Contracts\Authorization\PermissionServiceInterface;
use App\Http\Contracts\Configuration\BackendSettingServiceInterface;
use App\Http\Contracts\Configuration\SettingServiceInterface;
use App\Http\Contracts\Configuration\SystemConfigServiceInterface;
use App\Http\Contracts\Item\ItemInfoServiceInterface;
use App\Http\Contracts\User\UserInfoServiceInterface;
use App\Http\Contracts\User\UserServiceInterface;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Modules\Core\Constants\Constants;
use Modules\Core\DTOs\ItemDto;
use Modules\Core\Entities\Configuration\SystemConfig;
use Modules\Core\Entities\CoreImage;
use Modules\Core\Http\Facades\PsCache;
use Modules\Core\Http\Requests\Item\StoreItemApiRequest;
use Modules\Core\Http\Services\Image\ImageService;
use Modules\Core\Http\Services\Item\ItemService;
use Modules\Core\Http\Services\Utilities\VideoService;
use Modules\Core\Http\Services\Vendor\VendorService;
use Throwable;

class UpdateItemAction
{
    public function __construct(
        protected ItemService $itemService,
        protected ItemInfoServiceInterface $itemInfoService,
        protected UserInfoServiceInterface $userInfoService,
        protected SystemConfigServiceInterface $systemConfigService,
        protected UserServiceInterface $userService,
        protected PermissionServiceInterface $permissionService,
        protected BackendSettingServiceInterface $backendSettingService,
        protected SettingServiceInterface $settingService,
        protected GenerateItemDeeplinkAction $generateItemDeeplink,
        protected ImageService $imageService,
        protected VideoService $videoService,
        protected VendorService $vendorService

    ) {}

    public function handle($id, StoreItemApiRequest $request)
    {

        DB::beginTransaction();

        try {

            // Convert to DTO
            $itemDto = ItemDto::from($request);

            // init contexts
            [
                'systemConfig' => $systemConfig,
                'backendSetting' => $backendSetting,
            ] = $this->initSettings($itemDto);
            [
                'userRemainingPostInfo' => $userRemainingPostInfo,
                'user' => $user,
                'blueMarkUserInfo' => $blueMarkUserInfo
            ] = $this->prepareData($itemDto);

            // Validate
            $this->validate($itemDto, $user, $userRemainingPostInfo, $systemConfig, $backendSetting, $blueMarkUserInfo);

            // Update Item
            $item = $this->UpdateItem($itemDto, $systemConfig);

            DB::commit();

            // clear cache
            PsCache::clear(ItemCache::BASE);
            PsCache::clear(CategoryCache::BASE);
            PsCache::clear(VendorCache::BASE);

            return $item;

        } catch (Throwable $e) {
            DB::rollBack();
            throw new PsApiException($e->getMessage(), Constants::internalServerErrorStatusCode);
            // throw new PsApiException($e->getMessage().$e->getFile().$e->getLine(), Constants::internalServerErrorStatusCode);
        }
    }

    // //////////////////////////////////////////////////////////////////
    // / Private Functions
    // //////////////////////////////////////////////////////////////////
    private function initSettings(): array
    {

        $systemConfig = $this->systemConfigService->get();
        $backendSetting = $this->backendSettingService->get();

        return compact('systemConfig', 'backendSetting');
    }

    private function prepareData(ItemDto $itemDto): array
    {
        $userRemainingPostInfo = $this->userInfoService->get(null, null, $itemDto->loginUserId, Constants::usrRemainingPost);

        $user = $this->userService->get(conds: ['id' => $itemDto->loginUserId]);

        $blueMarkUserInfo = $this->userInfoService->get(
            parentId: $user->id,
            coreKeysId: Constants::usrIsVerifyBlueMark
        );

        return compact('userRemainingPostInfo', 'user', 'blueMarkUserInfo');
    }

    /**
     * @coveredBy testValidate*
     */
    private function validate(
        ItemDto $itemDto,
        $user,
        $userRemainingPostInfo,
        $systemConfig,
        $backendSetting,
        $blueMarkUserInfo)
    {

        if (! isset($itemDto->id) && empty($itemDto->id)) {
            throw new PsApiException(__('core__be_item_invalid', [], $itemDto->languageSymbol), Constants::badRequestStatusCode);
        }

        // Check owner
        if ((int) $itemDto->loginUserId !== (int) $itemDto->addedUserId && $user->role_id != Constants::superAdminRoleId) {
            $message = __('core__api_update_no_permission', [], $itemDto->languageSymbol);
            throw new PsApiException($message, Constants::forbiddenStatusCode);
        }

        // Check vendor
        if (filled($itemDto->vendorId)) {

            // Checking Vendor Permission
            if (! $this->permissionService->vendorPermissionControl(
                Constants::vendorItemModule,
                ps_constant::createPermission,
                $itemDto->vendorId,
                $itemDto->loginUserId)) {
                $msg = __('core__api_update_no_permission_for_vendor', [], $itemDto->languageSymbol);
                throw new PsApiException($msg, Constants::forbiddenStatusCode);
            }

            // Checking Vendor Default Currency
            $vendor = $this->vendorService->get($itemDto->vendorId);
            if ($vendor->currency_id == null) {
                throw new PsApiException(__('core__api_vendor_currency_error', [], $itemDto->languageSymbol), Constants::badRequestStatusCode);
            }
        }

        // check user have upload perimission depend on setting ? still need ?
        $userHasPermission = $this->userService->userHasUploadPermission(
            $backendSetting?->upload_setting,
            $user?->role_id,
            $blueMarkUserInfo?->value,
            true
        );
        if (! $userHasPermission) {
            throw new PsApiException(__('core__api_item_upload_not_allow', [], $itemDto->languageSymbol), Constants::forbiddenStatusCode);
        }
    }

    /**
     * @coveredBy testUpdateItem*
     */
    private function UpdateItem(ItemDto $itemDto, SystemConfig $systemConfig)
    {

        // Prepare spcial data handling
        $itemDto = $itemDto->copyWith(
            status: $this->itemService->prepareStatusData($itemDto->status, $systemConfig),
            currencyId: $this->itemService->prepareCurrencyIdData($itemDto->currencyId),
            percent: $this->itemService->preparePercentData($itemDto->percent),
            isDiscount: $this->itemService->prepareisDiscountData($itemDto->percent),
            originalPrice: $this->itemService->prepareOriginalPriceData($itemDto->originalPrice),
            price: $this->itemService->preparePriceData($itemDto->originalPrice, $itemDto->percent, $itemDto->price),
            isPaid: $this->itemService->prepareIsPaidData($itemDto->isPaid),
            vendorId: $this->itemService->prepareVendorIdData($itemDto->vendorId),
            addedUserId: ! empty($itemDto->addedUserId) ? $itemDto->addedUserId : Auth::user()->id,
        );

        // Create new items   
        $item = $this->itemService->updateV2($itemDto->id, $itemDto);

        // Save video icon ( video service )
        // Save video ( video service )
        if (! empty($itemDto->videoIcon)) {
            $vidoeIconImgData = $this->prepareSaveImageData($item->id, 'item-video-icon');
            $this->imageService->save($itemDto->videoIcon, $vidoeIconImgData);
        }

        if (! empty($itemDto->video)) {
            $itemVideoData = $this->prepareSaveVideoData($item->id, 'item-video');
            $this->videoService->saveVideo($itemDto->video, $itemDto->video->getClientOriginalExtension(), $itemVideoData, $itemDto->loginUserId);
        }

        // Generate Deeplink ( deeplink service )
        $item = $this->generateItemDeeplink->handle($item->id);

        // Save info ( item info service )
        $this->itemService->updateItemInfo($item->id, $itemDto->customFields, $item->category_id);

        return $item;
    }

    private function prepareSaveVideoData($id, $imgType)
    {
        return [
            'img_parent_id' => $id,
            'img_type' => $imgType,
        ];
    }

    private function prepareSaveImageData($id, $imgType, $imgDesc = null, $order = null)
    {
        $imageData = [
            CoreImage::imgParentId => $id,
            CoreImage::imgType => $imgType,
        ];

        if ($imgDesc) {
            $imageData[CoreImage::imgDesc] = $imgDesc;
        }

        if ($order) {
            $imageData[CoreImage::ordering] = $order;
        }

        return $imageData;
    }

    private function isUploadNotAllowed($uploadSetting, $roleId, $isVerifyBlueMark)
    {
        if ($uploadSetting == 'admin-bluemark') {
            return $roleId != 1 && $isVerifyBlueMark != 1;
        }

        if ($uploadSetting == 'admin') {
            return $roleId != 1;
        }

        return false; // Default to allow upload if no conditions match
    }
}
