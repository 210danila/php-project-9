<?php

$autoloadPath1 = __DIR__ .  '/../../autoload.php';
$autoloadPath2 = __DIR__ . '/../vendor/autoload.php';

if (file_exists($autoloadPath1)) {
    require_once $autoloadPath1;
} else {
    require_once $autoloadPath2;
}

use App\Connection;

$conn = new Connection();
$pdo = $conn->connect();

$migrationsFileContents = file_get_contents(__DIR__ . '/../database.sql');
if ($migrationsFileContents === false) {
    throw new \Exception('No such file database.sql');
}
$dbMigrations = explode("\n\n", $migrationsFileContents);
foreach ($dbMigrations as $sql) {
    $pdo->exec($sql);
}

echo "Миграции применены успешно\n";
