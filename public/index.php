<?php

$autoloadPath1 = __DIR__ .  '/../../autoload.php';
$autoloadPath2 = __DIR__ . '/../vendor/autoload.php';

if (file_exists($autoloadPath1)) {
    require_once $autoloadPath1;
} else {
    require_once $autoloadPath2;
}

use Slim\Http\{Response, ServerRequest as Request};
use Slim\Exception\HttpException;
use Slim\Factory\AppFactory;
use DI\Container;
use function App\Functions\{validateUrl, normalizeUrl, generateUrlCheck};

session_start();

$container = new Container();
$dependencies = require __DIR__ . '/dependencies.php';
$dependencies($container);

$app = AppFactory::createFromContainer($container);
$app->addErrorMiddleware(true, true, true);

$router = $app->getRouteCollector()->getRouteParser();

$app->get('/', function (Request $request, Response $response) use ($router) {
    $params = [
        'errors' => [],
        'urlName' => '',
        'router' => $router,
        'activeLink' => 'Главная'
    ];
    return $this->get('renderer')->render($response, "index.php", $params);
})->setName('root');

$app->get('/urls', function (Request $request, Response $response) use ($router) {
    $urls = $this->get('db')
        ->makeQuery('select', 'urls')
        ->orderBy('id', 'DESC')
        ->exec();
    $urlsData = array_map(function ($url) {
        $check = $this->get('db')
            ->makeQuery('select', 'url_checks')
            ->where('url_id', $url['id'])
            ->orderBy('created_at', 'DESC')
            ->exec(true);
        return [
            'name' => $url['name'],
            'id' => $url['id'],
            'check' => $check
        ];
    }, $urls);

    $params = [
        'urlsData' => $urlsData,
        'router' => $router,
        'activeLink' => 'Сайты'
    ];
    return $this->get('renderer')->render($response, "urls/index.php", $params);
})->setName('urls.index');

$app->get('/urls/{id}', function (Request $request, Response $response, array $args) use ($router) {
    $urlId = (int) $args['id'];

    $url = $this->get('db')
        ->makeQuery('select', 'urls')
        ->where('id', $urlId)
        ->exec(true);
    $urlChecks = $this->get('db')
        ->makeQuery('select', 'url_checks')
        ->where('url_id', $urlId)
        ->orderBy('id', 'DESC')
        ->exec();
    $flashMessages = $this->get('flash')->getMessages();

    $params = [
        'url' => $url,
        'urlChecks' => $urlChecks,
        'flash' => $flashMessages,
        'router' => $router,
        'activeLink' => ''
    ];
    return $this->get('renderer')->render($response, "urls/show.php", $params);
})->setName('urls.show');

$app->post('/urls', function (Request $request, Response $response) use ($router) {
    $urlName = $request->getParsedBodyParam('url')['name'];

    $normalizedUrlName = normalizeUrl($urlName);
    $errors = validateUrl($normalizedUrlName);

    if (empty($errors)) {
        $sameUrl = $this->get('db')
            ->makeQuery('select', 'urls')
            ->where('name', $normalizedUrlName)
            ->exec(true);

        if (!empty($sameUrl)) {
            $this->get('flash')->addMessage('success', 'Страница уже существует');
            $urlId = $sameUrl['id'];
        } else {
            $urlId = $this->get('db')
                ->makeQuery('insert', 'urls')
                ->values(['name' => $normalizedUrlName])
                ->exec();
            $this->get('flash')->addMessage('success', 'Страница успешно добавлена');
        }

        $redirectRoute = $router->urlFor('urls.show', ['id' => (string) $urlId]);
        return $response->withRedirect($redirectRoute, 302);
    }

    $params = [
        'errors' => $errors,
        'urlName' => $urlName,
        'router' => $router,
        'activeLink' => 'Сайты'
    ];
    return $this
        ->get('renderer')
        ->render($response, "index.php", $params)
        ->withStatus(422);
})->setName('urls.store');

$app->post('/urls/{id}/checks', function (Request $request, Response $response, array $args) use ($router) {
    $urlId = (int) $args['id'];
    $url = $this->get('db')
        ->makeQuery('select', 'urls')
        ->where('id', $urlId)
        ->exec(true);

    try {
        $values = generateUrlCheck($url);
        $this->get('db')
            ->makeQuery('insert', 'url_checks')
            ->values($values)
            ->exec();
        $flashMessage = 'Страница успешно проверена';
        $this->get('flash')->addMessage('success', $flashMessage);
    } catch (HttpException $e) {
        $flashMessage = 'Произошла ошибка при проверке,
        не удалось подключиться';
        $this->get('flash')->addMessage('error', $flashMessage);
    }
    $redirectRoute = $router->urlFor('urls.show', ['id' => (string) $urlId]);
    return $response->withRedirect($redirectRoute, 302);
})->setName('urls.checks.store');

$app->run();
