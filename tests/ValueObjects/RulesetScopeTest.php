<?php

declare(strict_types=1);

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use PlainSimple\Cloudflare\ValueObjects\RulesetScope;

final class RulesetScopeTest extends TestCase
{
    public function testBuildsZoneAndAccountPaths(): void
    {
        $zone = RulesetScope::zone('zone-id');
        $account = RulesetScope::account('account/id with space');

        $this->assertSame('zones/zone-id', $zone->path());
        $this->assertSame('zones/zone-id', $zone->getPath());
        $this->assertSame('accounts/account%2Fid%20with%20space', (string) $account);
    }

    #[DataProvider('emptyIds')]
    public function testRejectsEmptyIds(string $id): void
    {
        $this->expectException(InvalidArgumentException::class);

        RulesetScope::zone($id);
    }

    /** @return iterable<string, array{string}> */
    public static function emptyIds(): iterable
    {
        yield 'empty' => [''];
        yield 'whitespace' => [" \t\n"];
    }
}
