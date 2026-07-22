<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use PlainSimple\Cloudflare\Auth\Unauthorized;

class UnauthorizedTest extends TestCase
{
    public function testGetHeaders(): void
    {
        $auth = new Unauthorized();
        $headers = $auth->getHeaders();

        $this->assertEmpty($headers);
    }
}
