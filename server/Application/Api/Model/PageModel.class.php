<?php
namespace Api\Model;
use Api\Model\BaseModel;
/**
 * 
 * @author star7th      
 */
class PageModel extends BaseModel {

    protected $cat_name_id = array();

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
      $ret = M("Page")->where(" page_id = '$page_id' ")->save(array("is_del"=>1 ,"addtime"=>time()));
      return $ret;
   }

   //删除页面
   public function deletePage($page_id){
      $ret = M("Page")->where(" page_id = '$page_id' ")->delete();
      return $ret;
   }

}