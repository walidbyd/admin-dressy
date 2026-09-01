<?php

namespace Modules\Core\Tests\Unit\Actions\Item;

use App\Config\ps_constant;
use App\Exceptions\PsApiException;
use App\Http\Contracts\Authorization\PermissionServiceInterface;
use App\Http\Contracts\Configuration\BackendSettingServiceInterface;
use App\Http\Contracts\Configuration\SettingServiceInterface;
use App\Http\Contracts\Configuration\SystemConfigServiceInterface;
use App\Http\Contracts\Item\ItemInfoServiceInterface;
use App\Http\Contracts\User\UserInfoServiceInterface;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\UploadedFile;
use Mockery;
use Modules\Core\Actions\Item\CreateItemAction;
use Modules\Core\Actions\Item\GenerateItemDeeplinkAction;
use Modules\Core\Constants\Constants;
use Modules\Core\DTOs\ItemDto;
use Modules\Core\Entities\Configuration\SystemConfig;
use Modules\Core\Entities\CoreImage;
use Modules\Core\Entities\Item\Item;
use Modules\Core\Http\Services\Image\ImageService;
use Modules\Core\Http\Services\Item\ItemService;
use Modules\Core\Http\Services\Item\PackageService;
use Modules\Core\Http\Services\User\UserService;
use Modules\Core\Http\Services\Utilities\VideoService;
use Tests\TestCase;

class CreateItemActionTest extends TestCase
{
    use DatabaseTransactions;

    protected $itemService;

    protected $itemInfoService;

    protected $userInfoService;

    protected $systemConfigService;

    protected $userService;

    protected $permissionService;

    protected $backendSettingService;

    protected $settingService;

    protected $generateItemDeeplinkAction;

    protected $packageService;

    protected $imageService;

    protected $videoService;

    protected $createItemAction;

    protected $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create([User::roleId => '1']);

        $this->itemService = Mockery::mock(ItemService::class);
        $this->itemInfoService = Mockery::mock(ItemInfoServiceInterface::class);
        $this->userInfoService = Mockery::mock(UserInfoServiceInterface::class);
        $this->systemConfigService = Mockery::mock(SystemConfigServiceInterface::class);
        $this->userService = Mockery::mock(UserService::class);
        $this->permissionService = Mockery::mock(PermissionServiceInterface::class);
        $this->backendSettingService = Mockery::mock(BackendSettingServiceInterface::class);
        $this->settingService = Mockery::mock(SettingServiceInterface::class);
        $this->generateItemDeeplinkAction = Mockery::mock(GenerateItemDeeplinkAction::class);
        $this->packageService = Mockery::mock(PackageService::class);
        $this->imageService = Mockery::mock(ImageService::class);
        $this->videoService = Mockery::mock(VideoService::class);

        $this->createItemAction = new CreateItemAction(
            $this->itemService,
            $this->itemInfoService,
            $this->userInfoService,
            $this->systemConfigService,
            $this->userService,
            $this->permissionService,
            $this->backendSettingService,
            $this->settingService,
            $this->generateItemDeeplinkAction,
            $this->packageService,
            $this->imageService,
            $this->videoService
        );
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    // region validate
    // -------------------------------------------------------------------
    // validate
    // -------------------------------------------------------------------

    public function test_validate_create_item_with_id_throws_exception()
    {
        $itemDto = $this->createItemDto(['id' => 1]);

        // Use reflection to test private method
        $reflection = new \ReflectionClass($this->createItemAction);
        $method = $reflection->getMethod('validate');
        $method->setAccessible(true);

        $this->expectException(PsApiException::class);
        $method->invoke($this->createItemAction, $itemDto, ['id' => 1], ['remaining_post' => 1], ['system_config' => '1'], ['backend_setting' => 1], ['bluemark_user_info' => 1]);
    }

    public function test_validate_without_vendor_permission_throws_exception()
    {
        $itemDto = $this->createItemDto(['vendorId' => 1]);

        // Use reflection to test private method
        $reflection = new \ReflectionClass($this->createItemAction);
        $method = $reflection->getMethod('validate');
        $method->setAccessible(true);

        $this->permissionService->shouldReceive('vendorPermissionControl')
            ->with(Constants::vendorItemModule, ps_constant::createPermission, $itemDto->vendorId, $itemDto->loginUserId)
            ->andReturn(false);

        $this->expectException(PsApiException::class);
        $method->invoke($this->createItemAction, $itemDto, ['id' => 1], ['remaining_post' => 1], ['system_config' => '1'], ['backend_setting' => 1], ['bluemark_user_info' => 1]);
    }

