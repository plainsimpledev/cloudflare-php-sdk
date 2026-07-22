<?php

declare(strict_types=1);

use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use PlainSimple\Cloudflare\Adapters\AdapterInterface;
use PlainSimple\Cloudflare\Endpoints\Rulesets;
use PlainSimple\Cloudflare\Entities\Rule;
use PlainSimple\Cloudflare\Entities\Ruleset;
use PlainSimple\Cloudflare\Enums\RuleAction;
use PlainSimple\Cloudflare\Enums\RulesetKind;
use PlainSimple\Cloudflare\Enums\RulesetPhase;
use PlainSimple\Cloudflare\Responses\ActionResponse;
use PlainSimple\Cloudflare\Responses\EntityResponse;
use PlainSimple\Cloudflare\Responses\ListResponse;
use PlainSimple\Cloudflare\ValueObjects\RulePosition;
use PlainSimple\Cloudflare\ValueObjects\RulesetListQuery;
use PlainSimple\Cloudflare\ValueObjects\RulesetScope;
use Psr\Http\Message\ResponseInterface;

final class RulesetsTest extends TestCase
{
    private AdapterInterface&MockObject $adapter;
    private Rulesets $endpoint;

    protected function setUp(): void
    {
        $this->adapter = $this->createMock(AdapterInterface::class);
        $this->endpoint = new Rulesets($this->adapter);
    }

    public function testListsAccountRulesetsWithCursorAndOmittedRules(): void
    {
        $scope = RulesetScope::account('account/id');
        $this->adapter->expects($this->once())
            ->method('get')
            ->with('/accounts/account%2Fid/rulesets', [
                'cursor' => 'cursor-1',
                'per_page' => 25,
            ])
            ->willReturn($this->listResponse('cursor-2'));

        $response = $this->endpoint->list($scope, new RulesetListQuery('cursor-1', 25));
        $ruleset = $response->getItems()[0];

        $this->assertInstanceOf(ListResponse::class, $response);
        $this->assertInstanceOf(Ruleset::class, $ruleset);
        $this->assertFalse($ruleset->hasRules());
        $this->assertNull($ruleset->getRules());
        $this->assertSame('cursor-2', $response->getNextCursor());
    }

    public function testGetsLatestZoneRulesetWithHydratedRules(): void
    {
        $expression = '(http.request.uri.path eq "\\private\\\"path\"")';
        $scope = RulesetScope::zone('zone/id');
        $this->adapter->expects($this->once())
            ->method('get')
            ->with('/zones/zone%2Fid/rulesets/ruleset%2Fid')
            ->willReturn($this->entityResponse([
                ...$this->rulesetData(),
                'rules' => [[
                    'id' => 'rule-id',
                    'version' => '1',
                    'action' => 'block',
                    'expression' => $expression,
                    'enabled' => true,
                ]],
            ]));

        $response = $this->endpoint->get($scope, 'ruleset/id');
        $rules = $response->getEntity()->getRules();

        $this->assertInstanceOf(EntityResponse::class, $response);
        $this->assertCount(1, $rules);
        $this->assertInstanceOf(Rule::class, $rules[0]);
        $this->assertSame($expression, $rules[0]->getExpression());
    }

    public function testCreatesRulesetWithCreatePayload(): void
    {
        $rule = Rule::makeFromCloudflareData([
            'id' => 'excluded-id',
            'categories' => ['excluded-category'],
            'action' => RuleAction::Block->value,
            'expression' => 'http.host eq "example.com"',
            'enabled' => true,
        ]);
        $ruleset = Ruleset::forCreate(
            'Custom WAF',
            RulesetKind::Custom,
            RulesetPhase::HttpRequestFirewallCustom,
            'Description',
            [$rule],
        );
        $scope = RulesetScope::zone('zone-id');
        $this->adapter->expects($this->once())
            ->method('post')
            ->with('/zones/zone-id/rulesets', [
                'name' => 'Custom WAF',
                'description' => 'Description',
                'kind' => 'custom',
                'phase' => 'http_request_firewall_custom',
                'rules' => [[
                    'action' => 'block',
                    'expression' => 'http.host eq "example.com"',
                    'enabled' => true,
                ]],
            ])
            ->willReturn($this->entityResponse($this->rulesetData()));

        $this->assertInstanceOf(EntityResponse::class, $this->endpoint->create($scope, $ruleset));
    }

