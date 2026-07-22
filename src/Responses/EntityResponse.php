<?php

declare(strict_types=1);

namespace PlainSimple\Cloudflare\Responses;

use PlainSimple\Cloudflare\Entities\AbstractEntity;
use PlainSimple\Cloudflare\Exceptions\InvalidClassException;
use Psr\Http\Message\ResponseInterface;

/** @template TEntity of AbstractEntity */
class EntityResponse
{
    /** @var TEntity */
    private AbstractEntity $entity;

    private ResponseInterface $originalResponse;

    /**
     * @param array<string, mixed> $responseContents
     * @param class-string<TEntity> $entityClassName
     * @throws InvalidClassException
     */
    public function __construct(ResponseInterface $originalResponse, array $responseContents, string $entityClassName)
    {
        if (!is_subclass_of($entityClassName, AbstractEntity::class)) {
            throw new InvalidClassException($entityClassName . ' must extend ' . AbstractEntity::class);
        }

        $result = $responseContents['result'] ?? null;
        if (!is_array($result)) {
            throw new InvalidClassException('Response result must contain entity data');
        }

        $this->originalResponse = $originalResponse;
        $this->entity = $entityClassName::makeFromCloudflareData($result);
    }

    public function getOriginalResponse(): ResponseInterface
    {
        return $this->originalResponse;
    }

    /** @return TEntity */
    public function getEntity(): AbstractEntity
    {
        return $this->entity;
    }
}
