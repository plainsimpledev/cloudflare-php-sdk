<?php

declare(strict_types=1);

namespace PlainSimple\Cloudflare\Entities;

use DateMalformedStringException;
use DateTimeImmutable;
use DateTimeInterface;
use PlainSimple\Cloudflare\Enums\AccountType;

class Account extends AbstractEntity
{
    protected const CREATE_FIELDS = ['name', 'type'];
    protected const PATCH_FIELDS = ['name', 'settings'];
    protected const REPLACE_FIELDS = ['id', 'name', 'type', 'settings'];

    /** @var string identifier */
    private string $id;

    /** @var string account name */
    private string $name;

    private AccountType $type;

    /** @var DateTimeImmutable|null timestamp for the creation of the account */
    private ?DateTimeImmutable $created_on = null;

    private ?AccountManagedBy $managed_by = null;

    private ?AccountSettings $settings = null;

    public static function forCreate(
        string $name,
        AccountType|string $type = AccountType::Standard,
    ): self {
        $account = new self();
        $account->setName($name);
        $account->setType($type);

        return $account;
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

    public function getType(): AccountType
    {
        $this->getAttribute('type');

        return $this->type;
    }

    public function setType(AccountType|string $type): void
    {
        if (!$type instanceof AccountType) {
            $type = AccountType::from($type);
        }

        $this->type = $type;
        $this->setAttribute('type', $type);
    }

    public function getCreatedOn(): ?DateTimeImmutable
    {
        $this->getAttribute('created_on');

        return $this->created_on;
    }

    /** @throws DateMalformedStringException */
    public function setCreatedOn(DateTimeInterface|string|null $value): void
    {
        if ($value instanceof DateTimeInterface && !$value instanceof DateTimeImmutable) {
            $value = DateTimeImmutable::createFromInterface($value);
        } elseif (is_string($value)) {
            $value = new DateTimeImmutable($value);
        }

        $this->created_on = $value;
        $this->setAttribute('created_on', $value);
    }

    public function getManagedBy(): ?AccountManagedBy
    {
        $this->getAttribute('managed_by');

        return $this->managed_by;
    }

    /** @param AccountManagedBy|array<string, mixed>|null $value */
    public function setManagedBy(AccountManagedBy|array|null $value): void
    {
        if (is_array($value)) {
            $value = AccountManagedBy::makeFromCloudflareData($value);
        }

        $this->managed_by = $value;
        $this->setAttribute('managed_by', $value);
    }

    public function getSettings(): ?AccountSettings
    {
        $this->getAttribute('settings');

        return $this->settings;
    }

    /** @param AccountSettings|array<string, mixed>|null $value */
    public function setSettings(AccountSettings|array|null $value): void
    {
        if (is_array($value)) {
            $value = AccountSettings::makeFromCloudflareData($value);
        }

        $this->settings = $value;
        $this->setAttribute('settings', $value);
    }
}
