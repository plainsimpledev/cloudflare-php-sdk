<?php

declare(strict_types=1);

namespace PlainSimple\Cloudflare\Endpoints;

use JsonException;
use PlainSimple\Cloudflare\Entities\Account;
use PlainSimple\Cloudflare\Exceptions\ErrorResponseException;
use PlainSimple\Cloudflare\Exceptions\InvalidClassException;
use PlainSimple\Cloudflare\Responses\EntityResponse;
use PlainSimple\Cloudflare\Responses\ListResponse;

class Accounts extends Endpoint
{
    /**
     * @throws InvalidClassException
     * @throws ErrorResponseException
     * @throws JsonException
     */
    public function list(
        ?string $name = null,
        int $page = 1,
        int $perPage = 20,
        string $direction = self::DIRECTION_ASC
    ): ListResponse {
        $query = array_filter([
            'name' => $name,
            'page' => $page,
            'per_page' => $perPage,
            'direction' => $direction,
        ]);
        $response = $this->adapter->get('/accounts/list', $query);
        return $this->makeListResponse($response, Account::class);
    }

    /**
     * @throws InvalidClassException
     * @throws ErrorResponseException
     * @throws JsonException
     */
    public function get(string $accountId): EntityResponse
    {
        $response = $this->adapter->get('/accounts/' . $accountId);
        return $this->makeEntityResponse($response, Account::class);
    }
}
