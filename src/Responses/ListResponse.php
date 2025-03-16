<?php

declare(strict_types=1);

namespace PlainSimple\Cloudflare\Responses;

use JsonException;
use PlainSimple\Cloudflare\Entities\AbstractEntity;
use PlainSimple\Cloudflare\Exceptions\InvalidClassException;
use Psr\Http\Message\ResponseInterface;

class ListResponse
{
    private array $items = [];
    private int $count;
    private int $page;
    private int $perPage;
    private int $totalCount;

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
        $results = $responseContents['result'];
        foreach ($results as $result) {
            $this->items[] = $entityClassName::makeFromCloudflareData($result);
        }

        $resultsInfo = $responseContents['result_info'];

        $this->count = $resultsInfo['count'];
        $this->page = $resultsInfo['page'];
        $this->perPage = $resultsInfo['per_page'];
        $this->totalCount = $resultsInfo['total_count'];
    }

    public function getItems(): array
    {
        return $this->items;
    }

    public function getCount(): int
    {
        return $this->count;
    }

    public function getPage(): int
    {
        return $this->page;
    }

    public function getPerPage(): int
    {
        return $this->perPage;
    }

    public function getTotalCount(): int
    {
        return $this->totalCount;
    }

    public function getOriginalResponse(): ResponseInterface
    {
        return $this->originalResponse;
    }
}
