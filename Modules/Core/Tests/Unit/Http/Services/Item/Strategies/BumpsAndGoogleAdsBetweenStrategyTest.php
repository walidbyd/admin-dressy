<?php

namespace Tests\Unit\Services\Item\Strategies;

use App\Http\Contracts\Configuration\SystemConfigServiceInterface;
use App\Http\Contracts\Item\SearchItemServiceInterface;
use Mockery;
use Modules\Core\Constants\Constants;
use Modules\Core\DTOs\Item\SearchItemDto;
use Modules\Core\Http\Services\Item\ItemService;
use Modules\Core\Http\Services\Item\Strategies\BumpsAndGoogleAdsBetweenStrategy;
use Tests\TestCase;

class BumpsAndGoogleAdsBetweenStrategyTest extends TestCase
{
    protected $itemService;

    protected $searchItemService;

    protected $systemConfigService;

    protected $bumpsAndGoogleAdsBetweenStrategy;

    protected function setUp(): void
    {
        parent::setUp();

        $this->itemService = Mockery::mock(ItemService::class);
        $this->searchItemService = Mockery::mock(SearchItemServiceInterface::class);
        $this->systemConfigService = Mockery::mock(SystemConfigServiceInterface::class);

        $this->bumpsAndGoogleAdsBetweenStrategy = new BumpsAndGoogleAdsBetweenStrategy(
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

    // region getAll
    // -------------------------------------------------------------------
    // getAll
    // -------------------------------------------------------------------

    public function test_get_all()
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

        // Mock paid items (limited by effectivePaidLimit)
        $paidItems = collect([
            (object) ['id' => 'P1', 'title' => 'Paid Item 1'],
            (object) ['id' => 'P2', 'title' => 'Paid Item 2'],
        ]);

        // Mock normal items
        $normalItems = collect([
            (object) ['id' => 'N1', 'title' => 'Normal Item 1'],
            (object) ['id' => 'N2', 'title' => 'Normal Item 2'],
            (object) ['id' => 'N3', 'title' => 'Normal Item 3'],
            (object) ['id' => 'N4', 'title' => 'Normal Item 4'],
        ]);

        // Mock item service calls
        $this->itemService->shouldReceive('getAll')
            ->with(
                $dto->relation,
                $paidItemFilters,
                $dto->sorting,
                1, // effectivePaidLimit (ceil(2/2))
                0, // effectivePaidOffset (ceil(0/2))
                Constants::yes,
                $filtersNotIn
            )
            ->andReturn($paidItems->take(1));

        $this->itemService->shouldReceive('getAll')
            ->with(
                $dto->relation,
                $dto->filters,
                $dto->sorting,
                4, // normalLimit
                0, // normalOffset
                Constants::yes,
                $normalFiltersNotIn
            )
            ->andReturn($normalItems);

        // Execute
        $result = $this->bumpsAndGoogleAdsBetweenStrategy->getAll($dto);

        // Verify merged items follow pattern with Google ads
        $this->assertCount(6, $result);
        $this->assertEquals('N1', $result[0]->id); // normal
        $this->assertEquals('N2', $result[1]->id); // normal
        $this->assertEquals(Constants::googleAd, $result[2]->ad_type); // google ad
        $this->assertEquals('N3', $result[3]->id); // normal
        $this->assertEquals('N4', $result[4]->id); // normal
        $this->assertEquals('P1', $result[5]->id); // paid
    }
    // endregion

    // region shouldShowGoogleFirst
    // -------------------------------------------------------------------
    // shouldShowGoogleFirst
    // -------------------------------------------------------------------
    public function test_should_show_google_first()
    {
        $strategy = new \ReflectionClass(BumpsAndGoogleAdsBetweenStrategy::class);
        $method = $strategy->getMethod('shouldShowGoogleFirst');
        $method->setAccessible(true);

        // Test with even count (should show google first)
        $this->assertTrue($method->invokeArgs($this->bumpsAndGoogleAdsBetweenStrategy, [5, 0, 2])); // offset 0

        // Test with odd count (should not show google first)
        $this->assertFalse($method->invokeArgs($this->bumpsAndGoogleAdsBetweenStrategy, [5, 3, 2])); // offset 3

        // Test with no paid items
        $this->assertFalse($method->invokeArgs($this->bumpsAndGoogleAdsBetweenStrategy, [0, 0, 2]));
    }
    // endregion

    // region mergeItemsByPattern
    // -------------------------------------------------------------------
    // mergeItemsByPattern
    // -------------------------------------------------------------------

    public function test_merge_items_by_pattern_without_items()
    {
        $strategy = new \ReflectionClass(BumpsAndGoogleAdsBetweenStrategy::class);
        $method = $strategy->getMethod('mergeItemsByPattern');
        $method->setAccessible(true);

        $pattern = ['one', 'zero', 'one', 'zero', 'one'];
        // Empty Items
        $normalItems = collect([]);
        $paidItems = collect([]);
        $googleItem = new \stdClass;
        $googleItem->ad_type = Constants::googleAd;

        $result = $method->invokeArgs($this->bumpsAndGoogleAdsBetweenStrategy, [
            $pattern,
            $normalItems,
            $paidItems,
            $googleItem,
            0,
            2,
        ]);

        $this->assertEquals([], $result);
    }

    public function test_merge_items_by_pattern_with_google_ads_first()
    {
        $strategy = new \ReflectionClass(BumpsAndGoogleAdsBetweenStrategy::class);
        $method = $strategy->getMethod('mergeItemsByPattern');
        $method->setAccessible(true);

        $pattern = ['one', 'zero', 'one', 'zero', 'one'];
        $normalItems = collect([
            (object) ['id' => 'N1'],
            (object) ['id' => 'N2'],
            (object) ['id' => 'N3'],
        ]);
        $paidItems = collect([
            (object) ['id' => 'P1'],
        ]);
        $googleItem = new \stdClass;
        $googleItem->ad_type = Constants::googleAd;

        // Test with showGoogleFirst = true
        $result = $method->invokeArgs($this->bumpsAndGoogleAdsBetweenStrategy, [
            $pattern,
            $normalItems,
            $paidItems,
            $googleItem,
            0,
            2,
        ]);

        $this->assertCount(5, $result);
        $this->assertEquals('N1', $result[0]->id); // normal
        $this->assertEquals(Constants::googleAd, $result[1]->ad_type); // google ad
        $this->assertEquals('N2', $result[2]->id); // normal
        $this->assertEquals('P1', $result[3]->id); // paid
        $this->assertEquals('N3', $result[4]->id); // normal
    }

    public function test_merge_items_by_pattern_without_google_ads_first()
    {
        $strategy = new \ReflectionClass(BumpsAndGoogleAdsBetweenStrategy::class);
        $method = $strategy->getMethod('mergeItemsByPattern');
        $method->setAccessible(true);

        $pattern = ['zero', 'one', 'zero', 'one', 'zero'];
        $normalItems = collect([
            (object) ['id' => 'N1'],
            (object) ['id' => 'N2'],
        ]);
        $paidItems = collect([
            (object) ['id' => 'P1'],
            (object) ['id' => 'P2'],
        ]);

        $googleItem = new \stdClass;
        $googleItem->ad_type = Constants::googleAd;

        $result = $method->invokeArgs($this->bumpsAndGoogleAdsBetweenStrategy, [
            $pattern,
            $normalItems,
            $paidItems,
            $googleItem,
            2,
            1,
        ]);

        // Verify merged items follow pattern: paid, normal, google, normal, normal
        $this->assertCount(5, $result);
        $this->assertEquals('P1', $result[0]->id); // paid
        $this->assertEquals('N1', $result[1]->id); // normal
        $this->assertEquals(Constants::googleAd, $result[2]->ad_type); // google ad
        $this->assertEquals('N2', $result[3]->id); // normal
        $this->assertEquals('P2', $result[4]->id); // paid
    }

    public function test_merge_items_by_pattern_with_insufficient_paid_item_fills_google_ad()
    {
        $strategy = new \ReflectionClass(BumpsAndGoogleAdsBetweenStrategy::class);
        $method = $strategy->getMethod('mergeItemsByPattern');
        $method->setAccessible(true);

        $pattern = ['zero', 'one', 'zero', 'one', 'zero', 'one', 'zero'];
        $normalItems = collect([
            (object) ['id' => 'N1'],
            (object) ['id' => 'N2'],
            (object) ['id' => 'N3'],
        ]);
        // Not enough paid item
        $paidItems = collect([
            (object) ['id' => 'P1'],
        ]);

        $googleItem = new \stdClass;
        $googleItem->ad_type = Constants::googleAd;

        $result = $method->invokeArgs($this->bumpsAndGoogleAdsBetweenStrategy, [
            $pattern,
            $normalItems,
            $paidItems,
            $googleItem,
            2,
            1,
        ]);

        // Verify merged items follow pattern: paid, normal, google, normal, google, normal, google
        $this->assertCount(6, $result);
        $this->assertEquals('P1', $result[0]->id); // paid
        $this->assertEquals('N1', $result[1]->id); // normal
        $this->assertEquals(Constants::googleAd, $result[2]->ad_type); // google ad
        $this->assertEquals('N2', $result[3]->id); // normal
        $this->assertEquals(Constants::googleAd, $result[4]->ad_type); // google ad
        $this->assertEquals('N3', $result[5]->id); // normal
    }

    public function test_merge_items_by_pattern_with_insufficient_normal_item_fills_google_ad()
    {
        $strategy = new \ReflectionClass(BumpsAndGoogleAdsBetweenStrategy::class);
        $method = $strategy->getMethod('mergeItemsByPattern');
        $method->setAccessible(true);

        $pattern = ['zero', 'one', 'zero', 'one', 'zero', 'one', 'zero', 'one', 'zero'];
        // Not enough normal item
        $normalItems = collect([
            (object) ['id' => 'N1'],
            (object) ['id' => 'N2'],
        ]);
        $paidItems = collect([
            (object) ['id' => 'P1'],
            (object) ['id' => 'P2'],
            (object) ['id' => 'P3'],
        ]);

        $googleItem = new \stdClass;
        $googleItem->ad_type = Constants::googleAd;

        $result = $method->invokeArgs($this->bumpsAndGoogleAdsBetweenStrategy, [
            $pattern,
            $normalItems,
            $paidItems,
            $googleItem,
            0,
            1,
        ]);

        // Verify merged items follow pattern: google, normal, paid, normal, google, paid, google, paid
        $this->assertCount(8, $result);
        $this->assertEquals(Constants::googleAd, $result[0]->ad_type); // google ad
        $this->assertEquals('N1', $result[1]->id); // normal
        $this->assertEquals('P1', $result[2]->id); // paid
        $this->assertEquals('N2', $result[3]->id); // normal
        $this->assertEquals(Constants::googleAd, $result[4]->ad_type); // google ad
        $this->assertEquals('P2', $result[5]->id); // paid
        $this->assertEquals(Constants::googleAd, $result[6]->ad_type); // google ad
        $this->assertEquals('P3', $result[7]->id); // google ad
    }

    public function test_merge_items_by_pattern_without_paid_item_fills_google_ad()
    {
        $strategy = new \ReflectionClass(BumpsAndGoogleAdsBetweenStrategy::class);
        $method = $strategy->getMethod('mergeItemsByPattern');
        $method->setAccessible(true);

        $pattern = ['zero', 'one', 'zero', 'one', 'zero', 'one', 'zero'];
        $normalItems = collect([
            (object) ['id' => 'N1'],
            (object) ['id' => 'N2'],
            (object) ['id' => 'N3'],
        ]);
        // No Paid Item
        $paidItems = collect([]);

        $googleItem = new \stdClass;
        $googleItem->ad_type = Constants::googleAd;

        $result = $method->invokeArgs($this->bumpsAndGoogleAdsBetweenStrategy, [
            $pattern,
            $normalItems,
            $paidItems,
            $googleItem,
            1,
            1,
        ]);

        // Verify merged items follow pattern: google, normal, google, normal, google, normal
        $this->assertCount(6, $result);
        $this->assertEquals(Constants::googleAd, $result[0]->ad_type); // google ad
        $this->assertEquals('N1', $result[1]->id); // normal
        $this->assertEquals(Constants::googleAd, $result[2]->ad_type); // google ad
        $this->assertEquals('N2', $result[3]->id); // normal
        $this->assertEquals(Constants::googleAd, $result[4]->ad_type); // google ad
        $this->assertEquals('N3', $result[5]->id); // normal
    }

    public function test_merge_items_by_pattern_without_normal_item_fills_google_ad()
    {
        $strategy = new \ReflectionClass(BumpsAndGoogleAdsBetweenStrategy::class);
        $method = $strategy->getMethod('mergeItemsByPattern');
        $method->setAccessible(true);

        $pattern = ['zero', 'one', 'zero', 'one', 'zero', 'one', 'zero'];
        // No Normal Item
        $normalItems = collect([]);
        $paidItems = collect([
            (object) ['id' => 'P1'],
            (object) ['id' => 'P2'],
            (object) ['id' => 'P3'],
        ]);

        $googleItem = new \stdClass;
        $googleItem->ad_type = Constants::googleAd;

        $result = $method->invokeArgs($this->bumpsAndGoogleAdsBetweenStrategy, [
            $pattern,
            $normalItems,
            $paidItems,
            $googleItem,
            1,
            1,
        ]);

        // Verify merged items follow pattern: google, paid, google, paid, google, paid
        $this->assertCount(6, $result);
        $this->assertEquals(Constants::googleAd, $result[0]->ad_type); // google ad
        $this->assertEquals('P1', $result[1]->id); // paid
        $this->assertEquals(Constants::googleAd, $result[2]->ad_type); // google ad
        $this->assertEquals('P2', $result[3]->id); // paid
        $this->assertEquals(Constants::googleAd, $result[4]->ad_type); // google ad
        $this->assertEquals('P3', $result[5]->id); // paid
    }
    // endregion

    // region fetchItems
    // -------------------------------------------------------------------
    // fetchItems
    // -------------------------------------------------------------------

    public function test_fetch_items()
    {
        $strategy = new \ReflectionClass(BumpsAndGoogleAdsBetweenStrategy::class);
        $method = $strategy->getMethod('fetchItems');
        $method->setAccessible(true);

        $dto = new SearchItemDto(
            loginUserId: '1',
            limit: 6,
            offset: 0,
            sorting: ['added_date' => 'desc'],
            relation: ['category'],
            filters: ['category_id' => 1]
        );

        $limitAndOffsets = [
            'paidLimit' => 2,
            'paidOffset' => 0,
            'normalLimit' => 4,
            'normalOffset' => 0,
        ];

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

        // Mock item service calls
        $expectedPaidItems = collect([
            (object) ['id' => 'P1'],
        ]);
        $this->itemService->shouldReceive('getAll')
            ->with(
                $dto->relation,
                $paidItemFilters,
                $dto->sorting,
                1, // ceil(2/2)
                0, // ceil(0/2)
                Constants::yes,
                $filtersNotIn
            )
            ->andReturn($expectedPaidItems);

        $expectedNormalItems = collect([
            (object) ['id' => 'N1'],
            (object) ['id' => 'N2'],
            (object) ['id' => 'N3'],
            (object) ['id' => 'N4'],
        ]);
        $this->itemService->shouldReceive('getAll')
            ->with(
                $dto->relation,
                $dto->filters,
                $dto->sorting,
                4, // normalLimit
                0, // normalOffset
                Constants::yes,
                $normalFiltersNotIn
            )
            ->andReturn($expectedNormalItems);

        $result = $method->invokeArgs($this->bumpsAndGoogleAdsBetweenStrategy, [$dto, 1, $limitAndOffsets]);

        $this->assertArrayHasKey('paidItems', $result);
        $this->assertArrayHasKey('normalItems', $result);
        $this->assertEquals('N1', $result['normalItems'][0]->id);
        $this->assertEquals('P1', $result['paidItems'][0]->id);
    }
    // endregion
}
