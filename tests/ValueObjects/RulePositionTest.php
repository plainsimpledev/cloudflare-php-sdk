<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use PlainSimple\Cloudflare\ValueObjects\RulePosition;

final class RulePositionTest extends TestCase
{
    public function testSupportsEachPositionVariant(): void
    {
        $before = RulePosition::before('rule-a');
        $after = RulePosition::after('rule-b');
        $index = RulePosition::index(2);

        $this->assertSame(['before' => 'rule-a'], $before->toArray());
        $this->assertSame('rule-a', $before->getBefore());
        $this->assertSame(['after' => 'rule-b'], $after->toArray());
        $this->assertSame('rule-b', $after->getAfter());
        $this->assertSame(['index' => 2], $index->toArray());
        $this->assertSame(2, $index->getIndex());
        $this->assertSame(['before' => ''], RulePosition::before('')->toArray());
        $this->assertSame(['after' => ''], RulePosition::fromArray(['after' => ''])->toArray());
    }

    public function testRequiresExactlyOnePosition(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new RulePosition(before: 'rule-a', after: 'rule-b');
    }

    public function testRejectsMissingPosition(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new RulePosition();
    }

    public function testRejectsInvalidIndex(): void
    {
        $this->expectException(InvalidArgumentException::class);

        RulePosition::index(0);
    }

    public function testRejectsUnknownArrayShape(): void
    {
        $this->expectException(InvalidArgumentException::class);

        RulePosition::fromArray(['middle' => 'rule-a']);
    }
}
