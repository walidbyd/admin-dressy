<?php

namespace Modules\Core\Http\Controllers\Backend\Rests\App\V1_0\Table;

use Illuminate\Contracts\Support\Renderable;
use Illuminate\Routing\Controller;
use Modules\Core\Constants\Constants;
use Modules\Core\Http\Services\TableService;

class TableApiController extends Controller
{
    protected $tableService;

    protected $okStatusCode;

    protected $sucessStatus;

    protected $errorStatus;

    protected $internalServerErrorStatusCode;

    public function __construct(TableService $tableService)
    {
        $this->tableService = $tableService;
        $this->okStatusCode = Constants::okStatusCode;
        $this->sucessStatus = Constants::successStatus;
        $this->errorStatus = Constants::errorStatus;
        $this->internalServerErrorStatusCode = Constants::internalServerErrorStatusCode;

    }

    public function index()
    {
        return response()->json([
            'message' => 'san kyi tar par',
        ], 200);
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
}
