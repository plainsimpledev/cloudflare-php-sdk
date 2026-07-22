<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use PlainSimple\Cloudflare\Entities\Account;
use PlainSimple\Cloudflare\Entities\AccountManagedBy;
use PlainSimple\Cloudflare\Entities\AccountSettings;
use PlainSimple\Cloudflare\Enums\AccountType;
use PlainSimple\Cloudflare\Enums\DefaultNameservers;

final class AccountTest extends TestCase
{
    public function testHydratesCurrentAndCompatibleAccountFields(): void
    {
        $account = Account::makeFromCloudflareData([
            'id' => 'account-id',
            'name' => 'Example',
            'type' => 'enterprise',
            'created_on' => '2026-07-22T12:34:56Z',
            'managed_by' => [
                'parent_org_id' => 'parent-id',
                'parent_org_name' => 'Parent',
            ],
            'settings' => [
                'abuse_contact_email' => null,
                'default_nameservers' => 'custom.tenant',
                'enforce_twofactor' => true,
                'use_account_custom_ns_by_default' => false,
                'future_setting' => 'preserved',
            ],
            'future_account' => 'preserved',
        ]);

        $managedBy = $account->getManagedBy();
        $settings = $account->getSettings();

        $this->assertSame(AccountType::Enterprise, $account->getType());
        $this->assertInstanceOf(DateTimeImmutable::class, $account->getCreatedOn());
        $this->assertInstanceOf(AccountManagedBy::class, $managedBy);
        $this->assertSame('parent-id', $managedBy->getParentOrgId());
        $this->assertInstanceOf(AccountSettings::class, $settings);
        $this->assertNull($settings->getAbuseContactEmail());
        $this->assertSame(DefaultNameservers::CustomTenant, $settings->getDefaultNameservers());
        $this->assertTrue($settings->__get('enforce_twofactor'));
        $this->assertFalse($settings->__get('use_account_custom_ns_by_default'));
        $this->assertSame([], $account->getDirtyFields());
        $this->assertSame([], $managedBy->getDirtyFields());
        $this->assertSame([], $settings->getDirtyFields());
        $this->assertSame(['future_account' => 'preserved'], $account->getAdditionalAttributes());
        $this->assertSame(
            ['future_setting' => 'preserved'],
            $settings->getAdditionalAttributes(),
        );
    }

    public function testAccountPayloadsFollowOperationAllowlists(): void
    {
        $account = Account::makeFromCloudflareData([
            'id' => 'account-id',
            'name' => 'Original',
            'type' => 'standard',
            'created_on' => '2026-07-22T12:34:56Z',
            'managed_by' => ['parent_org_id' => 'parent-id'],
            'future_account' => 'preserved',
        ]);
        $account->setName('Changed');
        $account->setSettings([
            'enforce_twofactor' => false,
            'future_setting' => 'read only',
        ]);

        $this->assertSame([
            'name' => 'Changed',
            'type' => 'standard',
        ], $account->toCreatePayload());
        $this->assertSame([
            'name' => 'Changed',
            'settings' => ['enforce_twofactor' => false],
        ], $account->toPatchPayload());
        $this->assertSame([
            'id' => 'account-id',
            'name' => 'Changed',
            'type' => 'standard',
            'settings' => ['enforce_twofactor' => false],
        ], $account->toReplacePayload());
    }

    public function testDeprecatedSettingsRemainReadableButNeverWrite(): void
    {
        $settings = new AccountSettings();
        $settings->setAbuseContactEmail('abuse@example.com');
        $settings->setDefaultNameservers(DefaultNameservers::CustomAccount);
        $settings->setEnforceTwofactor(true);
        $settings->setUseAccountCustomNsByDefault(false);

        $expected = [
            'abuse_contact_email' => 'abuse@example.com',
            'enforce_twofactor' => true,
        ];

        $this->assertSame(DefaultNameservers::CustomAccount, $settings->getDefaultNameservers());
        $this->assertFalse($settings->isUseAccountCustomNsByDefault());
        $this->assertSame($expected, $settings->toCreatePayload());
        $this->assertSame($expected, $settings->toPatchPayload());
        $this->assertSame($expected, $settings->toReplacePayload());
    }

    public function testDeprecatedNestedSettingDoesNotDirtyAccountPatch(): void
    {
        $account = Account::makeFromCloudflareData([
            'id' => 'account-id',
            'name' => 'Example',
            'type' => 'standard',
            'settings' => [
                'abuse_contact_email' => 'abuse@example.com',
                'default_nameservers' => 'cloudflare.standard',
                'enforce_twofactor' => true,
            ],
        ]);
        $settings = $account->getSettings();
        $this->assertInstanceOf(AccountSettings::class, $settings);

        $settings->setDefaultNameservers(DefaultNameservers::CustomAccount);

        $this->assertSame([], $account->getDirtyFields());
        $this->assertSame([], $account->toPatchPayload());
    }
}
