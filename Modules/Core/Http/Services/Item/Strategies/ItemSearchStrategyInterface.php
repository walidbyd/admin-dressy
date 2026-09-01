<?php

namespace Modules\Core\Http\Services\Item\Strategies;

use Modules\Core\DTOs\Item\SearchItemDto;

interface ItemSearchStrategyInterface
{
    public function getAll(SearchItemDto $getAllItemDto);
}
