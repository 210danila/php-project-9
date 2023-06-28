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
use Carbon\Carbon;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;
use DiDom\Document;

use function App\Functions\{normalizeUrl};

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
    return $this->get('renderer')->render($response, "index.phtml", $params);
})->setName('root');

$app->get('/urls', function (Request $request, Response $response) {
    $sql = "SELECT * FROM urls ORDER BY id DESC;";
    $stmt = $this->get('pdo')->prepare($sql);
    $stmt->execute();
    $urls = $stmt->fetchAll();
    $sql = "SELECT DISTINCT ON (url_id) * FROM url_checks ORDER BY url_id, created_at DESC;";
    $stmt = $this->get('pdo')->prepare($sql);
    $stmt->execute();
    $urlChecks = $stmt->fetchAll();
    $urlChecks = collect($urlChecks)->keyBy('url_id')->toArray();

    $params = [
        'urls' => $urls,
        'urlChecks' => $urlChecks,
        'activeLink' => 'Сайты'
    ];
    return $this->get('renderer')->render($response, "urls/index.phtml", $params);
})->setName('urls.index');

$app->get('/urls/{id:\d+}', function (Request $request, Response $response, array $args) {
    $urlId = (int) $args['id'];

    $sql = "SELECT * FROM urls WHERE id=:url_id";
    $stmt = $this->get('pdo')->prepare($sql);
    $stmt->bindValue(':url_id', $urlId);
    $stmt->execute();
    $url = $stmt->fetch();
    $sql = "SELECT * FROM url_checks WHERE url_id=:url_id ORDER BY id DESC";
    $stmt = $this->get('pdo')->prepare($sql);
    $stmt->bindValue(':url_id', $urlId);
    $stmt->execute();
    $urlChecks = $stmt->fetchAll();

    $params = [
        'url' => $url,
        'urlChecks' => $urlChecks,
        'activeLink' => ''
    ];
    return $this->get('renderer')->render($response, "urls/show.phtml", $params);
})->setName('urls.show');

$app->post('/urls', function (Request $request, Response $response) {
    $urlName = $request->getParsedBodyParam('url')['name'];
    $normalizedUrlName = normalizeUrl($urlName);

    $validator = new \Valitron\Validator(['name' => $normalizedUrlName]);
    $validator->rule('required', 'name')->message(INCORRECT_URL);
    $validator->rule('lengthMax', 'name', 255)->message(INCORRECT_URL);
    $validator->rule('url', 'name')->message(INCORRECT_URL);

    if ($validator->validate()) {
        $sql = "SELECT * FROM urls WHERE name=:name";
        $stmt = $this->get('pdo')->prepare($sql);
        $stmt->bindValue(':name', $normalizedUrlName);
        $stmt->execute();
        $sameUrl = $stmt->fetch();

        if (!empty($sameUrl)) {
            $this->get('flash')->addMessage('success', 'Страница уже существует');
            $urlId = $sameUrl['id'];
        } else {
            $sql = "INSERT INTO urls (name, created_at) VALUES (:name, :created_at)";
            $stmt = $this->get('pdo')->prepare($sql);
            $stmt->bindValue(':name', $normalizedUrlName);
            $stmt->bindValue(':created_at', Carbon::now());
            $stmt->execute();
            $urlId = $this->get('pdo')->lastInsertId();
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
        ->render($response, "index.phtml", $params)
        ->withStatus(422);
})->setName('urls.store');

$app->post('/urls/{id:\d+}/checks', function (Request $request, Response $response, array $args) {
    $urlId = (int) $args['id'];
    $sql = "SELECT * FROM urls WHERE id=:url_id";
    $stmt = $this->get('pdo')->prepare($sql);
    $stmt->bindValue(':url_id', $urlId);
    $stmt->execute();
    $url = $stmt->fetch();

    try {
        $client = new Client();
        try {
            $urlResponse = $client->request('GET', $url['name']);
        } catch (ClientException $e) {
            $urlResponse = $e->getResponse();
        }
        $statusCode = $urlResponse->getStatusCode();
        $urlId = $url['id'];

        $body = (string) $urlResponse->getBody();
        $document = new Document($body);
        $h1 = $document->first('h1::text()');
        $title = $document->first('title::text()');
        $description = $document->first('meta[name=description][content]::attr(content)');

        $formatContent = fn ($text) => trim($text);
        $values = [
            ':url_id' => $urlId,
            ':status_code' => $statusCode,
            ':h1' => optional($h1, $formatContent),
            ':title' => optional($title, $formatContent),
            ':description' => optional($description, $formatContent),
            ':created_at' => Carbon::now()
        ];

        $sql = "INSERT INTO url_checks
                (url_id, status_code, h1, title, description, created_at)
                VALUES (:url_id, :status_code, :h1, :title, :description, :created_at)";
        $stmt = $this->get('pdo')->prepare($sql);
        foreach ($values as $name => $value) {
            $stmt->bindValue($name, $value);
        }
        $stmt->execute();
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
