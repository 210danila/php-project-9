<?php

namespace App;

use Carbon\Carbon;

class DBController
{
    /**
     * объект PDO
     * @var \PDO
     */
    private $pdo;

    /**
     * инициализация объекта с объектом \PDO
     * @тип параметра $pdo
     */
    public function __construct($pdo)
    {
        $this->pdo = $pdo;
    }

    public function createTables()
    {
        $dbMigrations = explode("\n\n", file_get_contents('../app/database.sql'));

        foreach ($dbMigrations as $sql) {
            $this->pdo->exec($sql);
        }
        return $this;
    }

    public function insertUrl(string $urlName)
    {
        $urls = $this->selectUrls();
        if (collect($urls)->contains('name', $urlName)) {
            throw new \Exception("URL with such name already exists.");
        }

        $sql = 'INSERT INTO urls(name, created_at) VALUES(:name, :time)';

        $stmt = $this->pdo->prepare($sql);
        $stmt->bindValue(':name', $urlName);
        $stmt->bindValue(':time', Carbon::now());
        $stmt->execute();

        return $this->pdo->lastInsertId('urls_id_seq');
    }

    public function selectUrls()
    {
        $sql = 'SELECT * FROM urls';
        $stmt = $this->pdo->query($sql);
        return $stmt->fetchAll();
    }

    public function selectUrl($columnName, $value)
    {
        $sql = "SELECT * FROM urls WHERE {$columnName}=:value";
        $stmt = $this->pdo->prepare($sql);
        $stmt->bindValue(':value', $value);
        $stmt->execute();
        return $stmt->fetch();
    }
}
