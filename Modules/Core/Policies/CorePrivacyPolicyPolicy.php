<?php

namespace Modules\Core\Policies;

use App\Policies\PsPolicy;
use Illuminate\Auth\Access\HandlesAuthorization;
use Modules\Core\Constants\Constants;
use Modules\Core\Entities\Information\CorePrivacyPolicy;

class CorePrivacyPolicyPolicy extends PsPolicy
{
    use HandlesAuthorization;

    /**
     * Create a new policy instance.
     *
     * @return void
     */
    public function __construct()
    {
        $module = Constants::privacyPolicyModule;
        $model = CorePrivacyPolicy::class;
        parent::__construct($module, $model);
    }
}
