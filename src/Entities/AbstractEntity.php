<?php

namespace PlainSimple\Cloudflare\Entities;

use PlainSimple\Cloudflare\Exceptions\AttributeDoesNotExistsException;
use PlainSimple\Cloudflare\Utilities\AttributeNamer;
use Throwable;

abstract class AbstractEntity
{
    /**
     * @throws AttributeDoesNotExistsException
     */
    public function __get(string $name)
    {
        $getterMethodName = AttributeNamer::getGetterName($name);

        if (method_exists($this, $getterMethodName)) {
            return $this->$getterMethodName();
        }

        if (property_exists($this, $name)) {
            return $this->$name;
        }

        throw new AttributeDoesNotExistsException($name, self::class);
    }

    /**
     * @throws AttributeDoesNotExistsException
     */
    public function __set(string $name, $value): void
    {
        $setterMethodName = AttributeNamer::getSetterName($name);
        var_dump($name,$setterMethodName);
        if ($name == 'settings') {
            var_dump($name,$setterMethodName);exit;
        }
        if (method_exists($this, $setterMethodName)) {
            $this->$setterMethodName($value);
            return;
        }

        if (property_exists($this, $name)) {
            $this->$name = $value;
            return;
        }

        throw new AttributeDoesNotExistsException($name, self::class);
    }

    public function __isset(string $name): bool
    {
        try {
            return $this->__get($name) !== null;
        } catch (Throwable $exception) {
            return false;
        }
    }

    public static function makeFromCloudflareData(array $cloudflareData): static
    {
        $entity = new static();
        foreach ($cloudflareData as $attribute => $value) {
            $entity->$attribute = $value;
        }
        return $entity;
    }
}