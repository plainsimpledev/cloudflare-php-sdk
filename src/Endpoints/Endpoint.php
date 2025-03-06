<?php

declare(strict_types=1);

namespace PlainSimple\Cloudflare\Endpoints;

use JsonException;
use PlainSimple\Cloudflare\Adapters\Adapter;
use PlainSimple\Cloudflare\Exceptions\ErrorResponseException;
use PlainSimple\Cloudflare\Exceptions\InvalidClassException;
use PlainSimple\Cloudflare\Responses\EntityResponse;
use PlainSimple\Cloudflare\Responses\ListResponse;
use Psr\Http\Message\ResponseInterface;

abstract class Endpoint
{
    public const string DIRECTION_ASC = 'asc';
    public const string DIRECTION_DESC = 'desc';

    public function __construct(protected Adapter $adapter)
    {
    }

    /**
     * @throws JsonException
     * @throws ErrorResponseException
     * @throws InvalidClassException
     */
    protected function makeListResponse(ResponseInterface $response, string $entityClassName): ListResponse
    {
        $responseContents = $this->getJsonContents($response);
        if (!$responseContents['success']) {
            throw new ErrorResponseException($response, $responseContents['errors'] ?? []);
        }

        return new ListResponse($response, $responseContents, $entityClassName);
    }

    /**
     * @throws JsonException
     * @throws ErrorResponseException
     * @throws InvalidClassException
     */
    protected function makeEntityResponse(ResponseInterface $response, string $entityClassName): EntityResponse
    {
        $responseContents = $this->getJsonContents($response);
        if (!$responseContents['success']) {
            throw new ErrorResponseException($response, $responseContents['errors'] ?? []);
        }

        return new EntityResponse($response, $responseContents, $entityClassName);
    }

    /**
     * @throws JsonException
     */
    protected function getJsonContents(ResponseInterface $response): array
    {
        return json_decode(
            $response->getBody()->getContents(),
            true,
            512,
            JSON_THROW_ON_ERROR
        );
    }
}