    public function testRulesetPutSupportsDescriptionOnly(): void
    {
        $ruleset = Ruleset::makeFromCloudflareData(['description' => 'Description only']);
        $scope = RulesetScope::zone('zone-id');
        $this->adapter->expects($this->once())
            ->method('put')
            ->with('/zones/zone-id/rulesets/ruleset-id', [
                'description' => 'Description only',
            ])
            ->willReturn($this->entityResponse($this->rulesetData()));

        $this->assertInstanceOf(
            EntityResponse::class,
            $this->endpoint->replace($scope, 'ruleset-id', $ruleset),
        );
    }

    public function testRulesetPutRejectsEmptyWritablePayload(): void
    {
        $ruleset = Ruleset::makeFromCloudflareData([
            'id' => 'ruleset-id',
            'description' => null,
        ]);
        $scope = RulesetScope::zone('zone-id');
        $this->adapter->expects($this->never())->method('put');

        $this->expectException(InvalidArgumentException::class);

        $this->endpoint->replace($scope, 'ruleset-id', $ruleset);
    }

    public function testReplacesRulesetWithCompleteWritableState(): void
    {
        $ruleset = Ruleset::makeFromCloudflareData([
            ...$this->rulesetData(),
            'rules' => [[
                'id' => 'excluded-id',
                'version' => '2',
                'categories' => ['excluded-category'],
                'ref' => 'stable-ref',
                'action' => 'block',
                'expression' => 'true',
                'position' => ['before' => 'another-rule'],
            ]],
        ]);
        $ruleset->setDescription('Replaced');
        $scope = RulesetScope::account('account-id');
        $this->adapter->expects($this->once())
            ->method('put')
            ->with('/accounts/account-id/rulesets/ruleset-id', [
                'description' => 'Replaced',
                'rules' => [[
                    'ref' => 'stable-ref',
                    'action' => 'block',
                    'expression' => 'true',
                ]],
            ])
            ->willReturn($this->entityResponse($this->rulesetData()));

        $this->assertInstanceOf(
            EntityResponse::class,
            $this->endpoint->replace($scope, 'ruleset-id', $ruleset),
        );
    }

    public function testDeletesRulesetAsActionResponse(): void
    {
        $scope = RulesetScope::zone('zone-id');
        $this->adapter->expects($this->once())
            ->method('delete')
            ->with('/zones/zone-id/rulesets/ruleset-id')
            ->willReturn($this->actionResponse(['id' => 'ruleset-id']));

        $response = $this->endpoint->delete($scope, 'ruleset-id');

        $this->assertInstanceOf(ActionResponse::class, $response);
        $this->assertSame(['id' => 'ruleset-id'], $response->getResult());
    }

    public function testGetsEntrypoint(): void
    {
        $scope = RulesetScope::zone('zone-id');
        $this->adapter->expects($this->once())
            ->method('get')
            ->with('/zones/zone-id/rulesets/phases/future%2Fphase/entrypoint')
            ->willReturn($this->entityResponse($this->rulesetData()));

        $response = $this->endpoint->getEntrypoint($scope, 'future/phase');

        $this->assertSame('ruleset-id', $response->getEntity()->getId());
    }

    public function testEntrypointPutSupportsDescriptionOnly(): void
    {
        $ruleset = Ruleset::makeFromCloudflareData([
            'description' => 'Entrypoint',
        ]);
        $scope = RulesetScope::account('account-id');
        $this->adapter->expects($this->once())
            ->method('put')
            ->with('/accounts/account-id/rulesets/phases/http_ratelimit/entrypoint', [
                'description' => 'Entrypoint',
            ])
            ->willReturn($this->entityResponse($this->rulesetData()));

        $this->assertInstanceOf(
            EntityResponse::class,
            $this->endpoint->replaceEntrypoint($scope, RulesetPhase::HttpRatelimit, $ruleset),
        );
    }

