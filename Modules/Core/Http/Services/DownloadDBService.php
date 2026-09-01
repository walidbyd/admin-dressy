<?php

namespace Modules\Core\Http\Services;

use App\Http\Services\PsService;
use Modules\Core\Constants\Constants;

class DownloadDBService extends PsService
{
    protected $menuGroupService;

    protected $superAdminRoleId;

    protected $normalUserRoleId;

    protected $imageService;

    protected $upload_path = 'storage/uploads/';

    protected $thumb1x_path = 'storage/thumbnail/';

    protected $thumb2x_path = 'storage/thumbnail2x/';

    protected $thumb3x_path = 'storage/thumbnail3x/';

    public function __construct(MenuGroupService $menuGroupService, ImageService $imageService)
    {
        $this->menuGroupService = $menuGroupService;
        $this->imageService = $imageService;

        $this->superAdminRoleId = Constants::superAdminRoleId;
        $this->normalUserRoleId = Constants::normalUserRoleId;
        $this->superAdminRoleId = Constants::superAdminRoleId;
        $this->normalUserRoleId = Constants::normalUserRoleId;
    }

    public function index()
    {
        $relation = ['sub_menu_group'];
        $menus = $this->menuGroupService->getMenuGroups($relation);
        $dataArr = [
            'menus' => $menus,
        ];

        return $dataArr;
    }
}
