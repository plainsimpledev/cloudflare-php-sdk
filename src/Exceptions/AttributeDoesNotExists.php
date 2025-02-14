<?php

declare(strict_types=1);

namespace PlainSimple\Cloudflare\Exceptions;

use Exception;

class AttributeDoesNotExists extends Exception
{
    public function __construct(string $attributeName, string $className)
    {
        $message = sprintf(
            "Attribute '%s' does not exist in class '%s'",
            $attributeName,
            $className
        );

        parent::__construct($message, 404);
    }
}
