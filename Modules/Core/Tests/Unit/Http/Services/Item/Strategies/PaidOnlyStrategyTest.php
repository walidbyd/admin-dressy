<?php

namespace Tests\Unit\Services\Item\Strategies;

use App\Http\Contracts\Configuration\SystemConfigServiceInterface;
use App\Http\Contracts\Item\SearchItemServiceInterface;
use Illuminate\Support\Collection;
use Mockery;
use Modules\Core\Constants\Constants;
use Modules\Core\DTOs\Item\SearchItemDto;
use Modules\Core\Http\Services\Item\ItemService;
use Modules\Core\Http\Services\Item\Strategies\PaidOnlyStrategy;
use Tests\TestCase;

class PaidOnlyStrategyTest extends TestCase
{
    protected $itemService;

    protected $searchItemService;

    protected $systemConfigService;

    protected $paidOnlyStrategy;

    protected function setUp(): void
    {
        parent::setUp();

        $this->itemService = Mockery::mock(ItemService::class);
        $this->searchItemService = Mockery::mock(SearchItemServiceInterface::class);
        $this->systemConfigService = Mockery::mock(SystemConfigServiceInterface::class);

        $this->paidOnlyStrategy = new PaidOnlyStrategy(
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

    public function test_get_all_returns_item_collection()
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
        $this->searchItemService->shouldReceive('preparePaidItemFiltersData')
            ->with($dto->filters)
            ->once()
            ->andReturn([
                'category_id' => 5,
                'keyword' => 'test',
                'is_paid' => Constants::yes,
                'paid_item_histories_timestamp' => getRoundedFiveMinuteTimestamp(),
                'paid_item_histories_deleted_at' => null,
            ]);

        $this->searchItemService->shouldReceive('prepareFiltersNotInData')
            ->with(1, '1', [])
            ->once()
            ->andReturn([
                'blockUserIds_not_in' => ['blocked1', 'blocked2'],
                'complaintItemIds_not_in' => ['complaint1', 'complaint2'],
            ]);

        // Mock item service response
        $expectedItems = collect([
            (object) ['id' => 1, 'title' => 'Paid Item 1', 'is_paid' => 1],
            (object) ['id' => 2, 'title' => 'Paid Item 2', 'is_paid' => 1],
        ]);

        $this->itemService->shouldReceive('getAll')
            ->with(
                $dto->relation,
                [
                    'category_id' => 5,
                    'keyword' => 'test',
                    'is_paid' => Constants::yes,
                    'paid_item_histories_timestamp' => getRoundedFiveMinuteTimestamp(),
                    'paid_item_histories_deleted_at' => null,
                ],
                $dto->sorting,
                $dto->limit,
                $dto->offset,
                Constants::yes,
                [
                    'blockUserIds_not_in' => ['blocked1', 'blocked2'],
                    'complaintItemIds_not_in' => ['complaint1', 'complaint2'],
                ]
            )
            ->once()
            ->andReturn($expectedItems);

        $result = $this->paidOnlyStrategy->getAll($dto);

        $this->assertInstanceOf(Collection::class, $result);
        $this->assertCount(2, $result);
        $this->assertEquals(1, $result->first()->is_paid);
    }
}
