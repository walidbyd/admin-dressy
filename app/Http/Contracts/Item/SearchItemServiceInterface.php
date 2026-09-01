<?php

namespace App\Http\Contracts\Item;

use App\Http\Contracts\Core\PsInterface;

interface SearchItemServiceInterface extends PsInterface
{
    public function prepareFiltersNotInData($isBlockUser, $loginUserId, $exculdeIds = []);

    public function preparePaidItemFiltersData($filters);

    public function prepareNormalItemFiltersNotInData($filtersNotIn);
}
