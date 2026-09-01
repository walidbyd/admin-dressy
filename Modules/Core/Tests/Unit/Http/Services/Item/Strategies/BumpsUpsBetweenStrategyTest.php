<?php

namespace Tests\Unit\Services\Item\Strategies;

use App\Http\Contracts\Configuration\SystemConfigServiceInterface;
use App\Http\Contracts\Item\SearchItemServiceInterface;
use Mockery;
use Modules\Core\Constants\Constants;
use Modules\Core\DTOs\Item\SearchItemDto;
use Modules\Core\Http\Services\Item\ItemService;
use Modules\Core\Http\Services\Item\Strategies\BumpsUpsBetweenStrategy;
use Tests\TestCase;

class BumpsUpsBetweenStrategyTest extends TestCase
{
    protected $itemService;

    protected $searchItemService;

    protected $systemConfigService;

    protected $bumpsUpBetweenStrategy;

    protected function setUp(): void
    {
        parent::setUp();

        $this->itemService = Mockery::mock(ItemService::class);
        $this->searchItemService = Mockery::mock(SearchItemServiceInterface::class);
        $this->systemConfigService = Mockery::mock(SystemConfigServiceInterface::class);

        $this->bumpsUpBetweenStrategy = new BumpsUpsBetweenStrategy(
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

    public function test_get_all_returns_merged_items_according_to_pattern()
    {
        // Setup DTO
        $dto = new SearchItemDto(
            loginUserId: '1',
            limit: 5,
            offset: 0,
            sorting: ['added_date' => 'desc'],
            relation: ['category'],
            filters: ['category_id' => 1]
        );

        // Mock system config
        $systemConfig = (object) [
            'is_block_user' => 1,
            'promo_cell_interval_no' => 2,
        ];
        $this->systemConfigService->shouldReceive('get')->andReturn($systemConfig);

        // Mock limit/offset calculation
        $this->itemService->shouldReceive('calculateItemLimitAndOffset')
            ->with(5, 0, 2)
            ->andReturn([
                'paidLimit' => 1,
                'paidOffset' => 0,
                'normalLimit' => 4,
                'normalOffset' => 0,
            ]);

        // Mock pattern generation
        $this->itemService->shouldReceive('generateVisiblePatternArray')
            ->with(5, 0, 2)
            ->andReturn(['one', 'one', 'zero', 'one', 'one']);

        // Mock filter preparation
        $paidItemFilters = ['category_id' => 1, 'is_paid' => Constants::yes];
        $filtersNotIn = ['blockUserIds_not_in' => ['u1']];
        $normalFiltersNotIn = array_merge($filtersNotIn, ['id' => []]);

        $this->searchItemService->shouldReceive('preparePaidItemFiltersData')
            ->with($dto->filters)
            ->andReturn($paidItemFilters);
        $this->searchItemService->shouldReceive('prepareFiltersNotInData')
            ->with(1, '1', [])
            ->andReturn($filtersNotIn);
        $this->searchItemService->shouldReceive('prepareNormalItemFiltersNotInData')
            ->with($filtersNotIn)
            ->andReturn($normalFiltersNotIn);

        // Mock paid items
        $paidItems = collect([
            (object) ['id' => 101, 'title' => 'Paid Item 1', 'is_paid' => 1],
        ]);
        $this->itemService->shouldReceive('getAll')
            ->with(
                $dto->relation,
                $paidItemFilters,
                $dto->sorting,
                1, // totalPaidNeeded
                null,
                Constants::yes,
                $filtersNotIn
            )
            ->andReturn($paidItems);

        // Mock normal items
        $normalItems = collect([
            (object) ['id' => 201, 'title' => 'Normal Item 1', 'is_paid' => 0],
            (object) ['id' => 202, 'title' => 'Normal Item 2', 'is_paid' => 0],
            (object) ['id' => 203, 'title' => 'Normal Item 3', 'is_paid' => 0],
            (object) ['id' => 204, 'title' => 'Normal Item 4', 'is_paid' => 0],
        ]);
        $this->itemService->shouldReceive('getAll')
            ->with(
                $dto->relation,
                $dto->filters,
                $dto->sorting,
                4, // adjustedNormalLimit
                0, // adjustedNormalOffset
                Constants::yes,
                $normalFiltersNotIn
            )
            ->andReturn($normalItems);

        // Execute
        $result = $this->bumpsUpBetweenStrategy->getAll($dto);

        // Verify merged items follow pattern: normal, normal, paid, normal, normal
        $this->assertCount(5, $result);
        $this->assertEquals(201, $result[0]->id); // normal (one)
        $this->assertEquals(202, $result[1]->id); // normal (one)
        $this->assertEquals(101, $result[2]->id); // paid (zero)
        $this->assertEquals(203, $result[3]->id); // normal (one)
        $this->assertEquals(204, $result[4]->id); // normal (one)
    }

    public function test_get_all_handles_insufficient_paid_items()
    {
        // Setup DTO
        $dto = new SearchItemDto(
            loginUserId: '1',
            limit: 5,
            offset: 2,
            sorting: ['added_date' => 'desc'],
            relation: ['category'],
            filters: ['category_id' => 1]
        );

        // Mock system config
        $systemConfig = (object) [
            'is_block_user' => 1,
            'promo_cell_interval_no' => 2,
        ];
        $this->systemConfigService->shouldReceive('get')->andReturn($systemConfig);

        // Mock limit/offset calculation
        $this->itemService->shouldReceive('calculateItemLimitAndOffset')
            ->with(5, 2, 2)
            ->andReturn([
                'paidLimit' => 2,
                'paidOffset' => 1,
                'normalLimit' => 3,
                'normalOffset' => 2,
            ]);

        // Mock pattern generation
        $this->itemService->shouldReceive('generateVisiblePatternArray')
            ->with(5, 2, 2)
            ->andReturn(['zero', 'one', 'one', 'zero', 'one']);

        // Mock filter preparation
        $paidItemFilters = ['category_id' => 1, 'is_paid' => Constants::yes];
        $filtersNotIn = ['blockUserIds_not_in' => ['u1']];
        $normalFiltersNotIn = array_merge($filtersNotIn, ['id' => []]);

        $this->searchItemService->shouldReceive('preparePaidItemFiltersData')
            ->with($dto->filters)
            ->andReturn($paidItemFilters);
        $this->searchItemService->shouldReceive('prepareFiltersNotInData')
            ->with(1, '1', [])
            ->andReturn($filtersNotIn);
        $this->searchItemService->shouldReceive('prepareNormalItemFiltersNotInData')
            ->with($filtersNotIn)
            ->andReturn($normalFiltersNotIn);

        // Mock only 1 paid item (insufficient for the requested offset)
        $paidItems = collect([
            (object) ['id' => 101, 'title' => 'Paid Item 1', 'is_paid' => 1],
            (object) ['id' => 102, 'title' => 'Paid Item 2', 'is_paid' => 1],
        ]);
        $this->itemService->shouldReceive('getAll')
            ->with(
                $dto->relation,
                $paidItemFilters,
                $dto->sorting,
                3,
                null,
                Constants::yes,
                $filtersNotIn
            )
            ->andReturn($paidItems);

        // Mock normal items
        $normalItems = collect([
            (object) ['id' => 201, 'title' => 'Normal Item 1', 'is_paid' => 0],
            (object) ['id' => 202, 'title' => 'Normal Item 2', 'is_paid' => 0],
            (object) ['id' => 203, 'title' => 'Normal Item 3', 'is_paid' => 0],
            (object) ['id' => 204, 'title' => 'Normal Item 4', 'is_paid' => 0],
        ]);
        $this->itemService->shouldReceive('getAll')
            ->with(
                $dto->relation,
                $dto->filters,
                $dto->sorting,
                4,
                2,
                Constants::yes,
                $normalFiltersNotIn
            )
            ->andReturn($normalItems);

        // Execute
        $result = $this->bumpsUpBetweenStrategy->getAll($dto);

        $this->assertCount(5, $result);
        $this->assertEquals(102, $result[0]->id); // paid
        $this->assertEquals(201, $result[1]->id); // normal
        $this->assertEquals(202, $result[2]->id); // normal
        $this->assertEquals(203, $result[3]->id); // don't have enough paid, replaced with normal
        $this->assertEquals(204, $result[4]->id); // normal
    }

    public function test_get_all_handles_insufficient_normal_items()
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

        // Mock system config
        $systemConfig = (object) [
            'is_block_user' => 1,
            'promo_cell_interval_no' => 2,
        ];
        $this->systemConfigService->shouldReceive('get')->andReturn($systemConfig);

        // Mock limit/offset calculation
        $this->itemService->shouldReceive('calculateItemLimitAndOffset')
            ->with(6, 0, 2)
            ->andReturn([
                'paidLimit' => 2,
                'paidOffset' => 0,
                'normalLimit' => 4,
                'normalOffset' => 0,
            ]);

        // Mock pattern generation
        $this->itemService->shouldReceive('generateVisiblePatternArray')
            ->with(6, 0, 2)
            ->andReturn(['one', 'one', 'zero', 'one', 'one', 'zero']);

        // Mock filter preparation
        $paidItemFilters = ['category_id' => 1, 'is_paid' => Constants::yes];
        $filtersNotIn = ['blockUserIds_not_in' => ['u1']];
        $normalFiltersNotIn = array_merge($filtersNotIn, ['id' => []]);

        $this->searchItemService->shouldReceive('preparePaidItemFiltersData')
            ->with($dto->filters)
            ->andReturn($paidItemFilters);
        $this->searchItemService->shouldReceive('prepareFiltersNotInData')
            ->with(1, '1', [])
            ->andReturn($filtersNotIn);
        $this->searchItemService->shouldReceive('prepareNormalItemFiltersNotInData')
            ->with($filtersNotIn)
            ->andReturn($normalFiltersNotIn);

        // Mock Paid Item
        $paidItems = collect([
            (object) ['id' => 101, 'title' => 'Paid Item 1', 'is_paid' => 1],
            (object) ['id' => 102, 'title' => 'Paid Item 2', 'is_paid' => 1],
        ]);
        $this->itemService->shouldReceive('getAll')
            ->with(
                $dto->relation,
                $paidItemFilters,
                $dto->sorting,
                2,
                null,
                Constants::yes,
                $filtersNotIn
            )
            ->andReturn($paidItems);

        // Mock normal items
        $normalItems = collect([
            (object) ['id' => 201, 'title' => 'Normal Item 1', 'is_paid' => 0],
            (object) ['id' => 202, 'title' => 'Normal Item 2', 'is_paid' => 0],
            (object) ['id' => 203, 'title' => 'Normal Item 3', 'is_paid' => 0],
        ]);
        $this->itemService->shouldReceive('getAll')
            ->with(
                $dto->relation,
                $dto->filters,
                $dto->sorting,
                4,
                0,
                Constants::yes,
                $normalFiltersNotIn
            )
            ->andReturn($normalItems);

        // Execute
        $result = $this->bumpsUpBetweenStrategy->getAll($dto);

        $this->assertCount(5, $result);
        $this->assertEquals(201, $result[0]->id); // normal
        $this->assertEquals(202, $result[1]->id); // normal
        $this->assertEquals(101, $result[2]->id); // paid
        $this->assertEquals(203, $result[3]->id); // normal
        $this->assertEquals(102, $result[4]->id); // not enough normal, replaced with paid
    }

    public function test_get_all_returns_empty_when_no_items_exist()
    {
        // Setup DTO
        $dto = new SearchItemDto(
            loginUserId: '1',
            limit: 3,
            offset: 0,
            sorting: ['added_date' => 'desc'],
            relation: ['category'],
            filters: ['category_id' => 999] // Non-existent
        );

        // Mock system config
        $this->systemConfigService->shouldReceive('get')
            ->andReturn((object) ['is_block_user' => 1, 'promo_cell_interval_no' => 2]);

        // Mock limit/offset calculation
        $this->itemService->shouldReceive('calculateItemLimitAndOffset')
            ->with(3, 0, 2)
            ->andReturn([
                'paidLimit' => 1,
                'paidOffset' => 0,
                'normalLimit' => 2,
                'normalOffset' => 0,
            ]);

        // Mock pattern generation
        $this->itemService->shouldReceive('generateVisiblePatternArray')
            ->with(3, 0, 2)
            ->andReturn(['one', 'one', 'zero']);

        // Mock empty returns
        $paidItemFilters = ['category_id' => 1, 'is_paid' => Constants::yes];
        $filtersNotIn = ['blockUserIds_not_in' => ['u1']];
        $normalFiltersNotIn = array_merge($filtersNotIn, ['id' => []]);

        $this->searchItemService->shouldReceive('preparePaidItemFiltersData')
            ->with($dto->filters)
            ->andReturn($paidItemFilters);
        $this->searchItemService->shouldReceive('prepareFiltersNotInData')
            ->with(1, '1', [])
            ->andReturn($filtersNotIn);
        $this->searchItemService->shouldReceive('prepareNormalItemFiltersNotInData')
            ->with($filtersNotIn)
            ->andReturn($normalFiltersNotIn);

        // Mock Item Service call
        $this->itemService->shouldReceive('getAll')
            ->withAnyArgs()
            ->andReturn(collect([]));

        // Execute
        $result = $this->bumpsUpBetweenStrategy->getAll($dto);

        // Verify empty result
        $this->assertIsArray($result);
        $this->assertEmpty($result);
    }
}
