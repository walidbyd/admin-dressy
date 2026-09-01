<?php

namespace Modules\Core\DTOs\Item;

use Illuminate\Http\Request;
use Modules\Core\Constants\Constants;

final class SearchItemDto
{
    public function __construct(
        public readonly string $loginUserId,
        public readonly int $limit,
        public readonly int $offset,
        public readonly array $sorting,
        public readonly array $relation,
        public readonly array $filters,
    ) {}

    /**
     * @coveredBy testFrom
     */
    public static function from(Request $request, $loginUserId, $itemApiRelation): SearchItemDto
    {
        [$limit, $offset] = getLimitOffsetFromSetting($request);

        $sorting = self::prepareSortingData($request);

        $filters = self::prepareFiltersData($request, $loginUserId);

        return new SearchItemDto(
            loginUserId: $loginUserId,
            limit: $limit,
            offset: $offset,
            sorting: $sorting,
            relation: $itemApiRelation,
            filters: $filters,
        );
    }

    // //////////////////////////////////////////////////////////////////
    // / Private Functions
    // //////////////////////////////////////////////////////////////////

    // -------------------------------------------------------------------
    // Data Preparations
    // -------------------------------------------------------------------
    private static function prepareFiltersData(Request $request, $loginUserId)
    {
        $productRelation = [];
        if ($request->has('product_relation')) {
            foreach ($request->product_relation as $relation) {
                $productRelation[$relation['core_keys_id']] = $relation['value'];
            }
        }

        return [
            'login_user_id' => $loginUserId,
            'category_id' => $request->cat_id,
            'subcategory_id' => $request->sub_cat_id,
            'is_sold_out' => $request->is_sold_out,
            'is_discount' => $request->is_discount,
            'status' => $request->status,
            'keyword' => $request->searchterm,
            'infos_filter' => $productRelation,
            'exclude_ids' => $request->product_not_in,
            'currency_id' => $request->item_currency_id,
            'location_city_id' => $request->item_location_id,
            'location_township_id' => $request->item_location_township_id,
            'max_price' => $request->max_price,
            'min_price' => $request->min_price,
            'lat' => $request->lat,
            'lng' => $request->lng,
            'miles' => $request->miles,
            'added_user_id' => $request->added_user_id,
            'vendor_id' => $request->vendor_id,
        ];
    }

    private static function prepareSortingData(Request $request)
    {
        $sort = [];
        if ($request->has('order_by') && $request->order_by) {
            $sort = [
                $request->order_by => $request->order_type ?? Constants::descending,
            ];
        }

        return $sort;
    }
}