    public function test_validate_without_user_permission_throws_exception()
    {
        $itemDto = $this->createItemDto();

        // Use reflection to test private method
        $reflection = new \ReflectionClass($this->createItemAction);
        $method = $reflection->getMethod('validate');
        $method->setAccessible(true);

        $this->userService->shouldReceive('userHasUploadPermission')
            ->with(1, 1, 1, null)
            ->andReturn(false);

        $this->expectException(PsApiException::class);
        $method->invoke($this->createItemAction, $itemDto, (object) ['id' => 1, 'role_id' => 1], ['remaining_post' => 1], ['system_config' => '1'], (object) ['upload_setting' => 1], (object) ['value' => 1]);
    }

    public function test_validate_without_sufficient_balance_throws_exception()
    {
        $itemDto = $this->createItemDto();
        $user = (object) ['id' => 1, 'role_id' => 1];

        // Use reflection to test private method
        $reflection = new \ReflectionClass($this->createItemAction);
        $method = $reflection->getMethod('validate');
        $method->setAccessible(true);

        $this->userService->shouldReceive('userHasUploadPermission')
            ->with(1, 1, 1, null)
            ->andReturn(true);

        $this->packageService->shouldReceive('isPaidItemUploadSettingEnabled')
            ->with(['system_config' => '1'])
            ->andReturn(true);

        $this->packageService->shouldReceive('hasSufficientBalance')
            ->with(['remaining_post' => 1], $user)
            ->andReturn(false);

        $this->expectException(PsApiException::class);
        $method->invoke($this->createItemAction, $itemDto, $user, ['remaining_post' => 1], ['system_config' => '1'], (object) ['upload_setting' => 1], (object) ['value' => 1]);
    }

    public function test_validate_returns_nothing_when_everything_pass()
    {
        $itemDto = $this->createItemDto();
        $user = (object) ['id' => 1, 'role_id' => 1];

        // Use reflection to test private method
        $reflection = new \ReflectionClass($this->createItemAction);
        $method = $reflection->getMethod('validate');
        $method->setAccessible(true);

        $this->userService->shouldReceive('userHasUploadPermission')
            ->with(1, 1, 1, null)
            ->andReturn(true);

        $this->packageService->shouldReceive('isPaidItemUploadSettingEnabled')
            ->with(['system_config' => '1'])
            ->andReturn(true);

        $this->packageService->shouldReceive('hasSufficientBalance')
            ->with(['remaining_post' => 1], $user)
            ->andReturn(true);

        $result = $method->invoke($this->createItemAction, $itemDto, $user, ['remaining_post' => 1], ['system_config' => '1'], (object) ['upload_setting' => 1], (object) ['value' => 1]);
        $this->assertNull($result);
    }
    // endregion

    // region createItem
    // -------------------------------------------------------------------
    // createItem
    // -------------------------------------------------------------------

