<?php

declare(strict_types=1);

namespace PlainSimple\Cloudflare\Endpoints;

use InvalidArgumentException;
use JsonException;
use PlainSimple\Cloudflare\Entities\ZoneSetting;
use PlainSimple\Cloudflare\Exceptions\ErrorResponseException;
use PlainSimple\Cloudflare\Exceptions\InvalidClassException;
use PlainSimple\Cloudflare\Responses\EntityResponse;
use PlainSimple\Cloudflare\Responses\ListResponse;
use PlainSimple\Cloudflare\Utilities\PathSegment;

class ZoneSettings extends AbstractEndpoint
{
    private const string SSL_RECOMMENDER_ID = 'ssl_recommender';

    /**
     * @return ListResponse<ZoneSetting>
     * @throws ErrorResponseException
     * @throws InvalidClassException
     * @throws JsonException
     */
    public function list(string $zoneId): ListResponse
    {
        $zoneId = PathSegment::encode($zoneId, 'Zone ID');
        $response = $this->adapter->get('/zones/' . $zoneId . '/settings');

        return $this->makeListResponse($response, ZoneSetting::class);
    }

    /**
     * @return EntityResponse<ZoneSetting>
     * @throws ErrorResponseException
     * @throws InvalidClassException
     * @throws JsonException
     */
    public function get(string $zoneId, string $settingId): EntityResponse
    {
        $zoneId = PathSegment::encode($zoneId, 'Zone ID');
        $settingId = PathSegment::encode($settingId, 'Zone setting ID');
        $response = $this->adapter->get('/zones/' . $zoneId . '/settings/' . $settingId);

        return $this->makeEntityResponse($response, ZoneSetting::class);
    }

    /**
     * @return EntityResponse<ZoneSetting>
     * @throws ErrorResponseException
     * @throws InvalidClassException
     * @throws JsonException
     */
    public function update(string $zoneId, ZoneSetting $setting): EntityResponse
    {
        $zoneId = PathSegment::encode($zoneId, 'Zone ID');
        $id = $this->requireId($setting);
        $patch = $this->requireUpdatePatch($id, $setting);
        $settingId = PathSegment::encode($id, 'Zone setting ID');

        $response = $this->adapter->patch('/zones/' . $zoneId . '/settings/' . $settingId, $patch);

        return $this->makeEntityResponse($response, ZoneSetting::class);
    }

    /**
     * @param list<ZoneSetting> $settings
     * @return ListResponse<ZoneSetting>
     * @throws ErrorResponseException
     * @throws InvalidClassException
     * @throws JsonException
     */
    public function updateMany(string $zoneId, array $settings): ListResponse
    {
        $zoneId = PathSegment::encode($zoneId, 'Zone ID');
        if ($settings === []) {
            throw new InvalidArgumentException('Zone settings update requires at least one setting.');
        }

        $payload = [];
        foreach ($settings as $setting) {
            $id = $this->requireId($setting);
            $patch = $this->requireUpdatePatch($id, $setting);

            $payload[] = ['id' => $id] + $patch;
        }

        $response = $this->adapter->patch('/zones/' . $zoneId . '/settings', $payload);

        return $this->makeListResponse($response, ZoneSetting::class);
    }

    private function requireId(ZoneSetting $setting): string
    {
        if (!$setting->hasAttribute('id')) {
            throw new InvalidArgumentException('Zone setting ID is required.');
        }

        $id = $setting->getId();
        PathSegment::encode($id, 'Zone setting ID');

        return $id;
    }

    /** @return array<string, mixed> */
    private function requireUpdatePatch(string $id, ZoneSetting $setting): array
    {
        $patch = $setting->toPatchPayload();
        $hasValue = array_key_exists('value', $patch);
        $hasEnabled = array_key_exists('enabled', $patch);

        if ($hasValue === $hasEnabled) {
            throw new InvalidArgumentException('Zone setting update requires exactly one of value or enabled.');
        }
        if (($hasValue && $patch['value'] === null) || ($hasEnabled && $patch['enabled'] === null)) {
            throw new InvalidArgumentException('Zone setting update value or enabled must not be null.');
        }
        if (($id === self::SSL_RECOMMENDER_ID) !== $hasEnabled) {
            throw new InvalidArgumentException(
                'ssl_recommender updates must use enabled; all other zone setting updates must use value.',
            );
        }

        return $patch;
    }
}
