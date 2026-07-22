<?php

declare(strict_types=1);

use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\TestCase;
use PlainSimple\Cloudflare\Entities\Account;
use PlainSimple\Cloudflare\Exceptions\InvalidClassException;
use PlainSimple\Cloudflare\Responses\ActionResponse;
use PlainSimple\Cloudflare\Responses\EntityResponse;
use PlainSimple\Cloudflare\Responses\RawResponse;

class ResponseWrappersTest extends TestCase
{
    public function testEntityResponseHydratesConfiguredEntityClass(): void
    {
        $original = new Response(200);
        $response = new EntityResponse($original, [
            'result' => ['id' => 'account-id', 'name' => 'Account'],
        ], Account::class);

        $this->assertInstanceOf(Account::class, $response->getEntity());
        $this->assertSame('account-id', $response->getEntity()->getId());
        $this->assertSame($original, $response->getOriginalResponse());
    }

    public function testEntityClassMustExtendAbstractEntity(): void
    {
        $this->expectException(InvalidClassException::class);

        /** @phpstan-ignore-next-line argument.type */
        new EntityResponse(new Response(200), ['result' => []], stdClass::class);
    }

    public function testActionResponseExposesResultEnvelopeAndOriginalResponse(): void
    {
        $original = new Response(200);
        $envelope = ['success' => true, 'result' => ['id' => 'result-id']];
        $response = new ActionResponse($original, $envelope['result'], $envelope);

        $this->assertSame(['id' => 'result-id'], $response->getResult());
        $this->assertSame($envelope, $response->getEnvelope());
        $this->assertSame($original, $response->getOriginalResponse());
    }

    public function testRawResponseExposesBodyAndOriginalResponse(): void
    {
        $original = new Response(200, [], 'raw body');
        $response = new RawResponse($original, 'raw body');

        $this->assertSame('raw body', $response->getBody());
        $this->assertSame($original, $response->getOriginalResponse());
    }
}
