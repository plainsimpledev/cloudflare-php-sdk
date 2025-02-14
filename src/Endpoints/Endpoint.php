<?php

declare(strict_types=1);

namespace PlainSimple\Cloudflare\Endpoints;

use PlainSimple\Cloudflare\Adapters\Adapter;

abstract readonly class Endpoint
{
    public function __construct(protected Adapter $adapter)
    {
    }
}