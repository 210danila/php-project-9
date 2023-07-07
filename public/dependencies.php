<?php

use App\Connection;
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
        $phpView->setLayout('layout.phtml');
        return $phpView;
    });
    $container->set('pdo', function () {
        $conn = new Connection();
        return $conn->connect();
    });
};
