<?php

declare(strict_types=1);

namespace PlainSimple\Cloudflare\Auth;

interface Auth
{
    public function getHeaders(): array;
}
