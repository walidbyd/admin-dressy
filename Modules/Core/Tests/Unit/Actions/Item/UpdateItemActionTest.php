<?php

namespace Modules\Core\Tests\Unit\Actions\Item;

use App\Exceptions\PsApiException;
use App\Http\Contracts\Authorization\PermissionServiceInterface;
use App\Http\Contracts\Configuration\BackendSettingServiceInterface;
use App\Http\Contracts\Configuration\SettingServiceInterface;
use App\Http\Contracts\Configuration\SystemConfigServiceInterface;
use App\Http\Contracts\Item\ItemInfoServiceInterface;
use App\Http\Contracts\User\UserInfoServiceInterface;
use App\Http\Contracts\User\UserServiceInterface;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\UploadedFile;
use Mockery;
use Modules\Core\Actions\Item\GenerateItemDeeplinkAction;
use Modules\Core\Actions\Item\UpdateItemAction;
use Modules\Core\DTOs\ItemDto;
use Modules\Core\Entities\Configuration\SystemConfig;
use Modules\Core\Entities\CoreImage;
use Modules\Core\Entities\Item\Item;
use Modules\Core\Http\Services\Image\ImageService;
use Modules\Core\Http\Services\Item\ItemService;
use Modules\Core\Http\Services\Utilities\VideoService;
use Modules\Core\Http\Services\Vendor\VendorService;
use Tests\TestCase;

class UpdateItemActionTest extends TestCase
{
    use DatabaseTransactions;

    protected $user;

    protected $itemService;

    protected $itemInfoService;

    protected $userInfoService;

    protected $systemConfigService;

    protected $userService;

    protected $permissionService;

    protected $backendSettingService;

    protected $settingService;

    protected $generateItemDeeplink;

    protected $imageService;

    protected $videoService;

    protected $vendorService;

    protected $updateItemAction;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create([User::roleId => '1']);

        $this->itemService = Mockery::mock(ItemService::class);
        $this->itemInfoService = Mockery::mock(ItemInfoServiceInterface::class);
        $this->userInfoService = Mockery::mock(UserInfoServiceInterface::class);
        $this->systemConfigService = Mockery::mock(SystemConfigServiceInterface::class);
        $this->userService = Mockery::mock(UserServiceInterface::class);
        $this->permissionService = Mockery::mock(PermissionServiceInterface::class);
        $this->backendSettingService = Mockery::mock(BackendSettingServiceInterface::class);
        $this->settingService = Mockery::mock(SettingServiceInterface::class);
        $this->generateItemDeeplink = Mockery::mock(GenerateItemDeeplinkAction::class);
        $this->imageService = Mockery::mock(ImageService::class);
        $this->videoService = Mockery::mock(VideoService::class);
        $this->vendorService = Mockery::mock(VendorService::class);

        $this->updateItemAction = new UpdateItemAction(
            $this->itemService,
            $this->itemInfoService,
            $this->userInfoService,
            $this->systemConfigService,
            $this->userService,
            $this->permissionService,
            $this->backendSettingService,
            $this->settingService,
            $this->generateItemDeeplink,
            $this->imageService,
            $this->videoService,
            $this->vendorService
        );
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    // region initSettings
    // -------------------------------------------------------------------
    // initSettings
    // -------------------------------------------------------------------

    public function test_init_settings()
    {
        $mockSystemConfig = ['system_key' => 'system_value'];
        $mockBackendSetting = ['backend_key' => 'backend_value'];

        $this->systemConfigService->shouldReceive('get')
            ->once()
            ->andReturn($mockSystemConfig);

        $this->backendSettingService->shouldReceive('get')
            ->once()
            ->andReturn($mockBackendSetting);

        $reflection = new \ReflectionClass($this->updateItemAction);
        $method = $reflection->getMethod('initSettings');
        $method->setAccessible(true);

        $result = $method->invoke($this->updateItemAction);

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

        $this->userInfoService->shouldReceive('get')
            ->andReturn(['id' => 1, 'user_id' => 1]);

        $this->userService->shouldReceive('get')
            ->andReturn(new User(['id' => 1]));

        $this->userInfoService->shouldReceive('get')
            ->andReturn(['id' => 2, 'user_id' => 1]);

        $reflection = new \ReflectionClass($this->updateItemAction);
        $method = $reflection->getMethod('prepareData');
        $method->setAccessible(true);

        $result = $method->invoke($this->updateItemAction, $itemDto);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('userRemainingPostInfo', $result);
        $this->assertArrayHasKey('user', $result);
        $this->assertArrayHasKey('blueMarkUserInfo', $result);
    }
    // endregion

    // region validate
    // -------------------------------------------------------------------
    // validate
    // -------------------------------------------------------------------

    public function test_validate_update_item_without_id_throws_exception()
    {
        $itemDto = $this->createItemDto();
        $mockSystemConfig = ['system_key' => 'system_value'];
        $mockBackendSetting = ['backend_key' => 'backend_value'];
        $mockBlueMarkUserInfo = ['value' => 'value'];

        $reflection = new \ReflectionClass($this->updateItemAction);
        $method = $reflection->getMethod('validate');
        $method->setAccessible(true);

        $this->expectException(PsApiException::class);
        $this->expectExceptionMessage(__('core__be_item_invalid'));
        $method->invoke($this->updateItemAction, $itemDto, $this->user, 5, $mockSystemConfig, $mockBackendSetting, $mockBlueMarkUserInfo);
    }

