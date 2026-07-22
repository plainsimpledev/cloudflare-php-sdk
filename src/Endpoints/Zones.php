<?php

declare(strict_types=1);

namespace PlainSimple\Cloudflare\Endpoints;

use InvalidArgumentException;
use JsonException;
use PlainSimple\Cloudflare\Entities\Zone;
use PlainSimple\Cloudflare\Exceptions\ErrorResponseException;
use PlainSimple\Cloudflare\Exceptions\InvalidClassException;
use PlainSimple\Cloudflare\Responses\ActionResponse;
use PlainSimple\Cloudflare\Responses\EntityResponse;
use PlainSimple\Cloudflare\Responses\ListResponse;
use PlainSimple\Cloudflare\Utilities\PathSegment;
use PlainSimple\Cloudflare\ValueObjects\ZoneListQuery;
use stdClass;

class Zones extends AbstractEndpoint
{
    /**
     * @return ListResponse<Zone>
     * @throws ErrorResponseException
     * @throws InvalidClassException
     * @throws JsonException
     */
    public function list(?ZoneListQuery $query = null): ListResponse
    {
        $response = $this->adapter->get('/zones', $query?->toArray() ?? []);

        return $this->makeListResponse($response, Zone::class);
    }

    /**
     * @return EntityResponse<Zone>
     * @throws ErrorResponseException
     * @throws InvalidClassException
     * @throws JsonException
     */
    public function get(string $zoneId): EntityResponse
    {
        $response = $this->adapter->get('/zones/' . PathSegment::encode($zoneId, 'Zone ID'));

        return $this->makeEntityResponse($response, Zone::class);
    }

    /**
     * @return EntityResponse<Zone>
     * @throws ErrorResponseException
     * @throws InvalidClassException
     * @throws JsonException
     */
    public function create(Zone $zone): EntityResponse
    {
        $payload = $zone->toCreatePayload();
        $name = $payload['name'] ?? null;
        if (!is_string($name) || trim($name) === '') {
            throw new InvalidArgumentException('Zone create requires a non-empty name.');
        }

        if (!array_key_exists('account', $payload) || !is_array($payload['account'])) {
            throw new InvalidArgumentException('Zone create requires an account object.');
        }

        if ($payload['account'] === []) {
            $payload['account'] = new stdClass();
        }

        $response = $this->adapter->post('/zones', $payload);

        return $this->makeEntityResponse($response, Zone::class);
    }

    /**
     * @return EntityResponse<Zone>
     * @throws ErrorResponseException
     * @throws InvalidClassException
     * @throws JsonException
     */
    public function update(Zone $zone): EntityResponse
    {
        $zoneId = $this->requireId($zone);
        $payload = $zone->toPatchPayload();

        if (count($payload) !== 1) {
            throw new InvalidArgumentException('Zone update requires exactly one dirty writable field.');
        }

        $response = $this->adapter->patch('/zones/' . $zoneId, $payload);

        return $this->makeEntityResponse($response, Zone::class);
    }

    /**
     * @throws ErrorResponseException
     * @throws JsonException
     */
    public function delete(Zone|string $zone): ActionResponse
    {
        $response = $this->adapter->delete('/zones/' . $this->resolveId($zone));

        return $this->makeActionResponse($response);
    }

    /**
     * @throws ErrorResponseException
     * @throws JsonException
     */
    public function rerunActivationCheck(Zone|string $zone): ActionResponse
    {
        $response = $this->adapter->put('/zones/' . $this->resolveId($zone) . '/activation_check', null);

        return $this->makeActionResponse($response);
    }

    private function resolveId(Zone|string $zone): string
    {
        return $zone instanceof Zone
            ? $this->requireId($zone)
            : PathSegment::encode($zone, 'Zone ID');
    }

    private function requireId(Zone $zone): string
    {
        if (!$zone->hasAttribute('id')) {
            throw new InvalidArgumentException('Zone operation requires an id.');
        }

        return PathSegment::encode($zone->getId(), 'Zone ID');
    }
}
