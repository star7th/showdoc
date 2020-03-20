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


        $item_array = array(
            "item_name" => $json_array['info']['title'] ? $json_array['info']['title']  : 'from swagger' ,
            "item_type" => '1' ,
            "item_description" => $json_array['info']['description'] ? $json_array['info']['description'] :'',
            "password" => time().rand(),
            "members" => array(),
            "pages" =>array(
                    "pages" => array(),
                    "catalogs" => array()
                )
            ) ;
        $level = 2 ;
        $item_array['pages']['pages'] = $this->_getPageByPaths($json_array['paths'] );
        D("Item")->import( json_encode($item_array) , $login_user['uid'] );
        
        //echo D("Item")->export(196053901215026 );
        //echo json_encode($item_array);
        $this->sendResult(array());

    }

    private function _getPageByPaths($paths){
        $return = array() ;
        foreach ($paths as $url => $value) {
            foreach ($value as $method => $value2) {
                $return[] = $this->_requestToDoc($method , $url , $value2);
            }
        }
        return $return ;

    }

    private function _requestToDoc($method , $url , $request){
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
$content .= '


 **返回示例**

``` 

'.$this->_indent_json(json_encode($request['responses']['200'])).'

```

';

}

        $return['page_content'] = $content ;
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