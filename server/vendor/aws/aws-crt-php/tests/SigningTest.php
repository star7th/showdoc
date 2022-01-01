<?php
/**
 * Copyright Amazon.com, Inc. or its affiliates. All Rights Reserved.
 * SPDX-License-Identifier: Apache-2.0.
 */

use AWS\CRT\Auth\SignatureType;
use AWS\CRT\Auth\SigningAlgorithm;
use AWS\CRT\Auth\SigningConfigAWS;
use AWS\CRT\Auth\Signing;
use AWS\CRT\Auth\Signable;
use AWS\CRT\Auth\StaticCredentialsProvider;
use AWS\CRT\HTTP\Request;

require_once('common.inc');

final class SigningTest extends CrtTestCase {

    public function testConfigAWSLifetime() {
        $config = new SigningConfigAWS();
        $this->assertNotNull($config, "Failed to create default SigningConfigAWS");
        $config = null;
    }

    public function testConfigAWSConstructionWithOptions() {
        $options = SigningConfigAWS::defaults();
        $options['service'] = 'CRT';
        $options['region'] = 'CRT';
        $config = new SigningConfigAWS($options);
        $this->assertNotNull($config, "Failed to create SigningConfigAWS with custom options");
        $config = null;
    }

    public function testSignableFromHttpRequestLifetime() {
        $request = new Request('GET', '/');
        $signable = Signable::fromHttpRequest($request);
        $this->assertNotNull($signable, "Failed to create Signable from HTTP::Request");
        $signable = null;
    }

    public function testSignableFromChunkLifetime() {
        $chunk = "THIS IS A TEST CHUNK IT CONTAINS MULTITUDES";
        $stream = fopen("php://memory", 'r+');
        fputs($stream, $chunk);
        rewind($stream);
        $signable = Signable::fromChunk($stream);
        $this->assertNotNull($signable, "Failed to create Signable from chunk stream");
        $signable = null;
    }

    public function testSignableFromCanonicalRequestLifetime() {
        $canonical_request = "THIS IS A CANONICAL_REQUEST. IT IS DEEPLY CANONICAL";
        $signable = Signable::fromCanonicalRequest($canonical_request);
        $this->assertNotNull($signable, "Failed to create Signable from canonical request");
        $signable = null;
    }

    const SIGV4TEST_ACCESS_KEY_ID = 'AKIDEXAMPLE';
    const SIGV4TEST_SECRET_ACCESS_KEY = 'wJalrXUtnFEMI/K7MDENG+bPxRfiCYEXAMPLEKEY';
    const SIGV4TEST_SESSION_TOKEN = null;
    const SIGV4TEST_SERVICE = 'service';
    const SIGV4TEST_REGION = 'us-east-1';
    private static function SIGV4TEST_DATE() {
        return mktime(12, 36, 0, 8, 30, 2015);
    }

    public function testShouldSignHeader() {
        $credentials_provider = new StaticCredentialsProvider([
            'access_key_id' => self::SIGV4TEST_ACCESS_KEY_ID,
            'secret_access_key' => self::SIGV4TEST_SECRET_ACCESS_KEY,
            'session_token' => self::SIGV4TEST_SESSION_TOKEN,
        ]);
        $signing_config = new SigningConfigAWS([
            'algorithm' => SigningAlgorithm::SIGv4,
            'signature_type' => SignatureType::HTTP_REQUEST_HEADERS,
            'credentials_provider' => $credentials_provider,
            'region' => self::SIGV4TEST_REGION,
            'service' => self::SIGV4TEST_SERVICE,
            'date' => self::SIGV4TEST_DATE(),
            'should_sign_header' => function($header) {
                return strtolower($header) != 'x-do-not-sign';
            }
        ]);
        $http_request = new Request('GET', '/', [], [
            'Host' => 'example.amazonaws.com',
            'X-Do-Not-Sign' => 'DO NOT SIGN THIS']);
        $this->assertNotNull($http_request, "Unable to create HttpRequest for signing");
        $signable = Signable::fromHttpRequest($http_request);
        $this->assertNotNull($signable, "Unable to create signable from HttpRequest");

        Signing::signRequestAws(
            $signable, $signing_config,
            function($signing_result, $error_code) use (&$http_request) {
                $this->assertEquals(0, $error_code);
                $signing_result->applyToHttpRequest($http_request);
            }
        );

        // This signature value is computed without the X-Do-Not-Sign header above
        $headers = $http_request->headers();
        $this->assertEquals(
            'AWS4-HMAC-SHA256 Credential=AKIDEXAMPLE/20150830/us-east-1/service/aws4_request, SignedHeaders=host;x-amz-date, Signature=5fa00fa31553b73ebf1942676e86291e8372ff2a2260956d9b8aae1d763fbf31',
            $headers->get('Authorization'));
    }

