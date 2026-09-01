<?php

namespace Modules\Core\Http\Services\Utilities;

use App\Http\Contracts\Utilities\UiTypeServiceInterface;
use App\Http\Services\PsService;
use Modules\Core\Entities\Utilities\UiType;

class UiTypeService extends PsService implements UiTypeServiceInterface
{
    public function __construct() {}

    public function getAll($coreKeyIds, $limit, $offset)
    {
        $uiTypes = UiType::when($coreKeyIds, function ($query, $coreKeyIds) {
            $query->whereIn('core_keys_id', $coreKeyIds);
        })
            ->when($limit, function ($query, $limit) {
                $query->limit($limit);
            })
            ->when($offset, function ($query, $offset) {
                $query->offset($offset);
            })
            ->get();

        return $uiTypes;
    }

    // //////////////////////////////////////////////////////////////////
    // / Private Functions
    // //////////////////////////////////////////////////////////////////

}
