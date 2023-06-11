<?php

use Psr\Container\ContainerInterface;
use App\{Connection, DBController};
use Slim\Views\PhpRenderer;

return function (ContainerInterface $container) {
    $container->set('renderer', function () {
        $phpView = new PhpRenderer(__DIR__ . '/../templates');
        $phpView->setLayout('layout.php');
        return $phpView;
    });
    $container->set('flash', function () {
        return new \Slim\Flash\Messages();
    });
    $container->set('db', function () {
        $pdo = Connection::get()->connect();
        return new DBController($pdo);
    });
};
