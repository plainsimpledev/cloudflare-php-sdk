<?php

declare(strict_types=1);

use GuzzleHttp\Psr7\NoSeekStream;
use GuzzleHttp\Psr7\Response;
use GuzzleHttp\Psr7\Utils;
use PHPUnit\Framework\TestCase;
use PlainSimple\Cloudflare\Adapters\AdapterInterface;
use PlainSimple\Cloudflare\Endpoints\AbstractEndpoint;
use PlainSimple\Cloudflare\Exceptions\ErrorResponseException;
use PlainSimple\Cloudflare\Responses\ActionResponse;
use PlainSimple\Cloudflare\Responses\RawResponse;
use Psr\Http\Message\ResponseInterface;

final class TestableAbstractEndpoint extends AbstractEndpoint
{
    public function action(ResponseInterface $response, bool $allowSparseEnvelope = false): ActionResponse
    {
        return $this->makeActionResponse($response, $allowSparseEnvelope);
    }

    public function raw(ResponseInterface $response): RawResponse
    {
        return $this->makeRawResponse($response);
    }
}

class AbstractEndpointTest extends TestCase
{
    private TestableAbstractEndpoint $endpoint;

    protected function setUp(): void
    {
        $this->endpoint = new TestableAbstractEndpoint($this->createStub(AdapterInterface::class));
    }

    public function testEscapedQuotesAndBackslashesSurviveAndBodyRemainsReadable(): void
    {
        $expression = 'path == "C:\\logs\\file" && note == \\"quoted\\"';
        $body = json_encode([
            'success' => true,
            'errors' => [],
            'result' => ['expression' => $expression],
        ], JSON_THROW_ON_ERROR);
        $response = new Response(200, [], Utils::streamFor($body));

        $action = $this->endpoint->action($response);

        $this->assertSame($expression, $action->getResult()['expression']);
        $this->assertSame($body, $action->getOriginalResponse()->getBody()->getContents());
        $action->getOriginalResponse()->getBody()->rewind();
        $this->assertSame($body, $action->getOriginalResponse()->getBody()->getContents());
    }

    public function testNonSeekableBodyIsReplacedWithReadableStream(): void
    {
        $body = '{"success":true,"result":{"id":"result-id"}}';
        $response = new Response(200, [], new NoSeekStream(Utils::streamFor($body)));

        $action = $this->endpoint->action($response);

        $this->assertSame($body, $action->getOriginalResponse()->getBody()->getContents());
        $this->assertTrue($action->getOriginalResponse()->getBody()->isSeekable());
    }

    public function testApiFailureThrowsRegardlessOfHttpStatus(): void
    {
        $errors = [['code' => 1003, 'message' => 'Invalid request parameter']];
        $body = json_encode([
            'success' => false,
            'errors' => $errors,
        ], JSON_THROW_ON_ERROR);

        try {
            $this->endpoint->action(new Response(200, [], $body));
            $this->fail('Expected API failure');
        } catch (ErrorResponseException $exception) {
            $this->assertSame('Invalid request parameter', $exception->getMessage());
            $this->assertSame(1003, $exception->getCode());
            $this->assertSame($errors, $exception->getErrors());
            $this->assertSame($body, $exception->getResponse()->getBody()->getContents());
            $this->assertNull($exception->getPrevious());
        }
    }

    public function testMissingSuccessThrowsApiFailure(): void
    {
        $this->expectException(ErrorResponseException::class);

        $this->endpoint->action(new Response(200, [], '{"result":{}}'));
    }

    public function testSparseActionEnvelopeCanBeAcceptedExplicitly(): void
    {
        $body = '{"result":{"id":"deleted-id"},"errors":[]}';

        $action = $this->endpoint->action(new Response(200, [], $body), true);

        $this->assertSame(['id' => 'deleted-id'], $action->getResult());
        $this->assertSame($body, $action->getOriginalResponse()->getBody()->getContents());
    }

    public function testSparseActionModeStillRejectsExplicitFailure(): void
    {
        $this->expectException(ErrorResponseException::class);
        $this->expectExceptionCode(1003);

        $this->endpoint->action(new Response(200, [], json_encode([
            'success' => false,
            'errors' => [['code' => 1003, 'message' => 'Delete failed']],
        ], JSON_THROW_ON_ERROR)), true);
    }

    public function testEmptySuccessfulActionResponseIsAccepted(): void
    {
        $action = $this->endpoint->action(new Response(204));

        $this->assertNull($action->getResult());
        $this->assertSame([], $action->getEnvelope());
        $this->assertSame('', $action->getOriginalResponse()->getBody()->getContents());
    }

    public function testEmptyErrorResponseThrows(): void
    {
        $this->expectException(ErrorResponseException::class);
        $this->expectExceptionCode(503);

        $this->endpoint->action(new Response(503));
    }

    public function testNonSuccessfulStatusRejectsSuccessfulEnvelope(): void
    {
        $body = '{"success":true,"result":{"id":"result-id"}}';

        try {
            $this->endpoint->action(new Response(409, [], $body));
            $this->fail('Expected HTTP failure');
        } catch (ErrorResponseException $exception) {
            $this->assertSame(409, $exception->getCode());
            $this->assertSame($body, $exception->getResponse()->getBody()->getContents());
        }
    }

    public function testMalformedNonSuccessfulResponseWrapsJsonException(): void
    {
        $body = '{not-json';

        try {
            $this->endpoint->action(new Response(502, [], $body));
            $this->fail('Expected HTTP failure');
        } catch (ErrorResponseException $exception) {
            $this->assertInstanceOf(JsonException::class, $exception->getPrevious());
            $this->assertSame($body, $exception->getResponse()->getBody()->getContents());
        }
    }

    public function testMalformedSuccessfulResponseKeepsJsonException(): void
    {
        $this->expectException(JsonException::class);

        $this->endpoint->action(new Response(200, [], '{not-json'));
    }

    public function testRawResponseKeepsBodyUntouched(): void
    {
        $body = 'let expression = "path \\\\ with \\"quotes\\"";';

        $raw = $this->endpoint->raw(new Response(200, [], $body));

        $this->assertSame($body, $raw->getBody());
        $this->assertSame($body, $raw->getOriginalResponse()->getBody()->getContents());
    }

    public function testEmptyRawResponseIsAccepted(): void
    {
        $raw = $this->endpoint->raw(new Response(204));

        $this->assertSame('', $raw->getBody());
        $this->assertSame(204, $raw->getOriginalResponse()->getStatusCode());
    }

    public function testNonSuccessfulRawEnvelopeThrowsApiError(): void
    {
        $body = '{"success":false,"errors":[{"code":1003,"message":"Invalid request"}]}';

        try {
            $this->endpoint->raw(new Response(400, [], $body));
            $this->fail('Expected raw API failure');
        } catch (ErrorResponseException $exception) {
            $this->assertSame(1003, $exception->getCode());
            $this->assertSame('Invalid request', $exception->getMessage());
            $this->assertSame($body, $exception->getResponse()->getBody()->getContents());
        }
    }

    public function testMalformedNonSuccessfulRawResponseWrapsJsonException(): void
    {
        $body = 'upstream failure';

        try {
            $this->endpoint->raw(new Response(502, [], $body));
            $this->fail('Expected raw HTTP failure');
        } catch (ErrorResponseException $exception) {
            $this->assertInstanceOf(JsonException::class, $exception->getPrevious());
            $this->assertSame($body, $exception->getResponse()->getBody()->getContents());
        }
    }
}
