<?php

namespace PlainSimple\Cloudflare\Entities;

use DateMalformedStringException;
use DateTime;

class Account extends AbstractEntity
{
    /**
     * @var string identifier
     */
    private string $id;

    /**
     * @var string account name
     */
    private string $name;

    /**
     * @var DateTime timestamp for the creation of the account
     */
    private DateTime $created_on;

    private AccountSettings $settings;

    public function getId(): string
    {
        return $this->id;
    }

    public function setId(string $id): void
    {
        $this->id = $id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): void
    {
        $this->name = $name;
    }

    public function getCreatedOn(): DateTime
    {
        return $this->created_on;
    }

    /**
     * @throws DateMalformedStringException
     */
    public function setCreatedOn(mixed $value): void
    {
        if (!($value instanceof DateTime)) {
            $value = new DateTime($value);
        }
        $this->created_on = $value;
    }

    public function getSettings(): AccountSettings
    {
        return $this->settings;
    }

    public function setSettings(mixed $value): void
    {
        if (!($value instanceof AccountSettings)) {
            $value = AccountSettings::makeFromCloudflareData($value);
        }
        $this->settings = $value;
    }
}