<?php

namespace Modules\Core\Tests\Unit\Http\Services\Vendor;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Modules\Core\Entities\Vendor\VendorUserPermission;
use Modules\Core\Http\Services\Vendor\VendorUserPermissionService;
use Tests\TestCase;

class VendorUserPermissionServiceTest extends TestCase
{
    use DatabaseTransactions;

    protected $vendorUserPermissionService;

    protected function setup(): void
    {
        parent::setUp();

        $this->vendorUserPermissionService = new VendorUserPermissionService;
    }

    protected function tearDown(): void
    {
        parent::tearDown();
    }

    // region get
    // -------------------------------------------------------------------
    // get
    // -------------------------------------------------------------------

    public function test_get_with_valid_user_id_returns_vendor_user_permission()
    {
        $user = User::factory()->create();
        $vendorUserPermission = VendorUserPermission::factory()->create([
            VendorUserPermission::userId => $user->id,
        ]);

        $result = $this->vendorUserPermissionService->get($user->id);

        $this->assertEquals($vendorUserPermission->id, $result->id);
    }

    public function test_get_with_relation_loads_relation()
    {
        $user = User::factory()->create();
        $vendorUserPermission = VendorUserPermission::factory()->create([
            VendorUserPermission::userId => $user->id,
        ]);

        $result = $this->vendorUserPermissionService->get($user->id, ['owner']);

        $this->assertEquals($vendorUserPermission->id, $result->id);
        $this->assertTrue($result->relationLoaded('owner'));
    }

    public function test_get_with_invalid_user_id_returns_null()
    {
        $invalidUser = User::factory()->create();
        $user = User::factory()->create();
        VendorUserPermission::factory()->create([
            VendorUserPermission::userId => $user->id,
        ]);

        $result = $this->vendorUserPermissionService->get($invalidUser->id);

        $this->assertNull($result);
    }
    // endregion
}
