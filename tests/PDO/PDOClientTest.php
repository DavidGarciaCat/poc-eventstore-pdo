<?php

declare(strict_types=1);

namespace Tests\POC\PDO;

use DateTime;
use PHPUnit\Framework\TestCase;
use POC\PDO\PDOClient;
use Ramsey\Uuid\Uuid;

class PDOClientTest extends TestCase
{
    private PDOClient $sut;

    protected function setUp(): void
    {
        $this->sut = new PDOClient();

        $statement1 = 'DROP TABLE IF EXISTS test_table;';
        $this->sut->getPDO()->query($statement1);

        $statement2 = 'CREATE TABLE test_table ( uuid VARCHAR(50) PRIMARY KEY, dateTime VARCHAR(50), testName VARCHAR(200) );';
        $this->sut->getPDO()->query($statement2);
    }

    protected function tearDown(): void
    {
        $statement = 'DROP TABLE test_table;';
        $this->sut->getPDO()->query($statement);
    }

    public function testCRUD()
    {
        // Prepare data

        $uuid = Uuid::uuid4()->toString();
        $dateTime = (new DateTime())->format('c');
        $testName = __METHOD__;
        $paramsInsert = ['uuid' => $uuid, 'dateTime' => $dateTime, 'testName' => null];
        $paramsUpdate = ['uuid' => $uuid, 'dateTime' => null, 'testName' => $testName];

        // Create new data

        $this->sut->create($paramsInsert);

        // Get new data

        $dataBeforeUpdate = $this->sut->read($uuid);

        self::assertCount(1, $dataBeforeUpdate);
        self::assertEquals($uuid, $dataBeforeUpdate[0]['uuid']);
        self::assertEquals($dateTime, $dataBeforeUpdate[0]['dateTime']);
        self::assertEquals(null, $dataBeforeUpdate[0]['testName']);

        // Update existing data

        $this->sut->update($paramsUpdate);

        // Get updated data

        $dataAfterUpdate = $this->sut->read($uuid);

        self::assertCount(1, $dataAfterUpdate);
        self::assertEquals($uuid, $dataAfterUpdate[0]['uuid']);
        self::assertEquals(null, $dataAfterUpdate[0]['dateTime']);
        self::assertEquals($testName, $dataAfterUpdate[0]['testName']);

        // Delete existing data

        $this->sut->delete($uuid);

        $deletedData = $this->sut->read($uuid);

        self::assertCount(0, $deletedData);
    }
}
