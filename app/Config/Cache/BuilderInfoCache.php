<?php

namespace App\Config\Cache;

class BuilderInfoCache
{
    const BASE = 'BUILDER';

    // For Builder App Info Fetch Cache Key
    const INFO_KEY = self::BASE.'_INFO';

    const INFO_EXPIRY = 30; // * 60 * 3; // 3 hrs

    // For retriving Builder App Info
    const GET_KEY = self::BASE.'_FIRST';

    const GET_EXPIRY = 60 * 60 * 24;
}
