<?php
/**
 * Copyright Amazon.com, Inc. or its affiliates. All Rights Reserved.
 * SPDX-License-Identifier: Apache-2.0.
 */
use AWS\CRT\CRT;

require_once('common.inc');

// This intentionally does not inherit from CrtTestCase because it needs a clean-room environment
final class CoreTest extends PHPUnit_Framework_TestCase {

    // The CRT should always be available in this test suite
    public function testIsAvailable() {
        $this->assertTrue(CRT::isAvailable());
    }

    // We have done nothing to necessitate loading the CRT, it should not be loaded
    public function testIsLoaded() {
        $this->assertTrue(!CRT::isLoaded());
    }
}