<?php

declare(strict_types=1);

use GuzzleHttp\Psr7\Query;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use PlainSimple\Cloudflare\Enums\ZoneStatus;
use PlainSimple\Cloudflare\Enums\ZoneType;
use PlainSimple\Cloudflare\ValueObjects\ZoneListQuery;

final class ZoneListQueryTest extends TestCase
{
    public function testToArrayUsesDottedAccountKeysAndCommaSeparatedTypes(): void
    {
        $query = new ZoneListQuery(
            accountId: 'account-id',
            accountName: 'Example Account',
            name: 'example.com',
            direction: 'asc',
            match: 'any',
            order: 'plan.id',
            page: 1,
            perPage: 50,
            status: ZoneStatus::Pending,
            types: [ZoneType::Full, 'partial', ZoneType::Internal],
        );

        $this->assertSame([
            'account.id' => 'account-id',
            'account.name' => 'Example Account',
            'name' => 'example.com',
            'direction' => 'asc',
            'match' => 'any',
            'order' => 'plan.id',
            'page' => 1,
            'per_page' => 50,
            'status' => 'pending',
            'type' => 'full,partial,internal',
        ], $query->toArray());
        $this->assertSame(
            'account.id=account-id&account.name=Example%20Account&name=example.com&direction=asc'
            . '&match=any&order=plan.id&page=1&per_page=50&status=pending&type=full%2Cpartial%2Cinternal',
            Query::build($query->toArray()),
        );
    }

    public function testToArrayOmitsNullValuesAndEmptyTypeList(): void
    {
        $query = new ZoneListQuery(
            name: '',
            types: [],
        );

        $this->assertSame([
            'name' => '',
        ], $query->toArray());
    }

    public function testToArrayPreservesUnknownStatusAndTypeStrings(): void
    {
        $query = new ZoneListQuery(
            status: 'future-status',
            types: ['future-type'],
        );

        $this->assertSame([
            'status' => 'future-status',
            'type' => 'future-type',
        ], $query->toArray());
    }

    /**
     * @param list<ZoneType|string>|null $types
     */
    #[DataProvider('invalidQueryProvider')]
    public function testRejectsInvalidPaginationAndOptions(
        ?int $page,
        ?int $perPage,
        ?string $direction,
        ?string $match,
        ?array $types,
    ): void {
        $this->expectException(InvalidArgumentException::class);

        new ZoneListQuery(
            direction: $direction,
            match: $match,
            page: $page,
            perPage: $perPage,
            types: $types,
        );
    }

    /** @return iterable<string, array{?int, ?int, ?string, ?string, list<ZoneType|string>|null}> */
    public static function invalidQueryProvider(): iterable
    {
        yield 'page below one' => [0, null, null, null, null];
        yield 'per page below five' => [null, 4, null, null, null];
        yield 'per page above fifty' => [null, 51, null, null, null];
        yield 'invalid direction' => [null, null, 'sideways', null, null];
        yield 'invalid match' => [null, null, null, 'none', null];
        yield 'blank type' => [null, null, null, null, ['   ']];
    }
}
