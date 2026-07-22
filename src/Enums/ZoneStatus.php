<?php

declare(strict_types=1);

namespace PlainSimple\Cloudflare\Enums;

enum ZoneStatus: string
{
    case Initializing = 'initializing';
    case Pending = 'pending';
    case Active = 'active';
    case Moved = 'moved';
}
