<?php

namespace Modules\Core\Http\Services\Item;

use App\Config\Cache\CategoryCache;
use App\Config\Cache\ItemCache;
use App\Config\Cache\VendorCache;
use App\Config\ps_constant;
use App\Http\Contracts\Authorization\PushNotificationTokenServiceInterface;
use App\Http\Contracts\Category\CategoryServiceInterface;
use App\Http\Contracts\Category\SubcategoryServiceInterface;
use App\Http\Contracts\Configuration\BackendSettingServiceInterface;
use App\Http\Contracts\Configuration\SettingServiceInterface;
use App\Http\Contracts\Configuration\SystemConfigServiceInterface;
use App\Http\Contracts\Financial\ItemCurrencyServiceInterface;
use App\Http\Contracts\Image\ImageServiceInterface;
use App\Http\Contracts\Item\CartItemServiceInterface;
use App\Http\Contracts\Item\ItemInfoServiceInterface;
use App\Http\Contracts\Item\ItemServiceInterface;
use App\Http\Contracts\Notification\FirebaseCloudMessagingServiceInterface;
use App\Http\Contracts\User\UserInfoServiceInterface;
use App\Http\Contracts\User\UserServiceInterface;
use App\Http\Contracts\Utilities\ChunkUpdateServiceInterface;
use App\Http\Contracts\Utilities\CoreFieldServiceInterface;
use App\Http\Contracts\Utilities\CustomFieldServiceInterface;
use App\Http\Contracts\Utilities\DynamicLinkServiceInterface;
use App\Http\Services\PsService;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Intervention\Image\Facades\Image;
use Modules\Core\Constants\Constants;
use Modules\Core\Constants\ItemStatus;
use Modules\Core\DTOs\ItemDto;
use Modules\Core\Entities\CoreImage;
use Modules\Core\Entities\Financial\ItemCurrency;
use Modules\Core\Entities\Item\Item;
use Modules\Core\Entities\ItemInfo;
use Modules\Core\Http\Facades\PsCache;
use Modules\Core\Http\Services\SubCatSubscribeService;
use Throwable;

class ItemService extends PsService implements ItemServiceInterface
{
    public function __construct(
        protected BackendSettingServiceInterface $backendSettingService,
        protected ImageServiceInterface $imageService,
        protected ItemInfoServiceInterface $itemInfoService,
        protected CategoryServiceInterface $categoryService,
        protected CartItemServiceInterface $cartItemService,
        protected CustomFieldServiceInterface $customFieldService,
        protected CoreFieldServiceInterface $coreFieldService,
        protected UserServiceInterface $userService,
        protected PushNotificationTokenServiceInterface $pushNotificationTokenService,
        protected FirebaseCloudMessagingServiceInterface $firebaseCloudMessagingService,
        protected SubcategoryServiceInterface $subcategoryService,
        protected UserInfoServiceInterface $userInfoService,
        protected SubCatSubscribeService $subCatSubscribeService,
        protected ItemCurrencyServiceInterface $itemCurrencyService,
        protected SystemConfigServiceInterface $systemConfigService,
        protected SettingServiceInterface $settingService,
        protected DynamicLinkServiceInterface $dynamicLinkService,
        protected ChunkUpdateServiceInterface $chunkUpdateService
    ) {}

    /**
     * @coveredBy testSave*
     */
    public function save($itemData, $itemVideoIconImage, $itemVideo, $relationalData)
    {
        DB::beginTransaction();

        try {
            // save in item table
            $item = $this->saveItem($itemData);

            $this->imageService->saveDropzoneMultiImage($itemData, $item->id);

            if (! empty($itemVideoIconImage)) {
                $vidoeIconImgData = $this->prepareSaveImageData($item->id, 'item-video-icon');
                $this->imageService->save($itemVideoIconImage, $vidoeIconImgData);
            }

            if (! empty($itemVideo)) {
                $itemVideoData = $this->prepareSaveVideoData($item->id, 'item-video');
                $this->imageService->saveVideo($itemVideo, $itemVideoData);
            }

            $this->itemDeeplinkGenerate($item->id);

            // save in item_info table
            $this->itemInfoService->save($item->id, $relationalData);

            PsCache::clear(ItemCache::BASE);
            PsCache::clear(CategoryCache::BASE);
            PsCache::clear(VendorCache::BASE);

            DB::commit();

            return $item;
        } catch (\Throwable $e) {
            DB::rollBack();

            throw $e;
        }
    }

