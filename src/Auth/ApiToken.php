<?php

declare(strict_types=1);

namespace PlainSimple\Cloudflare\Auth;

class ApiToken implements Auth
{
    public function __construct(private string $apiToken)
    {
    }

    public function getHeaders(): array
    {
        return [
            'Authorization' => 'Bearer ' . $this->apiToken,
        ];
    }
}
