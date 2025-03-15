<?php

declare(strict_types=1);

namespace PlainSimple\Cloudflare\Auth;

interface AuthInterface
{
    public function getHeaders(): array;
}
