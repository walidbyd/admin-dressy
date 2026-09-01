<?php

namespace App\Config\Cache;

class VendorSessionCache
{
    const BASE = 'VENDOR';

    const SESSION_KEY = self::BASE.'_SESSION';

    const SESSION_EXPIRY = 60 * 60 * 3; // 3 hrs
}
