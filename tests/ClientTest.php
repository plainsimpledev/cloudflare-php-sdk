<?php

declare(strict_types=1);

use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\TestCase;
use PlainSimple\Cloudflare\Adapters\AdapterInterface;
use PlainSimple\Cloudflare\Client;
use PlainSimple\Cloudflare\Endpoints\Accounts;
use PlainSimple\Cloudflare\Exceptions\EndpointDoesNotExistsException;
use Psr\Http\Message\RequestInterface;

final class ClientTest extends TestCase
{
    /** @var list<array<string, mixed>> */
    private array $history = [];

    public function testConstructorAcceptsAdapterAndCachesEndpointInstances(): void
    {
        $client = new Client($this->createStub(AdapterInterface::class));

        $this->assertInstanceOf(Accounts::class, $client->accounts());
        $this->assertSame($client->accounts(), $client->accounts());
    }

    public function testAccessorsDeclareConcreteEndpointTypes(): void
    {
        $client = new ReflectionClass(Client::class);
        $expectedTypes = [
            'accounts' => 'PlainSimple\\Cloudflare\\Endpoints\\Accounts',
            'zones' => 'PlainSimple\\Cloudflare\\Endpoints\\Zones',
            'dnsRecords' => 'PlainSimple\\Cloudflare\\Endpoints\\DnsRecords',
            'zoneSettings' => 'PlainSimple\\Cloudflare\\Endpoints\\ZoneSettings',
            'rulesets' => 'PlainSimple\\Cloudflare\\Endpoints\\Rulesets',
        ];

        foreach ($expectedTypes as $methodName => $expectedType) {
            $returnType = $client->getMethod($methodName)->getReturnType();
            $this->assertInstanceOf(ReflectionNamedType::class, $returnType);
            $this->assertSame($expectedType, $returnType->getName());
        }
    }

    public function testPrivateEndpointFactoryRejectsNonEndpointClasses(): void
    {
        $client = new Client($this->createStub(AdapterInterface::class));
        $factory = new ReflectionMethod($client, 'endpoint');

        $this->assertTrue($factory->isPrivate());
        $this->expectException(EndpointDoesNotExistsException::class);

        $factory->invoke($client, stdClass::class);
    }

    public function testWithApiTokenBuildsAuthenticatedAdapterWithoutNetwork(): void
    {
        $handler = HandlerStack::create(new MockHandler([
            new Response(200, [], json_encode([
                'success' => true,
                'result' => [],
                'result_info' => [
                    'page' => 1,
                    'per_page' => 20,
                    'count' => 0,
                    'total_count' => 0,
                ],
            ], JSON_THROW_ON_ERROR)),
        ]));
        /** @phpstan-ignore-next-line assign.propertyType */
        $handler->push(Middleware::history($this->history));
        $client = Client::withApiToken(
            'test-token',
            'https://api.example.test/client/v4/',
            [
                'handler' => $handler,
                'headers' => ['X-Client' => 'client-value'],
            ],
        );

        $client->accounts()->list();

        $request = $this->lastRequest();
        $this->assertSame('GET', $request->getMethod());
        $this->assertSame('https://api.example.test/client/v4/accounts?page=1&per_page=20&direction=asc', (string) $request->getUri());
        $this->assertSame('Bearer test-token', $request->getHeaderLine('Authorization'));
        $this->assertSame('application/json', $request->getHeaderLine('Accept'));
        $this->assertSame('client-value', $request->getHeaderLine('X-Client'));
    }

    private function lastRequest(): RequestInterface
    {
        $this->assertCount(1, $this->history);
        $request = $this->history[0]['request'];
        $this->assertInstanceOf(RequestInterface::class, $request);

        return $request;
    }
}
