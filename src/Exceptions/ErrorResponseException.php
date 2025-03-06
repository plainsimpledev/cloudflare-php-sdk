<?php

declare(strict_types=1);

namespace PlainSimple\Cloudflare\Exceptions;

use Exception;
use Psr\Http\Message\ResponseInterface;

class ErrorResponseException extends Exception
{
    private ResponseInterface $response;
    private array $errors;

    public function __construct(ResponseInterface $response, array $errors, string $message = '', int $code = 0, Exception $previous = null)
    {
        $this->response = $response;
        $this->errors = $errors;
        parent::__construct($message, $code, $previous);
    }

    public function getResponse(): ResponseInterface
    {
        return $this->response;
    }

    public function getErrors(): array
    {
        return $this->errors;
    }
}
