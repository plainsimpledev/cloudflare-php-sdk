<?php

declare(strict_types=1);

namespace PlainSimple\Cloudflare\ValueObjects;

final readonly class DnsImport
{
    public function __construct(
        public string $contents,
        public string $filename = 'dns_records.txt',
        public ?bool $proxied = null,
    ) {
    }

    /** @return list<array<string, mixed>> */
    public function toMultipart(): array
    {
        $multipart = [[
            'name' => 'file',
            'contents' => $this->contents,
            'filename' => $this->filename,
        ]];

        if ($this->proxied !== null) {
            $multipart[] = [
                'name' => 'proxied',
                'contents' => $this->proxied ? 'true' : 'false',
            ];
        }

        return $multipart;
    }
}
