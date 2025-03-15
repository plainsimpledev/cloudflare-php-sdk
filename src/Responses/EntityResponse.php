<?php

declare(strict_types=1);

namespace PlainSimple\Cloudflare\Responses;

use PlainSimple\Cloudflare\Entities\AbstractEntity;
use PlainSimple\Cloudflare\Exceptions\InvalidClassException;
use Psr\Http\Message\ResponseInterface;

class EntityResponse
{
    private AbstractEntity $entity;

    private ResponseInterface $originalResponse;

    /**
     * @throws InvalidClassException
     */
    public function __construct(ResponseInterface $originalResponse, array $responseContents, string $entityClassName)
    {
        if (!is_a($entityClassName, AbstractEntity::class, true)) {
            throw new InvalidClassException($entityClassName . ' must implement ' . AbstractEntity::class);
        }
        $this->originalResponse = $originalResponse;
        $this->entity = $entityClassName::makeFromCloudflareData($responseContents['result']);
    }

    public function getOriginalResponse(): ResponseInterface
    {
        return $this->originalResponse;
    }

    public function getEntity(): AbstractEntity
    {
        return $this->entity;
    }
}
