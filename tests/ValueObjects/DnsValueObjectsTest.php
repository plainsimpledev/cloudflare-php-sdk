<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use PlainSimple\Cloudflare\Entities\DnsRecord;
use PlainSimple\Cloudflare\Enums\DnsRecordType;
use PlainSimple\Cloudflare\ValueObjects\DnsBatch;
use PlainSimple\Cloudflare\ValueObjects\DnsImport;
use PlainSimple\Cloudflare\ValueObjects\DnsRecordListQuery;
use PlainSimple\Cloudflare\ValueObjects\DnsScanReview;

final class DnsValueObjectsTest extends TestCase
{
    public function testListQueryCoversNestedAndScalarFiltersWithoutDroppingFalse(): void
    {
        $query = new DnsRecordListQuery(
            comment: [
                'absent' => '',
                'contains' => 'verification',
                'endswith' => null,
                'exact' => 'exact comment',
                'present' => '',
                'startswith' => 'Domain',
            ],
            content: [
                'contains' => '192.0',
                'endswith' => '.1',
                'exact' => '192.0.2.1',
                'startswith' => '192',
            ],
            direction: 'desc',
            includeShadowMetadata: true,
            match: 'all',
            name: [
                'contains' => 'www',
                'endswith' => 'example.com',
                'exact' => 'www.example.com',
                'startswith' => 'www',
            ],
            order: 'proxied',
            page: 1,
            perPage: 100,
            proxied: false,
            search: '',
            shadowedByName: 'delegated.example.com',
            shadowingName: 'www.delegated.example.com',
            tag: [
                'absent' => 'archived',
                'contains' => 'owner:dns',
                'endswith' => ':dns',
                'exact' => 'env:prod',
                'present' => 'owner',
                'startswith' => 'owner:',
            ],
            tagMatch: 'any',
            type: DnsRecordType::A,
        );

        $result = $query->toArray();

        $this->assertTrue($result['include_shadow_metadata']);
        $this->assertFalse($result['proxied']);
        $this->assertSame(1, $result['page']);
        $this->assertSame('', $result['search']);
        $this->assertSame('', $result['comment.absent']);
        $this->assertArrayNotHasKey('comment.endswith', $result);
        $this->assertSame('A', $result['type']);
        $this->assertSame('www.delegated.example.com', $result['shadowing_name']);
    }

    public function testListQueryPreservesFalseWithoutShadowName(): void
    {
        $query = new DnsRecordListQuery(
            includeShadowMetadata: false,
            proxied: false,
        );

        $this->assertSame([
            'include_shadow_metadata' => false,
            'proxied' => false,
        ], $query->toArray());
    }

    public function testListQueryRejectsInvalidPaginationAndShadowDependency(): void
    {
        $factories = [
            static fn (): DnsRecordListQuery => new DnsRecordListQuery(page: 0),
            static fn (): DnsRecordListQuery => new DnsRecordListQuery(perPage: 0),
            static fn (): DnsRecordListQuery => new DnsRecordListQuery(perPage: 5000001),
            static fn (): DnsRecordListQuery => new DnsRecordListQuery(
                includeShadowMetadata: false,
                shadowedByName: 'delegated.example.com',
            ),
        ];

        foreach ($factories as $factory) {
            try {
                $factory();
                $this->fail('Invalid DNS record query was accepted.');
            } catch (InvalidArgumentException $exception) {
                $this->assertInstanceOf(InvalidArgumentException::class, $exception);
            }
        }
    }

    public function testListQueryAcceptsDocumentedPerPageBounds(): void
    {
        $this->assertSame(1, (new DnsRecordListQuery(perPage: 1))->toArray()['per_page']);
        $this->assertSame(5000000, (new DnsRecordListQuery(perPage: 5000000))->toArray()['per_page']);
    }

    public function testImportBuildsMultipartWithFilenameAndFalseProxied(): void
    {
        $import = new DnsImport('www.example.com. 300 IN A 192.0.2.1', 'zone.bind', false);

        $this->assertSame([
            [
                'name' => 'file',
                'contents' => 'www.example.com. 300 IN A 192.0.2.1',
                'filename' => 'zone.bind',
            ],
            [
                'name' => 'proxied',
                'contents' => 'false',
            ],
        ], $import->toMultipart());
    }