    public function test_create_new_item()
    {
        $this->actingAs($this->user);
        $video = UploadedFile::fake()->create('video.mp4');
        $videoIcon = UploadedFile::fake()->image('videoIcon.png', 10, 10);
        $itemDto = $this->createItemDto([
            'video' => $video,
            'videoIcon' => $videoIcon,
        ]);
        $systemConfig = new SystemConfig;

        // Use reflection to test private method
        $reflection = new \ReflectionClass($this->createItemAction);
        $method = $reflection->getMethod('createNewItem');
        $method->setAccessible(true);

        $this->itemService->shouldReceive('prepareStatusData')
            ->with($itemDto->status, $systemConfig)
            ->andReturn(1);

        $this->itemService->shouldReceive('prepareCurrencyIdData')
            ->with($itemDto->currencyId)
            ->andReturn(1);

        $this->itemService->shouldReceive('preparePercentData')
            ->with($itemDto->percent)
            ->andReturn(0);

        $this->itemService->shouldReceive('prepareisDiscountData')
            ->with($itemDto->percent)
            ->andReturn(0);

        $this->itemService->shouldReceive('prepareOriginalPriceData')
            ->with($itemDto->originalPrice)
            ->andReturn($itemDto->originalPrice);

        $this->itemService->shouldReceive('preparePriceData')
            ->with($itemDto->originalPrice, $itemDto->percent, $itemDto->price)
            ->andReturn($itemDto->price);

        $this->itemService->shouldReceive('prepareIsPaidData')
            ->with($itemDto->vendorId)
            ->andReturn(0);

        $this->itemService->shouldReceive('prepareVendorIdData')
            ->with($itemDto->vendorId)
            ->andReturnNull();

        $item = new Item([
            Item::id => 1,
        ]);
        $this->itemService->shouldReceive('create')
            ->with(Mockery::type(ItemDto::class))
            ->andReturn($item);

        $this->imageService->shouldReceive('saveDropzoneMultiImage')
            ->with([], $item->id, Mockery::type(ItemDto::class))
            ->andReturnNull();

        $this->imageService
            ->shouldReceive('save')
            ->with($videoIcon, [
                CoreImage::imgParentId => $item->{Item::id},
                CoreImage::imgType => 'item-video-icon',
            ])
            ->andReturnNull();

        $this->videoService
            ->shouldReceive('saveVideo')
            ->with(
                $video,
                'mp4',
                [
                    CoreImage::imgParentId => $item->{Item::id},
                    CoreImage::imgType => 'item-video',
                ],
                $itemDto->loginUserId
            )
            ->andReturnNull();

        $this->generateItemDeeplinkAction
            ->shouldReceive('handle')
            ->andReturn($item);

        $this->itemInfoService
            ->shouldReceive('save')
            ->andReturnNull();

        $result = $method->invoke($this->createItemAction, $itemDto, $systemConfig);

        $this->assertEquals($item->{Item::id}, $result->{Item::id});
    }
    // endregion

    // region initSettings
    // -------------------------------------------------------------------
    // initSettings
    // -------------------------------------------------------------------

    public function test_init_settings()
    {
        // Create mock return values
        $mockSystemConfig = ['system_key' => 'system_value'];
        $mockBackendSetting = ['backend_key' => 'backend_value'];

        // Set expectations for service calls
        $this->systemConfigService->shouldReceive('get')
            ->once()
            ->andReturn($mockSystemConfig);

        $this->backendSettingService->shouldReceive('get')
            ->once()
            ->andReturn($mockBackendSetting);

        // Use reflection to test private method
        $reflection = new \ReflectionClass($this->createItemAction);
        $method = $reflection->getMethod('initSettings');
        $method->setAccessible(true);

        // Execute the method
        $result = $method->invoke($this->createItemAction);

        // Verify the results
        $this->assertIsArray($result);
        $this->assertArrayHasKey('systemConfig', $result);
        $this->assertArrayHasKey('backendSetting', $result);
        $this->assertEquals($mockSystemConfig, $result['systemConfig']);
        $this->assertEquals($mockBackendSetting, $result['backendSetting']);
    }
    // endregion

    // region prepareData
    // -------------------------------------------------------------------
    // prepareData
    // -------------------------------------------------------------------

    public function test_prepare_data()
    {
        $itemDto = $this->createItemDto();

        // Mock the services to return simple arrays (we don't care about types)
        $this->userInfoService->shouldReceive('get')
            ->andReturn(['id' => 1, 'user_id' => 1]);

        $this->userService->shouldReceive('get')
            ->andReturn(new User(['id' => 1]));

        $this->userInfoService->shouldReceive('get')
            ->andReturn(['id' => 2, 'user_id' => 1]);

        // Use reflection to test private method
        $reflection = new \ReflectionClass($this->createItemAction);
        $method = $reflection->getMethod('prepareData');
        $method->setAccessible(true);

        $result = $method->invoke($this->createItemAction, $itemDto);

        // Assertions
        $this->assertIsArray($result);
        $this->assertArrayHasKey('userRemainingPostInfo', $result);
        $this->assertArrayHasKey('user', $result);
        $this->assertArrayHasKey('blueMarkUserInfo', $result);
    }
    // endregion

    // region prepareSaveVideoData
    // -------------------------------------------------------------------
    // prepareSaveVideoData
    // -------------------------------------------------------------------

