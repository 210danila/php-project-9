<?php

$autoloadPath1 = __DIR__ .  '/../../autoload.php';
$autoloadPath2 = __DIR__ . '/../vendor/autoload.php';

if (file_exists($autoloadPath1)) {
    require_once $autoloadPath1;
} else {
    require_once $autoloadPath2;
}

use Slim\Http\{Response, ServerRequest as Request};
use GuzzleHttp\Exception\TransferException;
use Slim\Factory\AppFactory;
use DI\Container;

use function App\Functions\{normalizeUrl, generateUrlCheck};

const INCORRECT_URL = 'Некорректный URL';

session_start();

$container = new Container();

$app = AppFactory::createFromContainer($container);
$dependencies = require 'dependencies.php';
$dependencies($container, $app);

$app->addErrorMiddleware(true, true, true);

$app->get('/', function (Request $request, Response $response) {
    $params = [
        'errors' => [],
        'urlName' => '',
        'activeLink' => 'Главная'
    ];
    return $this->get('renderer')->render($response, "index.php", $params);
})->setName('root');

$app->get('/urls', function (Request $request, Response $response) {
    $urls = $this->get('db')
        ->makeQuery('select', 'urls')
        ->orderBy('id', 'DESC')
        ->exec();
    $urlChecks = $this->get('db')
        ->makeQuery('select', 'url_checks')
        ->distinct('url_id')
        ->orderBy('url_id, created_at', 'DESC')
        ->exec();
    $urlChecks = collect($urlChecks)->keyBy('url_id')->toArray();

    $params = [
        'urls' => $urls,
        'urlChecks' => $urlChecks,
        'activeLink' => 'Сайты'
    ];
    return $this->get('renderer')->render($response, "urls/index.php", $params);
})->setName('urls.index');

$app->get('/urls/{id:\d+}', function (Request $request, Response $response, array $args) {
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

    $params = [
        'url' => $url,
        'urlChecks' => $urlChecks,
        'activeLink' => ''
    ];
    return $this->get('renderer')->render($response, "urls/show.php", $params);
})->setName('urls.show');

$app->post('/urls', function (Request $request, Response $response) {
    $urlName = $request->getParsedBodyParam('url')['name'];
    $normalizedUrlName = normalizeUrl($urlName);

    $validator = new \Valitron\Validator(['name' => $normalizedUrlName]);
    $validator->rule('required', 'name')->message(INCORRECT_URL);
    $validator->rule('lengthMax', 'name', 255)->message(INCORRECT_URL);
    $validator->rule('url', 'name')->message(INCORRECT_URL);

    if ($validator->validate()) {
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

        $redirectRoute = $this->get('router')->urlFor('urls.show', ['id' => (string) $urlId]);
        return $response->withRedirect($redirectRoute, 302);
    }

    $errors = $validator->errors();
    $nameErrors = is_array($errors) ? $errors['name'] : [];
    if (empty($normalizedUrlName)) {
        $nameErrors = ['URL не должен быть пустым'];
    }
    $params = [
        'errors' => $nameErrors,
        'urlName' => $normalizedUrlName,
        'activeLink' => 'Сайты'
    ];
    return $this
        ->get('renderer')
        ->render($response, "index.php", $params)
        ->withStatus(422);
})->setName('urls.store');

$app->post('/urls/{id:\d+}/checks', function (Request $request, Response $response, array $args) {
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
    } catch (TransferException $e) {
        $flashMessage = 'Произошла ошибка при проверке,
        не удалось подключиться';
        $this->get('flash')->addMessage('error', $flashMessage);
    }
    $redirectRoute = $this->get('router')->urlFor('urls.show', ['id' => (string) $urlId]);
    return $response->withRedirect($redirectRoute, 302);
})->setName('urls.checks.store');

$app->run();
