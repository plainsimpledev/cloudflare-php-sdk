<?php

declare(strict_types=1);

use PHPUnit\Framework\MockObject\Exception;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use PlainSimple\Cloudflare\Adapters\AdapterInterface;
use PlainSimple\Cloudflare\Endpoints\AbstractEndpoint;
use PlainSimple\Cloudflare\Endpoints\Accounts;
use PlainSimple\Cloudflare\Entities\Account;
use PlainSimple\Cloudflare\Exceptions\ErrorResponseException;
use PlainSimple\Cloudflare\Exceptions\InvalidClassException;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;

class AccountsTest extends TestCase
{
    private Accounts $accounts;
    private MockObject|AdapterInterface $adapter;

    /**
     * @throws Exception
     */
    protected function setUp(): void
    {
        $this->adapter = $this->createMock(AdapterInterface::class);
        $this->accounts = new Accounts($this->adapter);
    }

    /**
     * @throws InvalidClassException
     * @throws ErrorResponseException
     * @throws JsonException
     * @throws Exception
     */
    public function testListAccountsWithDefaultParameters(): void
    {
        $mockResponse = $this->createMock(ResponseInterface::class);
        $responseData = [
            'success' => true,
            'errors' => [],
            'messages' => [],
            'result' => [
                [
                    'id' => 'account-id-1',
                    'name' => 'Test Account 1',
                ],
                [
                    'id' => 'account-id-2',
                    'name' => 'Test Account 2',
                ],
            ],
            'result_info' => [
                'page' => 1,
                'per_page' => 20,
                'total_pages' => 1,
                'count' => 2,
                'total_count' => 2,
            ],
        ];

        $mockStream = $this->createStreamMock($responseData);

        $mockResponse->method('getStatusCode')->willReturn(200);
        $mockResponse->method('getBody')
            ->willReturn($mockStream);

        $this->adapter->expects($this->once())
            ->method('get')
            ->with('/accounts/list', [
                'page' => 1,
                'per_page' => 20,
                'direction' => 'asc',
            ])
            ->willReturn($mockResponse);

        $response = $this->accounts->list();

        $items = $response->getItems();
        $this->assertCount(2, $items);
        $this->assertContainsOnlyInstancesOf(Account::class, $items);
        $this->assertEquals('account-id-1', $items[0]->getId());
        $this->assertEquals('Test Account 1', $items[0]->getName());
    }

    /**
     * @throws InvalidClassException
     * @throws ErrorResponseException
     * @throws JsonException
     * @throws Exception
     */
    public function testListAccountsWithCustomParameters(): void
    {
        $mockResponse = $this->createMock(ResponseInterface::class);
        $responseData = [
            'success' => true,
            'errors' => [],
            'messages' => [],
            'result' => [
                [
                    'id' => 'filtered-account',
                    'name' => 'Filtered Account',
                ],
            ],
            'result_info' => [
                'page' => 2,
                'per_page' => 5,
                'total_pages' => 3,
                'count' => 1,
                'total_count' => 11,
            ],
        ];

        $mockStream = $this->createStreamMock($responseData);

        $mockResponse->method('getStatusCode')->willReturn(200);
        $mockResponse->method('getBody')
            ->willReturn($mockStream);

        $this->adapter->expects($this->once())
            ->method('get')
            ->with('/accounts/list', [
                'name' => 'Filtered',
                'page' => 2,
                'per_page' => 5,
                'direction' => 'desc',
            ])
            ->willReturn($mockResponse);

        $response = $this->accounts->list('Filtered', 2, 5, AbstractEndpoint::DIRECTION_DESC);

        $items = $response->getItems();
        $this->assertCount(1, $items);
        $this->assertEquals('filtered-account', $items[0]->getId());
        $this->assertEquals('Filtered Account', $items[0]->getName());
        $this->assertEquals(2, $response->getPage());
        $this->assertEquals(5, $response->getPerPage());
    }

    /**
     * @throws InvalidClassException
     * @throws ErrorResponseException
     * @throws JsonException
     * @throws Exception
     */
    public function testGetAccount(): void
    {
        $accountId = 'test-account-id';
        $mockResponse = $this->createMock(ResponseInterface::class);
        $responseData = [
            'success' => true,
            'errors' => [],
            'messages' => [],
            'result' => [
                'id' => $accountId,
                'name' => 'Test Account',
                'settings' => [
                    'abuse_contact_email' => 'abuse_contact_email',
                    'default_nameservers' => 'cloudflare.standard',
                    'enforce_twofactor' => true,
                    'use_account_custom_ns_by_default' => true
                ],
            ],
        ];

        $mockStream = $this->createStreamMock($responseData);

        $mockResponse->method('getStatusCode')->willReturn(200);
        $mockResponse->method('getBody')
            ->willReturn($mockStream);

        $this->adapter->expects($this->once())
            ->method('get')
            ->with('/accounts/' . $accountId)
            ->willReturn($mockResponse);

        $response = $this->accounts->get($accountId);

        /** @var Account $entity */
        $entity = $response->getEntity();

        $this->assertInstanceOf(Account::class, $entity);
        $this->assertEquals($accountId, $entity->getId());
        $this->assertEquals('Test Account', $entity->getName());
    }

