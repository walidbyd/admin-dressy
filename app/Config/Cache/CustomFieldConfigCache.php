<?php

namespace App\Config\Cache;

class CustomFieldConfigCache
{
    const BASE = 'CUSTOM_FIELD_CONFIG';

    const GET_KEY = self::BASE.'_FIRST';

    const GET_EXPIRY = 60 * 60 * 24;
}
