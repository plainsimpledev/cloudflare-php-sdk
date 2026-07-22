<?php

declare(strict_types=1);

use GuzzleHttp\Psr7\Response;
use GuzzleHttp\Psr7\Utils;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use PlainSimple\Cloudflare\Adapters\AdapterInterface;
use PlainSimple\Cloudflare\Endpoints\AbstractEndpoint;
use PlainSimple\Cloudflare\Endpoints\Accounts;
use PlainSimple\Cloudflare\Entities\Account;
use PlainSimple\Cloudflare\Exceptions\ErrorResponseException;

final class AccountsTest extends TestCase
{
    private Accounts $accounts;
    private AdapterInterface&MockObject $adapter;

    protected function setUp(): void
    {
        $this->adapter = $this->createMock(AdapterInterface::class);
        $this->accounts = new Accounts($this->adapter);
    }

    public function testListsAccountsUsingRelativeCollectionRouteAndNonNullQuery(): void
    {
        $response = $this->jsonResponse([
            'success' => true,
            'errors' => [],
            'messages' => [],
            'result' => [
                ['id' => 'account-id-1', 'name' => 'Test Account 1', 'type' => 'standard'],
                ['id' => 'account-id-2', 'name' => 'Test Account 2', 'type' => 'enterprise'],
            ],
            'result_info' => [
                'page' => 1,
                'per_page' => 20,
                'count' => 2,
                'total_count' => 2,
            ],
        ]);

        $this->adapter->expects($this->once())
            ->method('get')
            ->with('accounts', [
                'page' => 1,
                'per_page' => 20,
                'direction' => 'asc',
            ])
            ->willReturn($response);

        $list = $this->accounts->list();

        $this->assertCount(2, $list->getItems());
        $this->assertContainsOnlyInstancesOf(Account::class, $list->getItems());
        $this->assertSame('account-id-1', $list->getItems()[0]->getId());
        $this->assertSame(2, $list->getTotalCount());
    }

    public function testListPreservesEmptyNameAndPaginationValues(): void
    {
        $response = $this->jsonResponse([
            'success' => true,
            'result' => [],
            'result_info' => [
                'page' => 0,
                'per_page' => 0,
                'count' => 0,
                'total_count' => 0,
            ],
        ]);

        $this->adapter->expects($this->once())
            ->method('get')
            ->with('accounts', [
                'name' => '',
                'page' => 0,
                'per_page' => 0,
                'direction' => AbstractEndpoint::DIRECTION_DESC,
            ])
            ->willReturn($response);

        $list = $this->accounts->list('', 0, 0, AbstractEndpoint::DIRECTION_DESC);

        $this->assertSame(0, $list->getPage());
        $this->assertSame(0, $list->getPerPage());
    }

    public function testGetsAccountAndPreservesOriginalBody(): void
    {
        $accountId = '../account/id?view=full#details';
        $body = $this->jsonBody([
            'success' => true,
            'errors' => [],
            'result' => [
                'id' => $accountId,
                'name' => 'Test Account',
                'type' => 'standard',
            ],
        ]);
        $response = new Response(200, [], Utils::streamFor($body));

        $this->adapter->expects($this->once())
            ->method('get')
            ->with('accounts/..%2Faccount%2Fid%3Fview%3Dfull%23details')
            ->willReturn($response);

        $entityResponse = $this->accounts->get($accountId);

        $this->assertSame($accountId, $entityResponse->getEntity()->getId());
        $this->assertSame($body, $entityResponse->getOriginalResponse()->getBody()->getContents());
    }

    public function testGetRejectsWhitespaceAccountId(): void
    {
        $this->adapter->expects($this->never())->method('get');
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Account ID must not be empty.');

        $this->accounts->get('   ');
    }

    public function testCreatesAccountFromCreatePayload(): void
    {
        $account = Account::forCreate('New Account');
        $response = $this->jsonResponse([
            'success' => true,
            'result' => [
                'id' => 'new-account-id',
                'name' => 'New Account',
                'type' => 'standard',
            ],
        ], 201);

        $this->adapter->expects($this->once())
            ->method('post')
            ->with('accounts', [
                'name' => 'New Account',
                'type' => 'standard',
            ])
            ->willReturn($response);

        $created = $this->accounts->create($account);

        $this->assertSame('new-account-id', $created->getEntity()->getId());
        $this->assertSame(201, $created->getOriginalResponse()->getStatusCode());
    }

