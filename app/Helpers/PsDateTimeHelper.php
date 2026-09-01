<?php

use Carbon\Carbon;

if (! function_exists('getTodayDateTimeStamp')) {
    function getRoundedFiveMinuteTimestamp()
    {
        $today = Carbon::now()->minute((int) (Carbon::now()->minute / 5) * 5)->second(0);

        return strtotime($today->toDateTimeString());
    }
}
