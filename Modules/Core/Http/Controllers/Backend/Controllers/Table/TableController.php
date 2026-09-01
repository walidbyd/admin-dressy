<?php

namespace Modules\Core\Http\Controllers\Backend\Controllers\Table;

use Illuminate\Contracts\Support\Renderable;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Core\Constants\Constants;
use Modules\Core\Http\Services\ProjectService;
use Modules\Core\Http\Services\TableService;

class TableController extends Controller
{
    const parentPath = 'table/';

    const indexPath = self::parentPath.'Index';

    const createPath = self::parentPath.'Create';

    const editPath = self::parentPath.'Edit';

    const indexRoute = 'table.index';

    const createRoute = 'table.create';

    const editRoute = 'table.edit';

    protected $projectService;

    protected $tableService;

    protected $successFlag;

    protected $dangerFlag;

    protected $csvFile;

    protected $warningFlag;

    public function __construct(ProjectService $projectService, TableService $tableService)
    {
        $this->tableService = $tableService;
        $this->projectService = $projectService;
        $this->successFlag = Constants::success;
        $this->dangerFlag = Constants::danger;
        $this->warningFlag = Constants::warning;
        $this->csvFile = Constants::csvFile;
    }

    public function index(Request $request)
    {
        if (empty($this->projectService->getProject()->ps_license_code)) {
            return redirect()->route('admin.index');
        }

        $dataArr = $this->tableService->index($request);

        if (! empty($checkPermission)) {
            return $checkPermission;
        }

        return renderView(self::indexPath, $dataArr);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return Renderable
     */
    public function create()
    {
        return view('core::create');
    }

    /**
     * Store a newly created resource in storage.
     *
     * @return Renderable
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Show the specified resource.
     *
     * @param  int  $id
     * @return Renderable
     */
    public function show($id)
    {
        return view('core::show');
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return Renderable
     */
    public function edit($id)
    {
        return view('core::edit');
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  int  $id
     * @return Renderable
     */
    public function update(Request $request, $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return Renderable
     */
    public function destroy($id)
    {
        //
    }
}