    public function update($id, $itemData, $itemVideoIconImage, $itemVideo, $relationalData)
    {
        DB::beginTransaction();
        try {

            if (! empty($itemData['img_caption'])) {
                $conds = [];
                $image_arr = $itemData['img_caption'];
                foreach ($image_arr as $key => $value) {
                    $value = decodeIfJson($value);
                    $conds['img_path'] = $value['name'];
                    $image_path = $this->imageService->get($conds);
                    if ($image_path) {
                        $image_path->img_desc = $value['value'];
                        $image_path->update();
                    }
                }
            }

            if (! empty($itemData['img_order'])) {
                $conds = [];
                foreach ($itemData['img_order'] as $imgOrder) {
                    $imgOrder = decodeIfJson($imgOrder);
                    $conds['id'] = $imgOrder['id'];
                    $image_path = $this->imageService->get($conds);
                    if ($image_path) {
                        $image_path->ordering = $imgOrder['order'];
                        $image_path->update();
                    }
                }
            }

            // update in item table
            $item = $this->updateItem($id, $itemData);

            if (! empty($itemVideoIconImage)) {
                $vidoeIconImgData = $this->prepareSaveImageData($item->id, 'item-video-icon');
                $this->imageService->save($itemVideoIconImage, $vidoeIconImgData);
            }

            if (! empty($itemVideo)) {
                $itemVideoData = $this->prepareSaveVideoData($item->id, 'item-video');
                $this->imageService->saveVideo($itemVideo, $itemVideoData);
            }

            $this->itemDeeplinkGenerate($item->id);

            // update in item_infos table
            $this->updateItemInfo($item->id, $relationalData, $item->category_id);
            // $this->itemInfoService->update($item->id, $relationalData);

            PsCache::clear(ItemCache::BASE);
            PsCache::clear(VendorCache::BASE);

            DB::commit();

            return $item;
        } catch (\Throwable $e) {
            DB::rollBack();
            throw $e;
        }
    }

    public function delete($id)
    {
        DB::beginTransaction();
        try {
            // delete in items table
            $item = $this->get($id);
            $item->delete();

            // delete all files
            $this->imageService->deleteAll($id, null);

            // delete from cart
            $cartItem = $this->cartItemService->get(itemId: $id);
            if (! empty($cartItem)) {
                $cartItem->delete();
            }

            // delete in item_infos table
            $itemRelations = $this->itemInfoService->getAll(null, $id, null, Constants::yes, null);
            $this->itemInfoService->deleteAll($itemRelations);

            PsCache::clear(ItemCache::BASE);
            PsCache::clear(CategoryCache::BASE);

            DB::commit();

            return [
                'msg' => __('core__be_delete_success', ['attribute' => $item->title]),
                'flag' => Constants::success,
            ];

        } catch (\Throwable $e) {
            DB::rollBack();
            throw $e;
        }

    }

    /**
     * @coveredBy testGet*
     */
    public function get($id = null, $relation = null)
    {
        $langSymbol = request()->query('language_symbol');
        $param = [$id, $relation, $langSymbol];

        return PsCache::remember([ItemCache::BASE], ItemCache::GET_EXPIRY, $param,
            function () use ($id, $relation) {

                $item = Item::when($relation, function ($query, $relation) {
                    $query->with($relation);
                })
                    ->when($id, function ($query, $id) {
                        $query->where(Item::id, $id);
                    })
                    ->first();

                return $item;
            });
    }

