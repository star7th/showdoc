<?php

namespace Api\Controller;

use Think\Controller;

class ImportSwaggerController extends BaseController
{

    public $json_array = array();
    public $url_pre =  '';

    public function import()
    {
        $login_user = $this->checkLogin();
        $item_id = I("item_id") ? I("item_id") : '0';
        if ($item_id) {
            if (!$this->checkItemEdit($login_user['uid'], $item_id)) {
                $this->sendError(10302);
                return;
            }
        }
        $json = file_get_contents($_FILES["file"]["tmp_name"]);

        //$json = file_get_contents("../Public/swagger.json") ;//test
        $json_array = json_decode($json, 1);
        unset($json);
        if ($json_array['info']) {
            $json_array['item_id'] = $item_id;
            $this->json_array = $json_array;

            // 根据Swagger/OpenAPI版本分别处理
            $swagger_version = '';
            if (isset($json_array['swagger'])) {
                $swagger_version = $json_array['swagger'];
            } else if (isset($json_array['openapi'])) {
                $swagger_version = $json_array['openapi'];
            }

            // 设置URL前缀
            if (strstr($swagger_version, '2.')) {
                // Swagger 2.0 格式
                $scheme = $json_array['schemes'][0] ? $json_array['schemes'][0] : 'http';
                if ($json_array['host']) {
                    $this->url_pre = $scheme . "://" . $json_array['host'] . $json_array['basePath'];
                }
            } else {
                // OpenAPI 3.0 格式
                if ($json_array['servers'][0]['url']) {
                    $this->url_pre = $json_array['servers'][0]['url'];
                }
            }

            // 转换10次。我觉得可能解析的思路不对，以至于要用这种别扭的方法。以后再完善吧
            for ($i = 0; $i < 10; $i++) {
                $this->json_array = $this->_transferDefinition($json_array);
            }

            $this->_fromSwagger($this->json_array, $item_id, $swagger_version);
            return;
        }

        $this->sendError(10101);
    }

    private function _fromSwagger($json_array, $item_id, $swagger_version)
    {

        $login_user = $this->checkLogin();

        // TODO 这里需要检查下合法性。比如关键字检查/黑名单检查/字符串过滤

        $from = I("from") ? I("from") : '';
        $item_array = array(
            "item_id" => $json_array['item_id'],
            "item_name" => $json_array['info']['title'] ? $json_array['info']['title']  : 'from swagger',
            "item_type" => ($from == 'runapi') ? '3' : '1',
            "item_description" => $json_array['info']['description'] ? $json_array['info']['description'] : '',
            "password" => time() . rand(),
            "members" => array(),
            "pages" => array(
                "pages" => array(),
                "catalogs" => $this->_getAllTagsLogs($json_array, $swagger_version)
            )
        );
        $level = 2;
        //$item_array['pages']['catalogs'][0]['pages'] = $this->_getPageByPaths($json_array);
        $item_id = D("Item")->import(json_encode($item_array), $login_user['uid'], $item_id);

        //echo D("Item")->export(196053901215026 );
        //echo json_encode($item_array);
        $this->sendResult(array('item_id' => $item_id));
    }

    private function _getAllTagsLogs($json_array, $swagger_version)
    {
        $catalogsMap = array(
            "fromSwagger" => array("cat_name" => 'from swagger', "pages" => array())
        );
        $paths = $json_array['paths'];
        foreach ($paths as $url => $value) {
            foreach ($value as $method => $value2) {
                $tags = isset($value2["tags"]) ? $value2["tags"] : array();
                if ($tags == array()) {
                    $page = $this->_requestToDoc($method, $url, $value2, $json_array, $swagger_version);
                    if ($page['page_title']) {
                        $catalogsMap["fromSwagger"]["pages"][] = $page;
                    }
                } else {
                    foreach ($tags as $tag) {
                        if (!key_exists($tag, $catalogsMap)) {
                            $page = $this->_requestToDoc($method, $url, $value2, $json_array, $swagger_version);
                            if ($page["page_title"] != "" && $page["page_content"] != "") {
                                $catalogsMap[$tag] = array("cat_name" => $tag, "pages" => array($page));
                            }
                        } else {
                            // 存在则page merge
                            $page = $this->_requestToDoc($method, $url, $value2, $json_array, $swagger_version);
                            if ($page["page_title"] != "" && $page["page_content"] != "") {
                                $catalogsMap[$tag]["pages"][] = $page;
                            }
                        }
                    }
                }
            }
        }
        $catalogs = array();
        foreach ($catalogsMap as $key => $value) {
            $catalogs[] = $value;
        }
        return $catalogs;
    }

