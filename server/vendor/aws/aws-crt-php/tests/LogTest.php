<?php
/**
 * Copyright Amazon.com, Inc. or its affiliates. All Rights Reserved.
 * SPDX-License-Identifier: Apache-2.0.
 */
use AWS\CRT\Log;

require_once('common.inc');

class LogTest extends CrtTestCase {

    public function testLogToStream() {
        $log_stream = fopen("php://memory", "r+");
        $this->assertNotNull($log_stream);
        Log::toStream($log_stream);
        Log::setLogLevel(Log::TRACE);
        Log::log(Log::TRACE, "THIS IS A TEST");
        $this->assertTrue(rewind($log_stream));
        $log_contents = stream_get_contents($log_stream, -1, 0);
        $this->assertStringEndsWith("THIS IS A TEST", trim($log_contents));
        Log::stop();
    }
}
