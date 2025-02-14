<?php

declare(strict_types=1);

namespace PlainSimple\Cloudflare\Auth; 

readonly class Unauthorized implements Auth
{
    public function getHeaders(): array
    {
        return [];
    }
}
