<?php
namespace Api\Controller;
use Think\Controller;
class ImportSwaggerController extends BaseController {

    public $json_array = array();
    public $url_pre =  '';

    public function import(){
        $login_user = $this->checkLogin();

        $json = file_get_contents($_FILES["file"]["tmp_name"]) ;

        //$json = file_get_contents("../Public/swagger.json") ;//test
        $json_array = json_decode($json ,1 );
        unset($json);
        if ($json_array['info']) {
            $this->json_array = $json_array ;
            $scheme = $json_array['schemes'][0] ? $json_array['schemes'][0] : 'http';
            $this->url_pre = $scheme."://".$json_array['host'].$json_array['basePath'] ;
            $this->_fromSwaggerV2($json_array);
            return ;
        }

        $this->sendError(10303);
    }

    private function _fromSwaggerV2($json_array){

        $login_user = $this->checkLogin();

        // TODO 这里需要检查下合法性。比如关键字检查/黑名单检查/字符串过滤

        $from = I("from") ? I("from") : '' ;
        $item_array = array(
            "item_name" => $json_array['info']['title'] ? $json_array['info']['title']  : 'from swagger' ,
            "item_type" =>  ($from == 'runapi') ? '3': '1'  ,
            "item_description" => $json_array['info']['description'] ? $json_array['info']['description'] :'',
            "password" => time().rand(),
            "members" => array(),
            "pages" =>array(
                    "pages" => array(),
                    "catalogs" => $this->_getAllTagsLogs($json_array)
                )
            ) ;
        $level = 2 ;
//        $item_array['pages']['catalogs'][0]['pages'] = $this->_getPageByPaths($json_array);
        $item_id = D("Item")->import( json_encode($item_array) , $login_user['uid'] );
        
        //echo D("Item")->export(196053901215026 );
        //echo json_encode($item_array);
        $this->sendResult(array('item_id' => $item_id));

    }

    private function _getAllTagsLogs($json_array) {
        $catalogsMap = array(
            "fromSwagger" => array("cat_name" =>'from swagger', "pages" =>array())
        );
        $paths = $json_array['paths']  ;
        foreach ($paths as $url => $value) {
            foreach ($value as $method => $value2) {
                $tags = isset($value2["tags"]) ? $value2["tags"] : array();
                if ($tags == array()){
                    $page = $this->_requestToDoc($method, $url, $value2, $json_array);
                    if($page['page_title']){
                        $catalogsMap["fromSwagger"]["pages"][] = $page;
                    }
                                    }else{
                    foreach ($tags as $tag){
                        if (!key_exists($tag, $catalogsMap)) {
                            $page = $this->_requestToDoc($method, $url, $value2, $json_array);
                            if ($page["page_title"] != "" && $page["page_content"] != ""){
                                $catalogsMap[$tag] = array("cat_name" => $tag, "pages" => array($page));
                            }
                        }else{
                            // 存在则page merge
                            $page = $this->_requestToDoc($method, $url, $value2, $json_array);
                            if ($page["page_title"] != "" && $page["page_content"] != ""){
                                $catalogsMap[$tag]["pages"][] = $page;
                            }
                        }
                    }
                }
            }
        }
        $catalogs = array();
        foreach ($catalogsMap as $key => $value){
            $catalogs[] = $value;
        }
        return $catalogs;
    }

    private function _getPageByPaths($json_array){
        $return = array() ;
        $paths = $json_array['paths']  ;
        foreach ($paths as $url => $value) {
            foreach ($value as $method => $value2) {
                $return[] = $this->_requestToDoc($method , $url , $value2 , $json_array);
            }
        }
        return $return ;

    }

    private function _requestToDoc($method , $url , $request , $json_array){
        $from = I("from") ? I("from") : '' ;
        $res = $this->_requestToApi($method , $url , $request , $json_array);
        if($from == 'runapi'){
            return $res ;
        }else{
            $res['page_content'] = D("Page")->runapiToMd($res['page_content']); 
            return $res ;
        }
    }

