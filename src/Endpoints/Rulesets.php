<?php

declare(strict_types=1);

namespace PlainSimple\Cloudflare\Endpoints;

use InvalidArgumentException;
use PlainSimple\Cloudflare\Entities\Rule;
use PlainSimple\Cloudflare\Entities\Ruleset;
use PlainSimple\Cloudflare\Enums\RulesetPhase;
use PlainSimple\Cloudflare\Responses\ActionResponse;
use PlainSimple\Cloudflare\Responses\EntityResponse;
use PlainSimple\Cloudflare\Responses\ListResponse;
use PlainSimple\Cloudflare\Utilities\PathSegment;
use PlainSimple\Cloudflare\ValueObjects\RulePosition;
use PlainSimple\Cloudflare\ValueObjects\RulesetListQuery;
use PlainSimple\Cloudflare\ValueObjects\RulesetScope;

class Rulesets extends AbstractEndpoint
{
    /** @return ListResponse<Ruleset> */
    public function list(RulesetScope $scope, ?RulesetListQuery $query = null): ListResponse
    {
        $response = $this->adapter->get($this->basePath($scope), $query?->toArray() ?? []);

        return $this->makeListResponse($response, Ruleset::class);
    }

    /** @return EntityResponse<Ruleset> */
    public function get(RulesetScope $scope, string $rulesetId): EntityResponse
    {
        $response = $this->adapter->get($this->rulesetPath($scope, $rulesetId));

        return $this->makeEntityResponse($response, Ruleset::class);
    }

    /** @return EntityResponse<Ruleset> */
    public function create(RulesetScope $scope, Ruleset $ruleset): EntityResponse
    {
        $payload = array_filter(
            $ruleset->toCreatePayload(),
            static fn (mixed $value): bool => $value !== null,
        );
        foreach (['name', 'kind', 'phase'] as $requiredField) {
            if (!isset($payload[$requiredField])) {
                throw new InvalidArgumentException('Ruleset create requires name, kind, and phase.');
            }
        }
        $this->validateEmbeddedRules($payload);
        $response = $this->adapter->post($this->basePath($scope), $payload);

        return $this->makeEntityResponse($response, Ruleset::class);
    }

    /**
     * Sends a PUT containing the explicitly present writable ruleset fields.
     *
     * @return EntityResponse<Ruleset>
     */
    public function replace(RulesetScope $scope, string $rulesetId, Ruleset $ruleset): EntityResponse
    {
        $response = $this->adapter->put(
            $this->rulesetPath($scope, $rulesetId),
            $this->rulesetPutPayload($ruleset),
        );

        return $this->makeEntityResponse($response, Ruleset::class);
    }

    public function delete(RulesetScope $scope, string $rulesetId): ActionResponse
    {
        return $this->makeActionResponse($this->adapter->delete($this->rulesetPath($scope, $rulesetId)));
    }

    /** @return EntityResponse<Ruleset> */
    public function getEntrypoint(RulesetScope $scope, RulesetPhase|string $phase): EntityResponse
    {
        $response = $this->adapter->get($this->entrypointPath($scope, $phase));

        return $this->makeEntityResponse($response, Ruleset::class);
    }

    /**
     * Sends a PUT containing the explicitly present writable ruleset fields.
     *
     * @return EntityResponse<Ruleset>
     */
    public function replaceEntrypoint(
        RulesetScope $scope,
        RulesetPhase|string $phase,
        Ruleset $ruleset,
    ): EntityResponse {
        $response = $this->adapter->put(
            $this->entrypointPath($scope, $phase),
            $this->rulesetPutPayload($ruleset),
        );

        return $this->makeEntityResponse($response, Ruleset::class);
    }

    /** @return ListResponse<Ruleset> */
    public function listEntrypointVersions(
        RulesetScope $scope,
        RulesetPhase|string $phase,
    ): ListResponse {
        $response = $this->adapter->get($this->entrypointPath($scope, $phase) . '/versions');

        return $this->makeListResponse($response, Ruleset::class);
    }

    /** @return EntityResponse<Ruleset> */
    public function getEntrypointVersion(
        RulesetScope $scope,
        RulesetPhase|string $phase,
        string $version,
    ): EntityResponse {
        $response = $this->adapter->get(
            $this->entrypointPath($scope, $phase) . '/versions/' . PathSegment::encode($version, 'Ruleset version'),
        );

        return $this->makeEntityResponse($response, Ruleset::class);
    }

    /** @return ListResponse<Ruleset> */
    public function listVersions(
        RulesetScope $scope,
        string $rulesetId,
    ): ListResponse {
        $response = $this->adapter->get($this->rulesetPath($scope, $rulesetId) . '/versions');

        return $this->makeListResponse($response, Ruleset::class);
    }