    public function testSigv4HeaderSigning() {
        $credentials_provider = new StaticCredentialsProvider([
            'access_key_id' => self::SIGV4TEST_ACCESS_KEY_ID,
            'secret_access_key' => self::SIGV4TEST_SECRET_ACCESS_KEY,
            'session_token' => self::SIGV4TEST_SESSION_TOKEN,
        ]);
        $signing_config = new SigningConfigAWS([
            'algorithm' => SigningAlgorithm::SIGv4,
            'signature_type' => SignatureType::HTTP_REQUEST_HEADERS,
            'credentials_provider' => $credentials_provider,
            'region' => self::SIGV4TEST_REGION,
            'service' => self::SIGV4TEST_SERVICE,
            'date' => self::SIGV4TEST_DATE(),
        ]);
        $http_request = new Request('GET', '/', [], ['Host' => 'example.amazonaws.com']);
        $this->assertNotNull($http_request, "Unable to create HttpRequest for signing");
        $signable = Signable::fromHttpRequest($http_request);
        $this->assertNotNull($signable, "Unable to create signable from HttpRequest");

        Signing::signRequestAws(
            $signable, $signing_config,
            function($signing_result, $error_code) use (&$http_request) {
                $this->assertEquals(0, $error_code);
                $signing_result->applyToHttpRequest($http_request);
            }
        );

        $headers = $http_request->headers();
        $this->assertEquals(
            'AWS4-HMAC-SHA256 Credential=AKIDEXAMPLE/20150830/us-east-1/service/aws4_request, SignedHeaders=host;x-amz-date, Signature=5fa00fa31553b73ebf1942676e86291e8372ff2a2260956d9b8aae1d763fbf31',
            $headers->get('Authorization'));
        $this->assertEquals('20150830T123600Z', $headers->get('X-Amz-Date'));
    }

    public function testSigV4aHeaderSigning() {
        $credentials_provider = new StaticCredentialsProvider([
            'access_key_id' => self::SIGV4TEST_ACCESS_KEY_ID,
            'secret_access_key' => self::SIGV4TEST_SECRET_ACCESS_KEY,
            'session_token' => self::SIGV4TEST_SESSION_TOKEN,
        ]);
        $signing_config = new SigningConfigAWS([
            'algorithm' => SigningAlgorithm::SIGv4_ASYMMETRIC,
            'signature_type' => SignatureType::HTTP_REQUEST_HEADERS,
            'credentials_provider' => $credentials_provider,
            'region' => self::SIGV4TEST_REGION,
            'service' => self::SIGV4TEST_SERVICE,
            'date' => self::SIGV4TEST_DATE(),
        ]);

        $http_request = new Request('GET', '/', [], ['Host' => 'example.amazonaws.com']);
        $this->assertNotNull($http_request, "Unable to create HttpRequest for signing");
        $signable = Signable::fromHttpRequest($http_request);
        $this->assertNotNull($signable, "Unable to create signable from HttpRequest");

        Signing::signRequestAws(
            $signable, $signing_config,
            function($signing_result, $error_code) use (&$http_request) {
                $this->assertEquals(0, $error_code);
                $signing_result->applyToHttpRequest($http_request);
            }
        );

        $headers = $http_request->headers();
        $auth_header_value = $headers->get('Authorization');
        $this->assertNotNull($auth_header_value);
        $this->assertStringStartsWith(
            'AWS4-ECDSA-P256-SHA256 Credential=AKIDEXAMPLE/20150830/service/aws4_request, SignedHeaders=host;x-amz-date;x-amz-region-set, Signature=',
            $auth_header_value);
        $this->assertEquals('20150830T123600Z', $headers->get('X-Amz-Date'));
    }
}
