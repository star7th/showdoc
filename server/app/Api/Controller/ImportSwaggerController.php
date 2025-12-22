<?php

namespace App\Api\Controller;

use App\Common\BaseController;
use App\Model\Item;
use App\Common\Helper\Convert;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;

class ImportSwaggerController extends BaseController
{
    private $jsonArray = [];
    private $urlPre = '';

    /**
     * 导入 Swagger/OpenAPI 文档
     *
     * @param Request $request
     * @param Response $response
     * @return Response
     */
    public function import(Request $request, Response $response): Response
    {
        set_time_limit(100);
        ini_set('memory_limit', '6000M');

        $user = [];
        if ($error = $this->requireLoginUser($request, $response, $user)) {
            return $error;
        }

        $uid = (int) ($user['uid'] ?? 0);
        $itemId = $this->getParam($request, 'item_id', 0);

        if ($itemId > 0) {
            if (!$this->checkItemEdit($uid, $itemId)) {
                return $this->error($response, 10302, '没有权限');
            }
        }

        // 获取上传的文件
        $uploadedFiles = $request->getUploadedFiles();
        if (empty($uploadedFiles['file'])) {
            return $this->error($response, 10101, '请上传文件');
        }

        $file = $uploadedFiles['file'];
        $filename = $file->getClientFilename();

        // 检查文件扩展名（只允许 .json 文件）
        $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        if ($ext !== 'json') {
            return $this->error($response, 10101, '只支持上传 JSON 格式的 Swagger/OpenAPI 文件');
        }

        $tmpFile = sys_get_temp_dir() . '/' . \App\Common\Helper\FileHelper::getRandStr() . '.json';
        $file->moveTo($tmpFile);

        $json = file_get_contents($tmpFile);
        @unlink($tmpFile);

        $jsonArray = json_decode($json, true);
        unset($json);

        if (empty($jsonArray) || !isset($jsonArray['info'])) {
            return $this->error($response, 10101, '无效的 Swagger/OpenAPI 文件');
        }

        $jsonArray['item_id'] = $itemId;
        $this->jsonArray = $jsonArray;

        // 根据 Swagger/OpenAPI 版本分别处理
        $swaggerVersion = '';
        if (isset($jsonArray['swagger'])) {
            $swaggerVersion = $jsonArray['swagger'];
        } elseif (isset($jsonArray['openapi'])) {
            $swaggerVersion = $jsonArray['openapi'];
        }

        // 设置 URL 前缀
        if (strstr($swaggerVersion, '2.')) {
            // Swagger 2.0 格式
            $scheme = $jsonArray['schemes'][0] ?? 'http';
            if (!empty($jsonArray['host'])) {
                $this->urlPre = $scheme . "://" . $jsonArray['host'] . ($jsonArray['basePath'] ?? '');
            }
        } else {
            // OpenAPI 3.0 格式
            if (!empty($jsonArray['servers'][0]['url'])) {
                $this->urlPre = $jsonArray['servers'][0]['url'];
            }
        }

        // 转换10次（解析引用）
        for ($i = 0; $i < 10; $i++) {
            $this->jsonArray = $this->transferDefinition($this->jsonArray);
        }

        return $this->fromSwagger($this->jsonArray, $itemId, $swaggerVersion, $uid, $response, $request);
    }

    /**
     * 从 Swagger 导入
     */
    private function fromSwagger(array $jsonArray, int $itemId, string $swaggerVersion, int $uid, Response $response, ?Request $request = null): Response
    {
        $from = $request ? $this->getParam($request, 'from', '') : '';

        $itemArray = [
            'item_id'         => $jsonArray['item_id'],
            'item_name'       => $jsonArray['info']['title'] ?? 'from swagger',
            'item_type'       => ($from == 'runapi') ? '3' : '1',
            'item_description' => $jsonArray['info']['description'] ?? '',
            'password'        => time() . rand(),
            'members'         => [],
            'pages'           => [
                'pages'    => [],
                'catalogs' => $this->getAllTagsLogs($jsonArray, $swaggerVersion, $request),
            ],
        ];

        $itemId = Item::import(json_encode($itemArray), $uid, $itemId);

        return $this->success($response, ['item_id' => $itemId]);
    }

