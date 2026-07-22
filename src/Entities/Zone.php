<?php

declare(strict_types=1);

namespace PlainSimple\Cloudflare\Entities;

use DateMalformedStringException;
use DateTimeImmutable;
use DateTimeInterface;
use PlainSimple\Cloudflare\Enums\ZoneStatus;
use PlainSimple\Cloudflare\Enums\ZoneType;

class Zone extends AbstractEntity
{
    protected const CREATE_FIELDS = ['account', 'name', 'type'];
    protected const PATCH_FIELDS = ['paused', 'type', 'vanity_name_servers'];
    protected const REPLACE_FIELDS = [];

    private string $id;

    private AccountReference $account;

    private ?DateTimeImmutable $activated_on = null;

    private ?DateTimeImmutable $created_on = null;

    private int $development_mode;

    /** @var array<string, mixed> */
    private array $meta;

    private ?DateTimeImmutable $modified_on = null;

    private string $name;

    /** @var list<string> */
    private array $name_servers;

    private ?string $original_dnshost = null;

    /** @var list<string>|null */
    private ?array $original_name_servers = null;

    private ?string $original_registrar = null;

    /** @var array<string, mixed>|null */
    private ?array $owner = null;

    /** @var array<string, mixed>|null */
    private ?array $plan = null;

    private ?string $cname_suffix = null;

    private bool $paused;

    /** @var list<string> */
    private array $permissions;

    private string $status;

    /** @var array<string, mixed>|null */
    private ?array $tenant = null;

    /** @var array<string, mixed>|null */
    private ?array $tenant_unit = null;

    private string $type;

    /** @var list<string> */
    private array $vanity_name_servers;

    private ?string $verification_key = null;

    public static function forCreate(
        string $name,
        ?string $accountId = null,
        ZoneType|string|null $type = ZoneType::Full,
    ): self {
        $zone = new self();
        $zone->setAccount($accountId === null ? new AccountReference() : AccountReference::forId($accountId));
        $zone->setName($name);
        if ($type !== null) {
            $zone->setType($type);
        }

        return $zone;
    }

    public function getId(): string
    {
        $this->getAttribute('id');

        return $this->id;
    }

    public function setId(string $id): void
    {
        $this->id = $id;
        $this->setAttribute('id', $id);
    }

    public function getAccount(): AccountReference
    {
        $this->getAttribute('account');

        return $this->account;
    }

    /** @param AccountReference|array<string, mixed> $account */
    public function setAccount(AccountReference|array $account): void
    {
        if (is_array($account)) {
            $account = AccountReference::makeFromCloudflareData($account);
        }

        $this->account = $account;
        $this->setAttribute('account', $account);
    }

    public function getActivatedOn(): ?DateTimeImmutable
    {
        $this->getAttribute('activated_on');

        return $this->activated_on;
    }

    /** @throws DateMalformedStringException */
    public function setActivatedOn(DateTimeInterface|string|null $activatedOn): void
    {
        $this->activated_on = $this->normalizeDate($activatedOn);
        $this->setAttribute('activated_on', $this->activated_on);
    }

    public function getCreatedOn(): ?DateTimeImmutable
    {
        $this->getAttribute('created_on');

        return $this->created_on;
    }

    /** @throws DateMalformedStringException */
    public function setCreatedOn(DateTimeInterface|string|null $createdOn): void
    {
        $this->created_on = $this->normalizeDate($createdOn);
        $this->setAttribute('created_on', $this->created_on);
    }

    public function getDevelopmentMode(): int
    {
        $this->getAttribute('development_mode');

        return $this->development_mode;
    }

    public function setDevelopmentMode(int $developmentMode): void
    {
        $this->development_mode = $developmentMode;
        $this->setAttribute('development_mode', $developmentMode);
    }

    /** @return array<string, mixed> */
    public function getMeta(): array
    {
        $this->getAttribute('meta');

        return $this->meta;
    }

    /** @param array<string, mixed> $meta */
    public function setMeta(array $meta): void
    {
        $this->meta = $meta;
        $this->setAttribute('meta', $meta);
    }

    public function getModifiedOn(): ?DateTimeImmutable
    {
        $this->getAttribute('modified_on');

        return $this->modified_on;
    }

    /** @throws DateMalformedStringException */
    public function setModifiedOn(DateTimeInterface|string|null $modifiedOn): void
    {
        $this->modified_on = $this->normalizeDate($modifiedOn);
        $this->setAttribute('modified_on', $this->modified_on);
    }

    public function getName(): string
    {
        $this->getAttribute('name');

        return $this->name;
    }

    public function setName(string $name): void
    {
        $this->name = $name;
        $this->setAttribute('name', $name);
    }

    /** @return list<string> */
    public function getNameServers(): array
    {
        $this->getAttribute('name_servers');

        return $this->name_servers;
    }

    /** @param list<string> $nameServers */
    public function setNameServers(array $nameServers): void
    {
        $this->name_servers = $nameServers;
        $this->setAttribute('name_servers', $nameServers);
    }

    public function getOriginalDnshost(): ?string
    {
        $this->getAttribute('original_dnshost');

        return $this->original_dnshost;
    }

