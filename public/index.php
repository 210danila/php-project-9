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
    dump(getenv('PGPASSWORD'), getenv('PGUSER'), getenv('DATABASE_URL'));
    $params = [
        'errors' => [],
        'urlName' => ''
    ];
    return $this->get('renderer')->render($response, "index.html", $params);
})->setName('root');

$app->get('/urls', function (Request $request, Response $response) {
    $controller = getController();
    $urls = $controller->selectUrls();
    $sortedUrls = collect($urls)->sortByDesc('id');

    $params = ['urls' => $sortedUrls];
    return $this->get('renderer')->render($response, "urls.html", $params);
})->setName('urls');

$app->get('/urls/{id}', function (Request $request, Response $response, array $args) {
    $controller = getController();
    $id = $args['id'];
    $url = $controller->selectUrl('id', $id);
    $flashMessages = $this->get('flash')->getMessages();

    $params = ['url' => $url, 'flash' => $flashMessages];
    return $this->get('renderer')->render($response, "url.html", $params);
})->setName('url');

$app->post('/urls', function (Request $request, Response $response) use ($router) {
    $urlName = $request->getParsedBodyParam('url')['name'];
    $normalizedUrlName = normalizeUrl($urlName);
    $errors = validateUrl($normalizedUrlName);

    if (empty($errors)) {
        $controller = getController();
        try {
            $controller->insertUrl($normalizedUrlName);
            $this->get('flash')->addMessage('success', 'Страница успешно добавлена');
        } catch (\Exception $e) {
            $this->get('flash')->addMessage('success', 'Страница уже существует');
        }
        $id = $controller->selectUrl('name', $normalizedUrlName)['id'];
        return $response->withRedirect($router->urlFor('url', ['id' => $id]), 302);
    }

    $params = [
        'errors' => $errors,
        'urlName' => $normalizedUrlName
    ];
    return $this->get('renderer')->render($response, "index.html", $params);
});

$app->run();
