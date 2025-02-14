<?php

namespace PlainSimple\Cloudflare\Entities;

use PlainSimple\Cloudflare\Exceptions\AttributeDoesNotExists;
use PlainSimple\Cloudflare\Utilities\AttributeNamer;
use Throwable;

abstract class Entity
{
    /**
     * @throws AttributeDoesNotExists
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

        throw new AttributeDoesNotExists($name, self::class);
    }

    /**
     * @throws AttributeDoesNotExists
     */
    public function __set(string $name, $value): void
    {
        $setterMethodName = AttributeNamer::getSetterName($name);
        if (method_exists($this, $setterMethodName)) {
            $this->$setterMethodName($value);
            return;
        }

        if (property_exists($this, $name)) {
            $this->$name = $name;
            return;
        }

        throw new AttributeDoesNotExists($name, self::class);
    }

    public function __isset(string $name): bool
    {
        try {
            return $this->__get($name) !== null;
        } catch (Throwable $exception) {
            return false;
        }
    }

    public static function fromCloudflareData(array $cloudflareData): static
    {
        $entity = new static();
        foreach ($cloudflareData as $attribute => $value) {
            $entity->$attribute = $value;
        }
        return $entity;
    }
}