<?php

declare(strict_types=1);

namespace PlainSimple\Cloudflare\Entities;

use DateMalformedStringException;
use DateTimeImmutable;
use DateTimeInterface;
use InvalidArgumentException;
use PlainSimple\Cloudflare\Enums\RulesetKind;
use PlainSimple\Cloudflare\Enums\RulesetPhase;

class Ruleset extends AbstractEntity
{
    protected const CREATE_FIELDS = ['name', 'description', 'kind', 'phase', 'rules'];
    protected const PATCH_FIELDS = [];
    protected const REPLACE_FIELDS = ['description', 'rules'];

    private string $id;
    private string $name;
    private ?string $description = null;
    private string $kind;
    private string $phase;
    private string $version;
    private ?DateTimeImmutable $last_updated = null;

    /** @var list<Rule> */
    private array $rules = [];

    /**
     * @param list<Rule|array<string, mixed>> $rules
     */
    public static function forCreate(
        string $name,
        RulesetKind|string $kind,
        RulesetPhase|string $phase,
        ?string $description = null,
        array $rules = [],
    ): static {
        $ruleset = new static();
        $ruleset->setName($name);
        $ruleset->setKind($kind);
        $ruleset->setPhase($phase);
        if ($description !== null) {
            $ruleset->setDescription($description);
        }
        $ruleset->setRules($rules);

        return $ruleset;
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

    public function getKind(): string
    {
        $this->getAttribute('kind');

        return $this->kind;
    }

    public function getKnownKind(): ?RulesetKind
    {
        return RulesetKind::tryFrom($this->getKind());
    }

    public function setKind(RulesetKind|string $kind): void
    {
        $value = $kind instanceof RulesetKind ? $kind->value : $kind;
        $this->kind = $value;
        $this->setAttribute('kind', $value);
    }

    public function getPhase(): string
    {
        $this->getAttribute('phase');

        return $this->phase;
    }

    public function getKnownPhase(): ?RulesetPhase
    {
        return RulesetPhase::tryFrom($this->getPhase());
    }

    public function setPhase(RulesetPhase|string $phase): void
    {
        $value = $phase instanceof RulesetPhase ? $phase->value : $phase;
        $this->phase = $value;
        $this->setAttribute('phase', $value);
    }

    public function getVersion(): string
    {
        $this->getAttribute('version');

        return $this->version;
    }

    public function setVersion(string $version): void
    {
        $this->version = $version;
        $this->setAttribute('version', $version);
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

    public function hasRules(): bool
    {
        return $this->hasAttribute('rules');
    }

    /** @return list<Rule>|null */
    public function getRules(): ?array
    {
        if (!$this->hasRules()) {
            return null;
        }

        return $this->rules;
    }

    /** @param list<Rule|array<string, mixed>> $rules */
    public function setRules(array $rules): void
    {
        $entities = [];
        foreach ($rules as $rule) {
            if ($rule instanceof Rule) {
                $entities[] = $rule;

                continue;
            }
            if (is_array($rule)) {
                $entities[] = Rule::makeFromCloudflareData($rule);

                continue;
            }

            throw new InvalidArgumentException('Rules must contain Rule entities or rule data arrays.');
        }

        $this->rules = $entities;
        $this->setAttribute('rules', $entities);
    }
}