    /**
     * @coveredBy testGetAll*
     *
     * Retrieve all records with optional relations, filters, sorting, limit, and offset.
     *
     * @param  array  $relations  An array of relations to be loaded with the records.
     * @param  array  $filters  An array of filters to apply to the query. The filters can include:
     *                          - 'keyword': string - A keyword to search for.
     *                          - 'seller_buyer_name': string - The name of the seller or buyer.
     *                          - 'category_id': int - The ID of the category.
     *                          - 'subcategory_id': int - The ID of the subcategory.
     *                          - 'location_city_id': int - The ID of the city location.
     *                          - 'location_township_id': int - The ID of the township location.
     *                          - 'added_user_id' : int - The ID of the Added User.
     *                          - 'vendor_id' : int - The ID of the Vendor.
     *                          - 'is_sold_out' : int - Status of Sold Out
     *                          - 'psx_items.*' : any - All fillable fields of Item
     *                          - 'status_in' : array - Array of Status
     *                          - 'min_price': float - The minimum price value.
     *                          - 'max_price': float - The maximum price value.
     *                          - 'min_added_date': string - The minimum added date in 'Y-m-d H:i:s' format.
     *                          - 'max_added_date': string - The maximum added date in 'Y-m-d H:i:s' format.
     *                          - 'min_updated_date': string - The minimum updated date in 'Y-m-d H:i:s' format.
     *                          - 'max_updated_date': string - The maximum updated date in 'Y-m-d H:i:s' format.
     *                          - 'lat_lng': array - An array with latitude, longitude, and radius in miles:
     *                          - 'lat': float - The latitude coordinate.
     *                          - 'lng': float - The longitude coordinate.
     *                          - 'miles': float - The radius in miles.
     *                          - 'infos_filter': array - An array of additional filters with key-value pairs, e.g.,
     *                          - 'core_key_id': 'value' - Core/Custom Key and Value
     *                          - 'ps-itm00009' => 'India' - Example filter key.
     *                          - 'itm00029' => '75'    - Another Example
     *                          - blockUserIds_not_in => array - get data except blocked user id
     *                          - complaintItemIds_not_in => array - get data except complaint item id
     *                          - paid_item_histories_timestamp => timestamp - get paid item by timestamp
     *                          - paid_item_histories_deleted_at => timestamp/null - get paid item by fitlering soft delete
     * @param  array  $sorting  An array of sorting to apply to the query. The sorting can include:
     *                          - 'category_id@@name' => string - Category Sorting By Name
     *                          - 'currency_id@@currency_short_form' => string - Currency Sorting By Name
     *                          - 'location_city_id@@name' => string - Location City Sorting By Name
     *                          - 'location_township_id@@name' => string - Township Sorting By Name
     *                          - 'added_user_id@@name' => string - Added User Sorting By Name
     *                          - 'buyer_user_id@@name' => string - Buyer Sorting By Name
     *                          - 'seller_user_id@@name' => string - Seller Sorting By Name
     *                          - 'psx_items.*' => string - All fillable psx_items Sorting
     *                          - 'core_key_id.*' => string - All core key Sorting
     *
     * - 'custom_key_id.*@@name' => string - All custom field key Sorting
     *
     * * @param int $limit Limit
     * * @param int $offset Offset
     */
    public function getAll($relations = [], $filters = [], $sorting = [], $limit = null, $offset = null, $noPagination = null, $filterNotIn = [], $page = null)
    {

        $param = [$relations, $filters, $sorting, $limit, $offset, $noPagination, $filterNotIn, 'per_page' => $page];

        return PsCache::remember([ItemCache::BASE], ItemCache::GET_ALL_EXPIRY, $param,
            function () use ($relations, $filters, $sorting, $limit, $offset, $noPagination, $filterNotIn) {

                // Select
                $query = Item::select(Item::tableName.'.*')
                    ->selectCustomField(); // Select Raw ?? getCustomizeUiSelectSQL

                // Join
                $query
                    // @todo replace with normal relation
                    // start
                    ->joinWithItemInfo() // left join with item info
                    ->joinWithCustomFieldAttribute()
                    ->joinWithPaidItemHistory() // left join with paid item histories
                    ->groupBy(Item::tableName.'.'.Item::id)
                    // end
                    ->with('itemRelation.customizeUiDetail')
                    ->when($relations, fn ($q) => $q->with($relations)) // with relation
                    ->limitAndOffset($limit, $offset); // limit and offset

                // Where
                $query
                    ->searchKeyword($filters)
                    ->searchBuyerSellerByName($filters)
                    ->filterByFields($filters)
                    ->minPriceFilter($filters)
                    ->maxPriceFilter($filters)
                    ->minAddedDateFilter($filters)
                    ->maxAddedDateFilter($filters)
                    ->minUpdatedDateFilter($filters)
                    ->maxUpdatedDateFilter($filters)
                    ->statusInFilter($filters)
                    ->locationFilterWithLatLng($filters)
                    ->infosFilter($filters)
                    ->paidItemTimeStampFilter($filters);

                // WhereNotIn
                $query
                    ->blockUserNotInFilter($filterNotIn)
                    ->complaintItemNotInFilter($filterNotIn)
                    ->notInFilterByFields($filterNotIn);

                // Order
                $query->orderByCategoryName($sorting)
                    ->orderBySubCategoryName($sorting)
                    ->orderByCurrencyName($sorting)
                    ->orderByCityName($sorting)
                    ->orderByTownshipName($sorting)
                    ->orderByOwnerName($sorting)
                    ->orderByBuyerName($sorting)
                    ->orderBySellerName($sorting)
                    ->orderByFields($sorting);

                if ($noPagination) {
                    return $query->get();
                } else {
                    $countQuery = Item::select(Item::tableName.'.id');

                    // Where
                    $countQuery
                        ->searchKeyword($filters)
                        ->searchBuyerSellerByName($filters)
                        ->filterByFields($filters)
                        ->minPriceFilter($filters)
                        ->maxPriceFilter($filters)
                        ->minAddedDateFilter($filters)
                        ->maxAddedDateFilter($filters)
                        ->minUpdatedDateFilter($filters)
                        ->maxUpdatedDateFilter($filters)
                        ->statusInFilter($filters)
                        ->locationFilterWithLatLng($filters)
                        ->infosFilter($filters)
                        ->paidItemTimeStampFilter($filters);

                    // WhereNotIn
                    $countQuery
                        ->blockUserNotInFilter($filterNotIn)
                        ->complaintItemNotInFilter($filterNotIn)
                        ->notInFilterByFields($filterNotIn);

                    $total = $countQuery->filterByFields($filters)->count();

                    $rel = $query->simplePaginate($limit)->withQueryString();
                    $paginator = new LengthAwarePaginator(
                        $rel->items(),
                        $total,
                        $limit,
                        $rel->currentPage(),
                        ['path' => request()->url(), 'query' => request()->query()]
                    );

                    return $paginator;
                    // return $query->total($total);
                }
            });

        // We may need ?
        // return $query->get();

    }

