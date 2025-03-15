<?php

declare(strict_types=1);

namespace PlainSimple\Cloudflare\Auth; 

class Unauthorized implements AuthInterface
{
    public function getHeaders(): array
    {
        return [];
    }
}
