<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use PlainSimple\Cloudflare\Entities\DnsRecord;
use PlainSimple\Cloudflare\Enums\DnsRecordType;

final class DnsRecordTest extends TestCase
{
    public function testRecordTypeContainsEverySupportedType(): void
    {
        $this->assertSame([
            'A',
            'AAAA',
            'CAA',
            'CERT',
            'CNAME',
            'DNSKEY',
            'DS',
            'HTTPS',
            'LOC',
            'MX',
            'NAPTR',
            'NS',
            'OPENPGPKEY',
            'PTR',
            'SMIMEA',
            'SRV',
            'SSHFP',
            'SVCB',
            'TLSA',
            'TXT',
            'URI',
        ], array_column(DnsRecordType::cases(), 'value'));
    }

    public function testHydratesEveryFieldAndPreservesUnknownData(): void
    {
        $record = DnsRecord::makeFromCloudflareData([
            'id' => 'record-id',
            'name' => '_sip._tcp.example.com',
            'type' => 'SRV',
            'ttl' => 300,
            'content' => '10 5 5060 sip.example.com',
            'data' => [
                'priority' => 10,
                'weight' => 5,
                'port' => 5060,
                'target' => 'sip.example.com',
            ],
            'priority' => 10,
            'comment' => null,
            'tags' => ['owner:dns'],
            'proxied' => false,
            'proxiable' => false,
            'private_routing' => true,
            'settings' => ['ipv4_only' => false, 'future' => ['nested' => true]],
            'meta' => ['shadowed_by' => ['ns-record-id']],
            'created_on' => '2026-07-22T12:00:00+00:00',
            'modified_on' => '2026-07-22T12:01:00+00:00',
            'comment_modified_on' => null,
            'tags_modified_on' => '2026-07-22T12:02:00+00:00',
            'future_field' => ['preserved' => true],
        ]);

        $this->assertSame('record-id', $record->getId());
        $this->assertSame('_sip._tcp.example.com', $record->getName());
        $this->assertSame(DnsRecordType::SRV, $record->getType());
        $this->assertSame(300, $record->getTtl());
        $this->assertSame('10 5 5060 sip.example.com', $record->getContent());
        $this->assertSame(5060, $record->getData()['port']);
        $this->assertSame(10, $record->getPriority());
        $this->assertNull($record->getComment());
        $this->assertSame(['owner:dns'], $record->getTags());
        $this->assertFalse($record->isProxied());
        $this->assertFalse($record->isProxiable());
        $this->assertTrue($record->isPrivateRouting());
        $this->assertFalse($record->getSettings()['ipv4_only']);
        $this->assertSame(['ns-record-id'], $record->getMeta()['shadowed_by']);
        $this->assertInstanceOf(DateTimeImmutable::class, $record->getCreatedOn());
        $this->assertInstanceOf(DateTimeImmutable::class, $record->getModifiedOn());
        $this->assertNull($record->getCommentModifiedOn());
        $this->assertInstanceOf(DateTimeImmutable::class, $record->getTagsModifiedOn());
        $this->assertSame(['future_field' => ['preserved' => true]], $record->getAdditionalAttributes());
        $this->assertSame([], $record->getDirtyFields());
    }

    public function testUnknownResponseTypeRemainsAString(): void
    {
        $record = DnsRecord::makeFromCloudflareData(['type' => 'FUTURE']);

        $this->assertSame('FUTURE', $record->getType());
        $this->assertSame(['type' => 'FUTURE'], $record->toCloudflareData());
    }

    public function testCreatePatchAndReplacePayloadsUseWritableFields(): void
    {
        $created = DnsRecord::forCreate(DnsRecordType::CAA, 'example.com', [
            'flags' => 0,
            'tag' => 'issue',
            'value' => 'letsencrypt.org',
        ]);
        $created->setProxied(false);

        $this->assertSame([
            'name' => 'example.com',
            'ttl' => 1,
            'type' => 'CAA',
            'data' => [
                'flags' => 0,
                'tag' => 'issue',
                'value' => 'letsencrypt.org',
            ],
            'proxied' => false,
        ], $created->toCreatePayload());

        $record = DnsRecord::makeFromCloudflareData([
            'id' => 'record-id',
            'name' => 'www.example.com',
            'ttl' => 300,
            'type' => 'A',
            'content' => '192.0.2.1',
            'comment' => 'old',
            'proxied' => false,
            'proxiable' => true,
            'meta' => ['auto_added' => false],
            'future_field' => 'read-only',
        ]);
        $record->setComment(null);

        $this->assertSame([
            'name' => 'www.example.com',
            'ttl' => 300,
            'type' => 'A',
            'comment' => null,
        ], $record->toPatchPayload());
        $this->assertSame([
            'name' => 'www.example.com',
            'ttl' => 300,
            'type' => 'A',
            'comment' => null,
            'content' => '192.0.2.1',
            'proxied' => false,
        ], $record->toReplacePayload());
    }

