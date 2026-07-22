<?php

declare(strict_types=1);

use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use PlainSimple\Cloudflare\Adapters\AdapterInterface;
use PlainSimple\Cloudflare\Adapters\GuzzleAdapter;
use PlainSimple\Cloudflare\Auth\Unauthorized;
use PlainSimple\Cloudflare\Endpoints\DnsRecords;
use PlainSimple\Cloudflare\Entities\DnsRecord;
use PlainSimple\Cloudflare\Enums\DnsRecordType;
use PlainSimple\Cloudflare\Exceptions\ErrorResponseException;
use PlainSimple\Cloudflare\ValueObjects\DnsBatch;
use PlainSimple\Cloudflare\ValueObjects\DnsImport;
use PlainSimple\Cloudflare\ValueObjects\DnsRecordListQuery;
use PlainSimple\Cloudflare\ValueObjects\DnsScanReview;
use Psr\Http\Message\RequestInterface;

final class DnsRecordsTest extends TestCase
{
    private AdapterInterface&MockObject $adapter;
    private DnsRecords $records;

    protected function setUp(): void
    {
        $this->adapter = $this->createMock(AdapterInterface::class);
        $this->records = new DnsRecords($this->adapter);
    }

    public function testListSendsFiltersAndHydratesRecords(): void
    {
        $query = new DnsRecordListQuery(
            name: ['exact' => 'www.example.com'],
            proxied: false,
            includeShadowMetadata: true,
            type: DnsRecordType::A,
        );
        $this->adapter->expects($this->once())
            ->method('get')
            ->with('/zones/zone-id/dns_records', [
                'include_shadow_metadata' => true,
                'name.exact' => 'www.example.com',
                'proxied' => false,
                'type' => 'A',
            ])
            ->willReturn($this->jsonResponse([
                'success' => true,
                'result' => [[
                    'id' => 'record-id',
                    'name' => 'www.example.com',
                    'type' => 'A',
                    'ttl' => 300,
                    'content' => '192.0.2.1',
                    'proxied' => false,
                    'data' => ['address' => '192.0.2.1'],
                ]],
                'result_info' => [
                    'page' => 1,
                    'per_page' => 20,
                    'count' => 1,
                    'total_count' => 1,
                ],
            ]));

        $response = $this->records->list('zone-id', $query);
        $record = $response->getItems()[0];

        $this->assertSame('record-id', $record->getId());
        $this->assertSame(DnsRecordType::A, $record->getType());
        $this->assertFalse($record->isProxied());
        $this->assertSame(['address' => '192.0.2.1'], $record->getData());
        $this->assertSame(1, $response->getTotalCount());
    }

    public function testGetSendsShadowQuery(): void
    {
        $this->adapter->expects($this->once())
            ->method('get')
            ->with('/zones/zone-id/dns_records/record-id', ['include_shadow_metadata' => false])
            ->willReturn($this->recordResponse('record-id'));

        $record = $this->records->get('zone-id', 'record-id', false)->getEntity();

        $this->assertInstanceOf(DnsRecord::class, $record);
        $this->assertSame('record-id', $record->getId());
    }

    public function testEncodesZoneAndRecordPathSegments(): void
    {
        $this->adapter->expects($this->once())
            ->method('get')
            ->with('/zones/zone%2Fwith%3Fquery%3D1/dns_records/record%2Fwith%3Fquery%3D2', [])
            ->willReturn($this->recordResponse('record-id'));

        $this->records->get('zone/with?query=1', 'record/with?query=2');
    }

    public function testRejectsEmptyZoneIdBeforeTransport(): void
    {
        $this->adapter->expects($this->never())->method('get');

        $this->expectException(InvalidArgumentException::class);
        $this->records->list(' ');
    }

    public function testCreateSendsJsonAndShadowQuery(): void
    {
        $record = DnsRecord::forCreate('A', 'www.example.com', '192.0.2.1', 300);
        $record->setProxied(false);

        $this->adapter->expects($this->once())
            ->method('post')
            ->with('/zones/zone-id/dns_records?include_shadow_metadata=true', [
                'name' => 'www.example.com',
                'ttl' => 300,
                'type' => 'A',
                'content' => '192.0.2.1',
                'proxied' => false,
            ])
            ->willReturn($this->recordResponse('created-id'));

        $created = $this->records->create('zone-id', $record, true)->getEntity();

        $this->assertSame('created-id', $created->getId());
    }

    public function testCreateRejectsInvalidRecordBeforeTransport(): void
    {
        $this->adapter->expects($this->never())->method('post');

        $this->expectException(InvalidArgumentException::class);
        $this->records->create('zone-id', new DnsRecord());
    }

