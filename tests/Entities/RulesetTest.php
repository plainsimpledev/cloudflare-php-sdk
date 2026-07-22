<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use PlainSimple\Cloudflare\Entities\Rule;
use PlainSimple\Cloudflare\Entities\Ruleset;
use PlainSimple\Cloudflare\Enums\RuleAction;
use PlainSimple\Cloudflare\Enums\RulesetKind;
use PlainSimple\Cloudflare\Enums\RulesetPhase;

final class RulesetTest extends TestCase
{
    public function testDistinguishesOmittedRulesFromEmptyRules(): void
    {
        $summary = Ruleset::makeFromCloudflareData($this->summaryData());
        $empty = Ruleset::makeFromCloudflareData([
            ...$this->summaryData(),
            'rules' => [],
        ]);

        $this->assertFalse($summary->hasRules());
        $this->assertNull($summary->getRules());
        $this->assertTrue($empty->hasRules());
        $this->assertSame([], $empty->getRules());
    }

    public function testHydratesRulesAndPreservesExpressionBytes(): void
    {
        $expression = '(http.request.uri.path eq "\\api\\\"quoted\"")';
        $ruleset = Ruleset::makeFromCloudflareData([
            ...$this->summaryData(),
            'rules' => [[
                'id' => 'rule-id',
                'version' => '3',
                'action' => 'execute',
                'expression' => $expression,
                'enabled' => true,
                'action_parameters' => [
                    'id' => 'managed-ruleset-id',
                    'overrides' => [
                        'categories' => [[
                            'category' => 'wordpress',
                            'action' => 'block',
                        ]],
                        'rules' => [[
                            'id' => 'managed-rule-id',
                            'enabled' => false,
                        ]],
                    ],
                ],
            ]],
        ]);

        $rules = $ruleset->getRules();

        $this->assertCount(1, $rules);
        $this->assertContainsOnlyInstancesOf(Rule::class, $rules);
        $this->assertSame($expression, $rules[0]->getExpression());
        $this->assertSame($expression, $rules[0]->toCloudflareData()['expression']);
        $this->assertSame(RuleAction::Execute, $rules[0]->getKnownAction());
        $this->assertSame('managed-rule-id', $rules[0]->getActionParameters()['overrides']['rules'][0]['id']);
        $this->assertInstanceOf(DateTimeImmutable::class, $ruleset->getLastUpdated());
    }

    public function testFactoryAndPayloadAllowlistsProduceWritableState(): void
    {
        $rule = Rule::makeFromCloudflareData([
            'id' => 'server-rule-id',
            'version' => '1',
            'last_updated' => '2026-07-22T12:00:00Z',
            'categories' => ['server-category'],
            'ref' => 'stable-ref',
            'description' => 'Block admin',
            'action' => 'block',
            'expression' => 'http.request.uri.path eq "/admin"',
            'enabled' => true,
            'position' => ['after' => 'another-rule'],
        ]);
        $ruleset = Ruleset::forCreate(
            'Custom WAF',
            RulesetKind::Custom,
            RulesetPhase::HttpRequestFirewallCustom,
            'Custom protections',
            [$rule],
        );

        $this->assertSame([
            'name' => 'Custom WAF',
            'description' => 'Custom protections',
            'kind' => 'custom',
            'phase' => 'http_request_firewall_custom',
            'rules' => [[
                'ref' => 'stable-ref',
                'description' => 'Block admin',
                'action' => 'block',
                'expression' => 'http.request.uri.path eq "/admin"',
                'enabled' => true,
            ]],
        ], $ruleset->toCreatePayload());
        $this->assertSame([
            'description' => 'Custom protections',
            'rules' => [[
                'ref' => 'stable-ref',
                'description' => 'Block admin',
                'action' => 'block',
                'expression' => 'http.request.uri.path eq "/admin"',
                'enabled' => true,
            ]],
        ], $ruleset->toReplacePayload());
        $this->assertArrayNotHasKey('description', Ruleset::forCreate(
            'No description',
            RulesetKind::Custom,
            RulesetPhase::HttpRequestFirewallCustom,
        )->toCreatePayload());
    }

    public function testUnknownKindAndPhaseRemainReadable(): void
    {
        $ruleset = Ruleset::makeFromCloudflareData([
            ...$this->summaryData(),
            'kind' => 'future_kind',
            'phase' => 'http_request_future_phase',
        ]);

        $this->assertSame('future_kind', $ruleset->getKind());
        $this->assertNull($ruleset->getKnownKind());
        $this->assertSame('http_request_future_phase', $ruleset->getPhase());
        $this->assertNull($ruleset->getKnownPhase());
    }

    public function testEnumsCoverOfficialRulesetValues(): void
    {
        $this->assertCount(24, RulesetPhase::cases());
        $this->assertSame(
            ['managed', 'custom', 'root', 'zone'],
            array_column(RulesetKind::cases(), 'value'),
        );
        $this->assertCount(21, RuleAction::cases());
    }

    /** @return array<string, mixed> */
    private function summaryData(): array
    {
        return [
            'id' => 'ruleset-id',
            'name' => 'Ruleset',
            'description' => 'Description',
            'kind' => 'custom',
            'phase' => 'http_request_firewall_custom',
            'version' => '2',
            'last_updated' => '2026-07-22T12:00:00Z',
        ];
    }
}
