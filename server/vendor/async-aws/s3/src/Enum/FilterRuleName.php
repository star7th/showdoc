<?php

namespace AsyncAws\S3\Enum;

/**
 * The object key name prefix or suffix identifying one or more objects to which the filtering rule applies. The maximum
 * length is 1,024 characters. Overlapping prefixes and suffixes are not supported. For more information, see
 * Configuring Event Notifications in the *Amazon S3 User Guide*.
 *
 * @see https://docs.aws.amazon.com/AmazonS3/latest/dev/NotificationHowTo.html
 */
final class FilterRuleName
{
    public const PREFIX = 'prefix';
    public const SUFFIX = 'suffix';

    public static function exists(string $value): bool
    {
        return isset([
            self::PREFIX => true,
            self::SUFFIX => true,
        ][$value]);
    }
}
