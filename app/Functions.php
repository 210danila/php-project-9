<?php

namespace App\Functions;

use DiDom\Document;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;
use Illuminate\Support\Arr;

function normalizeUrl(string $urlName)
{
    $urlName = mb_strtolower(trim($urlName));
    if (empty($urlName)) {
        return '';
    }
    $parsedUrl = parse_url($urlName);
    if ($parsedUrl === false) {
        return $urlName;
    }
    $scheme = Arr::get($parsedUrl, 'scheme');
    $host = Arr::get($parsedUrl, 'host');
    if (empty($scheme) and empty($host) and $urlName !== '://') {
        return $urlName;
    }
    return $scheme . '://' . $host;
}

function generateUrlCheck(array $url)
{
    $client = new Client();
    try {
        $response = $client->request('GET', $url['name']);
    } catch (ClientException $e) {
        $response = $e->getResponse();
    }
    $statusCode = $response->getStatusCode();
    $urlId = $url['id'];

    $body = (string) $response->getBody();
    $document = new Document($body);
    $h1 = $document->first('h1::text()');
    $title = $document->first('title::text()');
    $description = $document->first('meta[name=description][content]::attr(content)');

    $formatContent = fn ($text) => trim($text);
    return [
        'url_id' => $urlId,
        'status_code' => $statusCode,
        'h1' => optional($h1, $formatContent),
        'title' => optional($title, $formatContent),
        'description' => optional($description, $formatContent)
    ];
}