    public function test_prepare_save_video_data()
    {
        $reflection = new \ReflectionClass($this->createItemAction);
        $method = $reflection->getMethod('prepareSaveVideoData');
        $method->setAccessible(true);

        $result = $method->invoke($this->createItemAction, 1, 'item-video');

        // Assertions
        $this->assertIsArray($result);
        $this->assertArrayHasKey(CoreImage::imgParentId, $result);
        $this->assertArrayHasKey(CoreImage::imgType, $result);
        $this->assertEquals(1, $result[CoreImage::imgParentId]);
        $this->assertEquals('item-video', $result[CoreImage::imgType]);
    }
    // endregion

    // region prepareSaveImageData
    // -------------------------------------------------------------------
    // prepareSaveImageData
    // -------------------------------------------------------------------

    public function test_prepare_save_image_data()
    {
        $reflection = new \ReflectionClass($this->createItemAction);
        $method = $reflection->getMethod('prepareSaveImageData');
        $method->setAccessible(true);

        $result = $method->invoke($this->createItemAction, 1, 'item-video-icon', 'description', 1);

        // Assertions
        $this->assertIsArray($result);
        $this->assertArrayHasKey(CoreImage::imgParentId, $result);
        $this->assertArrayHasKey(CoreImage::imgType, $result);
        $this->assertArrayHasKey(CoreImage::imgDesc, $result);
        $this->assertArrayHasKey(CoreImage::ordering, $result);
        $this->assertEquals(1, $result[CoreImage::imgParentId]);
        $this->assertEquals('item-video-icon', $result[CoreImage::imgType]);
        $this->assertEquals('description', $result[CoreImage::imgDesc]);
        $this->assertEquals(1, $result[CoreImage::ordering]);
    }
    // endregion

    private function createItemDto(array $overrides = [])
    {
        $defaults = [
            'id' => null,
            'title' => 'Test Title',
            'categoryId' => 1,
            'subcategoryId' => null,
            'currencyId' => null,
            'locationCityId' => 1,
            'locationTownshipId' => null,
            'shopId' => null,
            'price' => 100.50,
            'originalPrice' => null,
            'description' => null,
            'searchTag' => null,
            'dynamicLink' => null,
            'lat' => null,
            'lng' => null,
            'status' => null,
            'isPaid' => null,
            'isSoldOut' => 1,
            'ordering' => null,
            'isAvailable' => 1,
            'isDiscount' => 1,
            'itemTouchCount' => 1,
            'favouriteCount' => 1,
            'overallRating' => 0,
            'vendorId' => null,
            'addedUserId' => null,
            'updatedUserId' => null,
            'percent' => null,
            'phone' => null,
            'imgOrder' => null,
            'imgCaption' => null,
            'loginUserId' => 1,
            'languageSymbol' => 'en',
            'customFields' => [],
            'images' => [],
        ];

        $data = array_merge($defaults, $overrides);

        return new ItemDto(
            id: $data['id'],
            title: $data['title'],
            categoryId: $data['categoryId'],
            subcategoryId: $data['subcategoryId'],
            currencyId: $data['currencyId'],
            locationCityId: $data['locationCityId'],
            locationTownshipId: $data['locationTownshipId'],
            shopId: $data['shopId'],
            price: $data['price'],
            originalPrice: $data['originalPrice'],
            description: $data['description'],
            searchTag: $data['searchTag'],
            dynamicLink: $data['dynamicLink'],
            lat: $data['lat'],
            lng: $data['lng'],
            status: $data['status'],
            isPaid: $data['isPaid'],
            isSoldOut: $data['isSoldOut'],
            ordering: $data['ordering'],
            isAvailable: $data['isAvailable'],
            isDiscount: $data['isDiscount'],
            itemTouchCount: $data['itemTouchCount'],
            favouriteCount: $data['favouriteCount'],
            overallRating: $data['overallRating'],
            vendorId: $data['vendorId'],
            addedUserId: $data['addedUserId'],
            updatedUserId: $data['updatedUserId'],
            percent: $data['percent'],
            phone: $data['phone'],
            imgOrder: $data['imgOrder'],
            imgCaption: $data['imgCaption'],
            loginUserId: $data['loginUserId'],
            languageSymbol: $data['languageSymbol'],
            customFields: $data['customFields'],
            images: $data['images'],
            videoIcon: $data['videoIcon'] ?? null,
            video: $data['video'] ?? null
        );
    }
}
