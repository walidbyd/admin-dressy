<?php

namespace Modules\Core\Http\Services\Item\Strategies;

use App\Http\Contracts\Configuration\SystemConfigServiceInterface;
use App\Http\Contracts\Item\SearchItemServiceInterface;
use Modules\Core\Constants\Constants;
use Modules\Core\DTOs\Item\SearchItemDto;
use Modules\Core\Http\Services\Item\ItemService;

class PaidFirstWithGoogleStrategy implements ItemSearchStrategyInterface
{
    public function __construct(
        protected ItemService $itemService,
        protected SearchItemServiceInterface $searchItemService,
        protected SystemConfigServiceInterface $systemConfigService,
    ) {}

    public function getAll(SearchItemDto $getAllItemDto)
    {
        $systemConfig = $this->systemConfigService->get();
        $interval = $systemConfig->promo_cell_interval_no;

        $paidItemFilters = $this->searchItemService->preparePaidItemFiltersData($getAllItemDto->filters);
        $filtersNotIn = $this->searchItemService->prepareFiltersNotInData($systemConfig->is_block_user, $getAllItemDto->loginUserId, $getAllItemDto->filters['exclude_ids'] ?? []);
        $normalItemFiltersNotIn = $this->searchItemService->prepareNormalItemFiltersNotInData($filtersNotIn);

        $paidItemsAll = $this->itemService->getAll(
            relations: $getAllItemDto->relation,
            filters: $paidItemFilters,
            sorting: $getAllItemDto->sorting,
            limit: null,
            offset: null,
            noPagination: Constants::yes,
            filterNotIn: $filtersNotIn
        );
        $pagedPaidItems = $paidItemsAll->slice($getAllItemDto->offset, $getAllItemDto->limit)->values();

        $remainingNormalLimit = max(0, $getAllItemDto->limit - $pagedPaidItems->count());
        $adjustedNormalOffset = max(0, $getAllItemDto->offset - $paidItemsAll->count());

        $visiblePattern = $this->itemService->generateVisiblePatternArray(
            limit: $remainingNormalLimit,
            offset: $adjustedNormalOffset,
            interval: $interval
        );
        $normalItems = $remainingNormalLimit > 0 ?
            $this->itemService->getAll(
                relations: $getAllItemDto->relation,
                filters: $getAllItemDto->filters,
                sorting: $getAllItemDto->sorting,
                limit: $remainingNormalLimit,
                offset: $adjustedNormalOffset,
                noPagination: Constants::yes,
                filterNotIn: $normalItemFiltersNotIn
            ) : collect();
        $googleAdItems = $this->mergeItemsWithAds($normalItems, $visiblePattern);

        return collect($pagedPaidItems)->merge($googleAdItems);
    }

    private function mergeItemsWithAds($normalItems, array $visiblePattern)
    {
        $googleItem = $this->createGoogleAdPlaceholder();

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

    private function createGoogleAdPlaceholder(): \stdClass
    {
        $googleItem = new \stdClass;
        $googleItem->ad_type = Constants::googleAd;

        return $googleItem;
    }
}
