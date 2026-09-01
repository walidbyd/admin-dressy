<?php

namespace Modules\Core\Policies;

use App\Policies\PsPolicy;
use Illuminate\Auth\Access\HandlesAuthorization;
use Modules\Core\Constants\Constants;
use Modules\Core\Entities\Item\PaidItemHistory;

class PaidItemHistoryPolicy extends PsPolicy
{
    use HandlesAuthorization;

    /**
     * Create a new policy instance.
     *
     * @return void
     */
    public function __construct()
    {
        //
        $module = Constants::promotionReportModule;
        $model = PaidItemHistory::class;
        parent::__construct($module, $model);
    }
}
