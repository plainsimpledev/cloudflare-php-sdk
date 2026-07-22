<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use PlainSimple\Cloudflare\Entities\Rule;
use PlainSimple\Cloudflare\Enums\RuleAction;
use PlainSimple\Cloudflare\ValueObjects\RulePosition;

final class RuleTest extends TestCase
{
    public function testManagedWafOverridesRemainFlexibleAndWritable(): void
    {
        $parameters = [
            'id' => 'managed-waf-id',
            'overrides' => [
                'enabled' => true,
                'action' => 'log',
                'categories' => [[
                    'category' => 'wordpress',
                    'enabled' => false,
                ]],
                'rules' => [[
                    'id' => 'rule-1',
                    'action' => 'block',
                    'score_threshold' => 40,
                ]],
            ],
        ];
        $rule = Rule::makeFromCloudflareData([
            'id' => 'server-id',
            'version' => '4',
            'last_updated' => '2026-07-22T12:00:00Z',
            'categories' => ['server-only'],
            'action' => 'execute',
            'expression' => 'true',
            'action_parameters' => $parameters,
        ]);

        $this->assertSame(RuleAction::Execute, $rule->getKnownAction());
        $this->assertSame($parameters, $rule->getActionParameters());
        $this->assertSame([
            'action' => 'execute',
            'expression' => 'true',
            'action_parameters' => $parameters,
        ], $rule->toCreatePayload());
    }

    public function testCustomWafRateLimitFieldsRoundTrip(): void
    {
        $ratelimit = [
            'characteristics' => ['cf.colo.id', 'ip.src'],
            'period' => 60,
            'requests_per_period' => 100,
            'mitigation_timeout' => 600,
            'counting_expression' => '(http.request.uri.path contains "\\api\\\"")',
            'requests_to_origin' => true,
        ];
        $rule = Rule::makeFromCloudflareData([
            'action' => 'block',
            'expression' => 'http.request.uri.path starts_with "/api/"',
            'enabled' => true,
            'ratelimit' => $ratelimit,
            'logging' => ['enabled' => true, 'future_option' => 'kept'],
            'exposed_credential_check' => [
                'username_expression' => 'lookup_json_string(http.request.body.raw, "user")',
                'password_expression' => 'lookup_json_string(http.request.body.raw, "pass")',
            ],
        ]);

        $this->assertSame($ratelimit, $rule->getRatelimit());
        $this->assertSame($ratelimit['counting_expression'], $rule->toCreatePayload()['ratelimit']['counting_expression']);
        $this->assertSame('kept', $rule->getLogging()['future_option']);
    }

    public function testCacheActionParametersPreserveNestedMaps(): void
    {
        $parameters = [
            'cache' => true,
            'edge_ttl' => [
                'mode' => 'override_origin',
                'default' => 3600,
                'status_code_ttl' => [[
                    'status_code_range' => ['from' => 200, 'to' => 299],
                    'value' => 7200,
                ]],
            ],
            'cache_key' => [
                'custom_key' => [
                    'query_string' => ['include' => ['id', 'lang']],
                ],
            ],
        ];
        $rule = Rule::makeFromCloudflareData([
            'action' => 'set_cache_settings',
            'expression' => 'http.request.uri.path matches "^/assets/.*\\.(css|js)$"',
            'action_parameters' => $parameters,
        ]);

        $this->assertSame(RuleAction::SetCacheSettings, $rule->getKnownAction());
        $this->assertSame($parameters, $rule->getActionParameters());
        $this->assertSame($parameters, $rule->toCreatePayload()['action_parameters']);
    }

    public function testOperationPayloadContainsCompletePresentDefinitionWithoutPosition(): void
    {
        $rule = Rule::makeFromCloudflareData([
            'id' => 'rule-id',
            'version' => '1',
            'categories' => ['server-only'],
            'description' => 'Before',
            'action' => 'block',
            'expression' => 'true',
            'enabled' => true,
        ]);
        $rule->setDescription('After');
        $rule->setPosition(RulePosition::after('other-rule'));

        $this->assertSame([
            'description' => 'After',
            'action' => 'block',
            'expression' => 'true',
            'enabled' => true,
        ], $rule->toOperationPayload());
        $this->assertSame([
            'description' => 'After',
        ], $rule->toPatchPayload());
        $this->assertSame(['after' => 'other-rule'], $rule->getPosition()->toArray());
    }

    public function testRequestPayloadsOmitNullWritableFields(): void
    {
        $rule = Rule::makeFromCloudflareData([
            'action' => null,
            'description' => null,
            'expression' => 'true',
            'enabled' => null,
        ]);

        $this->assertNull($rule->getAction());
        $this->assertSame(['expression' => 'true'], $rule->toCreatePayload());
        $this->assertSame(['expression' => 'true'], $rule->toOperationPayload());
    }

    public function testUnknownActionRemainsReadable(): void
    {
        $rule = Rule::makeFromCloudflareData(['action' => 'future_action']);

        $this->assertSame('future_action', $rule->getAction());
        $this->assertNull($rule->getKnownAction());
    }
}
