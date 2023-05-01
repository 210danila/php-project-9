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

    $validator = new Valitron\Validator(['name' => $url]);
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

$container = new Container();
$container->set('renderer', function () {
    return new \Slim\Views\PhpRenderer(__DIR__ . '/../templates');
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
    $urls = $controller->selectUrls();
    $sortedUrls = collect($urls)->sortByDesc('id');

    $params = ['urls' => $sortedUrls];
    return $this->get('renderer')->render($response, "urls.html", $params);
})->setName('urls');

$app->get('/urls/{id}', function (Request $request, Response $response, array $args) {
    $controller = getController();
    $id = $args['id'];
    $url = $controller->selectUrl('id', $id);

    $params = ['url' => $url];
    return $this->get('renderer')->render($response, "url.html", $params);
})->setName('url');

$app->post('/urls', function (Request $request, Response $response) use ($router) {
    $urlName = $request->getParsedBodyParam('url')['name'];
    $errors = validateUrl($urlName);

    if (empty($errors)) {
        $controller = getController();
        flash('yo');
        try {
            print_r('fa');
            $controller->insertUrl($urlName);
        } catch (\Exception $e) {
        }
        $id = $controller->selectUrl('name', $urlName)['id'];
        return $response->withRedirect($router->urlFor('url', ['id' => $id]), 302);
    }

    $params = [
        'errors' => $errors,
        'urlName' => $urlName
    ];
    return $this->get('renderer')->render($response, "index.html", $params);
});

$app->run();