    public function testOverwriteSendsFullReplacementWithEntityId(): void
    {
        $record = DnsRecord::makeFromCloudflareData([
            'id' => 'record-id',
            'name' => 'www.example.com',
            'ttl' => 300,
            'type' => 'A',
            'content' => '192.0.2.1',
            'comment' => 'original',
            'proxied' => false,
            'proxiable' => true,
            'meta' => ['read_only' => true],
        ]);
        $record->setContent('192.0.2.2');

        $this->adapter->expects($this->once())
            ->method('put')
            ->with('/zones/zone-id/dns_records/record-id?include_shadow_metadata=false', [
                'name' => 'www.example.com',
                'ttl' => 300,
                'type' => 'A',
                'comment' => 'original',
                'content' => '192.0.2.2',
                'proxied' => false,
            ])
            ->willReturn($this->recordResponse('record-id'));

        $this->records->overwrite('zone-id', $record, false);
    }

    public function testUpdateSendsRequiredDiscriminatorsAndDirtyWritableFields(): void
    {
        $record = DnsRecord::makeFromCloudflareData([
            'id' => 'record-id',
            'name' => 'www.example.com',
            'ttl' => 300,
            'type' => 'A',
            'content' => '192.0.2.1',
            'proxied' => true,
            'future' => 'read-only',
        ]);
        $record->setProxied(false);
        $record->setComment(null);

        $this->adapter->expects($this->once())
            ->method('patch')
            ->with('/zones/zone-id/dns_records/record-id', [
                'name' => 'www.example.com',
                'ttl' => 300,
                'type' => 'A',
                'comment' => null,
                'proxied' => false,
            ])
            ->willReturn($this->recordResponse('record-id'));

        $this->records->update('zone-id', $record);
    }

    public function testOverwriteRequiresEntityId(): void
    {
        $record = DnsRecord::forCreate('A', 'www.example.com', '192.0.2.1');

        $this->expectException(InvalidArgumentException::class);
        $this->records->overwrite('zone-id', $record);
    }

    public function testOverwriteRejectsInvalidRecordBeforeTransport(): void
    {
        $record = new DnsRecord();
        $record->setId('record-id');
        $this->adapter->expects($this->never())->method('put');

        $this->expectException(InvalidArgumentException::class);
        $this->records->overwrite('zone-id', $record);
    }

    public function testDeleteAcceptsEntityAndReturnsActionResult(): void
    {
        $record = DnsRecord::makeFromCloudflareData(['id' => 'record-id']);
        $this->adapter->expects($this->once())
            ->method('delete')
            ->with('/zones/zone-id/dns_records/record-id')
            ->willReturn($this->jsonResponse([
                'result' => ['id' => 'record-id'],
            ]));

        $response = $this->records->delete('zone-id', $record);

        $this->assertSame(['id' => 'record-id'], $response->getResult());
    }

    public function testDeleteRejectsExplicitFalseSparseEnvelope(): void
    {
        $this->adapter->expects($this->once())
            ->method('delete')
            ->with('/zones/zone-id/dns_records/record-id')
            ->willReturn($this->jsonResponse([
                'success' => false,
                'result' => ['id' => 'record-id'],
                'errors' => [['code' => 1000, 'message' => 'Delete failed']],
            ]));

        $this->expectException(ErrorResponseException::class);
        $this->records->delete('zone-id', 'record-id');
    }

    public function testExportReturnsRawBindData(): void
    {
        $bind = "example.com. 300 IN A 192.0.2.1\n";
        $this->adapter->expects($this->once())
            ->method('get')
            ->with('/zones/zone-id/dns_records/export', [], ['Accept' => 'text/plain'])
            ->willReturn(new Response(200, ['Content-Type' => 'text/plain'], $bind));

        $response = $this->records->export('zone-id');

        $this->assertSame($bind, $response->getBody());
        $this->assertSame($bind, (string) $response->getOriginalResponse()->getBody());
    }

    public function testExportAllowsEmptySuccessfulBody(): void
    {
        $this->adapter->expects($this->once())
            ->method('get')
            ->with('/zones/zone-id/dns_records/export', [], ['Accept' => 'text/plain'])
            ->willReturn(new Response(200, ['Content-Type' => 'text/plain'], ''));

        $this->assertSame('', $this->records->export('zone-id')->getBody());
    }

    public function testExportSuppliesTextPlainAcceptHeaderToAdapterTransport(): void
    {
        $history = [];
        $stack = HandlerStack::create(new MockHandler([new Response(200, [], '')]));
        $stack->push(Middleware::history($history));
        $adapter = new GuzzleAdapter(
            new Unauthorized(),
            clientOptions: ['handler' => $stack],
        );

        (new DnsRecords($adapter))->export('zone-id');

        $request = $history[0]['request'] ?? null;
        $this->assertInstanceOf(RequestInterface::class, $request);
        $this->assertSame('text/plain', $request->getHeaderLine('Accept'));
    }

