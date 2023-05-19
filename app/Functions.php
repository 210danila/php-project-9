<?php

namespace App\Functions;

use App\Connection;
use App\DBController;
use DiDom\Document;
use GuzzleHttp\Client;
use Illuminate\Support\Arr;

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

function normalizeUrl(string $urlName)
{
    $parsedUrl = parse_url($urlName);
    if (empty($urlName)) {
        return '';
    }
    if ($parsedUrl === false) {
        return $urlName;
    }
    $sheme = Arr::get($parsedUrl, 'scheme');
    $host = Arr::get($parsedUrl, 'host');
    return $sheme . '://' . $host;
}

function generateUrlCheck(array $url)
{
    $client = new Client();
    $response = $client->request('GET', $url['name']);
    $statusCode = $response->getStatusCode();
    $urlId = $url['id'];

    $document = new Document($url['name'], true);
    $h1 = $document->first('h1::text()');
    $title = $document->first('title::text()');
    $description = $document->first('meta[name=description][content]::attr(content)');

    $formatContent = fn ($text) => mb_substr(trim($text), 0, 200);
    return [
        'url_id' => $urlId,
        'status_code' => $statusCode,
        'h1' => optional($h1, $formatContent),
        'title' => optional($title, $formatContent),
        'description' => optional($description, $formatContent)
    ];
}