    public function testListsEntrypointVersionsWithoutQuery(): void
    {
        $scope = RulesetScope::zone('zone-id');
        $this->adapter->expects($this->once())
            ->method('get')
            ->with('/zones/zone-id/rulesets/phases/http_request_firewall_managed/entrypoint/versions')
            ->willReturn($this->listResponse('cursor-b'));

        $response = $this->endpoint->listEntrypointVersions(
            $scope,
            RulesetPhase::HttpRequestFirewallManaged,
        );

        $this->assertSame('cursor-b', $response->getNextCursor());
    }

    public function testGetsEntrypointVersion(): void
    {
        $scope = RulesetScope::account('account-id');
        $this->adapter->expects($this->once())
            ->method('get')
            ->with('/accounts/account-id/rulesets/phases/http_request_cache_settings/entrypoint/versions/7')
            ->willReturn($this->entityResponse($this->rulesetData()));

        $response = $this->endpoint->getEntrypointVersion(
            $scope,
            RulesetPhase::HttpRequestCacheSettings,
            '7',
        );

        $this->assertInstanceOf(Ruleset::class, $response->getEntity());
    }

    public function testListsRulesetVersionsWithoutQuery(): void
    {
        $scope = RulesetScope::zone('zone-id');
        $this->adapter->expects($this->once())
            ->method('get')
            ->with('/zones/zone-id/rulesets/ruleset-id/versions')
            ->willReturn($this->listResponse('cursor-b'));

        $response = $this->endpoint->listVersions(
            $scope,
            'ruleset-id',
        );

        $this->assertInstanceOf(ListResponse::class, $response);
        $this->assertSame('cursor-b', $response->getNextCursor());
    }

    public function testGetsRulesetVersion(): void
    {
        $scope = RulesetScope::account('account-id');
        $this->adapter->expects($this->once())
            ->method('get')
            ->with('/accounts/account-id/rulesets/ruleset%2Fid/versions/3%2Fblue')
            ->willReturn($this->entityResponse($this->rulesetData()));

        $this->assertInstanceOf(
            EntityResponse::class,
            $this->endpoint->getVersion($scope, 'ruleset/id', '3/blue'),
        );
    }

    public function testGetsZoneRulesetVersionByEncodedTagWithHydratedRules(): void
    {
        $scope = RulesetScope::zone('zone/id');
        $expression = 'cf.zone.plan eq "ENT"';
        $this->adapter->expects($this->once())
            ->method('get')
            ->with(
                '/zones/zone%2Fid/rulesets/ruleset%2Fid/versions/3%2Fblue'
                . '/by_tag/wordpress%2Fmanaged%3Flevel%3Dhigh%23current',
            )
            ->willReturn($this->entityResponse([
                ...$this->rulesetData(),
                'rules' => [[
                    'id' => 'managed-rule-id',
                    'version' => '3',
                    'action' => 'block',
                    'expression' => $expression,
                    'enabled' => true,
                ]],
            ]));

        $response = $this->endpoint->getVersionByTag(
            $scope,
            'ruleset/id',
            '3/blue',
            'wordpress/managed?level=high#current',
        );
        $rules = $response->getEntity()->getRules();

        $this->assertInstanceOf(EntityResponse::class, $response);
        $this->assertCount(1, $rules);
        $this->assertInstanceOf(Rule::class, $rules[0]);
        $this->assertSame($expression, $rules[0]->getExpression());
    }

