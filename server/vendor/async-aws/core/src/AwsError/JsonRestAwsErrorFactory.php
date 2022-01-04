<?php

namespace AsyncAws\Core\AwsError;

use AsyncAws\Core\Exception\UnexpectedValue;
use AsyncAws\Core\Exception\UnparsableResponse;

/**
 * @internal
 */
class JsonRestAwsErrorFactory implements AwsErrorFactoryInterface
{
    use AwsErrorFactoryFromResponseTrait;

    public function createFromContent(string $content, array $headers): AwsError
    {
        try {
            $body = json_decode($content, true);

            return self::parseJson($body, $headers);
        } catch (\Throwable $e) {
            throw new UnparsableResponse('Failed to parse AWS error: ' . $content, 0, $e);
        }
    }

    private static function parseJson(array $body, array $headers): AwsError
    {
        $code = null;
        $type = $body['type'] ?? $body['Type'] ?? null;
        if ($type) {
            $type = strtolower($type);
        }
        $message = $body['message'] ?? $body['Message'] ?? null;
        if (isset($headers['x-amzn-errortype'][0])) {
            $code = explode(':', $headers['x-amzn-errortype'][0], 2)[0];
        }

        if (null !== $code) {
            return new AwsError($code, $message, $type, null);
        }

        throw new UnexpectedValue('JSON does not contains AWS Error');
    }
}
