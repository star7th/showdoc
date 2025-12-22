<?php

namespace AsyncAws\Core;

use AsyncAws\Core\Exception\Http\HttpException;
use AsyncAws\Core\Exception\InvalidArgument;

/**
 * Contains contextual information alongside a request.
 *
 * @author Jérémy Derussé <jeremy@derusse.com>
 */
final class RequestContext
{
    public const AVAILABLE_OPTIONS = [
        'region' => true,
        'operation' => true,
        'expirationDate' => true,
        'currentDate' => true,
        'exceptionMapping' => true,
        'usesEndpointDiscovery' => true,
        'requiresEndpointDiscovery' => true,
    ];

    /**
     * @var string|null
     */
    private $operation;

    /**
     * @var bool
     */
    private $usesEndpointDiscovery = false;

    /**
     * @var bool
     */
    private $requiresEndpointDiscovery = false;

    /**
     * @var string|null
     */
    private $region;

    /**
     * @var \DateTimeImmutable|null
     */
    private $expirationDate;

    /**
     * @var \DateTimeImmutable|null
     */
    private $currentDate;

    /**
     * @var array<string, class-string<HttpException>>
     */
    private $exceptionMapping = [];

    /**
     * @param array{
     *  operation?: null|string,
     *  region?: null|string,
     *  expirationDate?: null|\DateTimeImmutable,
     *  currentDate?: null|\DateTimeImmutable,
     *  exceptionMapping?: array<string, class-string<HttpException>>,
     *  usesEndpointDiscovery?: bool,
     *  requiresEndpointDiscovery?: bool,
     * } $options
     */
    public function __construct(array $options = [])
    {
        if (0 < \count($invalidOptions = array_diff_key($options, self::AVAILABLE_OPTIONS))) {
            throw new InvalidArgument(\sprintf('Invalid option(s) "%s" passed to "%s". ', implode('", "', array_keys($invalidOptions)), __METHOD__));
        }

        foreach ($options as $property => $value) {
            $this->$property = $value;
        }
    }

    public function getOperation(): ?string
    {
        return $this->operation;
    }

    public function getRegion(): ?string
    {
        return $this->region;
    }

    public function getExpirationDate(): ?\DateTimeImmutable
    {
        return $this->expirationDate;
    }

    public function getCurrentDate(): ?\DateTimeImmutable
    {
        return $this->currentDate;
    }

    /**
     * @return array<string, class-string<HttpException>>
     */
    public function getExceptionMapping(): array
    {
        return $this->exceptionMapping;
    }

    public function usesEndpointDiscovery(): bool
    {
        return $this->usesEndpointDiscovery;
    }

    public function requiresEndpointDiscovery(): bool
    {
        return $this->requiresEndpointDiscovery;
    }
}