    private function _requestToApi($method , $url , $request , $json_array){
        $return = array() ;
        $return['page_title'] =  $request['summary'] ? $request['summary']: $request['operationId'] ;
        $return['s_number'] = 99 ;
        $return['page_comments'] = '' ;
        
        $content_array = array(
                "info"=>array(
                    "from" =>  'runapi'  ,
                    "type" =>  'api'  ,
                    "title" =>  $request['summary'] ? $request['summary']: $request['operationId']   ,
                    "description" =>  $request['description']  ,
                    "method" =>  strtolower($method)  ,
                    "url" =>  $this->url_pre . $url   ,
                    "remark" =>  '' ,
                ),
                "request" =>array(
                    "params"=> array(
                        'mode' => "formdata",
                        'json' => "",
                        'jsonDesc' => array(),
                        'urlencoded' => array(),
                        'formdata' => array(),
                    ),
                    "headers"=> array(),
                    "cookies"=> array(),
                    "auth"=> array(),
                ),
                "response" =>array(),
                "extend" =>array(),
            );

        if ($request['headerData']) {
            $tmp_array = array();
            foreach ($request['headerData'] as $key => $value) {
                 $content_array['request']['headers'][] = array(
                        "name" =>$value["key"],
                        "type" =>'string',
                        "value" =>$value["value"],
                        "require" =>'1',
                        "remark" =>'',
                    );
            }
        }

        if ($request['parameters']) {

            foreach ($request['parameters'] as $key => $value) {
                // 如果in字段是body的话，应该就是参数为json的情况了
                if($value["in"] == 'body'){
                    $ref_str = $value['schema']['$ref'] ;
                    //如果含有引用标识，则获取引用
                    if($ref_str){
                        $ref_array = $this->_getDefinition($ref_str);
                    }else{
                        $ref_array = $value['schema'] ;
                    }
                    $json_array = $this->_definitionToJsonArray($ref_array);
                    $json_str = $this->_jsonArrayToStr($json_array);
                    $content_array['request']['params']['mode'] = 'json';
                    $content_array['request']['params']['json'] = $json_str;
                    $content_array['request']['params']['jsonDesc'] = $json_array;
                }else{
                    $content_array['request']['params']['formdata'][] = array(
                        "name" =>$value["name"],
                        "type" =>'string',
                        "value" =>$value["value"],
                        "require" =>'1',
                        "remark" =>$value["description"],
                    );
                }

            }
        }

        //处理返回结果情况
        if($request['responses'] && $request['responses']['200']){
            $ref_str = $request['responses']['200']['schema']['$ref'] ;
            //如果含有引用标识，则获取引用
            if($ref_str){
                $ref_array = $this->_getDefinition($ref_str);
            }else{
                $ref_array = $request['responses']['200']['schema'] ;
            }
            $json_array = $this->_definitionToJsonArray($ref_array);
            $json_str = $this->_jsonArrayToStr($json_array);
            $content_array['response']['responseExample'] = $json_str;
            $content_array['response']['responseParamsDesc'] = $json_array;
        }

        $return['page_content'] = json_encode($content_array);
        return $return ;

    }

    // 获取引用，返回数组。
    //$ref_str 是swagger里引用的字符串，比如"#/definitions/Petoo"
    private function _getDefinition($ref_str){
        $json_array = $this->json_array ;
        $str_array = explode('#/definitions/',$ref_str);
        $path = $str_array1[1];
        $target_array = $json_array['definitions'][$str_array[1]] ;
        if($target_array){
            return $target_array ;
        }
        return false;
    }

    //把引用类型的数组转换成纯json数组
    private function _definitionToJsonArray($ref_array){
        $res = array() ;
        foreach ($ref_array['properties'] as $key => $value) {
            $res[] = array(
                "name" =>$key,
                "type" =>'string',
                "value" =>'',
                "require" =>'1',
                "remark" =>$value["title"],
            );
        }
        return $res ;

    }

    // 把json数组转成纯json字符串
    private function _jsonArrayToStr($json_array){
        $res_array = array() ;
        foreach ($json_array as $key => $value) {
            $res_array[$value['name']] = '' ;
        }
        return json_encode($res_array) ;  
    }

}
