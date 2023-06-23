<?php

namespace App\Functions;

use Illuminate\Support\Arr;

function normalizeUrl(string $urlName)
{
    $urlName = mb_strtolower(trim($urlName));
    $parsedUrl = parse_url($urlName);
    if ($parsedUrl === false) {
        return $urlName;
    }
    $scheme = Arr::get($parsedUrl, 'scheme');
    $host = Arr::get($parsedUrl, 'host');
    if ((empty($scheme) or empty($host)) and $urlName !== '://') {
        return $urlName;
    }
    return $scheme . '://' . $host;
}
