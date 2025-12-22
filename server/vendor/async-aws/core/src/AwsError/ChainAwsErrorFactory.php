<?php

namespace AsyncAws\Core\AwsError;

use AsyncAws\Core\Exception\UnparsableResponse;

/**
 * @internal
 */
class ChainAwsErrorFactory implements AwsErrorFactoryInterface
{
    use AwsErrorFactoryFromResponseTrait;

    /**
     * @var AwsErrorFactoryInterface[]
     */
    private $factories;

    /**
     * @param AwsErrorFactoryInterface[]|null $factories
     */
    public function __construct(?array $factories = null)
    {
        $this->factories = $factories ?? [
            new JsonRestAwsErrorFactory(),
            new JsonRpcAwsErrorFactory(),
            new XmlAwsErrorFactory(),
        ];
    }

    public function createFromContent(string $content, array $headers): AwsError
    {
        $e = null;
        foreach ($this->factories as $factory) {
            try {
                return $factory->createFromContent($content, $headers);
            } catch (UnparsableResponse $e) {
            }
        }

        throw new UnparsableResponse('Failed to parse AWS error: ' . $content, 0, $e);
    }
}
