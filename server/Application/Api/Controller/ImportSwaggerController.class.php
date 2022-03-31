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
            if (strstr($json_array['swagger'], '2.')) {
                $this->sendError(10101, "暂未支持swagger2的文件。你尝试可以导入swagger3(openapi3)的json文件");
                return;
            }
            $json_array['item_id'] = $item_id;
            $this->json_array = $json_array;
            $scheme = $json_array['schemes'][0] ? $json_array['schemes'][0] : 'http';
            if ($json_array['host']) {
                $this->url_pre = $scheme . "://" . $json_array['host'] . $json_array['basePath'];
            }
            // 转换10次。我觉得可能解析的思路不对，以至于要用这种别扭的方法。以后再完善吧
            for ($i = 0; $i < 10; $i++) {
                $this->json_array = $this->_transferDefinition($json_array);
            }

            // echo json_encode($this->json_array) ;
            // exit();

            $this->_fromSwagger($this->json_array, $item_id);
            return;
        }

        $this->sendError(10303);
    }

    private function _fromSwagger($json_array, $item_id)
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
                "catalogs" => $this->_getAllTagsLogs($json_array)
            )
        );
        $level = 2;
        //        $item_array['pages']['catalogs'][0]['pages'] = $this->_getPageByPaths($json_array);
        $item_id = D("Item")->import(json_encode($item_array), $login_user['uid'], $item_id);

        //echo D("Item")->export(196053901215026 );
        //echo json_encode($item_array);
        $this->sendResult(array('item_id' => $item_id));
    }

    private function _getAllTagsLogs($json_array)
    {
        $catalogsMap = array(
            "fromSwagger" => array("cat_name" => 'from swagger', "pages" => array())
        );
        $paths = $json_array['paths'];
        foreach ($paths as $url => $value) {
            foreach ($value as $method => $value2) {
                $tags = isset($value2["tags"]) ? $value2["tags"] : array();
                if ($tags == array()) {
                    $page = $this->_requestToDoc($method, $url, $value2, $json_array);
                    if ($page['page_title']) {
                        $catalogsMap["fromSwagger"]["pages"][] = $page;
                    }
                } else {
                    foreach ($tags as $tag) {
                        if (!key_exists($tag, $catalogsMap)) {
                            $page = $this->_requestToDoc($method, $url, $value2, $json_array);
                            if ($page["page_title"] != "" && $page["page_content"] != "") {
                                $catalogsMap[$tag] = array("cat_name" => $tag, "pages" => array($page));
                            }
                        } else {
                            // 存在则page merge
                            $page = $this->_requestToDoc($method, $url, $value2, $json_array);
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

    private function _getPageByPaths($json_array)
    {
        $return = array();
        $paths = $json_array['paths'];
        foreach ($paths as $url => $value) {
            foreach ($value as $method => $value2) {
                $return[] = $this->_requestToDoc($method, $url, $value2, $json_array);
            }
        }
        return $return;
    }

    private function _requestToDoc($method, $url, $request, $json_array)
    {
        $from = I("from") ? I("from") : '';
        $res = $this->_requestToApi($method, $url, $request, $json_array);
        if ($from == 'runapi') {
            return $res;
        } else {
            $convert = new \Api\Helper\Convert();
            $res['page_content'] = $convert->runapiToMd($res['page_content']);
            return $res;
        }
    }

    private function _requestToApi($method, $url, $request, $json_array)
    {
        $return = array();
        $page_title = $request['summary'] ? $request['summary'] : $request['description'];
        $page_title = mb_substr($page_title, 0, 50, 'utf-8');
        $return['page_title'] = $page_title;
        $return['s_number'] = 99;
        $return['page_comments'] = '';

        $content_array = array(
            "info" => array(
                "from" =>  'runapi',
                "type" =>  'api',
                "title" =>  $request['summary'] ? $request['summary'] : $request['description'],
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
                "headers" => array(),
                "cookies" => array(),
                "auth" => array(),
            ),
            "response" => array(),
            "extend" => array(),
        );

        if ($request['headerData']) {
            $tmp_array = array();
            foreach ($request['headerData'] as $key => $value) {
                $content_array['request']['headers'][] = array(
                    "name" => $value["key"],
                    "type" => 'string',
                    "value" => $value["value"],
                    "require" => (!$value["required"]) ? "0" : '1',
                    "remark" => '',
                );
            }
        }

        if ($request['parameters']) {

            foreach ($request['parameters'] as $key => $value) {
                // 如果in字段是body的话，应该就是参数为json的情况了
                if ($value["in"] == 'body') {
                    $ref_str = $value['schema']['$ref'];
                    //如果含有引用标识，则获取引用
                    if ($ref_str) {
                        $ref_array = $this->_getDefinition($ref_str);
                    } else {
                        $ref_array = $value['schema'];
                    }
                    $json_array = $this->_definitionToJsonArray($ref_array);
                    $json_str = $this->_jsonArrayToStr($ref_array);
                    $content_array['request']['params']['mode'] = 'json';
                    $content_array['request']['params']['json'] = $json_str;
                    $content_array['request']['params']['jsonDesc'] = $json_array;
                } else {
                    $content_array['request']['params']['formdata'][] = array(
                        "name" => $value["name"],
                        "type" => 'string',
                        "value" => $value["value"],
                        "require" => (!$value["required"]) ? "0" : '1',
                        "remark" => $value["description"],
                    );
                }
            }
        }

        //处理返回结果情况
        if ($request['responses'] && $request['responses']['200']) {
            $ref_array = array();
            if ($request['responses']['200']['schema']) {
                $ref_array = $request['responses']['200']['schema'];
            }

            if ($request['responses']['200']['content']) {
                if ($request['responses']['200']['content']['text/json']) {
                    $ref_array = $request['responses']['200']['content']['text/json']['schema'];
                }
                if ($request['responses']['200']['content']['text/plain']) {
                    $ref_array = $request['responses']['200']['content']['text/plain']['schema'];
                }
                if ($request['responses']['200']['content']['application/json']) {
                    $ref_array = $request['responses']['200']['content']['application/json']['schema'];
                }
            }




            $json_array = $this->_definitionToJsonArray($ref_array);
            $json_str = $this->_jsonArrayToStr($ref_array);
            $content_array['response']['responseExample'] = $json_str;
            $content_array['response']['responseParamsDesc'] = $json_array;
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
        foreach ($ref_array['properties'] as $key => $value) {
            $remark = $value["title"] ? $value["title"] : '';
            $remark = $value["description"] ? $value["description"] : $remark;

            $res[] = array(
                "name" => $key,
                "type" => $value["type"] ? $value["type"] : 'string',
                "value" => '',
                "require" => '1',
                "remark" => $remark,
            );

            if ($value['properties']) {
                $tmp_json_array = $this->_definitionToJsonArray($value);
                $res = array_merge($res, $tmp_json_array);
            }
            if ($value['items']) {
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
        if ($json_array['properties']) {
            foreach ($json_array['properties'] as $key => $value) {
                $res_array[$key] = $this->_formatToFakeValue($value['type'], $value['format']);
                // value 为数组的场景
                if ($value['items']) {
                    $res_array[$key] = array($this->_toB($value['items']));
                }
                // value为object的场景
                if ($value['properties']) {
                    $res_array[$key] = $this->_toB($value);
                }
            }
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
}