    /**
     * 获取所有标签（目录）
     */
    private function getAllTagsLogs(array $jsonArray, string $swaggerVersion, ?Request $request = null): array
    {
        $catalogsMap = [
            'fromSwagger' => ['cat_name' => 'from swagger', 'pages' => []],
        ];

        $paths = $jsonArray['paths'] ?? [];
        foreach ($paths as $url => $value) {
            foreach ($value as $method => $value2) {
                $tags = $value2['tags'] ?? [];
                if (empty($tags)) {
                    $page = $this->requestToDoc($method, $url, $value2, $jsonArray, $swaggerVersion, $request);
                    if (!empty($page['page_title'])) {
                        $catalogsMap['fromSwagger']['pages'][] = $page;
                    }
                } else {
                    foreach ($tags as $tag) {
                        if (!key_exists($tag, $catalogsMap)) {
                            $page = $this->requestToDoc($method, $url, $value2, $jsonArray, $swaggerVersion, $request);
                            if (!empty($page['page_title']) && !empty($page['page_content'])) {
                                $catalogsMap[$tag] = ['cat_name' => $tag, 'pages' => [$page]];
                            }
                        } else {
                            $page = $this->requestToDoc($method, $url, $value2, $jsonArray, $swaggerVersion, $request);
                            if (!empty($page['page_title']) && !empty($page['page_content'])) {
                                $catalogsMap[$tag]['pages'][] = $page;
                            }
                        }
                    }
                }
            }
        }

        $catalogs = [];
        foreach ($catalogsMap as $value) {
            $catalogs[] = $value;
        }

        return $catalogs;
    }

    /**
     * 请求转文档
     */
    private function requestToDoc(string $method, string $url, array $request, array $jsonArray, string $swaggerVersion, ?Request $httpRequest = null): array
    {
        $from = $httpRequest ? $this->getParam($httpRequest, 'from', '') : '';
        $res = $this->requestToApi($method, $url, $request, $jsonArray, $swaggerVersion);
        if ($from == 'runapi') {
            return $res;
        } else {
            $convert = new Convert();
            $res['page_content'] = $convert->runapiToMd($res['page_content']);
            return $res;
        }
    }

    /**
     * 请求转 API 格式
     */
    private function requestToApi(string $method, string $url, array $request, array $jsonArray, string $swaggerVersion): array
    {
        $return = [];
        $pageTitle = $request['summary'] ?? $request['description'] ?? '';
        if (empty($pageTitle) && !empty($request['operationId'])) {
            $pageTitle = $request['operationId'];
        }
        $pageTitle = mb_substr($pageTitle, 0, 50, 'utf-8');
        $return['page_title'] = $pageTitle;
        $return['s_number'] = 99;
        $return['page_comments'] = '';

        $contentArray = [
            'info'     => [
                'from'        => 'runapi',
                'type'       => 'api',
                'title'      => $request['summary'] ?? $request['description'] ?? '',
                'description' => $request['description'] ?? '',
                'method'     => strtolower($method),
                'url'        => $this->urlPre . $url,
                'remark'     => '',
            ],
            'request'  => [
                'params'  => [
                    'mode'      => 'formdata',
                    'json'      => '',
                    'jsonDesc'  => [],
                    'urlencoded' => [],
                    'formdata'  => [],
                ],
                'query'   => [],
                'headers' => [],
                'cookies' => [],
                'auth'    => [],
            ],
            'response' => [],
            'extend'   => [],
        ];

        // 根据版本处理请求体
        if (strstr($swaggerVersion, '2.')) {
            $this->processSwagger2Request($request, $contentArray);
        } else {
            $this->processOpenAPI3Request($request, $contentArray);
        }

        // 根据版本处理响应
        if (strstr($swaggerVersion, '2.')) {
            $this->processSwagger2Response($request, $contentArray);
        } else {
            $this->processOpenAPI3Response($request, $contentArray);
        }

        $return['page_content'] = json_encode($contentArray);
        return $return;
    }

