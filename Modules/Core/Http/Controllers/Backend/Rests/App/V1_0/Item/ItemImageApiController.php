<?php

namespace Modules\Core\Http\Controllers\Backend\Rests\App\V1_0\Item;

use App\Config\ps_constant;
use App\Exceptions\PsApiException;
use App\Http\Contracts\Configuration\MobileSettingServiceInterface;
use App\Http\Contracts\Configuration\SystemConfigServiceInterface;
use App\Http\Contracts\Image\ImageServiceInterface;
use App\Http\Contracts\Item\ItemServiceInterface;
use App\Http\Controllers\PsApiController;
use Illuminate\Support\Facades\DB;
use Modules\Core\Constants\Constants;
use Modules\Core\Entities\CoreImage;
use Modules\Core\Http\Requests\Item\CoverUploadItemApiRequest;
use Modules\Core\Http\Requests\Item\DestroyImageItemApiRequest;
use Modules\Core\Http\Requests\Item\GetGalleryListItemApiRequest;
use Modules\Core\Http\Requests\Item\IconUploadItemApiRequest;
use Modules\Core\Http\Requests\Item\ReorderImagesItemApiRequest;
use Modules\Core\Http\Requests\Item\UploadMultiItemRequest;
use Modules\Core\Transformers\Api\App\V1_0\CoreImage\CoreImageApiResource;

class ItemImageApiController extends PsApiController
{
    public function __construct(
        protected ItemServiceInterface $itemService,
        protected ImageServiceInterface $imageService,
        protected MobileSettingServiceInterface $mobileSettingService,
        protected SystemConfigServiceInterface $systemConfigService,
    ) {
        parent::__construct();

    }

    public function uploadMulti(UploadMultiItemRequest $request)
    {
        try {
            $validData = $request->validated();
            $file = $request->file('file');

            return $this->itemService->updateMultiImage($validData, $file);
        } catch (\Throwable $e) {
            throw new PsApiException($e->getMessage(), Constants::internalServerErrorStatusCode);
        }
    }

    public function getGalleryList(GetGalleryListItemApiRequest $request)
    {
        $validatedData = $request->validated();
        $imgType = $validatedData[CoreImage::imgType] ?? 'item_related';
        [$limit, $offset] = $this->getLimitOffsetFromSetting($request);

        $conds['order_by'] = 'ordering';
        $conds['order_type'] = 'asc';

        $data = CoreImageApiResource::collection(
            $this->imageService->getAll(
                imgParentId: $validatedData[CoreImage::imgParentId],
                imgType: $imgType,
                limit: $limit,
                offset: $offset,
                conds: $conds
            )
        );

        return $this->handleNoDataResponse($offset, $data);
    }

    public function destroyImage(DestroyImageItemApiRequest $request)
    {
        $validatedData = $request->validated();
        $loginUserId = $request->query('login_user_id');
        $langSymbol = $request->query('language_symbol');
        $headerToken = $request->header(ps_constant::deviceTokenKeyFromApi);

        $imageObj = $this->imageService->get(['id' => $validatedData['img_id']]);
        $itemObj = $this->itemService->get($imageObj->img_parent_id);

        // check permission start
        $this->checkApiPermissionAndOwnerShip($loginUserId, $headerToken, $langSymbol, $itemObj->added_user_id);
        // check permission end

        try {
            // delete File
            $this->imageService->delete($imageObj->img_path);
            $imageObj->delete();

            return responseMsgApi(
                __('core__api_delete_image_success', [], $langSymbol),
                Constants::okStatusCode,
                Constants::successStatus
            );
        } catch (\Throwable $e) {
            throw new PsApiException(__('core__api_db_error', [], $langSymbol), Constants::internalServerErrorStatusCode);
        }
    }

    public function coverUpload(CoverUploadItemApiRequest $request)
    {
        $validatedData = $request->validated();
        $itemId = $validatedData['item_id'];
        $imgId = $validatedData['img_id'];
        $file = $request->file('cover');
        $loginUserId = $request->query('login_user_id');
        $langSymbol = $request->query('language_symbol');
        $headerToken = $request->header(ps_constant::deviceTokenKeyFromApi);

        $itemObj = $this->itemService->get($itemId);

        // check permission start
        $this->checkApiPermissionAndOwnerShip($loginUserId, $headerToken, $langSymbol, $itemObj->added_user_id);
        // check permission end

        $this->checkMaxImageUpload($itemId, $langSymbol);

        DB::beginTransaction();
        try {

            $imgData = $this->prepareImageData($itemId, Constants::itemCoverImgType, $validatedData['ordering']);
            if (empty($imgId)) {
                $this->imageService->save($file, $imgData);
            } else {
                $this->imageService->update($imgId, $file, $imgData);
            }

            $this->itemService->generateDeeplink($itemId);

            $data = new CoreImageApiResource($this->imageService->get($imgData));

            DB::commit();

            return responseDataApi($data);
        } catch (\Throwable $e) {
            DB::rollBack();
            throw new PsApiException(__('core__api_db_error', [], $langSymbol), Constants::internalServerErrorStatusCode);
        }
    }

