<?php

declare(strict_types=1);

namespace PlainSimple\Cloudflare\Entities;

use PlainSimple\Cloudflare\Enums\DefaultNameservers;

class AccountSettings extends AbstractEntity
{
    protected const CREATE_FIELDS = ['abuse_contact_email', 'enforce_twofactor'];
    protected const PATCH_FIELDS = self::CREATE_FIELDS;
    protected const REPLACE_FIELDS = self::CREATE_FIELDS;

    /** @var string|null sets an abuse contact email to notify for abuse reports. */
    private ?string $abuse_contact_email = null;

    /**
     * @var DefaultNameservers|null specifies default nameservers used for new zones added to this account.
     * @see https://developers.cloudflare.com/dns/nameservers/custom-nameservers/
     * @deprecated in favor of DNS Settings
     * @link https://developers.cloudflare.com/api/operations/dns-settings-for-an-account-update-dns-settings
     */
    private ?DefaultNameservers $default_nameservers = null;

    /** @var bool|null whether account membership requires Two-Factor Authentication */
    private ?bool $enforce_twofactor = null;

    /**
     * @var bool|null whether new zones use account-level custom nameservers by default
     * @deprecated in favor of DNS Settings
     * @link https://developers.cloudflare.com/api/operations/dns-settings-for-an-account-update-dns-settings
     */
    private ?bool $use_account_custom_ns_by_default = null;

    public function getAbuseContactEmail(): ?string
    {
        $this->getAttribute('abuse_contact_email');

        return $this->abuse_contact_email;
    }

    public function setAbuseContactEmail(?string $abuse_contact_email): void
    {
        $this->abuse_contact_email = $abuse_contact_email;
        $this->setAttribute('abuse_contact_email', $abuse_contact_email);
    }

    /** @deprecated in favor of DNS Settings */
    public function getDefaultNameservers(): ?DefaultNameservers
    {
        $this->getAttribute('default_nameservers');

        return $this->default_nameservers;
    }

    /** @deprecated in favor of DNS Settings */
    public function setDefaultNameservers(DefaultNameservers|string|null $default_nameservers): void
    {
        if (is_string($default_nameservers)) {
            $default_nameservers = DefaultNameservers::from($default_nameservers);
        }

        $this->default_nameservers = $default_nameservers;
        $this->setAttribute('default_nameservers', $default_nameservers);
    }

    public function isEnforceTwofactor(): ?bool
    {
        $this->getAttribute('enforce_twofactor');

        return $this->enforce_twofactor;
    }

    public function setEnforceTwofactor(?bool $enforce_twofactor): void
    {
        $this->enforce_twofactor = $enforce_twofactor;
        $this->setAttribute('enforce_twofactor', $enforce_twofactor);
    }

    /** @deprecated in favor of DNS Settings */
    public function isUseAccountCustomNsByDefault(): ?bool
    {
        $this->getAttribute('use_account_custom_ns_by_default');

        return $this->use_account_custom_ns_by_default;
    }

    /** @deprecated in favor of DNS Settings */
    public function setUseAccountCustomNsByDefault(?bool $use_account_custom_ns_by_default): void
    {
        $this->use_account_custom_ns_by_default = $use_account_custom_ns_by_default;
        $this->setAttribute('use_account_custom_ns_by_default', $use_account_custom_ns_by_default);
    }
}
