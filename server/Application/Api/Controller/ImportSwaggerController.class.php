<?php
namespace Api\Controller;
use Think\Controller;
class ImportSwaggerController extends BaseController {


    public function import(){
        $login_user = $this->checkLogin();

        $json = file_get_contents($_FILES["file"]["tmp_name"]) ;

        //$json = file_get_contents("../Public/swagger.json") ;//test
        $json_array = json_decode($json ,1 );
        unset($json);
        if ($json_array['info']) {
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
            "item_type" => '1' ,
            "item_description" => $json_array['info']['description'] ? $json_array['info']['description'] :'',
            "password" => time().rand(),
            "members" => array(),
            "pages" =>array(
                    "pages" => array(),
                    "catalogs" => array(
                            array(
                                "cat_name" =>'from swagger',
                                "pages" =>array()
                            )
                        )
                )
            ) ;
        $level = 2 ;
        $item_array['pages']['catalogs'][0]['pages'] = $this->_getPageByPaths($json_array);
        $item_id = D("Item")->import( json_encode($item_array) , $login_user['uid'] );
        
        //echo D("Item")->export(196053901215026 );
        //echo json_encode($item_array);
        $this->sendResult(array('item_id' => $item_id));

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
        if($from == 'runapi'){
            return $this->_requestToApi($method , $url , $request , $json_array);
            //如果是来自runapi的导入请求，则已经return不再执行下面
        }
        $return = array() ;
        $return['page_title'] = $request['summary'] ;
        $return['s_number'] = 99 ;
        $return['page_comments'] = '' ;
        
        $content = '  
**简要描述：** 

- '.$request['description'].' 

**请求URL：** 
- ` '.$url.' `
  
**请求方式：**
- '.$method.' ';

if ($request['header']) {
$content .='

**Header：** 

|Header名|是否必选|类型|说明|
|:----    |:---|:----- |-----   |'."\n";
    foreach ($request['headerData'] as $key => $value) {
         $content .= '|'.$value["key"].' |  | text | '.$value["value"].' |'."\n";
    }
}

if ($request['rawModeData']) {
$content .= '


 **请求参数示例**

``` 
'.$request['rawModeData'].'
```

';

}

if ($request['parameters']) {
$content .='

**参数：** 

|参数名|是否必选|类型|说明|
|:----    |:---|:----- |-----   |'."\n";
    foreach ($request['parameters'] as $key => $value) {
         $content .= '|'.$value["name"].' | '.($value["required"] ? '是' : '否' ).'  |'.$value["type"].' | '.$value["description"].' |'."\n";
    }
}

if ($request['responses']['200']) {

$responses = $request['responses']['200'] ;
//如果返回信息是引用对象
if ($request['responses']['200']['schema'] && $request['responses']['200']['schema']['$ref'] ) {
    $str_array = explode("/", $request['responses']['200']['schema']['$ref']) ;
    if ($str_array[1] && $str_array[2]) {
        $responses = $json_array[$str_array[1]][$str_array[2]] ;
$content .='

**返回参数说明：** 

|参数名|类型|说明|
|:----    |:---|:----- |-----   |'."\n";
    foreach ($responses['properties'] as $key => $value) {
         $content .= '|'.$key.'|'.$value["type"].' | '.$value["description"].' |'."\n";
    }
    
    }
    
}else{
    //如果返回的是普通json
$content .= '


 **返回示例**

``` 

'.$this->_indent_json(json_encode($responses)).'

```

';
}



}

        $return['page_content'] = $content ;
        return $return ;

    }

    private function _requestToApi($method , $url , $request , $json_array){
        $return = array() ;
        $return['page_title'] = $request['summary'] ;
        $return['s_number'] = 99 ;
        $return['page_comments'] = '' ;
        
        $content_array = array(
                "info"=>array(
                    "from" =>  'runapi'  ,
                    "type" =>  'api'  ,
                    "title" => $request['summary']  ,
                    "description" =>  $request['description']  ,
                    "method" =>  strtolower($method)  ,
                    "url" =>  $url  ,
                    "remark" =>  '' ,
                ),
                "request" =>array(
                    "params"=> array(
                        'mode' => "formdata",
                        'json' => "",
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
                 $content_array['request']['params']['formdata'][] = array(
                        "name" =>$value["name"],
                        "type" =>'string',
                        "value" =>$value["value"],
                        "require" =>'1',
                        "remark" =>$value["description"],
                    );
            }
        }

        $return['page_content'] = json_encode($content_array);
        return $return ;

    }


    /**
     * Indents a flat JSON string to make it more human-readable.
     *
     * @param string $json The original JSON string to process.
     *
     * @return string Indented version of the original JSON string.
     */
    private function _indent_json($json) {

        $result      = '';
        $pos         = 0;
        $strLen      = strlen($json);
        $indentStr   = '  ';
        $newLine     = "\n";
        $prevChar    = '';
        $outOfQuotes = true;

        for ($i=0; $i<=$strLen; $i++) {

            // Grab the next character in the string.
            $char = substr($json, $i, 1);

            // Are we inside a quoted string?
            if ($char == '"' && $prevChar != '\\') {
                $outOfQuotes = !$outOfQuotes;

            // If this character is the end of an element,
            // output a new line and indent the next line.
            } else if(($char == '}' || $char == ']') && $outOfQuotes) {
                $result .= $newLine;
                $pos --;
                for ($j=0; $j<$pos; $j++) {
                    $result .= $indentStr;
                }
            }

            // Add the character to the result string.
            $result .= $char;

            // If the last character was the beginning of an element,
            // output a new line and indent the next line.
            if (($char == ',' || $char == '{' || $char == '[') && $outOfQuotes) {
                $result .= $newLine;
                if ($char == '{' || $char == '[') {
                    $pos ++;
                }

                for ($j = 0; $j < $pos; $j++) {
                    $result .= $indentStr;
                }
            }

            $prevChar = $char;
        }

        return $result;
    }

}