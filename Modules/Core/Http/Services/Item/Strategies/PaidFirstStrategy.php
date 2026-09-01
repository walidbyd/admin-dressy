<?php

namespace Modules\Core\Http\Services\Item\Strategies;

use App\Http\Contracts\Configuration\SystemConfigServiceInterface;
use App\Http\Contracts\Item\SearchItemServiceInterface;
use Modules\Core\Constants\Constants;
use Modules\Core\DTOs\Item\SearchItemDto;
use Modules\Core\Http\Services\Item\ItemService;

class PaidFirstStrategy implements ItemSearchStrategyInterface
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

        return $pagedPaidItems->count() > 0 ? $pagedPaidItems->merge($normalItems) : $normalItems;
    }
}
