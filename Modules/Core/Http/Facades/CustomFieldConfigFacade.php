<?php

namespace Modules\Core\Http\Facades;

use Illuminate\Support\Facades\Facade;

class CustomFieldConfigFacade extends Facade
{
    protected static function getFacadeAccessor()
    {
        return 'ps_custom_field_config';
    }
}
