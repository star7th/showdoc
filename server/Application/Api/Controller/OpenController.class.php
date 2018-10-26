<?php
namespace Api\Controller;
use Think\Controller;
class OpenController extends BaseController {

    //根据内容更新项目
    public function updateItem(){
        $api_key = I("api_key");
        $api_token = I("api_token");
        $cat_name = I("cat_name")?I("cat_name"):'';
        $cat_name_sub = I("cat_name_sub");
        $page_title = I("page_title");
        $page_content = I("page_content");
        $s_number = I("s_number") ? I("s_number") : 99;

        $item_id = D("ItemToken")->check($api_key , $api_token);
        if (!$item_id) {
            //没验证通过
            $this->sendError(10306);
            return false;
        }

        //兼容之前的cat_name_sub参数
        if ($cat_name_sub) {
            $cat_name = $cat_name .'/'.$cat_name_sub ;
        }

        $page_id = D("Page")->update_by_content($item_id,$page_title,$page_content,$cat_name,$s_number);

        if ($page_id) {
            $ret = D("Page")->where(" page_id = '$page_id' ")->find();
            $this->sendResult($ret);
        }else{
            $this->sendError(10101);
        }
    }

    //根据shell上报的数据库结构信息生成数据字典
    public function updateDbItem(){
        $api_key = I("api_key");
        $api_token = I("api_token");
        $table_info = I("table_info");
        $table_detail = I("table_detail");
        $s_number = I("s_number") ? I("s_number") : 99;
        $cat_name = I("cat_name") ? I("cat_name") : '';
        header( 'Content-Type:text/html;charset=utf-8 ');
        $cat_name = str_replace(PHP_EOL, '', $cat_name);
        $item_id = D("ItemToken")->check($api_key , $api_token);
        if (!$item_id) {
            //没验证通过
            echo "api_key或者api_token不匹配\n";
            return false;
        }

        $tables = $this->_analyze_db_structure_to_array($table_info ,$table_detail);
        if (!empty($tables)) {
            foreach ($tables as $key => $value) {
                $page_title = $value['table_name'] ;
                $page_content = $value['markdown'] ;
                $result = D("Page")->update_by_content($item_id,$page_title,$page_content,$cat_name,$s_number);
            }
        }

        if (!empty($result)) {
            echo "成功\n";
        }else{
            echo "失败\n";
        }

        //$this->_record_log();
        
    }

    //通过注释生成api文档
    public function fromComments(){
        R("FromComments/generate");
    }

    
    private function _analyze_db_structure_to_array($table_info , $table_detail){
        $tables = array();

        //解析table_info
        $array = explode("\n", $table_info);
        if(!empty($array)){
            foreach ($array as $key => $value) {
                if ($key == 0) {
                    continue;
                }
                $array2 = explode("\t", $value);
                $table_name = str_replace(PHP_EOL, '', $array2[0]); 
                $tables[$array2[0]] = array(
                    "table_name" => $table_name ,
                    "table_comment" => $array2[1] ,
                    );
            }
        }



        //解析table_detail
        $array = explode("\n", $table_detail);
        if(!empty($array)){
            foreach ($array as $key => $value) {
                if ($key == 0) {
                    continue;
                }
                $array2 = explode("\t", $value);

                $tables[$array2[0]]['columns'][$array2[1]] = array(
                    "column_name" => $array2[1] ,
                    "default" => $array2[2] ,
                    "is_nullable" => $array2[3] ,
                    "column_type" => $array2[4] ,
                    "column_comment" => $array2[5] ? $array2[5] : '无' ,
                    );

            }
        }


        //生成markdown内容放在数组里
        if (!empty($tables)) {
            foreach ($tables as $key => $value) {
                $markdown = '';
                $markdown .= "- {$value['table_comment']} \n \n" ;
                $markdown .= "|字段|类型|允许空|默认|注释| \n ";
                $markdown .= "|:----    |:-------    |:--- |----|------      | \n ";
                foreach ($value['columns'] as $key2 => $value2) {
                    $markdown .= "|{$value2['column_name']} |{$value2['column_type']} |{$value2['is_nullable']} | {$value2['default']} | {$value2['column_comment']}  | \n ";
                }

                $tables[$key]['markdown'] = $markdown ;

            }
        }
        return $tables;
    }


}
