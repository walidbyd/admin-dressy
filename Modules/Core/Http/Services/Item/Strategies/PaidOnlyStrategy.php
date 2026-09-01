<?php

namespace Modules\Core\Http\Services\Item\Strategies;

use App\Http\Contracts\Configuration\SystemConfigServiceInterface;
use App\Http\Contracts\Item\SearchItemServiceInterface;
use Modules\Core\Constants\Constants;
use Modules\Core\DTOs\Item\SearchItemDto;
use Modules\Core\Http\Services\Item\ItemService;

class PaidOnlyStrategy implements ItemSearchStrategyInterface
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

        return $this->itemService->getAll(
            $getAllItemDto->relation,
            $paidItemFilters,
            $getAllItemDto->sorting,
            $getAllItemDto->limit,
            $getAllItemDto->offset,
            Constants::yes,
            $filtersNotIn
        );
    }
}
