<?php

declare(strict_types=1);

namespace Tests\POC\EventStore;

use DateTime;
use PHPUnit\Framework\TestCase;
use POC\EventStore\EventStoreClient;
use Ramsey\Uuid\Uuid;

class EventStoreClientTest extends TestCase
{
    private EventStoreClient $sut;

    protected function setUp(): void
    {
        $this->sut = new EventStoreClient();
    }

    public function testCRUD()
    {
        // Prepare data

        $streamName = sprintf('sut-%s', Uuid::uuid4());
        $eventName = 'pocEventName';
        $eventData1 = ['dateTime' => (new DateTime())->format('c')];
        $eventData2 = ['testName' => __METHOD__];

        // Create new stream with two events

        $createResponse1 = $this->sut->create($streamName, $eventName, $eventData1);
        $createResponse2 = $this->sut->create($streamName, $eventName, $eventData2);

        self::assertEquals(201, $createResponse1->getStatusCode());
        self::assertEquals(201, $createResponse2->getStatusCode());

        // Get the whole stream

        $readStreamResponse = $this->sut->readStream($streamName);
        $readStreamBody = json_decode((string) $readStreamResponse->getBody(), true);

        self::assertEquals(200, $readStreamResponse->getStatusCode());
        self::assertArrayHasKey('streamId', $readStreamBody);
        self::assertArrayHasKey('entries', $readStreamBody);
        self::assertCount(2, $readStreamBody['entries']);
        self::assertArrayHasKey(0, $readStreamBody['entries']);
        self::assertArrayHasKey(1, $readStreamBody['entries']);

        // Get the 1st event of the stream

        $readStreamEvent0Response = $this->sut->readStreamEvent($streamName, 0);
        $readStreamEvent0Body = json_decode((string) $readStreamEvent0Response->getBody(), true);

        self::assertEquals(200, $readStreamEvent0Response->getStatusCode());
        self::assertArrayHasKey('content', $readStreamEvent0Body);
        self::assertArrayHasKey('eventStreamId', $readStreamEvent0Body['content']);
        self::assertArrayHasKey('eventType', $readStreamEvent0Body['content']);
        self::assertArrayHasKey('data', $readStreamEvent0Body['content']);
        self::assertArrayHasKey('dateTime', $readStreamEvent0Body['content']['data']);

        // Get the 2nd event of the stream

        $readStreamEvent1Response = $this->sut->readStreamEvent($streamName, 1);
        $readStreamEvent1Body = json_decode((string) $readStreamEvent1Response->getBody(), true);

        self::assertEquals(200, $readStreamEvent1Response->getStatusCode());
        self::assertArrayHasKey('content', $readStreamEvent1Body);
        self::assertArrayHasKey('eventStreamId', $readStreamEvent1Body['content']);
        self::assertArrayHasKey('eventType', $readStreamEvent1Body['content']);
        self::assertArrayHasKey('data', $readStreamEvent1Body['content']);
        self::assertArrayHasKey('testName', $readStreamEvent1Body['content']['data']);

        // ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
        // IF YOU WANT TO KEEP THE RECORD FOR MANUAL CHECKS USING EVENT STORE UI,
        // THEN PLEASE COMMENT THE FOLLOWING LINES
        // ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

        // Delete the stream

        $deleteStreamResponse = $this->sut->delete($streamName);

        self::assertEquals(204, $deleteStreamResponse->getStatusCode());

        // Confirm that the stream no longer exists

        $reloadedReadStreamResponse = $this->sut->readStream($streamName);

        self::assertEquals(404, $reloadedReadStreamResponse->getStatusCode());
    }

    public function testConstructFromEvents()
    {
        // Prepare data

        $streamName = sprintf('sut-%s', Uuid::uuid4());
        $eventName = 'pocAddData';
        $eventData1 = ['dateTime' => (new DateTime())->format('c')];
        $eventData2 = ['testName' => __METHOD__];
        $eventData3 = ['dateTime' => '1970-01-01 00:00:00'];

        // Create

        $this->sut->create($streamName, $eventName, $eventData1);
        $this->sut->create($streamName, $eventName, $eventData2);
        $this->sut->create($streamName, $eventName, $eventData3);

        // Load data

        $readStreamResponse = $this->sut->readStream($streamName);
        $readStreamBody = json_decode((string) $readStreamResponse->getBody(), true);

        $constructFromEvents = [];

        foreach ($readStreamBody['entries'] as $entryKey => $entryBasicMetadata) {
            $readStreamEventResponse = $this->sut->readStreamEvent($streamName, $entryKey);
            $readStreamEventBody = json_decode((string) $readStreamEventResponse->getBody(), true);

            foreach ($readStreamEventBody['content']['data'] as $name => $value) {
                $constructFromEvents[$name] = $value;
            }
        }

        // ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
        // IF YOU WANT TO KEEP THE RECORD FOR MANUAL CHECKS USING EVENT STORE UI,
        // THEN PLEASE COMMENT JUST THE FOLLOWING LINE (THAT DELETES THE STREAM)
        // ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

        // Delete the stream

        //$this->sut->delete($streamName);

        // Check restored data

        self::assertCount(2, $constructFromEvents);

        self::assertArrayHasKey('dateTime', $constructFromEvents);
        self::assertArrayHasKey('testName', $constructFromEvents);

        self::assertEquals('1970-01-01 00:00:00', $constructFromEvents['dateTime']);
        self::assertEquals(__METHOD__, $constructFromEvents['testName']);
    }
}
