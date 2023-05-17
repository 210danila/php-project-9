<?php

$autoloadPath1 = __DIR__ .  '/../../autoload.php';
$autoloadPath2 = __DIR__ . '/../vendor/autoload.php';

if (file_exists($autoloadPath1)) {
    require_once $autoloadPath1;
} else {
    require_once $autoloadPath2;
}

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Factory\AppFactory;
use DI\Container;
use function App\Functions\{getController, validateUrl, normalizeUrl, generateUrlCheck};

session_start();

$container = new Container();
$container->set('renderer', function () {
    return new \Slim\Views\PhpRenderer(__DIR__ . '/../templates');
});
$container->set('flash', function () {
    return new \Slim\Flash\Messages();
});

$app = AppFactory::createFromContainer($container);
$app->addErrorMiddleware(true, true, true);

$router = $app->getRouteCollector()->getRouteParser();

$app->get('/', function (Request $request, Response $response) {
    $params = [
        'errors' => [],
        'urlName' => ''
    ];
    return $this->get('renderer')->render($response, "index.html", $params);
})->setName('root');

$app->get('/urls', function (Request $request, Response $response) {
    $controller = getController();
    $urls = $controller->makeQuery('select', 'urls')
        ->orderBy('id', 'DESC')
        ->exec();
    $urlsData = array_map(function ($url) use ($controller) {
        $check = $controller->makeQuery('select', 'url_checks')
            ->where('url_id', $url['id'])
            ->orderBy('created_at', 'DESC')
            ->exec(true);
        return ['name' => $url['name'], 'id' => $url['id'], 'check' => $check];
    }, $urls);

    $params = ['urlsData' => $urlsData];
    return $this->get('renderer')->render($response, "urls.html", $params);
})->setName('urls');

$app->get('/urls/{id}', function (Request $request, Response $response, array $args) {
    $controller = getController();
    $urlId = (int) $args['id'];

    $url = $controller->makeQuery('select', 'urls')
        ->where('id', $urlId)
        ->exec(true);
    $urlChecks = $controller->makeQuery('select', 'url_checks')
        ->where('url_id', $urlId)
        ->orderBy('id', 'DESC')
        ->exec();
    $flashMessages = $this->get('flash')->getMessages();

    $params = [
        'url' => $url,
        'urlChecks' => $urlChecks,
        'flash' => $flashMessages
    ];
    return $this->get('renderer')->render($response, "url.html", $params);
})->setName('url');

$app->post('/urls', function (Request $request, Response $response) use ($router) {
    $urlName = $request->getParsedBodyParam('url')['name'];
    $normalizedUrlName = normalizeUrl($urlName);
    $errors = validateUrl($normalizedUrlName);
    $controller = getController();

    if (empty($errors)) {
        $sameUrl = $controller->makeQuery('select', 'urls')
            ->where('name', $normalizedUrlName)
            ->exec(true);

        if (!empty($sameUrl)) {
            $this->get('flash')->addMessage('success', 'Страница уже существует');
            $urlId = $sameUrl['id'];
        } else {
            $urlId = $controller->makeQuery('insert', 'urls')
                ->values(['name' => $normalizedUrlName])
                ->exec();
            $this->get('flash')->addMessage('success', 'Страница успешно добавлена');
        }

        $redirectRoute = $router->urlFor('url', ['id' => $urlId]);
        return $response->withRedirect($redirectRoute, 302);
    }

    $params = [
        'errors' => $errors,
        'urlName' => $urlName,
    ];
    return $this->get('renderer')->render($response, "index.html", $params);
});

$app->post('/urls/{id}/checks', function (Request $request, Response $response, array $args) use ($router) {
    $controller = getController();
    $urlId = (int) $args['id'];
    $url = $controller->makeQuery('select', 'urls')
        ->where('id', $urlId)
        ->exec(true);

    try {
        $values = generateUrlCheck($url);
        $controller->makeQuery('insert', 'url_checks')
            ->values($values)
            ->exec();
        $flashMessage = 'Страница успешно проверена';
        $this->get('flash')->addMessage('success', $flashMessage);
    } catch (\Exception $e) {
        $flashMessage = 'Произошла ошибка при проверке,
        не удалось подключиться';
        $this->get('flash')->addMessage('error', $flashMessage);
    }
    return $response->withRedirect($router->urlFor('url', ['id' => $urlId]), 302);
});

$app->run();