    private function _getPageByPaths($json_array, $swagger_version)
    {
        $return = array();
        $paths = $json_array['paths'];
        foreach ($paths as $url => $value) {
            foreach ($value as $method => $value2) {
                $return[] = $this->_requestToDoc($method, $url, $value2, $json_array, $swagger_version);
            }
        }
        return $return;
    }

    private function _requestToDoc($method, $url, $request, $json_array, $swagger_version)
    {
        $from = I("from") ? I("from") : '';
        $res = $this->_requestToApi($method, $url, $request, $json_array, $swagger_version);
        if ($from == 'runapi') {
            return $res;
        } else {
            $convert = new \Api\Helper\Convert();
            $res['page_content'] = $convert->runapiToMd($res['page_content']);
            return $res;
        }
    }

    private function _requestToApi($method, $url, $request, $json_array, $swagger_version)
    {
        $return = array();
        $page_title = $request['summary'] ? $request['summary'] : $request['description'];
        if (!$page_title && $request['operationId']) {
            $page_title = $request['operationId'];
        }
        $page_title = mb_substr($page_title, 0, 50, 'utf-8');
        $return['page_title'] = $page_title;
        $return['s_number'] = 99;
        $return['page_comments'] = '';

        $content_array = array(
            "info" => array(
                "from" =>  'runapi',
                "type" =>  'api',
                "title" => $request['summary'] ? $request['summary'] : $request['description'],
                "description" =>  $request['description'],
                "method" =>  strtolower($method),
                "url" =>  $this->url_pre . $url,
                "remark" =>  '',
            ),
            "request" => array(
                "params" => array(
                    'mode' => "formdata",
                    'json' => "",
                    'jsonDesc' => array(),
                    'urlencoded' => array(),
                    'formdata' => array(),
                ),
                "query" => array(),
                "headers" => array(),
                "cookies" => array(),
                "auth" => array(),
            ),
            "response" => array(),
            "extend" => array(),
        );

        // 根据版本处理请求体
        if (strstr($swagger_version, '2.')) {
            // 处理Swagger 2.0
            $this->_processSwagger2Request($request, $content_array);
        } else {
            // 处理OpenAPI 3.0
            $this->_processOpenAPI3Request($request, $content_array);
        }

        // 根据版本处理响应
        if (strstr($swagger_version, '2.')) {
            // 处理Swagger 2.0响应
            $this->_processSwagger2Response($request, $content_array);
        } else {
            // 处理OpenAPI 3.0响应
            $this->_processOpenAPI3Response($request, $content_array);
        }

        $return['page_content'] = json_encode($content_array);
        return $return;
    }

    // 获取引用，返回数组。
    //$ref_str 是swagger里引用的字符串，比如"#/definitions/Petoo",比如"#/components/schemas/TenantArticleResult"
    private function _getDefinition($ref_str)
    {
        $json_array = $this->json_array;
        $str_array = explode('/', $ref_str);

        if ($str_array[2]) {
            $target_array = $json_array[$str_array[1]][$str_array[2]];
        }
        if ($str_array[3]) {
            $target_array = $json_array[$str_array[1]][$str_array[2]][$str_array[3]];
        }

        if ($target_array) {
            return $target_array;
        }
        return false;
    }

