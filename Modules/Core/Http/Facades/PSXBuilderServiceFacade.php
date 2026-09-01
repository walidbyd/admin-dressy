<?php

namespace Modules\Core\Http\Facades;

use Illuminate\Support\Facades\Facade;

class PSXBuilderServiceFacade extends Facade
{
    protected static function getFacadeAccessor()
    {
        return 'ps_builder_service';
    }
}
