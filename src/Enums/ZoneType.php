<?php

declare(strict_types=1);

namespace PlainSimple\Cloudflare\Enums;

enum ZoneType: string
{
    case Full = 'full';
    case Partial = 'partial';
    case Secondary = 'secondary';
    case Internal = 'internal';
}
