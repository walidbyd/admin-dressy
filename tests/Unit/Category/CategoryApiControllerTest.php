<?php

namespace Tests\Unit\Category;

use App\Helpers\PsTestHelper;
use App\Http\Contracts\Category\CategoryServiceInterface;
use App\Http\Contracts\Localization\LanguageServiceInterface;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\Request;
use Modules\Core\Http\Controllers\Backend\Rests\App\V1_0\Category\CategoryApiController;
use Modules\Core\Http\Services\Configuration\MobileSettingService;
use Tests\TestCase;

class CategoryApiControllerTest extends TestCase
{
    use DatabaseTransactions;

    protected $categoryApiController;

    protected function setUp(): void
    {
        parent::setUp();

        $this->categoryApiController = new CategoryApiController(
            app(CategoryServiceInterface::class),
            app(LanguageServiceInterface::class),
            app(MobileSettingService::class),
        );

    }

    // //////////////////////////////////////////////////////////////////
    // / Private Function Test Cases
    // //////////////////////////////////////////////////////////////////

    public function test_prepare_language_data()
    {
        $request = Request::create('/category', 'POST', [
            'language_symbol' => 'ar',
        ]);
        $result = PsTestHelper::invokeMethod($this->categoryApiController, 'prepareLanguageData', [$request]);
        $this->assertNotNull($result);
        $this->assertEquals('ar', $result['symbol']);
    }

    public function test_get_limit_offset_from_setting()
    {
        $request = Request::create('/category', 'POST', [
            'limit' => 10,
            'offset' => 0,
        ]);
        $result = PsTestHelper::invokeMethod($this->categoryApiController, 'getLimitOffsetFromSetting', [$request]);
        $this->assertNotNull($result);
        $this->assertEquals(10, $result[0]);
        $this->assertEquals(0, $result[1]);
    }

    public function test_get_filter_conditions()
    {
        $request = Request::create('/category', 'POST', [
            'keyword' => 'car',
            'order_by' => 'added_date',
            'order_type' => 'desc',
        ]);
        $result = PsTestHelper::invokeMethod($this->categoryApiController, 'getFilterConditions', [$request]);
        $this->assertNotNull($result);
        $this->assertEquals('car', $result['searchterm']);
        $this->assertEquals('added_date', $result['order_by']);
        $this->assertEquals('desc', $result['order_type']);
    }
}
