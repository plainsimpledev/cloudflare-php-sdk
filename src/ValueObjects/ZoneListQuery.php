<?php

declare(strict_types=1);

namespace PlainSimple\Cloudflare\ValueObjects;

use InvalidArgumentException;
use PlainSimple\Cloudflare\Enums\ZoneStatus;
use PlainSimple\Cloudflare\Enums\ZoneType;

final class ZoneListQuery
{
    /**
     * @param list<ZoneType|string>|null $types
     */
    public function __construct(
        private readonly ?string $accountId = null,
        private readonly ?string $accountName = null,
        private readonly ?string $name = null,
        private readonly ?string $direction = null,
        private readonly ?string $match = null,
        private readonly ?string $order = null,
        private readonly ?int $page = null,
        private readonly ?int $perPage = null,
        private readonly ZoneStatus|string|null $status = null,
        private readonly ?array $types = null,
    ) {
        if ($page !== null && $page < 1) {
            throw new InvalidArgumentException('Zone list page must be at least 1.');
        }

        if ($perPage !== null && ($perPage < 5 || $perPage > 50)) {
            throw new InvalidArgumentException('Zone list per_page must be between 5 and 50.');
        }

        if ($direction !== null && !in_array($direction, ['asc', 'desc'], true)) {
            throw new InvalidArgumentException('Zone list direction must be asc or desc.');
        }

        if ($match !== null && !in_array($match, ['any', 'all'], true)) {
            throw new InvalidArgumentException('Zone list match must be any or all.');
        }

        foreach ($types ?? [] as $type) {
            if (is_string($type) && trim($type) === '') {
                throw new InvalidArgumentException('Zone list types must contain non-empty values.');
            }
        }
    }

    /** @return array<string, int|string> */
    public function toArray(): array
    {
        $query = [
            'account.id' => $this->accountId,
            'account.name' => $this->accountName,
            'name' => $this->name,
            'direction' => $this->direction,
            'match' => $this->match,
            'order' => $this->order,
            'page' => $this->page,
            'per_page' => $this->perPage,
            'status' => $this->status instanceof ZoneStatus ? $this->status->value : $this->status,
            'type' => $this->types === null || $this->types === [] ? null : implode(',', array_map(
                static fn (ZoneType|string $type): string => $type instanceof ZoneType ? $type->value : $type,
                $this->types,
            )),
        ];

        return array_filter($query, static fn (mixed $value): bool => $value !== null);
    }
}
