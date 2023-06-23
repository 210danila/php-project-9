<?php

use App\{Connection, DBController};
use Slim\Views\PhpRenderer;
use DI\Container;

return function (Container $container, Slim\App $app) {
    $container->set('router', $app->getRouteCollector()->getRouteParser());
    $container->set('flash', function () {
        return new \Slim\Flash\Messages();
    });
    $container->set('renderer', function ($container) {
        $phpView = new PhpRenderer(__DIR__ . '/../templates');
        $phpView->addAttribute('router', $container->get('router'));
        $phpView->addAttribute('flash', $container->get('flash')->getMessages());
        $phpView->setLayout('layout.php');
        return $phpView;
    });
    $container->set('db', function () {
        $conn = new Connection();
        $pdo = $conn->connect();
        return new DBController($pdo);
    });
};
