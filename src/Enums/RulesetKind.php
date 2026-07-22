<?php

declare(strict_types=1);

namespace PlainSimple\Cloudflare\Enums;

enum RulesetKind: string
{
    case Managed = 'managed';
    case Custom = 'custom';
    case Root = 'root';
    case Zone = 'zone';
}
