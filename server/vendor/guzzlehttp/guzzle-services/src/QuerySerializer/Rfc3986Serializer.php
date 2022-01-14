<?php

namespace GuzzleHttp\Command\Guzzle\QuerySerializer;

class Rfc3986Serializer implements QuerySerializerInterface
{
    /**
     * @var bool
     */
    private $removeNumericIndices;

    /**
     * @param bool $removeNumericIndices
     */
    public function __construct($removeNumericIndices = false)
    {
        $this->removeNumericIndices = $removeNumericIndices;
    }

    /**
     * {@inheritDoc}
     */
    public function aggregate(array $queryParams)
    {
        $queryString = http_build_query($queryParams, null, '&', PHP_QUERY_RFC3986);

        if ($this->removeNumericIndices) {
            $queryString = preg_replace('/%5B[0-9]+%5D/simU', '%5B%5D', $queryString);
        }

        return $queryString;
    }
}