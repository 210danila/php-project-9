<?php

namespace App\Functions;

use App\Connection;
use App\DBController;

function getController()
{
    $pdo = Connection::get()->connect();
    return new DBController($pdo);
}

function validateUrl(string $url)
{
    if (empty($url)) {
        return ['URL не должен быть пустым'];
    }
    if ($url === 'https://' || $url === 'http://') {
        return ['Некорректный URL'];
    }

    $validator = new \Valitron\Validator(['name' => $url]);
    $validator->rule('required', 'name')
    ->rule('lengthMax', 'name', 255)
    ->rule('url', 'name')
    ->message('Некорректный URL');

    return $validator->validate() ? [] : ['Некорректный URL'];
}

function normalizeUrl($urlName)
{
    $parsedUrl = parse_url($urlName);
    return $parsedUrl['scheme'] . '://' . $parsedUrl['host'];
}
