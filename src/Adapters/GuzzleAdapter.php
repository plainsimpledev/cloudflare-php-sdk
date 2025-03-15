<?php

declare(strict_types=1);

namespace PlainSimple\Cloudflare\Adapters;

use Fig\Http\Message\RequestMethodInterface;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use PlainSimple\Cloudflare\Auth\AuthInterface;
use PlainSimple\Cloudflare\Exceptions\InvalidRequestMethodException;
use Psr\Http\Message\ResponseInterface;

class GuzzleAdapter implements AdapterInterface
{
    private const array ALLOWED_METHODS = [
        RequestMethodInterface::METHOD_GET,
        RequestMethodInterface::METHOD_POST,
        RequestMethodInterface::METHOD_PUT,
        RequestMethodInterface::METHOD_PATCH,
        RequestMethodInterface::METHOD_DELETE,
    ];

    private Client $client;

    public function __construct(
        AuthInterface $auth,
        string $baseUri = 'https://api.cloudflare.com/client/v4'
    ) {
        $headers = $auth->getHeaders();
        $headers['Content-Type'] = 'application/json';
        $headers['Accept'] = 'application/json';

        $this->client = new Client([
            'base_uri' => $baseUri,
            'headers' => $headers,
        ]);
    }

    /**
     * @throws GuzzleException
     * @throws InvalidRequestMethodException
     */
    public function get(string $url, array $data = [], array $headers = []): ResponseInterface
    {
        return $this->request(RequestMethodInterface::METHOD_GET, $url, $data, $headers);
    }

    /**
     * @throws InvalidRequestMethodException
     * @throws GuzzleException
     */
    public function post(string $url, array $data = [], array $headers = []): ResponseInterface
    {
        return $this->request(RequestMethodInterface::METHOD_POST, $url, $data, $headers);
    }

    /**
     * @throws InvalidRequestMethodException
     * @throws GuzzleException
     */
    public function put(string $url, array $data = [], array $headers = []): ResponseInterface
    {
        return $this->request(RequestMethodInterface::METHOD_PUT, $url, $data, $headers);
    }

    /**
     * @throws InvalidRequestMethodException
     * @throws GuzzleException
     */
    public function patch(string $url, array $data = [], array $headers = []): ResponseInterface
    {
        return $this->request(RequestMethodInterface::METHOD_PATCH, $url, $data, $headers);
    }

    /**
     * @throws GuzzleException
     * @throws InvalidRequestMethodException
     */
    public function delete(string $url, array $data = [], array $headers = []): ResponseInterface
    {
        return $this->request(RequestMethodInterface::METHOD_DELETE, $url, $data, $headers);
    }

    /**
     * @throws InvalidRequestMethodException
     * @throws GuzzleException
     */
    protected function request(string $method, string $url, array $data = [], array $headers = []): ResponseInterface
    {
        if (!in_array($method, self::ALLOWED_METHODS, true)) {
            throw new InvalidRequestMethodException(
                sprintf(
                    'Invalid request method `%s`. Request method must be one of the following: %s',
                    $method,
                    implode(', ', self::ALLOWED_METHODS)
                )
            );
        }

        return $this->client->request($method, $url, [
            'headers' => $headers,
            ($method === 'GET' ? 'query' : 'json') => $data
        ]);
    }
}