    public function testUpdatesAccountByIdUsingReplacePayload(): void
    {
        $accountId = '../account/id?replace=true';
        $account = Account::makeFromCloudflareData([
            'id' => $accountId,
            'name' => 'Original',
            'type' => 'enterprise',
        ]);
        $account->setName('Updated');
        $account->setSettings(['enforce_twofactor' => true]);
        $response = $this->jsonResponse([
            'success' => true,
            'result' => [
                'id' => $accountId,
                'name' => 'Updated',
                'type' => 'enterprise',
                'settings' => ['enforce_twofactor' => true],
            ],
        ]);

        $this->adapter->expects($this->once())
            ->method('put')
            ->with('accounts/..%2Faccount%2Fid%3Freplace%3Dtrue', [
                'id' => $accountId,
                'name' => 'Updated',
                'type' => 'enterprise',
                'settings' => ['enforce_twofactor' => true],
            ])
            ->willReturn($response);

        $updated = $this->accounts->update($account);

        $this->assertSame('Updated', $updated->getEntity()->getName());
    }

    public function testUpdateRejectsAccountWithoutId(): void
    {
        $this->adapter->expects($this->never())->method('put');
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Account must have an id.');

        $this->accounts->update(Account::forCreate('Missing ID'));
    }

    public function testUpdateRejectsEmptyAccountId(): void
    {
        $account = Account::forCreate('Missing ID');
        $account->setId('');
        $this->adapter->expects($this->never())->method('put');
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Account ID must not be empty.');

        $this->accounts->update($account);
    }

    public function testDeletesAccountEntityAndReturnsActionResponse(): void
    {
        $accountId = '../account/id?delete=true';
        $account = Account::makeFromCloudflareData([
            'id' => $accountId,
            'name' => 'Delete Me',
            'type' => 'standard',
        ]);
        $body = $this->jsonBody([
            'success' => true,
            'errors' => [],
            'result' => ['id' => $accountId],
        ]);
        $response = new Response(200, [], Utils::streamFor($body));

        $this->adapter->expects($this->once())
            ->method('delete')
            ->with('accounts/..%2Faccount%2Fid%3Fdelete%3Dtrue')
            ->willReturn($response);

        $deleted = $this->accounts->delete($account);

        $this->assertSame(['id' => $accountId], $deleted->getResult());
        $this->assertSame($body, $deleted->getOriginalResponse()->getBody()->getContents());
    }

    public function testDeletesAccountByStringId(): void
    {
        $accountId = '../account/id?delete=true';
        $response = new Response(204);

        $this->adapter->expects($this->once())
            ->method('delete')
            ->with('accounts/..%2Faccount%2Fid%3Fdelete%3Dtrue')
            ->willReturn($response);

        $deleted = $this->accounts->delete($accountId);

        $this->assertNull($deleted->getResult());
        $this->assertSame(204, $deleted->getOriginalResponse()->getStatusCode());
    }

    public function testDeleteRejectsAccountEntityWithoutId(): void
    {
        $this->adapter->expects($this->never())->method('delete');
        $this->expectException(InvalidArgumentException::class);

        $this->accounts->delete(Account::forCreate('Missing ID'));
    }

    public function testDeleteRejectsEmptyStringId(): void
    {
        $this->adapter->expects($this->never())->method('delete');
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Account ID must not be empty.');

        $this->accounts->delete('');
    }

    public function testApiErrorThrowsEvenForSuccessfulHttpStatus(): void
    {
        $response = $this->jsonResponse([
            'success' => false,
            'errors' => [
                ['code' => 1003, 'message' => 'Invalid request parameter'],
            ],
            'messages' => [],
        ]);

        $this->adapter->expects($this->once())
            ->method('get')
            ->with('accounts/invalid-id')
            ->willReturn($response);

        $this->expectException(ErrorResponseException::class);
        $this->expectExceptionCode(1003);
        $this->expectExceptionMessage('Invalid request parameter');

        $this->accounts->get('invalid-id');
    }

    public function testMalformedJsonThrowsJsonException(): void
    {
        $response = new Response(200, [], Utils::streamFor('{"success":'));

        $this->adapter->expects($this->once())
            ->method('get')
            ->with('accounts/malformed')
            ->willReturn($response);

        $this->expectException(JsonException::class);

        $this->accounts->get('malformed');
    }

    /** @param array<string, mixed> $envelope */
    private function jsonResponse(array $envelope, int $status = 200): Response
    {
        return new Response($status, [], Utils::streamFor($this->jsonBody($envelope)));
    }

    /** @param array<string, mixed> $envelope */
    private function jsonBody(array $envelope): string
    {
        return json_encode($envelope, JSON_THROW_ON_ERROR);
    }
}
