<?php

declare(strict_types=1);

namespace PlainSimple\Cloudflare\Entities;

use DateMalformedStringException;
use DateTimeImmutable;
use DateTimeInterface;
use PlainSimple\Cloudflare\Enums\RuleAction;
use PlainSimple\Cloudflare\ValueObjects\RulePosition;

class Rule extends AbstractEntity
{
    protected const CREATE_FIELDS = [
        'ref',
        'description',
        'action',
        'expression',
        'enabled',
        'action_parameters',
        'logging',
        'ratelimit',
        'exposed_credential_check',
    ];
    protected const PATCH_FIELDS = self::CREATE_FIELDS;
    protected const REPLACE_FIELDS = self::CREATE_FIELDS;

    private ?string $id = null;
    private ?string $version = null;
    private ?string $ref = null;
    private ?string $description = null;
    private ?string $action = null;
    private ?string $expression = null;
    private ?bool $enabled = null;
    private ?DateTimeImmutable $last_updated = null;

    /** @var array<string, mixed>|null */
    private ?array $action_parameters = null;

    /** @var list<string>|null */
    private ?array $categories = null;

    /** @var array<string, mixed>|null */
    private ?array $logging = null;

    /** @var array<string, mixed>|null */
    private ?array $ratelimit = null;

    /** @var array<string, mixed>|null */
    private ?array $exposed_credential_check = null;

    /** @var array{before: string}|array{after: string}|array{index: int}|null */
    private ?array $position = null;

    public function getId(): ?string
    {
        $this->getAttribute('id');

        return $this->id;
    }

    public function setId(?string $id): void
    {
        $this->id = $id;
        $this->setAttribute('id', $id);
    }

    public function getVersion(): ?string
    {
        $this->getAttribute('version');

        return $this->version;
    }

    public function setVersion(?string $version): void
    {
        $this->version = $version;
        $this->setAttribute('version', $version);
    }

    public function getRef(): ?string
    {
        $this->getAttribute('ref');

        return $this->ref;
    }

    public function setRef(?string $ref): void
    {
        $this->ref = $ref;
        $this->setAttribute('ref', $ref);
    }

    public function getDescription(): ?string
    {
        $this->getAttribute('description');

        return $this->description;
    }

    public function setDescription(?string $description): void
    {
        $this->description = $description;
        $this->setAttribute('description', $description);
    }

    public function getAction(): ?string
    {
        $this->getAttribute('action');

        return $this->action;
    }

    public function getKnownAction(): ?RuleAction
    {
        return $this->action === null ? null : RuleAction::tryFrom($this->action);
    }

    public function setAction(RuleAction|string|null $action): void
    {
        $value = $action instanceof RuleAction ? $action->value : $action;
        $this->action = $value;
        $this->setAttribute('action', $value);
    }

    public function getExpression(): ?string
    {
        $this->getAttribute('expression');

        return $this->expression;
    }

    public function setExpression(?string $expression): void
    {
        $this->expression = $expression;
        $this->setAttribute('expression', $expression);
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

    public function getLastUpdated(): ?DateTimeImmutable
    {
        $this->getAttribute('last_updated');

        return $this->last_updated;
    }

    /** @throws DateMalformedStringException */
    public function setLastUpdated(DateTimeInterface|string|null $lastUpdated): void
    {
        if ($lastUpdated instanceof DateTimeInterface && !$lastUpdated instanceof DateTimeImmutable) {
            $lastUpdated = DateTimeImmutable::createFromInterface($lastUpdated);
        } elseif (is_string($lastUpdated)) {
            $lastUpdated = new DateTimeImmutable($lastUpdated);
        }

        $this->last_updated = $lastUpdated;
        $this->setAttribute('last_updated', $lastUpdated);
    }

    /** @return array<string, mixed>|null */
    public function getActionParameters(): ?array
    {
        $this->getAttribute('action_parameters');

        return $this->action_parameters;
    }

    /** @param array<string, mixed>|null $actionParameters */
    public function setActionParameters(?array $actionParameters): void
    {
        $this->action_parameters = $actionParameters;
        $this->setAttribute('action_parameters', $actionParameters);
    }

    /** @return list<string>|null */
    public function getCategories(): ?array
    {
        $this->getAttribute('categories');

        return $this->categories;
    }

    /** @param list<string>|null $categories */
    public function setCategories(?array $categories): void
    {
        $this->categories = $categories;
        $this->setAttribute('categories', $categories);
    }

    /** @return array<string, mixed>|null */
    public function getLogging(): ?array
    {
        $this->getAttribute('logging');

        return $this->logging;
    }

    /** @param array<string, mixed>|null $logging */
    public function setLogging(?array $logging): void
    {
        $this->logging = $logging;
        $this->setAttribute('logging', $logging);
    }

    /** @return array<string, mixed>|null */
    public function getRatelimit(): ?array
    {
        $this->getAttribute('ratelimit');

        return $this->ratelimit;
    }

    /** @param array<string, mixed>|null $ratelimit */
    public function setRatelimit(?array $ratelimit): void
    {
        $this->ratelimit = $ratelimit;
        $this->setAttribute('ratelimit', $ratelimit);
    }

    /** @return array<string, mixed>|null */
    public function getExposedCredentialCheck(): ?array
    {
        $this->getAttribute('exposed_credential_check');

        return $this->exposed_credential_check;
    }

    /** @param array<string, mixed>|null $exposedCredentialCheck */
    public function setExposedCredentialCheck(?array $exposedCredentialCheck): void
    {
        $this->exposed_credential_check = $exposedCredentialCheck;
        $this->setAttribute('exposed_credential_check', $exposedCredentialCheck);
    }

    public function getPosition(): ?RulePosition
    {
        $this->getAttribute('position');

        return $this->position === null ? null : RulePosition::fromArray($this->position);
    }

    /** @param RulePosition|array<string, mixed> $position */
    public function setPosition(RulePosition|array $position): void
    {
        if (is_array($position)) {
            $position = RulePosition::fromArray($position);
        }

        $value = $position->toArray();
        $this->position = $value;
        $this->setAttribute('position', $value);
    }

    /** @return array<string, mixed> */
    public function toCreatePayload(): array
    {
        return $this->withoutNullWrites(parent::toCreatePayload());
    }

    /**
     * Complete set of present writable definition fields for rule operations.
     *
     * @return array<string, mixed>
     */
    public function toOperationPayload(): array
    {
        return $this->withoutNullWrites(parent::toReplacePayload());
    }

    /** @return array<string, mixed> */
    public function toReplacePayload(): array
    {
        return $this->toOperationPayload();
    }

    /**
     * @param array<string, mixed> $payload
     * @return array<string, mixed>
     */
    private function withoutNullWrites(array $payload): array
    {
        return array_filter($payload, static fn (mixed $value): bool => $value !== null);
    }
}
