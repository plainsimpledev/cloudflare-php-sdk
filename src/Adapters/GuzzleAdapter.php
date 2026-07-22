<?php

declare(strict_types=1);

namespace PlainSimple\Cloudflare\Adapters;

use Fig\Http\Message\RequestMethodInterface;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use InvalidArgumentException;
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

    /** @var array<string, string|list<string>> */
    private array $defaultHeaders;

    /** @var array<string, string|list<string>> */
    private array $authHeaders;

    /** @param array<string, mixed> $clientOptions */
    public function __construct(
        AuthInterface $auth,
        string $baseUri = 'https://api.cloudflare.com/client/v4',
        array $clientOptions = [],
    ) {
        /** @var array<string, string|list<string>> $clientHeaders */
        $clientHeaders = is_array($clientOptions['headers'] ?? null) ? $clientOptions['headers'] : [];
        /** @var array<string, string|list<string>> $authHeaders */
        $authHeaders = $auth->getHeaders();

        $this->authHeaders = $authHeaders;
        $this->defaultHeaders = $this->mergeHeaders(
            ['Accept' => 'application/json'],
            $clientHeaders,
            $this->authHeaders,
        );

        $clientOptions['base_uri'] = rtrim($baseUri, '/') . '/';
        unset($clientOptions['headers']);
        $clientOptions['allow_redirects'] = false;
        $clientOptions['http_errors'] = false;

        $this->client = new Client($clientOptions);
    }

    /**
     * @param array<string, mixed> $query
     * @param array<string, string|list<string>> $headers
     * @throws GuzzleException
     * @throws InvalidRequestMethodException
     */
    public function get(string $url, array $query = [], array $headers = []): ResponseInterface
    {
        return $this->request(RequestMethodInterface::METHOD_GET, $url, $query, $headers);
    }

    /**
     * @param array<string, string|list<string>> $headers
     * @throws InvalidRequestMethodException
     * @throws GuzzleException
     */
    public function post(string $url, mixed $data = null, array $headers = []): ResponseInterface
    {
        return $this->request(RequestMethodInterface::METHOD_POST, $url, $data, $headers);
    }

    /**
     * @param array<string, string|list<string>> $headers
     * @throws InvalidRequestMethodException
     * @throws GuzzleException
     */
    public function put(string $url, mixed $data = null, array $headers = []): ResponseInterface
    {
        return $this->request(RequestMethodInterface::METHOD_PUT, $url, $data, $headers);
    }

    /**
     * @param array<string, string|list<string>> $headers
     * @throws InvalidRequestMethodException
     * @throws GuzzleException
     */
    public function patch(string $url, mixed $data = null, array $headers = []): ResponseInterface
    {
        return $this->request(RequestMethodInterface::METHOD_PATCH, $url, $data, $headers);
    }

    /**
     * @param array<string, string|list<string>> $headers
     * @throws GuzzleException
     * @throws InvalidRequestMethodException
     */
    public function delete(string $url, mixed $data = null, array $headers = []): ResponseInterface
    {
        return $this->request(RequestMethodInterface::METHOD_DELETE, $url, $data, $headers);
    }

    /**
     * @param list<array<string, mixed>> $multipart
     * @param array<string, mixed> $query
     * @param array<string, string|list<string>> $headers
     * @throws GuzzleException
     */
    public function postMultipart(
        string $url,
        array $multipart,
        array $query = [],
        array $headers = [],
    ): ResponseInterface {
        return $this->client->request(RequestMethodInterface::METHOD_POST, $this->relativeUrl($url), [
            'headers' => $this->requestHeaders($headers),
            'multipart' => $multipart,
            'query' => $query,
        ]);
    }

    /**
     * @param array<string, string|list<string>> $headers
     * @throws InvalidRequestMethodException
     * @throws GuzzleException
     */
    protected function request(string $method, string $url, mixed $data = null, array $headers = []): ResponseInterface
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

        $options = ['headers' => $this->requestHeaders($headers)];
        if ($method === RequestMethodInterface::METHOD_GET) {
            $options['query'] = $data;
        } elseif ($data !== null) {
            $options['json'] = $data;
        }

        return $this->client->request($method, $this->relativeUrl($url), $options);
    }

    /**
     * @param array<string, string|list<string>> $headers
     * @return array<string, string|list<string>>
     */
    private function requestHeaders(array $headers): array
    {
        return $this->mergeHeaders($this->defaultHeaders, $headers, $this->authHeaders);
    }

    /**
     * @param array<string, string|list<string>> ...$headerSets
     * @return array<string, string|list<string>>
     */
    private function mergeHeaders(array ...$headerSets): array
    {
        $headers = [];
        $headerNames = [];

        foreach ($headerSets as $headerSet) {
            foreach ($headerSet as $name => $value) {
                $lowerName = strtolower($name);
                if (isset($headerNames[$lowerName])) {
                    unset($headers[$headerNames[$lowerName]]);
                }

                $headers[$name] = $value;
                $headerNames[$lowerName] = $name;
            }
        }

        return $headers;
    }

    private function relativeUrl(string $url): string
    {
        $relativeUrl = ltrim($url, '/');
        if (
            str_starts_with($url, '//')
            || preg_match('/^[a-z][a-z0-9+.-]*:/i', $url) === 1
            || preg_match('/^[a-z][a-z0-9+.-]*:/i', $relativeUrl) === 1
        ) {
            throw new InvalidArgumentException('Adapter URL must be relative.');
        }

        return $relativeUrl;
    }
}
