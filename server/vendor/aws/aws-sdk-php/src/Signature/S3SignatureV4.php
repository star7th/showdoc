<?php
namespace Aws\Signature;

use Aws\Credentials\CredentialsInterface;
use AWS\CRT\Auth\SignatureType;
use AWS\CRT\Auth\Signing;
use AWS\CRT\Auth\SigningAlgorithm;
use AWS\CRT\Auth\SigningConfigAWS;
use AWS\CRT\Auth\StaticCredentialsProvider;
use AWS\CRT\HTTP\Request;
use AWS\CRT\IO\InputStream;
use AWS\CRT\Auth\Signable;
use Aws\Exception\AwsException;
use Aws\Exception\CommonRuntimeException;
use Psr\Http\Message\RequestInterface;

/**
 * Amazon S3 signature version 4 support.
 */
class S3SignatureV4 extends SignatureV4
{
    /**
     * S3-specific signing logic
     *
     * {@inheritdoc}
     */
    public function signRequest(
        RequestInterface $request,
        CredentialsInterface $credentials,
        $signingService = null
    ) {
        // Always add a x-amz-content-sha-256 for data integrity
        if (!$request->hasHeader('x-amz-content-sha256')) {
            $request = $request->withHeader(
                'x-amz-content-sha256',
                $this->getPayload($request)
            );
        }
        $useCrt =
            strpos($request->getUri()->getHost(), "accesspoint.s3-global")
            !== false;
        if (!$useCrt) {
            if (strpos($request->getUri()->getHost(), "s3-object-lambda")) {
                return parent::signRequest($request, $credentials, "s3-object-lambda");
            }
            return parent::signRequest($request, $credentials);
        }
        return $this->signWithV4a($credentials, $request, $signingService);
    }

    /**
     * Always add a x-amz-content-sha-256 for data integrity.
     *
     * {@inheritdoc}
     */
    public function presign(
        RequestInterface $request,
        CredentialsInterface $credentials,
        $expires,
        array $options = []
    ) {
        if (!$request->hasHeader('x-amz-content-sha256')) {
            $request = $request->withHeader(
                'X-Amz-Content-Sha256',
                $this->getPresignedPayload($request)
            );
        }
        if (strpos($request->getUri()->getHost(), "accesspoint.s3-global")) {
            $request = $request->withHeader("x-amz-region-set", "*");
        }

        return parent::presign($request, $credentials, $expires, $options);
    }

    /**
     * Override used to allow pre-signed URLs to be created for an
     * in-determinate request payload.
     */
    protected function getPresignedPayload(RequestInterface $request)
    {
        return SignatureV4::UNSIGNED_PAYLOAD;
    }

    /**
     * Amazon S3 does not double-encode the path component in the canonical request
     */
    protected function createCanonicalizedPath($path)
    {
        // Only remove one slash in case of keys that have a preceding slash
        if (substr($path, 0, 1) === '/') {
            $path = substr($path, 1);
        }
        return '/' . $path;
    }

    /**
     * @param CredentialsInterface $credentials
     * @param RequestInterface $request
     * @param $signingService
     * @return RequestInterface
     */
    private function signWithV4a(CredentialsInterface $credentials, RequestInterface $request, $signingService)
    {
        if (!extension_loaded('awscrt')) {
            throw new CommonRuntimeException(
                "AWS Common Runtime for PHP is required to use Signature V4A and multi-region"
                . " access points.  Please install it using the instructions found at"
                . " https://github.com/aws/aws-sdk-php/blob/master/CRT_INSTRUCTIONS.md"
            );
        }
        $credentials_provider = new StaticCredentialsProvider([
            'access_key_id' => $credentials->getAccessKeyId(),
            'secret_access_key' => $credentials->getSecretKey(),
            'session_token' => $credentials->getSecurityToken(),
        ]);
        $signingService = $signingService ?: 's3';
        $sha = $this->getPayload($request);
        $signingConfig = new SigningConfigAWS([
            'algorithm' => SigningAlgorithm::SIGv4_ASYMMETRIC,
            'signature_type' => SignatureType::HTTP_REQUEST_HEADERS,
            'credentials_provider' => $credentials_provider,
            'signed_body_value' => $sha,
            'region' => "*",
            'service' => $signingService,
            'date' => time(),
        ]);
        $sha = $request->getHeader("x-amz-content-sha256");
        $request = $request->withoutHeader("x-amz-content-sha256");
        $invocationId = $request->getHeader("aws-sdk-invocation-id");
        $retry = $request->getHeader("aws-sdk-retry");
        $request = $request->withoutHeader("aws-sdk-invocation-id");
        $request = $request->withoutHeader("aws-sdk-retry");
        $http_request = new Request(
            $request->getMethod(),
            (string) $request->getUri(),
            [],
            array_map(function ($header) {
                return $header[0];
            }, $request->getHeaders())
        );

        Signing::signRequestAws(
            Signable::fromHttpRequest($http_request),
            $signingConfig, function ($signing_result, $error_code) use (&$http_request) {
            $signing_result->applyToHttpRequest($http_request);
        });
        $sigV4AHeaders = $http_request->headers();
        foreach ($sigV4AHeaders->toArray() as $h => $v) {
            $request = $request->withHeader($h, $v);
        }
        $request = $request->withHeader("aws-sdk-invocation-id", $invocationId);
        $request = $request->withHeader("x-amz-content-sha256", $sha);
        $request = $request->withHeader("aws-sdk-retry", $retry);
        $request = $request->withHeader("x-amz-region-set", "*");

        return $request;
    }
}
