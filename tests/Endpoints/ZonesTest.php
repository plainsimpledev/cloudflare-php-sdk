<?php

declare(strict_types=1);

use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use PlainSimple\Cloudflare\Adapters\AdapterInterface;
use PlainSimple\Cloudflare\Endpoints\Zones;
use PlainSimple\Cloudflare\Entities\AccountReference;
use PlainSimple\Cloudflare\Entities\Zone;
use PlainSimple\Cloudflare\Enums\ZoneStatus;
use PlainSimple\Cloudflare\Enums\ZoneType;
use PlainSimple\Cloudflare\Responses\ActionResponse;
use PlainSimple\Cloudflare\ValueObjects\ZoneListQuery;

final class ZonesTest extends TestCase
{
    private AdapterInterface&MockObject $adapter;

    private Zones $zones;

    protected function setUp(): void
    {
        $this->adapter = $this->createMock(AdapterInterface::class);
        $this->zones = new Zones($this->adapter);
    }

    public function testListSendsOpenApiQueryShapeAndHydratesCleanZones(): void
    {
        $query = new ZoneListQuery(
            accountId: 'account-id',
            accountName: 'Example Account',
            name: 'example.com',
            direction: 'desc',
            match: 'all',
            order: 'account.name',
            page: 2,
            perPage: 50,
            status: ZoneStatus::Active,
            types: [ZoneType::Full, 'internal'],
        );
        $response = $this->listResponse();

        $this->adapter->expects($this->once())
            ->method('get')
            ->with('/zones', [
                'account.id' => 'account-id',
                'account.name' => 'Example Account',
                'name' => 'example.com',
                'direction' => 'desc',
                'match' => 'all',
                'order' => 'account.name',
                'page' => 2,
                'per_page' => 50,
                'status' => 'active',
                'type' => 'full,internal',
            ])
            ->willReturn($response);

        $result = $this->zones->list($query);
        $items = $result->getItems();

        $this->assertCount(1, $items);
        $this->assertInstanceOf(Zone::class, $items[0]);
        $this->assertSame('zone-id', $items[0]->getId());
        $this->assertSame([], $items[0]->getDirtyFields());
        $this->assertSame(2, $result->getPage());
        $this->assertSame(50, $result->getPerPage());
    }

    public function testGetUsesZoneRoute(): void
    {
        $this->adapter->expects($this->once())
            ->method('get')
            ->with('/zones/..%2Fzone%20id%3Fx%3D1')
            ->willReturn($this->entityResponse());

        $entity = $this->zones->get('../zone id?x=1')->getEntity();

        $this->assertInstanceOf(Zone::class, $entity);
        $this->assertSame('example.com', $entity->getName());
    }

    public function testGetRejectsEmptyZoneIdBeforeAdapterCall(): void
    {
        $this->adapter->expects($this->never())->method('get');
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Zone ID must not be empty');

        $this->zones->get('   ');
    }

    public function testCreateSendsOnlyCreateFields(): void
    {
        $zone = Zone::forCreate('new.example.com', 'account-id', ZoneType::Partial);
        $zone->setId('client-side-id');
        $zone->setPaused(true);
        $zone->setStatus('pending');

        $this->adapter->expects($this->once())
            ->method('post')
            ->with('/zones', [
                'account' => ['id' => 'account-id'],
                'name' => 'new.example.com',
                'type' => 'partial',
            ])
            ->willReturn($this->entityResponse(['name' => 'new.example.com', 'type' => 'partial']));

        $entity = $this->zones->create($zone)->getEntity();

        $this->assertInstanceOf(Zone::class, $entity);
        $this->assertSame('new.example.com', $entity->getName());
    }

    public function testCreateRejectsEmptyZoneBeforeAdapterCall(): void
    {
        $this->adapter->expects($this->never())->method('post');
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('non-empty name');

        $this->zones->create(new Zone());
    }

    public function testCreateFactoryWithoutAccountIdSendsEmptyAccountObject(): void
    {
        $zone = Zone::forCreate('example.com');

        $this->adapter->expects($this->once())
            ->method('post')
            ->with('/zones', $this->callback(static function (array $payload): bool {
                self::assertInstanceOf(stdClass::class, $payload['account']);
                self::assertSame(
                    '{"account":{},"name":"example.com","type":"full"}',
                    json_encode($payload, JSON_THROW_ON_ERROR),
                );

                return true;
            }))
            ->willReturn($this->entityResponse());

        $this->zones->create($zone);
    }

    public function testCreateAcceptsArbitraryZoneWithoutType(): void
    {
        $zone = new Zone();
        $zone->setName('example.com');
        $zone->setAccount(AccountReference::forId('account-id'));

        $this->adapter->expects($this->once())
            ->method('post')
            ->with('/zones', [
                'account' => ['id' => 'account-id'],
                'name' => 'example.com',
            ])
            ->willReturn($this->entityResponse());

        $this->zones->create($zone);
    }

    public function testCreateRejectsZoneWithoutAccount(): void
    {
        $zone = new Zone();
        $zone->setName('example.com');

        $this->adapter->expects($this->never())->method('post');
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('account object');

        $this->zones->create($zone);
    }

    public function testCreateRejectsWhitespaceName(): void
    {
        $zone = Zone::forCreate('   ', 'account-id');

        $this->adapter->expects($this->never())->method('post');
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('non-empty name');

        $this->zones->create($zone);
    }

