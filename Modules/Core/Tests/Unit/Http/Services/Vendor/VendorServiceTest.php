<?php

namespace Modules\Core\Tests\Unit\Http\Services\Vendor;

use App\Http\Contracts\Configuration\MobileSettingServiceInterface;
use App\Http\Contracts\Image\ImageServiceInterface;
use App\Http\Contracts\Utilities\CoreFieldServiceInterface;
use App\Http\Contracts\Utilities\CustomFieldAttributeServiceInterface;
use App\Http\Contracts\Utilities\CustomFieldServiceInterface;
use App\Http\Contracts\Vendor\VendorApplicationServiceInterface;
use App\Http\Contracts\Vendor\VendorBranchServiceInterface;
use App\Http\Contracts\Vendor\VendorInfoServiceInterface;
use App\Http\Contracts\Vendor\VendorRoleServiceInterface;
use App\Http\Contracts\Vendor\VendorUserPermissionServiceInterface;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Mockery;
use Modules\Core\Entities\Vendor\Vendor;
use Modules\Core\Http\Services\User\UserService;
use Modules\Core\Http\Services\Vendor\VendorService;
use Tests\TestCase;

class VendorServiceTest extends TestCase
{
    use DatabaseTransactions;

    protected $imageService;

    protected $userService;

    protected $mobileSettingService;

    protected $customizeUiService;

    protected $customizeUiDetailService;

    protected $coreFieldService;

    protected $vendorBranchService;

    protected $vendorInfoService;

    protected $vendorApplicationService;

    protected $vendorUserPermissionService;

    protected $vendorRoleService;

    protected $vendorService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->imageService = Mockery::mock(ImageServiceInterface::class);
        $this->userService = Mockery::mock(UserService::class);
        $this->mobileSettingService = Mockery::mock(MobileSettingServiceInterface::class);
        $this->customizeUiService = Mockery::mock(CustomFieldServiceInterface::class);
        $this->customizeUiDetailService = Mockery::mock(CustomFieldAttributeServiceInterface::class);
        $this->coreFieldService = Mockery::mock(CoreFieldServiceInterface::class);
        $this->vendorBranchService = Mockery::mock(VendorBranchServiceInterface::class);
        $this->vendorInfoService = Mockery::mock(VendorInfoServiceInterface::class);
        $this->vendorApplicationService = Mockery::mock(VendorApplicationServiceInterface::class);
        $this->vendorUserPermissionService = Mockery::mock(VendorUserPermissionServiceInterface::class);
        $this->vendorRoleService = Mockery::mock(VendorRoleServiceInterface::class);

        $this->vendorService = new VendorService(
            $this->imageService,
            $this->userService,
            $this->mobileSettingService,
            $this->customizeUiService,
            $this->customizeUiDetailService,
            $this->coreFieldService,
            $this->vendorBranchService,
            $this->vendorInfoService,
            $this->vendorApplicationService,
            $this->vendorUserPermissionService,
            $this->vendorRoleService
        );
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    // region get
    // -------------------------------------------------------------------
    // get
    // -------------------------------------------------------------------

    public function test_get_with_invalid_id_returns_null()
    {
        Vendor::factory()->count(5)->create();

        $result = $this->vendorService->get(99999);
        $this->assertNull($result);
    }

    public function test_get_with_id_returns_vendor()
    {
        $vendor = Vendor::factory()->create();
        Vendor::factory()->count(4)->create();

        $result = $this->vendorService->get($vendor->{Vendor::id});
        $this->assertEquals($vendor->{Vendor::id}, $result->{Vendor::id});
    }

    public function test_get_with_id_and_relation_loads_relation()
    {
        $vendor = Vendor::factory()->create();
        Vendor::factory()->count(4)->create();

        $result = $this->vendorService->get($vendor->{Vendor::id}, ['owner']);
        $this->assertNotNull($result);
        $this->assertInstanceOf(Vendor::class, $result);
        $this->assertTrue($result->relationLoaded('owner'));
        $this->assertEquals($vendor->{Vendor::ownerUserId}, $result->owner->id);
    }
    // endregion
}
