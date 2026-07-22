<?php

declare(strict_types=1);

namespace PlainSimple\Cloudflare\Utilities;

use InvalidArgumentException;

final class PathSegment
{
    public static function encode(string $value, string $label = 'Path segment'): string
    {
        if ($value === '' || trim($value) === '') {
            throw new InvalidArgumentException($label . ' must not be empty.');
        }

        return rawurlencode($value);
    }
}
