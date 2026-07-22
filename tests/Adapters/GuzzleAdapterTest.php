<?php

declare(strict_types=1);

use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use PlainSimple\Cloudflare\Adapters\AdapterInterface;
use PlainSimple\Cloudflare\Adapters\GuzzleAdapter;
use PlainSimple\Cloudflare\Auth\ApiKeyAndEmail;
use PlainSimple\Cloudflare\Auth\ApiToken;
use PlainSimple\Cloudflare\Auth\AuthInterface;
use Psr\Http\Message\RequestInterface;

class GuzzleAdapterTest extends TestCase
{
    private const string TEST_API_TOKEN = 'test-api-token';

    /** @var list<array<string, mixed>> */
    private array $history = [];

    public function testAdapterInterfaceExposesTransportContractWithoutConstructor(): void
    {
        $interface = new ReflectionClass(AdapterInterface::class);

        $this->assertFalse($interface->hasMethod('__construct'));
        $this->assertSame(
            ['get', 'post', 'put', 'patch', 'delete', 'postMultipart'],
            array_map(
                static fn (ReflectionMethod $method): string => $method->getName(),
                $interface->getMethods(),
            ),
        );
    }

    public function testGetNormalizesUriAndSendsQueryAndHeaders(): void
    {
        $adapter = $this->makeAdapter([new Response(200)]);

        $adapter->get('/accounts', ['page' => 2, 'name' => 'A B'], ['X-Request-ID' => 'request-id']);

        $request = $this->lastRequest();
        $this->assertSame('GET', $request->getMethod());
        $this->assertSame('https://api.cloudflare.com/client/v4/accounts', $this->uriWithoutQuery($request));
        $this->assertSame(['page' => '2', 'name' => 'A B'], $this->query($request));
        $this->assertSame('application/json', $request->getHeaderLine('Accept'));
        $this->assertSame('Bearer ' . self::TEST_API_TOKEN, $request->getHeaderLine('Authorization'));
        $this->assertSame('request-id', $request->getHeaderLine('X-Request-ID'));
        $this->assertSame('', $request->getHeaderLine('Content-Type'));
        $this->assertSame(2.5, $this->history[0]['options']['timeout']);
    }

    #[DataProvider('jsonVerbProvider')]
    public function testJsonVerbs(string $method): void
    {
        $adapter = $this->makeAdapter([new Response(200)]);
        $data = ['name' => 'example', 'enabled' => true];

        match ($method) {
            'post' => $adapter->post('/zones', $data),
            'put' => $adapter->put('/zones', $data),
            'patch' => $adapter->patch('/zones', $data),
            'delete' => $adapter->delete('/zones', $data),
            default => throw new LogicException('Unsupported test method'),
        };

        $request = $this->lastRequest();
        $this->assertSame(strtoupper($method), $request->getMethod());
        $this->assertSame('https://api.cloudflare.com/client/v4/zones', (string) $request->getUri());
        $this->assertSame('application/json', $request->getHeaderLine('Content-Type'));
        $this->assertSame($data, json_decode((string) $request->getBody(), true, 512, JSON_THROW_ON_ERROR));
    }

    /** @return iterable<string, array{string}> */
    public static function jsonVerbProvider(): iterable
    {
        yield 'POST' => ['post'];
        yield 'PUT' => ['put'];
        yield 'PATCH' => ['patch'];
        yield 'DELETE' => ['delete'];
    }

    #[DataProvider('jsonVerbProvider')]
    public function testOmittedJsonBodySendsNoBodyOrContentType(string $method): void
    {
        $adapter = $this->makeAdapter([new Response(200)]);

        match ($method) {
            'post' => $adapter->post('/zones'),
            'put' => $adapter->put('/zones'),
            'patch' => $adapter->patch('/zones'),
            'delete' => $adapter->delete('/zones'),
            default => throw new LogicException('Unsupported test method'),
        };

        $request = $this->lastRequest();
        $this->assertSame('', (string) $request->getBody());
        $this->assertSame('', $request->getHeaderLine('Content-Type'));
    }

    public function testExplicitEmptyListSendsJsonBody(): void
    {
        $adapter = $this->makeAdapter([new Response(200)]);

        $adapter->post('/zones', []);

        $request = $this->lastRequest();
        $this->assertSame('[]', (string) $request->getBody());
        $this->assertSame('application/json', $request->getHeaderLine('Content-Type'));
    }

    #[DataProvider('unsafeUrlProvider')]
    public function testRejectsNonRelativeUrls(string $url): void
    {
        $adapter = $this->makeAdapter([new Response(200)]);

        $this->expectException(InvalidArgumentException::class);

        $adapter->get($url);
    }

    /** @return iterable<string, array{string}> */
    public static function unsafeUrlProvider(): iterable
    {
        yield 'absolute URL' => ['https://attacker.example/collect'];
        yield 'scheme-relative URL' => ['//attacker.example/collect'];
        yield 'absolute URL after endpoint slash' => ['/https://attacker.example/collect'];
    }

    public function testAuthenticationOverridesAuthorizationHeadersCaseInsensitively(): void
    {
        $adapter = $this->makeAdapter(
            [new Response(200)],
            new ApiToken(self::TEST_API_TOKEN),
            ['headers' => ['authorization' => 'Bearer client-option-token']],
        );

        $adapter->get('/accounts', [], ['AUTHORIZATION' => 'Bearer request-token']);

        $request = $this->lastRequest();
        $this->assertSame(['Bearer ' . self::TEST_API_TOKEN], $request->getHeader('Authorization'));
        $this->assertSingleHeaderName('authorization', $request);
    }

