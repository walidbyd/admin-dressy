<?php

namespace Modules\Core\Http\Tests\Unit\Http\Services\Item;

use App\Config\Cache\CategoryCache;
use App\Config\Cache\ItemCache;
use App\Config\Cache\VendorCache;
use App\Http\Contracts\Authorization\PushNotificationTokenServiceInterface;
use App\Http\Contracts\Category\CategoryServiceInterface;
use App\Http\Contracts\Category\SubcategoryServiceInterface;
use App\Http\Contracts\Configuration\BackendSettingServiceInterface;
use App\Http\Contracts\Configuration\SettingServiceInterface;
use App\Http\Contracts\Configuration\SystemConfigServiceInterface;
use App\Http\Contracts\Financial\ItemCurrencyServiceInterface;
use App\Http\Contracts\Image\ImageServiceInterface;
use App\Http\Contracts\Item\CartItemServiceInterface;
use App\Http\Contracts\Item\ItemInfoServiceInterface;
use App\Http\Contracts\Notification\FirebaseCloudMessagingServiceInterface;
use App\Http\Contracts\User\UserInfoServiceInterface;
use App\Http\Contracts\User\UserServiceInterface;
use App\Http\Contracts\Utilities\ChunkUpdateServiceInterface;
use App\Http\Contracts\Utilities\CoreFieldServiceInterface;
use App\Http\Contracts\Utilities\CustomFieldServiceInterface;
use App\Http\Contracts\Utilities\DynamicLinkServiceInterface;
use App\Models\User;
use Exception;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\UploadedFile;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use InvalidArgumentException;
use Mockery;
use Modules\Core\Constants\Constants;
use Modules\Core\Constants\ItemStatus;
use Modules\Core\DTOs\ItemDto;
use Modules\Core\Entities\Category\Category;
use Modules\Core\Entities\Category\Subcategory;
use Modules\Core\Entities\Financial\ItemCurrency;
use Modules\Core\Entities\Item\Item;
use Modules\Core\Entities\Item\PaidItemHistory;
use Modules\Core\Entities\ItemInfo;
use Modules\Core\Entities\Location\LocationCity;
use Modules\Core\Entities\Location\LocationTownship;
use Modules\Core\Entities\User\UserBought;
use Modules\Core\Entities\Utilities\CustomField;
use Modules\Core\Entities\Utilities\CustomFieldAttribute;
use Modules\Core\Http\Facades\PsCache;
use Modules\Core\Http\Services\Item\ItemService;
use Modules\Core\Http\Services\SubCatSubscribeService;
use Tests\TestCase;

class ItemServiceTest extends TestCase
{
    use DatabaseTransactions;

    protected $user;

    private $itemService;

    private $backendSettingService;

    private $imageService;

    private $itemInfoService;

    private $categoryService;

    private $cartItemService;

    private $customFieldService;

    private $coreFieldService;

    private $userService;

    private $pushNotificationTokenService;

    private $firebaseCloudMessagingService;

    private $subcategoryService;

    private $userInfoService;

    private $subCatSubscribeService;

    private $itemCurrencyService;

    private $systemConfigService;

    private $settingService;

    private $dynamicLinkService;

    private $chunkUpdateService;

    protected function setUp(): void
    {
        parent::setUp();
        // Additional setup if needed
        // $this->itemService = app(ItemService::class);
        $this->backendSettingService = Mockery::mock(BackendSettingServiceInterface::class);
        $this->imageService = Mockery::mock(ImageServiceInterface::class);
        $this->itemInfoService = Mockery::mock(ItemInfoServiceInterface::class);
        $this->categoryService = Mockery::mock(CategoryServiceInterface::class);
        $this->cartItemService = Mockery::mock(CartItemServiceInterface::class);
        $this->customFieldService = Mockery::mock(CustomFieldServiceInterface::class);
        $this->coreFieldService = Mockery::mock(CoreFieldServiceInterface::class);
        $this->userService = Mockery::mock(UserServiceInterface::class);
        $this->pushNotificationTokenService = Mockery::mock(PushNotificationTokenServiceInterface::class);
        $this->firebaseCloudMessagingService = Mockery::mock(FirebaseCloudMessagingServiceInterface::class);
        $this->subcategoryService = Mockery::mock(SubcategoryServiceInterface::class);
        $this->userInfoService = Mockery::mock(UserInfoServiceInterface::class);
        $this->subCatSubscribeService = Mockery::mock(SubCatSubscribeService::class);
        $this->itemCurrencyService = Mockery::mock(ItemCurrencyServiceInterface::class);
        $this->systemConfigService = Mockery::mock(SystemConfigServiceInterface::class);
        $this->settingService = Mockery::mock(SettingServiceInterface::class);
        $this->dynamicLinkService = Mockery::mock(DynamicLinkServiceInterface::class);
        $this->chunkUpdateService = Mockery::mock(ChunkUpdateServiceInterface::class);
        $this->itemService = Mockery::mock(ItemService::class, [
            $this->backendSettingService,
            $this->imageService,
            $this->itemInfoService,
            $this->categoryService,
            $this->cartItemService,
            $this->customFieldService,
            $this->coreFieldService,
            $this->userService,
            $this->pushNotificationTokenService,
            $this->firebaseCloudMessagingService,
            $this->subcategoryService,
            $this->userInfoService,
            $this->subCatSubscribeService,
            $this->itemCurrencyService,
            $this->systemConfigService,
            $this->settingService,
            $this->dynamicLinkService,
            $this->chunkUpdateService,
            $this->itemService,
        ])->makePartial();

        $this->user = User::factory()->create([User::roleId => '1']);
        $this->actingAs($this->user);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    // region preparePercentData
    // -------------------------------------------------------------------
    // preparePercentData
    // -------------------------------------------------------------------
    public function test_prepare_percent_data_with_zero()
    {
        $result = $this->itemService->preparePercentData(0);
        $this->assertEquals(0.00, $result, 'Expected percent data to be formatted as "0.00"');
    }

    public function test_prepare_percent_data_with_normal_number()
    {
        $result = $this->itemService->preparePercentData(10);
        $this->assertEquals(10.00, $result, 'Expected percent data to be formatted as "10.00"');
    }

    public function test_prepare_percent_data_with_minus_number()
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Percent must be between 0 and 100.');
        $result = $this->itemService->preparePercentData(-1);
    }

