<?php

declare(strict_types=1);

namespace POC\PDO;

use PDO;

class PDOClient
{
    /**
     * On real projects, you will inject these constants as environment variables.
     */
    private const PROTOCOL = 'mysql';
    private const HOST = 'mariadb';
    private const PORT = '3306';
    private const USER = 'poc';
    private const PASS = 'poc';
    private const DATABASE = 'poc';

    private PDO $pdo;

    public function __construct()
    {
        $dsn = sprintf('%s:host=%s;port=%d;dbname=%s;charset=utf8mb4', self::PROTOCOL, self::HOST, self::PORT, self::DATABASE);

        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ];

        $this->pdo = new PDO($dsn, self::USER, self::PASS, $options);
    }

    /**
     * Returns the instantiated PDO for manual operations.
     *
     * @return PDO The instantiated PDO
     */
    public function getPDO(): PDO
    {
        return $this->pdo;
    }

    /**
     * Creates a new row into the database.
     *
     * @param array $data Binding of data for the INSERT query
     */
    public function create(array $data): void
    {
        $data['uuid'] = $data['uuid'] ?? null;
        $data['dateTime'] = $data['dateTime'] ?? null;
        $data['testName'] = $data['testName'] ?? null;

        $query = "INSERT INTO `test_table` (`uuid`, `dateTime`, `testName`) VALUES (:uuid, :dateTime, :testName)";

        $statement = $this->pdo->prepare($query);
        $statement->bindParam('uuid', $data['uuid'], PDO::PARAM_STR);
        $statement->bindParam('dateTime', $data['dateTime'], PDO::PARAM_STR);
        $statement->bindParam('testName', $data['testName'], PDO::PARAM_STR);
        $statement->execute($data);
    }

    /**
     * Retrieves the record that matches the given ID.
     *
     * @param string $uuid The row's UUID
     *
     * @return array The matching data
     */
    public function read(string $uuid): array
    {
        $query = "SELECT `uuid`, `dateTime`, `testName` FROM `test_table` WHERE `uuid` = :uuid";

        $statement = $this->pdo->prepare($query);
        $statement->bindParam('uuid', $uuid, PDO::PARAM_STR);
        $statement->execute(['uuid' => $uuid]);

        return $statement->fetchAll();
    }

    /**
     * Updates the existing row with the new content.
     *
     * @param array $data Binding of data for the UPDATE query
     */
    public function update(array $data): void
    {
        $clauses = [];

        foreach ($data as $property => $value) {
            if ('uuid' !== $property) {
                $clauses[] = sprintf('`%s` = :%s', $property, $property);
            }
        }

        $setString = implode(', ', $clauses);

        $query = sprintf("UPDATE `test_table` SET %s WHERE `uuid` = :uuid", $setString);

        $statement = $this->pdo->prepare($query);

        foreach ($data as $property => $value) {
            $statement->bindParam($property, $data[$property], PDO::PARAM_STR);
        }

        $statement->execute($data);
    }

    /**
     * Deletes the existing row that matches the given ID.
     *
     * @param string $uuid The row's UUID
     */
    public function delete(string $uuid): void
    {
        $query = "DELETE FROM `test_table` WHERE `uuid` = :uuid";

        $statement = $this->pdo->prepare($query);
        $statement->bindParam('uuid', $uuid, PDO::PARAM_STR);
        $statement->execute(['uuid' => $uuid]);
    }
}
