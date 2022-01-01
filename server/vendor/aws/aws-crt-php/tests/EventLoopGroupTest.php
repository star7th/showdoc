<?php
/**
 * Copyright Amazon.com, Inc. or its affiliates. All Rights Reserved.
 * SPDX-License-Identifier: Apache-2.0.
 */
use AWS\CRT\IO\EventLoopGroup as EventLoopGroup;

require_once('common.inc');

final class EventLoopGroupTest extends CrtTestCase {

    public function testLifetime() {
        $elg = new EventLoopGroup();
        $this->assertNotNull($elg, "Failed to create default EventLoopGroup");
        $elg = null;
    }

    public function testConstructionWithOptions() {
        $options = EventLoopGroup::defaults();
        $options['num_threads'] = 1;
        $elg = new EventLoopGroup($options);
        $this->assertNotNull($elg, "Failed to create EventLoopGroup with 1 thread");
        $elg = null;
    }
}