    /**
     * 获取引用定义
     */
    private function getDefinition(string $refStr): ?array
    {
        $jsonArray = $this->jsonArray;
        $strArray = explode('/', $refStr);

        $targetArray = null;
        if (isset($strArray[2])) {
            $targetArray = $jsonArray[$strArray[1]][$strArray[2]] ?? null;
        }
        if (isset($strArray[3]) && $targetArray) {
            $targetArray = $targetArray[$strArray[3]] ?? null;
        }

        return $targetArray ?: null;
    }

    /**
     * 定义转 JSON 数组
     */
    private function definitionToJsonArray(array $refArray): array
    {
        $res = [];
        if (!isset($refArray['properties'])) {
            return $res;
        }

        foreach ($refArray['properties'] as $key => $value) {
            $remark = $value['title'] ?? $value['description'] ?? '';

            $exampleValue = $value['example'] ?? '';

            $required = '0';
            if (isset($refArray['required']) && is_array($refArray['required']) && in_array($key, $refArray['required'])) {
                $required = '1';
            }

            $paramType = $value['type'] ?? 'string';
            if ($paramType === 'int') {
                $paramType = 'integer';
            }

            $res[] = [
                'name'    => $key,
                'type'    => $paramType,
                'value'   => $exampleValue,
                'require' => $required,
                'remark'  => $remark,
            ];

            if (isset($value['properties'])) {
                $tmpJsonArray = $this->definitionToJsonArray($value);
                $res = array_merge($res, $tmpJsonArray);
            }
            if (isset($value['items'])) {
                $tmpJsonArray = $this->definitionToJsonArray($value['items']);
                $res = array_merge($res, $tmpJsonArray);
            }
        }

        return $res;
    }

    /**
     * JSON 数组转字符串
     */
    private function jsonArrayToStr(array $jsonArray): string
    {
        $resArray = $this->toB($jsonArray);
        return json_encode($resArray);
    }

    /**
     * 转换为示例值
     */
    private function toB(array $jsonArray): array
    {
        $resArray = [];
        if (isset($jsonArray['properties'])) {
            foreach ($jsonArray['properties'] as $key => $value) {
                $resArray[$key] = $this->formatToFakeValue($value['type'] ?? 'string', $value['format'] ?? '');
                if (isset($value['items'])) {
                    $resArray[$key] = [$this->toB($value['items'])];
                }
                if (isset($value['properties'])) {
                    $resArray[$key] = $this->toB($value);
                }
            }
        } elseif (isset($jsonArray['$ref'])) {
            $refData = $this->getDefinition($jsonArray['$ref']);
            if ($refData) {
                $resArray = $this->toB($refData);
            }
        } elseif (isset($jsonArray['type']) && $jsonArray['type'] == 'array' && isset($jsonArray['items'])) {
            $resArray = [$this->toB($jsonArray['items'])];
        }
        return $resArray;
    }

    /**
     * 格式化假值
     * 
     * @param string $type
     * @param string $format
     * @return mixed
     */
    private function formatToFakeValue(string $type, string $format)
    {
        switch ($format) {
            case 'int64':
            case 'int32':
                return 0;
            case 'double':
                return 0.00;
            case 'date-time':
                return date('Y-m-d H:i:s');
        }

        switch ($type) {
            case 'boolean':
                return true;
        }

        return $type;
    }

    /**
     * 转换定义（将引用改为真实数据）
     */
    private function transferDefinition(array $curArray): array
    {
        foreach ($curArray as $key => $value) {
            if (is_array($value)) {
                $curArray[$key] = $this->transferDefinition($value);
                if (isset($value['$ref'])) {
                    $curArray[$key] = $this->getDefinition($value['$ref']) ?? $value;
                }
            }
        }
        return $curArray;
    }