    //把引用类型的数组转换成适合showdoc-runapi格式的描述
    private function _definitionToJsonArray($ref_array)
    {
        $res = array();
        // 检查属性是否存在
        if (!isset($ref_array['properties'])) {
            return $res;
        }

        foreach ($ref_array['properties'] as $key => $value) {
            $remark = isset($value["title"]) ? $value["title"] : '';
            $remark = isset($value["description"]) ? $value["description"] : $remark;

            // 提取示例值
            $example_value = '';
            if (isset($value['example'])) {
                $example_value = $value['example'];
            }

            // 确定参数是否必填
            $required = '0';
            if (isset($ref_array['required']) && is_array($ref_array['required']) && in_array($key, $ref_array['required'])) {
                $required = '1';
            }

            // 修复int类型为integer
            $param_type = isset($value["type"]) ? $value["type"] : 'string';
            if ($param_type === 'int') {
                $param_type = 'integer';
            }

            $res[] = array(
                "name" => $key,
                "type" => $param_type,
                "value" => $example_value,
                "require" => $required,
                "remark" => $remark,
            );

            if (isset($value['properties'])) {
                $tmp_json_array = $this->_definitionToJsonArray($value);
                $res = array_merge($res, $tmp_json_array);
            }
            if (isset($value['items'])) {
                $tmp_json_array = $this->_definitionToJsonArray($value['items']);
                $res = array_merge($res, $tmp_json_array);
            }
        }

        return $res;
    }

    // 把json数组转成纯json字符串
    private function _jsonArrayToStr($json_array)
    {


        $res_array = $this->_toB($json_array);


        return json_encode($res_array);
    }

    // 被_jsonArrayToStr调用
    private function _toB($json_array)
    {
        $res_array = array();
        if (isset($json_array['properties'])) {
            foreach ($json_array['properties'] as $key => $value) {
                $res_array[$key] = $this->_formatToFakeValue($value['type'] ?? 'string', $value['format'] ?? '');
                // value 为数组的场景
                if (isset($value['items'])) {
                    $res_array[$key] = array($this->_toB($value['items']));
                }
                // value为object的场景
                if (isset($value['properties'])) {
                    $res_array[$key] = $this->_toB($value);
                }
            }
        } else if (isset($json_array['$ref'])) {
            $refData = $this->_getDefinition($json_array['$ref']);
            if ($refData) {
                $res_array = $this->_toB($refData);
            }
        } else if (isset($json_array['type']) && $json_array['type'] == 'array' && isset($json_array['items'])) {
            $res_array = array($this->_toB($json_array['items']));
        }
        return $res_array;
    }

    // 根据字段format的要求类型，模拟一个数值。被_toB调用
    private function _formatToFakeValue($type, $format)
    {

        switch ($format) {
            case 'int64':
                return 0;
            case 'int32':
                return 0;
            case 'double':
                return 0.00;
            case 'date-time':
                return date("Y-m-d H:i:s");
        }

        switch ($type) {
            case 'boolean':
                return true;
        }

        // 如果上面没有retuan，则默认返回$type ;
        return $type;
    }


    // 将引用改为真实数据
    private function _transferDefinition($cur_array)
    {
        $json_array = $this->json_array;
        foreach ($cur_array as $key => $value) {
            if (is_array($value)) {
                $cur_array[$key] = $this->_transferDefinition($value);
                if ($value['$ref']) {
                    $cur_array[$key] = $this->_getDefinition($value['$ref']);
                }
            }
        }
        return $cur_array;
    }

    // 处理OpenAPI 3.0的请求
    private function _processOpenAPI3Request($request, &$content_array)
    {
        // 添加请求头处理
        if (isset($request['headers'])) {
            foreach ($request['headers'] as $header) {
                $content_array['request']['headers'][] = array(
                    "name" => $header["name"],
                    "type" => 'string',
                    "value" => $header["value"] ?? '',
                    "require" => ($header["required"] ?? false) ? '1' : '0',
                    "remark" => $header["description"] ?? '',
                );
            }
        }

        // 处理请求体
        if (isset($request['requestBody']['content'])) {
            $hasJsonContent = false;
            $hasFormContent = false;

            // 首先检查是否有application/json
            if (isset($request['requestBody']['content']['application/json'])) {
                $hasJsonContent = true;
                $this->_processContentSchema($request['requestBody']['content']['application/json'], $content_array, 'json');
            }

            // 检查是否有x-www-form-urlencoded
            if (!$hasJsonContent && isset($request['requestBody']['content']['application/x-www-form-urlencoded'])) {
                $hasFormContent = true;
                $this->_processContentSchema($request['requestBody']['content']['application/x-www-form-urlencoded'], $content_array, 'formdata');
            }

            // 检查是否有multipart/form-data
            if (!$hasJsonContent && !$hasFormContent && isset($request['requestBody']['content']['multipart/form-data'])) {
                $this->_processContentSchema($request['requestBody']['content']['multipart/form-data'], $content_array, 'formdata');
            }

            // 如果还没有处理过任何内容类型，尝试处理第一个找到的内容类型
            if (!$hasJsonContent && !$hasFormContent) {
                foreach ($request['requestBody']['content'] as $contentType => $content) {
                    $mode = (strpos($contentType, 'json') !== false) ? 'json' : 'formdata';
                    $this->_processContentSchema($content, $content_array, $mode);
                    break;
                }
            }
        }

        // 处理参数
        $this->_processParameters($request, $content_array);
    }

