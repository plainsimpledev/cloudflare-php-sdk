<?php

declare(strict_types=1);

namespace PlainSimple\Cloudflare\Traits;

use BackedEnum;
use DateTimeInterface;
use PlainSimple\Cloudflare\Entities\AbstractEntity;
use PlainSimple\Cloudflare\Exceptions\AttributeDoesNotExistsException;
use PlainSimple\Cloudflare\Utilities\AttributeNamer;
use ReflectionClass;
use ReflectionMethod;
use ReflectionProperty;

/**
 * @mixin AbstractEntity
 */
trait EntityTrait
{
    /** @var array<string, mixed> */
    private array $entityAttributes = [];

    /** @var array<string, true> */
    private array $entityPresentFields = [];

    /** @var array<string, true> */
    private array $entityDirtyFields = [];

    /** @var array<string, mixed> */
    private array $entityAdditionalAttributes = [];

    /**
     * @throws AttributeDoesNotExistsException
     */
    public function __get(string $name): mixed
    {
        if (array_key_exists($name, $this->entityAttributes)) {
            return $this->entityAttributes[$name];
        }

        if (array_key_exists($name, $this->entityAdditionalAttributes)) {
            return $this->entityAdditionalAttributes[$name];
        }

        foreach ($this->getGetterMethodNames($name) as $getterMethodName) {
            if ($this->canDispatchMethod($getterMethodName, 0)) {
                return $this->$getterMethodName();
            }
        }

        throw new AttributeDoesNotExistsException($name, static::class);
    }

    /**
     * @throws AttributeDoesNotExistsException
     */
    public function __set(string $name, mixed $value): void
    {
        $setterMethodName = AttributeNamer::getSetterName($name);
        if ($this->canDispatchMethod($setterMethodName, 1)) {
            $this->$setterMethodName($value);

            return;
        }

        if ($this->isEntityProperty($name)) {
            $this->setAttribute($name, $value);

            return;
        }

        throw new AttributeDoesNotExistsException($name, static::class);
    }

    public function __isset(string $name): bool
    {
        try {
            return $this->__get($name) !== null;
        } catch (AttributeDoesNotExistsException) {
            return false;
        }
    }

    /** @param array<string, mixed> $cloudflareData */
    public static function makeFromCloudflareData(array $cloudflareData): static
    {
        $entity = (new ReflectionClass(static::class))->newInstance();

        foreach ($cloudflareData as $attribute => $value) {
            $attribute = (string) $attribute;
            $setterMethodName = AttributeNamer::getSetterName($attribute);

            if ($entity->canDispatchMethod($setterMethodName, 1)) {
                $entity->$setterMethodName($value);
            } elseif ($entity->isEntityProperty($attribute)) {
                $entity->setAttribute($attribute, $value);
            } else {
                $entity->entityAdditionalAttributes[$attribute] = $value;
                $entity->entityPresentFields[$attribute] = true;
            }
        }

        $entity->markClean();

        return $entity;
    }

    public function hasAttribute(string $name): bool
    {
        return isset($this->entityPresentFields[$name]);
    }

    /** @return list<string> */
    public function getDirtyFields(): array
    {
        return array_keys($this->entityDirtyFields);
    }

    /** @return array<string, mixed> */
    public function getAdditionalAttributes(): array
    {
        return $this->entityAdditionalAttributes;
    }

    public function getAdditionalAttribute(string $name, mixed $default = null): mixed
    {
        return array_key_exists($name, $this->entityAdditionalAttributes)
            ? $this->entityAdditionalAttributes[$name]
            : $default;
    }

    public function markClean(): void
    {
        $this->entityDirtyFields = [];
    }

    /** @return array<string, mixed> */
    public function toCreatePayload(): array
    {
        return $this->makePayload(static::CREATE_FIELDS, false);
    }

    /** @return array<string, mixed> */
    public function toPatchPayload(): array
    {
        return $this->makePayload(static::PATCH_FIELDS, true);
    }

    /** @return array<string, mixed> */
    public function toReplacePayload(): array
    {
        return $this->makePayload(static::REPLACE_FIELDS, false);
    }

