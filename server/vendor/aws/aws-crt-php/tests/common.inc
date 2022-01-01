<?php
/**
 * Copyright Amazon.com, Inc. or its affiliates. All Rights Reserved.
 * SPDX-License-Identifier: Apache-2.0.
 */
use AWS\CRT\CRT as CRT;

ini_set("memory_limit", "512M");

abstract class CrtTestCase extends PHPUnit_Framework_TestCase {
    private static $crt = null;
    public static function setUpBeforeClass() {
        self::$crt = new CRT();
    }

    public static function tearDownAfterClass() {
        self::$crt = null;
    }

    // Ensure that after every test there are no errors in the CRT itself
    protected function assertPostConditions() {
        if (CRT::last_error()) {
            $this->fail("Test left an error on the stack: " . CRT::error_name(CRT::last_error()));
        }
    }

    // Shim missing calls in older versions of PHPUnit
    public function __call($name, $arguments) {
        // shim expectException -> setExpectedException for PHPUnit 4.8.x
        if ($name == 'expectException') {
            $this->setExpectedException($arguments[0]);
        }
    }
}
