<?php

namespace Modules\Core\Http\Services\Item\Strategies;

use App\Http\Contracts\Configuration\SystemConfigServiceInterface;
use App\Http\Contracts\Item\SearchItemServiceInterface;
use Modules\Core\Constants\Constants;
use Modules\Core\DTOs\Item\SearchItemDto;
use Modules\Core\Http\Services\Item\ItemService;

class GoogleAdsBetweenStrategy implements ItemSearchStrategyInterface
{
    public function __construct(
        protected ItemService $itemService,
        protected SearchItemServiceInterface $searchItemService,
        protected SystemConfigServiceInterface $systemConfigService,
    ) {}

    /**
     * @coveredBy testGetAll*
     */
    public function getAll(SearchItemDto $getAllItemDto)
    {
        $systemConfig = $this->systemConfigService->get();
        $interval = $systemConfig->promo_cell_interval_no;

        $limitAndOffsets = $this->itemService->calculateItemLimitAndOffset(
            limit: $getAllItemDto->limit,
            offset: $getAllItemDto->offset,
            interval: $interval
        );

        $normalLimit = $limitAndOffsets['normalLimit'];
        $normalOffset = $limitAndOffsets['normalOffset'];

        $visiblePattern = $this->itemService->generateVisiblePatternArray(
            limit: $getAllItemDto->limit,
            offset: $getAllItemDto->offset,
            interval: $interval
        );

        $filtersNotIn = $this->searchItemService->prepareFiltersNotInData($systemConfig->is_block_user, $getAllItemDto->loginUserId, $getAllItemDto->filters['exclude_ids'] ?? []);

        $normalItemFiltersNotIn = $this->searchItemService->prepareNormalItemFiltersNotInData($filtersNotIn);

        $normalItems = $this->itemService->getAll(
            relations: $getAllItemDto->relation,
            filters: $getAllItemDto->filters,
            sorting: $getAllItemDto->sorting,
            limit: $normalLimit,
            offset: $normalOffset,
            noPagination: Constants::yes,
            filterNotIn: $normalItemFiltersNotIn
        );

        return $this->mergeItemsWithAds(
            normalItems: $normalItems,
            visiblePattern: $visiblePattern
        );
    }

    // //////////////////////////////////////////////////////////////////
    // / Private Functions
    // //////////////////////////////////////////////////////////////////

    private function mergeItemsWithAds($normalItems, array $visiblePattern)
    {
        $googleItem = new \stdClass;
        $googleItem->ad_type = Constants::googleAd;

        $mergedItems = [];
        $normalIndex = 0;
        $totalNormal = $normalItems->count() ?: 0;

        foreach ($visiblePattern as $pattern) {
            if ($pattern == 'one' && $normalIndex < $totalNormal && $totalNormal > 0) {
                $mergedItems[] = $normalItems[$normalIndex++];
            } else {
                $mergedItems[] = $googleItem;
                if ($normalIndex >= $totalNormal) {
                    break;
                }
            }
        }

        return $mergedItems;
    }
}
