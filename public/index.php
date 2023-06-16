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
use App\{Connection, DBController};
use Slim\Views\PhpRenderer;
use function App\Functions\{normalizeUrl, generateUrlCheck};

session_start();

$container = new Container();
AppFactory::setContainer($container);
$app = AppFactory::create();

$container = $app->getContainer();
if (!is_null($container)) {
    $container->set('router', $app->getRouteCollector()->getRouteParser());
    $container->set('renderer', function ($container) {
        $phpView = new PhpRenderer(__DIR__ . '/../templates');
        $phpView->addAttribute('router', $container->get('router'));
        $phpView->setLayout('layout.php');
        return $phpView;
    });
    $container->set('flash', function () {
        return new \Slim\Flash\Messages();
    });
    $container->set('db', function () {
        $pdo = Connection::get();
        return new DBController($pdo);
    });
}

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
    $checkSatusCodes = collect($urlChecks)->pluck('status_code', 'url_id')->toArray();
    $checkTimestamps = collect($urlChecks)->pluck('created_at', 'url_id')->toArray();

    $urlsData = array_map(function ($url) use ($checkSatusCodes, $checkTimestamps) {
        $url_id = $url['id'];
        return [
            'name' => $url['name'],
            'id' => $url_id,
            'check_status_code' => $checkSatusCodes[$url_id] ?? '',
            'check_created_at' => $checkTimestamps[$url_id] ?? ''
        ];
    }, $urls);

    $params = [
        'urlsData' => $urlsData,
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
    $flashMessages = $this->get('flash')->getMessages();

    $params = [
        'url' => $url,
        'urlChecks' => $urlChecks,
        'flash' => $flashMessages,
        'activeLink' => ''
    ];
    return $this->get('renderer')->render($response, "urls/show.php", $params);
})->setName('urls.show');

$app->post('/urls', function (Request $request, Response $response) {
    $urlName = $request->getParsedBodyParam('url')['name'];
    $normalizedUrlName = normalizeUrl($urlName);

    $validator = new \Valitron\Validator(['name' => $normalizedUrlName]);
    $validator->rule('required', 'name')
        ->rule('lengthMax', 'name', 255)
        ->rule('url', 'name');
    $errors =  $validator->validate() ? [] : ['Некорректный URL'];
    if (empty($normalizedUrlName)) {
        $errors = ['URL не должен быть пустым'];
    }

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

        $redirectRoute = $this->get('router')->urlFor('urls.show', ['id' => (string) $urlId]);
        return $response->withRedirect($redirectRoute, 302);
    }

    $params = [
        'errors' => $errors,
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
