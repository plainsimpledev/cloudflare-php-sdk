<?php

declare(strict_types=1);

namespace PlainSimple\Cloudflare\Endpoints;

use JsonException;
use PlainSimple\Cloudflare\Entities\Account;
use PlainSimple\Cloudflare\Exceptions\ErrorResponseException;
use PlainSimple\Cloudflare\Exceptions\InvalidClassException;
use PlainSimple\Cloudflare\Responses\EntityResponse;
use PlainSimple\Cloudflare\Responses\ListResponse;
use Psr\Http\Message\ResponseInterface;

class Accounts extends AbstractEndpoint
{
    /**
     * @link https://developers.cloudflare.com/api/resources/accounts/methods/list/
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
     * @link https://developers.cloudflare.com/api/resources/accounts/methods/get/
     * @throws InvalidClassException
     * @throws ErrorResponseException
     * @throws JsonException
     */
    public function get(string $accountId): EntityResponse
    {
        $response = $this->adapter->get('/accounts/' . $accountId);
        return $this->makeEntityResponse($response, Account::class);
    }

    /**
     * @link https://developers.cloudflare.com/api/resources/accounts/methods/create/
     * @throws InvalidClassException
     * @throws ErrorResponseException
     * @throws JsonException
     */
    public function create(array $data): EntityResponse
    {
        $response = $this->adapter->post('/accounts', $data);
        return $this->makeEntityResponse($response, Account::class);
    }

    /**
     * @link https://developers.cloudflare.com/api/resources/accounts/methods/update/
     * @throws InvalidClassException
     * @throws ErrorResponseException
     * @throws JsonException
     */
    public function update(string $accountId, array $data): EntityResponse
    {
        $response = $this->adapter->put('/accounts/' . $accountId, $data);
        return $this->makeEntityResponse($response, Account::class);
    }

    /**
     * @link https://developers.cloudflare.com/api/resources/accounts/methods/update/
     * @throws JsonException
     * @throws ErrorResponseException
     */
    public function delete(string $accountId): ResponseInterface
    {
        $response = $this->adapter->delete('/accounts/' . $accountId);
        return $this->processResponse($response);
    }
}
