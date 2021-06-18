<?php
namespace Api\Controller;
use Think\Controller;
/*
    通过注释生成api文档
 */
class FromCommentsController extends BaseController {

    public function generate(){
        //return ;
        header( 'Content-Type:text/html;charset=utf-8 ');
        $content = I("content") ;
        $api_key = I("api_key");
        $api_token = I("api_token");

        $item_id = D("ItemToken")->check($api_key , $api_token);
        if (!$item_id) {
            //没验证通过
            echo "\napi_key或者api_token不匹配\n\n";
            return false;
        }
        $content = str_replace("_this_and_change_", "&", $content);
        $p = "|/\*\*([\s\S]*)\*/|U";
        preg_match_all($p, $content , $matches) ;
        if ($matches && $matches[0]) {
            foreach ($matches[0] as $key => $value) {
                if (strstr($value,"@title") && strstr($value,"showdoc")) {
                   $ret = $this->generate_one($item_id , $value);
                }
            }
        }
        if ($ret) {
             echo "\n 成功 \n\n ";
        }else{
            echo "失败";
        }

    }

    private function generate_one($item_id,$content){
        $array = $this->parse_content($content);
        $page_content = $this->toMarkdown($array);
        $page_title = $array['title'];
        $page_content = $page_content;
        $cat_name = $array['cat_name'];
        $s_number = $array['s_number'] ? $array['s_number'] : 99;
        $page_id = D("Page")->update_by_content($item_id,$page_title,$page_content,$cat_name,$s_number);
        if ($page_id) {
            $ret = D("Page")->where(" page_id = '$page_id' ")->find();
            return $ret;
        }else{
            return false;
        }

    }  

    //解析content，返回数组
    private function parse_content($content){
        $array = array() ;

        //解析标题
        $array['title'] = $this->parse_one_line("title" , $content);

        $array['method'] = $this->parse_one_line("method" , $content);

        $array['description'] = $this->parse_one_line("description" , $content);

        $array['url'] = $this->parse_one_line("url" , $content);

        //解析目录
        $array['cat_name']= $this->parse_one_line("catalog" , $content);

        //解析返回内容
        $return = $this->parse_one_line("return" , $content);
        $return = htmlspecialchars_decode($return);
        //判断是否是json数据
        if (!is_null(json_decode($return))) {
            //格式化下
            $return = $this->indent_json($return);
        }
        $array['return'] = $return ;  

        //解析请求参数
        $array['param'] = $this->parse_muti_line('param' , $content);

        //解析请求header
        $array['header'] = $this->parse_muti_line('header' , $content);


        //解析返回参数
        $array['return_param'] = $this->parse_muti_line('return_param' , $content);

        $array['remark'] = $this->parse_one_line("remark" , $content);

        $array['s_number'] = $this->parse_one_line("number" , $content);

        //如果请求参数是json，则生成请求示例
        $json_param = $this->parse_one_line("json_param" , $content);
        $json_param = htmlspecialchars_decode($json_param);
        //判断是否是json数据
        if (!is_null(json_decode($json_param))) {
            //格式化下
            $json_param = $this->indent_json($json_param);
        }
        $array['json_param'] = $json_param ; 

        return $array ;
    }

    //解析单行标签，如method、url
    private function parse_one_line($tag , $content){
        $p = '/@'.$tag.'.+/' ;
        preg_match($p, $content , $matches) ;
        //var_dump($p);
        //var_dump($matches);
        if ($matches && $matches[0]) {
           return  trim(str_replace('@'.$tag, '', $matches[0]) );
        }

        return false;

    }

    //解析多行标签，如param
    private function parse_muti_line($tag , $content){
        $return =array() ;
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


    /**
     * Indents a flat JSON string to make it more human-readable.
     *
     * @param string $json The original JSON string to process.
     *
     * @return string Indented version of the original JSON string.
     */
    private function indent_json($json) {

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

    //生成markdown文档内容
    private function toMarkdown($array){
        $content = '  
**简要描述：** 

- '.$array['description'].'

**请求URL：** 

- ` '.$array['url'].' `
  
**请求方式：**

- '.$array['method'].' ';

if ($array['header']) {
$content .='

**Header：** 

|Header名|是否必选|类型|说明|
|:----    |:---|:----- |-----   |'."\n";
    foreach ($array['header'] as $key => $value) {
         $content .= '|'.$value[0].' |'.$value[1].'  |'.$value[2].' |'.$value[3].' |'."\n";
    }
}

if ($array['json_param']) {
$content .= '


 **请求参数示例**

``` 
'.$array['json_param'].'
```

';

}

if ($array['param']) {
$content .='

**参数：** 

|参数名|是否必选|类型|说明|
|:----    |:---|:----- |-----   |'."\n";
    foreach ($array['param'] as $key => $value) {
         $content .= '|'.$value[0].' |'.$value[1].'  |'.$value[2].' |'.$value[3].' |'."\n";
    }
}



$content .= '

 **返回示例**

``` 
'.$array['return'].'
```

 **返回参数说明** 

|参数名|类型|说明|
|:-----  |:-----|----- |'."\n";

if ($array['return_param']) {
    foreach ($array['return_param'] as $key => $value) {
         $content .= '|'.$value[0].' |'.$value[1].'  |'.$value[2]."\n";
    }
}

$content .= '

 **备注** 

- '.$array['remark'].'

        ';
        return $content;
    }

}
