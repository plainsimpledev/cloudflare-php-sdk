<?php

declare(strict_types=1);

namespace PlainSimple\Cloudflare\Responses;

use PlainSimple\Cloudflare\Entities\Entity;
use PlainSimple\Cloudflare\Exceptions\InvalidClassException;
use Psr\Http\Message\ResponseInterface;

class EntityResponse
{
    private Entity $entity;

    private ResponseInterface $originalResponse;

    /**
     * @throws InvalidClassException
     */
    public function __construct(ResponseInterface $originalResponse, array $responseContents, string $entityClassName)
    {
        if (!is_a($entityClassName, Entity::class, true)) {
            throw new InvalidClassException($entityClassName . ' must implement ' . Entity::class);
        }
        $this->originalResponse = $originalResponse;
        $this->entity = $entityClassName::makeFromCloudflareData($responseContents['result']);
    }

    public function getOriginalResponse(): ResponseInterface
    {
        return $this->originalResponse;
    }

    public function getEntity(): Entity
    {
        return $this->entity;
    }
}
