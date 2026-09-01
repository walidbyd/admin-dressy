<?php

namespace Modules\Core\Tests\Unit\Http\Services\Item;

use App\Http\Contracts\Item\PaidItemHistoryServiceInterface;
use App\Http\Contracts\User\BlockUserServiceInterface;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Mockery;
use Modules\Core\Constants\Constants;
use Modules\Core\Entities\Item\PaidItemHistory;
use Modules\Core\Http\Services\Item\ComplaintItemService;
use Modules\Core\Http\Services\Item\SearchItemService;
use Tests\TestCase;

class SearchItemServiceTest extends TestCase
{
    use DatabaseTransactions;

    protected $complaintItemService;

    protected $blockUserService;

    protected $paidItemHistoryService;

    protected $searchItemService;

    protected $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->complaintItemService = Mockery::mock(ComplaintItemService::class);
        $this->blockUserService = Mockery::mock(BlockUserServiceInterface::class);
        $this->paidItemHistoryService = Mockery::mock(PaidItemHistoryServiceInterface::class);

        $this->searchItemService = new SearchItemService(
            $this->complaintItemService,
            $this->blockUserService,
            $this->paidItemHistoryService
        );
        $this->user = User::factory()->create([User::roleId => '1']);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    // region prepareFiltersNotInData
    // -------------------------------------------------------------------
    // prepareFiltersNotInData
    // -------------------------------------------------------------------

    public function test_prepare_filters_not_in_data_returns_blocked_users_and_complaint_items_with_block_user_enable()
    {
        $this->blockUserService
            ->shouldReceive('getAll')
            ->once()
            ->andReturn(collect([
                (object) ['from_block_user_id' => 1],
                (object) ['from_block_user_id' => 2],
            ]));

        $this->complaintItemService
            ->shouldReceive('getComplaintItems')
            ->once()
            ->andReturn(collect([
                (object) ['item_id' => 10],
                (object) ['item_id' => 11],
            ]));

        $result = $this->searchItemService->prepareFiltersNotInData(1, $this->user->id);

        $this->assertEquals([1, 2], $result['blockUserIds_not_in']);
        $this->assertEquals([10, 11], $result['complaintItemIds_not_in']);
    }

    public function test_prepare_filters_not_in_data_returns_complaint_items_with_block_user_disable()
    {
        $this->blockUserService->shouldNotReceive();

        $this->complaintItemService
            ->shouldReceive('getComplaintItems')
            ->once()
            ->andReturn(collect([
                (object) ['item_id' => 10],
                (object) ['item_id' => 11],
            ]));

        $result = $this->searchItemService->prepareFiltersNotInData(0, $this->user->id);

        $this->assertEquals([], $result['blockUserIds_not_in']);
        $this->assertEquals([10, 11], $result['complaintItemIds_not_in']);
    }
    // endregion

    // region prepareFiltersNotInData
    // -------------------------------------------------------------------
    // prepareFiltersNotInData
    // -------------------------------------------------------------------

    public function test_prepare_paid_item_filters_data_appends_correct_paid_item_conditions()
    {
        $filters = [
            'category_id' => 1,
            'status' => 1,
        ];

        $roundedTimestamp = getRoundedFiveMinuteTimestamp();

        $result = $this->searchItemService->preparePaidItemFiltersData($filters);

        $this->assertEquals(Constants::yes, $result['is_paid']);
        $this->assertEquals($roundedTimestamp, $result['paid_item_histories_timestamp']);
        $this->assertNull($result['paid_item_histories_deleted_at']);

        $this->assertEquals(1, $result['category_id']);
        $this->assertEquals(1, $result['status']);
    }
    // endregion

    // region prepareFiltersNotInData
    // -------------------------------------------------------------------
    // prepareFiltersNotInData
    // -------------------------------------------------------------------

    public function test_prepare_normal_item_filters_not_in_data_sets_exclusion_ids()
    {
        $this->paidItemHistoryService
            ->shouldReceive('getAll')
            ->once()
            ->andReturn(collect([
                (object) [PaidItemHistory::itemId => 100],
                (object) [PaidItemHistory::itemId => 101],
            ]));

        $filtersNotIn = [
            'category_id' => 1,
            'status' => 0,
            'id' => [],
        ];

        $result = $this->searchItemService->prepareNormalItemFiltersNotInData($filtersNotIn);

        $this->assertEquals([100, 101], $result['id']);
        $this->assertEquals(1, $result['category_id']);
        $this->assertEquals(0, $result['status']);
    }
    // endregion
}
