<?php

declare(strict_types=1);

namespace PlainSimple\Cloudflare\Endpoints;

use JsonException;
use PlainSimple\Cloudflare\Adapters\AdapterInterface;
use PlainSimple\Cloudflare\Exceptions\ErrorResponseException;
use PlainSimple\Cloudflare\Exceptions\InvalidClassException;
use PlainSimple\Cloudflare\Responses\EntityResponse;
use PlainSimple\Cloudflare\Responses\ListResponse;
use Psr\Http\Message\ResponseInterface;

abstract class AbstractEndpoint
{
    public const string DIRECTION_ASC = 'asc';
    public const string DIRECTION_DESC = 'desc';

    public function __construct(protected AdapterInterface $adapter)
    {
    }

    /**
     * @throws ErrorResponseException
     * @throws JsonException
     */
    protected function processResponse(ResponseInterface $response): ResponseInterface
    {
        $responseContents = $this->getJsonContents($response);
        if (!isset($responseContents['success']) || !$responseContents['success']) {
            throw new ErrorResponseException($response, $responseContents['errors'] ?? []);
        }
        return $response;
    }

    /**
     * @throws JsonException
     * @throws ErrorResponseException
     * @throws InvalidClassException
     */
    protected function makeListResponse(ResponseInterface $response, string $entityClassName): ListResponse
    {
        $responseContents = $this->getJsonContents($response);
        if (!isset($responseContents['success']) || !$responseContents['success']) {
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
        if (!isset($responseContents['success']) || !$responseContents['success']) {
            throw new ErrorResponseException($response, $responseContents['errors'] ?? []);
        }

        return new EntityResponse($response, $responseContents, $entityClassName);
    }

    /**
     * @throws JsonException
     */
    protected function getJsonContents(ResponseInterface $response): mixed
    {
        $contents = $response->getBody()->getContents();
        $unescapedContents = stripslashes($contents);
        return json_decode($unescapedContents, true, 512, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }
}