    public function iconUpload(IconUploadItemApiRequest $request)
    {
        $validatedData = $request->validated();
        $itemId = $validatedData['item_id'];
        $imgId = $validatedData['img_id'];
        $file = $request->file('video_icon');
        $loginUserId = $request->query('login_user_id');
        $langSymbol = $request->query('language_symbol');
        $headerToken = $request->header(ps_constant::deviceTokenKeyFromApi);

        $itemObj = $this->itemService->get($itemId);

        // check permission start
        $this->checkApiPermissionAndOwnerShip($loginUserId, $headerToken, $langSymbol, $itemObj->added_user_id);
        // check permission end

        $this->checkMaxImageUpload($itemId, $langSymbol);

        DB::beginTransaction();
        try {

            $imgData = $this->prepareImageData($itemId, Constants::itemVideoIconImgType);
            if (empty($imgId)) {
                $this->imageService->save($file, $imgData);
            } else {
                $this->imageService->update($imgId, $file, $imgData);
            }

            $data = new CoreImageApiResource($this->imageService->get($imgData));

            DB::commit();

            return responseDataApi($data);
        } catch (\Throwable $e) {
            DB::rollBack();
            throw new PsApiException(__('core__api_db_error', [], $langSymbol), Constants::internalServerErrorStatusCode);
        }
    }

    public function reorderImages(ReorderImagesItemApiRequest $request)
    {
        $validatedData = $request->validated();
        $loginUserId = $request->query('login_user_id');
        $langSymbol = $request->query('language_symbol');
        $headerToken = $request->header(ps_constant::deviceTokenKeyFromApi);

        foreach ($validatedData as $validatedObj) {
            $image = $this->imageService->get([CoreImage::id => $validatedObj['img_id']]);
            $itemId = $image->img_parent_id;
            $ownerId = $this->itemService->get($itemId)->added_user_id;

            // check permission start
            $this->checkApiPermissionAndOwnerShip($loginUserId, $headerToken, $langSymbol, $ownerId);
            // check permission end

            $image->ordering = $validatedObj[CoreImage::ordering];
            $image->updated_user_id = $loginUserId;
            $image->update();
        }

        $this->itemService->generateDeeplink($itemId);
        $msg = __('core__api_success_image_reorder', [], $langSymbol);

        return responseMsgApi($msg, Constants::createdStatusCode, Constants::successStatus);
    }

    // //////////////////////////////////////////////////////////////////
    // / Private Functions
    // //////////////////////////////////////////////////////////////////

    // -------------------------------------------------------------------
    // Data Preparations
    // -------------------------------------------------------------------
    private function prepareImageData($ImgParentid, $imgType, $ordering = 1)
    {
        return [
            'img_parent_id' => $ImgParentid,
            'img_type' => $imgType,
            'ordering' => $ordering,
        ];
    }

    // -------------------------------------------------------------------
    // Other
    // -------------------------------------------------------------------
    private function getLimitOffsetFromSetting($request)
    {
        $offset = $request->query('offset');
        $limit = $request->query('limit') ?: $this->getDefaultLimit();

        return [$limit, $offset];
    }

    private function getDefaultLimit()
    {
        $defaultLimit = $this->mobileSettingService->get()->default_loading_limit;

        return $defaultLimit ?: 9;
    }

    // -------------------------------------------------------------------
    // Database
    // -------------------------------------------------------------------
    private function checkMaxImageUpload($itemId, $langSymbol)
    {
        $images = $this->imageService->getAll($itemId, Constants::itemCoverImgType);
        $systemConfig = $this->systemConfigService->get();
        if ($systemConfig->max_img_upload_of_item < $images->count()) {
            throw new PsApiException(__('core__api_err_max_img_upload', [], $langSymbol), Constants::badRequestStatusCode);
        }
    }
}
