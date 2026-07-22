<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use PlainSimple\Cloudflare\ValueObjects\RulesetListQuery;

final class RulesetListQueryTest extends TestCase
{
    public function testSerializesOnlyPresentValues(): void
    {
        $query = new RulesetListQuery('next-cursor', 50);

        $this->assertSame('next-cursor', $query->getCursor());
        $this->assertSame(50, $query->getPerPage());
        $this->assertSame([
            'cursor' => 'next-cursor',
            'per_page' => 50,
        ], $query->toArray());
        $this->assertSame([], (new RulesetListQuery())->toArray());
    }
}
