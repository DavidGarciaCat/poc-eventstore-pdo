<?php

declare(strict_types=1);

namespace POC\EventStore;

use Buzz\Client\Curl as HttpClient;
use Nyholm\Psr7\Factory\Psr17Factory;
use Nyholm\Psr7\Request;
use Psr\Http\Client\ClientExceptionInterface;
use Psr\Http\Message\ResponseInterface;
use Ramsey\Uuid\Uuid;

class EventStoreClient
{
    /**
     * On real projects, you will inject these constants as environment variables.
     */
    private const PROTOCOL = 'http';
    private const HOST = 'eventstore';
    private const PORT = '2113';
    private const USER = 'admin';
    private const PASS = 'changeit';

    private HttpClient $httpClient;
    private string $baseUrl;
    private array $baseHeaders;

    /**
     * EventStoreClient constructor.
     */
    public function __construct()
    {
        $this->httpClient = new HttpClient(new Psr17Factory());

        $this->baseUrl = sprintf('%s://%s:%s', self::PROTOCOL, self::HOST, self::PORT);

        $this->baseHeaders = [
            'Authorization' => sprintf('Basic %s', base64_encode(sprintf('%s:%s', self::USER, self::PASS))),
            'Accept' => 'application/vnd.eventstore.atom+json',
            'Content-Type' => 'application/json',
        ];
    }

    /**
     * Creates a new Stream and fills it with the given data.
     *
     * @param string $streamName The name (our ID) for the stream
     * @param string $eventName  The name of the event that we run
     *                           so we can re-build the data as expected by running conditionals and calling the right methods
     * @param array  $payload    An array with the data that we want to store as key-value pairs
     *
     * @throws ClientExceptionInterface An HTTP Client exception on failure
     *
     * @return ResponseInterface An HTTP Response object
     */
    public function create(string $streamName, string $eventName, array $payload): ResponseInterface
    {
        $endpoint = sprintf('%s/streams/%s', $this->baseUrl, $streamName);
        $headers = array_merge(
            $this->baseHeaders,
            [
                'ES-EventType' => $eventName,
                'ES-EventId' => Uuid::uuid4()->toString(),
            ]
        );

        $request = new Request('POST', $endpoint, $headers, json_encode($payload));

        return $this->httpClient->sendRequest($request);
    }

    /**
     * Retrieves the Stream with the basic metadata.
     *
     * @param string $streamName The name (our ID) for the stream
     *
     * @throws ClientExceptionInterface An HTTP Client exception on failure
     *
     * @return ResponseInterface An HTTP Response object
     */
    public function readStream(string $streamName): ResponseInterface
    {
        $endpoint = sprintf('%s/streams/%s', $this->baseUrl, $streamName);
        $headers = $this->baseHeaders;

        $request = new Request('GET', $endpoint, $headers);

        return $this->httpClient->sendRequest($request);
    }

    /**
     * Retrieves the requested event for the given Stream.
     *
     * @param string $streamName    The name (our ID) for the stream
     * @param int    $streamEventId The event of the stream that we want to retrieve.
     *                              It can be easily mapped with the array keys, so just run a loop to get all of them.
     *
     * @throws ClientExceptionInterface An HTTP Client exception on failure
     *
     * @return ResponseInterface An HTTP Response object
     */
    public function readStreamEvent(string $streamName, int $streamEventId): ResponseInterface
    {
        $endpoint = sprintf('%s/streams/%s/%d', $this->baseUrl, $streamName, $streamEventId);
        $headers = $this->baseHeaders;

        $request = new Request('GET', $endpoint, $headers);

        return $this->httpClient->sendRequest($request);
    }

    /**
     * Deletes the Stream and all the stored data for it.
     *
     * @param string $streamName The name (our ID) for the stream
     *
     * @throws ClientExceptionInterface An HTTP Client exception on failure
     *
     * @return ResponseInterface An HTTP Response object
     */
    public function delete(string $streamName): ResponseInterface
    {
        $endpoint = sprintf('%s/streams/%s', $this->baseUrl, $streamName);
        $headers = $this->baseHeaders;

        $request = new Request('DELETE', $endpoint, $headers);

        return $this->httpClient->sendRequest($request);
    }
}
