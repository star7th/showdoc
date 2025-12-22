<?php

namespace AsyncAws\S3\Enum;

/**
 * Requests Amazon S3 to encode the object keys in the response and specifies the encoding method to use. An object key
 * may contain any Unicode character; however, XML 1.0 parser cannot parse some characters, such as characters with an
 * ASCII value from 0 to 10. For characters that are not supported in XML 1.0, you can add this parameter to request
 * that Amazon S3 encode the keys in the response.
 */
final class EncodingType
{
    public const URL = 'url';

    public static function exists(string $value): bool
    {
        return isset([
            self::URL => true,
        ][$value]);
    }
}
