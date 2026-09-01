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
use Modules\Core\Http\Services\Item\PackageService;
use Modules\Core\Http\Services\User\UserService;
use Modules\Core\Http\Services\Utilities\VideoService;
use Throwable;

class CreateItemAction
{
    public function __construct(

        protected ItemService $itemService,
        protected ItemInfoServiceInterface $itemInfoService,
        protected UserInfoServiceInterface $userInfoService,
        protected SystemConfigServiceInterface $systemConfigService,
        protected UserService $userService,
        protected PermissionServiceInterface $permissionService,
        protected BackendSettingServiceInterface $backendSettingService,
        protected SettingServiceInterface $settingService,
        protected GenerateItemDeeplinkAction $generateItemDeeplink,
        protected PackageService $packageService,
        protected ImageService $imageService,
        protected VideoService $videoService
    ) {}

    public function handle(StoreItemApiRequest $request)
    {
        DB::beginTransaction();

        try {

            // Convert to DTO
            $itemDto = ItemDto::from($request);

            // init contexts
            [
                'systemConfig' => $systemConfig,
                'backendSetting' => $backendSetting,
            ] = $this->initSettings();
            [
                'userRemainingPostInfo' => $userRemainingPostInfo,
                'user' => $user,
                'blueMarkUserInfo' => $blueMarkUserInfo
            ] = $this->prepareData($itemDto);

            // Validate
            $this->validate($itemDto, $user, $userRemainingPostInfo, $systemConfig, $backendSetting, $blueMarkUserInfo);

            // Create New Item
            $item = $this->createNewItem($itemDto, $systemConfig);

            // Send notification ( notification service )
            try {
                $this->itemService->sendSubscribeNoti($item);
            } catch (Throwable $_) {
                // Ignore this error for now
                // @todo review it how to handle this.
            }

            // Consume Balance
            // Only consume if not vendor post
            // Note : it will ignore for the super admin
            if (empty($vendorId)) {
                $this->packageService->consumeBalance($systemConfig, $user, $userRemainingPostInfo);
            }

            DB::commit();

            // clear cache
            PsCache::clear(ItemCache::BASE);
            PsCache::clear(CategoryCache::BASE);
            PsCache::clear(VendorCache::BASE);

            return $item;

        } catch (Throwable $e) {
            DB::rollBack();
            throw $e;
            // throw new PsApiException($e->getMessage().$e->getFile().$e->getLine(), Constants::internalServerErrorStatusCode);
        }
    }

    // //////////////////////////////////////////////////////////////////
    // / Private Functions
    // //////////////////////////////////////////////////////////////////
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

        if (isset($itemDto->id) && ! empty($itemDto->id)) {
            throw new PsApiException(__('core__api_item_id_not_allow_to_create', [], $itemDto->languageSymbol), Constants::badRequestStatusCode);
        }

        // check vendor permission
        if (! empty($itemDto->vendorId)
            && ! $this->permissionService->vendorPermissionControl(Constants::vendorItemModule, ps_constant::createPermission, $itemDto->vendorId, $itemDto->loginUserId)
        ) {
            $msg = __('core__api_update_no_permission_for_vendor', [], $itemDto->languageSymbol);
            throw new PsApiException($msg, Constants::forbiddenStatusCode);
        }

        // check user have upload perimission depend on setting
        $userHasPermission = $this->userService->userHasUploadPermission(
            $backendSetting?->upload_setting,
            $user?->role_id,
            $blueMarkUserInfo?->value,
            $itemDto->vendorId
        );
        if (! $userHasPermission) {
            throw new PsApiException(__('core__api_item_upload_not_allow', [], $itemDto->languageSymbol), Constants::forbiddenStatusCode);
        }

        // If paid item uplolad setting is enable
        // check user has sufficient balance
        // Note : super admin can upload even don't have balance.
        if ($this->packageService->isPaidItemUploadSettingEnabled($systemConfig)) {
            if (! $this->packageService->hasSufficientBalance($userRemainingPostInfo, $user)) {
                throw new PsApiException(__('core__api_not_enought_to_post', [], $itemDto->languageSymbol), Constants::badRequestStatusCode);
            }
        }

    }

    /**
     * @coveredBy testCreateNewItem*
     */
    private function createNewItem(ItemDto $itemDto, SystemConfig $systemConfig)
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
        $item = $this->itemService->create($itemDto);

        // Save dropzone images ( image service )
        $this->imageService->saveDropzoneMultiImage([], $item->id, $itemDto);

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
        $this->itemInfoService->save($item->id, $itemDto->customFields);

        return $item;
    }

    /**
     * @coveredBy testPrepareSaveVideoData*
     */
    private function prepareSaveVideoData($id, $imgType)
    {
        return [
            'img_parent_id' => $id,
            'img_type' => $imgType,
        ];
    }

    /**
     * @coveredBy testPrepareSaveImageData*
     */
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

    /**
     * @coveredBy testInitSettings
     */
    private function initSettings(): array
    {

        $systemConfig = $this->systemConfigService->get();
        $backendSetting = $this->backendSettingService->get();

        return compact('systemConfig', 'backendSetting');
    }

    /**
     * @coveredBy testPrepareData
     */
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
}
