<?php

namespace PlainSimple\Cloudflare;

use PlainSimple\Cloudflare\Adapters\Adapter;
use PlainSimple\Cloudflare\Endpoints\Endpoint;
use PlainSimple\Cloudflare\Exceptions\EndpointDoesNotExistsException;

readonly class Client
{
    private array $endpointsInstances;

    public function __construct(private Adapter $adapter)
    {
    }

    /**
     * @throws EndpointDoesNotExistsException
     */
    private function getEndpointInstance(string $className): Endpoint
    {
        if (!class_exists($className)) {
            throw new EndpointDoesNotExistsException(sprintf('Endpoint `%s` does not exist.', $className));
        }

        if (!isset($this->endpointsInstances[$className])) {
            $this->endpointsInstances[$className] = new $className($this->adapter);
        }

        return $this->endpointsInstances[$className];
    }
}