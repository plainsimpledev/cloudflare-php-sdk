<?php

declare(strict_types=1);

namespace PlainSimple\Cloudflare\Endpoints;

use InvalidArgumentException;
use JsonException;
use PlainSimple\Cloudflare\Entities\Account;
use PlainSimple\Cloudflare\Exceptions\ErrorResponseException;
use PlainSimple\Cloudflare\Exceptions\InvalidClassException;
use PlainSimple\Cloudflare\Responses\ActionResponse;
use PlainSimple\Cloudflare\Responses\EntityResponse;
use PlainSimple\Cloudflare\Responses\ListResponse;
use PlainSimple\Cloudflare\Utilities\PathSegment;

class Accounts extends AbstractEndpoint
{
    /**
     * @link https://developers.cloudflare.com/api/resources/accounts/methods/list/
     * @return ListResponse<Account>
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
        ], static fn (mixed $value): bool => $value !== null);
        $response = $this->adapter->get('accounts', $query);

        return $this->makeListResponse($response, Account::class);
    }

    /**
     * @link https://developers.cloudflare.com/api/resources/accounts/methods/get/
     * @return EntityResponse<Account>
     * @throws InvalidClassException
     * @throws ErrorResponseException
     * @throws JsonException
     */
    public function get(string $accountId): EntityResponse
    {
        $response = $this->adapter->get('accounts/' . $this->accountId($accountId));

        return $this->makeEntityResponse($response, Account::class);
    }

    /**
     * @link https://developers.cloudflare.com/api/resources/accounts/methods/create/
     * @return EntityResponse<Account>
     * @throws InvalidClassException
     * @throws ErrorResponseException
     * @throws JsonException
     */
    public function create(Account $account): EntityResponse
    {
        $response = $this->adapter->post('accounts', $account->toCreatePayload());

        return $this->makeEntityResponse($response, Account::class);
    }

    /**
     * @link https://developers.cloudflare.com/api/resources/accounts/methods/update/
     * @return EntityResponse<Account>
     * @throws InvalidClassException
     * @throws ErrorResponseException
     * @throws JsonException
     */
    public function update(Account $account): EntityResponse
    {
        $response = $this->adapter->put(
            'accounts/' . $this->accountId($account),
            $account->toReplacePayload(),
        );

        return $this->makeEntityResponse($response, Account::class);
    }

    /**
     * @link https://developers.cloudflare.com/api/resources/accounts/methods/delete/
     * @throws JsonException
     * @throws ErrorResponseException
     */
    public function delete(Account|string $account): ActionResponse
    {
        $response = $this->adapter->delete('accounts/' . $this->accountId($account));

        return $this->makeActionResponse($response);
    }

    private function accountId(Account|string $account): string
    {
        if ($account instanceof Account && !$account->hasAttribute('id')) {
            throw new InvalidArgumentException('Account must have an id.');
        }

        return PathSegment::encode(
            $account instanceof Account ? $account->getId() : $account,
            'Account ID',
        );
    }
}