    public function testGetsAccountRulesetVersionByTag(): void
    {
        $scope = RulesetScope::account('account-id');
        $this->adapter->expects($this->once())
            ->method('get')
            ->with('/accounts/account-id/rulesets/ruleset-id/versions/7/by_tag/cloudflare-managed')
            ->willReturn($this->entityResponse([
                ...$this->rulesetData(),
                'rules' => [],
            ]));

        $response = $this->endpoint->getVersionByTag(
            $scope,
            'ruleset-id',
            '7',
            'cloudflare-managed',
        );

        $this->assertInstanceOf(Ruleset::class, $response->getEntity());
    }

    public function testVersionByTagRejectsEmptyTag(): void
    {
        $scope = RulesetScope::zone('zone-id');
        $this->adapter->expects($this->never())->method('get');
        $this->expectException(InvalidArgumentException::class);

        $this->endpoint->getVersionByTag($scope, 'ruleset-id', '1', " \t");
    }

    public function testDeletesRulesetVersionAsActionResponse(): void
    {
        $scope = RulesetScope::zone('zone-id');
        $this->adapter->expects($this->once())
            ->method('delete')
            ->with('/zones/zone-id/rulesets/ruleset-id/versions/2')
            ->willReturn($this->actionResponse(null));

        $this->assertInstanceOf(
            ActionResponse::class,
            $this->endpoint->deleteVersion($scope, 'ruleset-id', '2'),
        );
    }

    public function testCreatesRuleWithFlexibleParametersAndPosition(): void
    {
        $parameters = [
            'id' => 'managed-waf-id',
            'overrides' => [
                'categories' => [['category' => 'wordpress', 'enabled' => false]],
                'rules' => [['id' => 'managed-rule-id', 'action' => 'block']],
            ],
        ];
        $rule = Rule::makeFromCloudflareData([
            'id' => 'excluded-id',
            'version' => '1',
            'categories' => ['excluded'],
            'ref' => 'managed-waf',
            'action' => 'execute',
            'expression' => 'true',
            'action_parameters' => $parameters,
            'position' => ['after' => 'ignored-position'],
        ]);
        $scope = RulesetScope::zone('zone-id');
        $this->adapter->expects($this->once())
            ->method('post')
            ->with('/zones/zone-id/rulesets/ruleset-id/rules', [
                'ref' => 'managed-waf',
                'action' => 'execute',
                'expression' => 'true',
                'action_parameters' => $parameters,
                'position' => ['before' => 'first-rule'],
            ])
            ->willReturn($this->entityResponse([
                ...$this->rulesetData(),
                'rules' => [],
            ]));

        $response = $this->endpoint->createRule(
            $scope,
            'ruleset-id',
            $rule,
            RulePosition::before('first-rule'),
        );

        $this->assertInstanceOf(EntityResponse::class, $response);
    }

    public function testUpdatesRuleWithCompletePresentDefinitionAndPosition(): void
    {
        $rule = Rule::makeFromCloudflareData([
            'id' => 'rule-id',
            'version' => '1',
            'categories' => ['server-only'],
            'description' => 'Original',
            'action' => 'block',
            'expression' => 'true',
            'enabled' => true,
        ]);
        $rule->setDescription('Changed');
        $rule->setEnabled(false);
        $scope = RulesetScope::account('account-id');
        $this->adapter->expects($this->once())
            ->method('patch')
            ->with('/accounts/account-id/rulesets/ruleset-id/rules/rule-id', [
                'description' => 'Changed',
                'action' => 'block',
                'expression' => 'true',
                'enabled' => false,
                'position' => ['index' => 2],
            ])
            ->willReturn($this->entityResponse([
                ...$this->rulesetData(),
                'rules' => [],
            ]));

        $response = $this->endpoint->updateRule(
            $scope,
            'ruleset-id',
            'rule-id',
            $rule,
            RulePosition::index(2),
        );

        $this->assertInstanceOf(EntityResponse::class, $response);
    }

    public function testDefinitionPatchRejectsIncompletePresentState(): void
    {
        $rule = Rule::makeFromCloudflareData(['description' => 'Incomplete']);
        $scope = RulesetScope::zone('zone-id');
        $this->adapter->expects($this->never())->method('patch');

        $this->expectException(InvalidArgumentException::class);

        $this->endpoint->updateRule($scope, 'ruleset-id', 'rule-id', $rule);
    }

