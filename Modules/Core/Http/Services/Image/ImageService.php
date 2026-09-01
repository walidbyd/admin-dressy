<?php

namespace Modules\Core\Http\Services\Image;

use App\Http\Contracts\Image\ImageProcessingServiceInterface;
use App\Http\Contracts\Image\ImageServiceInterface;
use App\Http\Services\PsService;
use Carbon\Exceptions\InvalidFormatException;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\File;
use Intervention\Image\Exception\NotFoundException;
use Intervention\Image\Facades\Image;
use InvalidArgumentException;
use Modules\Core\Constants\Constants;
use Modules\Core\DTOs\ItemDto;
use Modules\Core\Entities\CoreImage;
use Throwable;

class ImageService extends PsService implements ImageServiceInterface
{
    private $storage_upload_path = '/storage/'.Constants::folderPath.'/uploads/';

    public function __construct(
        protected ImageProcessingServiceInterface $imageProcessingService) {}

    /**
     * @covereBy testSave*
     */
    public function save($file, $imgData, $extension = null)
    {

        // Validate Params
        $this->validateParams($file, $imgData);

        // Validate File
        if (empty($extension)) {
            $extension = $file->getClientOriginalExtension();
        }

        $this->validateExtension($extension);

        // Get File Info
        $fileName = newFileName($file, null, $extension);

        // Prepare and Clear Image Exist
        $image = new CoreImage;

        // Save File
        $imgData = $this->saveFile($file, $fileName, $extension, $imgData);

        // Save Image
        $this->saveOrUpdateImgObj($image, $imgData, $fileName);

        return $fileName;

    }

    public function update($id, $file, $imgData = null)
    {
        try {

            // Validate Params
            if (empty($file)) {
                return '';
            }

            // Validate File
            $extension = $file->getClientOriginalExtension();
            $this->validateExtension($extension);

            // Get File Info
            $fileName = newFileName($file);

            // Get Image to update
            $image = $this->getImageById($id);

            // Delete Old Images
            $this->delete($image->img_path);

            // Save Updated Image
            $imgData = $this->saveFile($file, $fileName, $extension, $imgData);

            // Save Image
            $this->saveOrUpdateImgObj($image, $imgData, $fileName);

            return $fileName;
        } catch (\Throwable $e) {
            // dd($e->getMessage());
        }

    }

    public function deleteAll($imgParentId, $imgType)
    {
        $images = $this->getAll($imgParentId, $imgType);

        foreach ($images as $image) {
            $this->imageProcessingService->deleteImageFile($image->img_path);
            $image->delete();
        }
    }

    public function delete($img_path)
    {
        $this->imageProcessingService->deleteImageFile($img_path);
    }

    public function get($conds)
    {
        return CoreImage::where($conds)->orderBy('id', 'desc')->first();
    }

    public function getAll($imgParentId = null, $imgType = null, $limit = null, $offset = null, $notImgTypes = null, $conds = null)
    {
        return CoreImage::when($imgParentId, function ($q, $imgParentId) {
            $q->where(CoreImage::imgParentId, $imgParentId);
        })
            ->when($imgType, function ($q, $imgType) {
                if ($imgType === 'item_related') {
                    $q->where(CoreImage::imgType, 'like', '%item%');
                } elseif ($imgType === Constants::categoryCoverImgType) {
                    $q->whereIn(CoreImage::imgType, [Constants::categoryCoverImgType, Constants::categoryIconImgType]);
                } elseif ($imgType === Constants::subcategoryCoverImgType) {
                    $q->whereIn(CoreImage::imgType, [Constants::subcategoryCoverImgType, Constants::subcategoryIconImgType]);
                } else {
                    $q->where(CoreImage::imgType, $imgType);
                }

            })
            ->when($notImgTypes, function ($q, $notImgTypes) {
                $q->whereNotIn(CoreImage::imgType, $notImgTypes);
            })
            ->when($conds, function ($q, $conds) {
                // if (isset($conds['order_by'])) {
                $q->orderBy($conds['order_by'], $conds['order_type']);
                // }
            })
            ->when($limit, function ($query, $limit) {
                $query->limit($limit);
            })
            ->when($offset, function ($query, $offset) {
                $query->offset($offset);
            })->latest()->get();

    }

    /**
     * @deprecated
     * Use VideoService::uploadVideo() instead
     */
    public function saveVideo($file, $data)
    {
        try {
            // save video file
            $fileName = uniqid().'_'.$file->getClientOriginalName();
            $file->move(public_path().$this->storage_upload_path, $fileName);

            // save video data at core_images table
            $video = new CoreImage;
            $video->fill($data);
            $video->img_path = $fileName;
            $video->added_user_id = Auth::user()->id;
            $video->save();

        } catch (Throwable $e) {
            throw $e;
        }

    }

    /**
     * @deprecated
     * Use VideoService::uploadVideo() instead
     */
    public function updateVideo($id, $file, $data)
    {

        try {

            $video = $this->get(['id' => $id]);
            if (! empty($video)) {
                $this->delete($video->img_path);
            }

            // save video file
            $fileName = uniqid().'_'.$file->getClientOriginalName();
            $file->move(public_path().$this->storage_upload_path, $fileName);

            // update video data at core_images table
            $video->fill($data);
            $video->img_path = $fileName;
            $video->updated_user_id = Auth::user()->id;
            $video->update();

        } catch (Throwable $e) {
            throw $e;
        }

    }