    /** @return array<string, mixed> */
    public function toCloudflareData(): array
    {
        $data = [];

        foreach ($this->entityPresentFields as $name => $_present) {
            $value = array_key_exists($name, $this->entityAttributes)
                ? $this->entityAttributes[$name]
                : $this->entityAdditionalAttributes[$name];
            $data[$name] = $this->normalizeValue($value, false);
        }

        return $data;
    }

    protected function setAttribute(string $name, mixed $value): void
    {
        if ($this->isEntityProperty($name)) {
            $property = new ReflectionProperty($this, $name);
            $property->setValue($this, $value);
        }

        $this->entityAttributes[$name] = $value;
        $this->entityPresentFields[$name] = true;
        $this->entityDirtyFields[$name] = true;
    }

    /**
     * @throws AttributeDoesNotExistsException
     */
    protected function getAttribute(string $name): mixed
    {
        if (!array_key_exists($name, $this->entityAttributes)) {
            throw new AttributeDoesNotExistsException($name, static::class);
        }

        return $this->entityAttributes[$name];
    }

    /**
     * @param list<string> $allowedFields
     * @return array<string, mixed>
     */
    private function makePayload(array $allowedFields, bool $dirtyOnly): array
    {
        $payload = [];

        foreach ($allowedFields as $name) {
            if (!$this->hasAttribute($name)) {
                continue;
            }

            $value = array_key_exists($name, $this->entityAttributes)
                ? $this->entityAttributes[$name]
                : $this->entityAdditionalAttributes[$name];

            if (
                $dirtyOnly
                && !isset($this->entityDirtyFields[$name])
                && !$this->containsDirtyEntity($value)
            ) {
                continue;
            }

            $payload[$name] = $this->normalizeValue($value, true);
        }

        return $payload;
    }

    private function normalizeValue(mixed $value, bool $forWrite): mixed
    {
        if ($value instanceof BackedEnum) {
            return $value->value;
        }

        if ($value instanceof DateTimeInterface) {
            return $value->format(DateTimeInterface::ATOM);
        }

        if ($value instanceof AbstractEntity) {
            return $forWrite ? $value->toReplacePayload() : $value->toCloudflareData();
        }

        if (is_array($value)) {
            foreach ($value as $key => $item) {
                $value[$key] = $this->normalizeValue($item, $forWrite);
            }
        }

        return $value;
    }

    private function containsDirtyEntity(mixed $value): bool
    {
        if ($value instanceof AbstractEntity) {
            return $value->hasDirtyWritePayload();
        }

        if (is_array($value)) {
            foreach ($value as $item) {
                if ($this->containsDirtyEntity($item)) {
                    return true;
                }
            }
        }

        return false;
    }

    private function hasDirtyWritePayload(): bool
    {
        foreach (static::PATCH_FIELDS as $name) {
            if (!$this->hasAttribute($name)) {
                continue;
            }

            if (isset($this->entityDirtyFields[$name])) {
                return true;
            }

            $value = array_key_exists($name, $this->entityAttributes)
                ? $this->entityAttributes[$name]
                : $this->entityAdditionalAttributes[$name];

            if ($this->containsDirtyEntity($value)) {
                return true;
            }
        }

        return false;
    }

    /** @return list<string> */
    private function getGetterMethodNames(string $name): array
    {
        return [
            AttributeNamer::getGetterName($name),
            AttributeNamer::getBooleanGetterName($name),
        ];
    }

    private function canDispatchMethod(string $methodName, int $argumentCount): bool
    {
        if (!method_exists($this, $methodName)) {
            return false;
        }

        $method = new ReflectionMethod($this, $methodName);

        return $method->isPublic()
            && !$method->isStatic()
            && $method->getNumberOfRequiredParameters() <= $argumentCount
            && ($method->isVariadic() || $method->getNumberOfParameters() >= $argumentCount);
    }

    private function isEntityProperty(string $name): bool
    {
        return property_exists($this, $name) && !in_array($name, [
            'entityAttributes',
            'entityPresentFields',
            'entityDirtyFields',
            'entityAdditionalAttributes',
        ], true);
    }
}
