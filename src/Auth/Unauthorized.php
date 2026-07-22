<?php

declare(strict_types=1);

namespace PlainSimple\Cloudflare\Auth;

class Unauthorized implements AuthInterface
{
    /** @return array<string, string> */
    public function getHeaders(): array
    {
        return [];
    }
}
