<?php

namespace App\Http\Contracts\Core;

interface PsInterface
{
    public function checkPermission($ability = null, $model = null, $routeName = null, $msg = null);
}