    // 处理内容schema
    private function _processContentSchema($content, &$content_array, $mode)
    {
        // 设置模式
        $content_array['request']['params']['mode'] = $mode;

        if (isset($content['schema'])) {
            $schema = $content['schema'];

            // 如果是json模式，设置json字段
            if ($mode === 'json' && (isset($schema['$ref']) || (isset($schema['type']) && $schema['type'] === 'object'))) {
                $json_obj = $this->_toB($schema);
                $content_array['request']['params']['json'] = json_encode($json_obj);

                // 为JSON类型生成jsonDesc描述
                if (!empty($json_obj)) {
                    $json_desc = $this->_definitionToJsonArray($schema);
                    $content_array['request']['params']['jsonDesc'] = $json_desc;
                }
            }

            // 处理属性
            if (isset($schema['properties'])) {
                foreach ($schema['properties'] as $key => $value) {
                    // 提取示例值
                    $example_value = '';
                    if (isset($value['example'])) {
                        $example_value = $value['example'];
                    }

                    // 确定参数是否必填
                    $required = '0';
                    if (isset($schema['required']) && is_array($schema['required']) && in_array($key, $schema['required'])) {
                        $required = '1';
                    }

                    // 如果是复杂对象类型，处理子属性
                    if (isset($value['type']) && $value['type'] === 'object' && isset($value['properties'])) {
                        $subProperties = $this->_definitionToJsonArray($value);
                        foreach ($subProperties as $sub) {
                            $content_array['request']['params']['formdata'][] = array(
                                "name" => $key . '[' . $sub['name'] . ']',
                                "type" => $sub['type'],
                                "value" => $sub['value'] ?? $example_value,
                                "require" => ($sub['require'] ?? false) ? '1' : '0',
                                "remark" => $sub['remark'] ?? (isset($value["description"]) ? $value["description"] : ''),
                            );
                        }
                    } else {
                        // 添加普通参数
                        $param_type = isset($value['type']) ? $value['type'] : 'string';
                        // 修复int类型为integer
                        if ($param_type === 'int') {
                            $param_type = 'integer';
                        }

                        $content_array['request']['params']['formdata'][] = array(
                            "name" => $key,
                            "type" => $param_type,
                            "value" => $example_value,
                            "require" => $required,
                            "remark" => $value["description"] ?? '',
                        );
                    }
                }
            } else if (isset($schema['$ref'])) {
                // 处理引用类型的schema
                $refData = $this->_getDefinition($schema['$ref']);
                if ($refData && isset($refData['properties'])) {
                    foreach ($refData['properties'] as $key => $value) {
                        // 提取示例值
                        $example_value = '';
                        if (isset($value['example'])) {
                            $example_value = $value['example'];
                        }

                        // 确定参数是否必填
                        $required = '0';
                        if (isset($refData['required']) && is_array($refData['required']) && in_array($key, $refData['required'])) {
                            $required = '1';
                        }

                        // 修复int类型为integer
                        $param_type = isset($value['type']) ? $value['type'] : 'string';
                        if ($param_type === 'int') {
                            $param_type = 'integer';
                        }

                        $content_array['request']['params']['formdata'][] = array(
                            "name" => $key,
                            "type" => $param_type,
                            "value" => $example_value,
                            "require" => $required,
                            "remark" => $value["description"] ?? '',
                        );
                    }
                }
            }
        }
    }

