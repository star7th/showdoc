<?php

namespace AsyncAws\S3\Enum;

/**
 * The bucket event for which to send notifications.
 */
final class Event
{
    public const S3_INTELLIGENT_TIERING = 's3:IntelligentTiering';
    public const S3_LIFECYCLE_EXPIRATION_ALL = 's3:LifecycleExpiration:*';
    public const S3_LIFECYCLE_EXPIRATION_DELETE = 's3:LifecycleExpiration:Delete';
    public const S3_LIFECYCLE_EXPIRATION_DELETE_MARKER_CREATED = 's3:LifecycleExpiration:DeleteMarkerCreated';
    public const S3_LIFECYCLE_TRANSITION = 's3:LifecycleTransition';
    public const S3_OBJECT_ACL_PUT = 's3:ObjectAcl:Put';
    public const S3_OBJECT_CREATED_ALL = 's3:ObjectCreated:*';
    public const S3_OBJECT_CREATED_COMPLETE_MULTIPART_UPLOAD = 's3:ObjectCreated:CompleteMultipartUpload';
    public const S3_OBJECT_CREATED_COPY = 's3:ObjectCreated:Copy';
    public const S3_OBJECT_CREATED_POST = 's3:ObjectCreated:Post';
    public const S3_OBJECT_CREATED_PUT = 's3:ObjectCreated:Put';
    public const S3_OBJECT_REMOVED_ALL = 's3:ObjectRemoved:*';
    public const S3_OBJECT_REMOVED_DELETE = 's3:ObjectRemoved:Delete';
    public const S3_OBJECT_REMOVED_DELETE_MARKER_CREATED = 's3:ObjectRemoved:DeleteMarkerCreated';
    public const S3_OBJECT_RESTORE_ALL = 's3:ObjectRestore:*';
    public const S3_OBJECT_RESTORE_COMPLETED = 's3:ObjectRestore:Completed';
    public const S3_OBJECT_RESTORE_DELETE = 's3:ObjectRestore:Delete';
    public const S3_OBJECT_RESTORE_POST = 's3:ObjectRestore:Post';
    public const S3_OBJECT_TAGGING_ALL = 's3:ObjectTagging:*';
    public const S3_OBJECT_TAGGING_DELETE = 's3:ObjectTagging:Delete';
    public const S3_OBJECT_TAGGING_PUT = 's3:ObjectTagging:Put';
    public const S3_REDUCED_REDUNDANCY_LOST_OBJECT = 's3:ReducedRedundancyLostObject';
    public const S3_REPLICATION_ALL = 's3:Replication:*';
    public const S3_REPLICATION_OPERATION_FAILED_REPLICATION = 's3:Replication:OperationFailedReplication';
    public const S3_REPLICATION_OPERATION_MISSED_THRESHOLD = 's3:Replication:OperationMissedThreshold';
    public const S3_REPLICATION_OPERATION_NOT_TRACKED = 's3:Replication:OperationNotTracked';
    public const S3_REPLICATION_OPERATION_REPLICATED_AFTER_THRESHOLD = 's3:Replication:OperationReplicatedAfterThreshold';

    public static function exists(string $value): bool
    {
        return isset([
            self::S3_INTELLIGENT_TIERING => true,
            self::S3_LIFECYCLE_EXPIRATION_ALL => true,
            self::S3_LIFECYCLE_EXPIRATION_DELETE => true,
            self::S3_LIFECYCLE_EXPIRATION_DELETE_MARKER_CREATED => true,
            self::S3_LIFECYCLE_TRANSITION => true,
            self::S3_OBJECT_ACL_PUT => true,
            self::S3_OBJECT_CREATED_ALL => true,
            self::S3_OBJECT_CREATED_COMPLETE_MULTIPART_UPLOAD => true,
            self::S3_OBJECT_CREATED_COPY => true,
            self::S3_OBJECT_CREATED_POST => true,
            self::S3_OBJECT_CREATED_PUT => true,
            self::S3_OBJECT_REMOVED_ALL => true,
            self::S3_OBJECT_REMOVED_DELETE => true,
            self::S3_OBJECT_REMOVED_DELETE_MARKER_CREATED => true,
            self::S3_OBJECT_RESTORE_ALL => true,
            self::S3_OBJECT_RESTORE_COMPLETED => true,
            self::S3_OBJECT_RESTORE_DELETE => true,
            self::S3_OBJECT_RESTORE_POST => true,
            self::S3_OBJECT_TAGGING_ALL => true,
            self::S3_OBJECT_TAGGING_DELETE => true,
            self::S3_OBJECT_TAGGING_PUT => true,
            self::S3_REDUCED_REDUNDANCY_LOST_OBJECT => true,
            self::S3_REPLICATION_ALL => true,
            self::S3_REPLICATION_OPERATION_FAILED_REPLICATION => true,
            self::S3_REPLICATION_OPERATION_MISSED_THRESHOLD => true,
            self::S3_REPLICATION_OPERATION_NOT_TRACKED => true,
            self::S3_REPLICATION_OPERATION_REPLICATED_AFTER_THRESHOLD => true,
        ][$value]);
    }
}
