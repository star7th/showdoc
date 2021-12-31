<?php
/**
 * Copyright Amazon.com, Inc. or its affiliates. All Rights Reserved.
 * SPDX-License-Identifier: Apache-2.0.
 */
use AWS\CRT\CRT;

require_once('common.inc');

final class CrcTest extends CrtTestCase {

    public function testCrc32ZeroesOneShot() {
        $input = implode(array_map("chr", array_fill(0, 32, 0)));
        $output = CRT::crc32($input);
        $expected = 0x190A55AD;
        $this->assertEquals($output, $expected);
    }

    public function testCrc32ZeroesIterated() {
        $output = 0;
        for ($i = 0; $i < 32; $i++) {
            $output = CRT::crc32("\x00", $output);
        }
        $expected = 0x190A55AD;
        $this->assertEquals($output, $expected);
    }

    public function testCrc32ValuesOneShot() {
        $input = implode(array_map("chr", range(0, 31)));
        $output = CRT::crc32($input);
        $expected = 0x91267E8A;
        $this->assertEquals($output, $expected);
    }

    public function testCrc32ValuesIterated() {
        $output = 0;
        foreach (range(0, 31) as $n) {
            $output = CRT::crc32(chr($n), $output);
        }
        $expected = 0x91267E8A;
        $this->assertEquals($output, $expected);
    }

    public function testCrc32LargeBuffer() {
        $input = implode(array_map("chr", array_fill(0, 1 << 20, 0)));
        $output = CRT::crc32($input);
        $expected = 0xA738EA1C;
        $this->assertEquals($output, $expected);
    }

    public function testCrc32cZeroesOneShot() {
        $input = implode(array_map("chr", array_fill(0, 32, 0)));
        $output = CRT::crc32c($input);
        $expected = 0x8A9136AA;
        $this->assertEquals($output, $expected);
    }

    public function testCrc32cZeroesIterated() {
        $output = 0;
        for ($i = 0; $i < 32; $i++) {
            $output = CRT::crc32c("\x00", $output);
        }
        $expected = 0x8A9136AA;
        $this->assertEquals($output, $expected);
    }

    public function testCrc32cValuesOneShot() {
        $input = implode(array_map("chr", range(0, 31)));
        $output = CRT::crc32c($input);
        $expected = 0x46DD794E;
        $this->assertEquals($output, $expected);
    }

    public function testCrc32cValuesIterated() {
        $output = 0;
        foreach (range(0, 31) as $n) {
            $output = CRT::crc32c(chr($n), $output);
        }
        $expected = 0x46DD794E;
        $this->assertEquals($output, $expected);
    }

    public function testCrc32cLargeBuffer() {
        $input = implode(array_map("chr", array_fill(0, 1 << 20, 0)));
        $output = CRT::crc32c($input);
        $expected = 0x14298C12;
        $this->assertEquals($output, $expected);
    }
    
}
