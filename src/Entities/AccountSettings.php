<?php

declare(strict_types=1);

namespace PlainSimple\Cloudflare\Entities;

use PlainSimple\Cloudflare\Enums\DefaultNameservers;

class AccountSettings extends AbstractEntity
{
    /**
     * @var string sets an abuse contact email to notify for abuse reports.
     */
    public string $abuse_contact_email;

    /**
     * @var DefaultNameservers specifies the default nameservers to be used for new zones added to this account.
     * @see https://developers.cloudflare.com/dns/nameservers/custom-nameservers/ for more information
     * @deprecated in favor of DNS Settings
     * @link https://developers.cloudflare.com/api/operations/dns-settings-for-an-account-update-dns-settings
     */
    public DefaultNameservers $default_nameservers;

    /**
     * @var bool indicates whether membership in this account requires that Two-Factor Authentication is enabled
     */
    public bool $enforce_twofactor;

    /**
     * @var bool indicates whether new zones should use the account-level custom nameservers by default.
     * @deprecated in favor of DNS Settings
     * @link https://developers.cloudflare.com/api/operations/dns-settings-for-an-account-update-dns-settings
     */
    public bool $use_account_custom_ns_by_default;
}
