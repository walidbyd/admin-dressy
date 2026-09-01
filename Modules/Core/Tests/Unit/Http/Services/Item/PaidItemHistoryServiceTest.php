<?php

namespace Modules\Core\Tests\Unit\Http\Services\User;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Mockery;
use Modules\Core\Entities\Category\Category;
use Modules\Core\Entities\Financial\ItemCurrency;
use Modules\Core\Entities\Item\PaidItemHistory;
use Modules\Core\Entities\Location\LocationCity;
use Modules\Core\Http\Services\Item\PaidItemHistoryService;
use Tests\TestCase;

class PaidItemHistoryServiceTest extends TestCase
{
    use DatabaseTransactions;

    protected $paidItemHistoryService;

    protected function setUp(): void
    {
        parent::setUp();

        Category::factory()->count(5)->create();
        LocationCity::factory()->count(5)->create();
        ItemCurrency::factory()->count(5)->create();
        User::factory()->count(5)->create();

        $this->paidItemHistoryService = new PaidItemHistoryService;
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    // region getAll
    // -------------------------------------------------------------------
    // getAll
    // -------------------------------------------------------------------

    public function test_get_all_filters_by_id()
    {
        $history = PaidItemHistory::factory()->create();
        PaidItemHistory::factory()->create();

        $result = $this->paidItemHistoryService->getAll($history->id);

        $this->assertCount(1, $result);
        $this->assertEquals($history->id, $result->first()->id);
    }

    public function test_get_all_filters_by_item_id()
    {
        $itemId = 123;
        PaidItemHistory::factory()->create([PaidItemHistory::itemId => $itemId]);
        PaidItemHistory::factory()->create();

        $result = $this->paidItemHistoryService->getAll(null, $itemId);

        $this->assertCount(1, $result);
        $this->assertEquals($itemId, $result->first()->{PaidItemHistory::itemId});
    }

    public function test_get_all_filters_by_status()
    {
        PaidItemHistory::truncate();
        $status = 1;
        PaidItemHistory::factory()->create([PaidItemHistory::status => $status]);
        PaidItemHistory::factory()->create([PaidItemHistory::status => 0]);

        $result = $this->paidItemHistoryService->getAll(null, null, $status);

        $this->assertCount(1, $result);
        $this->assertEquals($status, $result->first()->{PaidItemHistory::status});
    }

    public function test_get_all_filters_by_start_timestamp()
    {
        PaidItemHistory::truncate();
        $now = now()->unix();
        $past = now()->subDays(5)->unix();
        $future = now()->addDays(5)->unix();

        PaidItemHistory::factory()->create([
            PaidItemHistory::startTimestamp => $past,
            PaidItemHistory::endTimestamp => $future,
        ]);
        PaidItemHistory::factory()->create([
            PaidItemHistory::startTimestamp => $future,
            PaidItemHistory::endTimestamp => $future,
        ]);

        $result = $this->paidItemHistoryService->getAll(null, null, null, $now);

        $this->assertCount(1, $result);
        $this->assertLessThanOrEqual($now, $result->first()->{PaidItemHistory::startTimestamp});
    }

    public function test_get_all_filters_by_end_timestamp()
    {
        PaidItemHistory::truncate();
        $now = now()->unix();
        $past = now()->subDays(5)->unix();
        $future = now()->addDays(5)->unix();

        PaidItemHistory::factory()->create([
            PaidItemHistory::startTimestamp => $past,
            PaidItemHistory::endTimestamp => $future,
        ]);
        PaidItemHistory::factory()->create([
            PaidItemHistory::startTimestamp => $past,
            PaidItemHistory::endTimestamp => $past,
        ]);

        $result = $this->paidItemHistoryService->getAll(null, null, null, null, $now);

        $this->assertCount(1, $result);
        $this->assertGreaterThanOrEqual($now, $result->first()->{PaidItemHistory::endTimestamp});
    }

    public function test_get_all_combines_multiple_filters()
    {
        $itemId = 123;
        $status = 'active';
        $now = now()->unix();

        // Matching record
        $matching = PaidItemHistory::factory()->create([
            PaidItemHistory::itemId => $itemId,
            PaidItemHistory::status => $status,
            PaidItemHistory::startTimestamp => now()->subDay()->unix(),
            PaidItemHistory::endTimestamp => now()->addDay()->unix(),
        ]);

        // Non-matching records
        PaidItemHistory::factory()->count(3)->create();

        $result = $this->paidItemHistoryService->getAll(
            null,
            $itemId,
            $status,
            $now,
            $now
        );

        $this->assertCount(1, $result);
        $this->assertEquals($matching->id, $result->first()->id);
    }

    public function test_get_all_returns_all_records_when_no_parameters()
    {
        PaidItemHistory::truncate();
        PaidItemHistory::factory()->count(3)->create();

        $result = $this->paidItemHistoryService->getAll();

        $this->assertCount(3, $result);
    }

    public function test_get_all_returns_empty_when_no_matches()
    {
        PaidItemHistory::factory()->create();

        $result = $this->paidItemHistoryService->getAll(null, 999);

        $this->assertCount(0, $result);
    }
    // endregion
}
