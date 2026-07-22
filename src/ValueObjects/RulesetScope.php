<?php

declare(strict_types=1);

namespace PlainSimple\Cloudflare\ValueObjects;

use PlainSimple\Cloudflare\Utilities\PathSegment;

final readonly class RulesetScope
{
    private string $resource;
    private string $id;

    private function __construct(
        string $resource,
        string $id,
    ) {
        $this->resource = $resource;
        $this->id = PathSegment::encode($id, 'Ruleset scope ID');
    }

    public static function zone(string $id): self
    {
        return new self('zones', $id);
    }

    public static function account(string $id): self
    {
        return new self('accounts', $id);
    }

    public function path(): string
    {
        return $this->resource . '/' . $this->id;
    }

    public function getPath(): string
    {
        return $this->path();
    }

    public function __toString(): string
    {
        return $this->path();
    }
}
