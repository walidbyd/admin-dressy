<?php

namespace Modules\Core\DTOs;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;

final class ItemDto
{
    public function __construct(
        public readonly ?int $id,
        public readonly string $title,
        public readonly int $categoryId,
        public readonly ?int $subcategoryId,

        // currencyId is not nullable in DB
        // if currenyId is null, need to set the default currency before save or update.
        public readonly ?int $currencyId,

        public readonly int $locationCityId,
        public readonly ?int $locationTownshipId,
        public readonly ?int $shopId,
        public readonly float $price,
        public readonly ?float $originalPrice,
        public readonly ?string $description,
        public readonly ?string $searchTag,
        public readonly ?string $dynamicLink,
        public readonly ?string $lat,
        public readonly ?string $lng,

        // status is not nullable in DB
        // if status is null, based on the settting need to set the related status before save or update.
        public readonly ?string $status,

        // isPaid is not nullable in DB
        // if isPaid is null, set 0 as default before save or update.
        public readonly ?int $isPaid,

        public readonly int $isSoldOut,
        public readonly ?string $ordering,
        public readonly int $isAvailable,
        public readonly int $isDiscount,
        public readonly int $itemTouchCount,
        public readonly int $favouriteCount,
        public readonly int $overallRating,
        public readonly ?int $vendorId,

        // addedUserId is not nullable in DB
        // if addedUserId is null, set current login id or return error
        public readonly ?int $addedUserId,

        public readonly ?int $updatedUserId,
        public readonly ?float $percent,
        public readonly ?string $phone,

        // Other Informations
        public readonly ?array $imgOrder,
        public readonly ?array $imgCaption,
        public readonly string $loginUserId,
        public readonly string $languageSymbol,
        public readonly array $customFields = [],

        public readonly array $images = [],
        public readonly ?UploadedFile $videoIcon = null,
        public readonly ?UploadedFile $video = null
    ) {}

    /**
     * @coveredBy testFrom*
     */
    public static function from($request): self
    {
        $data = $request->validated();
        $customData = self::prepareDataCustomFields($request);

        return new self(
            // Item Data
            $data['id'] ?? null,
            $data['title'],
            $data['category_id'],
            $data['subcategory_id'] ?? null,
            $data['currency_id'] ?? null,
            $data['location_city_id'],
            $data['location_township_id'] ?? null,
            $data['shop_id'] ?? null,
            (float) ($data['price'] ?? 0.0),
            $data['original_price'],
            $data['description'] ?? '',
            $data['search_tag'] ?? null,
            $data['dynamic_link'] ?? null,
            $data['lat'] ?? 0,
            $data['lng'] ?? 0,
            $data['status'] ?? null,
            $data['is_paid'] ?? null,
            $data['is_sold_out'] ?? 0,
            $data['ordering'] ?? null,
            $data['is_available'] ?? 1,
            $data['is_discount'] ?? 0,
            $data['item_touch_count'] ?? 0,
            $data['favourite_count'] ?? 0,
            $data['overall_rating'] ?? 0,
            $data['vendor_id'] ?? null,
            $data['added_user_id'] ?? null,
            $data['updated_user_id'] ?? null,
            $data['percent'] ?? null,
            $data['phone'] ?? null,
            // Other Informations
            $data['img_order'] ?? [],
            $data['img_caption'] ?? [],
            (string) ($data['login_user_id'] ?? Auth::id()),
            (string) ($data['language_symbol'] ?? ''),
            $customData,
            $data['images'] ?? [],
            $request->file('video_icon') ?? null,
            $request->file('video') ?? null,

        );

    }

