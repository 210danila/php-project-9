<?php

namespace App\Functions;

use Illuminate\Support\Arr;

function normalizeUrl(string $urlName)
{

    $urlName = mb_strtolower(trim($urlName));
    $parsedUrl = parse_url($urlName);
    $scheme = Arr::get($parsedUrl, 'scheme');
    $host = Arr::get($parsedUrl, 'host');
    return $scheme . '://' . $host;
}