    /**
     * 处理 OpenAPI 3.0 请求
     */
    private function processOpenAPI3Request(array $request, array &$contentArray): void
    {
        // 添加请求头处理
        if (isset($request['headers'])) {
            foreach ($request['headers'] as $header) {
                $contentArray['request']['headers'][] = [
                    'name'    => $header['name'],
                    'type'    => 'string',
                    'value'   => $header['value'] ?? '',
                    'require' => ($header['required'] ?? false) ? '1' : '0',
                    'remark'  => $header['description'] ?? '',
                ];
            }
        }

        // 处理请求体
        if (isset($request['requestBody']['content'])) {
            $hasJsonContent = false;
            $hasFormContent = false;

            if (isset($request['requestBody']['content']['application/json'])) {
                $hasJsonContent = true;
                $this->processContentSchema($request['requestBody']['content']['application/json'], $contentArray, 'json');
            }

            if (!$hasJsonContent && isset($request['requestBody']['content']['application/x-www-form-urlencoded'])) {
                $hasFormContent = true;
                $this->processContentSchema($request['requestBody']['content']['application/x-www-form-urlencoded'], $contentArray, 'formdata');
            }

            if (!$hasJsonContent && !$hasFormContent && isset($request['requestBody']['content']['multipart/form-data'])) {
                $this->processContentSchema($request['requestBody']['content']['multipart/form-data'], $contentArray, 'formdata');
            }

            if (!$hasJsonContent && !$hasFormContent) {
                foreach ($request['requestBody']['content'] as $contentType => $content) {
                    $mode = (strpos($contentType, 'json') !== false) ? 'json' : 'formdata';
                    $this->processContentSchema($content, $contentArray, $mode);
                    break;
                }
            }
        }

        $this->processParameters($request, $contentArray);
    }

    /**
     * 处理内容 Schema
     */
    private function processContentSchema(array $content, array &$contentArray, string $mode): void
    {
        $contentArray['request']['params']['mode'] = $mode;

        if (isset($content['schema'])) {
            $schema = $content['schema'];

            if ($mode === 'json' && (isset($schema['$ref']) || (isset($schema['type']) && $schema['type'] === 'object'))) {
                $jsonObj = $this->toB($schema);
                $contentArray['request']['params']['json'] = json_encode($jsonObj);

                if (!empty($jsonObj)) {
                    $schemaForDesc = $schema;
                    if (isset($schema['$ref'])) {
                        $refData = $this->getDefinition($schema['$ref']);
                        if ($refData) {
                            $schemaForDesc = $refData;
                        }
                    }
                    $jsonDesc = $this->definitionToJsonArray($schemaForDesc);
                    $contentArray['request']['params']['jsonDesc'] = $jsonDesc;
                }
            }

            if (isset($schema['properties'])) {
                foreach ($schema['properties'] as $key => $value) {
                    $exampleValue = $value['example'] ?? '';

                    $required = '0';
                    if (isset($schema['required']) && is_array($schema['required']) && in_array($key, $schema['required'])) {
                        $required = '1';
                    }

                    if (isset($value['type']) && $value['type'] === 'object' && isset($value['properties'])) {
                        $subProperties = $this->definitionToJsonArray($value);
                        foreach ($subProperties as $sub) {
                            $contentArray['request']['params']['formdata'][] = [
                                'name'    => $key . '[' . $sub['name'] . ']',
                                'type'    => $sub['type'],
                                'value'   => $sub['value'] ?? $exampleValue,
                                'require' => ($sub['require'] ?? false) ? '1' : '0',
                                'remark'  => $sub['remark'] ?? ($value['description'] ?? ''),
                            ];
                        }
                    } else {
                        $paramType = $value['type'] ?? 'string';
                        if ($paramType === 'int') {
                            $paramType = 'integer';
                        }

                        $contentArray['request']['params']['formdata'][] = [
                            'name'    => $key,
                            'type'    => $paramType,
                            'value'   => $exampleValue,
                            'require' => $required,
                            'remark'  => $value['description'] ?? '',
                        ];
                    }
                }
            } elseif (isset($schema['$ref'])) {
                $refData = $this->getDefinition($schema['$ref']);
                if ($refData && isset($refData['properties'])) {
                    foreach ($refData['properties'] as $key => $value) {
                        $exampleValue = $value['example'] ?? '';

                        $required = '0';
                        if (isset($refData['required']) && is_array($refData['required']) && in_array($key, $refData['required'])) {
                            $required = '1';
                        }

                        $paramType = $value['type'] ?? 'string';
                        if ($paramType === 'int') {
                            $paramType = 'integer';
                        }

                        $contentArray['request']['params']['formdata'][] = [
                            'name'    => $key,
                            'type'    => $paramType,
                            'value'   => $exampleValue,
                            'require' => $required,
                            'remark'  => $value['description'] ?? '',
                        ];
                    }
                }
            }
        }
    }