    public function updateMultiImage($itemData, $file)
    {
        try {
            $imgParentId = $itemData['item_id'];
            if (($itemData['edit_mode'] == 1 || $itemData['edit_mode'] == 0) && ! empty($imgParentId)) {
                if ($file) {

                    $currentImages = $this->imageService->getAll(imgParentId: $imgParentId, imgType: 'item')->count();
                    $systemConfig = $this->systemConfigService->get();
                    if ($currentImages >= $systemConfig->max_img_upload_of_item) {
                        return response()->json(['success' => 'You have reach max image upload', 'msg' => 'fail']);
                    }

                    $imgData = $this->prepareSaveImageData($imgParentId, 'item', $itemData['caption'], $itemData['order']);
                    $fileName = $this->imageService->save($file, $imgData);

                    $this->generateDeeplink($imgParentId);

                    PsCache::clear(ItemCache::BASE);

                    return response()->json(['success' => $fileName, 'msg' => 'success']);
                }
            }

            if ($file) {
                $image = Image::make($file);
                $fileName = uniqid().'_'.'.'.$file->getClientOriginalExtension();
                if (! File::isDirectory(public_path().'/storage/uploads/items')) {
                    File::makeDirectory(public_path().'/storage/uploads/items', 0777, true, true);
                }

                $image->save(public_path().'/storage/uploads/items/'.$fileName, 50);

                PsCache::clear(ItemCache::BASE);

                return response()->json(['success' => $fileName, 'msg' => 'success']);
            }

        } catch (Throwable $e) {
            throw $e;
        }
    }

    public function setStatus($id, $status)
    {
        try {
            $status = $this->prepareUpdateStausData($status);

            $item = $this->get($id);
            $item->fill($status);
            $item->updated_user_id = Auth::user()->id;
            $item->update();
            PsCache::clear(ItemCache::BASE);

            return $item;

        } catch (\Throwable $e) {
            throw $e;
        }
    }

    public function duplicateItem($id)
    {
        DB::beginTransaction();
        try {
            $item = $this->get($id);

            $update_copies = $this->prepareDuplicateData($item);
            $duplicate = duplicate($item, $update_copies, true);

            $this->itemDeeplinkGenerate($duplicate->id);

            PsCache::clear(ItemCache::BASE);

            DB::commit();

            return $duplicate;

        } catch (Throwable $e) {
            DB::rollBack();
            throw $e;
        }

    }

    public function generateDeeplink($id)
    {
        try {

            $this->itemDeeplinkGenerate($id);

        } catch (Throwable $e) {
            throw $e;
        }
    }

    public function countCategory($categoryId = null, $conds = null)
    {
        if ($categoryId == null) {
            $count = Item::count();
        } else {
            $count = Item::where(Item::categoryId, $categoryId)
                ->whereIn(Item::status, [1, 4])
                ->when($conds, function ($query, $conds) {
                    $query->where($conds);
                })
                ->count();
        }

        return $count;
    }

    public function decreaseItemQuantity($itemId, $isSoldOut = false)
    {
        DB::beginTransaction();

        try {

            $quantity = $this->itemInfoService->get(
                itemId: $itemId,
                coreKeysId: Constants::itemQty
            );
            $item = $this->get($itemId);

            if (isset($item) && isset($quantity)) {
                if (! $isSoldOut && (int) $quantity->value - 1 > 0) {
                    $quantity->value = (int) $quantity->value - 1;
                    $quantity->updated_user_id = Auth::user()->id;
                    $quantity->update();
                } else {
                    $quantity->value = 0;
                    $quantity->updated_user_id = Auth::user()->id;
                    $quantity->update();

                    $item->is_sold_out = 1;
                    $item->updated_user_id = Auth::user()->id;
                    $item->update();
                }
            } elseif (isset($item)) {
                $item->is_sold_out = 1;
                $item->updated_user_id = Auth::user()->id;
                $item->update();
            }

            DB::commit();

            return $item;
        } catch (Throwable $e) {
            DB::rollBack();
            throw $e;
        }

    }

    public function sendApprovalNoti($id)
    {
        $item = $this->get($id);
        $user = $this->userService->get($item->added_user_id);
        $notiTokens = $this->pushNotificationTokenService->getAll(
            conds: ['user_id' => $user->id],
            noPagination: Constants::yes
        );

        $this->sendSubscribeNoti($item);

        [$device_ids, $platform_names] = $this->prepareDeviceIdsAndPlatformNames($notiTokens);
        [$message, $subject] = $this->prepareApprovalMsgAndSubjectData($item);

        $data['message'] = $message;
        $data['flag'] = Constants::approvalNotiFlag;
        $data['item_id'] = $item->id;
        foreach ($device_ids as $device_id) {
            $this->firebaseCloudMessagingService->sendAndroidFcm($device_id, $data, $platform_names);
        }

        // send approval mail
        sendMail(
            to: $user->email,
            to_name: $user->name,
            title: $subject,
            body: $message
        );
    }

