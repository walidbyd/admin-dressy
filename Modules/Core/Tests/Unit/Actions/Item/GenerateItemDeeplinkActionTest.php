<?php

namespace Modules\Core\Tests\Unit\Actions\Item;

use App\Config\ps_constant;
use App\Http\Contracts\Image\ImageServiceInterface;
use App\Http\Contracts\Utilities\DynamicLinkServiceInterface;
use App\Models\User;
use Exception;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use InvalidArgumentException;
use Mockery;
use Modules\Core\Actions\Item\GenerateItemDeeplinkAction;
use Modules\Core\Entities\Category\Category;
use Modules\Core\Entities\DynamicLink;
use Modules\Core\Entities\Financial\ItemCurrency;
use Modules\Core\Entities\Item\Item;
use Modules\Core\Entities\Location\LocationCity;
use Modules\Core\Http\Services\Item\ItemService;
use Tests\TestCase;

class GenerateItemDeeplinkActionTest extends TestCase
{
    use DatabaseTransactions;

    private $action;

    private $itemService;

    private $imageService;

    private $dynamicLinkService;

    protected $user;

    protected function setUp(): void
    {

        parent::setUp();

        $this->itemService = Mockery::mock(ItemService::class)->makePartial();
        $this->imageService = Mockery::mock(ImageServiceInterface::class);
        $this->dynamicLinkService = Mockery::mock(DynamicLinkServiceInterface::class);

        $this->action = Mockery::mock(GenerateItemDeeplinkAction::class, [
            $this->itemService,
            $this->imageService,
            $this->dynamicLinkService,
        ])->makePartial();

        $this->user = User::factory()->create(['role_id' => '1']);
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        // Clean up Mockery
        Mockery::close();
    }

    // region PSX Dynamic Link Test

    public function test_handle_with_psx_dynamic_link_success()
    {

        $this->dynamicLinkService->shouldReceive('getDeepLinkServiceProvider')
            ->once()
            ->andReturn(ps_constant::PSX_DYNAMIC_LINK);

        $this->actingAs($this->user);

        Category::factory()->create();
        ItemCurrency::factory()->create();
        LocationCity::factory()->create();

        $item = Item::factory()->create();

        $this->itemService->shouldReceive('get')
            ->once()
            ->with($item->id)
            ->andReturn($item);

        $rData = [
            DynamicLink::shortCode => 'www.psx.com',
            DynamicLink::parameters => [],
            DynamicLink::updatedUserId => '0',
        ];

        $this->dynamicLinkService->shouldReceive('generateDynamicLinks')
            ->once()
            ->with(
                $item,
                ['item_id' => Item::id],
                ps_constant::DYNAMIC_LINK_ITEM
            )
            ->andReturn(collect([$rData]));

        $updatedItem = clone $item;
        $updatedItem->dynamic_link = 'www.psx.com';

        $this->itemService->shouldReceive('updateDynamicLink')
            ->once()
            ->with(
                $item,
                'www.psx.com'
            )
            ->andReturn($updatedItem);

        $item = $this->action->handle($item->id);
        $this->assertEquals('www.psx.com', $item->dynamic_link, 'Should receive the item.');
    }

    public function test_handle_with_psx_dynamic_link_failed_to_generate()
    {

        $this->dynamicLinkService->shouldReceive('getDeepLinkServiceProvider')
            ->once()
            ->andReturn(ps_constant::PSX_DYNAMIC_LINK);

        $this->actingAs($this->user);

        Category::factory()->create();
        ItemCurrency::factory()->create();
        LocationCity::factory()->create();

        $item = Item::factory()->create();

        $this->itemService->shouldReceive('get')
            ->once()
            ->with($item->id)
            ->andReturn($item);

        $this->dynamicLinkService->shouldReceive('generateDynamicLinks')
            ->once()
            ->with(
                $item,
                ['item_id' => Item::id],
                ps_constant::DYNAMIC_LINK_ITEM
            )
            ->andReturn(null);

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Failed to generate dynamic link.');

        $item = $this->action->handle($item->id);

    }

    public function test_handle_with_invalid_item_id()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("ItemId can't be empty.");
        $item = $this->action->handle('');
    }

    // endregion
}
