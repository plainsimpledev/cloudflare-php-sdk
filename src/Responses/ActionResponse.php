<?php

declare(strict_types=1);

namespace PlainSimple\Cloudflare\Responses;

use Psr\Http\Message\ResponseInterface;

class ActionResponse
{
    /** @param array<string, mixed> $envelope */
    public function __construct(
        private ResponseInterface $originalResponse,
        private mixed $result,
        private array $envelope,
    ) {
    }

    public function getResult(): mixed
    {
        return $this->result;
    }

    /** @return array<string, mixed> */
    public function getEnvelope(): array
    {
        return $this->envelope;
    }

    public function getOriginalResponse(): ResponseInterface
    {
        return $this->originalResponse;
    }
}
