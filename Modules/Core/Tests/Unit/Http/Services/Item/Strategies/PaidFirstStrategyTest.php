<?php

namespace Tests\Unit\Services\Item\Strategies;

use App\Http\Contracts\Configuration\SystemConfigServiceInterface;
use App\Http\Contracts\Item\SearchItemServiceInterface;
use Illuminate\Support\Collection;
use Mockery;
use Modules\Core\Constants\Constants;
use Modules\Core\DTOs\Item\SearchItemDto;
use Modules\Core\Http\Services\Item\ItemService;
use Modules\Core\Http\Services\Item\Strategies\PaidFirstStrategy;
use Tests\TestCase;

class PaidFirstStrategyTest extends TestCase
{
    protected $itemService;

    protected $searchItemService;

    protected $systemConfigService;

    protected $paidFirstStrategy;

    protected function setUp(): void
    {
        parent::setUp();

        $this->itemService = Mockery::mock(ItemService::class);
        $this->searchItemService = Mockery::mock(SearchItemServiceInterface::class);
        $this->systemConfigService = Mockery::mock(SystemConfigServiceInterface::class);

        $this->paidFirstStrategy = new PaidFirstStrategy(
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

    // There is Normal Item and Paid Item data
    public function test_get_all_returns_merged_paid_and_normal_items()
    {
        // Setup DTO
        $dto = new SearchItemDto(
            loginUserId: '1',
            limit: 3,
            offset: 0,
            sorting: ['added_date' => 'desc'],
            relation: ['category'],
            filters: ['category_id' => 1]
        );

        // Mock system config
        $systemConfig = (object) ['is_block_user' => 1];
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

        // Mock paid items (2 items)
        $paidItems = collect([
            (object) ['id' => 1, 'title' => 'Paid 1', 'is_paid' => 1],
            (object) ['id' => 2, 'title' => 'Paid 2', 'is_paid' => 1],
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

        // Mock normal items (1 item to fill remaining limit)
        $normalItems = collect([
            (object) ['id' => 3, 'title' => 'Normal 1', 'is_paid' => 0],
        ]);

        $this->itemService->shouldReceive('getAll')
            ->with(
                $dto->relation,
                $dto->filters,
                $dto->sorting,
                1, // Remaining limit (3 total - 2 paid)
                0, // Adjusted offset
                Constants::yes,
                $normalFiltersNotIn
            )
            ->andReturn($normalItems);

        // Execute
        $result = $this->paidFirstStrategy->getAll($dto);

        // Verify
        $this->assertCount(3, $result);
        $this->assertEquals(1, $result[0]->is_paid); // First item is paid
        $this->assertEquals(1, $result[1]->is_paid); // Second item is paid
        $this->assertEquals(0, $result[2]->is_paid); // Third item is normal
        $this->assertEquals([1, 2, 3], $result->pluck('id')->toArray());
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

        // Mock system config
        $systemConfig = (object) ['is_block_user' => 1];
        $this->systemConfigService->shouldReceive('get')->andReturn($systemConfig);

        // Mock paid item filters
        $paidItemFilters = [
            'category_id' => 1,
            'is_paid' => Constants::yes,
            'paid_item_histories_timestamp' => getRoundedFiveMinuteTimestamp(),
            'paid_item_histories_deleted_at' => null,
        ];

        $filtersNotIn = ['blockUserIds_not_in' => ['u1']];
        $normalFiltersNotIn = array_merge($filtersNotIn, ['id' => [1, 2, 3]]); // All paid item IDs excluded

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

        // Mock empty normal items response
        $this->itemService->shouldReceive('getAll')
            ->with(
                $dto->relation,
                $dto->filters,
                $dto->sorting,
                1, // Remaining limit would be 0 (4 - 3 paid)
                0, // Adjusted offset
                Constants::yes,
                $normalFiltersNotIn
            )
            ->andReturn(collect([]));

        // Execute
        $result = $this->paidFirstStrategy->getAll($dto);

        // Verify
        $this->assertCount(3, $result);
        $this->assertTrue($result->every(fn ($item) => $item->is_paid === 1)); // All items are paid
        $this->assertEquals([1, 2, 3], $result->pluck('id')->toArray());
    }

    // There is only Normal Item data
    public function test_get_all_returns_only_normal_items_when_no_paid_items_exist()
    {
        // Setup DTO with basic parameters
        $dto = new SearchItemDto(
            loginUserId: '1',
            limit: 3,
            offset: 0,
            sorting: ['added_date' => 'desc'],
            relation: ['category'],
            filters: ['category_id' => 1]
        );

        // Mock system config
        $systemConfig = (object) ['is_block_user' => 1];
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

        // Mock empty paid items and some normal items
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
            ->andReturn(collect([])); // No paid items

        $normalItems = collect([
            (object) ['id' => 4, 'title' => 'Normal 1', 'is_paid' => 0],
            (object) ['id' => 5, 'title' => 'Normal 2', 'is_paid' => 0],
        ]);

        $this->itemService->shouldReceive('getAll')
            ->with(
                $dto->relation,
                $dto->filters,
                $dto->sorting,
                3, // Full limit since no paid items
                0, // Original offset
                Constants::yes,
                $normalFiltersNotIn
            )
            ->andReturn($normalItems);

        // Execute
        $result = $this->paidFirstStrategy->getAll($dto);

        // Verify
        $this->assertCount(2, $result);
        $this->assertEquals(0, $result->first()->is_paid); // All normal items
        $this->assertEquals(4, $result->first()->id);
    }

    // There is no Normal Item and Paid Item data
    public function test_get_all_returns_empty_collection_when_no_paid_and_no_normal_items_exist()
    {
        // Setup DTO with parameters that won't match any items
        $dto = new SearchItemDto(
            loginUserId: '1',
            limit: 3,
            offset: 0,
            sorting: ['added_date' => 'desc'],
            relation: ['category'],
            filters: ['category_id' => 999] // Non-existent category
        );

        // Mock system config
        $systemConfig = (object) ['is_block_user' => 1];
        $this->systemConfigService->shouldReceive('get')->andReturn($systemConfig);

        // Mock paid item filters
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

        // Mock empty paid items response
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

        // Mock empty normal items response
        $this->itemService->shouldReceive('getAll')
            ->with(
                $dto->relation,
                $dto->filters,
                $dto->sorting,
                3, // Full limit since no paid items
                0, // Original offset
                Constants::yes,
                $normalFiltersNotIn
            )
            ->andReturn(collect([]));

        // Execute
        $result = $this->paidFirstStrategy->getAll($dto);

        // Verify
        $this->assertCount(0, $result);
        $this->assertInstanceOf(Collection::class, $result);
        $this->assertTrue($result->isEmpty());
    }
}
