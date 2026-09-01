<?php

namespace Modules\Core\Tests\Unit\Http\Services\Authorization;

use App\Config\ps_constant;
use App\Http\Contracts\Authorization\RolePermissionServiceInterface;
use App\Http\Contracts\Authorization\UserPermissionServiceInterface;
use App\Http\Contracts\Vendor\VendorRoleServiceInterface;
use App\Http\Contracts\Vendor\VendorUserPermissionServiceInterface;
use Mockery;
use Modules\Core\Constants\Constants;
use Modules\Core\Http\Services\Authorization\PermissionService;
use Tests\TestCase;

class PermissionServiceTest extends TestCase
{
    protected $userPermissionService;

    protected $rolePermissionService;

    protected $vendorUserPermissionService;

    protected $vendorRoleService;

    protected $permissionService;

    protected function setup(): void
    {
        parent::setUp();

        $this->userPermissionService = Mockery::mock(UserPermissionServiceInterface::class);
        $this->rolePermissionService = Mockery::mock(RolePermissionServiceInterface::class);
        $this->vendorUserPermissionService = Mockery::mock(VendorUserPermissionServiceInterface::class);
        $this->vendorRoleService = Mockery::mock(VendorRoleServiceInterface::class);

        $this->permissionService = new PermissionService(
            $this->userPermissionService,
            $this->rolePermissionService,
            $this->vendorUserPermissionService,
            $this->vendorRoleService
        );
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_vendor_permission_control_returns_false_when_user_does_not_have_vendor_permission()
    {
        $this->vendorUserPermissionService->shouldReceive('get')->with(1)->andReturnNull();

        $result = $this->permissionService->vendorPermissionControl(Constants::vendorItemModule, ps_constant::createPermission, 1, 1);
        $this->assertFalse($result);
    }

    public function test_vendor_permission_control_returns_flase_when_vendor_id_is_not_set()
    {
        $this->vendorUserPermissionService->shouldReceive('get')->with(1)->andReturn((object) ['id' => 1, 'vendor_and_role' => json_encode(['key' => 'value'])]);

        $result = $this->permissionService->vendorPermissionControl(Constants::vendorItemModule, ps_constant::createPermission, 1, 1);
        $this->assertFalse($result);
    }

    // Failed due to json contain operation
    public function test_vendor_permission_control_returns_true_when_user_has_row_permission()
    {
        $this->vendorUserPermissionService->shouldReceive('get')->with(1)->andReturn((object) ['id' => 1, 'vendor_and_role' => json_encode(['1' => '1', '2' => '3'])]);

        $this->vendorRoleService->shouldReceive('getAll')->with(null, null, Constants::yes, null, null, [0 => '1'], Constants::publish)->andReturn(collect([['id' => 1], ['id' => 2]]));

        $result = $this->permissionService->vendorPermissionControl(Constants::vendorItemModule, ps_constant::createPermission, 1, 1);
        $this->assertTrue($result);
    }
}
