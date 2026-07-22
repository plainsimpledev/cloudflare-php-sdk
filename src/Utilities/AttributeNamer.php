<?php

declare(strict_types=1);

namespace PlainSimple\Cloudflare\Utilities;

final class AttributeNamer
{
    /**
     * Convert snake_case to camelCase for getter method names
     *
     * @param string $attributeName Snake case attribute name
     * @return string Getter method name in camelCase
     */
    public static function getGetterName(string $attributeName): string
    {
        return 'get' . str_replace('_', '', ucwords($attributeName, '_'));
    }

    /**
     * Convert snake_case to camelCase for boolean getter method names
     *
     * @param string $attributeName Snake case attribute name
     * @return string Boolean getter method name in camelCase
     */
    public static function getBooleanGetterName(string $attributeName): string
    {
        return 'is' . str_replace('_', '', ucwords($attributeName, '_'));
    }

    /**
     * Convert snake_case to camelCase for setter method names
     *
     * @param string $attributeName Snake case attribute name
     * @return string Setter method name in camelCase
     */
    public static function getSetterName(string $attributeName): string
    {
        return 'set' . str_replace('_', '', ucwords($attributeName, '_'));
    }
}
