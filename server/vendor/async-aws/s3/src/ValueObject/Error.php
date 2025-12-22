<?php

namespace AsyncAws\S3\ValueObject;

use AsyncAws\Core\Exception\InvalidArgument;

/**
 * Container for all error elements.
 */
final class Error
{
    /**
     * The error key.
     */
    private $key;

    /**
     * The version ID of the error.
     */
    private $versionId;

    /**
     * The error code is a string that uniquely identifies an error condition. It is meant to be read and understood by
     * programs that detect and handle errors by type. The following is a list of Amazon S3 error codes. For more
     * information, see Error responses [^1].
     *
     * - - *Code:* AccessDenied
     * - - *Description:* Access Denied
     * - - *HTTP Status Code:* 403 Forbidden
     * - - *SOAP Fault Code Prefix:* Client
     * -
     * - - *Code:* AccountProblem
     * - - *Description:* There is a problem with your Amazon Web Services account that prevents the action from completing
     * -   successfully. Contact Amazon Web Services Support for further assistance.
     * - - *HTTP Status Code:* 403 Forbidden
     * - - *SOAP Fault Code Prefix:* Client
     * -
     * - - *Code:* AllAccessDisabled
     * - - *Description:* All access to this Amazon S3 resource has been disabled. Contact Amazon Web Services Support for
     * -   further assistance.
     * - - *HTTP Status Code:* 403 Forbidden
     * - - *SOAP Fault Code Prefix:* Client
     * -
     * - - *Code:* AmbiguousGrantByEmailAddress
     * - - *Description:* The email address you provided is associated with more than one account.
     * - - *HTTP Status Code:* 400 Bad Request
     * - - *SOAP Fault Code Prefix:* Client
     * -
     * - - *Code:* AuthorizationHeaderMalformed
     * - - *Description:* The authorization header you provided is invalid.
     * - - *HTTP Status Code:* 400 Bad Request
     * - - *HTTP Status Code:* N/A
     * -
     * - - *Code:* BadDigest
     * - - *Description:* The Content-MD5 you specified did not match what we received.
     * - - *HTTP Status Code:* 400 Bad Request
     * - - *SOAP Fault Code Prefix:* Client
     * -
     * - - *Code:* BucketAlreadyExists
     * - - *Description:* The requested bucket name is not available. The bucket namespace is shared by all users of the
     * -   system. Please select a different name and try again.
     * - - *HTTP Status Code:* 409 Conflict
     * - - *SOAP Fault Code Prefix:* Client
     * -
     * - - *Code:* BucketAlreadyOwnedByYou
     * - - *Description:* The bucket you tried to create already exists, and you own it. Amazon S3 returns this error in all
     * -   Amazon Web Services Regions except in the North Virginia Region. For legacy compatibility, if you re-create an
     * -   existing bucket that you already own in the North Virginia Region, Amazon S3 returns 200 OK and resets the bucket
     * -   access control lists (ACLs).
     * - - *Code:* 409 Conflict (in all Regions except the North Virginia Region)
     * - - *SOAP Fault Code Prefix:* Client
     * -
     * - - *Code:* BucketNotEmpty
     * - - *Description:* The bucket you tried to delete is not empty.
     * - - *HTTP Status Code:* 409 Conflict
     * - - *SOAP Fault Code Prefix:* Client
     * -
     * - - *Code:* CredentialsNotSupported
     * - - *Description:* This request does not support credentials.
     * - - *HTTP Status Code:* 400 Bad Request
     * - - *SOAP Fault Code Prefix:* Client
     * -
     * - - *Code:* CrossLocationLoggingProhibited
     * - - *Description:* Cross-location logging not allowed. Buckets in one geographic location cannot log information to a
     * -   bucket in another location.
     * - - *HTTP Status Code:* 403 Forbidden
     * - - *SOAP Fault Code Prefix:* Client
     * -
     * - - *Code:* EntityTooSmall
     * - - *Description:* Your proposed upload is smaller than the minimum allowed object size.
     * - - *HTTP Status Code:* 400 Bad Request
     * - - *SOAP Fault Code Prefix:* Client
     * -
     * - - *Code:* EntityTooLarge
     * - - *Description:* Your proposed upload exceeds the maximum allowed object size.
     * - - *HTTP Status Code:* 400 Bad Request
     * - - *SOAP Fault Code Prefix:* Client
     * -
     * - - *Code:* ExpiredToken
     * - - *Description:* The provided token has expired.
     * - - *HTTP Status Code:* 400 Bad Request
     * - - *SOAP Fault Code Prefix:* Client
     * -
     * - - *Code:* IllegalVersioningConfigurationException
     * - - *Description:* Indicates that the versioning configuration specified in the request is invalid.
     * - - *HTTP Status Code:* 400 Bad Request
     * - - *SOAP Fault Code Prefix:* Client
     * -
     * - - *Code:* IncompleteBody
     * - - *Description:* You did not provide the number of bytes specified by the Content-Length HTTP header
     * - - *HTTP Status Code:* 400 Bad Request
     * - - *SOAP Fault Code Prefix:* Client
     * -
     * - - *Code:* IncorrectNumberOfFilesInPostRequest
     * - - *Description:* POST requires exactly one file upload per request.
     * - - *HTTP Status Code:* 400 Bad Request
     * - - *SOAP Fault Code Prefix:* Client
     * -
     * - - *Code:* InlineDataTooLarge
     * - - *Description:* Inline data exceeds the maximum allowed size.
     * - - *HTTP Status Code:* 400 Bad Request
     * - - *SOAP Fault Code Prefix:* Client
     * -
     * - - *Code:* InternalError
     * - - *Description:* We encountered an internal error. Please try again.
     * - - *HTTP Status Code:* 500 Internal Server Error
     * - - *SOAP Fault Code Prefix:* Server
     * -
     * - - *Code:* InvalidAccessKeyId
     * - - *Description:* The Amazon Web Services access key ID you provided does not exist in our records.
     * - - *HTTP Status Code:* 403 Forbidden
     * - - *SOAP Fault Code Prefix:* Client
     * -
     * - - *Code:* InvalidAddressingHeader
     * - - *Description:* You must specify the Anonymous role.
     * - - *HTTP Status Code:* N/A
     * - - *SOAP Fault Code Prefix:* Client
     * -
     * - - *Code:* InvalidArgument
     * - - *Description:* Invalid Argument
     * - - *HTTP Status Code:* 400 Bad Request
     * - - *SOAP Fault Code Prefix:* Client
     * -
     * - - *Code:* InvalidBucketName
     * - - *Description:* The specified bucket is not valid.
     * - - *HTTP Status Code:* 400 Bad Request
     * - - *SOAP Fault Code Prefix:* Client
     * -
     * - - *Code:* InvalidBucketState
     * - - *Description:* The request is not valid with the current state of the bucket.
     * - - *HTTP Status Code:* 409 Conflict
     * - - *SOAP Fault Code Prefix:* Client
     * -
     * - - *Code:* InvalidDigest
     * - - *Description:* The Content-MD5 you specified is not valid.
     * - - *HTTP Status Code:* 400 Bad Request
     * - - *SOAP Fault Code Prefix:* Client
     * -
     * - - *Code:* InvalidEncryptionAlgorithmError
     * - - *Description:* The encryption request you specified is not valid. The valid value is AES256.
     * - - *HTTP Status Code:* 400 Bad Request
     * - - *SOAP Fault Code Prefix:* Client
     * -
     * - - *Code:* InvalidLocationConstraint
     * - - *Description:* The specified location constraint is not valid. For more information about Regions, see How to
     * -   Select a Region for Your Buckets [^2].
     * - - *HTTP Status Code:* 400 Bad Request
     * - - *SOAP Fault Code Prefix:* Client
     * -
     * - - *Code:* InvalidObjectState
     * - - *Description:* The action is not valid for the current state of the object.
     * - - *HTTP Status Code:* 403 Forbidden
     * - - *SOAP Fault Code Prefix:* Client
     * -
     * - - *Code:* InvalidPart
     * - - *Description:* One or more of the specified parts could not be found. The part might not have been uploaded, or
     * -   the specified entity tag might not have matched the part's entity tag.
     * - - *HTTP Status Code:* 400 Bad Request
     * - - *SOAP Fault Code Prefix:* Client
     * -
     * - - *Code:* InvalidPartOrder
     * - - *Description:* The list of parts was not in ascending order. Parts list must be specified in order by part
     * -   number.
     * - - *HTTP Status Code:* 400 Bad Request
     * - - *SOAP Fault Code Prefix:* Client
     * -
     * - - *Code:* InvalidPayer
     * - - *Description:* All access to this object has been disabled. Please contact Amazon Web Services Support for
     * -   further assistance.
     * - - *HTTP Status Code:* 403 Forbidden
     * - - *SOAP Fault Code Prefix:* Client
     * -
     * - - *Code:* InvalidPolicyDocument
     * - - *Description:* The content of the form does not meet the conditions specified in the policy document.
     * - - *HTTP Status Code:* 400 Bad Request
     * - - *SOAP Fault Code Prefix:* Client
     * -
     * - - *Code:* InvalidRange
     * - - *Description:* The requested range cannot be satisfied.
     * - - *HTTP Status Code:* 416 Requested Range Not Satisfiable
     * - - *SOAP Fault Code Prefix:* Client
     * -
     * - - *Code:* InvalidRequest
     * - - *Description:* Please use `AWS4-HMAC-SHA256`.
     * - - *HTTP Status Code:* 400 Bad Request
     * - - *Code:* N/A
     * -
     * - - *Code:* InvalidRequest
     * - - *Description:* SOAP requests must be made over an HTTPS connection.
     * - - *HTTP Status Code:* 400 Bad Request
     * - - *SOAP Fault Code Prefix:* Client
     * -
     * - - *Code:* InvalidRequest
     * - - *Description:* Amazon S3 Transfer Acceleration is not supported for buckets with non-DNS compliant names.
     * - - *HTTP Status Code:* 400 Bad Request
     * - - *Code:* N/A
     * -
     * - - *Code:* InvalidRequest
     * - - *Description:* Amazon S3 Transfer Acceleration is not supported for buckets with periods (.) in their names.
     * - - *HTTP Status Code:* 400 Bad Request
     * - - *Code:* N/A
     * -
     * - - *Code:* InvalidRequest
     * - - *Description:* Amazon S3 Transfer Accelerate endpoint only supports virtual style requests.
     * - - *HTTP Status Code:* 400 Bad Request
     * - - *Code:* N/A
     * -
     * - - *Code:* InvalidRequest
     * - - *Description:* Amazon S3 Transfer Accelerate is not configured on this bucket.
     * - - *HTTP Status Code:* 400 Bad Request
     * - - *Code:* N/A
     * -
     * - - *Code:* InvalidRequest
     * - - *Description:* Amazon S3 Transfer Accelerate is disabled on this bucket.
     * - - *HTTP Status Code:* 400 Bad Request
     * - - *Code:* N/A
     * -
     * - - *Code:* InvalidRequest
     * - - *Description:* Amazon S3 Transfer Acceleration is not supported on this bucket. Contact Amazon Web Services
     * -   Support for more information.
     * - - *HTTP Status Code:* 400 Bad Request
     * - - *Code:* N/A
     * -
     * - - *Code:* InvalidRequest
     * - - *Description:* Amazon S3 Transfer Acceleration cannot be enabled on this bucket. Contact Amazon Web Services
     * -   Support for more information.
     * - - *HTTP Status Code:* 400 Bad Request
     * - - *Code:* N/A
     * -
     * - - *Code:* InvalidSecurity
     * - - *Description:* The provided security credentials are not valid.
     * - - *HTTP Status Code:* 403 Forbidden
     * - - *SOAP Fault Code Prefix:* Client
     * -
     * - - *Code:* InvalidSOAPRequest
     * - - *Description:* The SOAP request body is invalid.
     * - - *HTTP Status Code:* 400 Bad Request
     * - - *SOAP Fault Code Prefix:* Client
     * -
     * - - *Code:* InvalidStorageClass
     * - - *Description:* The storage class you specified is not valid.
     * - - *HTTP Status Code:* 400 Bad Request
     * - - *SOAP Fault Code Prefix:* Client
     * -
     * - - *Code:* InvalidTargetBucketForLogging
     * - - *Description:* The target bucket for logging does not exist, is not owned by you, or does not have the
     * -   appropriate grants for the log-delivery group.
     * - - *HTTP Status Code:* 400 Bad Request
     * - - *SOAP Fault Code Prefix:* Client
     * -
     * - - *Code:* InvalidToken
     * - - *Description:* The provided token is malformed or otherwise invalid.
     * - - *HTTP Status Code:* 400 Bad Request
     * - - *SOAP Fault Code Prefix:* Client
     * -
     * - - *Code:* InvalidURI
     * - - *Description:* Couldn't parse the specified URI.
     * - - *HTTP Status Code:* 400 Bad Request
     * - - *SOAP Fault Code Prefix:* Client
     * -
     * - - *Code:* KeyTooLongError
     * - - *Description:* Your key is too long.
     * - - *HTTP Status Code:* 400 Bad Request
     * - - *SOAP Fault Code Prefix:* Client
     * -
     * - - *Code:* MalformedACLError
     * - - *Description:* The XML you provided was not well-formed or did not validate against our published schema.
     * - - *HTTP Status Code:* 400 Bad Request
     * - - *SOAP Fault Code Prefix:* Client
     * -
     * - - *Code:* MalformedPOSTRequest
     * - - *Description:* The body of your POST request is not well-formed multipart/form-data.
     * - - *HTTP Status Code:* 400 Bad Request
     * - - *SOAP Fault Code Prefix:* Client
     * -
     * - - *Code:* MalformedXML
     * - - *Description:* This happens when the user sends malformed XML (XML that doesn't conform to the published XSD) for
     * -   the configuration. The error message is, "The XML you provided was not well-formed or did not validate against
     * -   our published schema."
     * - - *HTTP Status Code:* 400 Bad Request
     * - - *SOAP Fault Code Prefix:* Client
     * -
     * - - *Code:* MaxMessageLengthExceeded
     * - - *Description:* Your request was too big.
     * - - *HTTP Status Code:* 400 Bad Request
     * - - *SOAP Fault Code Prefix:* Client
     * -
     * - - *Code:* MaxPostPreDataLengthExceededError
     * - - *Description:* Your POST request fields preceding the upload file were too large.
     * - - *HTTP Status Code:* 400 Bad Request
     * - - *SOAP Fault Code Prefix:* Client
     * -
     * - - *Code:* MetadataTooLarge
     * - - *Description:* Your metadata headers exceed the maximum allowed metadata size.
     * - - *HTTP Status Code:* 400 Bad Request
     * - - *SOAP Fault Code Prefix:* Client
     * -
     * - - *Code:* MethodNotAllowed
     * - - *Description:* The specified method is not allowed against this resource.
     * - - *HTTP Status Code:* 405 Method Not Allowed
     * - - *SOAP Fault Code Prefix:* Client
     * -
     * - - *Code:* MissingAttachment
     * - - *Description:* A SOAP attachment was expected, but none were found.
     * - - *HTTP Status Code:* N/A
     * - - *SOAP Fault Code Prefix:* Client
     * -
     * - - *Code:* MissingContentLength
     * - - *Description:* You must provide the Content-Length HTTP header.
     * - - *HTTP Status Code:* 411 Length Required
     * - - *SOAP Fault Code Prefix:* Client
     * -
     * - - *Code:* MissingRequestBodyError
     * - - *Description:* This happens when the user sends an empty XML document as a request. The error message is,
     * -   "Request body is empty."
     * - - *HTTP Status Code:* 400 Bad Request
     * - - *SOAP Fault Code Prefix:* Client
     * -
     * - - *Code:* MissingSecurityElement
     * - - *Description:* The SOAP 1.1 request is missing a security element.
     * - - *HTTP Status Code:* 400 Bad Request
     * - - *SOAP Fault Code Prefix:* Client
     * -
     * - - *Code:* MissingSecurityHeader
     * - - *Description:* Your request is missing a required header.
     * - - *HTTP Status Code:* 400 Bad Request
     * - - *SOAP Fault Code Prefix:* Client
     * -
     * - - *Code:* NoLoggingStatusForKey
     * - - *Description:* There is no such thing as a logging status subresource for a key.
     * - - *HTTP Status Code:* 400 Bad Request
     * - - *SOAP Fault Code Prefix:* Client
     * -
     * - - *Code:* NoSuchBucket
     * - - *Description:* The specified bucket does not exist.
     * - - *HTTP Status Code:* 404 Not Found
     * - - *SOAP Fault Code Prefix:* Client
     * -
     * - - *Code:* NoSuchBucketPolicy
     * - - *Description:* The specified bucket does not have a bucket policy.
     * - - *HTTP Status Code:* 404 Not Found
     * - - *SOAP Fault Code Prefix:* Client
     * -
     * - - *Code:* NoSuchKey
     * - - *Description:* The specified key does not exist.
     * - - *HTTP Status Code:* 404 Not Found
     * - - *SOAP Fault Code Prefix:* Client
     * -
     * - - *Code:* NoSuchLifecycleConfiguration
     * - - *Description:* The lifecycle configuration does not exist.
     * - - *HTTP Status Code:* 404 Not Found
     * - - *SOAP Fault Code Prefix:* Client
     * -
     * - - *Code:* NoSuchUpload
     * - - *Description:* The specified multipart upload does not exist. The upload ID might be invalid, or the multipart
     * -   upload might have been aborted or completed.
     * - - *HTTP Status Code:* 404 Not Found
     * - - *SOAP Fault Code Prefix:* Client
     * -
     * - - *Code:* NoSuchVersion
     * - - *Description:* Indicates that the version ID specified in the request does not match an existing version.
     * - - *HTTP Status Code:* 404 Not Found
     * - - *SOAP Fault Code Prefix:* Client
     * -
     * - - *Code:* NotImplemented
     * - - *Description:* A header you provided implies functionality that is not implemented.
     * - - *HTTP Status Code:* 501 Not Implemented
     * - - *SOAP Fault Code Prefix:* Server
     * -
     * - - *Code:* NotSignedUp
     * - - *Description:* Your account is not signed up for the Amazon S3 service. You must sign up before you can use
     * -   Amazon S3. You can sign up at the following URL: Amazon S3 [^3]
     * - - *HTTP Status Code:* 403 Forbidden
     * - - *SOAP Fault Code Prefix:* Client
     * -
     * - - *Code:* OperationAborted
     * - - *Description:* A conflicting conditional action is currently in progress against this resource. Try again.
     * - - *HTTP Status Code:* 409 Conflict
     * - - *SOAP Fault Code Prefix:* Client
     * -
     * - - *Code:* PermanentRedirect
     * - - *Description:* The bucket you are attempting to access must be addressed using the specified endpoint. Send all
     * -   future requests to this endpoint.
     * - - *HTTP Status Code:* 301 Moved Permanently
     * - - *SOAP Fault Code Prefix:* Client
     * -
     * - - *Code:* PreconditionFailed
     * - - *Description:* At least one of the preconditions you specified did not hold.
     * - - *HTTP Status Code:* 412 Precondition Failed
     * - - *SOAP Fault Code Prefix:* Client
     * -
     * - - *Code:* Redirect
     * - - *Description:* Temporary redirect.
     * - - *HTTP Status Code:* 307 Moved Temporarily
     * - - *SOAP Fault Code Prefix:* Client
     * -
     * - - *Code:* RestoreAlreadyInProgress
     * - - *Description:* Object restore is already in progress.
     * - - *HTTP Status Code:* 409 Conflict
     * - - *SOAP Fault Code Prefix:* Client
     * -
     * - - *Code:* RequestIsNotMultiPartContent
     * - - *Description:* Bucket POST must be of the enclosure-type multipart/form-data.
     * - - *HTTP Status Code:* 400 Bad Request
     * - - *SOAP Fault Code Prefix:* Client
     * -
     * - - *Code:* RequestTimeout
     * - - *Description:* Your socket connection to the server was not read from or written to within the timeout period.
     * - - *HTTP Status Code:* 400 Bad Request
     * - - *SOAP Fault Code Prefix:* Client
     * -
     * - - *Code:* RequestTimeTooSkewed
     * - - *Description:* The difference between the request time and the server's time is too large.
     * - - *HTTP Status Code:* 403 Forbidden
     * - - *SOAP Fault Code Prefix:* Client
     * -
     * - - *Code:* RequestTorrentOfBucketError
     * - - *Description:* Requesting the torrent file of a bucket is not permitted.
     * - - *HTTP Status Code:* 400 Bad Request
     * - - *SOAP Fault Code Prefix:* Client
     * -
     * - - *Code:* SignatureDoesNotMatch
     * - - *Description:* The request signature we calculated does not match the signature you provided. Check your Amazon
     * -   Web Services secret access key and signing method. For more information, see REST Authentication [^4] and SOAP
     * -   Authentication [^5] for details.
     * - - *HTTP Status Code:* 403 Forbidden
     * - - *SOAP Fault Code Prefix:* Client
     * -
     * - - *Code:* ServiceUnavailable
     * - - *Description:* Service is unable to handle request.
     * - - *HTTP Status Code:* 503 Service Unavailable
     * - - *SOAP Fault Code Prefix:* Server
     * -
     * - - *Code:* SlowDown
     * - - *Description:* Reduce your request rate.
     * - - *HTTP Status Code:* 503 Slow Down
     * - - *SOAP Fault Code Prefix:* Server
     * -
     * - - *Code:* TemporaryRedirect
     * - - *Description:* You are being redirected to the bucket while DNS updates.
     * - - *HTTP Status Code:* 307 Moved Temporarily
     * - - *SOAP Fault Code Prefix:* Client
     * -
     * - - *Code:* TokenRefreshRequired
     * - - *Description:* The provided token must be refreshed.
     * - - *HTTP Status Code:* 400 Bad Request
     * - - *SOAP Fault Code Prefix:* Client
     * -
     * - - *Code:* TooManyBuckets
     * - - *Description:* You have attempted to create more buckets than allowed.
     * - - *HTTP Status Code:* 400 Bad Request
     * - - *SOAP Fault Code Prefix:* Client
     * -
     * - - *Code:* UnexpectedContent
     * - - *Description:* This request does not support content.
     * - - *HTTP Status Code:* 400 Bad Request
     * - - *SOAP Fault Code Prefix:* Client
     * -
     * - - *Code:* UnresolvableGrantByEmailAddress
     * - - *Description:* The email address you provided does not match any account on record.
     * - - *HTTP Status Code:* 400 Bad Request
     * - - *SOAP Fault Code Prefix:* Client
     * -
     * - - *Code:* UserKeyMustBeSpecified
     * - - *Description:* The bucket POST must contain the specified field name. If it is specified, check the order of the
     * -   fields.
     * - - *HTTP Status Code:* 400 Bad Request
     * - - *SOAP Fault Code Prefix:* Client
     * -
     *
     * [^1]: https://docs.aws.amazon.com/AmazonS3/latest/API/ErrorResponses.html
     * [^2]: https://docs.aws.amazon.com/AmazonS3/latest/dev/UsingBucket.html#access-bucket-intro
     * [^3]: http://aws.amazon.com/s3
     * [^4]: https://docs.aws.amazon.com/AmazonS3/latest/dev/RESTAuthentication.html
     * [^5]: https://docs.aws.amazon.com/AmazonS3/latest/dev/SOAPAuthentication.html
     */
    private $code;

    /**
     * The error message contains a generic description of the error condition in English. It is intended for a human
     * audience. Simple programs display the message directly to the end user if they encounter an error condition they
     * don't know how or don't care to handle. Sophisticated programs with more exhaustive error handling and proper
     * internationalization are more likely to ignore the error message.
     */
    private $message;

    /**
     * @param array{
     *   Key?: null|string,
     *   VersionId?: null|string,
     *   Code?: null|string,
     *   Message?: null|string,
     * } $input
     */
    public function __construct(array $input)
    {
        $this->key = $input['Key'] ?? null;
        $this->versionId = $input['VersionId'] ?? null;
        $this->code = $input['Code'] ?? null;
        $this->message = $input['Message'] ?? null;
    }

    public static function create($input): self
    {
        return $input instanceof self ? $input : new self($input);
    }

    public function getCode(): ?string
    {
        return $this->code;
    }

    public function getKey(): ?string
    {
        return $this->key;
    }

    public function getMessage(): ?string
    {
        return $this->message;
    }

    public function getVersionId(): ?string
    {
        return $this->versionId;
    }
}