    public function testRepositionsRuleWithPatch(): void
    {
        $scope = RulesetScope::zone('zone-id');
        $this->adapter->expects($this->once())
            ->method('patch')
            ->with('/zones/zone-id/rulesets/ruleset-id/rules/rule-id', [
                'position' => ['after' => 'other-rule'],
            ])
            ->willReturn($this->entityResponse([
                ...$this->rulesetData(),
                'rules' => [],
            ]));

        $response = $this->endpoint->updateRule(
            $scope,
            'ruleset-id',
            'rule-id',
            new Rule(),
            RulePosition::after('other-rule'),
        );

        $this->assertInstanceOf(EntityResponse::class, $response);
    }

    public function testDeletesRuleAndReturnsRuleset(): void
    {
        $scope = RulesetScope::account('account-id');
        $this->adapter->expects($this->once())
            ->method('delete')
            ->with('/accounts/account-id/rulesets/ruleset-id/rules/rule%2Fid')
            ->willReturn($this->entityResponse([
                ...$this->rulesetData(),
                'rules' => [],
            ]));

        $response = $this->endpoint->deleteRule($scope, 'ruleset-id', 'rule/id');

        $this->assertInstanceOf(EntityResponse::class, $response);
        $this->assertInstanceOf(Ruleset::class, $response->getEntity());
    }

    #[DataProvider('emptyPathSegments')]
    public function testRejectsEmptyPathSegments(string $segment): void
    {
        $scope = RulesetScope::zone('zone-id');
        $this->adapter->expects($this->never())->method('get');
        $this->adapter->expects($this->never())->method('delete');
        $this->expectException(InvalidArgumentException::class);

        match ($segment) {
            'ruleset' => $this->endpoint->get($scope, ' '),
            'rule' => $this->endpoint->deleteRule($scope, 'ruleset-id', ''),
            'version' => $this->endpoint->getVersion($scope, 'ruleset-id', "\t"),
            'phase' => $this->endpoint->getEntrypoint($scope, "\n"),
            default => throw new LogicException('Unknown path segment case.'),
        };
    }

    /** @return iterable<string, array{string}> */
    public static function emptyPathSegments(): iterable
    {
        yield 'ruleset ID' => ['ruleset'];
        yield 'rule ID' => ['rule'];
        yield 'version' => ['version'];
        yield 'phase' => ['phase'];
    }

    /** @param array<string, mixed> $result */
    private function entityResponse(array $result): ResponseInterface
    {
        return $this->jsonResponse([
            'success' => true,
            'errors' => [],
            'messages' => [],
            'result' => $result,
        ]);
    }

    private function listResponse(string $after): ResponseInterface
    {
        return $this->jsonResponse([
            'success' => true,
            'errors' => [],
            'messages' => [],
            'result' => [$this->rulesetData()],
            'result_info' => [
                'count' => 1,
                'cursors' => ['after' => $after],
            ],
        ]);
    }

    private function actionResponse(mixed $result): ResponseInterface
    {
        return $this->jsonResponse([
            'success' => true,
            'errors' => [],
            'messages' => [],
            'result' => $result,
        ]);
    }

    /** @param array<string, mixed> $envelope */
    private function jsonResponse(array $envelope): ResponseInterface
    {
        return new Response(
            200,
            ['Content-Type' => 'application/json'],
            json_encode($envelope, JSON_THROW_ON_ERROR),
        );
    }

    /** @return array<string, mixed> */
    private function rulesetData(): array
    {
        return [
            'id' => 'ruleset-id',
            'name' => 'Ruleset',
            'description' => 'Description',
            'kind' => 'custom',
            'phase' => 'http_request_firewall_custom',
            'version' => '1',
            'last_updated' => '2026-07-22T12:00:00Z',
        ];
    }
}