    public function saveFromApi($itemData, $relationalData, $userInfo, $isReduceRemainPostCount)
    {
        DB::beginTransaction();

        try {

            $isApprovalEnable = $this->systemConfigService->get()->is_approved_enable;
            $itemData['status'] = $isApprovalEnable ? Constants::pendingItem : Constants::publishItem;

            // save in item table
            $item = $this->saveItem($itemData);

            $this->itemDeeplinkGenerate($item->id);

            // save in item_info table
            $this->itemInfoService->save($item->id, $relationalData);

            // update remaining post count
            if ($isReduceRemainPostCount) {
                $userInfoData = $this->prepareUpdateRemainingPostCountData($userInfo);
                $this->userInfoService->update($userInfo->user_id, $userInfoData);
            }

            // send noti to subcat scribers
            $this->sendSubscribeNoti($item);

            PsCache::clear(ItemCache::BASE);
            PsCache::clear(VendorCache::BASE);
            PsCache::clear(CategoryCache::BASE);

            DB::commit();

            return $item;
        } catch (\Throwable $e) {
            DB::rollBack();

            throw $e;
        }
    }

    public function updateFromApi($itemObj, $itemData, $relationalData)
    {
        DB::beginTransaction();
        try {
            $this->updateDropzoneImageCaption($itemData);
            $this->updateDropzoneImageOrder($itemData);

            // update in item table
            $item = $this->updateItem($itemObj->id, $itemData);

            // update in item_infos table
            $this->updateItemInfo($item->id, $relationalData, $item->category_id);
            // $this->itemInfoService->update($item->id, $relationalData);

            PsCache::clear(ItemCache::BASE);
            PsCache::clear(VendorCache::BASE);

            DB::commit();

            return $item;

        } catch (Throwable $e) {
            DB::rollBack();
            throw $e;
        }
    }

    public function generateDynamicLinksForAllItems()
    {
        DB::beginTransaction();
        try {
            $offset = 0;
            $limit = ps_constant::CHUNK_LIMIT;
            $runnedTime = 0;
            $parameters = [
                'item_id' => 'id',
            ];

            do {
                $items = $this->getAll(limit: $limit, offset: $offset, noPagination: true);
                if ($items->isEmpty()) {
                    break;
                }

                $bulkUpdateItems = [];
                $dynamicLinks = $this->dynamicLinkService->generateDynamicLinks($items, $parameters, ps_constant::dynamicLinkItemType);
                foreach ($dynamicLinks as $dynamicLink) {
                    $decodedParams = json_decode($dynamicLink->parameters, true);
                    $bulkUpdateItems[] = [
                        Item::id => $decodedParams['item_id'],
                        Item::dynamicLink => $dynamicLink->short_code,
                    ];
                }

                if (! empty($bulkUpdateItems)) {
                    $this->chunkUpdateService->updateRecordsInChunks($bulkUpdateItems, Item::tableName, 'id');
                }

                $offset += $limit;
                $runnedTime++;

            } while ($items->count() >= $limit);

            PsCache::clear(ItemCache::BASE);
            DB::commit();
        } catch (Throwable $e) {
            DB::rollBack();
            throw $e;
        }
    }

    public function sendSubscribeNoti($item)
    {
        $systemConfig = $this->systemConfigService->get();
        // if ($item->updated_flag == 0 && $item->status == Constants::publishItem && $systemConfig->is_sub_subscription) {
        //     return ;
        // }
        if ($item->status != Constants::publishItem || $systemConfig->is_sub_subscription != 1) {
            return;
        }

        $subcategory_id = $item->subcategory_id;
        $subcategory = $this->subcategoryService->get($subcategory_id);
        $isSubscribed = $this->subCatSubscribeService->getSubscribedSubCat($subcategory_id.Constants::feSubscribeNotiFlag);
        if (! $isSubscribed) {
            $isSubscribed = $this->subCatSubscribeService->getSubscribedSubCat($subcategory_id.Constants::mbSubscribeNotiFlag);
        }

        if ($subcategory && $isSubscribed) {
            $name = $subcategory->name;

            $subscribe_msg = __('core__new_item_upload_label').' '.$name;

            $data['message'] = $subscribe_msg;
            $data['subscribe'] = 1;
            $data['push'] = 0;
            $data['subcategory_id'] = $subcategory_id;
            $data['item_id'] = $item->id;

            // send subscribe noti to mobile or ios
            $status = $this->firebaseCloudMessagingService->sendAndroidFcmTopicsSubscribe($data);

            // send subscribe noti to frontend
            $status = $this->firebaseCloudMessagingService->sendAndroidFcmTopicsSubscribeFe($data, env('APP_NAME'));

            // update the updated_flag 1
            $item_data = new \stdClass;
            $item_data->id = $item->id;
            $item->updated_flag = 1;
        }
    }