    public function testStructuredWritesUseDataAndDiscardCleanFormattedResponseContent(): void
    {
        $record = DnsRecord::makeFromCloudflareData([
            'id' => 'srv-id',
            'name' => '_sip._tcp.example.com',
            'ttl' => 300,
            'type' => 'SRV',
            'content' => '10 5 5060 sip.example.com',
            'data' => [
                'priority' => 10,
                'weight' => 5,
                'port' => 5060,
                'target' => 'sip.example.com',
            ],
            'priority' => 10,
            'comment' => 'discovered',
        ]);

        $this->assertSame([
            'name' => '_sip._tcp.example.com',
            'ttl' => 300,
            'type' => 'SRV',
            'comment' => 'discovered',
            'data' => [
                'priority' => 10,
                'weight' => 5,
                'port' => 5060,
                'target' => 'sip.example.com',
            ],
        ], $record->toReplacePayload());

        $record->setComment('accepted');

        $this->assertSame([
            'name' => '_sip._tcp.example.com',
            'ttl' => 300,
            'type' => 'SRV',
            'comment' => 'accepted',
        ], $record->toPatchPayload());
    }

    public function testWriteValidationRejectsIncompleteInvalidAndMismatchedRecords(): void
    {
        $empty = new DnsRecord();
        $emptyName = DnsRecord::forCreate('A', ' ', '192.0.2.1');
        $unknownType = DnsRecord::forCreate('FUTURE', 'example.com', 'value');
        $invalidTtl = DnsRecord::forCreate('A', 'example.com', '192.0.2.1', 29);
        $simpleWithData = DnsRecord::forCreate('A', 'example.com', ['address' => '192.0.2.1']);
        $structuredWithContent = DnsRecord::forCreate('CAA', 'example.com', '0 issue letsencrypt.org');

        foreach ([$empty, $emptyName, $unknownType, $invalidTtl, $simpleWithData, $structuredWithContent] as $record) {
            try {
                $record->toCreatePayload();
                $this->fail('Invalid DNS write was accepted.');
            } catch (InvalidArgumentException $exception) {
                $this->assertInstanceOf(InvalidArgumentException::class, $exception);
            }
        }
    }

    public function testEnterpriseMinimumTtlIsWritable(): void
    {
        $record = DnsRecord::forCreate('A', 'example.com', '192.0.2.1', 30);

        $this->assertSame(30, $record->toCreatePayload()['ttl']);
    }

    public function testWriteValidationRejectsWrongTypeSpecificDirtyFields(): void
    {
        $privateRouting = DnsRecord::forCreate('CNAME', 'www.example.com', 'target.example.com');
        $privateRouting->setPrivateRouting(true);

        $priority = DnsRecord::forCreate('A', 'www.example.com', '192.0.2.1');
        $priority->setPriority(10);

        $simpleData = DnsRecord::forCreate('A', 'www.example.com', '192.0.2.1');
        $simpleData->setData(['address' => '192.0.2.1']);

        $structuredContent = DnsRecord::forCreate('CAA', 'example.com', [
            'flags' => 0,
            'tag' => 'issue',
            'value' => 'letsencrypt.org',
        ]);
        $structuredContent->setContent('0 issue letsencrypt.org');

        foreach ([$privateRouting, $priority, $simpleData, $structuredContent] as $record) {
            try {
                $record->toCreatePayload();
                $this->fail('Invalid type-specific DNS field was accepted.');
            } catch (InvalidArgumentException $exception) {
                $this->assertInstanceOf(InvalidArgumentException::class, $exception);
            }
        }
    }

    public function testPatchRejectsRecordWithoutActualDirtyWrite(): void
    {
        $record = DnsRecord::makeFromCloudflareData([
            'id' => 'record-id',
            'name' => 'www.example.com',
            'ttl' => 300,
            'type' => 'A',
            'content' => '192.0.2.1',
        ]);

        $this->expectException(InvalidArgumentException::class);
        $record->toPatchPayload();
    }
}
