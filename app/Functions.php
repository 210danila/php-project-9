<?php

namespace App\Functions;

use App\Connection;
use App\DBController;
use DiDom\Document;
use GuzzleHttp\Client;

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

    $validator = new \Valitron\Validator(['name' => $url]);
    $validator->rule('required', 'name')
        ->rule('lengthMax', 'name', 255)
        ->rule('url', 'name');

    return $validator->validate() ? [] : ['Некорректный URL'];
}

function normalizeUrl($urlName)
{
    if (empty($urlName)) {
        return '';
    }
    $parsedUrl = parse_url($urlName);
    return $parsedUrl['scheme'] . '://' . $parsedUrl['host'];
}

function generateUrlCheck($url)
{
    $client = new Client();
    $res = $client->request('GET', $url['name']);
    $statusCode = $res->getStatusCode();
    $urlId = $url['id'];

    $document = new Document($url['name'], true);
    $formatContent = fn ($text) => mb_substr(trim($text), 0, 200);
    $h1 = $document->first('h1::text()');
    $title = $document->first('title::text()');
    $description = $document->first('meta[name=description][content]::attr(content)');

    return [
        'url_id' => $urlId,
        'status_code' => $statusCode,
        'h1' => optional($h1, $formatContent),
        'title' => optional($title, $formatContent),
        'description' => optional($description, $formatContent)
    ];
}
