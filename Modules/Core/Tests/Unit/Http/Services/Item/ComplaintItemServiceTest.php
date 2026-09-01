<?php

namespace Modules\Core\Tests\Unit\Http\Services\Item;

use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Pagination\LengthAwarePaginator;
use Mockery;
use Modules\Core\Entities\Category\Category;
use Modules\Core\Entities\Financial\ItemCurrency;
use Modules\Core\Entities\Item\ComplaintItem;
use Modules\Core\Entities\Item\Item;
use Modules\Core\Entities\Location\LocationCity;
use Modules\Core\Http\Services\Category\CategoryService;
use Modules\Core\Http\Services\Item\ComplaintItemService;
use Modules\Core\Http\Services\ItemService;
use Modules\Core\Http\Services\UserAccessApiTokenService;
use Tests\TestCase;

class ComplaintItemServiceTest extends TestCase
{
    use DatabaseTransactions;

    protected $categoryService;

    protected $itemService;

    protected $userAccessApiTokenService;

    protected $complaintItemService;

    protected $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->categoryService = Mockery::mock(CategoryService::class);
        $this->itemService = Mockery::mock(ItemService::class);
        $this->userAccessApiTokenService = Mockery::mock(UserAccessApiTokenService::class);
        $this->complaintItemService = new ComplaintItemService(
            $this->categoryService,
            $this->itemService,
            $this->userAccessApiTokenService
        );
        $this->user = User::factory()->create([User::roleId => '1']);
        User::factory()->count(5)->create();
        Category::factory()->create();
        LocationCity::factory()->create();
        ItemCurrency::factory()->create();
        Item::factory()->count(20)->create();
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    // region getComplaintItems
    // -------------------------------------------------------------------
    // getComplaintItems
    // -------------------------------------------------------------------

    public function test_get_complaint_items_with_relation_eager_loads_relations()
    {
        $this->createComplaintItem(1, [ComplaintItem::reportedUserId => $this->user->id]);

        $result = $this->complaintItemService->getComplaintItems(['reported_user']);

        $this->assertTrue($result->first()->relationLoaded('reported_user'));
        $this->assertEquals($this->user->id, $result->first()->reported_user->id);
    }

    public function test_get_complaint_items_with_order_by_item_title_sorts_by_item_title()
    {
        $item1 = Item::factory()->create(['title' => 'Z Item']);
        $item2 = Item::factory()->create(['title' => 'A Item']);

        $this->createComplaintItem(1, [ComplaintItem::itemId => $item1->id]);
        $this->createComplaintItem(1, [ComplaintItem::itemId => $item2->id]);

        $result = $this->complaintItemService->getComplaintItems(
            null,
            null,
            null,
            null,
            ['order_by' => 'item@@title', 'order_type' => 'asc']
        );

        $this->assertEquals($item2->id, $result->first()->item_id);

        $result = $this->complaintItemService->getComplaintItems(
            null,
            null,
            null,
            null,
            ['order_by' => 'item@@title', 'order_type' => 'desc']
        );

        $this->assertEquals($item1->id, $result->first()->item_id);
    }

    public function test_get_complaint_items_with_order_by_user_name_sorts_by_user_name()
    {
        $user1 = User::factory()->create(['name' => 'Z User']);
        $user2 = User::factory()->create(['name' => 'A User']);

        $this->createComplaintItem(1, [ComplaintItem::addedUserId => $user1->id]);
        $this->createComplaintItem(1, [ComplaintItem::addedUserId => $user2->id]);

        $result = $this->complaintItemService->getComplaintItems(
            null,
            null,
            null,
            null,
            ['order_by' => 'added_user_id@@name', 'order_type' => 'asc']
        );

        $this->assertEquals($user2->id, $result->first()->added_user_id);

        $result = $this->complaintItemService->getComplaintItems(
            null,
            null,
            null,
            null,
            ['order_by' => 'added_user_id@@name', 'order_type' => 'desc']
        );

        $this->assertEquals($user1->id, $result->first()->added_user_id);
    }

    public function test_get_complaint_items_with_search_term_filters_by_item_title()
    {
        $item1 = Item::factory()->create(['title' => 'Unique Title 123']);
        $item2 = Item::factory()->create(['title' => 'Another Title']);

        $this->createComplaintItem(1, [ComplaintItem::itemId => $item1->id]);
        $this->createComplaintItem(1, [ComplaintItem::itemId => $item2->id]);

        $result = $this->complaintItemService->getComplaintItems(
            null,
            null,
            null,
            null,
            ['searchterm' => 'Unique Title']
        );

        $this->assertCount(1, $result);
        $this->assertEquals($item1->id, $result->first()->item_id);
    }

    public function test_get_complaint_items_with_item_id_filters_by_specific_item()
    {
        $this->createComplaintItem(3);
        $items = Item::factory()->count(3)->create();
        $this->createComplaintItem(2, [ComplaintItem::itemId => $items[0]->id]);

        $result = $this->complaintItemService->getComplaintItems(
            null,
            null,
            null,
            null,
            ['item_id' => $items[0]->id]
        );

        $this->assertCount(2, $result);
        $result->each(function ($item) use ($items) {
            $this->assertEquals($items[0]->id, $item->item_id);
        });
    }