    /**
     * @coveredBy testCopyWith*
     */
    public function copyWith(
        ?string $title = null,
        ?int $categoryId = null,
        ?int $subcategoryId = null,
        ?int $currencyId = null,
        ?int $locationCityId = null,
        ?int $locationTownshipId = null,
        ?int $shopId = null,
        ?float $price = null,
        ?string $originalPrice = null,
        ?string $description = null,
        ?string $searchTag = null,
        ?string $dynamicLink = null,
        ?string $lat = null,
        ?string $lng = null,
        ?string $status = null,
        ?int $isPaid = null,
        ?int $isSoldOut = null,
        ?string $ordering = null,
        ?int $isAvailable = null,
        ?string $isDiscount = null,
        ?int $itemTouchCount = null,
        ?int $overallRating = null,
        ?int $vendorId = null,
        ?int $addedUserId = null,
        ?float $percent = null,
        ?string $phone = null,
        ?int $imgOrder = null,
        ?string $imgCaption = null,
        ?string $loginUserId = null,
        ?string $languageSymbol = null,
        ?array $customFields = null,
        ?array $images = null,
        ?UploadedFile $video = null,
        ?UploadedFile $videoIcon = null
    ): self {
        return new self(
            id: $this->id,
            title: $title ?? $this->title,
            categoryId: $categoryId ?? $this->categoryId,
            subcategoryId: $subcategoryId ?? $this->subcategoryId,
            currencyId: $currencyId ?? $this->currencyId,
            locationCityId: $locationCityId ?? $this->locationCityId,
            locationTownshipId: $locationTownshipId ?? $this->locationTownshipId,
            shopId: $shopId ?? $this->shopId,
            price: $price ?? $this->price,
            originalPrice: $originalPrice ?? $this->originalPrice,
            description: $description ?? $this->description,
            searchTag: $searchTag ?? $this->searchTag,
            dynamicLink: $dynamicLink ?? $this->dynamicLink,
            lat: $lat ?? $this->lat,
            lng: $lng ?? $this->lng,
            status: $status ?? $this->status,
            isSoldOut: $isSoldOut ?? $this->isSoldOut,
            isPaid: $isPaid ?? $this->isPaid,
            ordering: $ordering ?? $this->ordering,
            isAvailable: $isAvailable ?? $this->isAvailable,
            isDiscount: $isDiscount ?? $this->isDiscount,
            itemTouchCount: $itemTouchCount ?? $this->itemTouchCount,
            favouriteCount: $favouriteCount ?? $this->favouriteCount,
            overallRating: $overallRating ?? $this->overallRating,
            vendorId: $vendorId ?? $this->vendorId,
            addedUserId: $addedUserId ?? $this->addedUserId,
            updatedUserId: $updatedUserId ?? $this->updatedUserId,
            percent: $percent ?? $this->percent,
            phone: $phone ?? $this->phone,
            imgOrder: $imgOrder ?? $this->imgOrder,
            imgCaption: $imgCaption ?? $this->imgCaption,
            loginUserId: $loginUserId ?? $this->loginUserId,
            languageSymbol: $languageSymbol ?? $this->languageSymbol,
            customFields: $customFields ?? $this->customFields,
            images : $images ?? $this->images,
            video : $video ?? $this->video,
            videoIcon : $videoIcon ?? $this->videoIcon
        );
    }

    /**
     * @coveredBy testToArray*
     */
    public function toArray()
    {
        return [

            'id' => $this->id,
            'title' => $this->title,
            'category_id' => $this->categoryId,
            'subcategory_id' => $this->subcategoryId,
            'currency_id' => $this->currencyId,
            'location_city_id' => $this->locationCityId,
            'location_township_id' => $this->locationTownshipId,
            'shop_id' => $this->shopId,
            'price' => $this->price,
            'original_price' => $this->originalPrice,
            'description' => $this->description,
            'search_tag' => $this->searchTag,
            'dynamic_link' => $this->dynamicLink,
            'lat' => $this->lat,
            'lng' => $this->lng,
            'status' => $this->status,
            'is_paid' => $this->isPaid,
            'is_sold_out' => $this->isSoldOut,
            'ordering' => $this->ordering,
            'is_available' => $this->isAvailable,
            'is_discount' => $this->isDiscount,
            'item_touch_count' => $this->itemTouchCount,
            'favourite_count' => $this->favouriteCount,
            'overall_rating' => $this->overallRating,
            'vendor_id' => $this->vendorId,
            'added_user_id' => $this->addedUserId,
            'updated_user_id' => $this->updatedUserId,
            'percent' => $this->percent,
            'phone' => $this->phone,

        ];
    }

    // //////////////////////////////////////////////////////////////////
    // / Private Functions
    // //////////////////////////////////////////////////////////////////

    private static function prepareDataCustomFields($request)
    {
        // Retrieve the 'relation' input as an array of strings
        $relationsInput = $request->input('product_relation', []);

        // Retrieve the 'relation' files as an array of files
        $relationsFiles = ! empty($request->allFiles()['product_relation']) ? $request->allFiles()['product_relation'] : [];

        // Merge the input and files arrays, preserving keys
        return array_merge($relationsInput, $relationsFiles);
    }
}
