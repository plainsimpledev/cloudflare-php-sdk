<?php

declare(strict_types=1);

namespace PlainSimple\Cloudflare\Enums;

enum DnsRecordType: string
{
    case A = 'A';
    case AAAA = 'AAAA';
    case CAA = 'CAA';
    case CERT = 'CERT';
    case CNAME = 'CNAME';
    case DNSKEY = 'DNSKEY';
    case DS = 'DS';
    case HTTPS = 'HTTPS';
    case LOC = 'LOC';
    case MX = 'MX';
    case NAPTR = 'NAPTR';
    case NS = 'NS';
    case OPENPGPKEY = 'OPENPGPKEY';
    case PTR = 'PTR';
    case SMIMEA = 'SMIMEA';
    case SRV = 'SRV';
    case SSHFP = 'SSHFP';
    case SVCB = 'SVCB';
    case TLSA = 'TLSA';
    case TXT = 'TXT';
    case URI = 'URI';

    public static function fromCloudflare(string $value): self|string
    {
        return self::tryFrom($value) ?? $value;
    }
}
