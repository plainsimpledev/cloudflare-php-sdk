<?php

declare(strict_types=1);

namespace PlainSimple\Cloudflare\Auth;

class ApiToken implements AuthInterface
{
    public function __construct(private string $apiToken)
    {
    }

    /** @return array<string, string> */
    public function getHeaders(): array
    {
        return [
            'Authorization' => 'Bearer ' . $this->apiToken,
        ];
    }
}
