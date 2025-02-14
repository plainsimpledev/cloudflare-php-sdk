<?php

namespace PlainSimple\Cloudflare\Entities;

use DateMalformedStringException;
use DateTime;

class Account extends Entity
{
    /**
     * @var string identifier
     */
    public string $id;

    /**
     * @var string account name
     */
    public string $name;

    /**
     * @var DateTime timestamp for the creation of the account
     */
    public DateTime $created_on;

    public AccountSettings $settings;

    public function setSettings(mixed $value): void
    {
        if (!($value instanceof AccountSettings)) {
            $value = AccountSettings::fromCloudflareData($value);
        }
        $this->settings = $value;
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
}