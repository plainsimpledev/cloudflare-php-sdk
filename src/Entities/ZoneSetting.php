<?php

declare(strict_types=1);

namespace PlainSimple\Cloudflare\Entities;

use DateMalformedStringException;
use DateTimeImmutable;
use DateTimeInterface;

class ZoneSetting extends AbstractEntity
{
    protected const PATCH_FIELDS = ['value', 'enabled'];
    protected const REPLACE_FIELDS = self::PATCH_FIELDS;

    private string $id;
    private mixed $value = null;
    private ?bool $enabled = null;
    private ?bool $editable = null;
    private ?DateTimeImmutable $modified_on = null;
    private int|false|null $time_remaining = null;

    public static function forUpdate(string $id, mixed $value): static
    {
        $setting = new static();
        $setting->setId($id);
        $setting->markClean();
        $setting->setValue($value);

        return $setting;
    }

    public static function forEnabledUpdate(string $id, bool $enabled): static
    {
        $setting = new static();
        $setting->setId($id);
        $setting->markClean();
        $setting->setEnabled($enabled);

        return $setting;
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

    public function getValue(): mixed
    {
        $this->getAttribute('value');

        return $this->value;
    }

    public function setValue(mixed $value): void
    {
        $this->value = $value;
        $this->setAttribute('value', $value);
    }

    public function isEnabled(): ?bool
    {
        $this->getAttribute('enabled');

        return $this->enabled;
    }

    public function setEnabled(?bool $enabled): void
    {
        $this->enabled = $enabled;
        $this->setAttribute('enabled', $enabled);
    }

    public function isEditable(): ?bool
    {
        $this->getAttribute('editable');

        return $this->editable;
    }

    public function setEditable(?bool $editable): void
    {
        $this->editable = $editable;
        $this->setAttribute('editable', $editable);
    }

    public function getModifiedOn(): ?DateTimeImmutable
    {
        $this->getAttribute('modified_on');

        return $this->modified_on;
    }

    /** @throws DateMalformedStringException */
    public function setModifiedOn(DateTimeInterface|string|null $modified_on): void
    {
        if ($modified_on instanceof DateTimeInterface && !$modified_on instanceof DateTimeImmutable) {
            $modified_on = DateTimeImmutable::createFromInterface($modified_on);
        } elseif (is_string($modified_on)) {
            $modified_on = new DateTimeImmutable($modified_on);
        }

        $this->modified_on = $modified_on;
        $this->setAttribute('modified_on', $modified_on);
    }

    public function getTimeRemaining(): int|false|null
    {
        $this->getAttribute('time_remaining');

        return $this->time_remaining;
    }

    public function setTimeRemaining(int|false|null $time_remaining): void
    {
        $this->time_remaining = $time_remaining;
        $this->setAttribute('time_remaining', $time_remaining);
    }
}