    // 处理Swagger 2.0的请求
    private function _processSwagger2Request($request, &$content_array)
    {
        // 处理Swagger 2.0的请求体
        if (isset($request['parameters'])) {
            $hasBodyParam = false;
            $bodySchema = null;

            // 首先收集body参数
            foreach ($request['parameters'] as $param) {
                if ($param['in'] == 'body' && isset($param['schema'])) {
                    $hasBodyParam = true;
                    $bodySchema = $param['schema'];
                    break;
                }
            }

            // 处理body参数
            if ($hasBodyParam && $bodySchema) {
                // 如果Content-Type是application/json
                if (isset($request['consumes']) && (in_array('application/json', $request['consumes']) || in_array('text/json', $request['consumes']))) {
                    $content_array['request']['params']['mode'] = 'json';
                    $json_obj = $this->_toB($bodySchema);
                    $content_array['request']['params']['json'] = json_encode($json_obj);

                    // 为JSON类型生成jsonDesc描述
                    if (!empty($json_obj)) {
                        $json_desc = $this->_definitionToJsonArray($bodySchema);
                        $content_array['request']['params']['jsonDesc'] = $json_desc;
                    }
                }

                // 如果schema是引用类型
                if (isset($bodySchema['$ref'])) {
                    $refData = $this->_getDefinition($bodySchema['$ref']);
                    $this->_processSchemaProperties($refData, $content_array);
                }
                // 如果schema直接定义了属性
                else if (isset($bodySchema['properties'])) {
                    $this->_processSchemaProperties($bodySchema, $content_array);
                }
            }

            // 处理formData参数
            $hasFormDataParams = false;
            foreach ($request['parameters'] as $param) {
                if ($param['in'] == 'formData') {
                    $hasFormDataParams = true;

                    // 提取示例值
                    $example_value = '';
                    if (isset($param['example'])) {
                        $example_value = $param['example'];
                    }

                    // 修复int类型为integer
                    $param_type = isset($param['type']) ? $param['type'] : 'string';
                    if ($param_type === 'int') {
                        $param_type = 'integer';
                    }

                    $content_array['request']['params']['formdata'][] = array(
                        "name" => $param["name"],
                        "type" => $param_type,
                        "value" => $example_value,
                        "require" => ($param["required"] ?? false) ? '1' : '0',
                        "remark" => $param["description"] ?? '',
                    );
                }
            }

            // 如果没有body参数，但有formData参数，设置mode为formdata
            if (!$hasBodyParam && $hasFormDataParams) {
                $content_array['request']['params']['mode'] = 'formdata';
            }
        }

        // 处理参数
        $this->_processParameters($request, $content_array);
    }

    // 处理schema的属性
    private function _processSchemaProperties($schema, &$content_array)
    {
        if (isset($schema['properties'])) {
            foreach ($schema['properties'] as $key => $value) {
                // 提取示例值
                $example_value = '';
                if (isset($value['example'])) {
                    $example_value = $value['example'];
                }

                // 确定参数是否必填
                $required = '0';
                if (isset($schema['required']) && is_array($schema['required']) && in_array($key, $schema['required'])) {
                    $required = '1';
                }

                // 修复int类型为integer
                $param_type = isset($value['type']) ? $value['type'] : 'string';
                if ($param_type === 'int') {
                    $param_type = 'integer';
                }

                $content_array['request']['params']['formdata'][] = array(
                    "name" => $key,
                    "type" => $param_type,
                    "value" => $example_value,
                    "require" => $required,
                    "remark" => $value["description"] ?? '',
                );
            }
        }
    }

    // 处理共用的参数（query和header）
    private function _processParameters($request, &$content_array)
    {
        // 处理查询参数
        if (isset($request['parameters'])) {
            foreach ($request['parameters'] as $param) {
                if ($param['in'] == 'query') {
                    // 提取示例值
                    $example_value = '';
                    if (isset($param['example'])) {
                        $example_value = $param['example'];
                    }

                    // 修复int类型为integer
                    $param_type = isset($param['type']) ? $param['type'] : 'string';
                    if ($param_type === 'int') {
                        $param_type = 'integer';
                    }

                    $content_array['request']['query'][] = array(
                        "name" => $param["name"],
                        "type" => $param_type,
                        "value" => $example_value,
                        "require" => ($param["required"] ?? false) ? '1' : '0',
                        "remark" => $param["description"] ?? '',
                    );
                }

                // 处理header
                if ($param['in'] == 'header') {
                    $content_array['request']['headers'][] = array(
                        "name" => $param["name"],
                        "type" => "string",
                        "value" => $param["example"] ?? '',
                        "require" => ($param["required"] ?? false) ? '1' : '0',
                        "remark" => $param["description"] ?? '',
                    );
                }

                // 处理path参数，添加到URL中
                if ($param['in'] == 'path' && isset($content_array['info']['url'])) {
                    // URL中的路径参数保持原样，不需要替换
                }
            }
        }
    }

