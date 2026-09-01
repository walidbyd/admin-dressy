<?php

namespace Tests\Unit\DTOs\Item;

use App\Http\Contracts\Configuration\MobileSettingServiceInterface;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\Request;
use Mockery;
use Modules\Core\Constants\Constants;
use Modules\Core\DTOs\Item\SearchItemDto;
use Modules\Core\Entities\Item\Item;
use Tests\TestCase;

class SearchItemDtoTest extends TestCase
{
    use DatabaseTransactions;

    protected $mobileSettingService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->mobileSettingService = Mockery::mock(MobileSettingServiceInterface::class);
        app()->instance(MobileSettingServiceInterface::class, $this->mobileSettingService);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    // region from
    // -------------------------------------------------------------------
    // from
    // -------------------------------------------------------------------

    public function test_from_creates_dto_with_basic_parameters()
    {
        $this->mobileSettingService->shouldReceive('get')
            ->once()
            ->andReturn((object) ['default_loading_limit' => 9]);

        $request = new Request([
            'cat_id' => '5',
            'limit' => '20',
            'offset' => '10',
            'order_by' => 'price',
            'searchterm' => 'test',
        ]);

        $dto = SearchItemDto::from($request, 'nologinuser', ['relation1', 'relation2']);

        $this->assertEquals('nologinuser', $dto->loginUserId);
        $this->assertEquals(20, $dto->limit);
        $this->assertEquals(10, $dto->offset);
        $this->assertEquals(['price' => Constants::descending], $dto->sorting);
        $this->assertEquals(['relation1', 'relation2'], $dto->relation);
        $this->assertEquals('test', $dto->filters['keyword']);
        $this->assertEquals(5, $dto->filters['category_id']);
    }

    public function test_from_uses_default_limit_from_settings()
    {
        $this->mobileSettingService->shouldReceive('get')
            ->once()
            ->andReturn((object) ['default_loading_limit' => 15]);

        $request = new Request;
        $dto = SearchItemDto::from($request, '1', []);

        $this->assertEquals(15, $dto->limit);
        $this->assertEquals(0, $dto->offset);
    }

    public function test_from_uses_fallback_limit_when_no_setting()
    {
        $this->mobileSettingService->shouldReceive('get')
            ->once()
            ->andReturn((object) ['other_setting' => 'value']);

        $request = new Request;
        $dto = SearchItemDto::from($request, '1', []);

        $this->assertEquals(9, $dto->limit);
        $this->assertEquals(0, $dto->offset);
    }

    public function test_from_uses_default_sorting_when_not_provided()
    {
        $this->mobileSettingService->shouldReceive('get')
            ->once()
            ->andReturn((object) ['default_loading_limit' => 9]);

        $request = new Request;
        $dto = SearchItemDto::from($request, '1', []);

        $this->assertEquals([], $dto->sorting);
    }

    public function test_from_uses_request_values_over_defaults()
    {
        $this->mobileSettingService->shouldReceive('get')
            ->once()
            ->andReturn((object) ['default_loading_limit' => 9]);

        $request = new Request([
            'limit' => '25',
            'offset' => '5',
            'order_by' => Item::status,
            'order_type' => Constants::descending,
        ]);
        $dto = SearchItemDto::from($request, '1', []);

        $this->assertEquals(25, $dto->limit);
        $this->assertEquals(5, $dto->offset);
        $this->assertEquals([Item::status => Constants::descending], $dto->sorting);
    }

    public function test_from_converts_all_filter_parameters()
    {
        $this->mobileSettingService->shouldReceive('get')
            ->once()
            ->andReturn((object) ['default_loading_limit' => 9]);

        $request = new Request([
            'cat_id' => '1',
            'sub_cat_id' => '2',
            'is_sold_out' => '1',
            'is_discount' => '0',
            'status' => 'active',
            'searchterm' => 'phone',
            'item_currency_id' => '3',
            'item_location_id' => '4',
            'item_location_township_id' => '5',
            'max_price' => '1000',
            'min_price' => '100',
            'lat' => '16.8409',
            'lng' => '96.1735',
            'miles' => '5',
            'added_user_id' => '6',
            'vendor_id' => '7',
            'product_relation' => [
                ['core_keys_id' => 'itm00001', 'value' => 'red'],
                ['core_keys_id' => 'itm00002', 'value' => 'large'],
            ],
            'product_not_in' => ['1'],
        ]);

        $dto = SearchItemDto::from($request, 1, []);

        $this->assertEquals([
            'login_user_id' => 1,
            'category_id' => '1',
            'subcategory_id' => '2',
            'is_sold_out' => '1',
            'is_discount' => '0',
            'status' => 'active',
            'keyword' => 'phone',
            'infos_filter' => ['itm00001' => 'red', 'itm00002' => 'large'],
            'currency_id' => 3,
            'location_city_id' => 4,
            'location_township_id' => 5,
            'max_price' => '1000',
            'min_price' => '100',
            'lat' => '16.8409',
            'lng' => '96.1735',
            'miles' => '5',
            'added_user_id' => '6',
            'vendor_id' => '7',
            'exclude_ids' => ['1'],
        ], $dto->filters);
    }

    public function test_from_handles_empty_product_relations()
    {
        $this->mobileSettingService->shouldReceive('get')
            ->once()
            ->andReturn((object) ['default_loading_limit' => 9]);

        $request = new Request;
        $dto = SearchItemDto::from($request, '1', []);

        $this->assertEquals([], $dto->filters['infos_filter']);
    }

    public function test_from_preserves_null_values()
    {
        $this->mobileSettingService->shouldReceive('get')
            ->once()
            ->andReturn((object) ['default_loading_limit' => 9]);

        $request = new Request([
            'cat_id' => null,
            'sub_cat_id' => null,
        ]);

        $dto = SearchItemDto::from($request, '1', []);

        $this->assertNull($dto->filters['category_id']);
        $this->assertNull($dto->filters['subcategory_id']);
    }

    public function test_from_handles_missing_parameters()
    {
        $this->mobileSettingService->shouldReceive('get')
            ->once()
            ->andReturn((object) ['default_loading_limit' => 9]);

        $request = new Request;
        $dto = SearchItemDto::from($request, '1', []);

        $this->assertArrayHasKey('login_user_id', $dto->filters);
        $this->assertNull($dto->filters['category_id']);
        $this->assertNull($dto->filters['subcategory_id']);
    }
    // endregion
}
