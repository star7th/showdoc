<?php

namespace AsyncAws\Core\AwsError;

use AsyncAws\Core\Exception\RuntimeException;
use AsyncAws\Core\Exception\UnexpectedValue;
use AsyncAws\Core\Exception\UnparsableResponse;

/**
 * @internal
 */
class XmlAwsErrorFactory implements AwsErrorFactoryInterface
{
    use AwsErrorFactoryFromResponseTrait;

    public function createFromContent(string $content, array $headers): AwsError
    {
        try {
            /**
             * @phpstan-ignore-next-line
             * @psalm-suppress InvalidArgument
             */
            set_error_handler(static function ($errno, $errstr) {
                throw new RuntimeException($errstr, $errno);
            });

            try {
                $xml = new \SimpleXMLElement($content);
            } finally {
                restore_error_handler();
            }

            return self::parseXml($xml);
        } catch (\Throwable $e) {
            throw new UnparsableResponse('Failed to parse AWS error: ' . $content, 0, $e);
        }
    }

    private static function parseXml(\SimpleXMLElement $xml): AwsError
    {
        if (0 < $xml->Error->count()) {
            return new AwsError(
                $xml->Error->Code->__toString(),
                $xml->Error->Message->__toString(),
                $xml->Error->Type->__toString(),
                $xml->Error->Detail->__toString()
            );
        }

        if (1 === $xml->Code->count() && 1 === $xml->Message->count()) {
            return new AwsError(
                $xml->Code->__toString(),
                $xml->Message->__toString(),
                null,
                null
            );
        }

        throw new UnexpectedValue('XML does not contains AWS Error');
    }
}
