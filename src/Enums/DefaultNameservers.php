<?php

declare(strict_types=1);

namespace PlainSimple\Cloudflare\Enums;

enum DefaultNameservers: string
{
    case CloudflareStandard = 'cloudflare.standard';
    case CustomAccount = 'custom.account';
    case CustomTenant = 'custom.tenant';
}
