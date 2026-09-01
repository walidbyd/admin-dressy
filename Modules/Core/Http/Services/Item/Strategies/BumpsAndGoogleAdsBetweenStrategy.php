<?php

namespace Modules\Core\Http\Services\Item\Strategies;

use App\Http\Contracts\Configuration\SystemConfigServiceInterface;
use App\Http\Contracts\Item\SearchItemServiceInterface;
use Modules\Core\Constants\Constants;
use Modules\Core\DTOs\Item\SearchItemDto;
use Modules\Core\Http\Services\Item\ItemService;

class BumpsAndGoogleAdsBetweenStrategy implements ItemSearchStrategyInterface
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
            $getAllItemDto->limit,
            $getAllItemDto->offset,
            $interval
        );

        $visiblePattern = $this->itemService->generateVisiblePatternArray(
            limit: $getAllItemDto->limit,
            offset: $getAllItemDto->offset,
            interval: $interval
        );

        $fetchedItems = $this->fetchItems(
            $getAllItemDto,
            $systemConfig->is_block_user,
            $limitAndOffsets
        );

        $googleItem = new \stdClass;
        $googleItem->ad_type = Constants::googleAd;

        $items = $this->mergeItemsByPattern(
            $visiblePattern,
            $fetchedItems['normalItems'],
            $fetchedItems['paidItems'],
            $googleItem,
            $getAllItemDto->offset,
            $interval
        );

        return $items;
    }

    // //////////////////////////////////////////////////////////////////
    // / Private Functions
    // //////////////////////////////////////////////////////////////////

    /**
     * @coveredBy testMergeItemsByPattern*
     */
    private function mergeItemsByPattern(array $pattern, $normalItems, $paidItems, $googleItem, $offset, $interval): array
    {
        $items = [];
        $normalIndex = 0;
        $paidIndex = 0;
        $showGoogle = $this->shouldShowGoogleFirst($paidItems->count(), $offset, $interval);
        $totalItems = $normalItems->count() + $paidItems->count();

        if ($totalItems === 0) {
            return $items;
        }

        foreach ($pattern as $slot) {
            if ($slot === 'one' && $normalIndex < $normalItems->count()) {
                $items[] = $normalItems[$normalIndex++];
            } elseif ($paidIndex < $paidItems->count()) {
                $items[] = $showGoogle ? $googleItem : $paidItems[$paidIndex++];
                $showGoogle = ! $showGoogle;
            } else {
                $items[] = $googleItem;
            }

            if ($normalIndex + $paidIndex >= $totalItems) {
                break;
            }
        }

        return $items;
    }

    /**
     * @coveredBy testFetchItems*
     */
    private function fetchItems(SearchItemDto $getAllItemDto, string $isBlockUser, array $limitAndOffsets)
    {
        $paidItemFilters = $this->searchItemService->preparePaidItemFiltersData($getAllItemDto->filters);
        $filtersNotIn = $this->searchItemService->prepareFiltersNotInData($isBlockUser, $getAllItemDto->loginUserId, $getAllItemDto->filters['exclude_ids'] ?? []);
        $normalItemFiltersNotIn = $this->searchItemService->prepareNormalItemFiltersNotInData($filtersNotIn);

        $effectivePaidLimit = ceil($limitAndOffsets['paidLimit'] / 2);
        $effectivePaidOffset = ceil($limitAndOffsets['paidOffset'] / 2);
        $paidItems = $this->itemService->getAll(
            $getAllItemDto->relation,
            $paidItemFilters,
            $getAllItemDto->sorting,
            $effectivePaidLimit,
            $effectivePaidOffset,
            Constants::yes,
            $filtersNotIn
        );

        $normalItems = $this->itemService->getAll(
            $getAllItemDto->relation,
            $getAllItemDto->filters,
            $getAllItemDto->sorting,
            $limitAndOffsets['normalLimit'],
            $limitAndOffsets['normalOffset'],
            Constants::yes,
            $normalItemFiltersNotIn
        );

        return [
            'paidItems' => $paidItems,
            'normalItems' => $normalItems,
        ];
    }

    /**
     * @coveredBy testShouldShowGoogleFirst*
     */
    private function shouldShowGoogleFirst(int $paidItemsCount, $offset, $interval): bool
    {
        if ($paidItemsCount === 0) {
            return false;
        }

        $totalPaidShown = $offset / ($interval + 1);

        return ($totalPaidShown % 2) === 0;
    }
}
