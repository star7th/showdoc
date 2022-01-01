<?php
/**
 * Copyright Amazon.com, Inc. or its affiliates. All Rights Reserved.
 * SPDX-License-Identifier: Apache-2.0.
 */
use AWS\CRT\HTTP\Headers;
use AWS\CRT\HTTP\Request;
use AWS\CRT\HTTP\Response;

require_once('common.inc');

final class HttpMessageTest extends CrtTestCase {
    public function testHeaders() {
        $headers = new Headers();
        $this->assertSame(0, $headers->count());
    }

    public function testHeadersMarshalling() {
        $headers_array = [
            "host" => "s3.amazonaws.com",
            "test" => "this is a test header value"
        ];
        $headers = new Headers($headers_array);
        $this->assertSame(2, $headers->count());
        $this->assertSame($headers_array['host'], $headers->get('host'));
        $this->assertSame($headers_array['test'], $headers->get('test'));
        $buffer = Headers::marshall($headers);
        $headers_copy = Headers::unmarshall($buffer);
        $this->assertSame(2, $headers_copy->count());
        $this->assertSame($headers_array['host'], $headers_copy->get('host'));
        $this->assertSame($headers_array['test'], $headers_copy->get('test'));
    }

    private function assertMessagesMatch($a, $b) {
        $this->assertSame($a->method(), $b->method());
        $this->assertSame($a->path(), $b->path());
        $this->assertSame($a->query(), $b->query());
        $this->assertSame($a->headers()->toArray(), $b->headers()->toArray());
    }

    public function testRequestMarshalling() {
        $headers = [
            "host" => "s3.amazonaws.com",
            "test" => "this is a test header value"
        ];
        $method = "GET";
        $path = "/index.php";
        $query = [];

        $msg = new Request($method, $path, $query, $headers);
        $msg_buf = Request::marshall($msg);
        $msg_copy = Request::unmarshall($msg_buf);

        $this->assertMessagesMatch($msg, $msg_copy);
    }

    public function testRequestMarshallingWithQueryParams() {
        $headers = [
            "host" => "s3.amazonaws.com",
            "test" => "this is a test header value"
        ];
        $method = "GET";
        $path = "/index.php";
        $query = [
            'request' => '1',
            'test' => 'true',
            'answer' => '42',
            'foo' => 'bar',
        ];

        $msg = new Request($method, $path, $query, $headers);
        $msg_buf = Request::marshall($msg);
        $msg_copy = Request::unmarshall($msg_buf);

        $this->assertMessagesMatch($msg, $msg_copy);
    }

    public function testResponseMarshalling() {
        $headers = [
            "content-length" => "42",
            "test" => "this is a test header value"
        ];
        $method = "GET";
        $path = "/index.php";
        $query = [
            'response' => '1'
        ];

        $msg = new Response($method, $path, $query, $headers, 200);
        $msg_buf = Request::marshall($msg);
        $msg_copy = Request::unmarshall($msg_buf);

        $this->assertMessagesMatch($msg, $msg_copy);
    }
}
