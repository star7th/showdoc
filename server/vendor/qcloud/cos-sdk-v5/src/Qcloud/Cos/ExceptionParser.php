<?php

namespace Qcloud\Cos;

use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

/**
 * Parses default XML exception responses
 */
class ExceptionParser {

    public function parse(RequestInterface $request, ResponseInterface $response) {
        $data = array(
            'code'       => null,
            'message'    => null,
            //'type'       => $response->isClientError() ? 'client' : 'server',
            'type'       => 'client',
            'request_id' => null,
            'parsed'     => null
        );

		$body = strval($response->getBody());

        if (empty($body)) {
            $this->parseHeaders($request, $response, $data);
            return $data;
        }

        try {
            $xml = new \SimpleXMLElement(utf8_encode($body));
            $this->parseBody($xml, $data);
            return $data;
        } catch (\Exception $e) {
            $data['code'] = 'PhpInternalXmlParseError';
            $data['message'] = 'A non-XML response was received';
            return $data;
        }
    }

    /**
     * Parses additional exception information from the response headers
     *
     * @param RequestInterface $request  Request that was issued
     * @param Response         $response The response from the request
     * @param array            $data     The current set of exception data
     */
    protected function parseHeaders(RequestInterface $request, ResponseInterface $response, array &$data) {
        $data['message'] = $response->getStatusCode() . ' ' . $response->getReasonPhrase();
        $requestId = $response->getHeader('x-cos-request-id');
        if (isset($requestId[0])) {
            $requestId = $requestId[0];
            $data['request_id'] = $requestId;
            $data['message'] .= " (Request-ID: $requestId)";
        }

        // Get the request
        $status  = $response->getStatusCode();
        $method  = $request->getMethod();

        // Attempt to determine code for 403s and 404s
        if ($status === 403) {
            $data['code'] = 'AccessDenied';
        } elseif ($method === 'HEAD' && $status === 404) {
            $path   = explode('/', trim($request->getUri()->getPath(), '/'));
            $host   = explode('.', $request->getUri()->getHost());
            $bucket = (count($host) >= 4) ? $host[0] : array_shift($path);
            $object = array_shift($path);

            if ($bucket && $object) {
                $data['code'] = 'NoSuchKey';
            } elseif ($bucket) {
                $data['code'] = 'NoSuchBucket';
            }
        }
    }

    /**
     * Parses additional exception information from the response body
     *
     * @param \SimpleXMLElement $body The response body as XML
     * @param array             $data The current set of exception data
     */
    protected function parseBody(\SimpleXMLElement $body, array &$data) {
        $data['parsed'] = $body;

        $namespaces = $body->getDocNamespaces();
        if (isset($namespaces[''])) {
            // Account for the default namespace being defined and PHP not being able to handle it :(
            $body->registerXPathNamespace('ns', $namespaces['']);
            $prefix = 'ns:';
        } else {
            $prefix = '';
        }

        if ($tempXml = $body->xpath("//{$prefix}Code[1]")) {
            $data['code'] = (string) $tempXml[0];
        }

        if ($tempXml = $body->xpath("//{$prefix}Message[1]")) {
            $data['message'] = (string) $tempXml[0];
        }

        $tempXml = $body->xpath("//{$prefix}RequestId[1]");
        if (empty($tempXml)) {
            $tempXml = $body->xpath("//{$prefix}RequestID[1]");
        }
        if (isset($tempXml[0])) {
            $data['request_id'] = (string) $tempXml[0];
        }
    }
}
