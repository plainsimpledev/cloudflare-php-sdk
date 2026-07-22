<?php

declare(strict_types=1);

namespace PlainSimple\Cloudflare\Auth;

class ApiKeyAndEmail implements AuthInterface
{
    public function __construct(
        private string $email,
        private string $apiKey
    ) {
    }

    /** @return array<string, string> */
    public function getHeaders(): array
    {
        return [
            'X-Auth-Email' => $this->email,
            'X-Auth-Key' => $this->apiKey,
        ];
    }
}