    public function test_prepare_percent_data_with_big_number()
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Percent must be between 0 and 100.');
        $result = $this->itemService->preparePercentData(500000000);
    }

    public function test_prepare_percent_data_with_normal_upper_limit()
    {
        $result = $this->itemService->preparePercentData(100);
        $this->assertEquals(100.00, $result, 'Expected percent data to be formatted as "100.00"');
    }

    public function test_prepare_percent_data_with99()
    {
        $result = $this->itemService->preparePercentData(99.99);
        $this->assertEquals(99.99, $result, 'Expected percent data to be formatted as "90.00"');
    }

    public function test_prepare_percent_data_with_long_decimal()
    {
        $result = $this->itemService->preparePercentData(99.999999999);
        $this->assertEquals(100.00, $result, 'Expected percent data to be formatted as "100.00"');
    }

    public function test_prepare_percent_data_with_long_decimal2()
    {
        $result = $this->itemService->preparePercentData(99.111111111);
        $this->assertEquals(99.11, $result, 'Expected percent data to be formatted as "99.11"');
    }

    public function test_prepare_percent_data_with_not_number()
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Percent must be between 0 and 100.');
        $result = $this->itemService->preparePercentData('ABCE');
    }

    // endregion

    // region prepareisDiscountData
    // -------------------------------------------------------------------
    // prepareisDiscountData
    // -------------------------------------------------------------------
    public function test_prepareis_discount_data_with_null()
    {
        $result = $this->itemService->prepareisDiscountData(null);
        $this->assertEquals(0, $result, 'Discount should be enabled (0)');
    }

    public function test_prepareis_discount_data_with_zero()
    {
        $result = $this->itemService->prepareisDiscountData(0);
        $this->assertEquals(0, $result, 'Discount should be enabled (0)');
    }

    public function test_prepareis_discount_data_with_normal()
    {
        $result = $this->itemService->prepareisDiscountData(10);
        $this->assertEquals(1, $result, 'Discount should be enabled (1)');
    }

    public function test_prepareis_discount_data_with_normal_upper_limit()
    {
        $result = $this->itemService->prepareisDiscountData(100);
        $this->assertEquals(1, $result, 'Discount should be enabled (1)');
    }

    public function test_prepareis_discount_data_with_normal_lower_limit()
    {
        $result = $this->itemService->prepareisDiscountData(0.01);
        $this->assertEquals(1, $result, 'Discount should be enabled (1)');
    }

    public function test_prepareis_discount_data_with_negative()
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Percent must be between 0 and 100.');
        $result = $this->itemService->prepareisDiscountData(-1);
    }

    public function test_prepareis_discount_data_with_big_number()
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Percent must be between 0 and 100.');
        $result = $this->itemService->prepareisDiscountData(5555555);
    }

    public function test_prepareis_discount_data_with_not_numeric()
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Percent must be between 0 and 100.');
        $result = $this->itemService->prepareisDiscountData('ABC');
    }

    // endregion

    // region prepareStatusData

    public function test_prepare_status_data_with_non_nul_status()
    {
        $result = $this->itemService->prepareStatusData(ItemStatus::publish, null);
        $this->assertEquals(ItemStatus::publish, $result, 'It should be return '.ItemStatus::publish);

        $result = $this->itemService->prepareStatusData(ItemStatus::unpublish, null);
        $this->assertEquals(ItemStatus::unpublish, $result, 'It should be return '.ItemStatus::unpublish);

        $result = $this->itemService->prepareStatusData(ItemStatus::disable, null);
        $this->assertEquals(ItemStatus::disable, $result, 'It should be return '.ItemStatus::disable);

        $result = $this->itemService->prepareStatusData(ItemStatus::reject, null);
        $this->assertEquals(ItemStatus::reject, $result, 'It should be return '.ItemStatus::reject);

        $result = $this->itemService->prepareStatusData(ItemStatus::pending, null);
        $this->assertEquals(ItemStatus::pending, $result, 'It should be return '.ItemStatus::pending);
    }

    public function test_prepare_status_data_with_invalid_status()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid status value provided.');
        $this->itemService->prepareStatusData('invalid_status', null);
    }

    public function test_prepare_status_data_with_empty_string()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid status value provided.');
        $this->itemService->prepareStatusData('', null);
    }

    public function test_prepare_status_data_with_null()
    {
        $result = $this->itemService->prepareStatusData(null, null);
        $this->assertEquals(1, $result, 'It should be return 1.');
    }

    public function test_prepare_status_data_with_approved_enable_data()
    {
        $result = $this->itemService->prepareStatusData(null, (object) ['is_approved_enable' => true]);
        $this->assertEquals(ItemStatus::pending, $result, 'It should be return '.ItemStatus::pending);

        $result = $this->itemService->prepareStatusData(null, (object) ['is_approved_enable' => false]);
        $this->assertEquals(ItemStatus::publish, $result, 'It should be return '.ItemStatus::publish);

        $result = $this->itemService->prepareStatusData(null, (object) ['some_wrong_value' => false]);
        $this->assertEquals(ItemStatus::publish, $result, 'It should be return '.ItemStatus::publish);

        $result = $this->itemService->prepareStatusData(ItemStatus::reject, (object) ['is_approved_enable' => true]);
        $this->assertEquals(ItemStatus::reject, $result, 'It should be return '.ItemStatus::reject);
    }
    // endregion

    // region prepareCurrencyIdData

    public function test_prepare_currency_id_data_with_normal()
    {
        $result = $this->itemService->prepareCurrencyIdData(1);
        $this->assertEquals(1, $result, 'It should be return 1.');

        $result = $this->itemService->prepareCurrencyIdData(9999);
        $this->assertEquals(9999, $result, 'It should be return 9999.');

        $this->itemCurrencyService->shouldReceive('get')
            ->once()
            ->andReturn((object) ['id' => 9]);

        $result = $this->itemService->prepareCurrencyIdData(null);
        $this->assertEquals(9, $result, 'It should be return 9.');
    }

    public function test_prepare_currency_id_data_with_no_default_currency()
    {
        $this->itemCurrencyService->shouldReceive('get')
            ->once()
            ->andReturn(null);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Default currency not found.');
        $this->itemService->prepareCurrencyIdData(null);
    }

    public function test_prepare_currency_id_data_with_wrong_object()
    {
        $this->itemCurrencyService->shouldReceive('get')
            ->once()
            ->andReturn((object) ['somedata' => 'wrong']);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Default currency not found.');
        $this->itemService->prepareCurrencyIdData(null);
    }
    // endregion

    // region prepareOriginalPriceData

    public function test_prepare_original_price_data_with_normal()
    {
        $result = $this->itemService->prepareOriginalPriceData(100);
        $this->assertEquals(100, $result, 'It should be return 100.');

        $result = $this->itemService->prepareOriginalPriceData(0);
        $this->assertEquals(0, $result, 'It should be return 0.');

        $result = $this->itemService->prepareOriginalPriceData(9999.99);
        $this->assertEquals(9999.99, $result, 'It should be return 9999.99.');

        $result = $this->itemService->prepareOriginalPriceData(null);
        $this->assertEquals(0, $result, 'It should be return 0.');

        $result = $this->itemService->prepareOriginalPriceData('');
        $this->assertEquals(0, $result, 'It should be return 0.');
    }

    public function test_prepare_original_price_data_with_negative_value()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Original price must not be negative value.');
        $this->itemService->prepareOriginalPriceData(-200);
    }

    public function test_prepare_original_price_data_with_non_numeric()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Original price must be a numeric value.');
        $this->itemService->prepareOriginalPriceData('ABC');
    }

    // endregion

    // region preparePriceData

    public function test_prepare_price_data_with_no_percent()
    {
        $result = $this->itemService->preparePriceData(100, 0);
        $this->assertEquals(100, $result, 'it should return 100.');

        $result = $this->itemService->preparePriceData(null, 0);
        $this->assertEquals(0, $result, 'it should return 0.');

        $result = $this->itemService->preparePriceData(0, 0);
        $this->assertEquals(0, $result, 'it should return 0.');

        $result = $this->itemService->preparePriceData('', 0);
        $this->assertEquals(0, $result, 'it should return 0.');
    }

    public function test_prepare_price_data_with_invalid_price()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Original price must be a numeric value.');
        $this->itemService->preparePriceData('ABC', 0);
    }

    public function test_prepare_price_data_with_percent()
    {
        $result = $this->itemService->preparePriceData(100, 10);
        $this->assertEquals(90, $result, 'it should return 90.');

        $result = $this->itemService->preparePriceData(100, 100);
        $this->assertEquals(0, $result, 'it should return 0.');

        $result = $this->itemService->preparePriceData(100, 22.22);
        $this->assertEquals(77.78, $result, 'it should return 77.78.');

    }

    // endregion

    // region generateVisiblePatternArray
    // -------------------------------------------------------------------
    // generateVisiblePatternArray
    // -------------------------------------------------------------------
    public function test_generate_visible_pattern_array_with_negative_limit_value()
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage("The 'limit' parameter must be a positive integer.");
        $this->itemService->generateVisiblePatternArray(-10);
    }

    public function test_generate_visible_pattern_array_with_non_negative_limit_value()
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage("The 'limit' parameter must be a positive integer.");
        $this->itemService->generateVisiblePatternArray(0);
    }

    public function test_generate_visible_pattern_array_with_float_limit_value()
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage("The 'limit' parameter must be a positive integer.");
        $this->itemService->generateVisiblePatternArray(3.7);
    }

    public function test_generate_visible_pattern_array_with_string_limit_value()
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage("The 'limit' parameter must be a positive integer.");
        $this->itemService->generateVisiblePatternArray('test');
    }

    public function test_generate_visible_pattern_array_with_negative_offset_value()
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage("The 'offset' parameter must be a non-negative integer.");
        $this->itemService->generateVisiblePatternArray(5, -1);
    }

    public function test_generate_visible_pattern_array_with_float_offset_value()
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage("The 'offset' parameter must be a non-negative integer.");
        $this->itemService->generateVisiblePatternArray(5, 2.3);
    }

    public function test_generate_visible_pattern_array_with_string_offset_value()
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage("The 'offset' parameter must be a non-negative integer.");
        $this->itemService->generateVisiblePatternArray(5, 'testGenerateVisiblePatternArray_withPositiveOffsetValueOne');
    }

    public function test_generate_visible_pattern_array_with_negative_interval_value()
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage("The 'interval' parameter must be a non-negative integer.");
        $this->itemService->generateVisiblePatternArray(5, 0, -1);
    }

    public function test_generate_visible_pattern_array_with_float_interval_value()
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage("The 'interval' parameter must be a non-negative integer.");
        $this->itemService->generateVisiblePatternArray(5, 0, 6.2);
    }

    public function test_generate_visible_pattern_array_with_string_interval_value()
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage("The 'interval' parameter must be a non-negative integer.");
        $this->itemService->generateVisiblePatternArray(5, 0, 'hello');
    }

    // S N N
    public function test_generate_visible_pattern_array_with_small_limit_zero_offset_and_zero_interval_value()
    {
        $result = $this->itemService->generateVisiblePatternArray(10);
        $this->assertEquals(['one', 'one', 'one', 'one', 'one', 'one', 'one', 'one', 'one', 'one'], $result, "Expected array with the length of 10, filled with string value 'one'.");
    }

    // L N N
    public function test_generate_visible_pattern_array_with_large_limit_zero_offset_and_zero_interval_value()
    {
        $result = $this->itemService->generateVisiblePatternArray(30);
        $this->assertEquals(['one', 'one', 'one', 'one', 'one', 'one', 'one', 'one', 'one', 'one', 'one', 'one', 'one', 'one', 'one', 'one', 'one', 'one', 'one', 'one', 'one', 'one', 'one', 'one', 'one', 'one', 'one', 'one', 'one', 'one'], $result, "Expected array with the length of 30, filled with string value 'one'.");
    }

    // S N S
    public function test_generate_visible_pattern_array_with_small_limit_zero_offset_and_small_interval_value()
    {
        $result = $this->itemService->generateVisiblePatternArray(10, 0, 4);
        $this->assertEquals(['one', 'one', 'one', 'one', 'zero', 'one', 'one', 'one', 'one', 'zero'], $result, "Expected array with the length of 10. 'zero' value comes after every 4 'one' values (or) every 5th value is 'zero'.");
    }

    // S N L
    public function test_generate_visible_pattern_array_with_small_limit_zero_offset_and_large_interval_value()
    {
        $result = $this->itemService->generateVisiblePatternArray(10, 5, 40);
        $this->assertEquals(['one', 'one', 'one', 'one', 'one', 'one', 'one', 'one', 'one', 'one'], $result, "Expected array with the length of 10, filled with string value 'one'.");
    }

    // L N S
    public function test_generate_visible_pattern_array_with_large_limit_zero_offset_and_small_interval_value()
    {
        $result = $this->itemService->generateVisiblePatternArray(30, 0, 4);
        $this->assertEquals(['one', 'one', 'one', 'one', 'zero', 'one', 'one', 'one', 'one', 'zero', 'one', 'one', 'one', 'one', 'zero', 'one', 'one', 'one', 'one', 'zero', 'one', 'one', 'one', 'one', 'zero', 'one', 'one', 'one', 'one', 'zero'], $result, "Expected array with the length of 30. 'zero' value comes after every 4 'one' values (or) every 5th value is 'zero'.");
    }

    // L N L
    public function test_generate_visible_pattern_array_with_large_limit_zero_offset_and_large_interval_value()
    {
        $result = $this->itemService->generateVisiblePatternArray(40, 0, 19);
        $this->assertEquals(['one', 'one', 'one', 'one', 'one', 'one', 'one', 'one', 'one', 'one', 'one', 'one', 'one', 'one', 'one', 'one', 'one', 'one', 'one', 'zero', 'one', 'one', 'one', 'one', 'one', 'one', 'one', 'one', 'one', 'one', 'one', 'one', 'one', 'one', 'one', 'one', 'one', 'one', 'one', 'zero'], $result, "Expected array with the length of 40. 'zero' value comes after every 19 'one' values (or) every 20th value is 'zero'.");
    }

    // S S S
    public function test_generate_visible_pattern_array_with_small_limit_small_offset_and_small_interval_value()
    {
        $result = $this->itemService->generateVisiblePatternArray(10, 2, 7);
        $this->assertEquals(['one', 'one', 'one', 'one', 'one', 'zero', 'one', 'one', 'one', 'one'], $result, 'Passed');
    }

    // S S L
    public function test_generate_visible_pattern_array_with_small_limit_small_offset_and_large_interval_value()
    {
        $result = $this->itemService->generateVisiblePatternArray(10, 2, 33);
        $this->assertEquals(['one', 'one', 'one', 'one', 'one', 'one', 'one', 'one', 'one', 'one'], $result, 'Passed');
    }

    // S L S
    public function test_generate_visible_pattern_array_with_small_limit_large_offset_and_small_interval_value()
    {
        $result = $this->itemService->generateVisiblePatternArray(14, 53, 6);
        $this->assertEquals(['one', 'one', 'zero', 'one', 'one', 'one', 'one', 'one', 'one', 'zero', 'one', 'one', 'one', 'one'], $result, 'Passed');
    }

    // S L L
    public function test_generate_visible_pattern_array_with_small_limit_large_offset_and_large_interval_value()
    {
        $result = $this->itemService->generateVisiblePatternArray(9, 53, 58);
        $this->assertEquals(['one', 'one', 'one', 'one', 'one', 'zero', 'one', 'one', 'one'], $result, 'Passed');
    }

    // L S S
    public function test_generate_visible_pattern_array_with_large_limit_small_offset_and_small_interval_value()
    {
        $result = $this->itemService->generateVisiblePatternArray(40, 8, 9);
        $this->assertEquals(['one', 'zero', 'one', 'one', 'one', 'one', 'one', 'one', 'one', 'one', 'one', 'zero', 'one', 'one', 'one', 'one', 'one', 'one', 'one', 'one', 'one', 'zero', 'one', 'one', 'one', 'one', 'one', 'one', 'one', 'one', 'one', 'zero', 'one', 'one', 'one', 'one', 'one', 'one', 'one', 'one'], $result, 'Passed');
    }

    // L S L
    public function test_generate_visible_pattern_array_with_large_limit_small_offset_and_large_interval_value()
    {
        $result = $this->itemService->generateVisiblePatternArray(40, 7, 31);
        $this->assertEquals(['one', 'one', 'one', 'one', 'one', 'one', 'one', 'one', 'one', 'one', 'one', 'one', 'one', 'one', 'one', 'one', 'one', 'one', 'one', 'one', 'one', 'one', 'one', 'one', 'zero', 'one', 'one', 'one', 'one', 'one', 'one', 'one', 'one', 'one', 'one', 'one', 'one', 'one', 'one', 'one'], $result, 'Passed');
    }

    // L L S
    public function test_generate_visible_pattern_array_with_large_limit_large_offset_and_small_interval_value()
    {
        $result = $this->itemService->generateVisiblePatternArray(40, 63, 4);
        $this->assertEquals(['one', 'zero', 'one', 'one', 'one', 'one', 'zero', 'one', 'one', 'one', 'one', 'zero', 'one', 'one', 'one', 'one', 'zero', 'one', 'one', 'one', 'one', 'zero', 'one', 'one', 'one', 'one', 'zero', 'one', 'one', 'one', 'one', 'zero', 'one', 'one', 'one', 'one', 'zero', 'one', 'one', 'one'], $result, 'Passed');
    }

    // L L L
    public function test_generate_visible_pattern_array_with_large_limit_large_offset_and_large_interval_value()
    {
        $result = $this->itemService->generateVisiblePatternArray(40, 59, 26);
        $this->assertEquals(['one', 'one', 'one', 'one', 'one', 'one', 'one', 'one', 'one', 'one', 'one', 'one', 'one', 'one', 'one', 'one', 'one', 'one', 'one', 'one', 'one', 'zero', 'one', 'one', 'one', 'one', 'one', 'one', 'one', 'one', 'one', 'one', 'one', 'one', 'one', 'one', 'one', 'one', 'one', 'one'], $result, 'Passed');
    }
    // endregion

    // region calculateItemLimitAndOffset
    // -------------------------------------------------------------------
    // calculateItemLimitAndOffset
    // -------------------------------------------------------------------
    public function test_calculate_item_limit_and_offset_with_negative_limit_value()
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage("The 'limit' parameter must be a positive integer.");
        $this->itemService->calculateItemLimitAndOffset(-10, 10, 2);
    }

    public function test_calculate_item_limit_and_offset_with_non_negative_limit_value()
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage("The 'limit' parameter must be a positive integer.");
        $this->itemService->calculateItemLimitAndOffset(0, 10, 2);
    }

    public function test_calculate_item_limit_and_offset_with_float_limit_value()
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage("The 'limit' parameter must be a positive integer.");
        $this->itemService->calculateItemLimitAndOffset(14.5, 10, 2);
    }

    public function test_calculate_item_limit_and_offset_with_string_limit_value()
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage("The 'limit' parameter must be a positive integer.");
        $this->itemService->calculateItemLimitAndOffset('test', 10, 2);
    }

    public function test_calculate_item_limit_and_offset_with_negative_offset_value()
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage("The 'offset' parameter must be a non-negative integer.");
        $this->itemService->calculateItemLimitAndOffset(10, -5, 2);
    }

    public function test_calculate_item_limit_and_offset_with_float_offset_value()
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage("The 'offset' parameter must be a non-negative integer.");
        $this->itemService->calculateItemLimitAndOffset(10, 5.5, 2);
    }

    public function test_calculate_item_limit_and_offset_with_string_offset_value()
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage("The 'offset' parameter must be a non-negative integer.");
        $this->itemService->calculateItemLimitAndOffset(10, 'test', 2);
    }

    public function test_calculate_item_limit_and_offset_with_negative_interval_value()
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage("The 'interval' parameter must be a non-negative integer.");
        $this->itemService->calculateItemLimitAndOffset(10, 10, -10);
    }

    public function test_calculate_item_limit_and_offset_with_float_interval_value()
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage("The 'interval' parameter must be a non-negative integer.");
        $this->itemService->calculateItemLimitAndOffset(10, 10, 10.5);
    }

    public function test_calculate_item_limit_and_offset_with_string_interval_value()
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage("The 'interval' parameter must be a non-negative integer.");
        $this->itemService->calculateItemLimitAndOffset(10, 10, 'test');
    }

    // S N N
    public function test_calculate_item_limit_and_offset_with_small_limit_zero_offset_and_zero_interval_value()
    {
        $result = $this->itemService->calculateItemLimitAndOffset(10);
        $this->assertEquals([
            'normalLimit' => 10,
            'normalOffset' => 0,
            'paidLimit' => 0,
            'paidOffset' => 0,
        ], $result, "Expected array with the 'normalLimit' 10 and 0 for the rest of the key values.");
    }

    // L N N
    public function test_calculate_item_limit_and_offset_with_large_limit_zero_offset_and_zero_interval_value()
    {
        $result = $this->itemService->calculateItemLimitAndOffset(30);
        $this->assertEquals([
            'normalLimit' => 30,
            'normalOffset' => 0,
            'paidLimit' => 0,
            'paidOffset' => 0,
        ], $result, "Expected array with the 'normalLimit' 30 and 0 for the rest of the key values.");
    }

    // S N S
    public function test_calculate_item_limit_and_offset_with_small_limit_zero_offset_and_small_interval_value()
    {
        $result = $this->itemService->calculateItemLimitAndOffset(10, 0, 2);
        $this->assertEquals([
            'normalLimit' => 7,
            'normalOffset' => 0,
            'paidLimit' => 3,
            'paidOffset' => 0,
        ], $result, "Expected array with the 'normalLimit' of 7 and 'paidLimit' of 3.");
    }

    // S N L
    public function test_calculate_item_limit_and_offset_with_small_limit_zero_offset_and_large_interval_value()
    {
        $result = $this->itemService->calculateItemLimitAndOffset(10, 0, 25);
        $this->assertEquals([
            'normalLimit' => 10,
            'normalOffset' => 0,
            'paidLimit' => 0,
            'paidOffset' => 0,
        ], $result, "Expected array with the 'normalLimit' of 10 and 0 for the rest of  the key values.");
    }

    // L N S
    public function test_calculate_item_limit_and_offset_with_large_limit_zero_offset_and_small_interval_value()
    {
        $result = $this->itemService->calculateItemLimitAndOffset(35, 0, 3);
        $this->assertEquals([
            'normalLimit' => 27,
            'normalOffset' => 0,
            'paidLimit' => 8,
            'paidOffset' => 0,
        ], $result, "Expected array with the 'normalLimit' of 27 and 'paidLimit' of 8.");
    }

    // L N L
    public function test_calculate_item_limit_and_offset_with_large_limit_zero_offset_and_large_interval_value()
    {
        $result = $this->itemService->calculateItemLimitAndOffset(50, 0, 19);
        $this->assertEquals([
            'normalLimit' => 48,
            'normalOffset' => 0,
            'paidLimit' => 2,
            'paidOffset' => 0,
        ], $result, "Expected array with the 'normalLimit' of 48 and 'paidLimit' of 2.");
    }

    // S S S
    public function test_calculate_item_limit_and_offset_with_small_limit_small_offset_and_small_interval_value()
    {
        $result = $this->itemService->calculateItemLimitAndOffset(10, 10, 2);
        $this->assertEquals([
            'normalLimit' => 7,
            'normalOffset' => 7,
            'paidLimit' => 3,
            'paidOffset' => 3,
        ], $result, "Expected array with the 'normalLimit' of 7, 'normalOffest' of 7, 'paidLimit' of 3 and 'paidOffset' of 3.");
    }

    // S S L
    public function test_calculate_item_limit_and_offset_with_small_limit_small_offset_and_large_interval_value()
    {
        $result = $this->itemService->calculateItemLimitAndOffset(10, 10, 32);
        $this->assertEquals([
            'normalLimit' => 10,
            'normalOffset' => 10,
            'paidLimit' => 0,
            'paidOffset' => 0,
        ], $result, "Expected array with the 'normalLimit' of 10 and 'normalOffset' of 10.");
    }

    // S L S
    public function test_calculate_item_limit_and_offset_with_small_limit_large_offset_and_small_interval_value()
    {
        $result = $this->itemService->calculateItemLimitAndOffset(15, 44, 4);
        $this->assertEquals([
            'normalLimit' => 12,
            'normalOffset' => 36,
            'paidLimit' => 3,
            'paidOffset' => 8,
        ], $result, "Expected array with the 'normalLimit' of 12, 'normalOffset' of 36, 'paidLimit' of 3 and 'paidOffset' of 8.");
    }

    // S L L
    public function test_calculate_item_limit_and_offset_with_small_limit_large_offset_and_large_interval_value()
    {
        $result = $this->itemService->calculateItemLimitAndOffset(15, 44, 24);
        $this->assertEquals([
            'normalLimit' => 14,
            'normalOffset' => 43,
            'paidLimit' => 1,
            'paidOffset' => 1,
        ], $result, "Expected array with the 'normalLimit' of 14, 'normalOffset' of 43, 'paidLimit' of 1 and 'paidOffset' of 1.");
    }

    // L S S
    public function test_calculate_item_limit_and_offset_with_large_limit_small_offset_and_small_interval_value()
    {
        $result = $this->itemService->calculateItemLimitAndOffset(65, 12, 5);
        $this->assertEquals([
            'normalLimit' => 55,
            'normalOffset' => 10,
            'paidLimit' => 10,
            'paidOffset' => 2,
        ], $result, "Expected array with the 'normalLimit' of 55, 'normalOffset' of 10, 'paidLimit' of 10 and 'paidOffset' of 2.");
    }

    // L S L
    public function test_calculate_item_limit_and_offset_with_large_limit_small_offset_and_large_interval_value()
    {
        $result = $this->itemService->calculateItemLimitAndOffset(65, 12, 5);
        $this->assertEquals([
            'normalLimit' => 55,
            'normalOffset' => 10,
            'paidLimit' => 10,
            'paidOffset' => 2,
        ], $result, "Expected array with the 'normalLimit' of 55, 'normalOffset' of 10, 'paidLimit' of 10 and 'paidOffset' of 2.");
    }

    // L L S
    public function test_calculate_item_limit_and_offset_with_large_limit_large_offset_and_small_interval_value()
    {
        $result = $this->itemService->calculateItemLimitAndOffset(65, 52, 6);
        $this->assertEquals([
            'normalLimit' => 56,
            'normalOffset' => 45,
            'paidLimit' => 9,
            'paidOffset' => 7,
        ], $result, "Expected array with the 'normalLimit' of 56, 'normalOffset' of 45, 'paidLimit' of 9 and 'paidOffset' of 7.");
    }

    // L L L
    public function test_calculate_item_limit_and_offset_with_large_limit_large_offset_and_large_interval_value()
    {
        $result = $this->itemService->calculateItemLimitAndOffset(72, 75, 28);
        $this->assertEquals([
            'normalLimit' => 69,
            'normalOffset' => 73,
            'paidLimit' => 3,
            'paidOffset' => 2,
        ], $result, "Expected array with the 'normalLimit' of 69, 'normalOffset' of 73, 'paidLimit' of 3 and 'paidOffset' of 2.");
    }
    // endregion

    // region prepareIsPaidData
    // -------------------------------------------------------------------
    // prepareIsPaidData
    // -------------------------------------------------------------------
    public function test_prepare_is_paid_data_with_invalid_positive_integer_value()
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Invalid isPaidValue. Expected 0, 1, true, or false, got 21');
        $this->itemService->prepareIsPaidData(21);
    }

    public function test_prepare_is_paid_data_with_invalid_negative_integer_value()
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Invalid isPaidValue. Expected 0, 1, true, or false, got -31');
        $this->itemService->prepareIsPaidData(-31);
    }

    public function test_prepare_is_paid_data_with_invalid_float_value()
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Invalid isPaidValue. Expected 0, 1, true, or false, got 12.24');
        $this->itemService->prepareIsPaidData(12.24);
    }

    public function test_prepare_is_paid_data_with_invalid_string_value()
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage("Invalid isPaidValue. Expected 0, 1, true, or false, got 'test'");
        $this->itemService->prepareIsPaidData('test');
    }

    public function test_prepare_is_paid_data_with_invalid_object_value()
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Invalid isPaidValue. Expected 0, 1, true, or false, got object');
        $this->itemService->prepareIsPaidData((object) ['isPaidValue' => 1]);
    }

    public function test_prepare_is_paid_data_with_invalid_array_value()
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Invalid isPaidValue. Expected 0, 1, true, or false, got array');
        $this->itemService->prepareIsPaidData([0, 1]);
    }

    public function test_prepare_is_paid_data_with_null_value()
    {
        $result = $this->itemService->prepareIsPaidData(null);
        $this->assertEquals(0, $result, 'Expected to returns 0.');
    }

    public function test_prepare_is_paid_data_with_valid_string_value()
    {
        $result = $this->itemService->prepareIsPaidData('0');
        $this->assertEquals(0, $result, 'Expected to returns 0.');

        $result = $this->itemService->prepareIsPaidData('1');
        $this->assertEquals(1, $result, 'Expected to returns 1.');
    }

    public function test_prepare_is_paid_data_with_valid_integer_value()
    {
        $result = $this->itemService->prepareIsPaidData(0);
        $this->assertEquals(0, $result, 'Expected to returns 0.');

        $result = $this->itemService->prepareIsPaidData(1);
        $this->assertEquals(1, $result, 'Expected to returns 1.');
    }

    public function test_prepare_is_paid_data_with_valid_boolean_value()
    {
        $result = $this->itemService->prepareIsPaidData(false);
        $this->assertEquals(0, $result, 'Expected to returns 0.');

        $result = $this->itemService->prepareIsPaidData(true);
        $this->assertEquals(1, $result, 'Expected to returns 1.');
    }
    // endregion

    // region prepareVendorIdData
    // -------------------------------------------------------------------
    // prepareVendorIdData
    // -------------------------------------------------------------------
    public function test_prepare_vendor_id_data_with_null()
    {
        $result = $this->itemService->prepareVendorIdData(null);
        $this->assertEquals(null, $result, 'Expected to return null.');
    }

    public function test_prepare_vendor_id_data_with_invalid_negative_integer_value()
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Invalid vendorIdValue. Expected positive integer, got -1');
        $this->itemService->prepareVendorIdData(-1);
    }

    public function test_prepare_vendor_id_data_with_invalid_non_negative_integer_value()
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Invalid vendorIdValue. Expected positive integer, got 0');
        $this->itemService->prepareVendorIdData(0);
    }

    public function test_prepare_vendor_id_data_with_invalid_float_value()
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Invalid vendorIdValue. Expected positive integer, got 1.23');
        $this->itemService->prepareVendorIdData(1.23);
    }

    public function test_prepare_vendor_id_data_with_invalid_string_value()
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage("Invalid vendorIdValue. Expected positive integer, got 'test'");
        $this->itemService->prepareVendorIdData('test');
    }

    public function test_prepare_vendor_id_data_with_invalid_array_value()
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Invalid vendorIdValue. Expected positive integer, got array');
        $this->itemService->prepareVendorIdData([1, 2]);
    }

    public function test_prepare_vendor_id_data_with_invalid_object_value()
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Invalid vendorIdValue. Expected positive integer, got object');
        $this->itemService->prepareVendorIdData((object) ['vendor_id' => 1]);
    }

    public function test_prepare_vendor_id_data_with_valid_integer_value()
    {
        $result = $this->itemService->prepareVendorIdData(12);
        $this->assertEquals(12, $result, 'Expected to return 12.');
    }

    public function test_prepare_vendor_id_data_with_valid_string_value()
    {
        $result = $this->itemService->prepareVendorIdData('12');
        $this->assertEquals(12, $result, 'Expected to return 12.');
    }
    // endregion

    // region create
    // -------------------------------------------------------------------
    // create
    // -------------------------------------------------------------------

    public function test_create_with_valid_data()
    {
        $itemDto = new ItemDto(
            id: null,
            title: 'Nike Air Max',
            categoryId: 10,
            subcategoryId: 11,
            currencyId: 16,
            locationCityId: 16,
            locationTownshipId: 5,
            shopId: 3,
            price: 100,
            originalPrice: 120,
            description: 'High-quality running shoes.',
            searchTag: 'nike,airmax,running',
            dynamicLink: 'https://example.com/items/nike-air-max',
            lat: 16.807020,
            lng: 96.123326,
            status: 1,
            isPaid: 1,
            isSoldOut: 0,
            ordering: 2,
            isAvailable: 1,
            isDiscount: 1,
            itemTouchCount: 12,
            favouriteCount: 3,
            overallRating: 4.5,
            vendorId: 7,
            addedUserId: 1,
            updatedUserId: 1,
            percent: 20,
            phone: '09123456789',
            imgOrder: null,
            imgCaption: null,
            loginUserId: 1,
            languageSymbol: 'en',
            customFields: []
        );

        $item = $this->itemService->create($itemDto);

        $this->assertInstanceOf(Item::class, $item);
        $this->assertDatabaseHas('psx_items', [
            'id' => $item->id,
        ]);
        $this->assertEquals(7, $item->vendor_id);
    }

    public function test_create_without_optional_fields()
    {
        // Some Nullables values are provided and need to be fixed
        $itemDto = new ItemDto(
            id: null,
            title: 'Nike Air Max',
            categoryId: 10,
            subcategoryId: null,
            currencyId: 16,
            locationCityId: 16,
            locationTownshipId: null,
            shopId: null,
            price: 100,
            originalPrice: null,
            description: null,
            searchTag: null,
            dynamicLink: null,
            lat: null,
            lng: null,
            status: 1,
            isPaid: 0,
            isSoldOut: 0,
            ordering: null,
            isAvailable: 1,
            isDiscount: 0,
            itemTouchCount: 0,
            favouriteCount: 0,
            overallRating: 0,
            vendorId: null,
            addedUserId: 1,
            updatedUserId: null,
            percent: null,
            phone: null,
            imgOrder: null,
            imgCaption: null,
            loginUserId: 1,
            languageSymbol: 'en',
            customFields: []
        );

        $item = $this->itemService->create($itemDto);

        $this->assertInstanceOf(Item::class, $item);
        $this->assertDatabaseHas('psx_items', [
            'id' => $item->id,
        ]);
    }

    public function test_create_with_valid_float_values()
    {
        $itemDto = new ItemDto(
            id: null,
            title: 'Nike Air Max',
            categoryId: 10,
            subcategoryId: null,
            currencyId: 16,
            locationCityId: 16,
            locationTownshipId: null,
            shopId: null,
            price: 20.24,
            originalPrice: 20.24,
            description: null,
            searchTag: null,
            dynamicLink: null,
            lat: 16.807020,
            lng: 96.123326,
            status: 1,
            isPaid: 0,
            isSoldOut: 0,
            ordering: null,
            isAvailable: 1,
            isDiscount: 0,
            itemTouchCount: 0,
            favouriteCount: 0,
            overallRating: 0,
            vendorId: null,
            addedUserId: 1,
            updatedUserId: null,
            percent: 34.45,
            phone: null,
            imgOrder: null,
            imgCaption: null,
            loginUserId: 1,
            languageSymbol: 'en',
            customFields: []
        );

        $item = $this->itemService->create($itemDto);

        $this->assertIsFloat((float) $item->original_price);
        $this->assertEquals(20.24, (float) $item->original_price);

        $this->assertIsFloat((float) $item->price);
        $this->assertEquals(20.24, (float) $item->price);

        $this->assertIsFloat((float) $item->lat);
        $this->assertEquals(16.807020, (float) $item->lat);

        $this->assertIsFloat((float) $item->lng);
        $this->assertEquals(96.123326, (float) $item->lng);

        $this->assertIsFloat((float) $item->percent);
        $this->assertEquals(34.45, (float) $item->percent);
    }

    public function test_create_with_invalid_float_values()
    {
        $itemDto = new ItemDto(
            id: null,
            title: 'Nike Air Max',
            categoryId: 10,
            subcategoryId: null,
            currencyId: 16,
            locationCityId: 16,
            locationTownshipId: null,
            shopId: null,
            price: -1.23,
            originalPrice: '0',
            description: null,
            searchTag: null,
            dynamicLink: null,
            lat: '99999.9999999999',
            lng: 'test',
            status: 1,
            isPaid: 0,
            isSoldOut: 0,
            ordering: null,
            isAvailable: 1,
            isDiscount: 0,
            itemTouchCount: 0,
            favouriteCount: 0,
            overallRating: 0,
            vendorId: null,
            addedUserId: 1,
            updatedUserId: null,
            percent: '-20.34',
            phone: null,
            imgOrder: null,
            imgCaption: null,
            loginUserId: 1,
            languageSymbol: 'en',
            customFields: []
        );

        $item = $this->itemService->create($itemDto);

        $this->assertIsFloat((float) $item->original_price);
        $this->assertEquals(0, (float) $item->original_price);

        $this->assertIsFloat((float) $item->price);
        $this->assertEquals(-1.23, (float) $item->price);

        $this->assertIsFloat((float) $item->lat);
        $this->assertEquals(99999.9999999999, (float) $item->lat);

        $this->assertIsFloat((float) $item->lng);
        $this->assertEquals(0, (float) $item->lng);

        $this->assertIsFloat((float) $item->percent);
        $this->assertEquals(-20.34, (float) $item->percent);
    }
    // endregion

    // region update
    // -------------------------------------------------------------------
    // update
    // -------------------------------------------------------------------

    public function test_update_v2_update_existing_item_correctly(): void
    {
        Category::factory()->create();
        LocationCity::factory()->create();
        ItemCurrency::factory()->create();
        User::factory()->create();
        $item = Item::factory()->create();

        $itemDto = new ItemDto(
            id: $item->id,
            title: 'Updated Title Name',
            categoryId: 10,
            subcategoryId: 10,
            currencyId: 16,
            locationCityId: 16,
            locationTownshipId: 4,
            shopId: null,
            price: 200,
            originalPrice: 0,
            description: 'Update Description',
            searchTag: 'nike,airmax,running',
            dynamicLink: null,
            lat: 16.807020,
            lng: 96.123326,
            status: 1,
            isPaid: 0,
            isSoldOut: 0,
            ordering: 1,
            isAvailable: 1,
            isDiscount: 1,
            itemTouchCount: 0,
            favouriteCount: 0,
            overallRating: 0,
            vendorId: 5,
            addedUserId: 1,
            updatedUserId: null,
            percent: 10.0,
            phone: '0912345678',
            imgOrder: null,
            imgCaption: null,
            loginUserId: 1,
            languageSymbol: 'en',
            customFields: []
        );

        $updatedItem = $this->itemService->updateV2($item->id, $itemDto);

        $this->assertInstanceOf(Item::class, $updatedItem);
        $this->assertEquals('Updated Title Name', $updatedItem->title);
        $this->assertEquals('Update Description', $updatedItem->description);
        $this->assertEquals(200, $updatedItem->price);
    }

    public function test_update_v2_throws_exception_if_item_not_found(): void
    {
        $this->expectException(\Illuminate\Database\Eloquent\ModelNotFoundException::class);
        $this->expectExceptionMessage('Item with id 9999 not found.');
        $itemDto = new ItemDto(
            id: 9999999,
            title: 'Updated Title Name',
            categoryId: 10,
            subcategoryId: 10,
            currencyId: 16,
            locationCityId: 16,
            locationTownshipId: 4,
            shopId: null,
            price: 200,
            originalPrice: 0,
            description: 'Update Description',
            searchTag: 'nike,airmax,running',
            dynamicLink: null,
            lat: 16.807020,
            lng: 96.123326,
            status: 1,
            isPaid: 0,
            isSoldOut: 0,
            ordering: 1,
            isAvailable: 1,
            isDiscount: 1,
            itemTouchCount: 0,
            favouriteCount: 0,
            overallRating: 0,
            vendorId: 5,
            addedUserId: 1,
            updatedUserId: null,
            percent: 10.0,
            phone: '0912345678',
            imgOrder: null,
            imgCaption: null,
            loginUserId: 1,
            languageSymbol: 'en',
            customFields: []
        );

        $this->itemService->updateV2(9999, $itemDto);
    }

    public function test_update_v2_does_not_create_new_item()
    {
        Category::factory()->create();
        LocationCity::factory()->create();
        ItemCurrency::factory()->create();
        User::factory()->create();
        $item = Item::factory()->create();

        $itemDto = new ItemDto(
            id: $item->id,
            title: 'Updated Title Name',
            categoryId: 10,
            subcategoryId: 10,
            currencyId: 16,
            locationCityId: 16,
            locationTownshipId: 4,
            shopId: null,
            price: 200,
            originalPrice: 0,
            description: 'Update Description',
            searchTag: 'nike,airmax,running',
            dynamicLink: null,
            lat: 16.807020,
            lng: 96.123326,
            status: 1,
            isPaid: 0,
            isSoldOut: 0,
            ordering: 1,
            isAvailable: 1,
            isDiscount: 1,
            itemTouchCount: 0,
            favouriteCount: 0,
            overallRating: 0,
            vendorId: 5,
            addedUserId: 1,
            updatedUserId: null,
            percent: 10.0,
            phone: '0912345678',
            imgOrder: null,
            imgCaption: null,
            loginUserId: 1,
            languageSymbol: 'en',
            customFields: []
        );

        $updatedItem = $this->itemService->updateV2($item->id, $itemDto);
        $this->assertEquals('Updated Title Name', $updatedItem->title);
    }
    // endregion

    // region updateDynamicLink
    // -------------------------------------------------------------------
    // updateDynamicLink
    // -------------------------------------------------------------------

    public function test_update_dynamic_link_update_dynamic_link()
    {
        Category::factory()->create();
        LocationCity::factory()->create();
        ItemCurrency::factory()->create();
        User::factory()->create();
        $item = Item::factory()->create([
            Item::dynamicLink => null,
        ]);

        $dynamicLink = 'PT-1234';
        $updatedItem = $this->itemService->updateDynamicLink($item, $dynamicLink);

        $this->assertEquals($dynamicLink, $updatedItem->dynamic_link);
        $this->assertDatabaseHas(Item::tableName, [
            Item::id => $item->id,
            Item::dynamicLink => $dynamicLink,
        ]);
    }

    public function test_update_dynamic_link_overwrites_existing_link()
    {
        Category::factory()->create();
        LocationCity::factory()->create();
        ItemCurrency::factory()->create();
        User::factory()->create();
        $item = Item::factory()->create([
            Item::dynamicLink => 'ToOverwrite',
        ]);

        $newLink = 'Overwritten';
        $updatedItem = $this->itemService->updateDynamicLink($item, $newLink);

        $this->assertEquals($newLink, $updatedItem->dynamic_link);
        $this->assertDatabaseHas(Item::tableName, [
            Item::id => $item->id,
            Item::dynamicLink => $newLink,
        ]);
    }

    public function test_update_dynamic_link_accepts_empty_string()
    {
        Category::factory()->create();
        LocationCity::factory()->create();
        ItemCurrency::factory()->create();
        User::factory()->create();
        $item = Item::factory()->create([
            Item::dynamicLink => 'ToOverwrite',
        ]);

        $updatedItem = $this->itemService->updateDynamicLink($item, '');

        $this->assertEquals('', $updatedItem->dynamic_link);
        $this->assertDatabaseHas(Item::tableName, [
            Item::id => $item->id,
            Item::dynamicLink => '',
        ]);
    }

    public function test_update_dynamic_link_returns_updated_item_instance()
    {
        Category::factory()->create();
        LocationCity::factory()->create();
        ItemCurrency::factory()->create();
        User::factory()->create();
        $item = Item::factory()->create();

        $result = $this->itemService->updateDynamicLink($item, 'PT-1234');

        $this->assertInstanceOf(Item::class, $result);
    }
    // endregion

    // region get
    // -------------------------------------------------------------------
    // get
    // -------------------------------------------------------------------

    public function test_get_caches_result()
    {
        Category::factory()->create();
        LocationCity::factory()->create();
        ItemCurrency::factory()->create();
        User::factory()->create();
        PsCache::shouldReceive('remember')
            ->once()
            ->withArgs(function ($baseKey, $expiry, $param, $closure) {
                return true;
            })
            ->andReturn(Item::factory()->create());

        $result = $this->itemService->get(1);

        $this->assertInstanceOf(Item::class, $result);
        $this->assertNotNull($result->id);
    }

    public function test_get_returns_item_by_id()
    {
        Category::factory()->create();
        LocationCity::factory()->create();
        ItemCurrency::factory()->create();
        User::factory()->create();
        $item = Item::factory()->create();

        $result = $this->itemService->get($item->id);

        $this->assertNotNull($result);
        $this->assertEquals($item->id, $result->id);
    }

    public function test_get_returns_item_with_relations()
    {
        Category::factory()->create();
        LocationCity::factory()->create();
        ItemCurrency::factory()->create();
        User::factory()->create();
        $item = Item::factory()->create();
        $item->load('category');

        $result = $this->itemService->get($item->id, ['category', 'city']);

        $this->assertTrue($result->relationLoaded('category'));
        $this->assertTrue($result->relationLoaded('city'));
    }

    public function test_get_uses_language_symbol_from_request()
    {
        Category::factory()->create();
        LocationCity::factory()->create();
        ItemCurrency::factory()->create();
        User::factory()->create();
        $item = Item::factory()->create();

        request()->query->set('language_symbol', 'en');

        $result = $this->itemService->get($item->id);

        $this->assertEquals($item->id, $result->id);
    }

    public function test_get_returns_null_if_item_not_found()
    {
        $result = $this->itemService->get(99999);

        $this->assertNull($result);
    }
    // endregion

    // region getAll
    // -------------------------------------------------------------------
    // getAll
    // -------------------------------------------------------------------

    public function test_get_all_uses_cache()
    {
        CustomField::factory()->create([
            CustomField::moduleName => 'itm',
        ]);
        Category::factory()->create();
        LocationCity::factory()->create();
        ItemCurrency::factory()->create();
        User::factory()->create();
        PsCache::shouldReceive('remember')
            ->once()
            ->andReturn(collect([Item::factory()->make()]));

        $result = $this->itemService->getAll([], [], [], null, null, true);

        $this->assertInstanceOf(Collection::class, $result);
    }

    public function test_get_all_returns_item_info()
    {
        $customField = CustomField::factory()->create([
            CustomField::moduleName => 'itm',
        ]);
        Category::factory()->create();
        LocationCity::factory()->create();
        ItemCurrency::factory()->create();
        $user = User::factory()->create();
        $item = Item::factory()->create();
        $customFieldValue = 'Custom Field Value';
        ItemInfo::create([
            ItemInfo::itemId => $item->id,
            ItemInfo::coreKeysId => $customField->core_keys_id,
            ItemInfo::uiTypeId => $customField->ui_type_id,
            ItemInfo::value => $customFieldValue,
            ItemInfo::addedUserId => $user->id,
        ]);

        $result = $this->itemService->getAll([], [], [], null, null, true);

        $this->assertEquals($customFieldValue, $result->first()[$customField->core_keys_id]);
    }

    public function test_get_all_returns_item_info_attribute()
    {
        $customField = CustomField::factory()->create([
            CustomField::moduleName => 'itm',
            CustomField::uiTypeId => Constants::dropDownUi,
        ]);
        $customFieldAttribute = CustomFieldAttribute::factory()->create([
            CustomFieldAttribute::coreKeysId => $customField->{CustomField::coreKeysId},
            CustomFieldAttribute::name => 'Unique Value',
        ]);
        Category::factory()->create();
        LocationCity::factory()->create();
        ItemCurrency::factory()->create();
        $user = User::factory()->create();
        $item = Item::factory()->create();
        ItemInfo::create([
            ItemInfo::itemId => $item->id,
            ItemInfo::coreKeysId => $customField->core_keys_id,
            ItemInfo::uiTypeId => $customField->ui_type_id,
            ItemInfo::value => $customFieldAttribute->id,
            ItemInfo::addedUserId => $user->id,
        ]);

        $result = $this->itemService->getAll([], [], [], null, null, true);

        $this->assertEquals('Unique Value', $result->first()[$customFieldAttribute->core_keys_id.'@@name']);
    }

    public function test_get_all_with_relations_loads_them()
    {
        CustomField::factory()->create([
            CustomField::moduleName => 'itm',
        ]);
        $category = Category::factory()->create();
        $city = LocationCity::factory()->create();
        ItemCurrency::factory()->create();
        User::factory()->create();
        $item = Item::factory()->create([
            Item::categoryId => $category->id,
            Item::itemLocationId => $city->id,
        ]);

        $result = $this->itemService->getAll(['category', 'city'], [], [], null, null, true);

        $this->assertEquals($item->id, $result->first()->id);
        $this->assertTrue($result->first()->relationLoaded('category'));
        $this->assertEquals($category->name, $result->first()->category->name);
        $this->assertTrue($result->first()->relationLoaded('city'));
    }

    public function test_get_all_with_offset_and_limit()
    {
        Item::truncate();
        CustomField::factory()->create([
            CustomField::moduleName => 'itm',
        ]);
        Category::factory()->create();
        LocationCity::factory()->create();
        ItemCurrency::factory()->create();
        User::factory()->create();
        Item::factory()->count(20)->create();

        $firstResult = $this->itemService->getAll([], [], [], 10, 0, true)->pluck(Item::id);
        $secondResult = $this->itemService->getAll([], [], [], 10, 10, true)->pluck(Item::id);

        $this->assertEquals(10, $secondResult->count());
        $this->assertEquals(10, $secondResult->count());
        foreach ($firstResult as $itemId) {
            $this->assertNotContains($itemId, $secondResult);
        }
    }

    public function test_get_all_with_search_keyword()
    {
        CustomField::factory()->create([
            CustomField::moduleName => 'itm',
        ]);
        Category::factory()->create();
        LocationCity::factory()->create();
        ItemCurrency::factory()->create();
        User::factory()->create();
        Item::factory()->count(19)->create();
        Item::factory()->create([
            Item::title => 'Asus Ryzen 10 Laptop',
        ]);
        Item::factory()->create([
            Item::searchterm => 'iPhone 11',
        ]);
        Item::factory()->create([
            Item::description => 'Tablet',
        ]);

        $laptopSearch = $this->itemService->getAll([], ['keyword' => 'Ryzen 10'], [], 10, 0, false);
        $this->assertEquals(1, $laptopSearch->count());

        $phoneSearch = $this->itemService->getAll([], ['keyword' => 'iPhone'], [], 10, 0, false);
        $this->assertEquals(1, $phoneSearch->count());

        $tabletSearch = $this->itemService->getAll([], ['keyword' => 'tablet'], [], 10, 0, false);
        $this->assertEquals(1, $tabletSearch->count());
    }

    public function test_get_all_with_buyer_seller_name()
    {
        CustomField::factory()->create([
            CustomField::moduleName => 'itm',
        ]);
        Category::factory()->create();
        LocationCity::factory()->create();
        ItemCurrency::factory()->create();
        $firstUser = User::factory()->create([
            User::name => 'First User',
        ]);
        $secondUser = User::factory()->create([
            User::name => 'Second User',
        ]);
        Item::factory()->count(19)->create();
        $firstItem = Item::factory()->create([
            Item::title => 'Asus Ryzen 10 Laptop',
        ]);
        $secondItem = Item::factory()->create([
            Item::title => 'iPhone 16',
        ]);
        UserBought::create([
            UserBought::itemId => $firstItem->id,
            UserBought::buyerUserId => $secondUser->id,
            UserBought::sellerUserId => $firstUser->id,
            UserBought::addedUserId => $firstUser->id,
        ]);
        UserBought::create([
            UserBought::itemId => $secondItem->id,
            UserBought::buyerUserId => $firstUser->id,
            UserBought::sellerUserId => $secondUser->id,
            UserBought::addedUserId => $secondUser->id,
        ]);

        $result = $this->itemService->getAll([], ['seller_buyer_name' => 'First User'], [], null, null, true);

        $this->assertEquals(2, $result->count());

        foreach ($result as $item) {
            if ($item->id == $firstItem->id) {
                $this->assertEquals($firstUser->name, $item->seller_name);
                $this->assertEquals($secondUser->name, $item->buyer_name);
            } else {
                $this->assertEquals($secondUser->name, $item->seller_name);
                $this->assertEquals($firstUser->name, $item->buyer_name);
            }
        }
    }

    public function test_get_all_with_filter_by_fields()
    {
        CustomField::factory()->create([
            CustomField::moduleName => 'itm',
        ]);
        Category::factory()->create();
        LocationCity::factory()->create();
        ItemCurrency::factory()->create();
        User::factory()->create();
        Item::factory()->count(19)->create();
        $item = Item::factory()->create([
            Item::description => 'this is a laptop description.',
            Item::dynamicLink => 'AB-1234',
        ]);

        $firstResult = $this->itemService->getAll([], [Item::description => 'this is a laptop description.'], [], null, null, true);
        $this->assertEquals(1, $firstResult->count());
        $this->assertEquals($item->id, $firstResult->first()->id);

        $secondResult = $this->itemService->getAll([], [Item::dynamicLink => 'AB-1234'], [], null, null, true);
        $this->assertEquals(1, $secondResult->count());
        $this->assertEquals($item->id, $secondResult->first()->id);
    }

    public function test_get_all_applies_price_filter()
    {
        CustomField::factory()->create([
            CustomField::moduleName => 'itm',
        ]);
        Category::factory()->create();
        LocationCity::factory()->create();
        ItemCurrency::factory()->create();
        User::factory()->create();
        Item::factory()->create([Item::price => 1000]);
        Item::factory()->create([Item::price => 5000]);
        Item::factory()->create([Item::price => 9000]);

        $result = $this->itemService->getAll([], ['min_price' => 1000, 'max_price' => 5000], [], null, null, true);

        foreach ($result as $item) {
            $this->assertGreaterThanOrEqual(1000, $item->price);
            $this->assertLessThanOrEqual(5000, $item->price);
        }
    }

    public function test_get_all_applies_added_date_filter()
    {
        Item::truncate();

        CustomField::factory()->create([
            CustomField::moduleName => 'itm',
        ]);
        User::factory()->create();
        Item::factory()->create([Item::addedDate => now()->subDays(2)]);
        Item::factory()->create([Item::addedDate => now()]);

        $firstResult = $this->itemService->getAll([], ['min_added_date' => now()->subDays(2)], [], null, null, true);
        $this->assertEquals(2, $firstResult->count());

        $secondResult = $this->itemService->getAll([], ['min_added_date' => now()->subDays(1)], [], null, null, true);
        $this->assertEquals(1, $secondResult->count());
    }

    public function test_get_all_applies_update_date_filter()
    {
        Item::truncate();

        CustomField::factory()->create([
            CustomField::moduleName => 'itm',
        ]);
        Category::factory()->create();
        LocationCity::factory()->create();
        ItemCurrency::factory()->create();
        User::factory()->create();
        Item::factory()->create([Item::updatedDate => now()->subDays(2)]);
        Item::factory()->create([Item::updatedDate => now()]);

        $firstResult = $this->itemService->getAll([], ['min_updated_date' => now()->subDays(2)], [], null, null, true);
        $this->assertEquals(2, $firstResult->count());

        $secondResult = $this->itemService->getAll([], ['min_updated_date' => now()->subDays(1)], [], null, null, true);
        $this->assertEquals(1, $secondResult->count());
    }

    public function test_get_all_applies_status_in_filter()
    {
        Item::truncate();

        CustomField::factory()->create([
            CustomField::moduleName => 'itm',
        ]);
        Category::factory()->create();
        LocationCity::factory()->create();
        ItemCurrency::factory()->create();
        User::factory()->create();
        Item::factory()->count(5)->create([Item::status => 0]);
        Item::factory()->count(10)->create([Item::status => 1]);

        $firstResult = $this->itemService->getAll([], ['status_in' => 0], [], null, null, true);
        $this->assertEquals(5, $firstResult->count());

        $secondResult = $this->itemService->getAll([], ['status_in' => 1], [], null, null, true);
        $this->assertEquals(10, $secondResult->count());
    }

    public function test_get_all_applies_infos_filter()
    {
        Item::truncate();

        $dropdownCustomField = CustomField::factory()->create([
            CustomField::coreKeysId => 'itm00001',
            CustomField::uiTypeId => Constants::dropDownUi,
            CustomField::moduleName => 'itm',
            CustomField::name => 'Dropdown',
        ]);
        $dropDownCustomFieldAttribute = CustomFieldAttribute::factory()->create([
            CustomFieldAttribute::coreKeysId => 'itm00001',
            CustomFieldAttribute::name => 'Dropdown Value 1',
        ]);

        $inputCustomField = CustomField::factory()->create([
            CustomField::coreKeysId => 'itm00002',
            CustomField::moduleName => 'itm',
            CustomField::name => 'Input',
            CustomField::uiTypeId => Constants::textUi,
        ]);

        Category::factory()->create();
        LocationCity::factory()->create();
        ItemCurrency::factory()->create();
        $user = User::factory()->create();
        Item::factory()->count(10)->create();
        $item = Item::factory()->create();
        ItemInfo::create([
            ItemInfo::itemId => $item->id,
            ItemInfo::coreKeysId => $dropdownCustomField->core_keys_id,
            ItemInfo::uiTypeId => $dropdownCustomField->ui_type_id,
            ItemInfo::value => $dropDownCustomFieldAttribute->id,
            ItemInfo::addedUserId => $user->id,
        ]);
        ItemInfo::create([
            ItemInfo::itemId => $item->id,
            ItemInfo::coreKeysId => $inputCustomField->core_keys_id,
            ItemInfo::uiTypeId => $inputCustomField->ui_type_id,
            ItemInfo::value => 'Item to Find',
            ItemInfo::addedUserId => $user->id,
        ]);

        $firstResult = $this->itemService->getAll([], ['infos_filter' => [$dropdownCustomField->core_keys_id => $dropDownCustomFieldAttribute->id]], [], null, null, true);
        $this->assertEquals(1, $firstResult->count());

        $secondResult = $this->itemService->getAll([], ['infos_filter' => [$inputCustomField->core_keys_id => 'find']], [], null, null, true);
        $this->assertEquals(1, $secondResult->count());

        $firstAndSecondCombined = $this->itemService->getAll([], ['infos_filter' => [$dropdownCustomField->core_keys_id => $dropDownCustomFieldAttribute->id, $inputCustomField->core_keys_id => 'find']], [], null, null, true);
        $this->assertEquals(1, $firstAndSecondCombined->count());

        $noResult = $this->itemService->getAll([], ['infos_filter' => [$dropdownCustomField->core_keys_id => $dropDownCustomFieldAttribute->id, $inputCustomField->core_keys_id => 'wrong value']], [], null, null, true);
        $this->assertEquals(0, $noResult->count());
    }

    public function test_get_all_applies_paid_item_time_stamp_filter()
    {
        Item::truncate();

        CustomField::factory()->create([
            CustomField::moduleName => 'itm',
        ]);
        Category::factory()->create();
        LocationCity::factory()->create();
        ItemCurrency::factory()->create();
        $user = User::factory()->create();
        Item::factory()->count(10)->create();
        $item = Item::factory()->create();
        PaidItemHistory::create([
            PaidItemHistory::itemId => $item->id,
            PaidItemHistory::startDate => now()->subDays(2),
            PaidItemHistory::endDate => now()->addMonths(6),
            PaidItemHistory::promotedDays => 180,
            PaidItemHistory::startTimestamp => now()->subDays(2)->unix(),
            PaidItemHistory::endTimestamp => now()->addMonths(6)->unix(),
            PaidItemHistory::amount => 100,
            PaidItemHistory::paymentMethod => 'stripe',
            PaidItemHistory::addedUserId => $user->id,
        ]);

        $firstResult = $this->itemService->getAll([], ['paid_item_histories_timestamp' => now()->unix()], [], null, null, true);
        $this->assertEquals(1, $firstResult->count());

        $secondResult = $this->itemService->getAll([], ['paid_item_histories_timestamp' => now()->subDays(3)->unix()], [], null, null, true);
        $this->assertEquals(0, $secondResult->count());
    }

    public function test_get_all_applies_block_user_not_in_filter()
    {
        Item::truncate();

        CustomField::factory()->create([
            CustomField::moduleName => 'itm',
        ]);
        Category::factory()->create();
        LocationCity::factory()->create();
        ItemCurrency::factory()->create();
        User::factory()->count(2)->create();
        $blockUser = User::factory()->create();
        Item::factory()->count(50)->create();

        $results = $this->itemService->getAll([], [], [], null, null, true, ['blockUserIds_not_in' => $blockUser->id]);
        foreach ($results as $item) {
            $this->assertNotEquals($blockUser->id, $item->added_user_id);
        }
    }

    public function test_get_all_applies_complaint_item_not_in_filter()
    {
        Item::truncate();

        CustomField::factory()->create([
            CustomField::moduleName => 'itm',
        ]);
        Category::factory()->create();
        LocationCity::factory()->create();
        ItemCurrency::factory()->create();
        User::factory()->count(2)->create();
        User::factory()->create();
        Item::factory()->count(10)->create();
        $firstComplaintItem = Item::factory()->create();
        $secondComplaintItem = Item::factory()->create();

        $firstComplaintItemSet = [$firstComplaintItem->id, $secondComplaintItem->id];
        $firstResult = $this->itemService->getAll([], [], [], null, null, true, ['complaintItemIds_not_in' => $firstComplaintItemSet]);
        $this->assertEquals(10, $firstResult->count());

        $secondComplaintItemSet = [$firstComplaintItem->id];
        $secondResult = $this->itemService->getAll([], [], [], null, null, true, ['complaintItemIds_not_in' => $secondComplaintItemSet]);
        $this->assertEquals(11, $secondResult->count());
    }

    public function test_get_all_applies_not_in_field_filter()
    {
        Item::truncate();

        CustomField::factory()->create([
            CustomField::moduleName => 'itm',
        ]);
        Category::factory()->create();
        LocationCity::factory()->create();
        ItemCurrency::factory()->create();
        User::factory()->count(2)->create();
        User::factory()->create();
        Item::factory()->count(10)->create();
        Item::factory()->create([
            Item::description => 'Test Description 1',
            Item::dynamicLink => 'AB-1234',
        ]);

        Item::factory()->create([
            Item::description => 'Test Description 2',
            Item::dynamicLink => 'BC-2345',
        ]);

        $firstResult = $this->itemService->getAll([], [], [], null, null, true, [Item::description => ['Test Description 1']]);
        $this->assertEquals(11, $firstResult->count());

        $secondResult = $this->itemService->getAll([], [], [], null, null, true, [Item::description => ['Test Description 1', 'Test Description 2']]);
        $this->assertEquals(10, $secondResult->count());

        $thirdResult = $this->itemService->getAll([], [], [], null, null, true, [Item::dynamicLink => ['AB-1234']]);
        $this->assertEquals(1, $thirdResult->count());

        $fourthResult = $this->itemService->getAll([], [], [], null, null, true, [Item::dynamicLink => ['BC-2345']]);
        $this->assertEquals(1, $fourthResult->count());

        $fifthResult = $this->itemService->getAll([], [], [], null, null, true, [Item::dynamicLink => ['BC-2345'], Item::description => ['Test Description 1']]);
        $this->assertEquals(0, $fifthResult->count());
    }

    public function test_get_all_order_by_category_name()
    {
        Item::truncate();

        CustomField::factory()->create([
            CustomField::moduleName => 'itm',
        ]);
        $firstCategory = Category::factory()->create([
            Category::name => 'A Category',
        ]);
        $secondCategory = Category::factory()->create([
            Category::name => 'B Category',
        ]);
        LocationCity::factory()->create();
        ItemCurrency::factory()->create();
        User::factory()->create();
        Item::factory()->count(5)->create([
            Item::categoryId => $firstCategory->id,
        ]);
        Item::factory()->count(5)->create([
            Item::categoryId => $secondCategory->id,
        ]);

        $firstResult = $this->itemService->getAll([], [], ['category_id@@name' => 'desc'], null, null, true);
        $this->assertEquals('B Category', $firstResult->first()->cat_name);

        $secondResult = $this->itemService->getAll([], [], ['category_id@@name' => 'asc'], null, null, true);
        $this->assertEquals('A Category', $secondResult->first()->cat_name);
    }

    public function test_get_all_order_by_sub_category_name()
    {
        Item::truncate();

        CustomField::factory()->create([
            CustomField::moduleName => 'itm',
        ]);

        $firstCategory = Category::factory()->create([
            Category::name => 'A Category',
        ]);
        $firstSubCategory = Subcategory::factory()->create([
            Subcategory::categoryId => $firstCategory->id,
            Subcategory::name => '1 Category',
        ]);
        $secondSubCategory = Subcategory::factory()->create([
            Subcategory::categoryId => $firstCategory->id,
            Subcategory::name => '2 Category',
        ]);

        LocationCity::factory()->create();
        ItemCurrency::factory()->create();
        User::factory()->create();
        Item::factory()->count(5)->create([
            Item::subCategoryId => $firstSubCategory->id,
        ]);
        Item::factory()->count(5)->create([
            Item::subCategoryId => $secondSubCategory->id,
        ]);

        $firstResult = $this->itemService->getAll([], [], ['subcategory_id@@name' => 'desc'], null, null, true);
        $this->assertEquals('2 Category', $firstResult->first()->sub_cat_name);

        $secondResult = $this->itemService->getAll([], [], ['subcategory_id@@name' => 'asc'], null, null, true);
        $this->assertEquals('1 Category', $secondResult->first()->sub_cat_name);
    }

    public function test_get_all_order_by_currency_name()
    {
        Item::truncate();

        CustomField::factory()->create([
            CustomField::moduleName => 'itm',
        ]);
        Category::factory()->create();
        LocationCity::factory()->create();
        $firstCurrency = ItemCurrency::factory()->create([
            ItemCurrency::currencyShortForm => 'A Currency',
        ]);
        $secondCurrency = ItemCurrency::factory()->create([
            ItemCurrency::currencyShortForm => 'B Currency',
        ]);
        User::factory()->create();
        Item::factory()->count(5)->create([
            Item::itemCurrencyId => $firstCurrency->id,
        ]);
        Item::factory()->count(5)->create([
            Item::itemCurrencyId => $secondCurrency->id,
        ]);

        $firstResult = $this->itemService->getAll([], [], ['currency_id@@currency_short_form' => 'desc'], null, null, true);
        $this->assertEquals('B Currency', $firstResult->first()->curr_short_form);

        $secondResult = $this->itemService->getAll([], [], ['currency_id@@currency_short_form' => 'asc'], null, null, true);
        $this->assertEquals('A Currency', $secondResult->first()->curr_short_form);
    }

    public function test_get_all_order_by_city_name()
    {
        Item::truncate();

        CustomField::factory()->create([
            CustomField::moduleName => 'itm',
        ]);
        Category::factory()->create();
        $firstLocation = LocationCity::factory()->create([
            LocationCity::name => 'A Location',
        ]);
        $secondLocation = LocationCity::factory()->create([
            LocationCity::name => 'B Location',
        ]);
        ItemCurrency::factory()->create();
        User::factory()->create();
        Item::factory()->count(5)->create([
            Item::itemLocationId => $firstLocation->id,
        ]);
        Item::factory()->count(5)->create([
            Item::itemLocationId => $secondLocation->id,
        ]);

        $firstResult = $this->itemService->getAll([], [], ['location_city_id@@name' => 'desc'], null, null, true);
        $this->assertEquals('B Location', $firstResult->first()->city_name);

        $secondResult = $this->itemService->getAll([], [], ['location_city_id@@name' => 'asc'], null, null, true);
        $this->assertEquals('A Location', $secondResult->first()->city_name);
    }

    public function test_get_all_order_by_township_name()
    {
        Item::truncate();

        CustomField::factory()->create([
            CustomField::moduleName => 'itm',
        ]);
        Category::factory()->create();
        $firstTownship = LocationTownship::factory()->create([
            LocationTownship::name => 'A Township',
        ]);
        $secondTownship = LocationTownship::factory()->create([
            LocationTownship::name => 'B Township',
        ]);
        ItemCurrency::factory()->create();
        User::factory()->create();
        Item::factory()->count(5)->create([
            Item::itemLocationTownshipId => $firstTownship->id,
        ]);
        Item::factory()->count(5)->create([
            Item::itemLocationTownshipId => $secondTownship->id,
        ]);

        $firstResult = $this->itemService->getAll([], [], ['location_township_id@@name' => 'desc'], null, null, true);
        $this->assertEquals('B Township', $firstResult->first()->township_name);

        $secondResult = $this->itemService->getAll([], [], ['location_township_id@@name' => 'asc'], null, null, true);
        $this->assertEquals('A Township', $secondResult->first()->township_name);
    }

    public function test_get_all_order_by_owner_name()
    {
        Item::truncate();

        CustomField::factory()->create([
            CustomField::moduleName => 'itm',
        ]);
        Category::factory()->create();
        LocationCity::factory()->create();
        ItemCurrency::factory()->create();
        $firstUser = User::factory()->create([
            User::name => 'A User',
        ]);
        $secondUser = User::factory()->create([
            User::name => 'B User',
        ]);
        Item::factory()->count(5)->create([
            Item::addedUserId => $firstUser->id,
        ]);
        Item::factory()->count(5)->create([
            Item::addedUserId => $secondUser->id,
        ]);

        $firstResult = $this->itemService->getAll([], [], ['added_user_id@@name' => 'desc'], null, null, true);
        $this->assertEquals('B User', $firstResult->first()->owner_name);

        $secondResult = $this->itemService->getAll([], [], ['added_user_id@@name' => 'asc'], null, null, true);
        $this->assertEquals('A User', $secondResult->first()->owner_name);
    }

    public function test_get_all_order_by_buyer_name()
    {
        CustomField::factory()->create([
            CustomField::moduleName => 'itm',
        ]);
        Category::factory()->create();
        LocationCity::factory()->create();
        ItemCurrency::factory()->create();
        $firstUser = User::factory()->create([
            User::name => 'A User',
        ]);
        $secondUser = User::factory()->create([
            User::name => 'B User',
        ]);
        $firstItem = Item::factory()->create();
        $secondItem = Item::factory()->create();

        UserBought::create([
            UserBought::itemId => $firstItem->id,
            UserBought::buyerUserId => $firstUser->id,
            UserBought::sellerUserId => $secondUser->id,
            UserBought::addedUserId => $firstUser->id,
        ]);

        UserBought::create([
            UserBought::itemId => $secondItem->id,
            UserBought::buyerUserId => $secondUser->id,
            UserBought::sellerUserId => $firstUser->id,
            UserBought::addedUserId => $secondUser->id,
        ]);

        $firstResult = $this->itemService->getAll([], [], ['buyer_user_id@@name' => 'desc'], null, null, true);
        $this->assertEquals('B User', $firstResult->first()->buyer_name);

        $secondResult = $this->itemService->getAll([], [], ['buyer_user_id@@name' => 'asc'], null, null, true);
        $this->assertEquals('A User', $secondResult->first()->buyer_name);
    }

    public function test_get_all_order_by_seller_name()
    {
        CustomField::factory()->create([
            CustomField::moduleName => 'itm',
        ]);
        Category::factory()->create();
        LocationCity::factory()->create();
        ItemCurrency::factory()->create();
        $firstUser = User::factory()->create([
            User::name => 'A User',
        ]);
        $secondUser = User::factory()->create([
            User::name => 'B User',
        ]);
        $firstItem = Item::factory()->create();
        $secondItem = Item::factory()->create();

        UserBought::create([
            UserBought::itemId => $firstItem->id,
            UserBought::buyerUserId => $firstUser->id,
            UserBought::sellerUserId => $secondUser->id,
            UserBought::addedUserId => $firstUser->id,
        ]);

        UserBought::create([
            UserBought::itemId => $secondItem->id,
            UserBought::buyerUserId => $secondUser->id,
            UserBought::sellerUserId => $firstUser->id,
            UserBought::addedUserId => $secondUser->id,
        ]);

        $firstResult = $this->itemService->getAll([], [], ['seller_user_id@@name' => 'desc'], null, null, true);
        $this->assertEquals('B User', $firstResult->first()->seller_name);

        $secondResult = $this->itemService->getAll([], [], ['seller_user_id@@name' => 'asc'], null, null, true);
        $this->assertEquals('A User', $secondResult->first()->seller_name);
    }

    public function test_get_all_order_by_fields()
    {
        Item::truncate();

        CustomField::factory()->create([
            CustomField::moduleName => 'itm',
        ]);
        Category::factory()->create();
        LocationCity::factory()->create();
        ItemCurrency::factory()->create();
        User::factory()->create();
        $first = Item::factory()->create([
            Item::addedDate => now()->subDays(1),
            Item::title => 'A Title',
        ]);
        $second = Item::factory()->create([
            Item::addedDate => now(),
            Item::title => 'B Title',
        ]);

        $firstResult = $this->itemService->getAll([], [], [Item::addedDate => 'desc'], null, null, true);
        $this->assertEquals($second->id, $firstResult->first()->id);

        $secondResult = $this->itemService->getAll([], [], [Item::title => 'desc'], null, null, true);
        $this->assertEquals('B Title', $secondResult->first()->title);

        $thirdResult = $this->itemService->getAll([], [], [Item::title => 'asc'], null, null, true);
        $this->assertEquals('A Title', $thirdResult->first()->title);
    }

    public function test_get_all_with_pagination_returns_paginator()
    {
        CustomField::factory()->create([
            CustomField::moduleName => 'itm',
        ]);
        Category::factory()->create();
        LocationCity::factory()->create();
        ItemCurrency::factory()->create();
        User::factory()->create();
        Item::factory()->count(20)->create();

        $result = $this->itemService->getAll([], [], [], 10, 0, false);

        $this->assertInstanceOf(LengthAwarePaginator::class, $result);
        $this->assertLessThanOrEqual(10, $result->count());
    }

    public function test_get_all_without_filters_returns_collection()
    {
        Item::truncate();

        CustomField::factory()->create([
            CustomField::moduleName => 'itm',
        ]);
        Category::factory()->create();
        LocationCity::factory()->create();
        ItemCurrency::factory()->create();
        User::factory()->create();
        Item::factory()->count(15)->create();

        $result = $this->itemService->getAll([], [], [], null, null, true);

        $this->assertInstanceOf(Collection::class, $result);
        $this->assertCount(15, $result);
    }

    // endregion

    // region save
    // -------------------------------------------------------------------
    // save
    // -------------------------------------------------------------------

    public function test_save_with_all_data()
    {
        // Create necessary test data
        $customField = CustomField::factory()->create([
            CustomField::moduleName => 'itm',
        ]);
        $category = Category::factory()->create();
        $locationCity = LocationCity::factory()->create();
        $itemCurrency = ItemCurrency::factory()->create();
        $user = User::factory()->create();

        // Fake filesystem
        Storage::fake('public');

        // Setup fake image and video file
        $imageName = 'test-image.jpg';
        $fakeImage = UploadedFile::fake()->image($imageName);
        $imageUploadPath = public_path('storage/uploads/items/');
        File::ensureDirectoryExists($imageUploadPath);
        $fakeImage->move($imageUploadPath, $imageName);

        $videoIconName = 'test-video-icon.jpg';
        $fakeVideoIcon = UploadedFile::fake()->image($videoIconName);
        $videoIconUploadPath = public_path('storage/'.Constants::folderPath.'/thumbnail/');
        File::ensureDirectoryExists($videoIconUploadPath);
        $fakeVideoIcon->move($videoIconUploadPath, $videoIconName);

        $videoName = 'test-video.mp4';
        $fakeVideo = UploadedFile::fake()->create($videoName, 5000, 'video/mp4');
        $videoUploadPath = public_path('storage/'.Constants::folderPath.'/uploads/');
        File::ensureDirectoryExists($videoUploadPath);
        $fakeVideo->move($videoUploadPath, $videoName);

        // Build item data
        $itemData = [
            Item::title => 'Test Item',
            Item::categoryId => $category->id,
            Item::itemCurrencyId => $itemCurrency->id,
            Item::itemLocationId => $locationCity->id,
            Item::price => 100,
            Item::originalPrice => 100,
            Item::description => 'Test Item Description',
            Item::searchterm => 'test, item, description',
            Item::lat => 16.8409,
            Item::lng => 96.1735,
            Item::status => 1,
            Item::percent => 0,
            Item::isPaid => 0,
            Item::vendorId => null,
            Item::addedUserId => $user->id,
            'images' => [$imageName],
            'img_caption' => [
                ['name' => $imageName, 'value' => 'Test Caption'],
            ],
            'img_order' => [
                ['name' => $imageName, 'order' => 1],
            ],
        ];

        $this->settingService->shouldReceive('get')
            ->with(null, Constants::SYSTEM_CONFIG)
            ->andReturn((object) [
                'setting' => json_encode([
                    'key' => 'value',
                ]),
            ]);

        $this->systemConfigService->shouldReceive('get')->andReturn([
            'is_approved_enable' => true,
        ]);

        $this->imageService->shouldReceive('saveDropzoneMultiImage')
            ->once()
            ->andReturnNull();

        // Mock image service calls
        $this->imageService->shouldReceive('save')
            ->once()
            ->andReturnNull();

        $this->imageService->shouldReceive('saveVideo')
            ->once()
            ->andReturnNull();

        // Mock dynamic link service calls
        $this->dynamicLinkService->shouldReceive('getDeepLinkServiceProvider')
            ->andReturnNull();

        // Mock item info service calls
        $this->itemInfoService->shouldReceive('save')
            ->once()
            ->withAnyArgs()
            ->andReturnUsing(function ($itemId, $relationalData) use ($customField, $user) {
                foreach ($relationalData as $core_keys_id => $value) {
                    ItemInfo::create([
                        ItemInfo::coreKeysId => $core_keys_id,
                        ItemInfo::value => $value,
                        ItemInfo::itemId => $itemId,
                        ItemInfo::uiTypeId => $customField->ui_type_id,
                        ItemInfo::addedUserId => $user->id,
                    ]);
                }
            });

        // Call the save method
        $relationalData = [
            $customField->core_keys_id => 'Item Info Value',
        ];
        $item = $this->itemService->save($itemData, $fakeVideoIcon, $fakeVideo, $relationalData);
        $itemInfoCount = ItemInfo::where(ItemInfo::itemId, $item->id)->count();

        // Assertions
        $this->assertInstanceOf(Item::class, $item);
        $this->assertEquals(1, $itemInfoCount);
        $this->assertEquals('Test Item', $item->title);
        $this->assertTrue(File::exists($imageUploadPath.$imageName));
        $this->assertTrue(File::exists($videoIconUploadPath.$videoIconName));
        $this->assertTrue(File::exists($videoUploadPath.$videoName));
    }

    public function test_save_without_image()
    {
        // Create necessary test data
        $customField = CustomField::factory()->create([
            CustomField::moduleName => 'itm',
        ]);
        $category = Category::factory()->create();
        $locationCity = LocationCity::factory()->create();
        $itemCurrency = ItemCurrency::factory()->create();
        $user = User::factory()->create();

        // Fake filesystem
        Storage::fake('public');

        // Setup fake image and video file
        $videoIconName = 'test-video-icon.jpg';
        $fakeVideoIcon = UploadedFile::fake()->image($videoIconName);
        $videoIconUploadPath = public_path('storage/'.Constants::folderPath.'/thumbnail/');
        File::ensureDirectoryExists($videoIconUploadPath);
        $fakeVideoIcon->move($videoIconUploadPath, $videoIconName);

        $videoName = 'test-video.mp4';
        $fakeVideo = UploadedFile::fake()->create($videoName, 5000, 'video/mp4');
        $videoUploadPath = public_path('storage/'.Constants::folderPath.'/uploads/');
        File::ensureDirectoryExists($videoUploadPath);
        $fakeVideo->move($videoUploadPath, $videoName);

        // Build item data
        $itemData = [
            Item::title => 'Test Item',
            Item::categoryId => $category->id,
            Item::itemCurrencyId => $itemCurrency->id,
            Item::itemLocationId => $locationCity->id,
            Item::price => 100,
            Item::originalPrice => 100,
            Item::description => 'Test Item Description',
            Item::searchterm => 'test, item, description',
            Item::lat => 16.8409,
            Item::lng => 96.1735,
            Item::status => 1,
            Item::percent => 0,
            Item::isPaid => 0,
            Item::vendorId => null,
            Item::addedUserId => $user->id,
        ];

        $this->settingService->shouldReceive('get')
            ->with(null, Constants::SYSTEM_CONFIG)
            ->andReturn((object) [
                'setting' => json_encode([
                    'key' => 'value',
                ]),
            ]);

        $this->systemConfigService->shouldReceive('get')->andReturn([
            'is_approved_enable' => true,
        ]);

        // Mock image service calls
        $this->imageService->shouldReceive('saveDropzoneMultiImage')
            ->once()
            ->andReturnNull();

        $this->imageService->shouldReceive('save')
            ->once()
            ->andReturnNull();

        $this->imageService->shouldReceive('saveVideo')
            ->once()
            ->andReturnNull();

        // Mock dynamic link service calls
        $this->dynamicLinkService->shouldReceive('getDeepLinkServiceProvider')
            ->andReturnNull();

        // Mock item info service calls
        $this->itemInfoService->shouldReceive('save')
            ->once()
            ->withAnyArgs()
            ->andReturnUsing(function ($itemId, $relationalData) use ($customField, $user) {
                foreach ($relationalData as $core_keys_id => $value) {
                    ItemInfo::create([
                        ItemInfo::coreKeysId => $core_keys_id,
                        ItemInfo::value => $value,
                        ItemInfo::itemId => $itemId,
                        ItemInfo::uiTypeId => $customField->ui_type_id,
                        ItemInfo::addedUserId => $user->id,
                    ]);
                }
            });

        // Call the save method
        $relationalData = [
            $customField->core_keys_id => 'Item Info Value',
        ];
        $item = $this->itemService->save($itemData, $fakeVideoIcon, $fakeVideo, $relationalData);
        $itemInfoCount = ItemInfo::where(ItemInfo::itemId, $item->id)->count();

        // Assertions
        $this->assertInstanceOf(Item::class, $item);
        $this->assertEquals(1, $itemInfoCount);
        $this->assertEquals('Test Item', $item->title);
        $this->assertTrue(File::exists($videoIconUploadPath.$videoIconName));
        $this->assertTrue(File::exists($videoUploadPath.$videoName));
    }

    public function test_save_without_video()
    {
        try {
            // Create necessary test data
            $customField = CustomField::factory()->create([
                CustomField::moduleName => 'itm',
            ]);
            $category = Category::factory()->create();
            $locationCity = LocationCity::factory()->create();
            $itemCurrency = ItemCurrency::factory()->create();
            $user = User::factory()->create();

            // Fake filesystem
            Storage::fake('public');

            // Setup fake image and video file
            $imageName = 'test-image.jpg';
            $fakeImage = UploadedFile::fake()->image($imageName);
            $imageUploadPath = public_path('storage/uploads/items/');
            File::ensureDirectoryExists($imageUploadPath);
            $fakeImage->move($imageUploadPath, $imageName);

            $videoIconName = 'test-video-icon.jpg';
            $fakeVideoIcon = UploadedFile::fake()->image($videoIconName);
            $videoIconUploadPath = public_path('storage/'.Constants::folderPath.'/thumbnail/');
            File::ensureDirectoryExists($videoIconUploadPath);
            $fakeVideoIcon->move($videoIconUploadPath, $videoIconName);

            // Build item data
            $itemData = [
                Item::title => 'Test Item',
                Item::categoryId => $category->id,
                Item::itemCurrencyId => $itemCurrency->id,
                Item::itemLocationId => $locationCity->id,
                Item::price => 100,
                Item::originalPrice => 100,
                Item::description => 'Test Item Description',
                Item::searchterm => 'test, item, description',
                Item::lat => 16.8409,
                Item::lng => 96.1735,
                Item::status => 1,
                Item::percent => 0,
                Item::isPaid => 0,
                Item::vendorId => null,
                Item::addedUserId => $user->id,
                'images' => [$imageName],
                'img_caption' => [
                    ['name' => $imageName, 'value' => 'Test Caption'],
                ],
                'img_order' => [
                    ['name' => $imageName, 'order' => 1],
                ],
            ];

            $this->settingService->shouldReceive('get')
                ->with(null, Constants::SYSTEM_CONFIG)
                ->andReturn((object) [
                    'setting' => json_encode([
                        'key' => 'value',
                    ]),
                ]);

            $this->systemConfigService->shouldReceive('get')->andReturn([
                'is_approved_enable' => true,
            ]);

            // Mock image service calls
            $this->imageService->shouldReceive('saveDropzoneMultiImage')
                ->once()
                ->andReturnNull();

            $this->imageService->shouldReceive('save')
                ->once()
                ->andReturnNull();

            // Mock dynamic link service calls
            $this->dynamicLinkService->shouldReceive('getDeepLinkServiceProvider')
                ->andReturnNull();

            // Mock item info service calls
            $this->itemInfoService->shouldReceive('save')
                ->once()
                ->withAnyArgs()
                ->andReturnUsing(function ($itemId, $relationalData) use ($customField, $user) {
                    foreach ($relationalData as $core_keys_id => $value) {
                        ItemInfo::create([
                            ItemInfo::coreKeysId => $core_keys_id,
                            ItemInfo::value => $value,
                            ItemInfo::itemId => $itemId,
                            ItemInfo::uiTypeId => $customField->ui_type_id,
                            ItemInfo::addedUserId => $user->id,
                        ]);
                    }
                });

            // Call the save method
            $video = null;
            $relationalData = [
                $customField->core_keys_id => 'Item Info Value',
            ];
            $item = $this->itemService->save($itemData, $fakeVideoIcon, $video, $relationalData);
            // dd($item, $itemData);
            $itemInfoCount = ItemInfo::where(ItemInfo::itemId, $item->id)->count();

            // Assertions
            $this->assertInstanceOf(Item::class, $item);
            $this->assertEquals(1, $itemInfoCount);
            $this->assertEquals('Test Item', $item->title);
            $this->assertTrue(File::exists($imageUploadPath.$imageName));
            $this->assertTrue(File::exists($videoIconUploadPath.$videoIconName));
        } catch (Exception $e) {
            $id = ! empty($itemData[Item::addedUserId]) ? $itemData[Item::addedUserId] : Auth::user()->id;
            dd($e, $itemData, $id);
        }
    }

    public function test_save_without_video_icon()
    {
        // Create necessary test data
        $customField = CustomField::factory()->create([
            CustomField::moduleName => 'itm',
        ]);
        $category = Category::factory()->create();
        $locationCity = LocationCity::factory()->create();
        $itemCurrency = ItemCurrency::factory()->create();
        $user = User::factory()->create();

        // Fake filesystem
        Storage::fake('public');

        // Setup fake image and video file
        $imageName = 'test-image.jpg';
        $fakeImage = UploadedFile::fake()->image($imageName);
        $imageUploadPath = public_path('storage/uploads/items/');
        File::ensureDirectoryExists($imageUploadPath);
        $fakeImage->move($imageUploadPath, $imageName);

        $videoName = 'test-video.mp4';
        $fakeVideo = UploadedFile::fake()->create($videoName, 5000, 'video/mp4');
        $videoUploadPath = public_path('storage/'.Constants::folderPath.'/uploads/');
        File::ensureDirectoryExists($videoUploadPath);
        $fakeVideo->move($videoUploadPath, $videoName);

        // Build item data
        $itemData = [
            Item::title => 'Test Item',
            Item::categoryId => $category->id,
            Item::itemCurrencyId => $itemCurrency->id,
            Item::itemLocationId => $locationCity->id,
            Item::price => 100,
            Item::originalPrice => 100,
            Item::description => 'Test Item Description',
            Item::searchterm => 'test, item, description',
            Item::lat => 16.8409,
            Item::lng => 96.1735,
            Item::status => 1,
            Item::percent => 0,
            Item::isPaid => 0,
            Item::vendorId => null,
            Item::addedUserId => $user->id,
            'images' => [$imageName],
            'img_caption' => [
                ['name' => $imageName, 'value' => 'Test Caption'],
            ],
            'img_order' => [
                ['name' => $imageName, 'order' => 1],
            ],
        ];

        $this->settingService->shouldReceive('get')
            ->with(null, Constants::SYSTEM_CONFIG)
            ->andReturn((object) [
                'setting' => json_encode([
                    'key' => 'value',
                ]),
            ]);

        $this->systemConfigService->shouldReceive('get')->andReturn([
            'is_approved_enable' => true,
        ]);

        // Mock image service calls
        $this->imageService->shouldReceive('saveDropzoneMultiImage')
            ->once()
            ->andReturnNull();

        $this->imageService->shouldReceive('saveVideo')
            ->once()
            ->andReturn('video-names');

        // Mock dynamic link service calls
        $this->dynamicLinkService->shouldReceive('getDeepLinkServiceProvider')
            ->andReturnNull();

        // Mock item info service calls
        $this->itemInfoService->shouldReceive('save')
            ->once()
            ->withAnyArgs()
            ->andReturnUsing(function ($itemId, $relationalData) use ($customField, $user) {
                foreach ($relationalData as $core_keys_id => $value) {
                    ItemInfo::create([
                        ItemInfo::coreKeysId => $core_keys_id,
                        ItemInfo::value => $value,
                        ItemInfo::itemId => $itemId,
                        ItemInfo::uiTypeId => $customField->ui_type_id,
                        ItemInfo::addedUserId => $user->id,
                    ]);
                }
            });

        // Call the save method
        $videoIcon = null;
        $relationalData = [
            $customField->core_keys_id => 'Item Info Value',
        ];
        $item = $this->itemService->save($itemData, $videoIcon, $fakeVideo, $relationalData);
        $itemInfoCount = ItemInfo::where(ItemInfo::itemId, $item->id)->count();

        // Assertions
        $this->assertInstanceOf(Item::class, $item);
        $this->assertEquals(1, $itemInfoCount);
        $this->assertEquals('Test Item', $item->title);
        $this->assertTrue(File::exists($imageUploadPath.$imageName));
        $this->assertTrue(File::exists($videoUploadPath.$videoName));
    }

    public function test_save_without_video_and_video_icon()
    {
        // Create necessary test data
        $customField = CustomField::factory()->create([
            CustomField::moduleName => 'itm',
        ]);
        $category = Category::factory()->create();
        $locationCity = LocationCity::factory()->create();
        $itemCurrency = ItemCurrency::factory()->create();
        $user = User::factory()->create();

        // Fake filesystem
        Storage::fake('public');

        // Setup fake image file
        $imageName = 'test-image.jpg';
        $fakeImage = UploadedFile::fake()->image($imageName);
        $imageUploadPath = public_path('storage/uploads/items/');
        File::ensureDirectoryExists($imageUploadPath);
        $fakeImage->move($imageUploadPath, $imageName);

        // Build item data
        $itemData = [
            Item::title => 'Test Item',
            Item::categoryId => $category->id,
            Item::itemCurrencyId => $itemCurrency->id,
            Item::itemLocationId => $locationCity->id,
            Item::price => 100,
            Item::originalPrice => 100,
            Item::description => 'Test Item Description',
            Item::searchterm => 'test, item, description',
            Item::lat => 16.8409,
            Item::lng => 96.1735,
            Item::status => 1,
            Item::percent => 0,
            Item::isPaid => 0,
            Item::vendorId => null,
            Item::addedUserId => $user->id,
            'images' => [$imageName],
            'img_caption' => [
                ['name' => $imageName, 'value' => 'Test Caption'],
            ],
            'img_order' => [
                ['name' => $imageName, 'order' => 1],
            ],
        ];

        $this->settingService->shouldReceive('get')
            ->with(null, Constants::SYSTEM_CONFIG)
            ->andReturn((object) [
                'setting' => json_encode([
                    'key' => 'value',
                ]),
            ]);

        $this->systemConfigService->shouldReceive('get')->andReturn([
            'is_approved_enable' => true,
        ]);

        // Mock image service calls
        $this->imageService->shouldReceive('saveDropzoneMultiImage')
            ->once()
            ->andReturnNull();

        $this->dynamicLinkService->shouldReceive('getDeepLinkServiceProvider')
            ->andReturnNull();

        $this->itemInfoService->shouldReceive('save')
            ->once()
            ->withAnyArgs()
            ->andReturnUsing(function ($itemId, $relationalData) use ($customField, $user) {
                foreach ($relationalData as $core_keys_id => $value) {
                    ItemInfo::create([
                        ItemInfo::coreKeysId => $core_keys_id,
                        ItemInfo::value => $value,
                        ItemInfo::itemId => $itemId,
                        ItemInfo::uiTypeId => $customField->ui_type_id,
                        ItemInfo::addedUserId => $user->id,
                    ]);
                }
            });

        // dd($customField);
        // Call the save method
        $video = null;
        $videoIcon = null;
        $relationalData = [
            $customField->core_keys_id => 'Item Info Value',
        ];
        $item = $this->itemService->save($itemData, $videoIcon, $video, $relationalData);
        $itemInfoCount = ItemInfo::where(ItemInfo::itemId, $item->id)->count();

        // Assertions
        $this->assertInstanceOf(Item::class, $item);
        $this->assertEquals(1, $itemInfoCount);
        $this->assertEquals('Test Item', $item->title);
        $this->assertTrue(File::exists($imageUploadPath.$imageName));
    }

    public function test_save_without_video_video_icon_and_image()
    {
        // Create necessary test data
        $customField = CustomField::factory()->create([
            CustomField::moduleName => 'itm',
        ]);
        $category = Category::factory()->create();
        $locationCity = LocationCity::factory()->create();
        $itemCurrency = ItemCurrency::factory()->create();
        $user = User::factory()->create();

        // Build item data
        $itemData = [
            Item::title => 'Test Item',
            Item::categoryId => $category->id,
            Item::itemCurrencyId => $itemCurrency->id,
            Item::itemLocationId => $locationCity->id,
            Item::price => 100,
            Item::originalPrice => 100,
            Item::description => 'Test Item Description',
            Item::searchterm => 'test, item, description',
            Item::lat => 16.8409,
            Item::lng => 96.1735,
            Item::status => 1,
            Item::percent => 0,
            Item::isPaid => 0,
            Item::vendorId => null,
            Item::addedUserId => $user->id,
        ];

        $this->settingService->shouldReceive('get')
            ->with(null, Constants::SYSTEM_CONFIG)
            ->andReturn((object) [
                'setting' => json_encode([
                    'key' => 'value',
                ]),
            ]);

        $this->systemConfigService->shouldReceive('get')->andReturn([
            'is_approved_enable' => true,
        ]);

        // Mock image service calls
        $this->imageService->shouldReceive('saveDropzoneMultiImage')
            ->once()
            ->andReturnNull();

        $this->dynamicLinkService->shouldReceive('getDeepLinkServiceProvider')
            ->andReturnNull();

        $this->itemInfoService->shouldReceive('save')
            ->once()
            ->withAnyArgs()
            ->andReturnUsing(function ($itemId, $relationalData) use ($customField, $user) {
                foreach ($relationalData as $core_keys_id => $value) {
                    ItemInfo::create([
                        ItemInfo::coreKeysId => $core_keys_id,
                        ItemInfo::value => $value,
                        ItemInfo::itemId => $itemId,
                        ItemInfo::uiTypeId => $customField->ui_type_id,
                        ItemInfo::addedUserId => $user->id,
                    ]);
                }
            });

        // Call the save method
        $video = null;
        $videoIcon = null;
        $relationalData = [
            $customField->core_keys_id => 'Item Info Value',
        ];
        $item = $this->itemService->save($itemData, $videoIcon, $video, $relationalData);
        $itemInfoCount = ItemInfo::where(ItemInfo::itemId, $item->id)->count();

        // Assertions
        $this->assertInstanceOf(Item::class, $item);
        $this->assertEquals(1, $itemInfoCount);
        $this->assertEquals('Test Item', $item->title);
    }

    public function test_save_clears_cache_three_times()
    {
        PsCache::shouldReceive('clear')->once()->with(ItemCache::BASE);
        PsCache::shouldReceive('clear')->once()->with(CategoryCache::BASE);
        PsCache::shouldReceive('clear')->once()->with(VendorCache::BASE);

        // Create necessary test data
        $customField = CustomField::factory()->create([
            CustomField::moduleName => 'itm',
        ]);
        $category = Category::factory()->create();
        $locationCity = LocationCity::factory()->create();
        $itemCurrency = ItemCurrency::factory()->create();
        $user = User::factory()->create();

        // Build item data
        $itemData = [
            Item::title => 'Test Item',
            Item::categoryId => $category->id,
            Item::itemCurrencyId => $itemCurrency->id,
            Item::itemLocationId => $locationCity->id,
            Item::price => 100,
            Item::originalPrice => 100,
            Item::description => 'Test Item Description',
            Item::searchterm => 'test, item, description',
            Item::lat => 16.8409,
            Item::lng => 96.1735,
            Item::status => 1,
            Item::percent => 0,
            Item::isPaid => 0,
            Item::vendorId => null,
            Item::addedUserId => $user->id,
        ];

        $this->settingService->shouldReceive('get')
            ->with(null, Constants::SYSTEM_CONFIG)
            ->andReturn((object) [
                'setting' => json_encode([
                    'key' => 'value',
                ]),
            ]);

        $this->systemConfigService->shouldReceive('get')->andReturn([
            'is_approved_enable' => true,
        ]);

        // Mock image service calls
        $this->imageService->shouldReceive('saveDropzoneMultiImage')
            ->once()
            ->andReturnNull();

        $this->dynamicLinkService->shouldReceive('getDeepLinkServiceProvider')
            ->andReturnNull();

        $this->itemInfoService->shouldReceive('save')
            ->once()
            ->withAnyArgs()
            ->andReturnUsing(function ($itemId, $relationalData) use ($customField, $user) {
                foreach ($relationalData as $core_keys_id => $value) {
                    ItemInfo::create([
                        ItemInfo::coreKeysId => $core_keys_id,
                        ItemInfo::value => $value,
                        ItemInfo::itemId => $itemId,
                        ItemInfo::uiTypeId => $customField->ui_type_id,
                        ItemInfo::addedUserId => $user->id,
                    ]);
                }
            });

        // Call the save method
        $video = null;
        $videoIcon = null;
        $relationalData = [
            $customField->core_keys_id => 'Item Info Value',
        ];
        $this->itemService->save($itemData, $videoIcon, $video, $relationalData);

        $this->assertTrue(true);
    }
    // endregion

    // region updateItemInfo
    // -------------------------------------------------------------------
    // updateItemInfo
    // -------------------------------------------------------------------
    public function test_update_item_info()
    {
        $relationalData = [
            'itm00001' => 'Test 1',
        ];
        $mockCustomField = [
            [ItemInfo::coreKeysId => 'itm00001'],
            [ItemInfo::coreKeysId => 'itm00002'],
        ];

        $this->customFieldService
            ->shouldReceive('getAll')
            ->withAnyArgs()
            ->andReturn(collect($mockCustomField));

        $this->itemInfoService
            ->shouldReceive('update')
            ->with(1, array_merge($relationalData, ['itm00002' => null]));

        $this->itemService->updateItemInfo(1, $relationalData);
        $this->assertTrue(true);
    }
}