    // 处理Swagger 2.0的响应
    private function _processSwagger2Response($request, &$content_array)
    {
        if (isset($request['responses']) && isset($request['responses']['200'])) {
            $ref_array = array();
            $example = null;

            // 检查OpenAPI 3.0 格式中的example
            if (isset($request['responses']['200']['content'])) {
                foreach ($request['responses']['200']['content'] as $contentType => $content) {
                    // 检查example字段
                    if (isset($content['example'])) {
                        $example = $content['example'];
                        break;
                    }

                    // 检查schema中的example
                    if (isset($content['schema']) && isset($content['schema']['example'])) {
                        $example = $content['schema']['example'];
                        break;
                    }
                }
            }

            // 检查是否有examples（Swagger 2.0格式）
            if (!$example && isset($request['responses']['200']['examples'])) {
                foreach ($request['responses']['200']['examples'] as $contentType => $content) {
                    $example = $content;
                    break;
                }
            }

            // 检查schema
            if (isset($request['responses']['200']['schema'])) {
                $ref_array = $request['responses']['200']['schema'];

                // 检查schema中是否有example
                if (!$example && isset($ref_array['example'])) {
                    $example = $ref_array['example'];
                }
            }

            // 处理example - 主要针对example是字符串类型的情况
            if ($example !== null) {
                // 如果example是字符串且看起来是JSON格式
                if (is_string($example)) {
                    // 先移除转义字符
                    $unescaped_example = stripslashes($example);

                    // 检查是否是JSON字符串
                    if ((substr($unescaped_example, 0, 1) === '{' || substr($unescaped_example, 0, 1) === '[') &&
                        json_decode($unescaped_example) !== null
                    ) {
                        // 是有效的JSON字符串，解码后使用
                        $content_array['response']['responseExample'] = $unescaped_example;

                        // 尝试解析JSON并从中生成参数描述
                        $parsed_example = json_decode($unescaped_example, true);
                        if (json_last_error() === JSON_ERROR_NONE) {
                            $content_array['response']['responseParamsDesc'] = $this->_exampleToParamsDesc($parsed_example);
                        }
                    } else {
                        // 不是JSON格式的字符串，直接使用
                        $content_array['response']['responseExample'] = $example;
                    }
                } else {
                    // example是数组或对象
                    $content_array['response']['responseExample'] = json_encode($example);
                    $content_array['response']['responseParamsDesc'] = $this->_exampleToParamsDesc($example);
                }
            }
            // 否则使用schema
            else if (!empty($ref_array)) {
                $json_array = $this->_definitionToJsonArray($ref_array);
                $json_str = $this->_jsonArrayToStr($ref_array);
                $content_array['response']['responseExample'] = $json_str;
                $content_array['response']['responseParamsDesc'] = $json_array;
            } else {
                // 设置一个空对象作为返回示例
                $content_array['response']['responseExample'] = '{}';
                $content_array['response']['responseParamsDesc'] = array();
            }
        }
    }

