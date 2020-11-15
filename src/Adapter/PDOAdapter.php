<?php

namespace Sgiberne\UnitOfWork\Adapter;

final class PDOAdapter
{
    protected ?\PDO $connection = null;
    protected ?\PDOStatement $statement = null;
    protected int $fetchMode = \PDO::FETCH_ASSOC;
    private string $databaseDsn;
    private string $databaseUser;
    private string $databasePassword;

    public function __construct(string $databaseDsn, string $databaseUser, string $databasePassword)
    {
        $this->databaseDsn = $databaseDsn;
        $this->databaseUser = $databaseUser;
        $this->databasePassword = $databasePassword;
    }

    public function getStatement(): \PDOStatement
    {
        if ($this->statement === null) {
            throw new \PDOException(
                "There is no PDOStatement object for use.");
        }
        return $this->statement;
    }

    public function connect(): void
    {
        if ($this->connection instanceof \PDO) {
            return;
        }

        try {
            $this->connection = new \PDO($this->databaseDsn, $this->databaseUser, $this->databasePassword);
            $this->connection->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
        } catch (\PDOException $e) {
            throw new \RunTimeException($e->getMessage());
        }
    }

    public function disconnect(): void
    {
        $this->connection = null;
    }

    public function prepare(string $sql, array $options = []): self
    {
        $this->connect();

        try {
            $this->statement = $this->connection->prepare($sql, $options);

            return $this;
        } catch (\PDOException $e) {
            throw new \RunTimeException($e->getMessage());
        }
    }

    public function execute(array $parameters = []) : self
    {
        try {
            $this->getStatement()->execute($parameters);

            return $this;
        } catch (\PDOException $e) {
            throw new \RunTimeException($e->getMessage());
        }
    }

    public function countAffectedRows(): int
    {
        try {
            return $this->getStatement()->rowCount();
        } catch (\PDOException $e) {
            throw new \RunTimeException($e->getMessage());
        }
    }

    public function fetch(int $fetchStyle = null, int $cursorOrientation = null, int $cursorOffset = null)
    {
        if ($fetchStyle === null) {
            $fetchStyle = $this->fetchMode;
        }

        try {
            return $this->getStatement()->fetch($fetchStyle, $cursorOrientation, $cursorOffset);
        } catch (\PDOException $e) {
            throw new \RunTimeException($e->getMessage());
        }
    }

    public function fetchAll(int $fetchStyle = null, int $column = 0): array
    {
        if ($fetchStyle === null) {
            $fetchStyle = $this->fetchMode;
        }

        try {
            return $fetchStyle === \PDO::FETCH_COLUMN ? $this->getStatement()->fetchAll($fetchStyle, $column) : $this->getStatement()->fetchAll($fetchStyle);
        } catch (\PDOException $e) {
            throw new \RunTimeException($e->getMessage());
        }
    }

    public function select(string $table, array $bind = [], string $where = "", array $options = [], array $orderBy = []): self
    {
        $bindParameters = [];
        foreach ($bind as $col => $value) {
            $bindParameters[":$col"] = $value;
        }

        $fields = $options['fields'] ?? '*';
        $sql = "SELECT $fields FROM $table";

        if (strlen($where) > 2) {
            $sql .= " WHERE $where";
        }

        $sql .= $this->getOrderByCondition($orderBy);

        $this->prepare($sql)
            ->execute($bindParameters);

        return $this;
    }

    private function getOrderByCondition(array $orderBy): string
    {
        if (empty($orderBy)) {
            return '';
        }

        $orderByCondition = ' ORDER BY ';
        $iteration = 0;

        foreach ($orderBy as $field => $order) {
            if ($iteration > 0 && $iteration < count($orderBy)) {
                $orderByCondition .= ', ';
            }

            $orderByCondition .= "$field $order";
            $iteration++;
        }

        return $orderByCondition;
    }

    public function insert(string $table, array $bind): int
    {
        $cols = implode(', ', array_keys($bind));
        $values = implode(', :', array_keys($bind));
        foreach ($bind as $col => $value) {
            unset($bind[$col]);
            $bind[":$col"] = $value;
        }

        $sql = "INSERT IGNORE INTO $table ($cols) VALUES (:$values)";

        return $this->prepare($sql)
            ->execute($bind)
            ->countAffectedRows();
    }

    public function update(string $table, array $bind, string $where = ""): int
    {
        $set = [];
        foreach ($bind as $col => $value) {
            unset($bind[$col]);
            $bind[":$col"] = $value;
            $set[] = "$col = :$col";
        }

        $sql = "UPDATE $table SET " . implode(", ", $set). (($where) ? " WHERE " . $where : " ");

        return $this->prepare($sql)
            ->execute($bind)
            ->countAffectedRows();
    }

    public function delete($table, $where = ""): int
    {
        $sql = "DELETE FROM $table" . (($where) ? " WHERE $where" : " ");

        return $this->prepare($sql)
            ->execute()
            ->countAffectedRows();
    }

    public function getLastInsertId($name = null): string
    {
        $this->connect();

        return $this->connection->lastInsertId($name);
    }
}
