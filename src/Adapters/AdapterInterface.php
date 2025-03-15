<?php

declare(strict_types=1);

namespace PlainSimple\Cloudflare\Adapters;

use PlainSimple\Cloudflare\Auth\AuthInterface;
use Psr\Http\Message\ResponseInterface;

interface AdapterInterface
{
    public function __construct(AuthInterface $auth, string $baseUri);

    public function get(string $url, array $data = [], array $headers = []): ResponseInterface;

    public function post(string $url, array $data = [], array $headers = []): ResponseInterface;

    public function put(string $url, array $data = [], array $headers = []): ResponseInterface;

    public function delete(string $url, array $data = [], array $headers = []): ResponseInterface;
}