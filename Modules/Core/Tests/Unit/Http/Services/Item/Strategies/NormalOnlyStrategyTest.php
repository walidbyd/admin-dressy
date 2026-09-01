<?php

namespace Tests\Unit\Services\Item\Strategies;

use App\Http\Contracts\Configuration\SystemConfigServiceInterface;
use App\Http\Contracts\Item\SearchItemServiceInterface;
use Illuminate\Support\Collection;
use Mockery;
use Modules\Core\Constants\Constants;
use Modules\Core\DTOs\Item\SearchItemDto;
use Modules\Core\Http\Services\Item\ItemService;
use Modules\Core\Http\Services\Item\Strategies\NormalOnlyStrategy;
use Tests\TestCase;

class NormalOnlyStrategyTest extends TestCase
{
    protected $itemService;

    protected $searchItemService;

    protected $systemConfigService;

    protected $normalOnlyStrategy;

    protected function setUp(): void
    {
        parent::setUp();

        $this->itemService = Mockery::mock(ItemService::class);
        $this->searchItemService = Mockery::mock(SearchItemServiceInterface::class);
        $this->systemConfigService = Mockery::mock(SystemConfigServiceInterface::class);

        $this->normalOnlyStrategy = new NormalOnlyStrategy(
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

    public function test_get_all_returns_normal_items()
    {
        // Create test DTO
        $dto = new SearchItemDto(
            loginUserId: '1',
            limit: 10,
            offset: 0,
            sorting: ['added_date' => 'desc'],
            relation: ['category', 'images'],
            filters: [
                'category_id' => 5,
                'keyword' => 'test',
            ]
        );

        // Mock system config
        $this->systemConfigService->shouldReceive('get')
            ->once()
            ->andReturn((object) ['is_block_user' => 1]);

        // Mock filter preparation
        $expectedFiltersNotIn = [
            'blockUserIds_not_in' => ['blocked1', 'blocked2'],
            'complaintItemIds_not_in' => ['complaint1', 'complaint2'],
        ];

        $this->searchItemService->shouldReceive('prepareFiltersNotInData')
            ->with(1, '1', [])
            ->once()
            ->andReturn($expectedFiltersNotIn);

        // Mock item service response
        $expectedItems = collect([
            (object) ['id' => 1, 'title' => 'Normal Item 1', 'is_paid' => 0],
            (object) ['id' => 2, 'title' => 'Normal Item 2', 'is_paid' => 0],
        ]);

        $this->itemService->shouldReceive('getAll')
            ->with(
                $dto->relation,
                $dto->filters,
                $dto->sorting,
                $dto->limit,
                $dto->offset,
                Constants::yes,
                $expectedFiltersNotIn
            )
            ->once()
            ->andReturn($expectedItems);

        $result = $this->normalOnlyStrategy->getAll($dto);

        // Assertions
        $this->assertInstanceOf(Collection::class, $result);
        $this->assertCount(2, $result);
        $this->assertEquals(0, $result->first()->is_paid);
        $this->assertEquals(1, $result->first()->id);
    }
}
