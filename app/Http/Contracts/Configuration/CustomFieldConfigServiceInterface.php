<?php

namespace App\Http\Contracts\Configuration;

use App\Http\Contracts\Core\PsInterface;

interface CustomFieldConfigServiceInterface extends PsInterface
{
    public function get();

    public function update($request);

}
