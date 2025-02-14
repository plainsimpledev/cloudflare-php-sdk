<?php

declare(strict_types=1);

use GuzzleHttp\Exception\GuzzleException;
use PHPUnit\Framework\TestCase;
use PlainSimple\Cloudflare\Adapters\GuzzleAdapter;
use PlainSimple\Cloudflare\Auth\ApiToken;
use PlainSimple\Cloudflare\Auth\Auth;
use PlainSimple\Cloudflare\Exceptions\InvalidRequestMethod;
use Psr\Http\Message\ResponseInterface;

class GuzzleAdapterTest extends TestCase
{
    private const string TEST_API_TOKEN = '1234567890abcdefgEHASDGKCGKASGCA';

    /**
     * @throws InvalidRequestMethod
     * @throws GuzzleException
     * @throws JsonException
     */
    public function testGetRequest(): void
    {
        $adapter = $this->makeAdapter();
        $response = $adapter->get('/get', ['query1' => 'value1', 'query2' => 'value2']);

        $headers = $response->getHeaders();
        $this->assertEquals('application/json', $headers['Content-Type'][0]);
        $this->assertEquals(200, $response->getStatusCode());

        $responseContents = json_decode(
            $response->getBody()->getContents(),
            true,
            512,
            JSON_THROW_ON_ERROR
        );

        $this->assertArrayHasKey('args', $responseContents);

        $queryParams = $responseContents['args'];

        $this->assertArrayHasKey('query1', $queryParams);
        $this->assertEquals('value1', $queryParams['query1']);

        $this->assertArrayHasKey('query2', $queryParams);
        $this->assertEquals('value2', $queryParams['query2']);
    }

    /**
     * @throws InvalidRequestMethod
     * @throws GuzzleException
     * @throws JsonException
     */
    public function testPostRequest(): void
    {
        $adapter = $this->makeAdapter();
        $response = $adapter->post('/post', ['body1' => 'value1', 'body2' => ['value1', 'value2']]);
        $this->runPostLikeRequestsTest($response);
    }

    /**
     * @throws InvalidRequestMethod
     * @throws GuzzleException
     * @throws JsonException
     */
    public function testPutRequest(): void
    {
        $adapter = $this->makeAdapter();
        $response = $adapter->put('/put', ['body1' => 'value1', 'body2' => ['value1', 'value2']]);
        $this->runPostLikeRequestsTest($response);
    }

    /**
     * @throws InvalidRequestMethod
     * @throws GuzzleException
     * @throws JsonException
     */
    public function testPatchRequest(): void
    {
        $adapter = $this->makeAdapter();
        $response = $adapter->patch('/patch', ['body1' => 'value1', 'body2' => ['value1', 'value2']]);
        $this->runPostLikeRequestsTest($response);
    }

    /**
     * @throws InvalidRequestMethod
     * @throws GuzzleException
     * @throws JsonException
     */
    public function testDeleteRequest(): void
    {
        $adapter = $this->makeAdapter();
        $response = $adapter->delete('/delete', ['body1' => 'value1', 'body2' => ['value1', 'value2']]);
        $this->runPostLikeRequestsTest($response);
    }

    /**
     * @throws JsonException
     */
    private function runPostLikeRequestsTest(ResponseInterface $response): void
    {
        $headers = $response->getHeaders();
        $this->assertEquals('application/json', $headers['Content-Type'][0]);
        $this->assertEquals(200, $response->getStatusCode());

        $responseContents = json_decode(
            $response->getBody()->getContents(),
            true,
            512,
            JSON_THROW_ON_ERROR
        );

        $this->assertArrayHasKey('json', $responseContents);

        $json = $responseContents['json'];

        $this->assertArrayHasKey('body1', $json);
        $this->assertEquals('value1', $json['body1']);

        $this->assertArrayHasKey('body2', $json);
        $this->assertIsArray($json['body2']);
        $this->assertEquals(['value1', 'value2'], $json['body2']);
    }

    private function makeAdapter(): GuzzleAdapter
    {
        return new GuzzleAdapter(new ApiToken(self::TEST_API_TOKEN), 'https://httpbin.org');
    }
}