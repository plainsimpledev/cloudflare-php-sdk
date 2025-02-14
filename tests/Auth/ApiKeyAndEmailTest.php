<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use PlainSimple\Cloudflare\Auth\ApiKeyAndEmail;

class ApiKeyAndEmailTest extends TestCase
{
    public function testGetHeaders(): void
    {
        $auth = new ApiKeyAndEmail('email@test.local', '1234567893feefc5f0q5000bfo0c38d90bbeb');
        $headers = $auth->getHeaders();

        $this->assertIsArray($headers);

        $this->assertArrayHasKey('X-Auth-Email', $headers);
        $this->assertArrayHasKey('X-Auth-Key', $headers);

        $this->assertEquals('email@test.local', $headers['X-Auth-Email']);
        $this->assertEquals('1234567893feefc5f0q5000bfo0c38d90bbeb', $headers['X-Auth-Key']);

        $this->assertCount(2, $headers);
    }
}