    /**
     * @coveredBy testSaveDropzoneMultiImage*
     */
    public function saveDropzoneMultiImage($itemData, $itemId, ?ItemDto $itemDto = null)
    {

        if ($itemDto != null) {

            // ** Note **
            // This code is the temporary adapter from itemDto to itemData array
            // After migrated fully to CreateItemAction, "$itemData" will be removed
            // And update the code to use directly from DTO class.
            // $itemData = [];
            // foreach($itemDto->images as $key=>$image) {
            //     dd($key, $image);
            // }

            $itemData['images'] = $itemDto->images;
            $itemData['img_caption'] = $itemDto->imgCaption;
            $itemData['img_order'] = $itemDto->imgOrder;
        }

        if (! empty($itemData['images'])) {
            $images = $itemData['images'];

            foreach ($images as $key => $image) {
                $image_description = '';
                $path = public_path('storage/uploads/items/'.$image);

                $file_exist = File::exists($path);

                $image_arr = $itemData['img_caption'] ?? null;
                if ($image_arr) {
                    foreach ($image_arr as $key => $value) {
                        if ($value['name'] == $image) {
                            $image_description = $value['value'];
                        }
                    }
                }
                $data[CoreImage::imgParentId] = $itemId;
                $data[CoreImage::imgType] = 'item';
                $data[CoreImage::ordering] = $key;
                if (isset($itemData['img_order'])) {
                    foreach ($itemData['img_order'] as $order) {
                        if ($order['name'] == $image) {
                            $data[CoreImage::ordering] = $order['order'];
                        }
                    }
                }
                if ($image_description) {
                    $data[CoreImage::imgDesc] = $image_description;
                } else {
                    $data[CoreImage::imgDesc] = '';
                }

                // dd($path);
                $extension = pathinfo($path, PATHINFO_EXTENSION);
                $file = Image::make($path);

                $this->save($file, $data, $extension);

                if (File::exists($path)) {
                    File::delete($path);
                }
            }
        }
    }

    // //////////////////////////////////////////////////////////////////
    // / Private Functions
    // //////////////////////////////////////////////////////////////////

    private function getImageById($id)
    {
        $image = $this->get([CoreImage::id => $id]);
        if ($image === null) {
            throw new NotFoundException('Image not found to edit.');
        }

        return $image;
    }

    /**
     * @coveredBy testSaveFile*
     */
    private function saveFile($file, $fileName, $extension, $imgData)
    {
        $imageSizeInfo = $this->createImages($file, $fileName, $extension, $imgData['img_type']);
        $this->updateImgDataWithSizeInfo($imgData, $imageSizeInfo);

        return $imgData;
    }

    /**
     * @coveredBy testValidateExtension
     */
    private function validateExtension($extension)
    {
        // Check the file extension
        $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg', 'ico'];

        if (! in_array($extension, $allowedExtensions)) {
            throw new InvalidFormatException('Invalid Format Extension.');
        }

    }

    private function validateParams($file = null, $imgData = null)
    {
        if ($file === null || $imgData === null) {
            throw new InvalidArgumentException('Not allow empty params. : Image Validation');
        }

        $requiredKeys = ['img_parent_id', 'img_type'];
        foreach ($requiredKeys as $key) {
            if (empty($imgData[$key])) {
                throw new InvalidArgumentException("{$key} can't be empty.");
            }
        }

    }

    /**
     * @coveredBy testSaveFile*
     */
    private function createImages($file, $fileName, $extension, $uploadType)
    {

        // To store original image width and height
        if ($extension === 'ico') {
            // For the ico format, we will not create thumbnails
            $this->imageProcessingService->createIcoFile(file: $file, fileName: $fileName);

            return $this->getImageWidthAndHeight($file);
        }

        // Will save 1x,2x,3x thumbnails and original image
        $resolutions = ['original', '3x', '2x', '1x'];
        $rtnImages = $this->imageProcessingService->createImageFiles(file : $file,
            fileName : $fileName,
            imageType : $uploadType,
            resolutions : $resolutions);

        return $this->getImageWidthAndHeight($rtnImages);

    }

    private function getImageWidthAndHeight($rtnImages = [])
    {
        if (empty($rtnImages)) {
            return null;
        }

        return [
            'img_width' => $rtnImages[count($rtnImages) - 1]->width(),
            'img_height' => $rtnImages[count($rtnImages) - 1]->height(),
        ];

    }

    /**
     * @coveredBy testSaveOrUpdateImgObj*
     */
    private function prepareImageData($image, $fileName)
    {
        return [
            'img_parent_id' => $image->img_parent_id ?? 0,
            'img_type' => $image->img_type ?? '',
            'img_width' => $image->img_width ?? 0,
            'img_height' => $image->img_height ?? 0,
            'ordering' => $image->ordering ?? 1,
            'img_path' => $fileName,
            'img_desc' => $image->img_desc ?? '',
        ];
    }

    /**
     * @coveredBy testSaveOrUpdateImgObj*
     */
    private function addUserId(&$image)
    {
        if ($image->exists) {
            $image->updated_user_id = Auth::id();
        } else {
            $image->added_user_id = Auth::id();
        }
    }

    /**
     * @coveredBy testSaveOrUpdateImgObj*
     */
    private function saveOrUpdateImgObj($image, $imgData, $fileName)
    {
        $defaults = $this->prepareImageData($image, $fileName);

        $image->fill(array_merge($defaults, array_intersect_key($imgData, $defaults)));
        $this->addUserId($image);

        $image->save();
    }

    /**
     * @coveredBy testSaveFile*
     */
    private function updateImgDataWithSizeInfo(&$imgData, $imageSizeInfo)
    {
        $imgData['img_width'] = $imageSizeInfo['img_width'];
        $imgData['img_height'] = $imageSizeInfo['img_height'];
    }
}
