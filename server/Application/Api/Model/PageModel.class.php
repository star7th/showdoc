<?php
namespace Api\Model;
use Api\Model\BaseModel;
/**
 * 
 * @author star7th      
 */
class PageModel extends BaseModel {

    protected $cat_name_id = array();

    //搜索某个项目下的页面
    public function search($item_id,$keyword){
        $return_pages = array() ;
        $item = D("Item")->where("item_id = '%d' and is_del = 0 ",array($item_id))->find();
        $pages = $this->where("item_id = '$item_id' and is_del = 0")->order(" s_number asc  ")->select();
        if (!empty($pages)) {
          foreach ($pages as $key => &$value) {
            $page_content = $value['page_content'];
            if (strpos( strtolower($item['item_name']."-". $value['page_title']."  ".$page_content) ,strtolower ($keyword) ) !== false) {
              $value['page_content'] = $page_content ;
              $return_pages[] = $value;
            }
          }
        }
        unset($pages);
        return $return_pages;
    }  

    //根据内容更新页面
    //其中cat_name参数特别说明下,传递各格式如 '二级目录/三级目录/四级目录'
    public function update_by_content($item_id,$page_title,$page_content,$cat_name='',$s_number = 99){
        $item_id = intval($item_id);
        if (!$item_id) {
          return false;
        }

        if ($this->cat_name_id && isset($this->cat_name_id[$cat_name])) {
          $cat_id = $this->cat_name_id[$cat_name] ;
          $cat_name = '' ; //如果已经有缓存了则设置为空
        }else{
          $cat_id = 0 ;
        }

        $catalog_array = explode('/', $cat_name);
        $cat_name = $catalog_array[0] ;
        $cat_name_sub = !empty($catalog_array[1])? $catalog_array[1] : '';
        $cat_name_sub_sub = !empty($catalog_array[2])? $catalog_array[2] : '';


        //如果传送了二级目录
        if ($cat_name) {
            $cat_name_array = D("Catalog")->where(" item_id = '$item_id' and level = 2 and cat_name = '%s' ",array($cat_name))->find();
            //如果不存在则新建
            if (!$cat_name_array) {
                $add_data = array(
                    "cat_name" => $cat_name, 
                    "item_id" => $item_id, 
                    "addtime" => time(),
                    "level" => 2 
                    );
                D("Catalog")->add($add_data);
                $cat_name_array = D("Catalog")->where(" item_id = '$item_id' and level = 2 and cat_name = '%s' ",array($cat_name))->find();
            }
        }

        //如果传送了三级目录
        if ($cat_name_sub) {
            $cat_name_sub_array = D("Catalog")->where(" item_id = '$item_id' and level = 3 and cat_name = '%s'  and parent_cat_id = '%s' ",array($cat_name_sub,$cat_name_array['cat_id']))->find();
            //如果不存在则新建
            if (!$cat_name_sub_array) {
                $add_data = array(
                    "cat_name" => $cat_name_sub, 
                    "item_id" => $item_id, 
                    "parent_cat_id" => $cat_name_array['cat_id'], 
                    "addtime" => time(),
                    "level" => 3 
                    );
                D("Catalog")->add($add_data);
                $cat_name_sub_array = D("Catalog")->where(" item_id = '$item_id' and level = 3 and cat_name = '%s' and parent_cat_id = '%s' ",array($cat_name_sub,$cat_name_array['cat_id']))->find();
            }
        }

        //如果传送了四级目录
        if ($cat_name_sub_sub) {
            $cat_name_sub_sub_array = D("Catalog")->where(" item_id = '$item_id' and level = 4 and cat_name = '%s' and parent_cat_id = '%s' ",array($cat_name_sub_sub,$cat_name_sub_array['cat_id']))->find();
            //如果不存在则新建
            if (!$cat_name_sub_sub_array) {
                $add_data = array(
                    "cat_name" => $cat_name_sub_sub, 
                    "item_id" => $item_id, 
                    "parent_cat_id" => $cat_name_sub_array['cat_id'], 
                    "addtime" => time(),
                    "level" => 4 
                    );
                D("Catalog")->add($add_data);
                $cat_name_sub_sub_array = D("Catalog")->where(" item_id = '$item_id' and level = 4 and cat_name = '%s'  and parent_cat_id = '%s' ",array($cat_name_sub_sub,$cat_name_sub_array['cat_id']))->find();
            }
        }

        if ($cat_name_array && $cat_name_array['cat_id'] > 0 ) {
            $cat_id = $cat_name_array['cat_id'] ;
        }

        if ($cat_name_sub_array && $cat_name_sub_array['cat_id'] > 0 ) {
            $cat_id = $cat_name_sub_array['cat_id'] ;
        }
        if ($cat_name_sub_sub_array && $cat_name_sub_sub_array['cat_id'] > 0 ) {
            $cat_id = $cat_name_sub_sub_array['cat_id'] ;
        }

        $this->cat_name_id[$cat_name] = $cat_id ;
        
        if ($page_content) {
            $page_array = D("Page")->where(" item_id = '$item_id' and is_del = 0  and cat_id = '$cat_id'  and page_title ='%s' ",array($page_title))->find();
            //如果不存在则新建
            if (!$page_array) {
                $add_data = array(
                    "author_username" => "update_by_content", 
                    "item_id" => $item_id, 
                    "cat_id" => $cat_id, 
                    "page_title" => $page_title, 
                    "page_content" => $page_content, 
                    "s_number" => $s_number, 
                    "addtime" => time(),
                    );
                $page_id = D("Page")->add($add_data);
            }else{
                $page_id = $page_array['page_id'] ;
                $update_data = array(
                    "author_username" => "update_by_content", 
                    "item_id" => $item_id, 
                    "cat_id" => $cat_id, 
                    "page_title" => $page_title, 
                    "page_content" => $page_content, 
                    "s_number" => $s_number, 
                    );
                D("Page")->where(" page_id = '$page_id' ")->save($update_data);
            }
        }

        return $page_id ;
    }

