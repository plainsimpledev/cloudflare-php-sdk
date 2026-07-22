<?php

declare(strict_types=1);

namespace PlainSimple\Cloudflare\Auth;

interface AuthInterface
{
    /** @return array<string, string> */
    public function getHeaders(): array;
}