    /**
     * @coveredBy testCalculateItemLimitAndOffset*
     */
    public function calculateItemLimitAndOffset($limit, $offset = 0, $interval = 0)
    {
        if (! isset($limit) || ! is_int($limit) || $limit <= 0) {
            throw new \InvalidArgumentException("The 'limit' parameter must be a positive integer.");
        }
        if (! isset($offset) || ! is_int($offset) || $offset < 0) {
            throw new \InvalidArgumentException("The 'offset' parameter must be a non-negative integer.");
        }
        if (! isset($interval) || ! is_int($interval) || $interval < 0) {
            throw new \InvalidArgumentException("The 'interval' parameter must be a non-negative integer.");
        }

        $patternLength = $interval + 1;

        $paidBeforeOffset = floor($offset / $patternLength);

        $paidAfterOffset = $interval > 0 ? floor(($offset + $limit) / $patternLength)
            - floor($offset / $patternLength) : 0;

        return [
            'normalLimit' => (int) ($limit - $paidAfterOffset),
            'normalOffset' => (int) ($offset - $paidBeforeOffset),
            'paidLimit' => (int) ($paidAfterOffset),
            'paidOffset' => (int) ($paidBeforeOffset),
        ];
    }

    /**
     * @coveredBy testGenerateVisiblePatternArray*
     */
    public function generateVisiblePatternArray($limit, $offset = 0, $interval = 0)
    {
        if (! is_int($limit) || $limit <= 0) {
            throw new \InvalidArgumentException("The 'limit' parameter must be a positive integer.");
        }
        if (! is_int($offset) || $offset < 0) {
            throw new \InvalidArgumentException("The 'offset' parameter must be a non-negative integer.");
        }
        if (! is_int($interval) || $interval < 0) {
            throw new \InvalidArgumentException("The 'interval' parameter must be a non-negative integer.");
        }
        $result = [];
        $patternLength = $interval + 1;

        for ($i = $offset + 1; $i <= $offset + $limit; $i++) {
            $result[] = ($i % $patternLength === 0 && $interval > 0) ? 'zero' : 'one';
        }

        return $result;
    }

    // -------------------------------------------------------------------
    // Next Update with Actions
    // -------------------------------------------------------------------

    /**
     * @coveredBy testCreate*
     */
    public function create(ItemDto $itemDto)
    {
        $item = new Item;
        $dataArr = $itemDto->toArray();
        $item->fill($dataArr);
        $item->save();

        // save in item_info table
        // $this->itemInfoService->save($item->id, $itemDto->customFields);

        return $item;
    }

    /**
     * @coveredBy testUpdateV2*
     */
    public function updateV2($id, ItemDto $itemDto)
    {
        $item = $this->get($id);
        if (! $item) {
            throw new ModelNotFoundException("Item with id {$id} not found.");
        }
        $item->fill($itemDto->toArray());
        $item->update();

        return $item;
    }

    /**
     * @coveredBy testUpdateDynamicLink*
     */
    public function updateDynamicLink($item, string $dynamicLink)
    {
        $item->dynamic_link = $dynamicLink;
        $item->update();

        return $item;
    }

    /**
     * @coveredBy testPrepareStatusData*
     */
    public function prepareStatusData($status, $systemConfig)
    {
        if (isset($status)) {
            if (
                $status == ItemStatus::publish
                || $status == ItemStatus::unpublish
                || $status == ItemStatus::disable
                || $status == ItemStatus::reject
                || $status == ItemStatus::pending
            ) {
                return $status;
            } else {
                throw new \InvalidArgumentException('Invalid status value provided.');
            }
        }

        if (! isset($systemConfig->is_approved_enable)) {
            return ItemStatus::publish;
        }

        return $systemConfig->is_approved_enable ? ItemStatus::pending : ItemStatus::publish;
    }

    /**
     * @coveredBy testPrepareCurrencyIdData*
     */
    public function prepareCurrencyIdData($currencyId, $selctedArray = null)
    {

        if (isset($currencyId)) {
            return $currencyId;
        }

        $default_currency = $this->itemCurrencyService->get(conds: [ItemCurrency::isDefault => Constants::yes]);

        if (! isset($default_currency->id)) {
            throw new \InvalidArgumentException('Default currency not found.');
        }

        return $default_currency->id;

    }

    /**
     * @coveredBy testPreparePercentData*
     */
    public function preparePercentData($percent): float
    {

        if (! isset($percent) || $percent === null || $percent === '') {
            return 0;
        }

        if ($percent < 0 || $percent > 100 || ! is_numeric($percent)) {
            throw new \InvalidArgumentException('Percent must be between 0 and 100.');
        }

        return floatval(number_format(floatval($percent), 2, '.', ''));
    }

    /**
     * @coveredBy testPrepareisDiscountData*
     */
    public function prepareisDiscountData($percent): int
    {
        try {
            $_percent = $this->preparePercentData($percent);

            return $_percent > 0 ? 1 : 0;
        } catch (\Throwable $e) {
            throw $e;
        }
    }

    /**
     * @coveredBy testPrepareOriginalPriceData*
     */
    public function prepareOriginalPriceData($originalPriceValue)
    {
        if (empty($originalPriceValue)) {
            return 0;
        }

        if (! is_numeric($originalPriceValue)) {
            throw new \InvalidArgumentException(
                'Original price must be a numeric value.'
            );
        }

        if ($originalPriceValue < 0) {
            throw new \InvalidArgumentException(
                'Original price must not be negative value.'
            );
        }

        return $originalPriceValue;
    }

