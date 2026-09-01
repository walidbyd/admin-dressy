<?php

namespace Modules\Core\Http\Controllers\Backend\Controllers\Authorization;

use App\Config\Cache\PersonalAccessTokenCache;
use App\Config\ps_constant;
use App\Http\Contracts\Authorization\ApiTokenServiceInterface;
use App\Http\Controllers\PsController;
use App\Models\ApiToken;
use Illuminate\Http\Request;
use Laravel\Jetstream\Jetstream;
use Modules\Core\Constants\Constants;
use Modules\Core\Http\Facades\PsCache;
use Modules\Core\Http\Requests\Authorization\StoreApiTokenRequest;
use Modules\Core\Http\Requests\Authorization\UpdateApiTokenRequest;
use Modules\Core\Transformers\Backend\Model\Authorization\ApiTokenWithKeyResource;

class ApiTokenController extends PsController
{
    private const parentPath = 'api_token/';

    private const indexPath = self::parentPath.'Index';

    private const createPath = self::parentPath.'Create';

    private const editPath = self::parentPath.'Edit';

    private const indexRoute = 'api_token.index';

    private const createRoute = 'api_token.create';

    private const editRoute = 'api_token.edit';

    public function __construct(protected ApiTokenServiceInterface $apiTokenService)
    {
        parent::__construct();
    }

    public function index(Request $request)
    {
        // check permission
        $this->handlePermissionWithModel(ApiToken::class, Constants::viewAnyAbility);

        $dataArr = $this->prepareIndexData($request);

        return renderView(self::indexPath, $dataArr);
    }

    public function create()
    {
        // check permission start
        $this->handlePermissionWithModel(ApiToken::class, Constants::createAbility);

        $dataArr = $this->prepareCreateData();

        return renderView(self::createPath, $dataArr);
    }

    public function store(StoreApiTokenRequest $request)
    {
        try {
            $validateData = $request->validated();

            $token = $this->saveToken($validateData, $request);
            PsCache::clear(PersonalAccessTokenCache::BASE);

            return back()->with('flash', [
                'token' => explode('|', $token->plainTextToken, 2)[1],
            ]);
        } catch (\Exception $e) {
            return $e->getMessage();
        }
    }

    public function update(UpdateApiTokenRequest $request, $tokenId)
    {
        try {
            $token = $request->user()->tokens()->where('id', $tokenId)->firstOrFail();

            $token->forceFill([
                'abilities' => Jetstream::validPermissions($request->input('permissions', [])),
            ])->save();
            PsCache::clear(PersonalAccessTokenCache::BASE);

            return redirect()->back();
        } catch (\Exception $e) {
            return $e->getMessage();
        }
    }

    public function destroy($id)
    {
        try {
            $apiToken = $this->apiTokenService->get($id);

            $this->handlePermissionWithModel($apiToken, Constants::deleteAbility);

            $dataArr = $this->apiTokenService->delete($id);

            return redirectView(self::indexRoute, $dataArr['msg'], $dataArr['flag']);
        } catch (\Exception $e) {
            return redirectViewWithError(self::indexRoute, $e->getMessage());
        }
    }

    // //////////////////////////////////////////////////////////////////
    // / Private Functions
    // //////////////////////////////////////////////////////////////////

    // -------------------------------------------------------------------
    // Data Preparation
    // -------------------------------------------------------------------

    private function prepareIndexData($request)
    {
        $conds = [
            'searchterm' => $request->input('search') ?? '',
            'order_by' => $request->input('sort_field') ?? null,
            'order_type' => $request->input('sort_order') ?? null,
        ];
        $row = $request->input('row') ?? Constants::dataTableDefaultRow;

        $apiTokens = ApiTokenWithKeyResource::collection($this->apiTokenService->getAll(null, null, null, false, $row, $conds));

        // taking for column and columnFilterOption
        $columnAndColumnFilter = takingForColumnAndFilterOption(Constants::apiToken, $this->controlFieldArr());

        // prepare for permission
        $keyValueArr = [
            'createApiToken' => 'create-apiToken',
        ];

        $availablePermissions = Jetstream::$permissions;

        return [
            'availablePermissions' => $availablePermissions,
            'showCoreAndCustomFieldArr' => $columnAndColumnFilter[ps_constant::handlingColumn],
            'hideShowFieldForFilterArr' => $columnAndColumnFilter[ps_constant::handlingFilter],
            'apiTokens' => $apiTokens,
            'sort_field' => $conds['order_by'],
            'sort_order' => $request->sort_order,
            'search' => $conds['searchterm'],
            'can' => $this->permissionService->checkingForCreateAbilityWithModel($keyValueArr),
        ];
    }

    private function prepareCreateData()
    {
        $availablePermissions = Jetstream::$permissions;

        return [
            'availablePermissions' => $availablePermissions,
        ];
    }

    // -------------------------------------------------------------------
    // Database
    // -------------------------------------------------------------------

    private function saveToken($validateData, $request)
    {
        $token = $request->user()->createToken(
            $validateData['name'],
            Jetstream::validPermissions($request->input('permissions', []))
        );

        return $token;
    }

    // -------------------------------------------------------------------
    // Others
    // -------------------------------------------------------------------
    private function controlFieldArr()
    {
        // for control
        $controlFieldArr = [];
        $controlFieldObj = takingForColumnProps(__('core__be_action'), 'action', 'Action', false, 0);
        array_push($controlFieldArr, $controlFieldObj);

        return $controlFieldArr;
    }
}
