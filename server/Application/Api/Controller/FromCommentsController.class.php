<?php

namespace Api\Controller;

use Think\Controller;
/*
    通过注释生成api文档
 */

class FromCommentsController extends BaseController
{

    public function generate()
    {
        //return ;
        header('Content-Type:text/html;charset=utf-8 ');
        $content = I("content");
        $api_key = I("api_key");
        $api_token = I("api_token");

        $item_id = D("ItemToken")->check($api_key, $api_token);
        if (!$item_id) {
            //没验证通过
            echo "\napi_key或者api_token不匹配\n\n";
            return false;
        }
        $content = str_replace("_this_and_change_", "&", $content);
        $p = "|/\*\*([\s\S]*)\*/|U";
        preg_match_all($p, $content, $matches);
        if ($matches && $matches[0]) {
            foreach ($matches[0] as $key => $value) {
                if (strstr($value, "@title") && strstr($value, "showdoc")) {
                    $ret = $this->generate_one($item_id, $value);
                }
            }
        }
        if ($ret) {
            echo "\n 成功 \n\n ";
        } else {
            echo "失败";
        }
    }

    private function generate_one($item_id, $content)
    {
        $convert = new \Api\Helper\Convert();
        $item = D("Item")->where("item_id = '%d'", array($item_id))->find();
        $array = $this->parse_content($content);
        $page_content = $this->_toRunapiFormat($array);
        if ($item['item_type'] != '3') {
            $page_content = $convert->runapiToMd($page_content);
        }
        $page_title = $array['title'];
        $page_content = $page_content;
        $cat_name = $array['cat_name'];
        $s_number = $array['s_number'] ? $array['s_number'] : 99;
        $page_id = D("Page")->update_by_title($item_id, $page_title, $page_content, $cat_name, $s_number);
        if ($page_id) {
            $ret = D("Page")->where(" page_id = '$page_id' ")->find();
            return $ret;
        } else {
            return false;
        }
    }

    //解析content，返回数组
    private function parse_content($content)
    {
        $array = array();

        //解析标题
        $array['title'] = $this->parse_one_line("title", $content);

        $array['method'] = $this->parse_one_line("method", $content);

        $array['description'] = $this->parse_one_line("description", $content);

        $array['url'] = $this->parse_one_line("url", $content);

        //解析目录
        $array['cat_name'] = $this->parse_one_line("catalog", $content);

        //解析返回内容
        $return = $this->parse_one_line("return", $content);
        $return = htmlspecialchars_decode($return);

        $array['return'] = $return;

        //解析请求参数
        $array['param'] = $this->parse_muti_line('param', $content);

        //解析请求header
        $array['header'] = $this->parse_muti_line('header', $content);


        //解析返回参数
        $array['return_param'] = $this->parse_muti_line('return_param', $content);

        $array['remark'] = $this->parse_one_line("remark", $content);

        $array['s_number'] = $this->parse_one_line("number", $content);

        //如果请求参数是json，则生成请求示例
        $json_param = $this->parse_one_line("json_param", $content);
        $json_param = htmlspecialchars_decode($json_param);

        $array['json_param'] = $json_param;

        return $array;
    }

    //解析单行标签，如method、url
    private function parse_one_line($tag, $content)
    {
        $p = '/@' . $tag . '.+/';
        preg_match($p, $content, $matches);
        //var_dump($p);
        //var_dump($matches);
        if ($matches && $matches[0]) {
            return  trim(str_replace('@' . $tag, '', $matches[0]));
        }

        return false;
    }

    //解析多行标签，如param
    private function parse_muti_line($tag, $content)
    {
        $return = array();
        $array1 = explode("@", $content);
        foreach ($array1 as $key => $value) {
            $array2 = preg_split("/[\s]+/", trim($value));
            if (!empty($array2[0]) && $array2[0] == $tag) {
                unset($array2[0]);
                $return[] = array_values($array2);
            }
        }

        return $return;
    }

    //转成runapi的接口格式
    private function _toRunapiFormat($array)
    {
        $content_array = array(
            "info" => array(
                "from" =>  'runapi',
                "type" =>  'api',
                "title" =>  $array['title'],
                "description" =>  $array['description'],
                "method" =>  strtolower($array['method']),
                "url" =>  $array['url'],
                "remark" =>  $array['remark'],
            ),
            "request" => array(
                "params" => array(
                    'mode' => "formdata",
                    'json' => "",
                    'urlencoded' => array(),
                    'formdata' => array(),
                ),
                "headers" => array(),
                "query" => array(),
                "cookies" => array(),
                "auth" => array(),
            ),
            "response" => array(
                "responseExample" => $array['return'],
                "responseParamsDesc" => array(),
            ),
            "extend" => array(),
        );

        $responseExample = $this->_indent_json($content_array['response']['responseExample']);
        $content_array['response']['responseExample'] = $responseExample ? $responseExample : $content['response']['responseExample'];

        if ($array['header']) {
            foreach ($array['header'] as $key => $value) {
                // |参数名|是否必选|类型|说明
                $content_array['request']['headers'][] = array(
                    "name" => $value[0],
                    "require" => ($value[1] == '必选') ? '1' : '0',
                    "type" => $value[2],
                    "value" => '',
                    "remark" => $value[3],
                );
            }
        }

        if ($array['json_param']) {
            $content_array['request']['params']['mode'] = 'json';
            $content_array['request']['params']['json'] = $array['json_param'];
            // 请求方式是json的话，原有的参数说明数组就写入json描述中
            if ($array['param']) {
                foreach ($array['param'] as $key => $value) {
                    // |参数名|是否必选|类型|说明
                    $content_array['request']['params']['jsonDesc'][] = array(
                        "name" => $value[0],
                        "require" => ($value[1] == '必选') ? '1' : '0',
                        "type" => $value[2],
                        "value" => '',
                        "remark" => $value[3],
                    );
                }
            }
        }
        // 
        if ($array['param']) {
            if (strtolower($array['method']) == 'get') {
                $query_str = '';
                foreach ($array['param'] as $key => $value) {
                    // |参数名|是否必选|类型|说明
                    $content_array['request']['query'][] = array(
                        "name" => $value[0],
                        "require" => ($value[1] == '必选') ? '1' : '0',
                        "type" => $value[2],
                        "value" => '',
                        "remark" => $value[3],
                    );
                    $query_str .= $value[0] . "=" . '&';
                }
                // 为了兼容，还要把query参数追加到url里去
                if (strstr($content_array['info']['url'], "?")) {
                    $content_array['info']['url'] .= "&" . $query_str;
                } else {
                    $content_array['info']['url'] .= "?" . $query_str;
                }
            } else {
                foreach ($array['param'] as $key => $value) {
                    // |参数名|是否必选|类型|说明
                    $content_array['request']['params']['formdata'][] = array(
                        "name" => $value[0],
                        "require" => ($value[1] == '必选') ? '1' : '0',
                        "type" => $value[2],
                        "value" => '',
                        "remark" => $value[3],
                    );
                }
            }
        }

        if ($array['return_param']) {
            foreach ($array['return_param'] as $key => $value) {
                // |参数名|类型|说明
                $content_array['response']['responseParamsDesc'][] = array(
                    "name" => $value[0],
                    "type" => $value[1],
                    "value" => '',
                    "remark" => $value[2],
                );
            }
        }

        return json_encode($content_array);
    }

    // json美化
    private function _indent_json($json)
    {

        $json_new = json_encode(json_decode($json), JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        if ($json_new && $json_new != 'null') {
            return $json_new;
        }
        return $json;
    }
}
