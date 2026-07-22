<?php

declare(strict_types=1);

namespace PlainSimple\Cloudflare\Enums;

enum AccountType: string
{
    case Standard = 'standard';
    case Enterprise = 'enterprise';
}
