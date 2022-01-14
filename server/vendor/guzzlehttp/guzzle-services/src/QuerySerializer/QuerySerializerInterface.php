<?php
namespace GuzzleHttp\Command\Guzzle\QuerySerializer;

interface QuerySerializerInterface
{
    /**
     * Aggregate query params and transform them into a string
     *
     * @param  array $queryParams
     * @return string
     */
    public function aggregate(array $queryParams);
}