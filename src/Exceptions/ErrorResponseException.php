<?php

declare(strict_types=1);

namespace PlainSimple\Cloudflare\Exceptions;

use Exception;
use Psr\Http\Message\ResponseInterface;
use Throwable;

class ErrorResponseException extends Exception
{
    private ResponseInterface $response;

    /** @var array<int, mixed> */
    private array $errors;

    /** @param array<int, mixed> $errors */
    public function __construct(
        ResponseInterface $response,
        array $errors,
        string $message = '',
        int $code = 0,
        ?Throwable $previous = null,
    ) {
        $this->response = $response;
        $this->errors = $errors;

        $firstError = reset($errors);
        if ($message === '' && is_array($firstError) && is_string($firstError['message'] ?? null)) {
            $message = $firstError['message'];
        }
        if ($code === 0 && is_array($firstError) && is_numeric($firstError['code'] ?? null)) {
            $code = (int) $firstError['code'];
        }

        if ($message === '') {
            $message = sprintf(
                'Cloudflare API request failed with HTTP %d%s.',
                $response->getStatusCode(),
                $response->getReasonPhrase() === '' ? '' : ' ' . $response->getReasonPhrase(),
            );
        }
        if ($code === 0 && $response->getStatusCode() >= 400) {
            $code = $response->getStatusCode();
        }

        parent::__construct($message, $code, $previous);
    }

    public function getResponse(): ResponseInterface
    {
        return $this->response;
    }

    /** @return array<int, mixed> */
    public function getErrors(): array
    {
        return $this->errors;
    }
}
