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

use function App\Functions\normalizeUrl;

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
    $sql1 = "SELECT * FROM urls ORDER BY id DESC;";
    $stmt1 = $this->get('pdo')->prepare($sql1);
    $stmt1->execute();
    $urls = $stmt1->fetchAll();

    $sql2 = "SELECT DISTINCT ON (url_id) * FROM url_checks ORDER BY url_id, created_at DESC;";
    $stmt2 = $this->get('pdo')->prepare($sql2);
    $stmt2->execute();
    $urlChecks = $stmt2->fetchAll();
    $urlChecksByUrlId = collect($urlChecks)->keyBy('url_id')->toArray();

    $params = [
        'urls' => $urls,
        'urlChecks' => $urlChecksByUrlId,
        'activeLink' => 'Сайты'
    ];
    return $this->get('renderer')->render($response, "urls/index.phtml", $params);
})->setName('urls.index');

$app->get('/urls/{id:\d+}', function (Request $request, Response $response, array $args) {
    $urlId = (int) $args['id'];

    $sql1 = "SELECT * FROM urls WHERE id=:url_id";
    $stmt1 = $this->get('pdo')->prepare($sql1);
    $stmt1->bindValue(':url_id', $urlId);
    $stmt1->execute();
    $url = $stmt1->fetch();

    $sql2 = "SELECT * FROM url_checks WHERE url_id=:url_id ORDER BY id DESC";
    $stmt2 = $this->get('pdo')->prepare($sql2);
    $stmt2->bindValue(':url_id', $urlId);
    $stmt2->execute();
    $urlChecks = $stmt2->fetchAll();

    $params = [
        'url' => $url,
        'urlChecks' => $urlChecks,
        'activeLink' => ''
    ];
    return $this->get('renderer')->render($response, "urls/show.phtml", $params);
})->setName('urls.show');

$app->post('/urls', function (Request $request, Response $response) {
    $urlName = $request->getParsedBodyParam('url')['name'];

    $validator = new \Valitron\Validator(['name' => $urlName]);
    $validator->rule('required', 'name')->message('URL не должен быть пустым');
    $validator->rule('lengthMax', 'name', 255)->message('Некорректный URL');
    $validator->rule('url', 'name')->message('Некорректный URL');

    $normalizedUrlName = normalizeUrl($urlName);

    if (!$validator->validate()) {
        $errors = $validator->errors();
        $params = [
            'errors' => $errors,
            'urlName' => $urlName,
            'activeLink' => 'Сайты'
        ];
        return $this
            ->get('renderer')
            ->render($response, "index.phtml", $params)
            ->withStatus(422);
    }

    $sql1 = "SELECT * FROM urls WHERE name=:name";
    $stmt1 = $this->get('pdo')->prepare($sql1);
    $stmt1->bindValue(':name', $normalizedUrlName);
    $stmt1->execute();
    $sameUrl = $stmt1->fetch();

    if (!empty($sameUrl)) {
        $this->get('flash')->addMessage('success', 'Страница уже существует');
        $urlId = $sameUrl['id'];
    } else {
        $sql2 = "INSERT INTO urls (name, created_at) VALUES (:name, :created_at)";
        $stmt2 = $this->get('pdo')->prepare($sql2);
        $stmt2->bindValue(':name', $normalizedUrlName);
        $stmt2->bindValue(':created_at', Carbon::now());
        $stmt2->execute();
        $urlId = $this->get('pdo')->lastInsertId();
        $this->get('flash')->addMessage('success', 'Страница успешно добавлена');
    }

    $redirectRoute = $this->get('router')->urlFor('urls.show', ['id' => (string) $urlId]);
    return $response->withRedirect($redirectRoute, 302);
})->setName('urls.store');

$app->post('/urls/{id:\d+}/checks', function (Request $request, Response $response, array $args) {
    $urlId = (int) $args['id'];
    $sql1 = "SELECT * FROM urls WHERE id=:url_id";
    $stmt1 = $this->get('pdo')->prepare($sql1);
    $stmt1->bindValue(':url_id', $urlId);
    $stmt1->execute();
    $url = $stmt1->fetch();

    $client = new Client();
    try {
        $urlResponse = $client->request('GET', $url['name']);
    } catch (ClientException $e) {
        $urlResponse = $e->getResponse();
    } catch (TransferException $e) {
        $flashMessage = 'Произошла ошибка при проверке,
        не удалось подключиться';
        $this->get('flash')->addMessage('error', $flashMessage);
        $redirectRoute = $this->get('router')->urlFor('urls.show', ['id' => (string) $urlId]);
        return $response->withRedirect($redirectRoute, 302);
    }
    $statusCode = $urlResponse->getStatusCode();
    $urlId = $url['id'];

    $body = (string) $urlResponse->getBody();
    $document = new Document($body);
    $h1 = $document->first('h1::text()');
    $title = $document->first('title::text()');
    $description = $document->first('meta[name=description][content]::attr(content)');

    $values = [
        ':url_id' => $urlId,
        ':status_code' => $statusCode,
        ':h1' => optional($h1, 'trim'),
        ':title' => optional($title, 'trim'),
        ':description' => optional($description, 'trim'),
        ':created_at' => Carbon::now()
    ];

    $sql2 = "INSERT INTO url_checks
            (url_id, status_code, h1, title, description, created_at)
            VALUES (:url_id, :status_code, :h1, :title, :description, :created_at)";
    $stmt2 = $this->get('pdo')->prepare($sql2);
    foreach ($values as $name => $value) {
        $stmt2->bindValue($name, $value);
    }
    $stmt2->execute();

    $flashMessage = 'Страница успешно проверена';
    $this->get('flash')->addMessage('success', $flashMessage);

    $redirectRoute = $this->get('router')->urlFor('urls.show', ['id' => (string) $urlId]);
    return $response->withRedirect($redirectRoute, 302);
})->setName('urls.checks.store');

$app->run();
