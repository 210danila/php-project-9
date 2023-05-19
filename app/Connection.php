<?php

namespace App;

use Illuminate\Support\Arr;

final class Connection
{
    private static ?Connection $conn = null;

    public function connect()
    {
        $databaseUrl = getenv('DATABASE_URL');
        if ($databaseUrl === false) {
            throw new \Exception("Error reading database configuration file.");
        }
        $parsedDatabaseUrl = parse_url($databaseUrl);
        if ($parsedDatabaseUrl === false) {
            throw new \Exception("Error parsing databaseUrl.");
        }

        $conStr = sprintf(
            "pgsql:host=%s;port=%d;dbname=%s;user=%s;password=%s",
            Arr::get($parsedDatabaseUrl, 'host'),
            Arr::get($parsedDatabaseUrl, 'port'),
            ltrim(Arr::get($parsedDatabaseUrl, 'path'), '/'),
            Arr::get($parsedDatabaseUrl, 'user'),
            Arr::get($parsedDatabaseUrl, 'pass')
        );

        $pdo = new \PDO($conStr);
        $pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);

        $migrationsFileContents = file_get_contents('../database.sql');
        if ($migrationsFileContents === false) {
            throw new \Exception('No such file database.sql');
        }
        $dbMigrations = explode("\n\n", $migrationsFileContents);
        foreach ($dbMigrations as $sql) {
            $pdo->exec($sql);
        }

        return $pdo;
    }

    public static function get()
    {
        if (null === static::$conn) {
            static::$conn = new self();
        }

        return static::$conn;
    }
}