    public function test_validate_when_login_user_id_not_equals_added_user_id_throws_exception()
    {
        $itemDto = $this->createItemDto([
            'id' => 1,
            'loginUserId' => $this->user->{User::id},
            'addedUserId' => '99999999999',
        ]);
        $mockSystemConfig = ['system_key' => 'system_value'];
        $mockBackendSetting = ['backend_key' => 'backend_value'];
        $mockBlueMarkUserInfo = ['value' => 'value'];

        $reflection = new \ReflectionClass($this->updateItemAction);
        $method = $reflection->getMethod('validate');
        $method->setAccessible(true);
        $this->user->role_id = 999;

        $this->expectException(PsApiException::class);
        $this->expectExceptionMessage(__('core__api_update_no_permission'));
        $method->invoke($this->updateItemAction, $itemDto, $this->user, 5, $mockSystemConfig, $mockBackendSetting, $mockBlueMarkUserInfo);
    }

    public function test_validate_when_vendor_id_not_have_permission_throws_exception()
    {
        $itemDto = $this->createItemDto([
            'id' => 1,
            'loginUserId' => $this->user->{User::id},
            'addedUserId' => $this->user->{User::id},
            'vendorId' => 1,
        ]);
        $mockSystemConfig = ['system_key' => 'system_value'];
        $mockBackendSetting = ['backend_key' => 'backend_value'];
        $mockBlueMarkUserInfo = ['value' => 'value'];

        $reflection = new \ReflectionClass($this->updateItemAction);
        $method = $reflection->getMethod('validate');
        $method->setAccessible(true);

        $this->permissionService
            ->shouldReceive('vendorPermissionControl')
            ->withAnyArgs()
            ->andReturn(false);

        $this->expectException(PsApiException::class);
        $this->expectExceptionMessage(__('core__api_update_no_permission_for_vendor'));
        $method->invoke($this->updateItemAction, $itemDto, $this->user, 5, $mockSystemConfig, $mockBackendSetting, $mockBlueMarkUserInfo);
    }

    public function test_validate_when_vendor_id_not_have_currency_id_throws_exception()
    {
        $itemDto = $this->createItemDto([
            'id' => 1,
            'loginUserId' => $this->user->{User::id},
            'addedUserId' => $this->user->{User::id},
            'vendorId' => 1,
        ]);
        $mockSystemConfig = ['system_key' => 'system_value'];
        $mockBackendSetting = ['backend_key' => 'backend_value'];
        $mockBlueMarkUserInfo = ['value' => 'value'];

        $reflection = new \ReflectionClass($this->updateItemAction);
        $method = $reflection->getMethod('validate');
        $method->setAccessible(true);

        $this->permissionService
            ->shouldReceive('vendorPermissionControl')
            ->withAnyArgs()
            ->andReturn(true);

        $this->vendorService
            ->shouldReceive('get')
            ->withAnyArgs()
            ->andReturn((object) ['currency_id' => null]);

        $this->expectException(PsApiException::class);
        $this->expectExceptionMessage(__('core__api_vendor_currency_error'));
        $method->invoke($this->updateItemAction, $itemDto, $this->user, 5, $mockSystemConfig, $mockBackendSetting, $mockBlueMarkUserInfo);
    }

    public function test_validate_when_user_not_have_permission_throws_exception()
    {
        $itemDto = $this->createItemDto([
            'id' => 1,
            'loginUserId' => $this->user->{User::id},
            'addedUserId' => $this->user->{User::id},
            'vendorId' => 1,
        ]);
        $mockSystemConfig = ['system_key' => 'system_value'];
        $mockBackendSetting = (object) ['upload_setting' => 'backend_value'];
        $mockBlueMarkUserInfo = (object) ['value' => 'value'];

        $reflection = new \ReflectionClass($this->updateItemAction);
        $method = $reflection->getMethod('validate');
        $method->setAccessible(true);

        $this->permissionService
            ->shouldReceive('vendorPermissionControl')
            ->withAnyArgs()
            ->andReturn(true);

        $this->vendorService
            ->shouldReceive('get')
            ->withAnyArgs()
            ->andReturn((object) ['currency_id' => 1]);

        $this->userService
            ->shouldReceive('userHasUploadPermission')
            ->withAnyArgs()
            ->andReturn(false);

        $this->expectException(PsApiException::class);
        $this->expectExceptionMessage(__('core__api_item_upload_not_allow'));
        $method->invoke($this->updateItemAction, $itemDto, $this->user, 5, $mockSystemConfig, $mockBackendSetting, $mockBlueMarkUserInfo);
    }
    // endregion

    // region UpdateItem
    // -------------------------------------------------------------------
    // UpdateItem
    // -------------------------------------------------------------------

    public function test_update_item()
    {
        $this->actingAs($this->user);

        $item = Item::factory()->create();
        $video = UploadedFile::fake()->create('video.mp4');
        $videoIcon = UploadedFile::fake()->image('videoIcon.png', 10, 10);
        $customFields = [
            'itm00001' => 'Updated Value',
            'imt00002' => 'Inserted Value',
        ];
        $itemDto = $this->createItemDto([
            'id' => $item->{Item::id},
            'video' => $video,
            'videoIcon' => $videoIcon,
            'customFields' => $customFields,
            'categoryId' => $item->category->id,
        ]);
        $systemConfig = new SystemConfig;

        $reflection = new \ReflectionClass($this->updateItemAction);
        $method = $reflection->getMethod('UpdateItem');
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

        $this->itemService
            ->shouldReceive('updateV2')
            ->with($item->id, Mockery::type(ItemDto::class))
            ->andReturn($item);

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

        $this->generateItemDeeplink
            ->shouldReceive('handle')
            ->with($item->{Item::id})
            ->andReturn($item);

        $this->itemService
            ->shouldReceive('updateItemInfo')
            ->with($item->id, $customFields, $itemDto->categoryId)
            ->andReturnNull();

        $result = $method->invoke($this->updateItemAction, $itemDto, $systemConfig);

        $this->assertEquals($item->{Item::id}, $result->{Item::id});
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
