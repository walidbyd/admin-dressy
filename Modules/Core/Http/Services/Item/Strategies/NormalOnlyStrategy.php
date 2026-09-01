<?php

namespace Modules\Core\Http\Services\Item\Strategies;

use App\Http\Contracts\Configuration\SystemConfigServiceInterface;
use App\Http\Contracts\Item\SearchItemServiceInterface;
use Modules\Core\Constants\Constants;
use Modules\Core\DTOs\Item\SearchItemDto;
use Modules\Core\Http\Services\Item\ItemService;

class NormalOnlyStrategy implements ItemSearchStrategyInterface
{
    public function __construct(
        protected ItemService $itemService,
        protected SearchItemServiceInterface $searchItemService,
        protected SystemConfigServiceInterface $systemConfigService,
    ) {}

    public function getAll(SearchItemDto $getAllItemDto)
    {
        $systemConfig = $this->systemConfigService->get();

        $filterNotIn = $this->searchItemService->prepareFiltersNotInData($systemConfig->is_block_user, $getAllItemDto->loginUserId, $getAllItemDto->filters['exclude_ids'] ?? []);

        return $this->itemService->getAll(
            relations: $getAllItemDto->relation,
            filters: $getAllItemDto->filters,
            sorting: $getAllItemDto->sorting,
            limit: $getAllItemDto->limit,
            offset: $getAllItemDto->offset,
            noPagination: Constants::yes,
            filterNotIn: $filterNotIn
        );
    }
}