    /**
     * @coveredBy testPreparePriceData*
     */
    public function preparePriceData($originalPrice, $percent, $selctedArray = null, $priceValue = null)
    {
        $price = 0;

        if (! isset($originalPrice) || $originalPrice === null || $originalPrice === '') {
            $originalPrice = 0;
        }

        if (! is_numeric($originalPrice)) {
            throw new \InvalidArgumentException('Original price must be a numeric value.');
        }
        $_percent = $this->preparePercentData($percent);
        if ($_percent > 0) {
            $price = $this->calculatePrice($originalPrice, $_percent);
        } else {
            $price = $originalPrice;
        }

        return $price;

    }

    /**
     * @coveredBy testPrepareIsPaidData*
     */
    public function prepareIsPaidData($isPaidValue): int
    {
        if ($isPaidValue == null) {
            return 0;
        }

        if (is_bool($isPaidValue)) {
            return $isPaidValue ? 1 : 0;
        }

        $normalizedValue = filter_var($isPaidValue, FILTER_VALIDATE_INT, [
            'options' => ['min_range' => 0, 'max_range' => 1],
        ]);

        if ($normalizedValue === false) {
            throw new \InvalidArgumentException(
                sprintf('Invalid isPaidValue. Expected 0, 1, true, or false, got %s',
                    is_scalar($isPaidValue) ? var_export($isPaidValue, true) : gettype($isPaidValue)
                )
            );
        }

        return $normalizedValue;
    }

    /**
     * @coveredBy testPrepareVendorIdData*
     */
    public function prepareVendorIdData($vendorIdValue): ?int
    {
        if ($vendorIdValue === null) {
            return null;
        }

        $normalizedValue = filter_var($vendorIdValue, FILTER_VALIDATE_INT, [
            'options' => ['min_range' => 1],
        ]);

        if ($normalizedValue === false) {
            throw new \InvalidArgumentException(
                sprintf('Invalid vendorIdValue. Expected positive integer, got %s',
                    is_scalar($vendorIdValue) ? var_export($vendorIdValue, true) : gettype($vendorIdValue)
                )
            );
        }

        return $normalizedValue;
    }

    // //////////////////////////////////////////////////////////////////
    // / Private Functions
    // //////////////////////////////////////////////////////////////////

    // -------------------------------------------------------------------
    // Data Preparations
    // -------------------------------------------------------------------

    private function prepareUpdateRemainingPostCountData($userInfo)
    {
        return [
            Constants::usrRemainingPost => (int) $userInfo->value - 1,
        ];
    }

    private function prepareDuplicateStatusData($status)
    {
        $systemConfig = $this->systemConfigService->get();
        if ($systemConfig->is_approved_enable == 1) {
            $status = 0;
        } else {
            $status = $status;
        }

        return $status;
    }

    private function prepareDuplicateData($itemObj)
    {
        return [
            'title' => 'Copy of '.$itemObj->title,
            'status' => $this->prepareDuplicateStatusData($itemObj->status),
            'added_user_id' => $itemObj->added_user_id,
            'added_date' => Carbon::now(),
            'updated_user_id' => null,
            'updated_date' => null,
        ];
    }