    public function test_get_complaint_items_with_reported_user_id_in_conds_filters_correctly()
    {
        $this->createComplaintItem(3);
        $user = User::factory()->create();
        $this->createComplaintItem(2, [ComplaintItem::reportedUserId => $user->id]);

        $result = $this->complaintItemService->getComplaintItems(
            null,
            null,
            null,
            null,
            ['reported_user_id' => $user->id]
        );

        $this->assertCount(2, $result);
        $result->each(function ($item) use ($user) {
            $this->assertEquals($user->id, $item->reported_user_id);
        });
    }

    public function test_get_complaint_items_with_reported_item_status_id_filters_by_status()
    {
        $this->createComplaintItem(2, [ComplaintItem::reportedItemStatusId => 3]);
        $this->createComplaintItem(3, [ComplaintItem::reportedItemStatusId => 1]);

        $result = $this->complaintItemService->getComplaintItems(
            null,
            null,
            null,
            null,
            ['reported_item_status_id' => 3]
        );

        $this->assertCount(2, $result);
        $result->each(function ($item) {
            $this->assertEquals(3, $item->reported_item_status_id);
        });
    }

    public function test_get_complaint_items_with_added_date_filters_by_exact_date()
    {
        ComplaintItem::truncate();
        $specificDateTime = now()->subDays(5)->setTime(14, 30, 15);
        $now = now();
        $this->createComplaintItem(2, [ComplaintItem::addedDate => $specificDateTime]);
        $this->createComplaintItem(3, [ComplaintItem::addedDate => $now]); // Control group with random dates

        $result = $this->complaintItemService->getComplaintItems(
            null,
            null,
            null,
            null,
            ['added_date' => $now]
        );

        $this->assertCount(3, $result);
        $result->each(function ($item) {
            $this->assertEquals(
                now()->format('Y-m-d'),
                $item->{ComplaintItem::addedDate}->format('Y-m-d')
            );
        });
    }

    public function test_get_complaint_items_with_added_user_id_filters_by_added_user()
    {
        $this->createComplaintItem(3);
        $user = User::factory()->create();
        $this->createComplaintItem(2, [ComplaintItem::addedUserId => $user->id]);

        $result = $this->complaintItemService->getComplaintItems(
            null,
            null,
            null,
            null,
            ['added_user_id' => $user->id]
        );

        $this->assertCount(2, $result);
        $result->each(function ($item) use ($user) {
            $this->assertEquals($user->id, $item->added_user_id);
        });
    }

    public function test_get_complaint_items_with_date_range_filters_by_date_range()
    {
        ComplaintItem::truncate();
        $now = now();
        $this->createComplaintItem(2, [ComplaintItem::addedDate => $now->subDays(3)]);
        $this->createComplaintItem(3, [ComplaintItem::addedDate => $now->addDays(1)]);

        $result = $this->complaintItemService->getComplaintItems(
            null,
            null,
            null,
            null,
            ['date_range' => [$now->copy()->subDays(5), $now->copy()->subDays(1)]]
        );

        $this->assertCount(2, $result);
    }

    public function test_get_complaint_items_with_multiple_conditions_applies_all_filters()
    {
        // Non-matching items
        $this->createComplaintItem(5, [ComplaintItem::addedDate => now()->subDays(2)]);

        $user = User::factory()->create();
        $item = Item::factory()->create(['title' => 'Special Item']);

        // Matching items
        $this->createComplaintItem(2, [
            ComplaintItem::itemId => $item->id,
            ComplaintItem::reportedUserId => $user->id,
            ComplaintItem::reportedItemStatusId => 2,
        ]);

        $result = $this->complaintItemService->getComplaintItems(
            null,
            null,
            null,
            null,
            [
                'searchterm' => 'Special',
                'reported_user_id' => $user->id,
                'added_date' => now(),
                'reported_item_status_id' => 2,
                'order_by' => 'id',
                'order_type' => 'desc',
            ]
        );

        $this->assertCount(2, $result);
        $result->each(function ($item) use ($user) {
            $this->assertEquals($user->id, $item->reported_user_id);
            $this->assertEquals(now()->format('Y-m-d'), $item->added_date->format('Y-m-d'));
            $this->assertEquals(2, $item->reported_item_status_id);
        });
    }

    public function test_get_complaint_items_with_order_by_id_sorts_by_id()
    {
        ComplaintItem::truncate();

        $this->createComplaintItem(5);

        // Test ascending order
        $resultAsc = $this->complaintItemService->getComplaintItems(
            null,
            null,
            null,
            null,
            ['order_by' => 'id', 'order_type' => 'asc']
        );

        $this->assertEquals(1, $resultAsc->first()->id);
        $this->assertEquals(5, $resultAsc->last()->id);

        // Test descending order
        $resultDesc = $this->complaintItemService->getComplaintItems(
            null,
            null,
            null,
            null,
            ['order_by' => 'id', 'order_type' => 'desc']
        );

        $this->assertEquals(5, $resultDesc->first()->id);
        $this->assertEquals(1, $resultDesc->last()->id);
    }

