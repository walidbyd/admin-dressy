<?php

namespace Modules\Core\Policies;

use App\Policies\PsPolicy;
use Illuminate\Auth\Access\HandlesAuthorization;

class MenuGroupPolicy extends PsPolicy
{
    use HandlesAuthorization;

    /**
     * Create a new policy instance.
     *
     * @return void
     */
    public function __construct() {}
}
