<?php

namespace Modules\Core\Http\Services\Item\Strategies;

use App\Http\Contracts\Configuration\SystemConfigServiceInterface;
use App\Http\Contracts\Item\SearchItemServiceInterface;
use Modules\Core\Constants\Constants;
use Modules\Core\DTOs\Item\SearchItemDto;
use Modules\Core\Http\Services\Item\ItemService;

class BumpsUpsBetweenStrategy implements ItemSearchStrategyInterface
{
    public function __construct(
        protected ItemService $itemService,
        protected SearchItemServiceInterface $searchItemService,
        protected SystemConfigServiceInterface $systemConfigService,
    ) {}

    /**
     * @coveredBy testGetAll
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

        $visiblePattern = $this->itemService->generateVisiblePatternArray(
            limit: $getAllItemDto->limit,
            offset: $getAllItemDto->offset,
            interval: $interval
        );

        $paidItemFilters = $this->searchItemService->preparePaidItemFiltersData($getAllItemDto->filters);
        $filtersNotIn = $this->searchItemService->prepareFiltersNotInData($systemConfig->is_block_user, $getAllItemDto->loginUserId, $getAllItemDto->filters['exclude_ids'] ?? []);
        $normalItemFiltersNotIn = $this->searchItemService->prepareNormalItemFiltersNotInData($filtersNotIn);

        $totalPaidNeeded = $limitAndOffsets['paidOffset'] + $limitAndOffsets['paidLimit'];
        $allPaidItems = $this->itemService->getAll(
            $getAllItemDto->relation,
            $paidItemFilters,
            $getAllItemDto->sorting,
            $totalPaidNeeded,
            null,
            Constants::yes,
            $filtersNotIn
        );

        $paidItems = $allPaidItems->slice($limitAndOffsets['paidOffset'], $limitAndOffsets['paidLimit'])->values();

        $availableAfterOffset = max(0, $allPaidItems->count() - $limitAndOffsets['paidOffset']);
        $actualPaidCount = min($availableAfterOffset, $limitAndOffsets['paidLimit']);
        $missingPaidCount = max(0, $limitAndOffsets['paidLimit'] - $actualPaidCount);

        $adjustedNormalOffset = $limitAndOffsets['normalOffset'] + max(0, $limitAndOffsets['paidOffset'] - $allPaidItems->count());
        $adjustedNormalLimit = $limitAndOffsets['normalLimit'] + $missingPaidCount;

        $normalItems = $this->itemService->getAll(
            $getAllItemDto->relation,
            $getAllItemDto->filters,
            $getAllItemDto->sorting,
            $adjustedNormalLimit,
            $adjustedNormalOffset,
            Constants::yes,
            $normalItemFiltersNotIn
        );

        $items = $this->mergeItemsByPattern($visiblePattern, $paidItems, $normalItems);

        return $items;
    }

    // //////////////////////////////////////////////////////////////////
    // / Private Functions
    // //////////////////////////////////////////////////////////////////

    private function mergeItemsByPattern(array $pattern, $paidItems, $normalItems): array
    {
        $items = [];
        $paidIndex = 0;
        $normalIndex = 0;

        foreach ($pattern as $type) {
            if ($type === 'zero' && $paidIndex < $paidItems->count()) {
                $items[] = $paidItems[$paidIndex++];
            } elseif ($normalIndex < $normalItems->count()) {
                $items[] = $normalItems[$normalIndex++];
            } elseif ($paidIndex < $paidItems->count()) {
                $items[] = $paidItems[$paidIndex++];
            }
        }

        return $items;
    }
}
