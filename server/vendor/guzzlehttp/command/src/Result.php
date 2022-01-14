<?php
namespace GuzzleHttp\Command;

/**
 * Default command implementation.
 */
class Result implements ResultInterface
{
    use HasDataTrait;

    /**
     * @param array $data
     */
    public function __construct(array $data = [])
    {
        $this->data = $data;
    }
}