    public function testPerRequestAcceptOverridesDefaultWhileAuthenticationRemainsProtected(): void
    {
        $adapter = $this->makeAdapter(
            [new Response(200)],
            new ApiToken(self::TEST_API_TOKEN),
            ['headers' => ['authorization' => 'Bearer client-option-token']],
        );

        $adapter->get('/zones/example/dns_records/export', [], [
            'accept' => 'text/plain',
            'AUTHORIZATION' => 'Bearer request-token',
        ]);

        $request = $this->lastRequest();
        $this->assertSame('text/plain', $request->getHeaderLine('Accept'));
        $this->assertSame('Bearer ' . self::TEST_API_TOKEN, $request->getHeaderLine('Authorization'));
        $this->assertSingleHeaderName('accept', $request);
        $this->assertSingleHeaderName('authorization', $request);
    }

    public function testAuthenticationOverridesXAuthHeadersCaseInsensitively(): void
    {
        $adapter = $this->makeAdapter(
            [new Response(200)],
            new ApiKeyAndEmail('trusted@example.com', 'trusted-key'),
            [
                'headers' => [
                    'x-auth-email' => 'client@example.com',
                    'X-AUTH-KEY' => 'client-key',
                ],
            ],
        );

        $adapter->get('/accounts', [], [
            'X-AUTH-EMAIL' => 'request@example.com',
            'x-auth-key' => 'request-key',
        ]);

        $request = $this->lastRequest();
        $this->assertSame(['trusted@example.com'], $request->getHeader('X-Auth-Email'));
        $this->assertSame(['trusted-key'], $request->getHeader('X-Auth-Key'));
        $this->assertSingleHeaderName('x-auth-email', $request);
        $this->assertSingleHeaderName('x-auth-key', $request);
    }

    public function testPostMultipartSendsMultipartBodyAndQuery(): void
    {
        $adapter = $this->makeAdapter([new Response(201)]);

        $response = $adapter->postMultipart('/rules/import', [
            ['name' => 'metadata', 'contents' => '{"kind":"rules"}'],
            ['name' => 'file', 'contents' => 'rule contents', 'filename' => 'rules.txt'],
        ], ['validate' => 'true']);

        $request = $this->lastRequest();
        $body = (string) $request->getBody();
        $this->assertSame(201, $response->getStatusCode());
        $this->assertSame('POST', $request->getMethod());
        $this->assertSame(['validate' => 'true'], $this->query($request));
        $this->assertStringStartsWith('multipart/form-data; boundary=', $request->getHeaderLine('Content-Type'));
        $this->assertStringContainsString('name="metadata"', $body);
        $this->assertStringContainsString('{"kind":"rules"}', $body);
        $this->assertStringContainsString('filename="rules.txt"', $body);
        $this->assertStringContainsString('rule contents', $body);
    }

    public function testHttpErrorsAreReturnedWithoutGuzzleException(): void
    {
        $queuedResponse = new Response(422, [], '{"success":false}');
        $adapter = $this->makeAdapter([$queuedResponse]);

        $response = $adapter->get('/invalid');

        $this->assertSame($queuedResponse, $response);
        $this->assertSame(422, $response->getStatusCode());
    }

    public function testRedirectsAreDisabledRegardlessOfClientOptions(): void
    {
        $adapter = $this->makeAdapter(
            [new Response(302, ['Location' => 'https://attacker.example/collect'])],
            clientOptions: ['allow_redirects' => true],
        );

        $response = $adapter->get('/redirect');

        $this->assertSame(302, $response->getStatusCode());
        $this->assertFalse($this->history[0]['options']['allow_redirects']);
    }

    /**
     * @param list<Response> $responses
     * @param array<string, mixed> $clientOptions
     */
    private function makeAdapter(
        array $responses,
        ?AuthInterface $auth = null,
        array $clientOptions = [],
    ): GuzzleAdapter {
        $mockHandler = new MockHandler($responses);
        $handler = HandlerStack::create($mockHandler);
        /** @phpstan-ignore-next-line assign.propertyType */
        $handler->push(Middleware::history($this->history));

        $clientOptions['handler'] = $handler;
        $clientOptions['timeout'] ??= 2.5;

        return new GuzzleAdapter(
            $auth ?? new ApiToken(self::TEST_API_TOKEN),
            'https://api.cloudflare.com/client/v4',
            $clientOptions,
        );
    }

    private function lastRequest(): RequestInterface
    {
        $this->assertNotEmpty($this->history);
        $request = $this->history[count($this->history) - 1]['request'];
        $this->assertInstanceOf(RequestInterface::class, $request);

        return $request;
    }

    private function uriWithoutQuery(RequestInterface $request): string
    {
        return (string) $request->getUri()->withQuery('');
    }

    private function assertSingleHeaderName(string $expectedName, RequestInterface $request): void
    {
        $headerNames = array_filter(
            array_keys($request->getHeaders()),
            static fn (string $name): bool => strtolower($name) === $expectedName,
        );

        $this->assertCount(1, $headerNames);
    }

    /** @return array<string, string> */
    private function query(RequestInterface $request): array
    {
        parse_str($request->getUri()->getQuery(), $query);
        $stringQuery = [];
        foreach ($query as $name => $value) {
            if (is_string($name) && is_string($value)) {
                $stringQuery[$name] = $value;
            }
        }

        return $stringQuery;
    }
}
