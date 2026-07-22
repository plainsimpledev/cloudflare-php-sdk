<?php

declare(strict_types=1);

namespace PlainSimple\Cloudflare\Endpoints;

use InvalidArgumentException;
use PlainSimple\Cloudflare\Entities\DnsRecord;
use PlainSimple\Cloudflare\Responses\ActionResponse;
use PlainSimple\Cloudflare\Responses\EntityResponse;
use PlainSimple\Cloudflare\Responses\ListResponse;
use PlainSimple\Cloudflare\Responses\RawResponse;
use PlainSimple\Cloudflare\Utilities\PathSegment;
use PlainSimple\Cloudflare\ValueObjects\DnsBatch;
use PlainSimple\Cloudflare\ValueObjects\DnsImport;
use PlainSimple\Cloudflare\ValueObjects\DnsRecordListQuery;
use PlainSimple\Cloudflare\ValueObjects\DnsScanReview;

final class DnsRecords extends AbstractEndpoint
{
    /** @return ListResponse<DnsRecord> */
    public function list(string $zoneId, ?DnsRecordListQuery $query = null): ListResponse
    {
        $response = $this->adapter->get($this->basePath($zoneId), $query?->toArray() ?? []);

        return $this->makeListResponse($response, DnsRecord::class);
    }

    /** @return EntityResponse<DnsRecord> */
    public function get(
        string $zoneId,
        string $recordId,
        ?bool $includeShadowMetadata = null,
    ): EntityResponse {
        $response = $this->adapter->get(
            $this->basePath($zoneId) . '/' . $this->pathId($recordId, 'DNS record id'),
            $this->shadowQuery($includeShadowMetadata),
        );

        return $this->makeEntityResponse($response, DnsRecord::class);
    }

    /** @return EntityResponse<DnsRecord> */
    public function create(
        string $zoneId,
        DnsRecord $record,
        ?bool $includeShadowMetadata = null,
    ): EntityResponse {
        $response = $this->adapter->post(
            $this->withShadowQuery($this->basePath($zoneId), $includeShadowMetadata),
            $record->toCreatePayload(),
        );

        return $this->makeEntityResponse($response, DnsRecord::class);
    }

    /** @return EntityResponse<DnsRecord> */
    public function overwrite(
        string $zoneId,
        DnsRecord $record,
        ?bool $includeShadowMetadata = null,
    ): EntityResponse {
        $path = $this->basePath($zoneId) . '/' . $this->requireId($record);
        $response = $this->adapter->put(
            $this->withShadowQuery($path, $includeShadowMetadata),
            $record->toReplacePayload(),
        );

        return $this->makeEntityResponse($response, DnsRecord::class);
    }

    /** @return EntityResponse<DnsRecord> */
    public function update(
        string $zoneId,
        DnsRecord $record,
        ?bool $includeShadowMetadata = null,
    ): EntityResponse {
        $path = $this->basePath($zoneId) . '/' . $this->requireId($record);
        $response = $this->adapter->patch(
            $this->withShadowQuery($path, $includeShadowMetadata),
            $record->toPatchPayload(),
        );

        return $this->makeEntityResponse($response, DnsRecord::class);
    }

    public function delete(string $zoneId, DnsRecord|string $record): ActionResponse
    {
        $recordId = $record instanceof DnsRecord
            ? $this->requireId($record)
            : $this->pathId($record, 'DNS record id');

        $response = $this->adapter->delete($this->basePath($zoneId) . '/' . $recordId);

        return $this->makeActionResponse($response, true);
    }

    public function export(string $zoneId): RawResponse
    {
        $response = $this->adapter->get(
            $this->basePath($zoneId) . '/export',
            [],
            ['Accept' => 'text/plain'],
        );

        return $this->makeRawResponse($response);
    }

    public function import(string $zoneId, DnsImport $import): ActionResponse
    {
        $response = $this->adapter->postMultipart(
            $this->basePath($zoneId) . '/import',
            $import->toMultipart(),
        );

        return $this->makeActionResponse($response);
    }

    public function scan(string $zoneId, mixed $body = null): ActionResponse
    {
        $response = $this->adapter->post(
            $this->basePath($zoneId) . '/scan',
            $body ?? new \stdClass(),
        );

        return $this->makeActionResponse($response);
    }

    public function triggerScan(string $zoneId): ActionResponse
    {
        $response = $this->adapter->post($this->basePath($zoneId) . '/scan/trigger', null);

        return $this->makeActionResponse($response);
    }

    /** @return ListResponse<DnsRecord> */
    public function listScanned(string $zoneId): ListResponse
    {
        $response = $this->adapter->get($this->basePath($zoneId) . '/scan/review');

        return $this->makeListResponse($response, DnsRecord::class);
    }

    public function reviewScan(string $zoneId, DnsScanReview $review): ActionResponse
    {
        $response = $this->adapter->post(
            $this->basePath($zoneId) . '/scan/review',
            $review->toArray(),
        );

        return $this->makeActionResponse($response);
    }

    public function batch(
        string $zoneId,
        DnsBatch $batch,
        ?bool $includeShadowMetadata = null,
    ): ActionResponse {
        $response = $this->adapter->post(
            $this->withShadowQuery($this->basePath($zoneId) . '/batch', $includeShadowMetadata),
            $batch->toArray(),
        );

        return $this->makeActionResponse($response);
    }

    private function basePath(string $zoneId): string
    {
        return '/zones/' . $this->pathId($zoneId, 'Zone id') . '/dns_records';
    }

    private function requireId(DnsRecord $record): string
    {
        if (!$record->hasAttribute('id')) {
            throw new InvalidArgumentException('DNS record operation requires an id.');
        }

        return $this->pathId($record->getId(), 'DNS record id');
    }

    private function pathId(string $id, string $label): string
    {
        $encoded = PathSegment::encode($id, $label);
        if ($encoded === '.' || $encoded === '..') {
            throw new InvalidArgumentException($label . ' must not be a relative path segment.');
        }

        return $encoded;
    }

    /** @return array<string, bool> */
    private function shadowQuery(?bool $includeShadowMetadata): array
    {
        return $includeShadowMetadata === null
            ? []
            : ['include_shadow_metadata' => $includeShadowMetadata];
    }

    private function withShadowQuery(string $path, ?bool $includeShadowMetadata): string
    {
        if ($includeShadowMetadata === null) {
            return $path;
        }

        return $path . '?include_shadow_metadata=' . ($includeShadowMetadata ? 'true' : 'false');
    }
}
