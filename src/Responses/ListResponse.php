<?php

declare(strict_types=1);

namespace PlainSimple\Cloudflare\Responses;

use PlainSimple\Cloudflare\Entities\AbstractEntity;
use PlainSimple\Cloudflare\Exceptions\InvalidClassException;
use Psr\Http\Message\ResponseInterface;

/** @template TEntity of AbstractEntity */
class ListResponse
{
    /** @var list<TEntity> */
    private array $items = [];
    private ?int $count;
    private ?int $page;
    private ?int $perPage;
    private ?int $totalCount;
    private ?int $totalPages;
    private ?string $nextCursor;

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

        $this->originalResponse = $originalResponse;
        $results = $responseContents['result'] ?? null;
        if (!is_array($results)) {
            throw new InvalidClassException('Response result must contain a list of entity data');
        }

        foreach ($results as $result) {
            if (!is_array($result)) {
                throw new InvalidClassException('Each response result must contain entity data');
            }

            $this->items[] = $entityClassName::makeFromCloudflareData($result);
        }

        $resultsInfo = is_array($responseContents['result_info'] ?? null)
            ? $responseContents['result_info']
            : [];
        $cursors = is_array($resultsInfo['cursors'] ?? null) ? $resultsInfo['cursors'] : [];

        $this->count = is_int($resultsInfo['count'] ?? null) ? $resultsInfo['count'] : null;
        $this->page = is_int($resultsInfo['page'] ?? null) ? $resultsInfo['page'] : null;
        $this->perPage = is_int($resultsInfo['per_page'] ?? null) ? $resultsInfo['per_page'] : null;
        $this->totalCount = is_int($resultsInfo['total_count'] ?? null) ? $resultsInfo['total_count'] : null;
        $this->totalPages = is_int($resultsInfo['total_pages'] ?? null) ? $resultsInfo['total_pages'] : null;
        $this->nextCursor = is_string($cursors['after'] ?? null) ? $cursors['after'] : null;
    }

    /** @return list<TEntity> */
    public function getItems(): array
    {
        return $this->items;
    }

    public function getCount(): ?int
    {
        return $this->count;
    }

    public function getPage(): ?int
    {
        return $this->page;
    }

    public function getPerPage(): ?int
    {
        return $this->perPage;
    }

    public function getTotalCount(): ?int
    {
        return $this->totalCount;
    }

    public function getTotalPages(): ?int
    {
        return $this->totalPages;
    }

    public function getNextCursor(): ?string
    {
        return $this->nextCursor;
    }

    public function getOriginalResponse(): ResponseInterface
    {
        return $this->originalResponse;
    }
}
