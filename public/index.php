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
use function App\Functions\getController;
use function App\Functions\validateUrl;
use function App\Functions\normalizeUrl;

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
    $params = ['urls' => $urls];
    return $this->get('renderer')->render($response, "urls.html", $params);
})->setName('urls');

$app->get('/urls/{id}', function (Request $request, Response $response, array $args) {
    $controller = getController();
    $id = (int) $args['id'];
    $url = $controller->makeQuery('select', 'urls')->where('id', $id)->exec(true);
    $urlChecks = $controller->makeQuery('select', 'url_checks')
        ->where('url_id', $id)
        ->orderBy('id', 'DESC')
        ->exec();

    $flashMessages = $this->get('flash')->getMessages();
    $params = ['url' => $url, 'flash' => $flashMessages, 'urlChecks' => $urlChecks];
    return $this->get('renderer')->render($response, "url.html", $params);
})->setName('url');

$app->post('/urls', function (Request $request, Response $response) use ($router) {
    $urlName = $request->getParsedBodyParam('url')['name'];
    $errors = validateUrl($urlName);

    if (empty($errors)) {
        $normalizedUrlName = normalizeUrl($urlName);
        $controller = getController();

        $isNameInDB = $controller->makeQuery('select', 'urls')
            ->where('name', $normalizedUrlName)
            ->exec(true);
        if ($isNameInDB) {
            $this->get('flash')->addMessage('success', 'Страница уже существует');
        } else {
            $urlId = $controller->makeQuery('insert', 'urls')
                ->values(['name' => $normalizedUrlName])
                ->exec();
            $this->get('flash')->addMessage('success', 'Страница успешно добавлена');
        }

        return $response->withRedirect($router->urlFor('url', ['id' => $urlId]), 302);
    }

    $params = [
        'errors' => $errors,
        'urlName' => $urlName
    ];
    return $this->get('renderer')->render($response, "index.html", $params);
});

$app->post('/urls/{id}/checks', function (Request $request, Response $response, array $args) use ($router) {
    $controller = getController();
    $url_id = (int) $args['id'];

    $controller->makeQuery('insert', 'url_checks')
        ->values(['url_id' => $url_id])
        ->exec();
    return $response->withRedirect($router->urlFor('url', ['id' => $url_id]), 302);
});

$app->run();
