<?php

declare(strict_types=1);

namespace PlainSimple\Cloudflare\Entities;

use PlainSimple\Cloudflare\Enums\DefaultNameservers;

class AccountSettings extends AbstractEntity
{
    /**
     * @var string sets an abuse contact email to notify for abuse reports.
     */
    private string $abuse_contact_email;

    /**
     * @var DefaultNameservers specifies the default nameservers to be used for new zones added to this account.
     * @see https://developers.cloudflare.com/dns/nameservers/custom-nameservers/ for more information
     * @deprecated in favor of DNS Settings
     * @link https://developers.cloudflare.com/api/operations/dns-settings-for-an-account-update-dns-settings
     */
    private DefaultNameservers $default_nameservers;

    /**
     * @var bool indicates whether membership in this account requires that Two-Factor Authentication is enabled
     */
    private bool $enforce_twofactor;

    /**
     * @var bool indicates whether new zones should use the account-level custom nameservers by default.
     * @deprecated in favor of DNS Settings
     * @link https://developers.cloudflare.com/api/operations/dns-settings-for-an-account-update-dns-settings
     */
    private bool $use_account_custom_ns_by_default;

    public function getAbuseContactEmail(): string
    {
        return $this->abuse_contact_email;
    }

    public function setAbuseContactEmail(string $abuse_contact_email): void
    {
        $this->abuse_contact_email = $abuse_contact_email;
    }

    /**
     * @deprecated in favor of DNS Settings
     */
    public function getDefaultNameservers(): DefaultNameservers
    {
        return $this->default_nameservers;
    }

    /**
     * @deprecated in favor of DNS Settings
     */
    public function setDefaultNameservers(mixed $default_nameservers): void
    {
        if (!($default_nameservers instanceof DefaultNameservers)) {
            $default_nameservers = DefaultNameservers::tryFrom($default_nameservers);
        }
        $this->default_nameservers = $default_nameservers;
    }

    public function isEnforceTwofactor(): bool
    {
        return $this->enforce_twofactor;
    }

    public function setEnforceTwofactor(bool $enforce_twofactor): void
    {
        $this->enforce_twofactor = $enforce_twofactor;
    }

    /**
     * @deprecated in favor of DNS Settings
     */
    public function isUseAccountCustomNsByDefault(): bool
    {
        return $this->use_account_custom_ns_by_default;
    }

    /**
     * @deprecated in favor of DNS Settings
     */
    public function setUseAccountCustomNsByDefault(bool $use_account_custom_ns_by_default): void
    {
        $this->use_account_custom_ns_by_default = $use_account_custom_ns_by_default;
    }
}
