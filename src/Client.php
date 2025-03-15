<?php

namespace PlainSimple\Cloudflare;

use PlainSimple\Cloudflare\Adapters\AdapterInterface;
use PlainSimple\Cloudflare\Endpoints\AbstractEndpoint;
use PlainSimple\Cloudflare\Exceptions\EndpointDoesNotExistsException;

readonly class Client
{
    private array $endpointsInstances;

    public function __construct(private AdapterInterface $adapter)
    {
    }

    /**
     * @throws EndpointDoesNotExistsException
     */
    private function getEndpointInstance(string $className): AbstractEndpoint
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