    public function setOriginalDnshost(?string $originalDnshost): void
    {
        $this->original_dnshost = $originalDnshost;
        $this->setAttribute('original_dnshost', $originalDnshost);
    }

    /** @return list<string>|null */
    public function getOriginalNameServers(): ?array
    {
        $this->getAttribute('original_name_servers');

        return $this->original_name_servers;
    }

    /** @param list<string>|null $originalNameServers */
    public function setOriginalNameServers(?array $originalNameServers): void
    {
        $this->original_name_servers = $originalNameServers;
        $this->setAttribute('original_name_servers', $originalNameServers);
    }

    public function getOriginalRegistrar(): ?string
    {
        $this->getAttribute('original_registrar');

        return $this->original_registrar;
    }

    public function setOriginalRegistrar(?string $originalRegistrar): void
    {
        $this->original_registrar = $originalRegistrar;
        $this->setAttribute('original_registrar', $originalRegistrar);
    }

    /** @return array<string, mixed>|null */
    public function getOwner(): ?array
    {
        $this->getAttribute('owner');

        return $this->owner;
    }

    /** @param array<string, mixed>|null $owner */
    public function setOwner(?array $owner): void
    {
        $this->owner = $owner;
        $this->setAttribute('owner', $owner);
    }

    /** @return array<string, mixed>|null */
    public function getPlan(): ?array
    {
        $this->getAttribute('plan');

        return $this->plan;
    }

    /** @param array<string, mixed>|null $plan */
    public function setPlan(?array $plan): void
    {
        $this->plan = $plan;
        $this->setAttribute('plan', $plan);
    }

    public function getCnameSuffix(): ?string
    {
        $this->getAttribute('cname_suffix');

        return $this->cname_suffix;
    }

    public function setCnameSuffix(?string $cnameSuffix): void
    {
        $this->cname_suffix = $cnameSuffix;
        $this->setAttribute('cname_suffix', $cnameSuffix);
    }

    public function isPaused(): bool
    {
        $this->getAttribute('paused');

        return $this->paused;
    }

    public function setPaused(bool $paused): void
    {
        $this->paused = $paused;
        $this->setAttribute('paused', $paused);
    }

    /** @return list<string> */
    public function getPermissions(): array
    {
        $this->getAttribute('permissions');

        return $this->permissions;
    }

    /** @param list<string> $permissions */
    public function setPermissions(array $permissions): void
    {
        $this->permissions = $permissions;
        $this->setAttribute('permissions', $permissions);
    }

    public function getStatus(): string
    {
        $this->getAttribute('status');

        return $this->status;
    }

    public function getKnownStatus(): ?ZoneStatus
    {
        return ZoneStatus::tryFrom($this->getStatus());
    }

    public function setStatus(ZoneStatus|string $status): void
    {
        $this->status = $status instanceof ZoneStatus ? $status->value : $status;
        $this->setAttribute('status', $this->status);
    }

    /** @return array<string, mixed>|null */
    public function getTenant(): ?array
    {
        $this->getAttribute('tenant');

        return $this->tenant;
    }

    /** @param array<string, mixed>|null $tenant */
    public function setTenant(?array $tenant): void
    {
        $this->tenant = $tenant;
        $this->setAttribute('tenant', $tenant);
    }

    /** @return array<string, mixed>|null */
    public function getTenantUnit(): ?array
    {
        $this->getAttribute('tenant_unit');

        return $this->tenant_unit;
    }

    /** @param array<string, mixed>|null $tenantUnit */
    public function setTenantUnit(?array $tenantUnit): void
    {
        $this->tenant_unit = $tenantUnit;
        $this->setAttribute('tenant_unit', $tenantUnit);
    }

    public function getType(): string
    {
        $this->getAttribute('type');

        return $this->type;
    }

    public function getKnownType(): ?ZoneType
    {
        return ZoneType::tryFrom($this->getType());
    }

    public function setType(ZoneType|string $type): void
    {
        $this->type = $type instanceof ZoneType ? $type->value : $type;
        $this->setAttribute('type', $this->type);
    }

    /** @return list<string> */
    public function getVanityNameServers(): array
    {
        $this->getAttribute('vanity_name_servers');

        return $this->vanity_name_servers;
    }

    /** @param list<string> $vanityNameServers */
    public function setVanityNameServers(array $vanityNameServers): void
    {
        $this->vanity_name_servers = $vanityNameServers;
        $this->setAttribute('vanity_name_servers', $vanityNameServers);
    }

    public function getVerificationKey(): ?string
    {
        $this->getAttribute('verification_key');

        return $this->verification_key;
    }

    public function setVerificationKey(?string $verificationKey): void
    {
        $this->verification_key = $verificationKey;
        $this->setAttribute('verification_key', $verificationKey);
    }

    /** @throws DateMalformedStringException */
    private function normalizeDate(DateTimeInterface|string|null $value): ?DateTimeImmutable
    {
        if ($value instanceof DateTimeImmutable) {
            return $value;
        }

        if ($value instanceof DateTimeInterface) {
            return DateTimeImmutable::createFromInterface($value);
        }

        return is_string($value) ? new DateTimeImmutable($value) : null;
    }
}
