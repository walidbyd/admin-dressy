<?php

namespace App\Policies;

use App\Config\ps_constant;
use App\Http\Contracts\Authorization\PermissionServiceInterface;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class PsPolicy
{
    use HandlesAuthorization;

    protected $model;

    protected $module;

    protected $createPermission;

    protected $readPermission;

    protected $updatePermission;

    protected $deletePermission;

    protected $loginUserIdPara;

    protected $permissionService;

    public function __construct($module, $model = null)
    {
        $this->loginUserIdPara = ps_constant::loginUserIdParaFromApi;
        $this->createPermission = ps_constant::createPermission;
        $this->readPermission = ps_constant::readPermission;
        $this->updatePermission = ps_constant::updatePermission;
        $this->deletePermission = ps_constant::deletePermission;
        $this->module = $module;
        $this->model = $model;
        $this->permissionService = app(PermissionServiceInterface::class);
    }

    public function viewAny(User $user)
    {
        $canCreate = $this->permissionService->permissionControl($this->module, $this->createPermission);
        $canUpdate = $this->permissionService->permissionControl($this->module, $this->updatePermission);
        $canDelete = $this->permissionService->permissionControl($this->module, $this->deletePermission);
        $Can = $this->permissionService->permissionControl($this->module, $this->readPermission);

        if ($Can) {
            return true;
        } elseif ($canCreate || $canUpdate || $canDelete) {
            return true;
        }
    }

    public function create(User $user)
    {
        $Can = $this->permissionService->permissionControl($this->module, $this->createPermission);

        if ($Can) {
            return true;
        }
    }

    public function update(User $user, $model = null)
    {
        $Can = $this->permissionService->permissionControl($this->module, $this->updatePermission);
        if ($Can) {
            return true;
        }
        if (! empty($this->model)) {
            $userId = getLoginUserId($this->loginUserIdPara, $user->id);

            return $userId == $model->added_user_id;
        } else {
            return false;
        }
    }

    public function delete(User $user, $model = null)
    {
        $Can = $this->permissionService->permissionControl($this->module, $this->deletePermission);
        if ($Can) {
            return true;
        }
        if (! empty($this->model)) {
            $userId = getLoginUserId($this->loginUserIdPara, $user->id);

            return $userId == $model->added_user_id;
        } else {
            return false;
        }
    }
}
