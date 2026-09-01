<?php

namespace Modules\Core\Tests\Unit\Http\Services\User;

use App\Http\Contracts\Authorization\UserPermissionServiceInterface;
use App\Http\Contracts\Configuration\SystemConfigServiceInterface;
use App\Http\Contracts\Image\ImageProcessingServiceInterface;
use App\Http\Contracts\Image\ImageServiceInterface;
use App\Http\Contracts\User\UserInfoServiceInterface;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Mockery;
use Modules\Core\Http\Services\User\UserService;
use Tests\TestCase;

class UserServiceTest extends TestCase
{
    use DatabaseTransactions;

    protected $imageService;

    protected $imageProcessingService;

    protected $userInfoService;

    protected $userPermissionService;

    protected $systemConfigService;

    protected $userService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->imageService = Mockery::mock(ImageServiceInterface::class);
        $this->imageProcessingService = Mockery::mock(ImageProcessingServiceInterface::class);
        $this->userInfoService = Mockery::mock(UserInfoServiceInterface::class);
        $this->userPermissionService = Mockery::mock(UserPermissionServiceInterface::class);
        $this->systemConfigService = Mockery::mock(SystemConfigServiceInterface::class);

        // Instantiate the real service like UserInfoServiceTest does
        $this->userService = new UserService(
            $this->imageService,
            $this->imageProcessingService,
            $this->userInfoService,
            $this->userPermissionService,
            $this->systemConfigService
        );

        // User::factory()->count(5)->create();

    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    // region userHasUploadPermission

    public function test_user_has_upload_permission_default()
    {
        $result = $this->userService->userHasUploadPermission(null, null, null);
        $this->assertTrue($result);

        $result = $this->userService->userHasUploadPermission('wrong-setting', null, null);
        $this->assertTrue($result);
    }

    public function test_user_has_upload_permission_admin_only_setting()
    {
        $result = $this->userService->userHasUploadPermission('admin', null, null);
        $this->assertFalse($result);

        $result = $this->userService->userHasUploadPermission('admin', 1, null);
        $this->assertTrue($result);

        $result = $this->userService->userHasUploadPermission('admin', 0, null);
        $this->assertFalse($result);

        $result = $this->userService->userHasUploadPermission('admin', 0, 1);
        $this->assertFalse($result);

        $result = $this->userService->userHasUploadPermission('admin', 0, 0);
        $this->assertFalse($result);
    }

    public function test_user_has_upload_permission_admin_and_blue_mark_setting()
    {
        $result = $this->userService->userHasUploadPermission('admin-bluemark', null, null);
        $this->assertFalse($result);

        $result = $this->userService->userHasUploadPermission('admin-bluemark', 1, null);
        $this->assertTrue($result);

        $result = $this->userService->userHasUploadPermission('admin-bluemark', 0, null);
        $this->assertFalse($result);

        $result = $this->userService->userHasUploadPermission('admin-bluemark', null, 1);
        $this->assertTrue($result);

        $result = $this->userService->userHasUploadPermission('admin-bluemark', null, 0);
        $this->assertFalse($result);

        $result = $this->userService->userHasUploadPermission('admin-bluemark', 1, 1);
        $this->assertTrue($result);

        $result = $this->userService->userHasUploadPermission('admin-bluemark', 1, 0);
        $this->assertTrue($result);

        $result = $this->userService->userHasUploadPermission('admin-bluemark', 0, 1);
        $this->assertTrue($result);

        $result = $this->userService->userHasUploadPermission('admin-bluemark', 0, 0);
        $this->assertFalse($result);
    }

    public function test_user_has_upload_permission_vendor_only_setting()
    {
        $result = $this->userService->userHasUploadPermission('vendor-only', 0, 0);
        $this->assertFalse($result);

        $result = $this->userService->userHasUploadPermission('vendor-only', 0, 0, 1);
        $this->assertTrue($result);
    }
    // endregion

    // region get

    public function test_get()
    {

        $user = User::factory()->create([
            'name' => 'Test Name',
            'added_user_id' => 1,
        ]);

        // Get with Id
        $idResult = $this->userService->get($user->id);
        $this->assertEquals($user->id, $idResult->id);

        // Get with conds
        $condResult = $this->userService->get(conds: ['name' => 'Test Name']);
        $this->assertEquals($user->name, $condResult->name);

        // Get with relation
        $relationResult = $this->userService->get($user->id, relation: ['owner']);
        $this->assertTrue($relationResult->relationLoaded('owner'));

        // Get with matching parameters
        $allResult = $this->userService->get($user->id, ['name' => 'Test Name'], 'owner');
        $this->assertEquals($user->id, $allResult->id);
        $this->assertEquals($user->name, $allResult->name);
        $this->assertTrue($allResult->relationLoaded('owner'));

        // Get with non matching parameters
        $allResult = $this->userService->get($user->id, ['name' => 'Non Existing Name'], 'owner');
        $this->assertNull($allResult);

    }
    // endregion
}
