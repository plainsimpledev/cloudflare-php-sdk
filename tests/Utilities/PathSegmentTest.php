<?php

declare(strict_types=1);

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use PlainSimple\Cloudflare\Utilities\PathSegment;

class PathSegmentTest extends TestCase
{
    public function testEncodesReservedCharacters(): void
    {
        $this->assertSame('..%2Fzones%2Fvictim%3Fforce%3D1', PathSegment::encode('../zones/victim?force=1'));
    }

    #[DataProvider('emptyValues')]
    public function testRejectsEmptyValues(string $value): void
    {
        $this->expectException(InvalidArgumentException::class);

        PathSegment::encode($value, 'Resource ID');
    }

    /** @return iterable<string, array{string}> */
    public static function emptyValues(): iterable
    {
        yield 'empty' => [''];
        yield 'whitespace' => ['   '];
    }
}
