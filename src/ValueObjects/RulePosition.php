<?php

declare(strict_types=1);

namespace PlainSimple\Cloudflare\ValueObjects;

use InvalidArgumentException;

final readonly class RulePosition
{
    public function __construct(
        private ?string $before = null,
        private ?string $after = null,
        private ?int $index = null,
    ) {
        $defined = (int) ($before !== null) + (int) ($after !== null) + (int) ($index !== null);
        if ($defined !== 1) {
            throw new InvalidArgumentException('Rule position requires exactly one of before, after, or index.');
        }
        if ($index !== null && $index < 1) {
            throw new InvalidArgumentException('Rule position index must be positive.');
        }
    }

    public static function before(string $ruleId): self
    {
        return new self(before: $ruleId);
    }

    public static function after(string $ruleId): self
    {
        return new self(after: $ruleId);
    }

    public static function index(int $index): self
    {
        return new self(index: $index);
    }

    /** @param array<string, mixed> $position */
    public static function fromArray(array $position): self
    {
        if (count($position) !== 1) {
            throw new InvalidArgumentException('Rule position requires exactly one field.');
        }
        if (array_key_exists('before', $position) && is_string($position['before'])) {
            return self::before($position['before']);
        }
        if (array_key_exists('after', $position) && is_string($position['after'])) {
            return self::after($position['after']);
        }
        if (array_key_exists('index', $position) && is_int($position['index'])) {
            return self::index($position['index']);
        }

        throw new InvalidArgumentException('Invalid rule position.');
    }

    public function getBefore(): ?string
    {
        return $this->before;
    }

    public function getAfter(): ?string
    {
        return $this->after;
    }

    public function getIndex(): ?int
    {
        return $this->index;
    }

    /** @return array{before: string}|array{after: string}|array{index: int} */
    public function toArray(): array
    {
        if ($this->before !== null) {
            return ['before' => $this->before];
        }
        if ($this->after !== null) {
            return ['after' => $this->after];
        }

        return ['index' => $this->index];
    }
}