    private function prepareUpdateStausData($status)
    {
        return ['status' => $status];
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

    private function prepareSaveVideoData($id, $imgType)
    {
        return [
            'img_parent_id' => $id,
            'img_type' => $imgType,
        ];
    }

    private function prepareApprovalMsgAndSubjectData($item)
    {
        if ($item->status == Constants::publishItem) {
            $message = __('core__be_item_approved_msg', ['item' => $item->title]);
            $subject = __('core__be_item_approved');
        } elseif ($item->status == Constants::rejectItem) {
            $message = __('core__be_item_rejected_msg', ['item' => $item->title]);
            $subject = __('core__be_item_rejected');
        } elseif ($item->status == Constants::disableItem) {
            $message = __('core__be_item_disabled_msg', ['item' => $item->title]);
            $subject = __('core__be_item_disabled');
        } elseif ($item->status == Constants::pendingItem) {
            $message = __('core__be_item_pending_msg', ['item' => $item->title]);
            $subject = __('core__be_item_pending');
        }

        return [$message, $subject];
    }

    private function prepareDeviceIdsAndPlatformNames($notiTokens)
    {
        $device_ids = [];
        $platform_names = [];
        foreach ($notiTokens as $token) {
            $device_ids[] = $token->device_token;
            $platform_names[] = $token->platform_name;
        }

        return [$device_ids, $platform_names];
    }

    // -------------------------------------------------------------------
    // Database
    // -------------------------------------------------------------------

    private function saveItem($itemData)
    {
        $setting = $this->settingService->get(env: Constants::SYSTEM_CONFIG);
        $systemConfig = $this->systemConfigService->get();
        $selctedArray = json_decode($setting->setting, true);

        unset($itemData['images'], $itemData['img_caption'], $itemData['img_order']);

        $item = new Item;
        $item->fill($itemData);
        $item->status = $this->prepareStatusData($itemData[Item::status], $systemConfig);
        $item->currency_id = $this->prepareCurrencyIdData($itemData[Item::itemCurrencyId], $selctedArray);
        $item->percent = $this->preparePercentData($itemData[Item::percent]);
        $item->is_discount = $this->prepareisDiscountData($item->percent);
        $item->original_price = $this->prepareOriginalPriceData($item[Item::originalPrice]);
        $item->price = $this->preparePriceData($item->original_price, $item->percent, $selctedArray, $item[Item::price]);
        $item->is_paid = $this->prepareIsPaidData($item[Item::isPaid]);
        $item->vendor_id = $this->prepareVendorIdData($item[Item::vendorId]);
        $item->added_user_id = ! empty($itemData[Item::addedUserId]) ? $itemData[Item::addedUserId] : Auth::user()->id;
        $item->save();

        return $item;
    }

    private function updateItem($id, $itemData)
    {
        $setting = $this->settingService->get(env: Constants::SYSTEM_CONFIG);
        $systemConfig = $this->systemConfigService->get();
        $selctedArray = json_decode($setting->setting, true);

        $item = $this->get($id);
        $item->fill($itemData);
        $item->status = $this->prepareStatusData($item->status, $systemConfig);
        $item->currency_id = $this->prepareCurrencyIdData($itemData[Item::itemCurrencyId], $selctedArray);
        $item->percent = $this->preparePercentData($itemData[Item::percent]);
        $item->is_discount = $this->prepareisDiscountData($item->percent);
        $item->original_price = $this->prepareOriginalPriceData($item[Item::originalPrice]);
        $item->price = $this->preparePriceData($item->original_price, $item->percent, $selctedArray, $item[Item::price]);
        $item->is_paid = $this->prepareIsPaidData($item[Item::isPaid]);
        $item->vendor_id = $this->prepareVendorIdData($item[Item::vendorId]);
        $item->updated_user_id = Auth::user()->id;
        $item->update();

        return $item;
    }

    private function updateDropzoneImageCaption($imgCaptionData)
    {
        if (empty($imgCaptionData['img_caption'])) {
            return;
        }

        $captions = array_filter($imgCaptionData['img_caption'], fn ($caption) => $caption['name'] !== 'undefined');

        foreach ($captions as $caption) {
            $img = $this->imageService->get(['img_path' => $caption['name']]);
            if ($img) {
                $img->update(['img_desc' => $caption['value']]);
            }
        }
    }

    private function updateDropzoneImageOrder($imgOrderData)
    {
        if (empty($imgCaptionData['img_order'])) {
            return;
        }

        foreach ($imgOrderData['img_order'] as $order) {
            $img = $this->imageService->get(['id' => $order['id']]);
            if ($img) {
                $img->update(['ordering' => $order['order']]);
            }
        }
    }

    // -------------------------------------------------------------------
    // Other
    // -------------------------------------------------------------------

    private function priceType($selctedArray)
    {
        return $selctedArray['selected_price_type']['id'];
    }

    private function calculatePrice($originalPrice, $percentValue)
    {
        return $originalPrice - ((floatval($percentValue) / 100) * $originalPrice);
    }

    private function itemDeeplinkGenerate($itemId)
    {
        $deepLinkServiceProvider = $this->dynamicLinkService->getDeepLinkServiceProvider();
        try {
            if ($deepLinkServiceProvider == ps_constant::FIREBASE) {
                $conds = [CoreImage::imgParentId => $itemId, CoreImage::imgType => 'item', CoreImage::ordering => 1];
                $image = $this->imageService->get(conds: $conds);
                $img = $image ? $image->img_path : '';
                $item = $this->get($itemId);
                $dynamic_link = deeplinkGenerate($item->id, $item->title, $item->description, $img);
                $item->dynamic_link = $dynamic_link['msg'];
                $item->update();
            } elseif ($deepLinkServiceProvider == ps_constant::PSX_DYNAMIC_LINK) {
                $item = $this->get($itemId);
                $dynamic_link = $this->dynamicLinkService->generateDynamicLinks($item, ['item_id' => Item::id], ps_constant::DYNAMIC_LINK_ITEM);
                if ($dynamic_link != null && $dynamic_link->isNotEmpty()) {
                    $item->dynamic_link = $dynamic_link[0]['short_code'];
                    $item->update();
                } else {
                    throw new \Exception("Failed to generate dynamic link for item ID {$itemId}");
                }
            }
        } catch (Throwable $e) {
            throw $e;
        }
    }

    /**
     * @coverdBy testUpdateItemInfo*
     */
    public function updateItemInfo($itemId, $relationalData, $categoryId = null)
    {
        $customFieldCoreKeysIds = $this->customFieldService->getAll(
            code: Constants::item,
            categoryId: $categoryId,
            withNoPag: Constants::yes
        )->pluck(ItemInfo::coreKeysId)->toArray();
        $coreKeysIds = array_keys($relationalData);

        foreach ($customFieldCoreKeysIds as $customFieldCoreKey) {
            if (! in_array($customFieldCoreKey, $coreKeysIds)) {
                $relationalData[$customFieldCoreKey] = null;
            }
        }
        $this->itemInfoService->update($itemId, $relationalData);
    }
}
