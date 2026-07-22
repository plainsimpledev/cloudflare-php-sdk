<?php

declare(strict_types=1);

namespace PlainSimple\Cloudflare\ValueObjects;

final readonly class RulesetListQuery
{
    public function __construct(
        private ?string $cursor = null,
        private ?int $perPage = null,
    ) {
    }

    public function getCursor(): ?string
    {
        return $this->cursor;
    }

    public function getPerPage(): ?int
    {
        return $this->perPage;
    }

    /** @return array{cursor?: string, per_page?: int} */
    public function toArray(): array
    {
        $query = [];
        if ($this->cursor !== null) {
            $query['cursor'] = $this->cursor;
        }
        if ($this->perPage !== null) {
            $query['per_page'] = $this->perPage;
        }

        return $query;
    }
}
