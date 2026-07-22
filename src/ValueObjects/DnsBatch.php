<?php

declare(strict_types=1);

namespace PlainSimple\Cloudflare\ValueObjects;

use InvalidArgumentException;
use PlainSimple\Cloudflare\Entities\DnsRecord;
use PlainSimple\Cloudflare\Utilities\PathSegment;

final readonly class DnsBatch
{
    /**
     * @param list<DnsRecord|string> $deletes
     * @param list<DnsRecord> $patches
     * @param list<DnsRecord> $puts
     * @param list<DnsRecord> $posts
     */
    public function __construct(
        public array $deletes = [],
        public array $patches = [],
        public array $puts = [],
        public array $posts = [],
    ) {
        if ($this->deletes === [] && $this->patches === [] && $this->puts === [] && $this->posts === []) {
            throw new InvalidArgumentException('DNS batch requires at least one operation.');
        }
    }

    /** @return array<string, list<array<string, mixed>>> */
    public function toArray(): array
    {
        $payload = [];

        if ($this->deletes !== []) {
            $payload['deletes'] = array_values(array_map(
                fn (DnsRecord|string $record): array => [
                    'id' => $record instanceof DnsRecord
                        ? $this->entityId($record)
                        : $this->bodyId($record),
                ],
                $this->deletes,
            ));
        }

        if ($this->patches !== []) {
            $payload['patches'] = array_values(array_map(
                fn (DnsRecord $record): array => [
                    'id' => $this->entityId($record),
                    ...$record->toPatchPayload(),
                ],
                $this->patches,
            ));
        }

        if ($this->puts !== []) {
            $payload['puts'] = array_values(array_map(
                fn (DnsRecord $record): array => [
                    'id' => $this->entityId($record),
                    ...$record->toReplacePayload(),
                ],
                $this->puts,
            ));
        }

        if ($this->posts !== []) {
            $payload['posts'] = array_values(array_map(
                static fn (DnsRecord $record): array => $record->toCreatePayload(),
                $this->posts,
            ));
        }

        return $payload;
    }

    private function entityId(DnsRecord $record): string
    {
        if (!$record->hasAttribute('id')) {
            throw new InvalidArgumentException('DNS batch update requires a record id.');
        }

        return $this->bodyId($record->getId());
    }

    private function bodyId(string $id): string
    {
        $encoded = PathSegment::encode($id, 'DNS batch record id');
        if ($encoded !== $id || $encoded === '.' || $encoded === '..') {
            throw new InvalidArgumentException('DNS batch record id contains unsafe characters.');
        }

        return $id;
    }
}
