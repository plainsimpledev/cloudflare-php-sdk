<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use PlainSimple\Cloudflare\Auth\ApiToken;

class ApiTokenTest extends TestCase
{
    public function testGetHeaders(): void
    {
        $auth = new ApiToken('zKq9RDO6PbCjs6PRUXF3BoqFi3QdwY36C2VfOaRy');
        $headers = $auth->getHeaders();

        $this->assertIsArray($headers);

        $this->assertArrayHasKey('Authorization', $headers);

        $this->assertEquals('Bearer zKq9RDO6PbCjs6PRUXF3BoqFi3QdwY36C2VfOaRy', $headers['Authorization']);

        $this->assertCount(1, $headers);
    }
}
