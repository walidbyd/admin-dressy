<?php

namespace Tests\Unit\Category;

use App\Helpers\PsTestHelper;
use App\Http\Contracts\Category\SubcategoryServiceInterface;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Modules\Core\Http\Controllers\Backend\Rests\App\V1_0\Category\SubcategoryApiController;
use Tests\TestCase;

class SubcategoryApiControllerTest extends TestCase
{
    use RefreshDatabase;

    protected $subcategoryApiController;

    protected function setUp(): void
    {
        // @todo Skip all tests in this class
        $this->markTestSkipped('This entire test class is currently disabled.');

        parent::setUp();

        $this->subcategoryApiController = new SubcategoryApiController(
            app(SubcategoryServiceInterface::class)
        );

    }

    // //////////////////////////////////////////////////////////////////
    // / Private Function Test Cases
    // //////////////////////////////////////////////////////////////////

    public function test_get_limit_offset_from_setting()
    {
        $request = Request::create('/subcategory', 'POST', [
            'limit' => 10,
            'offset' => 0,
        ]);
        $result = PsTestHelper::invokeMethod($this->subcategoryApiController, 'getLimitOffsetFromSetting', [$request]);
        $this->assertNotNull($result);
        $this->assertEquals(10, $result[0]);
        $this->assertEquals(0, $result[1]);
    }

    public function test_get_filter_conditions()
    {
        $request = Request::create('/subcategory', 'POST', [
            'keyword' => 'car',
            'category_filter' => 'all',
            'order_by' => 'added_date',
            'order_type' => 'desc',
        ]);
        $result = PsTestHelper::invokeMethod($this->subcategoryApiController, 'getFilterConditions', [$request]);
        $this->assertNotNull($result);
        $this->assertEquals('car', $result['searchterm']);
        $this->assertEquals(null, $result['category_id']);
        $this->assertEquals('added_date', $result['order_by']);
        $this->assertEquals('desc', $result['order_type']);
    }
}
