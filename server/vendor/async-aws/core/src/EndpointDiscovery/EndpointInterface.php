<?php

namespace AsyncAws\Core\EndpointDiscovery;

interface EndpointInterface
{
    public function getAddress(): string;

    public function getCachePeriodInMinutes(): int;
}