    public function testImportSendsMultipartAndKeepsResultAccessible(): void
    {
        $import = new DnsImport('example.com. 300 IN A 192.0.2.1', 'example.bind', false);
        $this->adapter->expects($this->once())
            ->method('postMultipart')
            ->with('/zones/zone-id/dns_records/import', [
                [
                    'name' => 'file',
                    'contents' => 'example.com. 300 IN A 192.0.2.1',
                    'filename' => 'example.bind',
                ],
                [
                    'name' => 'proxied',
                    'contents' => 'false',
                ],
            ])
            ->willReturn($this->jsonResponse([
                'success' => true,
                'result' => ['recs_added' => 1, 'total_records_parsed' => 1],
            ]));

        $response = $this->records->import('zone-id', $import);

        $this->assertSame(1, $response->getResult()['recs_added']);
    }

    public function testLegacyAndTriggeredScansAcceptSparseAndEmptyActions(): void
    {
        $this->adapter->expects($this->exactly(2))
            ->method('post')
            ->willReturnCallback(function (string $path, mixed $body = null): Response {
                return match ($path) {
                    '/zones/zone-id/dns_records/scan' => $this->legacyScanResponse($body),
                    '/zones/zone-id/dns_records/scan/trigger' => $this->triggerScanResponse($body),
                    default => throw new LogicException('Unexpected path: ' . $path),
                };
            });

        $legacy = $this->records->scan('zone-id', 'legacy');
        $triggered = $this->records->triggerScan('zone-id');

        $this->assertSame([], $legacy->getEnvelope());
        $this->assertNull($triggered->getResult());
    }

    public function testLegacyScanDefaultsToExplicitEmptyObject(): void
    {
        $this->adapter->expects($this->once())
            ->method('post')
            ->with(
                '/zones/zone-id/dns_records/scan',
                $this->callback(static fn (mixed $body): bool => $body instanceof stdClass),
            )
            ->willReturn(new Response(200, [], ''));

        $this->records->scan('zone-id');
    }

    public function testListsAndReviewsScannedRecords(): void
    {
        $scanned = DnsRecord::makeFromCloudflareData([
            'id' => 'temporary-id',
            'name' => 'found.example.com',
            'ttl' => 300,
            'type' => 'A',
            'content' => '192.0.2.10',
        ]);
        $this->adapter->expects($this->once())
            ->method('get')
            ->with('/zones/zone-id/dns_records/scan/review')
            ->willReturn($this->jsonResponse([
                'success' => true,
                'result' => [$scanned->toCloudflareData()],
            ]));

        $items = $this->records->listScanned('zone-id')->getItems();
        $this->assertSame('temporary-id', $items[0]->getId());

        $review = new DnsScanReview([$items[0]], ['rejected-id']);
        $this->adapter->expects($this->once())
            ->method('post')
            ->with('/zones/zone-id/dns_records/scan/review', [
                'accepts' => [[
                    'name' => 'found.example.com',
                    'ttl' => 300,
                    'type' => 'A',
                    'content' => '192.0.2.10',
                ]],
                'rejects' => [['id' => 'rejected-id']],
            ])
            ->willReturn($this->jsonResponse(['success' => true]));

        $this->assertNull($this->records->reviewScan('zone-id', $review)->getResult());
    }

    public function testBatchSendsGroupsAndReturnsRawResult(): void
    {
        $post = DnsRecord::forCreate('A', 'new.example.com', '192.0.2.20');
        $batch = new DnsBatch(deletes: ['delete-id'], posts: [$post]);
        $this->adapter->expects($this->once())
            ->method('post')
            ->with('/zones/zone-id/dns_records/batch?include_shadow_metadata=true', [
                'deletes' => [['id' => 'delete-id']],
                'posts' => [[
                    'name' => 'new.example.com',
                    'ttl' => 1,
                    'type' => 'A',
                    'content' => '192.0.2.20',
                ]],
            ])
            ->willReturn($this->jsonResponse([
                'success' => true,
                'result' => [
                    'deletes' => [['id' => 'delete-id']],
                    'posts' => [['id' => 'new-id']],
                ],
            ]));

        $result = $this->records->batch('zone-id', $batch, true)->getResult();

        $this->assertSame('new-id', $result['posts'][0]['id']);
    }

    /** @param array<string, mixed> $body */
    private function jsonResponse(array $body): Response
    {
        return new Response(
            200,
            ['Content-Type' => 'application/json'],
            json_encode($body, JSON_THROW_ON_ERROR),
        );
    }

    private function recordResponse(string $id): Response
    {
        return $this->jsonResponse([
            'success' => true,
            'result' => [
                'id' => $id,
                'name' => 'www.example.com',
                'type' => 'A',
                'ttl' => 300,
                'content' => '192.0.2.1',
            ],
        ]);
    }

    private function legacyScanResponse(mixed $body): Response
    {
        $this->assertSame('legacy', $body);

        return new Response(200, [], '');
    }

    private function triggerScanResponse(mixed $body): Response
    {
        $this->assertNull($body);

        return new Response(
            200,
            [],
            json_encode(['success' => true], JSON_THROW_ON_ERROR),
        );
    }
}