    // 处理OpenAPI 3.0的响应
    private function _processOpenAPI3Response($request, &$content_array)
    {
        if (isset($request['responses']) && isset($request['responses']['200'])) {
            $ref_array = array();
            $example = null;

            if (isset($request['responses']['200']['content'])) {
                foreach ($request['responses']['200']['content'] as $contentType => $content) {
                    // 1. 优先检查examples对象（OpenAPI 3.0规范）
                    if (isset($content['examples']) && !empty($content['examples'])) {
                        // 提取第一个example的value
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

                    // 2. 检查单独的example字段
                    if (isset($content['example'])) {
                        $example = $content['example'];
                        break;
                    }

                    // 3. 检查schema中的example
                    if (isset($content['schema']) && isset($content['schema']['example'])) {
                        $example = $content['schema']['example'];
                        break;
                    }

                    if (isset($content['schema'])) {
                        $ref_array = $content['schema'];
                    }
                }
            }

            // 处理example - 主要针对example是字符串类型的情况
            if ($example !== null) {
                // 如果example是字符串且看起来是JSON格式
                if (is_string($example)) {
                    // 先移除转义字符
                    $unescaped_example = stripslashes($example);

                    // 检查是否是JSON字符串
                    if ((substr($unescaped_example, 0, 1) === '{' || substr($unescaped_example, 0, 1) === '[') &&
                        json_decode($unescaped_example) !== null
                    ) {
                        // 是有效的JSON字符串，解码后使用
                        $content_array['response']['responseExample'] = $unescaped_example;

                        // 尝试解析JSON并从中生成参数描述
                        $parsed_example = json_decode($unescaped_example, true);
                        if (json_last_error() === JSON_ERROR_NONE) {
                            $content_array['response']['responseParamsDesc'] = $this->_exampleToParamsDesc($parsed_example);
                        } else if (!empty($ref_array)) {
                            $json_array = $this->_definitionToJsonArray($ref_array);
                            $content_array['response']['responseParamsDesc'] = $json_array;
                        }
                    } else {
                        // 不是JSON格式的字符串，直接使用
                        $content_array['response']['responseExample'] = $example;
                        if (!empty($ref_array)) {
                            $json_array = $this->_definitionToJsonArray($ref_array);
                            $content_array['response']['responseParamsDesc'] = $json_array;
                        }
                    }
                } else {
                    // example是数组或对象
                    $content_array['response']['responseExample'] = json_encode($example);
                    if (is_array($example) || is_object($example)) {
                        $content_array['response']['responseParamsDesc'] = $this->_exampleToParamsDesc($example);
                    } else if (!empty($ref_array)) {
                        $json_array = $this->_definitionToJsonArray($ref_array);
                        $content_array['response']['responseParamsDesc'] = $json_array;
                    }
                }
            }
            // 否则使用schema
            else if (!empty($ref_array)) {
                $json_array = $this->_definitionToJsonArray($ref_array);
                $json_str = $this->_jsonArrayToStr($ref_array);
                $content_array['response']['responseExample'] = $json_str;
                $content_array['response']['responseParamsDesc'] = $json_array;
            } else {
                // 设置一个空对象作为返回示例
                $content_array['response']['responseExample'] = '{}';
                $content_array['response']['responseParamsDesc'] = array();
            }
        }
    }

    // 将example转换为参数描述
    private function _exampleToParamsDesc($example, $parentKey = '')
    {
        $res = array();

        if (is_array($example)) {
            foreach ($example as $key => $value) {
                $fullKey = $parentKey ? $parentKey . '.' . $key : $key;

                if (is_array($value)) {
                    // 如果是数组且第一个元素是数组或对象，则递归处理
                    if (!empty($value) && (is_array(reset($value)) || is_object(reset($value)))) {
                        $childParams = $this->_exampleToParamsDesc(reset($value), $fullKey);
                        $res = array_merge($res, $childParams);
                    } else {
                        $res[] = array(
                            "name" => $fullKey,
                            "type" => "array",
                            "value" => '',
                            "require" => '0',
                            "remark" => '',
                        );

                        // 处理数组中的子项
                        $childParams = $this->_exampleToParamsDesc($value, $fullKey);
                        $res = array_merge($res, $childParams);
                    }
                } else {
                    $res[] = array(
                        "name" => $fullKey,
                        "type" => $this->_getTypeFromValue($value),
                        "value" => '',
                        "require" => '0',
                        "remark" => '',
                    );
                }
            }
        }

        return $res;
    }

    // 根据值获取类型
    private function _getTypeFromValue($value)
    {
        if (is_null($value)) return 'null';
        if (is_bool($value)) return 'boolean';
        if (is_int($value)) return 'integer';
        if (is_float($value)) return 'number';
        if (is_string($value)) return 'string';
        if (is_array($value)) return 'array';
        if (is_object($value)) return 'object';

        return 'string';
    }
}
