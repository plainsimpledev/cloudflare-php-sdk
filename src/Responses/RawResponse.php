<?php

declare(strict_types=1);

namespace PlainSimple\Cloudflare\Responses;

use Psr\Http\Message\ResponseInterface;

class RawResponse
{
    public function __construct(
        private ResponseInterface $originalResponse,
        private string $body,
    ) {
    }

    public function getBody(): string
    {
        return $this->body;
    }

    public function getOriginalResponse(): ResponseInterface
    {
        return $this->originalResponse;
    }
}
