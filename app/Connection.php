<?php

namespace App;

use Illuminate\Support\Arr;

final class Connection
{
    public function connect()
    {
        $databaseUrl = getenv('DATABASE_URL');
        if ($databaseUrl === false) {
            throw new \Exception("Error reading DATABASE_URL: no such env variable.");
        }
        $parsedDatabaseUrl = parse_url($databaseUrl);
        if ($parsedDatabaseUrl === false) {
            throw new \Exception("Error parsing DATABASE_URL.");
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
        return $pdo;
    }
}