    /**
     * 处理 Swagger 2.0 请求
     */
    private function processSwagger2Request(array $request, array &$contentArray): void
    {
        if (isset($request['parameters'])) {
            $hasBodyParam = false;
            $bodySchema = null;

            foreach ($request['parameters'] as $param) {
                if (($param['in'] ?? '') == 'body' && isset($param['schema'])) {
                    $hasBodyParam = true;
                    $bodySchema = $param['schema'];
                    break;
                }
            }

            if ($hasBodyParam && $bodySchema) {
                if (isset($request['consumes']) && (in_array('application/json', $request['consumes']) || in_array('text/json', $request['consumes']))) {
                    $contentArray['request']['params']['mode'] = 'json';
                    $jsonObj = $this->toB($bodySchema);
                    $contentArray['request']['params']['json'] = json_encode($jsonObj);

                    if (!empty($jsonObj)) {
                        $schemaForDesc = $bodySchema;
                        if (isset($bodySchema['$ref'])) {
                            $refData = $this->getDefinition($bodySchema['$ref']);
                            if ($refData) {
                                $schemaForDesc = $refData;
                            }
                        }
                        $jsonDesc = $this->definitionToJsonArray($schemaForDesc);
                        $contentArray['request']['params']['jsonDesc'] = $jsonDesc;
                    }
                }

                if (isset($bodySchema['$ref'])) {
                    $refData = $this->getDefinition($bodySchema['$ref']);
                    if ($refData) {
                        $this->processSchemaProperties($refData, $contentArray);
                    }
                } elseif (isset($bodySchema['properties'])) {
                    $this->processSchemaProperties($bodySchema, $contentArray);
                }
            }

            $hasFormDataParams = false;
            foreach ($request['parameters'] as $param) {
                if (($param['in'] ?? '') == 'formData') {
                    $hasFormDataParams = true;

                    $exampleValue = $param['example'] ?? '';

                    $paramType = $param['type'] ?? 'string';
                    if ($paramType === 'int') {
                        $paramType = 'integer';
                    }

                    $contentArray['request']['params']['formdata'][] = [
                        'name'    => $param['name'],
                        'type'    => $paramType,
                        'value'   => $exampleValue,
                        'require' => ($param['required'] ?? false) ? '1' : '0',
                        'remark'  => $param['description'] ?? '',
                    ];
                }
            }

            if (!$hasBodyParam && $hasFormDataParams) {
                $contentArray['request']['params']['mode'] = 'formdata';
            }
        }

        $this->processParameters($request, $contentArray);
    }

    /**
     * 处理 Schema 属性
     */
    private function processSchemaProperties(array $schema, array &$contentArray): void
    {
        if (isset($schema['properties'])) {
            foreach ($schema['properties'] as $key => $value) {
                $exampleValue = $value['example'] ?? '';

                $required = '0';
                if (isset($schema['required']) && is_array($schema['required']) && in_array($key, $schema['required'])) {
                    $required = '1';
                }

                $paramType = $value['type'] ?? 'string';
                if ($paramType === 'int') {
                    $paramType = 'integer';
                }

                $contentArray['request']['params']['formdata'][] = [
                    'name'    => $key,
                    'type'    => $paramType,
                    'value'   => $exampleValue,
                    'require' => $required,
                    'remark'  => $value['description'] ?? '',
                ];
            }
        }
    }

