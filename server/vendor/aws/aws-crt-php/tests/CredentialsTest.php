<?php
/**
 * Copyright Amazon.com, Inc. or its affiliates. All Rights Reserved.
 * SPDX-License-Identifier: Apache-2.0.
 */
use AWS\CRT\Auth\AwsCredentials as AwsCredentials;
use AWS\CRT\Auth\StaticCredentialsProvider as StaticCredentialsProvider;

require_once('common.inc');

final class CredentialsTest extends CrtTestCase {

    public function testEmptyCredentials() {
        $this->expectException(InvalidArgumentException::class);
        $creds = new AwsCredentials(AwsCredentials::defaults());
        $this->assertNotNull($creds, "Failed to create default/empty credentials");
        $creds = null;
    }

    private function getCredentialsConfig() {
        $options = AwsCredentials::defaults();
        $options['access_key_id'] = 'TESTAWSACCESSKEYID';
        $options['secret_access_key'] = 'TESTSECRETaccesskeyThatDefinitelyDoesntWork';
        $options['session_token'] = 'ThisIsMyTestSessionTokenIMadeItUpMyself';
        $options['expiration_timepoint_seconds'] = 42;
        return $options;
    }

    public function testCredentialsLifetime() {
        $options = $this->getCredentialsConfig();
        $creds = new AwsCredentials($options);
        $this->assertNotNull($creds, "Failed to create Credentials with options");
        $this->assertEquals($creds->access_key_id, $options['access_key_id']);
        $this->assertEquals($creds->secret_access_key, $options['secret_access_key']);
        $this->assertEquals($creds->session_token, $options['session_token']);
        $this->assertEquals($creds->expiration_timepoint_seconds, $options['expiration_timepoint_seconds']);
        $creds = null;
    }

    public function testStaticCredentialsProviderLifetime() {
        $options = $this->getCredentialsConfig();
        $provider = new StaticCredentialsProvider($options);
        $this->assertNotNull($provider, "Failed to create StaticCredentialsProvider");
        $provider = null;
    }
}
