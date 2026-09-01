<?php

namespace Tests\Unit\Services\Item\Strategies;

use App\Http\Contracts\Configuration\SystemConfigServiceInterface;
use App\Http\Contracts\Item\SearchItemServiceInterface;
use Mockery;
use Modules\Core\Constants\Constants;
use Modules\Core\DTOs\Item\SearchItemDto;
use Modules\Core\Http\Services\Item\ItemService;
use Modules\Core\Http\Services\Item\Strategies\GoogleAdsBetweenStrategy;
use Tests\TestCase;

class GoogleAdsBetweenStrategyTest extends TestCase
{
    protected $itemService;

    protected $searchItemService;

    protected $systemConfigService;

    protected $googleAdsBetweenStrategy;

    protected function setUp(): void
    {
        parent::setUp();

        $this->itemService = Mockery::mock(ItemService::class);
        $this->searchItemService = Mockery::mock(SearchItemServiceInterface::class);
        $this->systemConfigService = Mockery::mock(SystemConfigServiceInterface::class);

        $this->googleAdsBetweenStrategy = new GoogleAdsBetweenStrategy(
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

    public function test_get_all_returns_items_with_ads_according_to_pattern()
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

        // Mock system config with ad interval
        $systemConfig = (object) [
            'is_block_user' => 1,
            'promo_cell_interval_no' => 2, // Show ad every 2 items
        ];
        $this->systemConfigService->shouldReceive('get')->andReturn($systemConfig);

        // Mock limit/offset calculation
        $this->itemService->shouldReceive('calculateItemLimitAndOffset')
            ->with(5, 0, 2)
            ->andReturn([
                'normalLimit' => 4,
                'normalOffset' => 0,
            ]);

        // Mock pattern generation
        $this->itemService->shouldReceive('generateVisiblePatternArray')
            ->with(5, 0, 2)
            ->andReturn(['one', 'one', 'zero', 'one', 'one']);

        // Mock filter preparation
        $filtersNotIn = ['blockUserIds_not_in' => ['u1']];
        $normalFiltersNotIn = array_merge($filtersNotIn, ['id' => []]);

        $this->searchItemService->shouldReceive('prepareFiltersNotInData')
            ->with(1, '1', [])
            ->andReturn($filtersNotIn);

        $this->searchItemService->shouldReceive('prepareNormalItemFiltersNotInData')
            ->with($filtersNotIn)
            ->andReturn($normalFiltersNotIn);

        // Mock normal items
        $normalItems = collect([
            (object) ['id' => 1, 'title' => 'Item 1'],
            (object) ['id' => 2, 'title' => 'Item 2'],
            (object) ['id' => 3, 'title' => 'Item 3'],
            (object) ['id' => 4, 'title' => 'Item 4'],
        ]);

        $this->itemService->shouldReceive('getAll')
            ->with(
                ['category'],
                ['category_id' => 1],
                ['added_date' => 'desc'],
                4, // normalLimit
                0, // normalOffset
                Constants::yes,
                $normalFiltersNotIn
            )
            ->andReturn($normalItems);

        // Execute
        $result = $this->googleAdsBetweenStrategy->getAll($dto);

        // Verify
        $this->assertCount(5, $result);

        // Check pattern: item, item, ad, item, ad
        $this->assertEquals(1, $result[0]->id);
        $this->assertEquals(2, $result[1]->id);
        $this->assertEquals(Constants::googleAd, $result[2]->ad_type);
        $this->assertEquals(3, $result[3]->id);
        $this->assertEquals(4, $result[4]->id);
    }

    public function test_get_all_handles_empty_normal_items()
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

        // Mock limit/offset calculation
        $this->itemService->shouldReceive('calculateItemLimitAndOffset')
            ->with(3, 0, 2)
            ->andReturn([
                'normalLimit' => 2,
                'normalOffset' => 0,
            ]);

        // Mock pattern generation
        $this->itemService->shouldReceive('generateVisiblePatternArray')
            ->with(3, 0, 2)
            ->andReturn(['one', 'one', 'zero']);

        // Mock filter preparation
        $filtersNotIn = ['blockUserIds_not_in' => ['u1']];
        $normalFiltersNotIn = array_merge($filtersNotIn, ['id' => []]);

        $this->searchItemService->shouldReceive('prepareFiltersNotInData')
            ->with(1, '1', [])
            ->andReturn($filtersNotIn);

        $this->searchItemService->shouldReceive('prepareNormalItemFiltersNotInData')
            ->with($filtersNotIn)
            ->andReturn($normalFiltersNotIn);

        // Mock empty normal items
        $this->itemService->shouldReceive('getAll')
            ->with(
                ['category'],
                ['category_id' => 999],
                ['added_date' => 'desc'],
                2,
                0,
                Constants::yes,
                $normalFiltersNotIn
            )
            ->andReturn(collect([]));

        // Execute
        $result = $this->googleAdsBetweenStrategy->getAll($dto);

        // Verify - should return just the first ad
        $this->assertCount(1, $result);
        $this->assertEquals(Constants::googleAd, $result[0]->ad_type);
    }
}