    public function test_get_complaint_items_with_order_by_complete_status_sorts_by_status()
    {
        $this->createComplaintItem(1, [ComplaintItem::reportedItemStatusId => 3]);
        $this->createComplaintItem(1, [ComplaintItem::reportedItemStatusId => 1]);
        $this->createComplaintItem(1, [ComplaintItem::reportedItemStatusId => 2]);

        // Test ascending order
        $resultAsc = $this->complaintItemService->getComplaintItems(
            null,
            null,
            null,
            null,
            ['order_by' => 'complete', 'order_type' => 'asc']
        );

        $this->assertEquals(1, $resultAsc->first()->reported_item_status_id);
        $this->assertEquals(3, $resultAsc->last()->reported_item_status_id);

        // Test descending order
        $resultDesc = $this->complaintItemService->getComplaintItems(
            null,
            null,
            null,
            null,
            ['order_by' => 'complete', 'order_type' => 'desc']
        );

        $this->assertEquals(3, $resultDesc->first()->reported_item_status_id);
        $this->assertEquals(1, $resultDesc->last()->reported_item_status_id);
    }

    public function test_get_complaint_items_with_status_filters_by_status()
    {
        $this->createComplaintItem(3);
        $item = Item::factory()->create([Item::isPaid => 1]);
        $this->createComplaintItem(2, [ComplaintItem::itemId => $item->id]);

        $result = $this->complaintItemService->getComplaintItems(null, 1);

        $this->assertCount(2, $result);
        $result->each(function ($complaintItem) use ($item) {
            $this->assertEquals($item->id, $complaintItem->item_id);
        });
    }

    public function test_get_complaint_items_with_reported_user_id_filters_by_reported_user()
    {
        $this->createComplaintItem(2);
        $user = User::factory()->create();
        $this->createComplaintItem(3, [ComplaintItem::reportedUserId => $user->id]);

        $result = $this->complaintItemService->getComplaintItems(null, null, null, null, null, null, null, $user->id);

        $this->assertCount(3, $result);
        $result->each(function ($item) use ($user) {
            $this->assertEquals($user->id, $item->reported_user_id);
        });
    }

    public function test_get_complaint_items_with_limit_and_offset_returns_paginated_results()
    {
        ComplaintItem::truncate();

        $this->createComplaintItem(10);

        $result = $this->complaintItemService->getComplaintItems(null, null, 5, 5);

        $this->assertCount(5, $result);
    }

    public function test_get_complaint_items_without_order_by_returns_latest()
    {
        ComplaintItem::truncate();

        $latestComplaintItem = $this->createComplaintItem(1, [ComplaintItem::addedDate => now()])->first();
        $this->createComplaintItem(1, [ComplaintItem::addedDate => now()->subDays(2)])->first();

        $result = $this->complaintItemService->getComplaintItems()->first();

        $this->assertEquals($latestComplaintItem->id, $result->id);
    }

    public function test_get_complaint_items_with_pag_per_page_returns_paginator()
    {
        ComplaintItem::truncate();

        $this->createComplaintItem(15);

        $result = $this->complaintItemService->getComplaintItems(null, null, null, null, null, null, 10);

        $this->assertInstanceOf(LengthAwarePaginator::class, $result);
        $this->assertCount(10, $result);
        $this->assertEquals(15, $result->total());
    }

    public function test_get_complaint_items_with_no_pagination_returns_collection()
    {
        ComplaintItem::truncate();

        $this->createComplaintItem(5);

        $result = $this->complaintItemService->getComplaintItems(null, null, null, null, null, true);

        $this->assertInstanceOf(Collection::class, $result);
        $this->assertCount(5, $result);
    }

    public function test_get_complaint_items_with_no_parameters_returns_all_items()
    {
        ComplaintItem::truncate();

        $this->createComplaintItem(5);

        $result = $this->complaintItemService->getComplaintItems();

        $this->assertCount(5, $result);
    }
    // endregion

    private function createComplaintItem(int $count = 1, array $attributes = [])
    {
        $complaintItems = collect();

        for ($i = 0; $i < $count; $i++) {
            $user = User::inRandomOrder()->first();
            $item = Item::inRandomOrder()->first();

            $complaintItems->push(ComplaintItem::create(array_merge([
                ComplaintItem::itemId => $item->id,
                ComplaintItem::reportedUserId => $user->id,
                ComplaintItem::textNote => 'Text Note '.$user->id,
                ComplaintItem::reportedItemStatusId => 1,
                ComplaintItem::addedUserId => $user->id,
            ], $attributes)));
        }

        return $complaintItems;
    }
}
