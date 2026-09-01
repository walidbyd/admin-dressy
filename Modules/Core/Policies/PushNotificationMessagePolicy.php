<?php

namespace Modules\Core\Policies;

use App\Policies\PsPolicy;
use Illuminate\Auth\Access\HandlesAuthorization;
use Modules\Core\Constants\Constants;
use Modules\Core\Entities\Notification\PushNotificationMessage;

class PushNotificationMessagePolicy extends PsPolicy
{
    use HandlesAuthorization;

    /**
     * Create a new policy instance.
     *
     * @return void
     */
    public function __construct()
    {
        $module = Constants::pushNotificationModule;
        $model = PushNotificationMessage::class;
        parent::__construct($module, $model);
    }
}
