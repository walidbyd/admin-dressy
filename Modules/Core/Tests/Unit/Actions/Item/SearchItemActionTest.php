<?php

namespace Tests\Unit\Actions;

use App\Exceptions\PsApiException;
use App\Http\Contracts\Configuration\AdPostTypeServiceInterface;
use App\Models\User;
use Exception;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Mockery;
use Modules\Core\Actions\Item\SearchItemAction;
use Modules\Core\Entities\Category\Category;
use Modules\Core\Entities\Financial\ItemCurrency;
use Modules\Core\Entities\Item\Item;
use Modules\Core\Entities\Location\LocationCity;
use Modules\Core\Http\Services\Item\ItemSearchStrategyResolver;
use Modules\Core\Http\Services\Item\Strategies\ItemSearchStrategyInterface;
use Tests\TestCase;

class SearchItemActionTest extends TestCase
{
    use DatabaseTransactions;

    protected $adPostTypeService;

    protected $resolver;

    protected $searchItemAction;

    protected $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->adPostTypeService = Mockery::mock(AdPostTypeServiceInterface::class);
        $this->resolver = Mockery::mock(ItemSearchStrategyResolver::class);

        $this->searchItemAction = new SearchItemAction(
            $this->adPostTypeService,
            $this->resolver
        );

        $this->user = User::factory()->create([User::roleId => '1']);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    // region handle
    // -------------------------------------------------------------------
    // handle
    // -------------------------------------------------------------------

    public function test_handle_returns_item_collection()
    {
        $request = new Request;

        [$paidItems, $normalItems] = $this->createTestItems(5, 10);

        $this->adPostTypeService->shouldReceive('getAdPostType')
            ->once()
            ->andReturn('paid_item_first');

        $strategy = Mockery::mock(ItemSearchStrategyInterface::class);

        $this->resolver->shouldReceive('resolve')
            ->with('paid_item_first')
            ->once()
            ->andReturn($strategy);

        $strategy->shouldReceive('getAll')
            ->once()
            ->andReturn($paidItems);

        $result = $this->searchItemAction->handle($request);

        $this->assertInstanceOf(Collection::class, $result);
        $this->assertCount(5, $result);
    }

    public function test_handle_throws_ps_api_exception_when_something_fails()
    {
        $request = new Request;

        $this->expectException(PsApiException::class);

        $this->adPostTypeService->shouldReceive('getAdPostType')
            ->once()
            ->andThrow(new Exception('Error: Attempt to read property "key" on null'));

        $this->searchItemAction->handle($request);
    }

    private function createTestItems(int $paidCount, int $normalCount): array
    {
        Category::factory()->create();
        ItemCurrency::factory()->create();
        LocationCity::factory()->create();

        $paidItems = Item::factory()->count($paidCount)->create([Item::isPaid => 1]);
        $normalItems = Item::factory()->count($normalCount)->create([Item::isPaid => 0]);

        return [$paidItems, $normalItems];
    }
    // endregion
}
