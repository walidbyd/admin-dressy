<?php

namespace Modules\Core\Http\Services;

use App\Config\ps_config;
use App\Http\Services\PsService;
use Modules\Core\Constants\Constants;
use Modules\Core\Entities\CoreKeyType;
use Modules\Core\Entities\Table;
use Modules\Core\Entities\TableUsedType;
use Modules\Core\Http\Services\Localization\BeLanguageStringService;
use Modules\Core\Http\Services\Localization\LanguageService;
use Modules\Core\Http\Services\Utilities\CustomFieldService;
use Modules\Core\Transformers\Backend\Model\Table\TableWithKeyResource;

class TableService extends PsService
{
    protected $internalServerErrorStatusCode;

    protected $languageStringService;

    protected $languageService;

    protected $pagePerPag;

    protected $customizeUiService;

    protected $coreFieldFilterSettingService;

    protected $okStatusCode;

    public function __construct(BeLanguageStringService $languageStringService, LanguageService $languageService, CustomFieldService $customizeUiService, CoreFieldFilterSettingService $coreFieldFilterSettingService)
    {
        $this->pagePerPag = ps_config::pagPerPage;
        $this->customizeUiService = $customizeUiService;
        $this->languageService = $languageService;
        $this->languageStringService = $languageStringService;
        $this->coreFieldFilterSettingService = $coreFieldFilterSettingService;

        $this->tableNameCol = Table::name;

        $this->viewAnyAbility = Constants::viewAnyAbility;
        $this->createAbility = Constants::createAbility;
        $this->editAbility = Constants::editAbility;
        $this->deleteAbility = Constants::deleteAbility;
        $this->okStatusCode = Constants::okStatusCode;
        $this->internalServerErrorStatusCode = Constants::internalServerErrorStatusCode;
    }

    public function getTables($relation = null, $withNoPag = null, $conds = null, $loading = null)
    {
        $tables = Table::when($relation, function ($q, $relation) {
            $q->with($relation);
        })
            ->when($conds, function ($query, $conds) {
                // search term
                if (isset($conds['keyword']) && $conds['keyword']) {
                    $query->where($this->tableNameCol, 'LIKE', '%'.$conds['keyword'].'%');
                }

                if (isset($conds['table_used_type_id']) && $conds['table_used_type_id']) {
                    if ($conds['table_used_type_id'] == 99) {
                        $query->where('table_used_type_id', null);
                    } else {
                        $query->where('table_used_type_id', $conds['table_used_type_id']);
                    }
                }

                // order by
                if (isset($conds['order_by']) && isset($conds['order_type']) && $conds['order_by'] && $conds['order_type']) {
                    $query->orderBy($conds['order_by'], $conds['order_type']);
                } elseif (isset($conds['order_by']) && $conds['order_by']) {
                    $query->orderBy($conds['order_by']);
                }
            });

        if ($withNoPag) {
            $tables = $tables->get();
        } else {
            $counts = $tables->count();

            if ($loading) {
                $tables = $tables->paginate($counts);
            } else {
                $tables = $tables->paginate(9)->onEachSide(1)->withQueryString();
            }
        }

        return $tables;
    }

    public function getCoreKeyTypes($relation = null, $withNoPag = null, $conds = null, $loading = null)
    {
        $coreKeyTypes = CoreKeyType::when($relation, function ($q, $relation) {
            $q->with($relation);
        })
            ->when($conds, function ($query, $conds) {
                // search term
                if (isset($conds['keyword']) && $conds['keyword']) {
                    $query->where('name', 'LIKE', '%'.$conds['keyword'].'%');
                    $query->orWhere('description', 'LIKE', '%'.$conds['keyword'].'%');
                }

                // order by
                if (isset($conds['order_by']) && isset($conds['order_type']) && $conds['order_by'] && $conds['order_type']) {
                    $query->orderBy($conds['order_by'], $conds['order_type']);
                } elseif (isset($conds['order_by']) && $conds['order_by']) {
                    $query->orderBy($conds['order_by']);
                }
            });

        if ($withNoPag) {
            $coreKeyTypes = $coreKeyTypes->get();
        } else {
            $counts = $coreKeyTypes->count();

            if ($loading) {
                $coreKeyTypes = $coreKeyTypes->paginate($counts);
            } else {
                $coreKeyTypes = $coreKeyTypes->paginate(9)->onEachSide(1)->withQueryString();
            }
        }

        return $coreKeyTypes;
    }

    public function getTable($id = null)
    {
        $table = Table::when($id, function ($q, $id) {
            $q->where('id', $id);
        })
            ->first();

        return $table;
    }

    public function getCoreKeyType($id = null)
    {
        $coreKeyType = CoreKeyType::when($id, function ($q, $id) {
            $q->where('id', $id);
        })
            ->first();

        return $coreKeyType;
    }

    public function index($request)
    {

        $checkPermission = $this->checkPermission($this->viewAnyAbility, Table::class, 'admin.index');

        $isloaded = 0;
        $isSorting = 0;

        $tableUsedTypes = TableUsedType::all();

        $search = $request->input('search') ?? '';
        $conds['keyword'] = $search;

        if ($request->loading == 1) {
            $isloaded = 1;
        }

        if ($request->sorting && $request->sorting == 1) {
            $conds['order_by'] = 'name';
            $conds['order_type'] = 'desc';
            $isSorting = 0;
        } else {
            $conds['order_by'] = 'name';
            $conds['order_type'] = 'asc';
            $isSorting = 1;
        }

        $table_used_type_id = 1;
        if ($request->tableUsedTypeId) {
            $table_used_type_id = $request->tableUsedTypeId;
        }
        $conds['table_used_type_id'] = $table_used_type_id;

        if ($request->loading && $request->loading == true) {

            $tables = TableWithKeyResource::collection($this->getTables(null, null, $conds, 1));
        } else {
            $tables = TableWithKeyResource::collection($this->getTables(null, null, $conds));
        }

        $dataArr = [
            'checkPermission' => $checkPermission,
            'tables' => $tables,
            'loadMore' => $isloaded,
            'search' => $search,
            'tableUsedTypes' => $tableUsedTypes,
            'tableUsedTypeId' => $table_used_type_id,
            'sorting' => $isSorting,
        ];

        return $dataArr;
    }
}
