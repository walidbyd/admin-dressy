<?php

namespace Modules\Core\Tests\Unit\Http\Controllers\Backend\Rests\App\V1_0\Item;

use App\Http\Contracts\Authorization\PermissionServiceInterface;
use App\Http\Contracts\Category\CategoryServiceInterface;
use App\Http\Contracts\Configuration\AdPostTypeServiceInterface;
use App\Http\Contracts\Configuration\BackendSettingServiceInterface;
use App\Http\Contracts\Configuration\MobileSettingServiceInterface;
use App\Http\Contracts\Configuration\SystemConfigServiceInterface;
use App\Http\Contracts\Item\ItemServiceInterface;
use App\Http\Contracts\Item\PaidItemHistoryServiceInterface;
use App\Http\Contracts\Localization\LanguageServiceInterface;
use App\Http\Contracts\User\BlockUserServiceInterface;
use App\Http\Contracts\User\UserInfoServiceInterface;
use App\Http\Contracts\User\UserServiceInterface;
use App\Http\Contracts\Utilities\CoreFieldServiceInterface;
use App\Http\Contracts\Utilities\CustomFieldAttributeServiceInterface;
use App\Http\Contracts\Utilities\CustomFieldServiceInterface;
use App\Http\Contracts\Utilities\UiTypeServiceInterface;
use App\Http\Contracts\Vendor\VendorServiceInterface;
use App\Models\User;
use Illuminate\Contracts\Translation\Translator;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Mockery;
use Modules\Core\Actions\Item\CreateItemAction;
use Modules\Core\Actions\Item\SearchItemAction;
use Modules\Core\Actions\Item\UpdateItemAction;
use Modules\Core\Http\Controllers\Backend\Rests\App\V1_0\Item\ItemApiController;
use Modules\Core\Http\Services\Item\ComplaintItemService;
use Modules\Core\Http\Services\SearchHistoryService;
use Modules\Core\Http\Services\UserAccessApiTokenService;
use Tests\TestCase;

class ItemApiControllerTest extends TestCase
{
    use DatabaseTransactions;

    protected $user;

    protected $vendorService;

    protected $paidItemHistoryService;

    protected $translator;

    protected $itemService;

    protected $systemConfigService;

    protected $userInfoService;

    protected $userService;

    protected $mobileSettingService;

    protected $coreFieldServcie;

    protected $customFieldService;

    protected $languageService;

    protected $customFieldAttributeService;

    protected $uiTypeService;

    protected $blockUserService;

    protected $complaintItemService;

    protected $adPostTypeService;

    protected $searchHistoryService;

    protected $backendSettingService;

    protected $categoryService;

    protected $permissionService;

    protected $createItemAction;

    protected $updateItemAction;

    protected $searchItemAction;

    protected $userAccessApiTokenService;

    protected $itemApiController;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();

        $this->userAccessApiTokenService = Mockery::mock(UserAccessApiTokenService::class);

        $this->vendorService = Mockery::mock(VendorServiceInterface::class);
        $this->paidItemHistoryService = Mockery::mock(PaidItemHistoryServiceInterface::class);
        $this->translator = Mockery::mock(Translator::class);
        $this->itemService = Mockery::mock(ItemServiceInterface::class);
        $this->systemConfigService = Mockery::mock(SystemConfigServiceInterface::class);
        $this->userInfoService = Mockery::mock(UserInfoServiceInterface::class);
        $this->userService = Mockery::mock(UserServiceInterface::class);
        $this->mobileSettingService = Mockery::mock(MobileSettingServiceInterface::class);
        $this->coreFieldServcie = Mockery::mock(CoreFieldServiceInterface::class);
        $this->customFieldService = Mockery::mock(CustomFieldServiceInterface::class);
        $this->languageService = Mockery::mock(LanguageServiceInterface::class);
        $this->customFieldAttributeService = Mockery::mock(CustomFieldAttributeServiceInterface::class);
        $this->uiTypeService = Mockery::mock(UiTypeServiceInterface::class);
        $this->blockUserService = Mockery::mock(BlockUserServiceInterface::class);
        $this->complaintItemService = Mockery::mock(ComplaintItemService::class);
        $this->adPostTypeService = Mockery::mock(AdPostTypeServiceInterface::class);
        $this->searchHistoryService = Mockery::mock(SearchHistoryService::class);
        $this->backendSettingService = Mockery::mock(BackendSettingServiceInterface::class);
        $this->categoryService = Mockery::mock(CategoryServiceInterface::class);
        $this->permissionService = Mockery::mock(PermissionServiceInterface::class);
        $this->createItemAction = Mockery::Mock(CreateItemAction::class);
        $this->updateItemAction = Mockery::mock(UpdateItemAction::class);
        $this->searchItemAction = Mockery::mock(SearchItemAction::class);

        $this->itemApiController = new ItemApiController(
            $this->vendorService,
            $this->paidItemHistoryService,
            $this->translator,
            $this->itemService,
            $this->systemConfigService,
            $this->userInfoService,
            $this->userService,
            $this->mobileSettingService,
            $this->coreFieldServcie,
            $this->customFieldService,
            $this->languageService,
            $this->customFieldAttributeService,
            $this->uiTypeService,
            $this->blockUserService,
            $this->complaintItemService,
            $this->adPostTypeService,
            $this->searchHistoryService,
            $this->backendSettingService,
            $this->categoryService,
            $this->permissionService,
            $this->createItemAction,
            $this->updateItemAction,
            $this->searchItemAction,
        );
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_dummy()
    {
        $this->assertTrue(true);
    }
}
