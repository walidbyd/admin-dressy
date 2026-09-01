<?php

namespace Tests\Unit\Services\Item\Strategies;

use App\Http\Contracts\Configuration\SystemConfigServiceInterface;
use App\Http\Contracts\Item\SearchItemServiceInterface;
use Mockery;
use Modules\Core\Constants\Constants;
use Modules\Core\DTOs\Item\SearchItemDto;
use Modules\Core\Http\Services\Item\ItemService;
use Modules\Core\Http\Services\Item\Strategies\PaidFirstWithGoogleStrategy;
use Tests\TestCase;

class PaidFirstWithGoogleStrategyTest extends TestCase
{
    protected $itemService;

    protected $searchItemService;

    protected $systemConfigService;

    protected $paidFirstWithGoogleStrategy;

    protected function setUp(): void
    {
        parent::setUp();

        $this->itemService = Mockery::mock(ItemService::class);
        $this->searchItemService = Mockery::mock(SearchItemServiceInterface::class);
        $this->systemConfigService = Mockery::mock(SystemConfigServiceInterface::class);

        $this->paidFirstWithGoogleStrategy = new PaidFirstWithGoogleStrategy(
            $this->itemService,
            $this->searchItemService,
            $this->systemConfigService
        );
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    // There are Normal Item and Paid Item data
    public function test_get_all_returns_merged_paid_normal_items_with_google_ads()
    {
        // Setup DTO
        $dto = new SearchItemDto(
            loginUserId: '1',
            limit: 6,
            offset: 0,
            sorting: ['added_date' => 'desc'],
            relation: ['category'],
            filters: ['category_id' => 1]
        );

        // Mock system config with ad interval
        $systemConfig = (object) [
            'is_block_user' => 1,
            'promo_cell_interval_no' => 3, // Show ad every 3 items
        ];
        $this->systemConfigService->shouldReceive('get')->andReturn($systemConfig);

        // Mock paid item filters
        $paidItemFilters = [
            'category_id' => 1,
            'is_paid' => Constants::yes,
            'paid_item_histories_timestamp' => getRoundedFiveMinuteTimestamp(),
            'paid_item_histories_deleted_at' => null,
        ];

        $filtersNotIn = ['blockUserIds_not_in' => ['u1']];
        $normalFiltersNotIn = array_merge($filtersNotIn, ['id' => [1, 2]]);

        // Mock service responses
        $this->searchItemService->shouldReceive('preparePaidItemFiltersData')
            ->with($dto->filters)
            ->andReturn($paidItemFilters);

        $this->searchItemService->shouldReceive('prepareFiltersNotInData')
            ->with($systemConfig->is_block_user, $dto->loginUserId, [])
            ->andReturn($filtersNotIn);

        $this->searchItemService->shouldReceive('prepareNormalItemFiltersNotInData')
            ->with($filtersNotIn)
            ->andReturn($normalFiltersNotIn);

        // Mock paid items (3 items)
        $paidItems = collect([
            (object) ['id' => 1, 'title' => 'Paid 1', 'is_paid' => 1],
            (object) ['id' => 2, 'title' => 'Paid 2', 'is_paid' => 1],
            (object) ['id' => 3, 'title' => 'Paid 3', 'is_paid' => 1],
        ]);

        $this->itemService->shouldReceive('getAll')
            ->with(
                $dto->relation,
                $paidItemFilters,
                $dto->sorting,
                null,
                null,
                Constants::yes,
                $filtersNotIn
            )
            ->andReturn($paidItems);

        // Mock pattern generation for remaining 3 normal items
        $this->itemService->shouldReceive('generateVisiblePatternArray')
            ->with(3, 0, 3)
            ->andReturn(['one', 'one', 'zero']);

        // Mock normal items (3 items to fill remaining limit)
        $normalItems = collect([
            (object) ['id' => 4, 'title' => 'Normal 1', 'is_paid' => 0],
            (object) ['id' => 5, 'title' => 'Normal 2', 'is_paid' => 0],
            (object) ['id' => 6, 'title' => 'Normal 3', 'is_paid' => 0],
        ]);

        $this->itemService->shouldReceive('getAll')
            ->with(
                $dto->relation,
                $dto->filters,
                $dto->sorting,
                3, // Remaining limit (6 total - 3 paid)
                0, // Adjusted offset
                Constants::yes,
                $normalFiltersNotIn
            )
            ->andReturn($normalItems);

        // Execute
        $result = $this->paidFirstWithGoogleStrategy->getAll($dto);

        // Verify
        $this->assertCount(6, $result);

        // Paid items come first
        $this->assertEquals(1, $result[0]->is_paid);
        $this->assertEquals(1, $result[1]->is_paid);
        $this->assertEquals(1, $result[2]->is_paid);

        // Then normal items with ads
        $this->assertEquals(0, $result[3]->is_paid);
        $this->assertEquals(0, $result[4]->is_paid);
        $this->assertEquals(Constants::googleAd, $result[5]->ad_type);
    }

    // There is only Normal Item data
    public function test_get_all_returns_normal_items_with_google_ads_when_no_paid_items_exist()
    {
        // Setup DTO with basic parameters
        $dto = new SearchItemDto(
            loginUserId: '1',
            limit: 5,
            offset: 0,
            sorting: ['added_date' => 'desc'],
            relation: ['category'],
            filters: ['category_id' => 1]
        );

        // Mock system config with ad interval
        $systemConfig = (object) [
            'is_block_user' => 1,
            'promo_cell_interval_no' => 2, // Show ad every 2 items
        ];
        $this->systemConfigService->shouldReceive('get')->andReturn($systemConfig);

        // Mock empty paid items response
        $paidItemFilters = [
            'category_id' => 1,
            'is_paid' => Constants::yes,
            'paid_item_histories_timestamp' => getRoundedFiveMinuteTimestamp(),
            'paid_item_histories_deleted_at' => null,
        ];

        $filtersNotIn = ['blockUserIds_not_in' => ['u1']];
        $normalFiltersNotIn = array_merge($filtersNotIn, ['id' => []]);

        // Mock service responses
        $this->searchItemService->shouldReceive('preparePaidItemFiltersData')
            ->with($dto->filters)
            ->andReturn($paidItemFilters);

        $this->searchItemService->shouldReceive('prepareFiltersNotInData')
            ->with($systemConfig->is_block_user, $dto->loginUserId, [])
            ->andReturn($filtersNotIn);

        $this->searchItemService->shouldReceive('prepareNormalItemFiltersNotInData')
            ->with($filtersNotIn)
            ->andReturn($normalFiltersNotIn);

        // Mock empty paid items
        $this->itemService->shouldReceive('getAll')
            ->with(
                $dto->relation,
                $paidItemFilters,
                $dto->sorting,
                null,
                null,
                Constants::yes,
                $filtersNotIn
            )
            ->andReturn(collect([]));

        // Mock pattern generation
        $this->itemService->shouldReceive('generateVisiblePatternArray')
            ->with(5, 0, 2)
            ->andReturn(['one', 'one', 'zero', 'one', 'zero']);

        // Mock normal items
        $normalItems = collect([
            (object) ['id' => 4, 'title' => 'Normal 1', 'is_paid' => 0],
            (object) ['id' => 5, 'title' => 'Normal 2', 'is_paid' => 0],
            (object) ['id' => 6, 'title' => 'Normal 3', 'is_paid' => 0],
        ]);

        $this->itemService->shouldReceive('getAll')
            ->with(
                $dto->relation,
                $dto->filters,
                $dto->sorting,
                5, // Full limit since no paid items
                0, // Original offset
                Constants::yes,
                $normalFiltersNotIn
            )
            ->andReturn($normalItems);

        // Execute
        $result = $this->paidFirstWithGoogleStrategy->getAll($dto);

        // Verify
        $this->assertCount(5, $result);
        $this->assertEquals(0, $result[0]->is_paid ?? null); // Normal item
        $this->assertEquals(0, $result[1]->is_paid ?? null); // Normal item
        $this->assertEquals(Constants::googleAd, $result[2]->ad_type); // Ad
        $this->assertEquals(0, $result[3]->is_paid ?? null); // Normal item
        $this->assertEquals(Constants::googleAd, $result[4]->ad_type); // Ad
    }

    // There is only Paid Item data
    public function test_get_all_returns_only_paid_items_when_no_normal_items_exist()
    {
        // Setup DTO
        $dto = new SearchItemDto(
            loginUserId: '1',
            limit: 4,
            offset: 0,
            sorting: ['added_date' => 'desc'],
            relation: ['category'],
            filters: ['category_id' => 1]
        );

        // Mock system config with ad interval
        $systemConfig = (object) [
            'is_block_user' => 1,
            'promo_cell_interval_no' => 2,
        ];
        $this->systemConfigService->shouldReceive('get')->andReturn($systemConfig);

        // Mock paid item filters
        $paidItemFilters = [
            'category_id' => 1,
            'is_paid' => Constants::yes,
            'paid_item_histories_timestamp' => getRoundedFiveMinuteTimestamp(),
            'paid_item_histories_deleted_at' => null,
        ];

        $filtersNotIn = ['blockUserIds_not_in' => ['u1']];
        $normalFiltersNotIn = array_merge($filtersNotIn, ['id' => [1, 2, 3, 4]]);

        // Mock service responses
        $this->searchItemService->shouldReceive('preparePaidItemFiltersData')
            ->with($dto->filters)
            ->andReturn($paidItemFilters);

        $this->searchItemService->shouldReceive('prepareFiltersNotInData')
            ->with($systemConfig->is_block_user, $dto->loginUserId, [])
            ->andReturn($filtersNotIn);

        $this->searchItemService->shouldReceive('prepareNormalItemFiltersNotInData')
            ->with($filtersNotIn)
            ->andReturn($normalFiltersNotIn);

        // Mock paid items (4 items)
        $paidItems = collect([
            (object) ['id' => 1, 'title' => 'Paid 1', 'is_paid' => 1],
            (object) ['id' => 2, 'title' => 'Paid 2', 'is_paid' => 1],
            (object) ['id' => 3, 'title' => 'Paid 3', 'is_paid' => 1],
            (object) ['id' => 4, 'title' => 'Paid 4', 'is_paid' => 1],
        ]);

        $this->itemService->shouldReceive('getAll')
            ->with(
                $dto->relation,
                $paidItemFilters,
                $dto->sorting,
                null,
                null,
                Constants::yes,
                $filtersNotIn
            )
            ->andReturn($paidItems);

        // Mock pattern generation
        $this->itemService->shouldReceive('generateVisiblePatternArray')
            ->with(0, 0, 2)
            ->andReturn([]);

        // Mock empty normal items response
        $this->itemService->shouldReceive('getAll')
            ->with(
                $dto->relation,
                $dto->filters,
                $dto->sorting,
                0, // Remaining limit would be 0 (4 - 4 paid)
                0,
                Constants::yes,
                $normalFiltersNotIn
            )
            ->andReturn(collect([]));

        // Execute
        $result = $this->paidFirstWithGoogleStrategy->getAll($dto);

        // Verify
        $this->assertCount(4, $result);
        $this->assertTrue($result->every(fn ($item) => $item->is_paid === 1)); // All paid
        $this->assertEquals([1, 2, 3, 4], $result->pluck('id')->toArray());
    }

    // There is no Normal Item and Paid Item data
    public function test_get_all_returns_empty_when_no_items_exist()
    {
        // Setup DTO
        $dto = new SearchItemDto(
            loginUserId: '1',
            limit: 3,
            offset: 0,
            sorting: ['added_date' => 'desc'],
            relation: ['category'],
            filters: ['category_id' => 999] // Non-existent category
        );

        // Mock system config
        $systemConfig = (object) [
            'is_block_user' => 1,
            'promo_cell_interval_no' => 2,
        ];
        $this->systemConfigService->shouldReceive('get')->andReturn($systemConfig);

        // Mock empty paid items response
        $paidItemFilters = [
            'category_id' => 999,
            'is_paid' => Constants::yes,
            'paid_item_histories_timestamp' => getRoundedFiveMinuteTimestamp(),
            'paid_item_histories_deleted_at' => null,
        ];

        $filtersNotIn = ['blockUserIds_not_in' => ['u1']];
        $normalFiltersNotIn = array_merge($filtersNotIn, ['id' => []]);

        // Mock service responses
        $this->searchItemService->shouldReceive('preparePaidItemFiltersData')
            ->with($dto->filters)
            ->andReturn($paidItemFilters);

        $this->searchItemService->shouldReceive('prepareFiltersNotInData')
            ->with($systemConfig->is_block_user, $dto->loginUserId, [])
            ->andReturn($filtersNotIn);

        $this->searchItemService->shouldReceive('prepareNormalItemFiltersNotInData')
            ->with($filtersNotIn)
            ->andReturn($normalFiltersNotIn);

        // Mock empty paid items
        $this->itemService->shouldReceive('getAll')
            ->with(
                $dto->relation,
                $paidItemFilters,
                $dto->sorting,
                null,
                null,
                Constants::yes,
                $filtersNotIn
            )
            ->andReturn(collect([]));

        // Mock pattern generation
        $this->itemService->shouldReceive('generateVisiblePatternArray')
            ->with(3, 0, 2)
            ->andReturn([]);

        // Mock empty normal items
        $this->itemService->shouldReceive('getAll')
            ->with(
                $dto->relation,
                $dto->filters,
                $dto->sorting,
                3, // Full limit
                0,
                Constants::yes,
                $normalFiltersNotIn
            )
            ->andReturn(collect([]));

        // Execute
        $result = $this->paidFirstWithGoogleStrategy->getAll($dto);

        // Verify
        $this->assertCount(0, $result);
        $this->assertEmpty($result);
    }
}
