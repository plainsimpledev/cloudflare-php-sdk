<?php

declare(strict_types=1);

namespace PlainSimple\Cloudflare\ValueObjects;

use InvalidArgumentException;
use PlainSimple\Cloudflare\Entities\DnsRecord;
use PlainSimple\Cloudflare\Utilities\PathSegment;

final readonly class DnsScanReview
{
    /**
     * @param list<DnsRecord> $accepts
     * @param list<string> $rejects
     */
    public function __construct(
        public array $accepts = [],
        public array $rejects = [],
    ) {
    }

    /** @return array<string, list<array<string, mixed>>> */
    public function toArray(): array
    {
        $payload = [];

        if ($this->accepts !== []) {
            $payload['accepts'] = array_values(array_map(
                static fn (DnsRecord $record): array => $record->toCreatePayload(),
                $this->accepts,
            ));
        }

        if ($this->rejects !== []) {
            $payload['rejects'] = array_values(array_map(
                fn (string $id): array => ['id' => $this->bodyId($id)],
                $this->rejects,
            ));
        }

        return $payload;
    }

    private function bodyId(string $id): string
    {
        $encoded = PathSegment::encode($id, 'Rejected DNS record id');
        if ($encoded !== $id || $encoded === '.' || $encoded === '..') {
            throw new InvalidArgumentException('Rejected DNS record id contains unsafe characters.');
        }

        return $id;
    }
}
