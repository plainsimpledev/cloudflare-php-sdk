<?php

declare(strict_types=1);

namespace PlainSimple\Cloudflare\Auth;

class ApiKeyAndEmail implements Auth
{
    public function __construct(
        private string $email,
        private string $apiKey
    ) {
    }

    public function getHeaders(): array
    {
        return [
            'X-Auth-Email' => $this->email,
            'X-Auth-Key' => $this->apiKey,
        ];
    }
}
