<?php

namespace AsyncAws\Core\Sts\Input;

use AsyncAws\Core\Input;
use AsyncAws\Core\Request;
use AsyncAws\Core\Stream\StreamFactory;

final class GetCallerIdentityRequest extends Input
{
    /**
     * @param array{
     *   '@region'?: string|null,
     * } $input
     */
    public function __construct(array $input = [])
    {
        parent::__construct($input);
    }

    /**
     * @param array{
     *   '@region'?: string|null,
     * }|GetCallerIdentityRequest $input
     */
    public static function create($input): self
    {
        return $input instanceof self ? $input : new self($input);
    }

    /**
     * @internal
     */
    public function request(): Request
    {
        // Prepare headers
        $headers = ['content-type' => 'application/x-www-form-urlencoded'];

        // Prepare query
        $query = [];

        // Prepare URI
        $uriString = '/';

        // Prepare Body
        $body = http_build_query(['Action' => 'GetCallerIdentity', 'Version' => '2011-06-15'] + $this->requestBody(), '', '&', \PHP_QUERY_RFC1738);

        // Return the Request
        return new Request('POST', $uriString, $query, $headers, StreamFactory::create($body));
    }

    private function requestBody(): array
    {
        $payload = [];

        return $payload;
    }
}