    /**
     * 处理参数（query 和 header）
     */
    private function processParameters(array $request, array &$contentArray): void
    {
        if (isset($request['parameters'])) {
            foreach ($request['parameters'] as $param) {
                if (($param['in'] ?? '') == 'query') {
                    $exampleValue = $param['example'] ?? '';

                    $paramType = $param['type'] ?? 'string';
                    if ($paramType === 'int') {
                        $paramType = 'integer';
                    }

                    $contentArray['request']['query'][] = [
                        'name'    => $param['name'],
                        'type'    => $paramType,
                        'value'   => $exampleValue,
                        'require' => ($param['required'] ?? false) ? '1' : '0',
                        'remark'  => $param['description'] ?? '',
                    ];
                }

                if (($param['in'] ?? '') == 'header') {
                    $contentArray['request']['headers'][] = [
                        'name'    => $param['name'],
                        'type'    => 'string',
                        'value'   => $param['example'] ?? '',
                        'require' => ($param['required'] ?? false) ? '1' : '0',
                        'remark'  => $param['description'] ?? '',
                    ];
                }
            }
        }
    }

    /**
     * 处理 Swagger 2.0 响应
     */
    private function processSwagger2Response(array $request, array &$contentArray): void
    {
        if (isset($request['responses']['200'])) {
            $refArray = [];
            $example = null;

            if (isset($request['responses']['200']['content'])) {
                foreach ($request['responses']['200']['content'] as $contentType => $content) {
                    if (isset($content['example'])) {
                        $example = $content['example'];
                        break;
                    }
                    if (isset($content['schema']['example'])) {
                        $example = $content['schema']['example'];
                        break;
                    }
                }
            }

            if (!$example && isset($request['responses']['200']['examples'])) {
                foreach ($request['responses']['200']['examples'] as $contentType => $content) {
                    $example = $content;
                    break;
                }
            }

            if (isset($request['responses']['200']['schema'])) {
                $refArray = $request['responses']['200']['schema'];
                if (!$example && isset($refArray['example'])) {
                    $example = $refArray['example'];
                }
            }

            if ($example !== null) {
                if (is_string($example)) {
                    $unescapedExample = stripslashes($example);
                    if ((substr($unescapedExample, 0, 1) === '{' || substr($unescapedExample, 0, 1) === '[') &&
                        json_decode($unescapedExample) !== null
                    ) {
                        $contentArray['response']['responseExample'] = $unescapedExample;
                        $parsedExample = json_decode($unescapedExample, true);
                        if (json_last_error() === JSON_ERROR_NONE) {
                            $contentArray['response']['responseParamsDesc'] = $this->exampleToParamsDesc($parsedExample);
                        }
                    } else {
                        $contentArray['response']['responseExample'] = $example;
                    }
                } else {
                    $contentArray['response']['responseExample'] = json_encode($example);
                    $contentArray['response']['responseParamsDesc'] = $this->exampleToParamsDesc($example);
                }
            } elseif (!empty($refArray)) {
                $jsonArray = $this->definitionToJsonArray($refArray);
                $jsonStr = $this->jsonArrayToStr($refArray);
                $contentArray['response']['responseExample'] = $jsonStr;
                $contentArray['response']['responseParamsDesc'] = $jsonArray;
            } else {
                $contentArray['response']['responseExample'] = '{}';
                $contentArray['response']['responseParamsDesc'] = [];
            }
        }
    }