    public function testUpdateSendsExactlyOneDirtyWritableField(): void
    {
        $zone = Zone::makeFromCloudflareData(self::zoneFixture());
        $zone->setId('../zone id?x=1');
        $zone->setPaused(false);

        $this->adapter->expects($this->once())
            ->method('patch')
            ->with('/zones/..%2Fzone%20id%3Fx%3D1', ['paused' => false])
            ->willReturn($this->entityResponse(['paused' => false]));

        $entity = $this->zones->update($zone)->getEntity();

        $this->assertInstanceOf(Zone::class, $entity);
        $this->assertFalse($entity->isPaused());
    }

    public function testUpdateRejectsMoreThanOneDirtyWritableField(): void
    {
        $zone = Zone::makeFromCloudflareData(self::zoneFixture());
        $zone->setPaused(false);
        $zone->setType(ZoneType::Secondary);

        $this->adapter->expects($this->never())->method('patch');
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('exactly one dirty writable field');

        $this->zones->update($zone);
    }

    public function testUpdateRejectsNoDirtyWritableFields(): void
    {
        $zone = Zone::makeFromCloudflareData(self::zoneFixture());

        $this->adapter->expects($this->never())->method('patch');
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('exactly one dirty writable field');

        $this->zones->update($zone);
    }

    public function testUpdateRejectsZoneWithoutId(): void
    {
        $zone = new Zone();
        $zone->setPaused(true);

        $this->adapter->expects($this->never())->method('patch');
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('requires an id');

        $this->zones->update($zone);
    }

    public function testDeleteReturnsActionResponse(): void
    {
        $zone = Zone::makeFromCloudflareData(self::zoneFixture());
        $zone->setId('../zone id?force=1');

        $this->adapter->expects($this->once())
            ->method('delete')
            ->with('/zones/..%2Fzone%20id%3Fforce%3D1')
            ->willReturn($this->actionResponse());

        $result = $this->zones->delete($zone);

        $this->assertInstanceOf(ActionResponse::class, $result);
        $this->assertSame(['id' => 'zone-id'], $result->getResult());
    }

    public function testRerunActivationCheckUsesPutAndReturnsActionResponse(): void
    {
        $this->adapter->expects($this->once())
            ->method('put')
            ->with('/zones/..%2Fzone%20id%3Fforce%3D1/activation_check', null)
            ->willReturn($this->actionResponse());

        $result = $this->zones->rerunActivationCheck('../zone id?force=1');

        $this->assertInstanceOf(ActionResponse::class, $result);
        $this->assertSame(['id' => 'zone-id'], $result->getResult());
    }

    private function listResponse(): Response
    {
        return $this->response([
            'success' => true,
            'errors' => [],
            'messages' => [],
            'result' => [self::zoneFixture()],
            'result_info' => [
                'page' => 2,
                'per_page' => 50,
                'count' => 1,
                'total_count' => 1,
                'total_pages' => 1,
            ],
        ]);
    }

    /** @param array<string, mixed> $overrides */
    private function entityResponse(array $overrides = []): Response
    {
        return $this->response([
            'success' => true,
            'errors' => [],
            'messages' => [],
            'result' => array_replace(self::zoneFixture(), $overrides),
        ]);
    }

    private function actionResponse(): Response
    {
        return $this->response([
            'success' => true,
            'errors' => [],
            'messages' => [],
            'result' => ['id' => 'zone-id'],
        ]);
    }

    /** @param array<string, mixed> $envelope */
    private function response(array $envelope): Response
    {
        return new Response(200, [], json_encode($envelope, JSON_THROW_ON_ERROR));
    }

    /** @return array<string, mixed> */
    private static function zoneFixture(): array
    {
        return [
            'id' => 'zone-id',
            'account' => [
                'id' => 'account-id',
                'name' => 'Example Account',
            ],
            'activated_on' => '2014-01-02T00:01:00.12345Z',
            'created_on' => '2014-01-01T05:20:00.12345Z',
            'development_mode' => 7200,
            'meta' => [
                'cdn_only' => true,
                'custom_certificate_quota' => 1,
                'dns_only' => true,
                'foundation_dns' => true,
                'page_rule_quota' => 100,
                'phishing_detected' => false,
                'step' => 2,
            ],
            'modified_on' => '2014-01-01T05:20:00.12345Z',
            'name' => 'example.com',
            'name_servers' => [
                'bob.ns.cloudflare.com',
                'lola.ns.cloudflare.com',
            ],
            'original_dnshost' => 'NameCheap',
            'original_name_servers' => [
                'ns1.originaldnshost.com',
                'ns2.originaldnshost.com',
            ],
            'original_registrar' => 'GoDaddy',
            'owner' => [
                'id' => 'owner-id',
                'name' => 'Example Org',
                'type' => 'organization',
            ],
            'plan' => [
                'id' => 'plan-id',
                'can_subscribe' => false,
                'currency' => 'USD',
                'externally_managed' => false,
                'frequency' => 'monthly',
                'is_subscribed' => false,
                'legacy_discount' => false,
                'legacy_id' => 'free',
                'name' => 'Free Website',
                'price' => 0,
            ],
            'cname_suffix' => 'cdn.cloudflare.com',
            'paused' => true,
            'permissions' => ['#worker:read'],
            'status' => 'active',
            'tenant' => [
                'id' => 'tenant-id',
                'name' => 'Example Tenant',
            ],
            'tenant_unit' => ['id' => 'tenant-unit-id'],
            'type' => 'full',
            'vanity_name_servers' => [
                'ns1.example.com',
                'ns2.example.com',
            ],
            'verification_key' => '284344499-1084221259',
        ];
    }
}
