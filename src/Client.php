<?php

declare(strict_types=1);

namespace PlainSimple\Cloudflare;

use PlainSimple\Cloudflare\Adapters\AdapterInterface;
use PlainSimple\Cloudflare\Adapters\GuzzleAdapter;
use PlainSimple\Cloudflare\Auth\ApiToken;
use PlainSimple\Cloudflare\Endpoints\AbstractEndpoint;
use PlainSimple\Cloudflare\Endpoints\Accounts;
use PlainSimple\Cloudflare\Endpoints\DnsRecords;
use PlainSimple\Cloudflare\Endpoints\Rulesets;
use PlainSimple\Cloudflare\Endpoints\Zones;
use PlainSimple\Cloudflare\Endpoints\ZoneSettings;
use PlainSimple\Cloudflare\Exceptions\EndpointDoesNotExistsException;

class Client
{
    /** @var array<class-string<AbstractEndpoint>, AbstractEndpoint> */
    private array $endpointInstances = [];

    public function __construct(private AdapterInterface $adapter)
    {
    }

    /** @param array<string, mixed> $clientOptions */
    public static function withApiToken(
        string $token,
        string $baseUri = 'https://api.cloudflare.com/client/v4',
        array $clientOptions = [],
    ): self {
        return new self(new GuzzleAdapter(new ApiToken($token), $baseUri, $clientOptions));
    }

    public function accounts(): Accounts
    {
        return $this->endpoint(Accounts::class);
    }

    public function zones(): Zones
    {
        return $this->endpoint(Zones::class);
    }

    public function dnsRecords(): DnsRecords
    {
        return $this->endpoint(DnsRecords::class);
    }

    public function zoneSettings(): ZoneSettings
    {
        return $this->endpoint(ZoneSettings::class);
    }

    public function rulesets(): Rulesets
    {
        return $this->endpoint(Rulesets::class);
    }

    /**
     * @template TEndpoint of AbstractEndpoint
     * @param class-string<TEndpoint> $className
     * @return TEndpoint
     * @throws EndpointDoesNotExistsException
     */
    private function endpoint(string $className): AbstractEndpoint
    {
        if (!class_exists($className) || !is_subclass_of($className, AbstractEndpoint::class)) {
            throw new EndpointDoesNotExistsException(sprintf(
                'Endpoint `%s` must extend `%s`.',
                $className,
                AbstractEndpoint::class,
            ));
        }

        if (!isset($this->endpointInstances[$className])) {
            $this->endpointInstances[$className] = new $className($this->adapter);
        }

        /** @var TEndpoint */
        return $this->endpointInstances[$className];
    }
}