    /**
     * 处理 OpenAPI 3.0 响应
     */
    private function processOpenAPI3Response(array $request, array &$contentArray): void
    {
        if (isset($request['responses']['200'])) {
            $refArray = [];
            $example = null;

            if (isset($request['responses']['200']['content'])) {
                foreach ($request['responses']['200']['content'] as $contentType => $content) {
                    if (isset($content['examples']) && !empty($content['examples'])) {
                        foreach ($content['examples'] as $exampleKey => $exampleObj) {
                            if (isset($exampleObj['value'])) {
                                $example = $exampleObj['value'];
                                break;
                            }
                        }
                        if ($example !== null) {
                            break;
                        }
                    }

                    if (isset($content['example'])) {
                        $example = $content['example'];
                        break;
                    }

                    if (isset($content['schema']['example'])) {
                        $example = $content['schema']['example'];
                        break;
                    }

                    if (isset($content['schema'])) {
                        $refArray = $content['schema'];
                    }
                }
            }

            if ($example !== null) {
                if (is_string($example)) {
                    $unescapedExample = stripslashes($example);
                    if ((substr($unescapedExample, 0, 1) === '{' || substr($unescapedExample, 0, 1) === '[') &&
                        json_decode($unescapedExample) !== null
                    ) {
                        $contentArray['response']['responseExample'] = $unescapedExample;
                        $parsedExample = json_decode($unescapedExample, true);
                        if (json_last_error() === JSON_ERROR_NONE) {
                            $contentArray['response']['responseParamsDesc'] = $this->exampleToParamsDesc($parsedExample);
                        } elseif (!empty($refArray)) {
                            $jsonArray = $this->definitionToJsonArray($refArray);
                            $contentArray['response']['responseParamsDesc'] = $jsonArray;
                        }
                    } else {
                        $contentArray['response']['responseExample'] = $example;
                        if (!empty($refArray)) {
                            $jsonArray = $this->definitionToJsonArray($refArray);
                            $contentArray['response']['responseParamsDesc'] = $jsonArray;
                        }
                    }
                } else {
                    $contentArray['response']['responseExample'] = json_encode($example);
                    if (is_array($example) || is_object($example)) {
                        $contentArray['response']['responseParamsDesc'] = $this->exampleToParamsDesc($example);
                    } elseif (!empty($refArray)) {
                        $jsonArray = $this->definitionToJsonArray($refArray);
                        $contentArray['response']['responseParamsDesc'] = $jsonArray;
                    }
                }
            } elseif (!empty($refArray)) {
                $jsonArray = $this->definitionToJsonArray($refArray);
                $jsonStr = $this->jsonArrayToStr($refArray);
                $contentArray['response']['responseExample'] = $jsonStr;
                $contentArray['response']['responseParamsDesc'] = $jsonArray;
            } else {
                $contentArray['response']['responseExample'] = '{}';
                $contentArray['response']['responseParamsDesc'] = [];
            }
        }
    }

    /**
     * 将 example 转换为参数描述
     */
    private function exampleToParamsDesc($example, string $parentKey = ''): array
    {
        $res = [];

        if (is_array($example)) {
            foreach ($example as $key => $value) {
                $fullKey = $parentKey ? $parentKey . '.' . $key : $key;

                if (is_array($value)) {
                    if (!empty($value) && (is_array(reset($value)) || is_object(reset($value)))) {
                        $childParams = $this->exampleToParamsDesc(reset($value), $fullKey);
                        $res = array_merge($res, $childParams);
                    } else {
                        $res[] = [
                            'name'    => $fullKey,
                            'type'    => 'array',
                            'value'   => '',
                            'require' => '0',
                            'remark'  => '',
                        ];
                        $childParams = $this->exampleToParamsDesc($value, $fullKey);
                        $res = array_merge($res, $childParams);
                    }
                } else {
                    $res[] = [
                        'name'    => $fullKey,
                        'type'    => $this->getTypeFromValue($value),
                        'value'   => '',
                        'require' => '0',
                        'remark'  => '',
                    ];
                }
            }
        }

        return $res;
    }

    /**
     * 根据值获取类型
     */
    private function getTypeFromValue($value): string
    {
        if (is_null($value)) {
            return 'null';
        }
        if (is_bool($value)) {
            return 'boolean';
        }
        if (is_int($value)) {
            return 'integer';
        }
        if (is_float($value)) {
            return 'number';
        }
        if (is_string($value)) {
            return 'string';
        }
        if (is_array($value)) {
            return 'array';
        }
        if (is_object($value)) {
            return 'object';
        }

        return 'string';
    }
}
