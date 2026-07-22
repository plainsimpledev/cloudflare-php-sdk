<?php

declare(strict_types=1);

namespace PlainSimple\Cloudflare\Adapters;

use Psr\Http\Message\ResponseInterface;

interface AdapterInterface
{
    /**
     * @param array<string, mixed> $query
     * @param array<string, string|list<string>> $headers
     */
    public function get(string $url, array $query = [], array $headers = []): ResponseInterface;

    /** @param array<string, string|list<string>> $headers */
    public function post(string $url, mixed $data = null, array $headers = []): ResponseInterface;

    /** @param array<string, string|list<string>> $headers */
    public function put(string $url, mixed $data = null, array $headers = []): ResponseInterface;

    /** @param array<string, string|list<string>> $headers */
    public function patch(string $url, mixed $data = null, array $headers = []): ResponseInterface;

    /** @param array<string, string|list<string>> $headers */
    public function delete(string $url, mixed $data = null, array $headers = []): ResponseInterface;

    /**
     * @param list<array<string, mixed>> $multipart
     * @param array<string, mixed> $query
     * @param array<string, string|list<string>> $headers
     */
    public function postMultipart(
        string $url,
        array $multipart,
        array $query = [],
        array $headers = [],
    ): ResponseInterface;
}