    /**
     * @throws InvalidClassException
     * @throws ErrorResponseException
     * @throws JsonException
     * @throws Exception
     */
    public function testCreateAccount(): void
    {
        $accountData = [
            'name' => 'New Test Account',
        ];

        $mockResponse = $this->createMock(ResponseInterface::class);
        $responseData = [
            'success' => true,
            'errors' => [],
            'messages' => [],
            'result' => [
                'id' => 'new-account-id',
                'name' => 'New Test Account',
            ],
        ];

        $mockStream = $this->createStreamMock($responseData);

        $mockResponse->method('getStatusCode')->willReturn(200);
        $mockResponse->method('getBody')
            ->willReturn($mockStream);

        $this->adapter->expects($this->once())
            ->method('post')
            ->with('/accounts', $accountData)
            ->willReturn($mockResponse);

        $response = $this->accounts->create($accountData);

        $entity = $response->getEntity();

        $this->assertInstanceOf(Account::class, $entity);
        $this->assertEquals('new-account-id', $entity->getId());
        $this->assertEquals('New Test Account', $entity->getName());
    }

    /**
     * @throws InvalidClassException
     * @throws ErrorResponseException
     * @throws JsonException
     * @throws Exception
     */
    public function testUpdateAccount(): void
    {
        $accountId = 'update-account-id';
        $updateData = [
            'name' => 'Updated Account Name',
        ];

        $mockResponse = $this->createMock(ResponseInterface::class);
        $responseData = [
            'success' => true,
            'errors' => [],
            'messages' => [],
            'result' => [
                'id' => $accountId,
                'name' => 'Updated Account Name',
            ],
        ];

        $mockStream = $this->createStreamMock($responseData);

        $mockResponse->method('getStatusCode')->willReturn(200);
        $mockResponse->method('getBody')
            ->willReturn($mockStream);

        $this->adapter->expects($this->once())
            ->method('put')
            ->with('/accounts/' . $accountId, $updateData)
            ->willReturn($mockResponse);

        $response = $this->accounts->update($accountId, $updateData);

        $entity = $response->getEntity();

        $this->assertInstanceOf(Account::class, $entity);
        $this->assertEquals($accountId, $entity->getId());
        $this->assertEquals('Updated Account Name', $entity->getName());
    }

    /**
     * @throws ErrorResponseException
     * @throws JsonException
     * @throws Exception
     */
    public function testDeleteAccount(): void
    {
        $accountId = 'delete-account-id';
        $mockResponse = $this->createMock(ResponseInterface::class);
        $responseData = [
            'success' => true,
            'errors' => [],
            'messages' => [],
        ];

        $mockStream = $this->createStreamMock($responseData);

        $mockResponse->method('getStatusCode')->willReturn(200);
        $mockResponse->method('getBody')
            ->willReturn($mockStream);

        $this->adapter->expects($this->once())
            ->method('delete')
            ->with('/accounts/' . $accountId)
            ->willReturn($mockResponse);

        $response = $this->accounts->delete($accountId);

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals(json_encode($responseData, JSON_THROW_ON_ERROR), $response->getBody()->getContents());
    }

    /**
     * @throws InvalidClassException
     * @throws ErrorResponseException
     * @throws JsonException
     * @throws Exception
     */
    public function testErrorResponseException(): void
    {
        $mockResponse = $this->createMock(ResponseInterface::class);
        $responseData = [
            'success' => false,
            'errors' => [
                [
                    'code' => 1003,
                    'message' => 'Invalid request parameter',
                ],
            ],
            'messages' => [],
        ];

        $mockStream = $this->createStreamMock($responseData);

        $mockResponse->method('getStatusCode')->willReturn(400);
        $mockResponse->method('getBody')
            ->willReturn($mockStream);

        $this->adapter->expects($this->once())
            ->method('get')
            ->with('/accounts/invalid-id')
            ->willReturn($mockResponse);

        $this->expectException(ErrorResponseException::class);

        $this->accounts->get('invalid-id');
    }

    /**
     * @throws InvalidClassException
     * @throws ErrorResponseException
     * @throws JsonException
     * @throws Exception
     */
    public function testJsonException(): void
    {
        $mockResponse = $this->createMock(ResponseInterface::class);

        $mockStream = $this->createStreamMock('{"key": "value",}');

        $mockResponse->method('getStatusCode')->willReturn(200);
        $mockResponse->method('getBody')
            ->willReturn($mockStream);

        $this->adapter->expects($this->once())
            ->method('get')
            ->with('/accounts/json-error')
            ->willReturn($mockResponse);

        $this->expectException(JsonException::class);

        $this->accounts->get('json-error');
    }

    /**
     * @throws Exception
     * @throws JsonException
     */
    private function createStreamMock(mixed $responseData): StreamInterface
    {
        $mockStream = $this->createMock(StreamInterface::class);
        $mockStream->method('getContents')
            ->willReturn(json_encode($responseData, JSON_THROW_ON_ERROR));

        return $mockStream;
    }
}