   //软删除页面
   public function softDeletePage($page_id){
    $page_id = intval($page_id) ;
      //放入回收站
      $login_user = session('login_user');
      $page = D("Page")->field("item_id,page_title")->where(" page_id = '$page_id' ")->find() ;
      D("Recycle")->add(array(
        "item_id" =>$page['item_id'],
        "page_id" =>$page_id,
        "page_title" =>$page['page_title'],
        "del_by_uid" =>$login_user['uid'],
        "del_by_username" =>$login_user['username'],
        "del_time" =>time()
        ));
      $ret = M("Page")->where(" page_id = '$page_id' ")->save(array("is_del"=>1 ,"addtime"=>time()));
      return $ret;
   }

   //删除页面
   public function deletePage($page_id){
    $page_id = intval($page_id) ;
      $ret = M("Page")->where(" page_id = '$page_id' ")->delete();
      return $ret;
   }

   public function deleteFile($file_id){
    $file_id = intval($file_id) ;
        return D("Attachment")->deleteFile($file_id) ;
    }

    //把runapi的格式内容转换为markdown格式。如果不是runapi格式，则会返回false
    //参数content为json字符串或者数组
    public function runapiToMd($content){
        if(!is_array($content) ){
          $content_json = htmlspecialchars_decode($content) ;
          $content = json_decode($content_json , true) ;
        }
        if(!$content || !$content['info'] || !$content['info']['url'] ){
            return false ;
        }
        $new_content = "
##### 简要描述
  
- ".($content['info']['description'] ? $content['info']['description'] :'无') ."
  
##### 请求URL
  
- `{$content['info']['url']}`
  
##### 请求方式
  
- {$content['info']['method']}
  ";
  
    if($content['request']['headers'] && $content['request']['headers'][0] && $content['request']['headers'][0]['name']){
        $new_content .= " 
##### Header 
  
|header|必选|类型|说明|
|:-----  |:-----|-----|
  ";
        foreach ($content['request']['headers'] as $key => $value) {
            $value['require'] = $value['require'] > 0 ? "是" : "否" ;
            $value['remark'] = $value['remark'] ? $value['remark'] : '无' ;
            $new_content .= "|{$value['name']}|  {$value['require']} |  {$value['type']} |  {$value['remark']} | \n";
        } 
    }
  
    $params = $content['request']['params'][$content['request']['params']['mode']];
    if ($params && is_array($params) && $params[0] && $params[0]['name']){
        $new_content .= " 
##### 请求参数
  
|参数名|必选|类型|说明|
|:-----  |:-----|-----|
  ";
  
    foreach ($params as $key => $value) {
        $value['require'] = $value['require'] > 0 ? "是" : "否" ;
        $value['remark'] = $value['remark'] ? $value['remark'] : '无' ;
        $new_content .= "|{$value['name']}|  {$value['require']} |  {$value['type']} |  {$value['remark']} | \n";
    }
    }
    //如果参数类型为json
    if($content['request']['params']['mode'] == 'json' && $params){
        $params = $this->_indent_json($params);
        $new_content .= " 
##### 请求参数示例  
```
{$params}
  
``` 
  "; 
    }
        // json字段说明
        $jsonDesc = $content['request']['params']['jsonDesc'] ;
        if ($content['request']['params']['mode'] == 'json' && $jsonDesc && $jsonDesc[0] && $jsonDesc[0]['name']){
            $new_content .= " 
##### json字段说明
  
|字段名|必选|类型|说明|
|:-----  |:-----|-----|
  ";
    
        foreach ($jsonDesc as $key => $value) {
            $value['require'] = $value['require'] > 0 ? "是" : "否" ;
            $value['remark'] = $value['remark'] ? $value['remark'] : '无' ;
            $new_content .= "|{$value['name']}|  {$value['require']} |  {$value['type']} |  {$value['remark']} | \n";
        }
        }
  
        //返回示例
        if($content['response']['responseExample']){
          $responseExample = $this->_indent_json($content['response']['responseExample']);
          $responseExample = $responseExample ? $responseExample : $content['response']['responseExample'] ;
            $new_content .= " 
##### 返回示例  
```
{$responseExample}
  
``` 
  "; 
        }
  
        //返回示例说明
        if($content['response']['responseParamsDesc'] && $content['response']['responseParamsDesc'][0] && $content['response']['responseParamsDesc'][0]['name']){
            $new_content .= " 
##### 返回参数说明 
  
|参数名|类型|说明|
|:-----  |:-----|-----|
  ";
            foreach ($content['response']['responseParamsDesc'] as $key => $value) {
                $value['remark'] = $value['remark'] ? $value['remark'] : '无' ;
                $new_content .= "|{$value['name']}| {$value['type']} |  {$value['remark']} | \n";
            }
        }
  
        $new_content .= " 
##### 备注
  
{$content['info']['remark']}
  ";
  
    
  
        return $new_content ;
  
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