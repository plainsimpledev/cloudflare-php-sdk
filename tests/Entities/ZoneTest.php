<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use PlainSimple\Cloudflare\Entities\AccountReference;
use PlainSimple\Cloudflare\Entities\Zone;
use PlainSimple\Cloudflare\Enums\ZoneStatus;
use PlainSimple\Cloudflare\Enums\ZoneType;

final class ZoneTest extends TestCase
{
    public function testHydratesEveryDocumentedFieldAndMarksNestedEntitiesClean(): void
    {
        $zone = Zone::makeFromCloudflareData(self::zoneFixture());
        $account = $zone->getAccount();

        $this->assertSame('zone-id', $zone->getId());
        $this->assertInstanceOf(AccountReference::class, $account);
        $this->assertSame('account-id', $account->getId());
        $this->assertSame('Example Account', $account->getName());
        $this->assertInstanceOf(DateTimeImmutable::class, $zone->getActivatedOn());
        $this->assertInstanceOf(DateTimeImmutable::class, $zone->getCreatedOn());
        $this->assertSame(7200, $zone->getDevelopmentMode());
        $this->assertTrue($zone->getMeta()['cdn_only']);
        $this->assertInstanceOf(DateTimeImmutable::class, $zone->getModifiedOn());
        $this->assertSame('example.com', $zone->getName());
        $this->assertSame(['bob.ns.cloudflare.com', 'lola.ns.cloudflare.com'], $zone->getNameServers());
        $this->assertSame('NameCheap', $zone->getOriginalDnshost());
        $this->assertSame(
            ['ns1.originaldnshost.com', 'ns2.originaldnshost.com'],
            $zone->getOriginalNameServers(),
        );
        $this->assertSame('GoDaddy', $zone->getOriginalRegistrar());
        $this->assertSame('owner-id', $zone->getOwner()['id']);
        $this->assertSame('plan-id', $zone->getPlan()['id']);
        $this->assertSame('cdn.cloudflare.com', $zone->getCnameSuffix());
        $this->assertTrue($zone->isPaused());
        $this->assertSame(['#worker:read'], $zone->getPermissions());
        $this->assertSame('active', $zone->getStatus());
        $this->assertSame(ZoneStatus::Active, $zone->getKnownStatus());
        $this->assertSame('tenant-id', $zone->getTenant()['id']);
        $this->assertSame('tenant-unit-id', $zone->getTenantUnit()['id']);
        $this->assertSame('full', $zone->getType());
        $this->assertSame(ZoneType::Full, $zone->getKnownType());
        $this->assertSame(['ns1.example.com', 'ns2.example.com'], $zone->getVanityNameServers());
        $this->assertSame('verification-key', $zone->getVerificationKey());
        $this->assertSame([], $zone->getDirtyFields());
        $this->assertSame([], $account->getDirtyFields());
    }

    public function testNullableDatesAndUnknownTypeValuesSurviveHydration(): void
    {
        $zone = Zone::makeFromCloudflareData(array_replace(self::zoneFixture(), [
            'activated_on' => null,
            'created_on' => null,
            'modified_on' => null,
            'status' => 'deactivated',
            'type' => 'future-type',
            'future_zone_field' => ['preserved' => true],
        ]));

        $this->assertNull($zone->getActivatedOn());
        $this->assertNull($zone->getCreatedOn());
        $this->assertNull($zone->getModifiedOn());
        $this->assertSame('deactivated', $zone->getStatus());
        $this->assertNull($zone->getKnownStatus());
        $this->assertSame('future-type', $zone->getType());
        $this->assertNull($zone->getKnownType());
        $this->assertSame(['future_zone_field' => ['preserved' => true]], $zone->getAdditionalAttributes());
    }

    public function testForCreateProducesOnlyDocumentedCreateFields(): void
    {
        $zone = Zone::forCreate('example.com', ' account/id ');
        $zone->setId('read-only-id');
        $zone->setPaused(true);
        $zone->setCreatedOn('2026-07-22T10:00:00Z');

        $this->assertSame([
            'account' => ['id' => ' account/id '],
            'name' => 'example.com',
            'type' => 'full',
        ], $zone->toCreatePayload());
        $this->assertSame([
            'paused' => true,
            'type' => 'full',
        ], $zone->toPatchPayload());
        $this->assertSame([], $zone->toReplacePayload());
    }

    public function testForCreateWithoutAccountIdMarksEmptyAccountPresent(): void
    {
        $zone = Zone::forCreate('example.com');

        $this->assertTrue($zone->hasAttribute('account'));
        $this->assertInstanceOf(AccountReference::class, $zone->getAccount());
        $this->assertSame([
            'account' => [],
            'name' => 'example.com',
            'type' => 'full',
        ], $zone->toCreatePayload());
    }

    public function testForCreateCanOmitDefaultTypeExplicitly(): void
    {
        $zone = Zone::forCreate('example.com', type: null);

        $this->assertSame([
            'account' => [],
            'name' => 'example.com',
        ], $zone->toCreatePayload());
    }

    public function testAccountReferenceForIdRequiresNonemptyId(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Account ID must not be empty');

        AccountReference::forId('   ');
    }

    public function testPatchPayloadIncludesOnlyDirtyWritableFieldsAndPreservesFalse(): void
    {
        $zone = Zone::makeFromCloudflareData(self::zoneFixture());
        $zone->setName('read-only-change.example.com');
        $zone->setPaused(false);

        $this->assertSame(['paused' => false], $zone->toPatchPayload());
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
            'name_servers' => ['bob.ns.cloudflare.com', 'lola.ns.cloudflare.com'],
            'original_dnshost' => 'NameCheap',
            'original_name_servers' => ['ns1.originaldnshost.com', 'ns2.originaldnshost.com'],
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
            'vanity_name_servers' => ['ns1.example.com', 'ns2.example.com'],
            'verification_key' => 'verification-key',
        ];
    }
}