    public function testBatchEmitsGroupsInCloudflareExecutionOrder(): void
    {
        $delete = DnsRecord::makeFromCloudflareData(['id' => 'delete-entity']);

        $patch = DnsRecord::makeFromCloudflareData([
            'id' => 'patch-id',
            'name' => 'patch.example.com',
            'ttl' => 300,
            'type' => 'A',
            'content' => '192.0.2.1',
        ]);
        $patch->setProxied(false);

        $put = DnsRecord::makeFromCloudflareData([
            'id' => 'put-id',
            'name' => 'put.example.com',
            'ttl' => 600,
            'type' => 'AAAA',
            'content' => '2001:db8::1',
            'proxied' => false,
        ]);

        $post = DnsRecord::forCreate('TXT', 'post.example.com', 'verification');
        $batch = new DnsBatch(
            deletes: ['delete-string', $delete],
            patches: [$patch],
            puts: [$put],
            posts: [$post],
        );

        $payload = $batch->toArray();

        $this->assertFalse(array_is_list($payload));
        $this->assertSame(['deletes', 'patches', 'puts', 'posts'], array_keys($payload));
        $this->assertSame([
            ['id' => 'delete-string'],
            ['id' => 'delete-entity'],
        ], $payload['deletes']);
        $this->assertSame([
            'id' => 'patch-id',
            'name' => 'patch.example.com',
            'ttl' => 300,
            'type' => 'A',
            'proxied' => false,
        ], $payload['patches'][0]);
        $this->assertSame([
            'id' => 'put-id',
            'name' => 'put.example.com',
            'ttl' => 600,
            'type' => 'AAAA',
            'content' => '2001:db8::1',
            'proxied' => false,
        ], $payload['puts'][0]);
        $this->assertSame([
            'name' => 'post.example.com',
            'ttl' => 1,
            'type' => 'TXT',
            'content' => 'verification',
        ], $payload['posts'][0]);
    }

    public function testScanReviewSerializesAcceptedRecordsAndRejectedIds(): void
    {
        $record = DnsRecord::makeFromCloudflareData([
            'id' => 'temporary-id',
            'name' => 'found.example.com',
            'ttl' => 300,
            'type' => 'A',
            'content' => '192.0.2.10',
            'proxiable' => true,
        ]);
        $review = new DnsScanReview([$record], ['reject-id']);

        $this->assertSame([
            'accepts' => [[
                'name' => 'found.example.com',
                'ttl' => 300,
                'type' => 'A',
                'content' => '192.0.2.10',
            ]],
            'rejects' => [['id' => 'reject-id']],
        ], $review->toArray());
    }

    public function testBatchRejectsEmptyAndUnsafeIds(): void
    {
        try {
            new DnsBatch();
            $this->fail('Empty DNS batch was accepted.');
        } catch (InvalidArgumentException $exception) {
            $this->assertInstanceOf(InvalidArgumentException::class, $exception);
        }

        $batch = new DnsBatch(deletes: ['record/../batch?inject=1']);

        $this->expectException(InvalidArgumentException::class);
        $batch->toArray();
    }

    public function testBatchRejectsUnsafeUpdateEntityId(): void
    {
        $record = DnsRecord::makeFromCloudflareData([
            'id' => 'record?inject=1',
            'name' => 'www.example.com',
            'ttl' => 300,
            'type' => 'A',
            'content' => '192.0.2.1',
        ]);
        $record->setComment('changed');
        $batch = new DnsBatch(patches: [$record]);

        $this->expectException(InvalidArgumentException::class);
        $batch->toArray();
    }

    public function testBatchRejectsInvalidOperationEntity(): void
    {
        $batch = new DnsBatch(posts: [new DnsRecord()]);

        $this->expectException(InvalidArgumentException::class);
        $batch->toArray();
    }

    public function testScanReviewUsesStructuredSerializerAndRejectsUnsafeIds(): void
    {
        $record = DnsRecord::makeFromCloudflareData([
            'id' => 'temporary-id',
            'name' => 'example.com',
            'ttl' => 300,
            'type' => 'CAA',
            'content' => '0 issue letsencrypt.org',
            'data' => ['flags' => 0, 'tag' => 'issue', 'value' => 'letsencrypt.org'],
        ]);

        $this->assertSame([
            'accepts' => [[
                'name' => 'example.com',
                'ttl' => 300,
                'type' => 'CAA',
                'data' => ['flags' => 0, 'tag' => 'issue', 'value' => 'letsencrypt.org'],
            ]],
        ], (new DnsScanReview([$record]))->toArray());

        $this->expectException(InvalidArgumentException::class);
        (new DnsScanReview(rejects: ['record/../review?inject=1']))->toArray();
    }
}
