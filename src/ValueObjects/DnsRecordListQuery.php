<?php

declare(strict_types=1);

namespace PlainSimple\Cloudflare\ValueObjects;

use InvalidArgumentException;
use PlainSimple\Cloudflare\Enums\DnsRecordType;

final readonly class DnsRecordListQuery
{
    /**
     * @param array{absent?: string|null, contains?: string|null, endswith?: string|null, exact?: string|null, present?: string|null, startswith?: string|null}|null $comment
     * @param array{contains?: string|null, endswith?: string|null, exact?: string|null, startswith?: string|null}|null $content
     * @param array{contains?: string|null, endswith?: string|null, exact?: string|null, startswith?: string|null}|null $name
     * @param array{absent?: string|null, contains?: string|null, endswith?: string|null, exact?: string|null, present?: string|null, startswith?: string|null}|null $tag
     */
    public function __construct(
        public ?array $comment = null,
        public ?array $content = null,
        public ?string $direction = null,
        public ?bool $includeShadowMetadata = null,
        public ?string $match = null,
        public ?array $name = null,
        public ?string $order = null,
        public ?int $page = null,
        public ?int $perPage = null,
        public ?bool $proxied = null,
        public ?string $search = null,
        public ?string $shadowedByName = null,
        public ?string $shadowingName = null,
        public ?array $tag = null,
        public ?string $tagMatch = null,
        public DnsRecordType|string|null $type = null,
    ) {
        if ($this->page !== null && $this->page < 1) {
            throw new InvalidArgumentException('DNS record page must be at least 1.');
        }
        if ($this->perPage !== null && ($this->perPage < 1 || $this->perPage > 5000000)) {
            throw new InvalidArgumentException('DNS record per-page value must be between 1 and 5000000.');
        }
        if ($this->shadowedByName !== null && $this->includeShadowMetadata !== true) {
            throw new InvalidArgumentException(
                'Shadowed-by-name filtering requires include_shadow_metadata=true.',
            );
        }
    }

    /** @return array<string, bool|int|string> */
    public function toArray(): array
    {
        $query = [];
        $this->appendNested($query, 'comment', $this->comment);
        $this->appendNested($query, 'content', $this->content);

        $query += $this->withoutNulls([
            'direction' => $this->direction,
            'include_shadow_metadata' => $this->includeShadowMetadata,
            'match' => $this->match,
        ]);

        $this->appendNested($query, 'name', $this->name);

        $query += $this->withoutNulls([
            'order' => $this->order,
            'page' => $this->page,
            'per_page' => $this->perPage,
            'proxied' => $this->proxied,
            'search' => $this->search,
            'shadowed_by_name' => $this->shadowedByName,
            'shadowing_name' => $this->shadowingName,
        ]);

        $this->appendNested($query, 'tag', $this->tag);

        $query += $this->withoutNulls([
            'tag_match' => $this->tagMatch,
            'type' => $this->type instanceof DnsRecordType ? $this->type->value : $this->type,
        ]);

        return $query;
    }

    /**
     * @param array<string, bool|int|string> $query
     * @param array<string, string|null>|null $operators
     */
    private function appendNested(array &$query, string $field, ?array $operators): void
    {
        if ($operators === null) {
            return;
        }

        foreach ($operators as $operator => $value) {
            if ($value !== null) {
                $query[$field . '.' . $operator] = $value;
            }
        }
    }

    /**
     * @param array<string, bool|int|string|null> $values
     * @return array<string, bool|int|string>
     */
    private function withoutNulls(array $values): array
    {
        return array_filter($values, static fn (mixed $value): bool => $value !== null);
    }
}
