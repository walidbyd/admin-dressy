<?php

namespace Modules\Core\Http\Controllers\Backend\Rests\App\V1_0\Item;

use App\Config\ps_constant;
use App\Exceptions\PsApiException;
use App\Http\Contracts\Configuration\SystemConfigServiceInterface;
use App\Http\Contracts\Image\ImageServiceInterface;
use App\Http\Contracts\Item\ItemServiceInterface;
use App\Http\Contracts\Utilities\VideoServiceInterface;
use App\Http\Controllers\PsApiController;
use Illuminate\Support\Facades\DB;
use Modules\Core\Constants\Constants;
use Modules\Core\Http\Requests\Item\DestroyVideoItemApiRequest;
use Modules\Core\Http\Requests\Item\VideoUploadItemApiRequest;
use Modules\Core\Http\Requests\Item\VideoUploadItemApiRequestV2;
use Modules\Core\Transformers\Api\App\V1_0\CoreImage\CoreImageApiResource;

class ItemVideoApiController extends PsApiController
{
    public function __construct(
        protected ItemServiceInterface $itemService,
        protected ImageServiceInterface $imageService,
        protected SystemConfigServiceInterface $systemConfigService,
        protected VideoServiceInterface $videoService

    ) {
        parent::__construct();
    }

    public function destroyVideo(DestroyVideoItemApiRequest $request)
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
                __('core__api_delete_video_success', [], $langSymbol),
                Constants::okStatusCode,
                Constants::successStatus
            );
        } catch (\Throwable $e) {
            throw new PsApiException(__('core__api_db_error', [], $langSymbol), Constants::internalServerErrorStatusCode);
        }
    }

    public function videoUpload_Original(VideoUploadItemApiRequest $request)
    {
        $validatedData = $request->validated();
        $itemId = $validatedData['item_id'];
        $imgId = $validatedData['img_id'];
        $file = $request->file('video');
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

            $imgData = $this->prepareImageData($itemId, Constants::itemVideoImgType);
            if (empty($imgId)) {
                $this->imageService->saveVideo($file, $imgData);
            } else {
                $this->imageService->updateVideo($imgId, $file, $imgData);
            }

            $data = new CoreImageApiResource($this->imageService->get($imgData));

            DB::commit();

            return responseDataApi($data);
        } catch (\Throwable $e) {
            DB::rollBack();
            throw new PsApiException(__('core__api_db_error', [], $langSymbol), Constants::internalServerErrorStatusCode);
        }
    }

    public function videoUpload(VideoUploadItemApiRequest $request)
    {
        $validatedData = $request->validated();
        $itemId = $validatedData['item_id'];
        $imgId = $validatedData['img_id'];
        $file = $request->file('video');
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

            $imgData = $this->prepareImageData($itemId, Constants::itemVideoImgType);
            if (empty($imgId)) {
                $this->imageService->saveVideo($file, $imgData);
            } else {
                $this->imageService->updateVideo($imgId, $file, $imgData);
            }

            $data = new CoreImageApiResource($this->imageService->get($imgData));

            DB::commit();

            return responseDataApi($data);

        } catch (\Throwable $e) {
            DB::rollBack();
            throw new PsApiException(__('core__api_db_error', [], $langSymbol), Constants::internalServerErrorStatusCode);
        }
    }

    public function videoUploadV2(VideoUploadItemApiRequestV2 $request)
    {
        $meta = [];
        $validatedData = $request->validated();

        $file = $request->file('video');
        $itemId = $validatedData['item_id'];
        $loginUserId = $request->query('login_user_id');
        $meta['name'] = $request->input('name');
        $meta['total_chunks'] = $request->input('total_chunks');
        $meta['file_size'] = $request->input('file_size');
        $meta['chunk_no'] = $request->input('chunk_no');
        $meta['img_id'] = $request->input('img_id');
        $langSymbol = $request->query('language_symbol');
        $headerToken = $request->header(ps_constant::deviceTokenKeyFromApi);

        $itemObj = $this->itemService->get($itemId);

        $this->checkApiPermissionAndOwnerShip($loginUserId, $headerToken, $langSymbol, $itemObj->added_user_id);

        $imgData = $this->prepareImageData($itemId, Constants::itemVideoImgType);

        $this->videoService->uploadVideo($loginUserId, $file, $meta, $imgData);

        return responseDataApi(['message' => 'Chunk uploaded']);

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
