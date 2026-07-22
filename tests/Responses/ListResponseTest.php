<?php

declare(strict_types=1);

use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\TestCase;
use PlainSimple\Cloudflare\Entities\Account;
use PlainSimple\Cloudflare\Exceptions\InvalidClassException;
use PlainSimple\Cloudflare\Responses\ListResponse;

class ListResponseTest extends TestCase
{
    public function testPageMetadataIsExposed(): void
    {
        $response = new ListResponse(new Response(200), [
            'result' => [['id' => 'account-id', 'name' => 'Account']],
            'result_info' => [
                'count' => 1,
                'page' => 2,
                'per_page' => 25,
                'total_count' => 51,
                'total_pages' => 3,
            ],
        ], Account::class);

        $this->assertCount(1, $response->getItems());
        $this->assertInstanceOf(Account::class, $response->getItems()[0]);
        $this->assertSame(1, $response->getCount());
        $this->assertSame(2, $response->getPage());
        $this->assertSame(25, $response->getPerPage());
        $this->assertSame(51, $response->getTotalCount());
        $this->assertSame(3, $response->getTotalPages());
        $this->assertNull($response->getNextCursor());
    }

    public function testCursorMetadataIsExposed(): void
    {
        $response = new ListResponse(new Response(200), [
            'result' => [],
            'result_info' => [
                'count' => 0,
                'cursors' => [
                    'before' => 'previous-cursor',
                    'after' => 'next-cursor',
                ],
            ],
        ], Account::class);

        $this->assertSame(0, $response->getCount());
        $this->assertSame('next-cursor', $response->getNextCursor());
        $this->assertNull($response->getPage());
        $this->assertNull($response->getTotalPages());
    }

    public function testMissingResultInfoProducesNullMetadata(): void
    {
        $response = new ListResponse(new Response(200), ['result' => []], Account::class);

        $this->assertNull($response->getCount());
        $this->assertNull($response->getPage());
        $this->assertNull($response->getPerPage());
        $this->assertNull($response->getTotalCount());
        $this->assertNull($response->getTotalPages());
        $this->assertNull($response->getNextCursor());
    }

    public function testEntityClassMustExtendAbstractEntity(): void
    {
        $this->expectException(InvalidClassException::class);

        /** @phpstan-ignore-next-line argument.type */
        new ListResponse(new Response(200), ['result' => []], stdClass::class);
    }
}
