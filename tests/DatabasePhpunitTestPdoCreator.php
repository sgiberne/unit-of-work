<?php

namespace Sgiberne\UnitOfWork\Tests;

class DatabasePhpunitTestPdoCreator
{
    public const DATABASE_NAME = 'phpunit_test';
    public const DATABASE_USER = 'root';
    public const DATABASE_PASSWORD = 'password';
    public const DATABASE_HOST = 'mysql';
    public const DATABASE_PORT = '3306';

    private ?\PDO $connection = null;

    private function getDsn(string $databaseName = null): string
    {
        return sprintf("mysql:dbname=%s;host=%s;port=%s", $databaseName, self::DATABASE_HOST, self::DATABASE_PORT);
    }

    public function createPhpunitTestDatabase(): bool
    {
        try {
            $this->getConnection()->exec(sprintf("CREATE DATABASE `%s`;", self::DATABASE_NAME));
            $this->disconnect();

            return true;
        } catch(\PDOException $e) {
            throw new \RuntimeException("Cannot create database ".self::DATABASE_NAME.". Error: ".$e->getMessage());
        }
    }

    public function createActorTable(): bool
    {
        try {
            $sql ="CREATE TABLE IF NOT EXISTS actor(
             id INT( 11 ) AUTO_INCREMENT PRIMARY KEY,
             firstname VARCHAR( 50 ) NOT NULL, 
             lastname VARCHAR( 50 ) NOT NULL,
             url_avatar VARCHAR( 550 ) NOT NULL);";

            $this->getConnection(self::DATABASE_NAME)->exec($sql);
            $this->disconnect();

            return true;
        } catch(\PDOException $e) {
            throw new \RuntimeException("Cannot create table actor. Error: ".$e->getMessage());
        }
    }

    public function dropDatabase(): bool
    {
        try {
            $this->getConnection()->exec("DROP DATABASE " . self::DATABASE_NAME);
            $this->disconnect();

            return true;
        } catch(\PDOException $e) {
            return false;
        }
    }

    private function getConnection(string $databaseName = null): \PDO
    {
        if ($this->connection !== null) {
            return $this->connection;
        }

        try {
            $this->connection = new \PDO($this->getDsn($databaseName), self::DATABASE_USER, self::DATABASE_PASSWORD);
            $this->connection->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);

            return $this->connection;
        } catch (\PDOException $e) {
            throw new \RuntimeException(sprintf('Cannot connect to %s with user "%s" and password "%s". Error: %s', $this->getDsn(), self::DATABASE_USER, self::DATABASE_PASSWORD, $e->getMessage()));
        }
    }

    public function disconnect(): void
    {
        $this->connection = null;
    }
}
