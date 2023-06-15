<?php

namespace App;

use Carbon\Carbon;

class DBController
{
    public const AVAILABLE_QUERIES = ['insert', 'select'];

    private \PDO $pdo;
    private array $queryData;

    public function __construct(\PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function setQueryData(array $data)
    {
        $this->queryData = $data;
    }

    public function setQueryParam(string $name, string|array $value)
    {
        $this->queryData[$name] = $value;
    }

    public function setQueryClause(string $name, array $clause)
    {
        $clauses = $this->queryData['clauses'];
        $this->queryData['clauses'] = array_merge($clauses, [$name => $clause]);
    }

    public function getQueryParam(string $paramName)
    {
        return $this->queryData[$paramName];
    }

    public function getQueryClause(string $clauseName)
    {
        $clauses = $this->queryData['clauses'];
        if (!array_key_exists($clauseName, $clauses)) {
            return false;
        }
        return $clauses[$clauseName];
    }

    public function makeQuery(string $queryType, string $tableName)
    {
        if (array_search($queryType, self::AVAILABLE_QUERIES) === false) {
            throw new \Exception("No such handler for {$queryType}.");
        }
        $this->setQueryData([
            'type' => $queryType,
            'sql' => $queryType === 'select' ? "SELECT * FROM {$tableName}" : "INSERT INTO {$tableName}",
            'clauses' => [],
            'insertData' => []
        ]);
        return $this;
    }

    public function values(array $data)
    {
        $colomns = collect($data)
            ->keys()
            ->implode(', ');
        $valuesNames = collect($data)
            ->keys()
            ->map(fn($column) => ":{$column}")
            ->implode(', ');

        $sql = $this->getQueryParam('sql') .
            <<<INSERTVALUES
            ({$colomns}, created_at) 
            VALUES({$valuesNames}, :time)
            INSERTVALUES;
        $this->setQueryParam('sql', $sql);
        $this->setQueryParam('insertData', $data);
        return $this;
    }

    public function where(string $column, string $value)
    {
        $sql = $this->getQueryParam('sql');
        $newSql = $sql . " WHERE {$column}=:value";
        $this->setQueryParam('sql', $newSql);
        $this->setQueryClause('where', ['column' => $column, 'value' => $value]);
        return $this;
    }

    public function orderBy(string $columns, string $order)
    {
        $sql = $this->getQueryParam('sql');
        $newSql = $sql . " ORDER BY {$columns} {$order}";
        $this->setQueryParam('sql', $newSql);
        return $this;
    }

    public function distinct(string $column)
    {
        $sql = $this->getQueryParam('sql');
        $sqlFromTable = explode(' * ', $sql)[1];
        $newSql = "SELECT" . " DISTINCT ON ({$column}) * " . $sqlFromTable;
        $this->setQueryParam('sql', $newSql);
        return $this;
    }

    public function exec(bool $onlyFirstValue = false)
    {
        $queryType = $this->getQueryParam('type');
        $stmt = $this->pdo->prepare($this->getQueryParam('sql'));

        $where = $this->getQueryClause('where');
        if ($where) {
            $stmt->bindValue(':value', $where['value']);
        }

        if ($queryType === 'insert') {
            $insertData = $this->getQueryParam('insertData');
            foreach ($insertData as $column => $value) {
                $stmt->bindValue(":{$column}", $value);
            }
            $stmt->bindValue(':time', Carbon::now());
        }

        $stmt->execute();

        if ($queryType === 'insert') {
            return $this->pdo->lastInsertId();
        } elseif ($onlyFirstValue) {
            return $stmt->fetch();
        } else {
            return $stmt->fetchAll();
        }
    }
}
