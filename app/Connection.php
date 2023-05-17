<?php

namespace App;

final class Connection
{
    /**
     * Connection
     * тип @var
     */
    private static ?Connection $conn = null;

    /**
     * Подключение к базе данных и возврат экземпляра объекта \PDO
     * @return \PDO
     * @throws \Exception
     */
    public function connect()
    {
        $databaseUrl = parse_url(getenv('DATABASE_URL'));
        if ($databaseUrl === false) {
            throw new \Exception("Error reading database configuration file");
        }

        $databaseName = ltrim($databaseUrl['path'], '/');
        // $databasePassword = getenv('PGPASSWORD');
        // $databaseUser = getenv('PGUSER');
        $conStr = sprintf(
            "pgsql:host=%s;port=%d;dbname={$databaseName};user=%s;password=%s",
            $databaseUrl['host'],
            $databaseUrl['port'],
            $databaseUrl['user'],
            $databaseUrl['password']
        );

        $pdo = new \PDO($conStr);
        $pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);

        $dbMigrations = explode("\n\n", file_get_contents('../database.sql'));
        foreach ($dbMigrations as $sql) {
            $pdo->exec($sql);
        }

        return $pdo;
    }

    /**
     * возврат экземпляра объекта Connection
     * тип @return
     */
    public static function get()
    {
        if (null === static::$conn) {
            static::$conn = new self();
        }

        return static::$conn;
    }
}
