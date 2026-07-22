<?php

declare(strict_types=1);

namespace PlainSimple\Cloudflare\Endpoints;

use GuzzleHttp\Psr7\Utils;
use JsonException;
use PlainSimple\Cloudflare\Adapters\AdapterInterface;
use PlainSimple\Cloudflare\Entities\AbstractEntity;
use PlainSimple\Cloudflare\Exceptions\ErrorResponseException;
use PlainSimple\Cloudflare\Exceptions\InvalidClassException;
use PlainSimple\Cloudflare\Responses\ActionResponse;
use PlainSimple\Cloudflare\Responses\EntityResponse;
use PlainSimple\Cloudflare\Responses\ListResponse;
use PlainSimple\Cloudflare\Responses\RawResponse;
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
        [$preservedResponse] = $this->parseEnvelope($response);

        return $preservedResponse;
    }

    /**
     * @template TEntity of AbstractEntity
     * @param class-string<TEntity> $entityClassName
     * @return ListResponse<TEntity>
     * @throws JsonException
     * @throws ErrorResponseException
     * @throws InvalidClassException
     */
    protected function makeListResponse(ResponseInterface $response, string $entityClassName): ListResponse
    {
        [$preservedResponse, $responseContents] = $this->parseEnvelope($response);

        return new ListResponse($preservedResponse, $responseContents, $entityClassName);
    }

    /**
     * @template TEntity of AbstractEntity
     * @param class-string<TEntity> $entityClassName
     * @return EntityResponse<TEntity>
     * @throws JsonException
     * @throws ErrorResponseException
     * @throws InvalidClassException
     */
    protected function makeEntityResponse(ResponseInterface $response, string $entityClassName): EntityResponse
    {
        [$preservedResponse, $responseContents] = $this->parseEnvelope($response);

        return new EntityResponse($preservedResponse, $responseContents, $entityClassName);
    }

    /**
     * @param bool $allowSparseEnvelope Accept a 2xx envelope without `success`; explicit failure remains invalid.
     * @throws ErrorResponseException
     * @throws JsonException
     */
    protected function makeActionResponse(
        ResponseInterface $response,
        bool $allowSparseEnvelope = false,
    ): ActionResponse {
        [$preservedResponse, $responseContents] = $this->parseEnvelope(
            $response,
            true,
            $allowSparseEnvelope,
        );

        return new ActionResponse(
            $preservedResponse,
            $responseContents['result'] ?? null,
            $responseContents,
        );
    }

    /**
     * @throws ErrorResponseException
     */
    protected function makeRawResponse(ResponseInterface $response): RawResponse
    {
        [$preservedResponse, $body] = $this->readBody($response);

        if ($this->isSuccessful($preservedResponse)) {
            return new RawResponse($preservedResponse, $body);
        }

        try {
            $responseContents = json_decode($body, true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException $exception) {
            throw new ErrorResponseException($preservedResponse, [], previous: $exception);
        }

        $errors = is_array($responseContents) && is_array($responseContents['errors'] ?? null)
            ? $responseContents['errors']
            : [];

        throw new ErrorResponseException($preservedResponse, $errors);
    }

    /**
     * @throws JsonException
     */
    protected function getJsonContents(ResponseInterface $response): mixed
    {
        [, $body] = $this->readBody($response);

        return json_decode($body, true, 512, JSON_THROW_ON_ERROR);
    }

    /**
     * @return array{ResponseInterface, array<string, mixed>}
     * @throws ErrorResponseException
     * @throws JsonException
     */
    private function parseEnvelope(
        ResponseInterface $response,
        bool $allowEmpty = false,
        bool $allowSparseEnvelope = false,
    ): array {
        [$preservedResponse, $body] = $this->readBody($response);

        if (trim($body) === '' && $allowEmpty && $this->isSuccessful($preservedResponse)) {
            return [$preservedResponse, []];
        }

        try {
            $responseContents = json_decode($body, true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException $exception) {
            if (!$this->isSuccessful($preservedResponse)) {
                throw new ErrorResponseException($preservedResponse, [], previous: $exception);
            }

            throw $exception;
        }

        if (!is_array($responseContents)) {
            throw new ErrorResponseException($preservedResponse, []);
        }

        if (!$this->isSuccessful($preservedResponse)) {
            $errors = is_array($responseContents['errors'] ?? null) ? $responseContents['errors'] : [];

            throw new ErrorResponseException($preservedResponse, $errors);
        }

        $this->assertSuccessfulEnvelope($preservedResponse, $responseContents, $allowSparseEnvelope);

        return [$preservedResponse, $responseContents];
    }

    /** @param array<array-key, mixed> $responseContents */
    private function assertSuccessfulEnvelope(
        ResponseInterface $response,
        array $responseContents,
        bool $allowSparseEnvelope = false,
    ): void {
        if (($responseContents['success'] ?? null) === true) {
            return;
        }
        if ($allowSparseEnvelope && !array_key_exists('success', $responseContents)) {
            return;
        }

        $errors = is_array($responseContents['errors'] ?? null) ? $responseContents['errors'] : [];

        throw new ErrorResponseException($response, $errors);
    }

    /** @return array{ResponseInterface, string} */
    private function readBody(ResponseInterface $response): array
    {
        $stream = $response->getBody();
        if ($stream->isSeekable()) {
            $stream->rewind();
        }

        $body = $stream->getContents();
        if ($stream->isSeekable()) {
            $stream->rewind();

            return [$response, $body];
        }

        $preservedResponse = $response->withBody(Utils::streamFor($body));

        return [
            $preservedResponse instanceof ResponseInterface ? $preservedResponse : $response,
            $body,
        ];
    }

    private function isSuccessful(ResponseInterface $response): bool
    {
        return $response->getStatusCode() >= 200 && $response->getStatusCode() < 300;
    }
}
