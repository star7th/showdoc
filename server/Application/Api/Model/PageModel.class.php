<?php
namespace Api\Model;
use Api\Model\BaseModel;
/**
 * 
 * @author star7th      
 */
class PageModel extends BaseModel {

    //根据内容更新页面
    public function update_by_content($item_id,$page_title,$page_content,$cat_name,$cat_name_sub,$s_number = 99){
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

        //目录id
        $cat_id = 0 ;
        if ($cat_name_array && $cat_name_array['cat_id'] > 0 ) {
            $cat_id = $cat_name_array['cat_id'] ;
        }

        if ($cat_name_sub_array && $cat_name_sub_array['cat_id'] > 0 ) {
            $cat_id = $cat_name_sub_array['cat_id'] ;
        }

        if ($page_content) {
            $page_array = D("Page")->where(" item_id = '$item_id'  and cat_id = '$cat_id'  and page_title ='%s' ",array($page_title))->find();
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

	
}