    /** @return EntityResponse<Ruleset> */
    public function getVersion(RulesetScope $scope, string $rulesetId, string $version): EntityResponse
    {
        $response = $this->adapter->get(
            $this->rulesetPath($scope, $rulesetId) . '/versions/' . PathSegment::encode($version, 'Ruleset version'),
        );

        return $this->makeEntityResponse($response, Ruleset::class);
    }

    /** @return EntityResponse<Ruleset> */
    public function getVersionByTag(
        RulesetScope $scope,
        string $rulesetId,
        string $version,
        string $ruleTag,
    ): EntityResponse {
        $response = $this->adapter->get(
            $this->rulesetPath($scope, $rulesetId)
            . '/versions/' . PathSegment::encode($version, 'Ruleset version')
            . '/by_tag/' . PathSegment::encode($ruleTag, 'Rule tag'),
        );

        return $this->makeEntityResponse($response, Ruleset::class);
    }

    public function deleteVersion(RulesetScope $scope, string $rulesetId, string $version): ActionResponse
    {
        $response = $this->adapter->delete(
            $this->rulesetPath($scope, $rulesetId) . '/versions/' . PathSegment::encode($version, 'Ruleset version'),
        );

        return $this->makeActionResponse($response);
    }

    /** @return EntityResponse<Ruleset> */
    public function createRule(
        RulesetScope $scope,
        string $rulesetId,
        Rule $rule,
        ?RulePosition $position = null,
    ): EntityResponse {
        $payload = $rule->toCreatePayload();
        $this->validateRuleDefinition($payload);
        if ($position !== null) {
            $payload['position'] = $position->toArray();
        }
        $response = $this->adapter->post($this->rulesPath($scope, $rulesetId), $payload);

        return $this->makeEntityResponse($response, Ruleset::class);
    }

    /** @return EntityResponse<Ruleset> */
    public function updateRule(
        RulesetScope $scope,
        string $rulesetId,
        string $ruleId,
        Rule $rule,
        ?RulePosition $position = null,
    ): EntityResponse {
        $payload = $rule->toOperationPayload();
        if ($payload !== []) {
            $this->validateRuleDefinition($payload);
        } elseif ($position === null) {
            throw new InvalidArgumentException('Rule update requires a complete definition or position.');
        }
        if ($position !== null) {
            $payload['position'] = $position->toArray();
        }
        $response = $this->adapter->patch(
            $this->rulesPath($scope, $rulesetId) . '/' . PathSegment::encode($ruleId, 'Rule ID'),
            $payload,
        );

        return $this->makeEntityResponse($response, Ruleset::class);
    }

    /** @return EntityResponse<Ruleset> */
    public function deleteRule(RulesetScope $scope, string $rulesetId, string $ruleId): EntityResponse
    {
        $response = $this->adapter->delete(
            $this->rulesPath($scope, $rulesetId) . '/' . PathSegment::encode($ruleId, 'Rule ID'),
        );

        return $this->makeEntityResponse($response, Ruleset::class);
    }

    private function basePath(RulesetScope $scope): string
    {
        return '/' . $scope->path() . '/rulesets';
    }

    private function rulesetPath(RulesetScope $scope, string $rulesetId): string
    {
        return $this->basePath($scope) . '/' . PathSegment::encode($rulesetId, 'Ruleset ID');
    }

    private function rulesPath(RulesetScope $scope, string $rulesetId): string
    {
        return $this->rulesetPath($scope, $rulesetId) . '/rules';
    }

    private function entrypointPath(RulesetScope $scope, RulesetPhase|string $phase): string
    {
        $value = $phase instanceof RulesetPhase ? $phase->value : $phase;

        return $this->basePath($scope) . '/phases/' . PathSegment::encode($value, 'Ruleset phase') . '/entrypoint';
    }

    /** @return array<string, mixed> */
    private function rulesetPutPayload(Ruleset $ruleset): array
    {
        $payload = array_filter(
            $ruleset->toReplacePayload(),
            static fn (mixed $value): bool => $value !== null,
        );
        if ($payload === []) {
            throw new InvalidArgumentException('Ruleset PUT requires at least one writable field.');
        }
        $this->validateEmbeddedRules($payload);

        return $payload;
    }

    /** @param array<string, mixed> $payload */
    private function validateEmbeddedRules(array $payload): void
    {
        if (!is_array($payload['rules'] ?? null)) {
            return;
        }

        foreach ($payload['rules'] as $rule) {
            if (!is_array($rule)) {
                throw new InvalidArgumentException('Ruleset rules must contain rule definitions.');
            }
            $this->validateRuleDefinition($rule);
        }
    }

    /** @param array<string, mixed> $payload */
    private function validateRuleDefinition(array $payload): void
    {
        foreach (['action', 'expression'] as $requiredField) {
            if (!is_string($payload[$requiredField] ?? null) || $payload[$requiredField] === '') {
                throw new InvalidArgumentException('Rule definition requires action and expression.');
            }
        }
    }
}
